<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FacilityRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FacilityRegistrationController extends Controller
{
    /**
     * Store a new facility registration (public endpoint)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'facility_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'requested_subdomain' => 'nullable|string|max:255|alpha_dash|unique:facilities,subdomain',
        ]);

        // Check if email already has a pending registration
        $existing = FacilityRegistration::where('email', $validated['email'])
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'email' => 'You already have a pending registration with this email address.',
            ]);
        }

        $registration = FacilityRegistration::create([
            'facility_name' => $validated['facility_name'],
            'contact_name' => $validated['contact_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'requested_subdomain' => $validated['requested_subdomain'] ?? null,
            'status' => 'pending',
        ]);

        // TODO: Send notification to super admins
        // Notification::send(User::where('role', 'super_admin')->get(), new NewFacilityRegistration($registration));

        return response()->json([
            'message' => 'Registration submitted successfully. Our team will review your request.',
            'registration' => $registration
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $query = FacilityRegistration::query();

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('facility_name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $registrations = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($registrations);
    }

    public function show($id): JsonResponse
    {
        return response()->json(FacilityRegistration::findOrFail($id));
    }

    public function approve(Request $request, $id): JsonResponse
    {
        $registration = FacilityRegistration::findOrFail($id);
        
        if ($registration->status !== 'pending') {
            return response()->json(['message' => 'Registration already processed'], 400);
        }

        $validated = $request->validate([
            'facility_name' => 'required|string|max:255',
            'subdomain' => 'required|string|max:255|unique:facilities,subdomain',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'branch_name' => 'required|string|max:255',
            'branch_address' => 'nullable|string',
            'owner_name' => 'required|string|max:255',
            // Email validation - uniqueness will be checked scoped by facility_id when facility is created
            'owner_email' => 'required|email',
            'owner_role' => 'required|string|in:administrator,manager,clinical_supervisor',
            'owner_password' => 'required|string|min:8',
        ]);

        // This will be handled by the backend service
        // For now, return the validated data for frontend to handle
        return response()->json([
            'message' => 'Approval data validated',
            'data' => $validated
        ]);
    }
}

