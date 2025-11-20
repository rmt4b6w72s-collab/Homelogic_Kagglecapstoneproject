<?php

namespace App\Http\Controllers;

use App\Models\FacilityRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class FacilityRegistrationController extends Controller
{
    /**
     * Show the facility registration form
     */
    public function show()
    {
        return view('facility-registration');
    }

    /**
     * Store a new facility registration request
     */
    public function store(Request $request)
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

        return redirect()->route('facility-registration.success')
            ->with('success', 'Your registration request has been submitted successfully. We will review it and contact you soon.');
    }

    /**
     * Show success page after registration
     */
    public function success()
    {
        return view('facility-registration-success');
    }
}
