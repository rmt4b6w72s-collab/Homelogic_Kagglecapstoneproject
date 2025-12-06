<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VitalRange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VitalRangeController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = VitalRange::query();
        $ranges = $query->orderBy('parameter')->paginate($request->get('per_page', 50));
        return response()->json($ranges);
    }

    public function store(Request $request): JsonResponse
    {
        if ($error = $this->requirePermission('create_vital_ranges')) {
            return $error;
        }

        $validated = $request->validate([
            'parameter' => 'required|string|max:100',
            'min_normal' => 'nullable|numeric',
            'max_normal' => 'nullable|numeric',
            'min_warning' => 'nullable|numeric',
            'max_warning' => 'nullable|numeric',
            'min_critical' => 'nullable|numeric',
            'max_critical' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $range = VitalRange::create($validated);
        return response()->json($range, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        if ($error = $this->requirePermission('edit_vital_ranges')) {
            return $error;
        }

        $range = VitalRange::findOrFail($id);
        $validated = $request->validate([
            'parameter' => 'sometimes|required|string|max:100',
            'min_normal' => 'nullable|numeric',
            'max_normal' => 'nullable|numeric',
            'min_warning' => 'nullable|numeric',
            'max_warning' => 'nullable|numeric',
            'min_critical' => 'nullable|numeric',
            'max_critical' => 'nullable|numeric',
            'unit' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $range->update($validated);
        return response()->json($range);
    }

    public function destroy($id): JsonResponse
    {
        if ($error = $this->requirePermission('delete_vital_ranges')) {
            return $error;
        }

        VitalRange::findOrFail($id)->delete();
        return response()->json(['message' => 'Vital range deleted']);
    }
}


