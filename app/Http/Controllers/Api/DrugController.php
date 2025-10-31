<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DrugController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Drug::query();

        // Filter by active status
        if ($request->has('active_only') && $request->get('active_only') === 'true') {
            $query->where('is_active', true);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%");
            });
        }

        $drugs = $query->orderBy('name', 'asc')
            ->paginate($request->get('per_page', 100));

        return response()->json($drugs);
    }

    public function show($id): JsonResponse
    {
        $drug = Drug::findOrFail($id);
        return response()->json($drug);
    }
}

