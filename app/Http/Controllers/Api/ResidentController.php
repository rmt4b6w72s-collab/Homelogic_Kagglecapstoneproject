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

        // Filter by branch
        if ($request->has('branch_id') && !empty($request->get('branch_id'))) {
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

        $perPage = $request->get('per_page', 50);
        $residents = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($residents);
    }

    public function show($id): JsonResponse
    {
        $resident = Resident::with(['branch', 'appointments', 'vitalSigns'])
            ->findOrFail($id);

        return response()->json($resident);
    }

    public function appointments($id): JsonResponse
    {
        $resident = Resident::findOrFail($id);
        $appointments = $resident->appointments()
            ->with(['healthcareProvider'])
            ->orderBy('appointment_date', 'desc')
            ->paginate(15);

        return response()->json($appointments);
    }

    public function vitals($id): JsonResponse
    {
        $resident = Resident::findOrFail($id);
        $vitals = $resident->vitalSigns()
            ->orderBy('measurement_date', 'desc')
            ->paginate(15);

        return response()->json($vitals);
    }

    public function store(Request $request): JsonResponse
    {
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
            'status' => 'nullable|string|max:50',
            'is_active' => 'boolean',
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
            'allergies' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'physician_name' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
            'is_active' => 'boolean',
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

