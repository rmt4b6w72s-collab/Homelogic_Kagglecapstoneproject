<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Role::with('permissions');
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%");
        }
        $roles = $query->orderBy('name')->paginate($request->get('per_page', 20));
        return response()->json($roles);
    }

    public function permissions(): JsonResponse
    {
        return response()->json(Permission::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
        ]);
        $role = Role::create(['name' => $validated['name']]);
        if (!empty($validated['permissions'])) {
            $role->permissions()->sync(Permission::whereIn('name', $validated['permissions'])->pluck('id'));
        }
        return response()->json($role->load('permissions'), 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
        ]);
        if (isset($validated['name'])) {
            $role->name = $validated['name'];
            $role->save();
        }
        if (isset($validated['permissions'])) {
            $role->permissions()->sync(Permission::whereIn('name', $validated['permissions'])->pluck('id'));
        }
        return response()->json($role->load('permissions'));
    }

    public function destroy($id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $role->permissions()->detach();
        $role->delete();
        return response()->json(['message' => 'Role deleted']);
    }
}


