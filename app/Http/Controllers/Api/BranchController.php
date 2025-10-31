<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Branch::with('facility');
        if ($request->has('facility_id')) {
            $query->where('facility_id', $request->get('facility_id'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
        }

        $branches = $query->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json($branches);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'facility_id' => 'required|exists:facilities,id',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $branch = Branch::create($validated);
        return response()->json($branch->load('facility'), 201);
    }

    public function show($id): JsonResponse
    {
        return response()->json(Branch::with('facility')->findOrFail($id));
    }

    public function update(Request $request, $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'facility_id' => 'sometimes|exists:facilities,id',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);
        $branch->update($validated);
        return response()->json($branch->load('facility'));
    }

    public function destroy($id): JsonResponse
    {
        $branch = Branch::findOrFail($id);
        $branch->delete();
        return response()->json(['message' => 'Branch deleted']);
    }
}


