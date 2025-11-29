<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffClockIn;
use App\Models\User;
use App\Services\LocationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StaffClockInController extends Controller
{
    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Clock in (authenticated users)
     */
    public function clockIn(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if already clocked in
        if ($user->hasActiveClockIn()) {
            return response()->json([
                'message' => 'You are already clocked in',
                'clock_in' => $user->activeClockIn,
            ], 422);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Validate location
        $locationError = $this->locationService->validateCheckInLocation(
            $user,
            $validated['latitude'],
            $validated['longitude']
        );

        if ($locationError) {
            return response()->json($locationError, 422);
        }

        // Create clock-in record
        $clockIn = StaffClockIn::create([
            'staff_id' => $user->id,
            'branch_id' => $user->assigned_branch_id,
            'facility_id' => $user->facility_id,
            'clock_in_at' => now(),
            'clock_in_latitude' => $validated['latitude'],
            'clock_in_longitude' => $validated['longitude'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
            'clock_method' => 'authenticated',
        ]);

        return response()->json([
            'message' => 'Successfully clocked in',
            'clock_in' => $clockIn->load(['staff', 'branch', 'facility']),
        ], 201);
    }

    /**
     * Clock out (authenticated users)
     */
    public function clockOut(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $activeClockIn = $user->activeClockIn;

        if (!$activeClockIn) {
            return response()->json([
                'message' => 'You are not currently clocked in',
            ], 422);
        }

        $validated = $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Clock out
        $activeClockIn->clockOut(
            $validated['latitude'] ?? null,
            $validated['longitude'] ?? null
        );

        if (isset($validated['notes'])) {
            $activeClockIn->notes = ($activeClockIn->notes ? $activeClockIn->notes . "\n" : '') . $validated['notes'];
            $activeClockIn->save();
        }

        return response()->json([
            'message' => 'Successfully clocked out',
            'clock_in' => $activeClockIn->fresh()->load(['staff', 'branch', 'facility']),
        ]);
    }

    /**
     * Clock out a staff member (admin only)
     */
    public function clockOutStaff(Request $request, $clockInId): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if user is admin
        $isAdmin = $user->role === 'super_admin' || $user->role === 'administrator' || $user->hasRole('administrator');
        
        if (!$isAdmin) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        $clockIn = StaffClockIn::findOrFail($clockInId);

        if (!$clockIn->is_active) {
            return response()->json([
                'message' => 'Staff member is not currently clocked in',
            ], 422);
        }

        $validated = $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Clock out
        $clockIn->clockOut(
            $validated['latitude'] ?? null,
            $validated['longitude'] ?? null
        );

        if (isset($validated['notes'])) {
            $clockIn->notes = ($clockIn->notes ? $clockIn->notes . "\n" : '') . '[Admin clock-out] ' . $validated['notes'];
            $clockIn->save();
        }

        return response()->json([
            'message' => 'Successfully clocked out staff member',
            'clock_in' => $clockIn->fresh()->load(['staff', 'branch', 'facility']),
        ]);
    }

    /**
     * Get current active clock-in
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $activeClockIn = $user->activeClockIn;

        if (!$activeClockIn) {
            return response()->json([
                'clocked_in' => false,
            ]);
        }

        return response()->json([
            'clocked_in' => true,
            'clock_in' => $activeClockIn->load(['staff', 'branch', 'facility']),
        ]);
    }

    /**
     * List clock-ins
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = StaffClockIn::with(['staff', 'branch', 'facility']);

        $isAdmin = $user->role === 'super_admin' || $user->role === 'administrator' || $user->hasRole('administrator');

        // Apply facility filtering for non-super admins
        if ($user->role !== 'super_admin' && $user->facility_id) {
            $query->where('facility_id', $user->facility_id);
        }

        // Filter by staff
        if ($request->filled('staff_id')) {
            if ($isAdmin) {
                // Admins can filter by any staff in their facility
                $query->where('staff_id', $request->get('staff_id'));
            } else {
                // Regular users can only see their own
                $query->where('staff_id', $user->id);
            }
        } else {
            // If no staff_id specified:
            if ($isAdmin) {
                // Admins see all staff clock-ins from their facility (no filter)
                // Facility filter already applied above
            } else {
                // Regular users see only their own
                $query->where('staff_id', $user->id);
            }
        }

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('clock_in_at', '>=', $request->get('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('clock_in_at', '<=', $request->get('end_date'));
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min(100, max(1, (int) $request->get('per_page', 50)));
        $clockIns = $query->orderBy('clock_in_at', 'desc')->paginate($perPage);

        return response()->json($clockIns);
    }

    /**
     * Get stats
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $staffId = $request->filled('staff_id') && ($user->role === 'super_admin' || $user->role === 'administrator')
            ? $request->get('staff_id')
            : $user->id;

        $today = Carbon::today();
        $thisWeek = Carbon::today()->startOfWeek();
        $thisMonth = Carbon::today()->startOfMonth();

        // Today's hours
        $todayClockIns = StaffClockIn::where('staff_id', $staffId)
            ->whereDate('clock_in_at', $today)
            ->get();

        $todayHours = $todayClockIns->sum(function ($clockIn) {
            if ($clockIn->clock_out_at) {
                return $clockIn->total_hours ?? 0;
            }
            // If still clocked in, calculate from clock_in_at to now
            return round($clockIn->clock_in_at->diffInMinutes(now()) / 60, 2);
        });

        // This week's hours
        $weekClockIns = StaffClockIn::where('staff_id', $staffId)
            ->where('clock_in_at', '>=', $thisWeek)
            ->whereNotNull('clock_out_at')
            ->get();

        $weekHours = $weekClockIns->sum('total_hours') ?? 0;

        // This month's hours
        $monthClockIns = StaffClockIn::where('staff_id', $staffId)
            ->where('clock_in_at', '>=', $thisMonth)
            ->whereNotNull('clock_out_at')
            ->get();

        $monthHours = $monthClockIns->sum('total_hours') ?? 0;

        return response()->json([
            'today_hours' => round($todayHours, 2),
            'week_hours' => round($weekHours, 2),
            'month_hours' => round($monthHours, 2),
            'is_clocked_in' => $user->hasActiveClockIn(),
        ]);
    }
}

