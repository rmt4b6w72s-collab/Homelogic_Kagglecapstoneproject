<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Facility::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
        }

        $facilities = $query->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json($facilities);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $facility = Facility::create($validated);
        return response()->json($facility, 201);
    }

    public function show($id): JsonResponse
    {
        return response()->json(Facility::findOrFail($id));
    }

    public function update(Request $request, $id): JsonResponse
    {
        $facility = Facility::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);
        $facility->update($validated);
        return response()->json($facility);
    }

    public function destroy($id): JsonResponse
    {
        $facility = Facility::findOrFail($id);
        $facility->delete();
        return response()->json(['message' => 'Facility deleted']);
    }
}


