<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LeaveRequest::with(['staff', 'approvedBy']);
        
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }
        
        $leaves = $query->orderBy('start_date', 'desc')
            ->paginate($request->get('per_page', 15));
        
        return response()->json($leaves);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);
        $leave = LeaveRequest::create($validated);
        return response()->json($leave->load(['staff', 'approvedBy']), 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $leave = LeaveRequest::findOrFail($id);
        $validated = $request->validate([
            'staff_id' => 'sometimes|exists:users,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,rejected',
            'approved_by' => 'nullable|exists:users,id',
        ]);
        $leave->update($validated);
        return response()->json($leave->load(['staff', 'approvedBy']));
    }

    public function destroy($id): JsonResponse
    {
        LeaveRequest::findOrFail($id)->delete();
        return response()->json(['message' => 'Leave request deleted']);
    }
}


