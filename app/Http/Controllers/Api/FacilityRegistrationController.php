<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FacilityRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityRegistrationController extends Controller
{
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
            'owner_email' => 'required|email|unique:users,email',
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

