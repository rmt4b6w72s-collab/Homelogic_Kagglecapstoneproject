<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ResidentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Resident::with(['branch']);
        $user = $request->user();
        $caregiverBranchId = null;

        // Check if user is a caregiver (including all caregiver-related roles)
        $isCaregiver = $user && in_array($user->role, ['caregiver', 'care_giver', 'nurse', 'registered_nurse', 'licensed_nurse']);
        
        if ($isCaregiver) {
            $caregiverBranchId = (int) ($user->assigned_branch_id ?? 0);

            if ($caregiverBranchId === 0) {
                // No branch assignment means the caregiver should not see any residents
                $query->whereRaw('1 = 0');
            } else {
                if ($request->filled('branch_id') && (int) $request->get('branch_id') !== $caregiverBranchId) {
                    return response()->json([
                        'message' => 'You may only view residents in your assigned branch.',
                    ], 403);
                }

                $query->where('branch_id', $caregiverBranchId);
            }
        }

        // Search
        if ($request->has('search') && !empty($request->get('search'))) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('middle_names', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('room_number', 'like', "%{$search}%")
                  ->orWhere('room', 'like', "%{$search}%");
            });
        }

        // Filter by branch (only for non-caregivers - caregivers are already filtered above)
        if (!$isCaregiver && $request->has('branch_id') && !empty($request->get('branch_id'))) {
            $query->where('branch_id', $request->get('branch_id'));
        }

        // Filter by status
        if ($request->has('status')) {
            if ($request->get('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->get('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Only filter by active if explicitly requested and show_all is not set
        if (!$request->has('show_all') && !$request->has('status')) {
            // Default: show active residents, but allow all if show_all is set
            $query->where('is_active', true);
        }

        $perPage = (int) $request->get('per_page', 50);
        $perPage = max(1, min(100, $perPage));
        $residents = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($residents);
    }

    public function show($id): JsonResponse
    {
        $resident = Resident::with([
                'branch',
                'appointments',
                'vitalSigns',
                'sleepRecords',
                'sleepPatterns',
            ])
            ->findOrFail($id);

        $user = request()->user();
        $isCaregiver = $user && in_array($user->role, ['caregiver', 'care_giver', 'nurse', 'registered_nurse', 'licensed_nurse']);
        if ($isCaregiver) {
            $caregiverBranchId = (int) ($user->assigned_branch_id ?? 0);
            if ($caregiverBranchId === 0 || (int) $resident->branch_id !== $caregiverBranchId) {
                return response()->json([
                    'message' => 'You do not have permission to view this resident.',
                ], 403);
            }
        }

        return response()->json($resident);
    }

    public function appointments($id): JsonResponse
    {
        $resident = Resident::findOrFail($id);
        $user = request()->user();
        $isCaregiver = $user && in_array($user->role, ['caregiver', 'care_giver', 'nurse', 'registered_nurse', 'licensed_nurse']);
        if ($isCaregiver) {
            $caregiverBranchId = (int) ($user->assigned_branch_id ?? 0);
            if ($caregiverBranchId === 0 || (int) $resident->branch_id !== $caregiverBranchId) {
                return response()->json([
                    'message' => 'You do not have permission to view appointments for this resident.',
                ], 403);
            }
        }

        $appointments = $resident->appointments()
            ->with(['healthcareProvider'])
            ->orderBy('appointment_date', 'desc')
            ->paginate(15);

        return response()->json($appointments);
    }

    public function vitals($id): JsonResponse
    {
        $resident = Resident::findOrFail($id);
        $user = request()->user();
        $isCaregiver = $user && in_array($user->role, ['caregiver', 'care_giver', 'nurse', 'registered_nurse', 'licensed_nurse']);
        if ($isCaregiver) {
            $caregiverBranchId = (int) ($user->assigned_branch_id ?? 0);
            if ($caregiverBranchId === 0 || (int) $resident->branch_id !== $caregiverBranchId) {
                return response()->json([
                    'message' => 'You do not have permission to view vitals for this resident.',
                ], 403);
            }
        }

        $vitals = $resident->vitalSigns()
            ->orderBy('measurement_date', 'desc')
            ->paginate(15);

        return response()->json($vitals);
    }

    public function store(Request $request): JsonResponse
    {
        // Convert is_active from FormData string to boolean if present
        if ($request->has('is_active')) {
            $request->merge(['is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)]);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_names' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:50',
            'room_number' => 'nullable|string|max:50',
            'branch_id' => 'required|exists:branches,id',
            'admission_date' => 'required|date',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'diagnosis' => 'nullable|string',
            'allergies' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'physician_name' => 'nullable|string|max:255',
            'medicare_number' => 'nullable|string|max:255',
            'primary_care_doctor' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('residents/profile_images', $imageName, 'public');
            $validated['profile_image'] = $imagePath;
        }

        // Handle array fields that come as strings - convert to arrays if needed
        if (isset($validated['medical_conditions']) && is_string($validated['medical_conditions'])) {
            $validated['medical_conditions'] = !empty(trim($validated['medical_conditions'])) 
                ? [$validated['medical_conditions']] 
                : null;
        }
        if (isset($validated['allergies']) && is_string($validated['allergies'])) {
            $validated['allergies'] = !empty(trim($validated['allergies'])) 
                ? [$validated['allergies']] 
                : null;
        }

        // Auto-generate name if not provided
        if (!isset($validated['name'])) {
            $parts = array_filter([$validated['first_name'], $validated['middle_names'] ?? null, $validated['last_name']]);
            $validated['name'] = implode(' ', $parts);
        }

        $resident = Resident::create($validated);

        return response()->json($resident->load(['branch']), 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $resident = Resident::findOrFail($id);

        // Convert is_active from FormData string to boolean if present
        if ($request->has('is_active')) {
            $request->merge(['is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)]);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'middle_names' => 'nullable|string|max:255',
            'date_of_birth' => 'sometimes|required|date',
            'gender' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:50',
            'room_number' => 'nullable|string|max:50',
            'branch_id' => 'sometimes|exists:branches,id',
            'admission_date' => 'sometimes|required|date',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'diagnosis' => 'nullable|string',
            'allergies' => 'nullable',
            'medical_conditions' => 'nullable',
            'physician_name' => 'nullable|string|max:255',
            'medicare_number' => 'nullable|string|max:255',
            'primary_care_doctor' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image if it exists
            if ($resident->profile_image && \Storage::disk('public')->exists($resident->profile_image)) {
                \Storage::disk('public')->delete($resident->profile_image);
            }
            
            $image = $request->file('profile_image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('residents/profile_images', $imageName, 'public');
            $validated['profile_image'] = $imagePath;
        }

        // Handle array fields - convert to arrays if they come as strings or ensure they're arrays
        if (isset($validated['medical_conditions'])) {
            if (is_string($validated['medical_conditions'])) {
                $validated['medical_conditions'] = !empty(trim($validated['medical_conditions'])) 
                    ? [$validated['medical_conditions']] 
                    : null;
            } elseif (is_array($validated['medical_conditions'])) {
                // Filter out empty values and ensure it's a clean array
                $validated['medical_conditions'] = array_filter($validated['medical_conditions'], function($item) {
                    return !empty(trim($item));
                });
                $validated['medical_conditions'] = !empty($validated['medical_conditions']) 
                    ? array_values($validated['medical_conditions']) 
                    : null;
            }
        }
        
        if (isset($validated['allergies'])) {
            if (is_string($validated['allergies'])) {
                $validated['allergies'] = !empty(trim($validated['allergies'])) 
                    ? [$validated['allergies']] 
                    : null;
            } elseif (is_array($validated['allergies'])) {
                // Filter out empty values and ensure it's a clean array
                $validated['allergies'] = array_filter($validated['allergies'], function($item) {
                    return !empty(trim($item));
                });
                $validated['allergies'] = !empty($validated['allergies']) 
                    ? array_values($validated['allergies']) 
                    : null;
            }
        }

        // Update name if first/last name changed
        if (isset($validated['first_name']) || isset($validated['last_name']) || isset($validated['middle_names'])) {
            $first = $validated['first_name'] ?? $resident->first_name;
            $middle = $validated['middle_names'] ?? $resident->middle_names;
            $last = $validated['last_name'] ?? $resident->last_name;
            $parts = array_filter([$first, $middle, $last]);
            $validated['name'] = implode(' ', $parts);
        }

        $resident->update($validated);

        return response()->json($resident->load(['branch']));
    }

    public function destroy($id): JsonResponse
    {
        $resident = Resident::findOrFail($id);
        $resident->delete();

        return response()->json(['message' => 'Resident deleted successfully']);
    }
}

