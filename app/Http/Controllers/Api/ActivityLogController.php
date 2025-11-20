<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends BaseApiController
{
    /**
     * Get all activity logs
     */
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::with(['user', 'branch']);

        // Filter by log type
        if ($request->has('log_type') && !empty($request->get('log_type'))) {
            $query->ofType($request->get('log_type'));
        }

        // Filter by event
        if ($request->has('event') && !empty($request->get('event'))) {
            $query->event($request->get('event'));
        }

        // Filter by level
        if ($request->has('level') && !empty($request->get('level'))) {
            $query->level($request->get('level'));
        }

        // Filter by user
        if ($request->has('user_id') && !empty($request->get('user_id'))) {
            $query->forUser($request->get('user_id'));
        }

        // Filter by branch
        if ($request->has('branch_id') && !empty($request->get('branch_id'))) {
            $query->where('branch_id', $request->get('branch_id'));
        }

        // Filter by subject
        if ($request->has('subject_type') && !empty($request->get('subject_type'))) {
            $query->forSubject($request->get('subject_type'), $request->get('subject_id'));
        }

        // Date range filter
        if ($request->has('logged_from')) {
            $query->whereDate('logged_at', '>=', $request->get('logged_from'));
        }
        if ($request->has('logged_until')) {
            $query->whereDate('logged_at', '<=', $request->get('logged_until'));
        }

        // Search in description
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // If user is a caregiver, show logs for their branch only
        if (auth()->user()->hasRole('caregiver')) {
            $query->where('branch_id', auth()->user()->assigned_branch_id);
        }

        $perPage = $request->get('per_page', 50);
        $logs = $query->orderBy('logged_at', 'desc')->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Get a single activity log
     */
    public function show($id): JsonResponse
    {
        $log = ActivityLog::with(['user', 'branch', 'subject'])
            ->findOrFail($id);

        // Check permission for caregivers
        if (auth()->user()->hasRole('caregiver')) {
            if ($log->branch_id !== auth()->user()->assigned_branch_id) {
                abort(403, 'Unauthorized access to this log');
            }
        }

        return response()->json($log);
    }

    /**
     * Get activity logs for a specific subject (model)
     */
    public function forSubject(Request $request, $subjectType, $subjectId): JsonResponse
    {
        $query = ActivityLog::with(['user', 'branch'])
            ->forSubject($subjectType, $subjectId)
            ->orderBy('logged_at', 'desc');

        $perPage = $request->get('per_page', 50);
        $logs = $query->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Get stats for activity logs
     */
    public function stats(Request $request): JsonResponse
    {
        $query = ActivityLog::query();

        // If user is a caregiver, show stats for their branch only
        if (auth()->user()->hasRole('caregiver')) {
            $query->where('branch_id', auth()->user()->assigned_branch_id);
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('logged_at', '>=', $request->get('date_from'));
        }
        if ($request->has('date_until')) {
            $query->whereDate('logged_at', '<=', $request->get('date_until'));
        }

        $stats = [
            'total' => (clone $query)->count(),
            'by_type' => (clone $query)->selectRaw('log_type, count(*) as count')
                ->groupBy('log_type')
                ->pluck('count', 'log_type'),
            'by_event' => (clone $query)->selectRaw('event, count(*) as count')
                ->groupBy('event')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'event'),
            'by_level' => (clone $query)->selectRaw('level, count(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level'),
            'recent_errors' => (clone $query)->where('level', 'error')
                ->orderBy('logged_at', 'desc')
                ->limit(5)
                ->get(['id', 'description', 'logged_at']),
        ];

        return response()->json($stats);
    }
}
