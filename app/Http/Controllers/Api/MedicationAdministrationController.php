<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicationAdministration;
use App\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class MedicationAdministrationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MedicationAdministration::with(['medication', 'resident', 'branch', 'administeredBy']);
        $user = $request->user();

        if ($user && $user->hasRole('caregiver')) {
            if ($user->assigned_branch_id) {
                $query->where('branch_id', $user->assigned_branch_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filter by medication
        if ($request->has('medication_id')) {
            $query->where('medication_id', $request->get('medication_id'));
        }

        // Filter by resident
        if ($request->has('resident_id')) {
            $residentId = $request->get('resident_id');

            if ($user && $user->hasRole('caregiver')) {
                $residentBranch = \App\Models\Resident::where('id', $residentId)->value('branch_id');

                if ($user->assigned_branch_id && (int) $residentBranch !== (int) $user->assigned_branch_id) {
                    return response()->json([
                        'message' => 'You do not have permission to view medication history for this resident.',
                    ], 403);
                }
            }

            $query->where('resident_id', $residentId);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by date
        if ($request->has('date_from')) {
            $query->whereDate('administered_at', '>=', $request->get('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('administered_at', '<=', $request->get('date_to'));
        }

        // Filter by today
        if ($request->has('today') && $request->get('today') === 'true') {
            $query->whereDate('administered_at', today());
        }

        $perPage = (int) $request->get('per_page', 25);
        $perPage = max(1, min(100, $perPage));

        $administrations = $query->orderBy('administered_at', 'desc')
            ->paginate($perPage);

        return response()->json($administrations);
    }

    public function show($id): JsonResponse
    {
        $administration = MedicationAdministration::with(['medication', 'resident', 'branch', 'administeredBy'])
            ->findOrFail($id);

        return response()->json($administration);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'medication_id' => 'required|exists:medications,id',
            'resident_id' => 'required|exists:residents,id',
            'branch_id' => 'required|exists:branches,id',
            'administered_at' => 'required|date',
            'status' => 'required|in:completed,missed,refused',
            'dosage_given' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Get medication to validate resident matches and enforce rules
        $medication = Medication::findOrFail($validated['medication_id']);
        if ($medication->resident_id != $validated['resident_id']) {
            return response()->json([
                'message' => 'Resident does not match medication resident'
            ], 422);
        }

        $validated['administered_by'] = auth()->id();

        // If no administered_at time, use current time
        if (!isset($validated['administered_at']) || empty($validated['administered_at'])) {
            $validated['administered_at'] = Carbon::now();
        }

        $administeredAt = Carbon::parse($validated['administered_at']);

        // Enforce date range: cannot administer before start_date or after end_date (if set)
        if ($medication->start_date && $administeredAt->lt(Carbon::parse($medication->start_date)->startOfDay())) {
            return response()->json([
                'message' => 'Medication cannot be administered before its start date.'
            ], 422);
        }
        if ($medication->end_date && $administeredAt->gt(Carbon::parse($medication->end_date)->endOfDay())) {
            return response()->json([
                'message' => 'Medication administration period has ended.'
            ], 422);
        }

        // Enforce daily frequency based on instructions
        $instruction = strtolower((string) $medication->instructions);
        $allowedPerDay = null; // null means unlimited (e.g., PRN)
        if (in_array($instruction, ['b.i.d', 'bid', 'b.i.d.'])) {
            $allowedPerDay = 2;
        } elseif (in_array($instruction, ['t.i.d', 'tid', 't.i.d.'])) {
            $allowedPerDay = 3;
        } elseif (in_array($instruction, ['q.i.d', 'qid', 'q.i.d.'])) {
            $allowedPerDay = 4;
        } elseif (in_array($instruction, ['a.m', 'am', 'p.m', 'pm', 'h.s', 'hs'])) {
            $allowedPerDay = 1;
        } elseif ($instruction === 'prn') {
            $allowedPerDay = null; // as needed
        }

        if (!is_null($allowedPerDay)) {
            $countToday = MedicationAdministration::where('medication_id', $medication->id)
                ->whereDate('administered_at', $administeredAt->toDateString())
                ->count();

            if ($countToday >= $allowedPerDay) {
                return response()->json([
                    'message' => 'Daily administration limit reached for this medication.'
                ], 422);
            }
        }

        $administration = MedicationAdministration::create($validated);

        return response()->json($administration->load(['medication', 'resident', 'branch', 'administeredBy']), 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $administration = MedicationAdministration::findOrFail($id);

        $validated = $request->validate([
            'administered_at' => 'sometimes|date',
            'status' => 'sometimes|in:completed,missed,refused',
            'dosage_given' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $administration->update($validated);

        return response()->json($administration->load(['medication', 'resident', 'branch', 'administeredBy']));
    }

    public function destroy($id): JsonResponse
    {
        $administration = MedicationAdministration::findOrFail($id);
        $administration->delete();

        return response()->json(['message' => 'Medication administration deleted successfully']);
    }
}

