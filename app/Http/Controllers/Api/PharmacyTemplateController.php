<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PharmacyTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PharmacyTemplateController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = PharmacyTemplate::with(['branch', 'createdBy']);
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

        // Search by name
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = (int) $request->get('per_page', 50);
        $perPage = max(1, min(100, $perPage));
        $templates = $query->orderBy('name')->paginate($perPage);

        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'default_notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $template = PharmacyTemplate::create($validated);

        return response()->json($template->load(['branch', 'createdBy']), 201);
    }

    public function show(string $id): JsonResponse
    {
        $template = PharmacyTemplate::with(['branch', 'createdBy'])->findOrFail($id);
        return response()->json($template);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $template = PharmacyTemplate::findOrFail($id);

        $validated = $request->validate([
            'branch_id' => 'sometimes|exists:branches,id',
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'default_notes' => 'nullable|string',
        ]);

        $template->update($validated);

        return response()->json($template->load(['branch', 'createdBy']));
    }

    public function destroy(string $id): JsonResponse
    {
        $template = PharmacyTemplate::findOrFail($id);
        $template->delete();

        return response()->json(['message' => 'Pharmacy template deleted successfully']);
    }
}
