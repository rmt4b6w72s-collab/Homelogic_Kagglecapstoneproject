<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FireDrill;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class FireDrillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FireDrill::with(['branch', 'createdBy']);
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

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('scheduled_date', '>=', $request->get('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('scheduled_date', '<=', $request->get('date_to'));
        }

        // Filter upcoming drills
        if ($request->has('upcoming') && $request->get('upcoming') === 'true') {
            $query->where('status', 'scheduled')
                  ->whereDate('scheduled_date', '>=', now()->toDateString());
        }

        $perPage = (int) $request->get('per_page', 50);
        $perPage = max(1, min(100, $perPage));
        $drills = $query->orderBy('scheduled_date', 'desc')
                       ->orderBy('scheduled_time', 'desc')
                       ->paginate($perPage);

        return response()->json($drills);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i:s',
            'status' => 'required|in:scheduled,completed,cancelled',
            'notes' => 'nullable|string',
            'completed_at' => 'nullable|date',
        ]);

        $validated['created_by'] = auth()->id();

        // Set completed_at if status is completed
        if ($validated['status'] === 'completed' && !isset($validated['completed_at'])) {
            $validated['completed_at'] = now();
        }

        $drill = FireDrill::create($validated);

        return response()->json($drill->load(['branch', 'createdBy']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        $drill = FireDrill::with(['branch', 'createdBy'])->findOrFail($id);
        return response()->json($drill);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $drill = FireDrill::findOrFail($id);

        $validated = $request->validate([
            'branch_id' => 'sometimes|required|exists:branches,id',
            'scheduled_date' => 'sometimes|required|date',
            'scheduled_time' => 'sometimes|required|date_format:H:i:s',
            'status' => 'sometimes|required|in:scheduled,completed,cancelled',
            'notes' => 'nullable|string',
            'completed_at' => 'nullable|date',
        ]);

        // Set completed_at if status is being changed to completed
        if (isset($validated['status']) && $validated['status'] === 'completed' && !isset($validated['completed_at'])) {
            $validated['completed_at'] = now();
        }

        // Clear completed_at if status is changed from completed
        if (isset($validated['status']) && $validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }

        $drill->update($validated);

        return response()->json($drill->load(['branch', 'createdBy']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $drill = FireDrill::findOrFail($id);
        $drill->delete();

        return response()->json(['message' => 'Fire drill deleted successfully']);
    }
}
