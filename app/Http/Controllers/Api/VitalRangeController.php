<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VitalRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VitalRangeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = VitalRange::query();
        $ranges = $query->orderBy('vital_type')->paginate($request->get('per_page', 50));
        return response()->json($ranges);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vital_type' => 'required|string|max:100',
            'min_value' => 'nullable|numeric',
            'max_value' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);
        $range = VitalRange::create($validated);
        return response()->json($range, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $range = VitalRange::findOrFail($id);
        $validated = $request->validate([
            'vital_type' => 'sometimes|required|string|max:100',
            'min_value' => 'nullable|numeric',
            'max_value' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);
        $range->update($validated);
        return response()->json($range);
    }

    public function destroy($id): JsonResponse
    {
        VitalRange::findOrFail($id)->delete();
        return response()->json(['message' => 'Vital range deleted']);
    }
}


