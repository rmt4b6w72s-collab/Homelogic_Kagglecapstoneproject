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

        $currentUser = auth()->user();
        
        // Apply facility/branch filtering based on user role
        if ($currentUser->hasRole('caregiver')) {
            // Caregivers see only their branch logs
            $query->where('branch_id', $currentUser->assigned_branch_id);
        } elseif ($currentUser->role !== 'super_admin' && $currentUser->facility_id) {
            // Facility admins see logs from their facility only
            // Filter by logs where:
            // 1. The user who performed the action belongs to their facility, OR
            // 2. The branch associated with the log belongs to their facility
            $facilityId = $currentUser->facility_id;
            $query->where(function($q) use ($facilityId) {
                $q->whereHas('user', function($userQuery) use ($facilityId) {
                    $userQuery->where('facility_id', $facilityId);
                })->orWhereHas('branch', function($branchQuery) use ($facilityId) {
                    $branchQuery->where('facility_id', $facilityId);
                });
            });
        }
        // Super admins see all logs (no filtering)

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

        $currentUser = auth()->user();
        
        // Check permission based on user role
        if ($currentUser->hasRole('caregiver')) {
            // Caregivers can only see logs from their branch
            if ($log->branch_id !== $currentUser->assigned_branch_id) {
                abort(403, 'Unauthorized access to this log');
            }
        } elseif ($currentUser->role !== 'super_admin' && $currentUser->facility_id) {
            // Facility admins can only see logs from their facility
            $facilityId = $currentUser->facility_id;
            $hasAccess = false;
            
            // Check if the user who performed the action belongs to their facility
            if ($log->user && $log->user->facility_id === $facilityId) {
                $hasAccess = true;
            }
            
            // Check if the branch belongs to their facility
            if (!$hasAccess && $log->branch && $log->branch->facility_id === $facilityId) {
                $hasAccess = true;
            }
            
            if (!$hasAccess) {
                abort(403, 'Unauthorized access to this log');
            }
        }
        // Super admins can access all logs

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

        $currentUser = auth()->user();
        
        // Apply facility/branch filtering based on user role
        if ($currentUser->hasRole('caregiver')) {
            // Caregivers see only their branch logs
            $query->where('branch_id', $currentUser->assigned_branch_id);
        } elseif ($currentUser->role !== 'super_admin' && $currentUser->facility_id) {
            // Facility admins see logs from their facility only
            $facilityId = $currentUser->facility_id;
            $query->where(function($q) use ($facilityId) {
                $q->whereHas('user', function($userQuery) use ($facilityId) {
                    $userQuery->where('facility_id', $facilityId);
                })->orWhereHas('branch', function($branchQuery) use ($facilityId) {
                    $branchQuery->where('facility_id', $facilityId);
                });
            });
        }

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

        $currentUser = auth()->user();
        
        // Apply facility/branch filtering based on user role
        if ($currentUser->hasRole('caregiver')) {
            // Caregivers see stats for their branch only
            $query->where('branch_id', $currentUser->assigned_branch_id);
        } elseif ($currentUser->role !== 'super_admin' && $currentUser->facility_id) {
            // Facility admins see stats from their facility only
            $facilityId = $currentUser->facility_id;
            $query->where(function($q) use ($facilityId) {
                $q->whereHas('user', function($userQuery) use ($facilityId) {
                    $userQuery->where('facility_id', $facilityId);
                })->orWhereHas('branch', function($branchQuery) use ($facilityId) {
                    $branchQuery->where('facility_id', $facilityId);
                });
            });
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
