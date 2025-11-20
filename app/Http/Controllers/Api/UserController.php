<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class UserController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['assignedBranch', 'roles']);

        // Filter by status
        if ($request->has('status')) {
            if ($request->get('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->get('status') === 'inactive') {
                $query->where('is_active', false);
            }
        } elseif ($request->has('active_only') && $request->get('active_only') === 'true') {
            // Legacy support for older clients
            $query->where('is_active', true);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->where('assigned_branch_id', $request->get('branch_id'));
        }

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->get('role'));
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate($request->get('per_page', 20));

        return response()->json($users);
    }

    public function show($id): JsonResponse
    {
        $user = User::with(['assignedBranch', 'roles', 'roles.permissions'])
            ->findOrFail($id);

        return response()->json($user);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'first_name' => 'required|string|max:255',
            'middle_names' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:50',
            'date_of_birth' => 'required|date|before:' . now()->subYears(18)->format('Y-m-d'),
            'marital_status' => 'nullable|string|max:50',
            'sex' => 'required|string|in:male,female,other',
            'position' => 'nullable|string|max:255',
            'credentials' => 'nullable|string|max:255',
            'credential_details' => 'nullable|string',
            'date_employed' => 'required|date|before_or_equal:today',
            'hire_date' => 'nullable|date',
            'supervisor_name' => 'nullable|string|max:255',
            'provider_name' => 'nullable|string|max:255',
            'role' => 'required|string|max:255',
            'assigned_branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:5120',
            'role_ids' => 'array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        // Convert empty string to null for nullable fields
        if (array_key_exists('assigned_branch_id', $validated)) {
            $validated['assigned_branch_id'] = $validated['assigned_branch_id'] ?: null;
        }
        
        // Remove position if column doesn't exist in database or if it's empty
        if (!Schema::hasColumn('users', 'position')) {
            unset($validated['position']);
        } elseif (array_key_exists('position', $validated) && empty($validated['position'])) {
            unset($validated['position']);
        }

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('profile-images', $fileName, 'public');
            $validated['profile_image'] = $filePath;
        }

        // Hash password
        $validated['password'] = Hash::make($validated['password']);

        // Extract role_ids if provided
        $roleIds = $validated['role_ids'] ?? null;
        unset($validated['role_ids']);

        $user = User::create($validated);

        // Assign roles if provided
        if ($roleIds) {
            $user->roles()->sync($roleIds);
        }

        // Refresh the model to get accessors (like profile_image_url)
        $user->refresh();

        return response()->json($user->load(['assignedBranch', 'roles']), 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Convert is_active from FormData string to boolean if present (like residents do)
        // This handles both FormData ('1'/'0') and JSON (true/false) formats
        if ($request->has('is_active')) {
            $isActive = $request->input('is_active');
            if (is_string($isActive)) {
                $request->merge(['is_active' => filter_var($isActive, FILTER_VALIDATE_BOOLEAN)]);
            }
        }

        // Convert empty strings to null for date fields before validation
        $input = $request->all();
        foreach (['date_of_birth', 'date_employed', 'hire_date'] as $dateField) {
            if (isset($input[$dateField]) && $input[$dateField] === '') {
                $input[$dateField] = null;
            }
        }
        
        // Replace request data with cleaned input for validation
        $request->merge($input);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'first_name' => 'sometimes|required|string|max:255',
            'middle_names' => 'nullable|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|max:50',
            'date_of_birth' => 'nullable|date|before:' . now()->subYears(18)->format('Y-m-d'),
            'marital_status' => 'nullable|string|max:50',
            'sex' => 'sometimes|required|string|in:male,female,other',
            'position' => 'nullable|string|max:255',
            'credentials' => 'nullable|string|max:255',
            'credential_details' => 'nullable|string',
            'date_employed' => 'nullable|date|before_or_equal:today',
            'hire_date' => 'nullable|date',
            'supervisor_name' => 'nullable|string|max:255',
            'provider_name' => 'nullable|string|max:255',
            'role' => 'sometimes|required|string|max:255',
            'assigned_branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:5120',
            'remove_profile_image' => 'nullable|boolean',
            'role_ids' => 'array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        // Convert empty string to null for nullable fields
        if (array_key_exists('assigned_branch_id', $validated)) {
            $validated['assigned_branch_id'] = $validated['assigned_branch_id'] ?: null;
        }
        
        // Remove position if column doesn't exist in database or if it's empty
        if (!Schema::hasColumn('users', 'position')) {
            unset($validated['position']);
        } elseif (array_key_exists('position', $validated) && empty($validated['position'])) {
            unset($validated['position']);
        }

        // Debug: Log request information for profile image
        \Log::info('User update request - profile image check', [
            'user_id' => $user->id,
            'has_file' => $request->hasFile('profile_image'),
            'has_remove_flag' => $request->has('remove_profile_image'),
            'remove_flag_value' => $request->get('remove_profile_image'),
            'all_request_keys' => array_keys($request->all()),
            'files_keys' => array_keys($request->allFiles())
        ]);

        // Handle profile image removal if requested
        if ($request->has('remove_profile_image') && $request->get('remove_profile_image') === '1') {
            // Delete old profile image if exists
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $validated['profile_image'] = null;
            // Remove the flag from validated array since it's not a user field
            unset($validated['remove_profile_image']);
        }
        // Handle profile image upload if provided
        elseif ($request->hasFile('profile_image')) {
            try {
                // Delete old profile image if exists
                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }

                $file = $request->file('profile_image');
                
                // Validate file was uploaded successfully
                if (!$file->isValid()) {
                    \Log::error('Profile image upload failed: Invalid file', [
                        'user_id' => $user->id,
                        'error' => $file->getError()
                    ]);
                    throw new \Exception('Invalid file upload');
                }
                
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('profile-images', $fileName, 'public');
                
                if (!$filePath) {
                    \Log::error('Profile image upload failed: Storage failed', [
                        'user_id' => $user->id,
                        'file_name' => $fileName
                    ]);
                    throw new \Exception('Failed to store file');
                }
                
                $validated['profile_image'] = $filePath;
                \Log::info('Profile image uploaded successfully', [
                    'user_id' => $user->id,
                    'file_path' => $filePath
                ]);
            } catch (\Exception $e) {
                \Log::error('Profile image upload error: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't fail the entire update if image upload fails, but log it
            }
        }
        // If neither remove flag nor new file, preserve existing image by not including it in validated array
        // Remove remove_profile_image flag if it exists (shouldn't be in validated, but just in case)
        if (isset($validated['remove_profile_image'])) {
            unset($validated['remove_profile_image']);
        }

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Extract role_ids if provided
        $roleIds = $validated['role_ids'] ?? null;
        if (isset($validated['role_ids'])) {
            unset($validated['role_ids']);
        }

        $wasActive = (bool) $user->is_active;

        $user->update($validated);

        // Revoke any active API tokens when an account is deactivated
        if (
            array_key_exists('is_active', $validated)
            && $wasActive
            && $validated['is_active'] === false
        ) {
            $user->tokens()->delete();
        }

        // Update roles if provided
        if ($roleIds !== null) {
            $user->roles()->sync($roleIds);
        }

        // Refresh the model to get updated accessors (like profile_image_url)
        $user->refresh();

        return response()->json($user->load(['assignedBranch', 'roles']));
    }

    public function destroy($id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account.'
            ], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}

