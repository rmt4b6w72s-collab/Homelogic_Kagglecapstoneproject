<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VitalSign;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VitalSignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = VitalSign::with(['resident', 'takenBy']);

        // Filter by date
        if ($request->has('date_from')) {
            $query->where('measurement_date', '>=', $request->get('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('measurement_date', '<=', $request->get('date_to'));
        }

        // Filter by resident
        if ($request->has('resident_id')) {
            $query->where('resident_id', $request->get('resident_id'));
        }

        // Filter by today
        if ($request->has('today') && $request->get('today') === 'true') {
            $query->whereDate('measurement_date', today());
        }

        $vitals = $query->orderBy('measurement_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($vitals);
    }

    public function show($id): JsonResponse
    {
        $vital = VitalSign::with(['resident', 'takenBy'])
            ->findOrFail($id);

        return response()->json($vital);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'resident_id' => 'required|exists:residents,id',
            'branch_id' => 'nullable|exists:branches,id',
            'measurement_date' => 'required|date',
            'systolic' => 'nullable|integer|min:0|max:300',
            'diastolic' => 'nullable|integer|min:0|max:200',
            'temperature' => 'nullable|numeric|min:90|max:110',
            'pulse' => 'nullable|integer|min:0|max:200',
            'oxygen_saturation' => 'nullable|integer|min:0|max:100',
            'pain_level' => 'nullable|integer|min:0|max:10',
            'pain_description' => 'nullable|string|max:255',
            'reason_declined' => 'nullable|string|max:255',
            'status' => 'nullable|in:approved,pending_review,declined,critical',
            'notes' => 'nullable|string',
        ]);

        // If branch_id not provided, infer from resident
        if (!isset($validated['branch_id'])) {
            $resident = \App\Models\Resident::find($validated['resident_id']);
            if ($resident) {
                $validated['branch_id'] = $resident->branch_id;
            }
        }

        // Set taken_by to current user
        $validated['taken_by'] = auth()->id();

        // Auto-determine status if not provided
        if (!isset($validated['status'])) {
            $vital = new \App\Models\VitalSign($validated);
            $validated['status'] = $vital->determineStatus();
        }

        $vital = VitalSign::create($validated);

        return response()->json($vital->load(['resident', 'takenBy']), 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $vital = VitalSign::findOrFail($id);

        $validated = $request->validate([
            'resident_id' => 'sometimes|exists:residents,id',
            'branch_id' => 'nullable|exists:branches,id',
            'measurement_date' => 'sometimes|date',
            'systolic' => 'nullable|integer|min:0|max:300',
            'diastolic' => 'nullable|integer|min:0|max:200',
            'temperature' => 'nullable|numeric|min:90|max:110',
            'pulse' => 'nullable|integer|min:0|max:200',
            'oxygen_saturation' => 'nullable|integer|min:0|max:100',
            'pain_level' => 'nullable|integer|min:0|max:10',
            'pain_description' => 'nullable|string|max:255',
            'reason_declined' => 'nullable|string|max:255',
            'status' => 'nullable|in:approved,pending_review,declined,critical',
            'notes' => 'nullable|string',
        ]);

        // Re-determine status if vital signs changed
        if (isset($validated['systolic']) || isset($validated['diastolic']) || 
            isset($validated['temperature']) || isset($validated['pulse']) || 
            isset($validated['oxygen_saturation'])) {
            $vital->fill($validated);
            $validated['status'] = $vital->determineStatus();
        }

        $vital->update($validated);

        return response()->json($vital->load(['resident', 'takenBy']));
    }

    public function destroy($id): JsonResponse
    {
        $vital = VitalSign::findOrFail($id);
        $vital->delete();

        return response()->json(['message' => 'Vital sign deleted successfully']);
    }
}

