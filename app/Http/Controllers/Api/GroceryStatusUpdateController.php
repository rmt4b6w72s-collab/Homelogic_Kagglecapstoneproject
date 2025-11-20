<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroceryStatusUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class GroceryStatusUpdateController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = GroceryStatusUpdate::with(['branch', 'updatedBy']);
        $user = $request->user();
        $isCaregiver = $user && in_array($user->role, ['caregiver', 'care_giver', 'nurse', 'registered_nurse', 'licensed_nurse']);
        
        // Filter by branch for caregivers
        if ($isCaregiver && $user->assigned_branch_id) {
            $query->where('branch_id', $user->assigned_branch_id);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by week
        if ($request->has('week_start_date')) {
            $query->where('week_start_date', $request->get('week_start_date'));
        }

        // Get latest status for a week
        if ($request->has('latest') && $request->has('branch_id') && $request->has('week_start_date')) {
            $update = $query->latestForWeek($request->get('branch_id'), $request->get('week_start_date'))->first();
            return response()->json($update);
        }

        $perPage = (int) $request->get('per_page', 50);
        $perPage = max(1, min(100, $perPage));
        $updates = $query->orderBy('week_start_date', 'desc')->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($updates);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'week_start_date' => 'required|date',
            'status' => 'required|in:pending,in_progress,completed,needs_attention',
            'items_needed' => 'nullable|string',
            'items_received' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Ensure week_start_date is Monday
        $date = Carbon::parse($validated['week_start_date']);
        $validated['week_start_date'] = $date->startOfWeek(Carbon::MONDAY)->toDateString();

        $validated['updated_by'] = auth()->id();

        // Set completed_at if status is completed
        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $update = GroceryStatusUpdate::create($validated);

        return response()->json($update->load(['branch', 'updatedBy']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $update = GroceryStatusUpdate::with(['branch', 'updatedBy'])
            ->findOrFail($id);

        $user = request()->user();
        $isCaregiver = $user && in_array($user->role, ['caregiver', 'care_giver', 'nurse', 'registered_nurse', 'licensed_nurse']);
        if ($isCaregiver && $user->assigned_branch_id && (int) $update->branch_id !== (int) $user->assigned_branch_id) {
            return response()->json([
                'message' => 'You do not have permission to view this update.',
            ], 403);
        }

        return response()->json($update);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $update = GroceryStatusUpdate::findOrFail($id);

        // Check permissions
        $user = request()->user();
        $isCaregiver = $user && in_array($user->role, ['caregiver', 'care_giver', 'nurse', 'registered_nurse', 'licensed_nurse']);
        if ($isCaregiver && $update->updated_by !== $user->id) {
            return response()->json([
                'message' => 'You can only edit your own updates.',
            ], 403);
        }

        $validated = $request->validate([
            'branch_id' => 'sometimes|exists:branches,id',
            'week_start_date' => 'sometimes|date',
            'status' => 'sometimes|in:pending,in_progress,completed,needs_attention',
            'items_needed' => 'nullable|string',
            'items_received' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Ensure week_start_date is Monday if provided
        if (isset($validated['week_start_date'])) {
            $date = Carbon::parse($validated['week_start_date']);
            $validated['week_start_date'] = $date->startOfWeek(Carbon::MONDAY)->toDateString();
        }

        // Set completed_at if status is completed
        if (isset($validated['status']) && $validated['status'] === 'completed' && !$update->completed_at) {
            $validated['completed_at'] = now();
        }

        $update->update($validated);

        return response()->json($update->load(['branch', 'updatedBy']));
    }

    /**
     * Update status only (quick status update).
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $update = GroceryStatusUpdate::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,needs_attention',
        ]);

        $update->status = $validated['status'];
        
        // Set completed_at if status is completed
        if ($validated['status'] === 'completed' && !$update->completed_at) {
            $update->completed_at = now();
        } elseif ($validated['status'] !== 'completed') {
            $update->completed_at = null;
        }

        $update->save();

        return response()->json($update->load(['branch', 'updatedBy']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $update = GroceryStatusUpdate::findOrFail($id);
        
        // Only admins can delete
        $user = request()->user();
        if (!$user->hasRole('administrator') && !$user->hasRole('super_admin')) {
            return response()->json([
                'message' => 'You do not have permission to delete updates.',
            ], 403);
        }

        $update->delete();

        return response()->json(['message' => 'Grocery status update deleted successfully']);
    }
}
