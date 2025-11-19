<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicationDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MedicationDeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MedicationDelivery::with(['branch', 'resident', 'medication', 'receivedBy']);
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

        // Filter by delivery type
        if ($request->has('delivery_type')) {
            $query->where('delivery_type', $request->get('delivery_type'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('received_date', '>=', $request->get('from_date'));
        }
        if ($request->has('to_date')) {
            $query->whereDate('received_date', '<=', $request->get('to_date'));
        }

        $perPage = (int) $request->get('per_page', 50);
        $perPage = max(1, min(100, $perPage));
        $deliveries = $query->orderBy('received_date', 'desc')->paginate($perPage);

        return response()->json($deliveries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'delivery_type' => 'required|in:individual,batch',
            'resident_id' => 'nullable|exists:residents,id',
            'medication_id' => 'nullable|exists:medications,id',
            'pharmacy_name' => 'required|string|max:255',
            'quantity_received' => 'required|string|max:255',
            'received_date' => 'required|date',
            'received_time' => 'required|string',
            'status' => 'nullable|in:received,verified,stored',
            'notes' => 'nullable|string',
        ]);

        // Validate medication_id is required for individual deliveries
        if ($validated['delivery_type'] === 'individual' && empty($validated['medication_id'])) {
            return response()->json([
                'message' => 'Medication is required for individual deliveries.',
            ], 422);
        }

        $validated['received_by'] = auth()->id();
        $validated['status'] = $validated['status'] ?? 'received';

        $delivery = MedicationDelivery::create($validated);

        return response()->json($delivery->load(['branch', 'resident', 'medication', 'receivedBy']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $delivery = MedicationDelivery::with(['branch', 'resident', 'medication', 'receivedBy'])
            ->findOrFail($id);

        $user = request()->user();
        $isCaregiver = $user && in_array($user->role, ['caregiver', 'care_giver', 'nurse', 'registered_nurse', 'licensed_nurse']);
        if ($isCaregiver && $user->assigned_branch_id && (int) $delivery->branch_id !== (int) $user->assigned_branch_id) {
            return response()->json([
                'message' => 'You do not have permission to view this delivery.',
            ], 403);
        }

        return response()->json($delivery);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $delivery = MedicationDelivery::findOrFail($id);

        $validated = $request->validate([
            'branch_id' => 'sometimes|exists:branches,id',
            'delivery_type' => 'sometimes|in:individual,batch',
            'resident_id' => 'nullable|exists:residents,id',
            'medication_id' => 'nullable|exists:medications,id',
            'pharmacy_name' => 'sometimes|string|max:255',
            'quantity_received' => 'sometimes|string|max:255',
            'received_date' => 'sometimes|date',
            'received_time' => 'sometimes|string',
            'status' => 'sometimes|in:received,verified,stored',
            'notes' => 'nullable|string',
        ]);

        // Validate medication_id is required for individual deliveries
        if (isset($validated['delivery_type']) && $validated['delivery_type'] === 'individual' && empty($validated['medication_id'])) {
            return response()->json([
                'message' => 'Medication is required for individual deliveries.',
            ], 422);
        }

        $delivery->update($validated);

        return response()->json($delivery->load(['branch', 'resident', 'medication', 'receivedBy']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $delivery = MedicationDelivery::findOrFail($id);
        $delivery->delete();

        return response()->json(['message' => 'Medication delivery deleted successfully']);
    }
}
