<?php

namespace App\Http\Controllers\Api;

use App\Constants\UserRoles;
use App\Http\Requests\Api\Resident\StoreResidentRequest;
use App\Http\Requests\Api\Resident\UpdateResidentRequest;
use App\Http\Resources\Api\ResidentResource;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ResidentController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Resident::with(['branch']);
        $user = $request->user();
        $caregiverBranchId = null;

        // Check if user is a caregiver
        $isCaregiver = $this->isCaregiver($user);
        
        if ($isCaregiver) {
            $caregiverBranchId = (int) ($user->assigned_branch_id ?? 0);

            if ($caregiverBranchId === 0) {
                // No branch assignment means the caregiver should not see any residents
                $query->whereRaw('1 = 0');
            } else {
                if ($request->filled('branch_id') && (int) $request->get('branch_id') !== $caregiverBranchId) {
                    return $this->error('You may only view residents in your assigned branch.', 403);
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

        $query->orderBy('created_at', 'desc');
        
        $perPage = (int) $request->get('per_page', 50);
        $perPage = max(1, min(100, $perPage));
        $residents = $query->paginate($perPage);

        return response()->json([
            'data' => ResidentResource::collection($residents->items()),
            'current_page' => $residents->currentPage(),
            'per_page' => $residents->perPage(),
            'total' => $residents->total(),
            'last_page' => $residents->lastPage(),
            'from' => $residents->firstItem(),
            'to' => $residents->lastItem(),
        ]);
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
        if ($this->isCaregiver($user)) {
            $caregiverBranchId = (int) ($user->assigned_branch_id ?? 0);
            if ($caregiverBranchId === 0 || (int) $resident->branch_id !== $caregiverBranchId) {
                return $this->error('You do not have permission to view this resident.', 403);
            }
        }

        return $this->success(new ResidentResource($resident));
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

    public function store(StoreResidentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('residents/profile_images', $imageName, 'public');
            $validated['profile_image'] = $imagePath;
        }

        $resident = Resident::create($validated);

        return $this->success(
            new ResidentResource($resident->load(['branch'])),
            'Resident created successfully',
            201
        );
    }

    public function update(UpdateResidentRequest $request, $id): JsonResponse
    {
        $resident = Resident::findOrFail($id);
        $validated = $request->validated();

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image if it exists
            if ($resident->profile_image && Storage::disk('public')->exists($resident->profile_image)) {
                Storage::disk('public')->delete($resident->profile_image);
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

        return $this->success(
            new ResidentResource($resident->load(['branch'])),
            'Resident updated successfully'
        );
    }

    public function destroy($id): JsonResponse
    {
        $resident = Resident::findOrFail($id);
        $resident->delete();

        return $this->success(null, 'Resident deleted successfully');
    }
}

