<?php

namespace App\Http\Controllers\Api;

use App\Constants\Modules;
use App\Http\Controllers\Controller;
use App\Models\PharmacyInventory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PharmacyInventoryController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::PHARMACY)) {
            return $error;
        }
        $query = PharmacyInventory::with(['branch', 'drug', 'stockLots']);
        
        $user = $request->user();
        $isCaregiver = $user && in_array($user->role, ['caregiver', 'care_giver', 'nurse']);
        
        // Filter by branch for caregivers
        if ($isCaregiver && $user->assigned_branch_id) {
            $query->where('branch_id', $user->assigned_branch_id);
        }
        
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }
        
        if ($request->has('drug_id')) {
            $query->where('drug_id', $request->get('drug_id'));
        }
        
        if ($request->has('stock_status')) {
            $status = $request->get('stock_status');
            if ($status === 'low_stock') {
                $query->lowStock();
            } elseif ($status === 'out_of_stock') {
                $query->outOfStock();
            }
        }
        
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('drug', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%");
            });
        }
        
        $perPage = (int) $request->get('per_page', 50);
        $perPage = max(1, min(100, $perPage));
        $inventory = $query->orderBy('branch_id')->orderBy('drug_id')->paginate($perPage);
        
        return response()->json($inventory);
    }
    
    public function store(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::PHARMACY)) {
            return $error;
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'drug_id' => 'required|exists:drugs,id',
            'quantity' => 'required|integer|min:0',
            'minimum_stock_level' => 'required|integer|min:0',
            'maximum_stock_level' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'requires_refrigeration' => 'boolean',
            'is_controlled_substance' => 'boolean',
            'storage_notes' => 'nullable|string',
        ]);
        
        // Check if inventory already exists
        $existing = PharmacyInventory::where('branch_id', $validated['branch_id'])
            ->where('drug_id', $validated['drug_id'])
            ->first();
        
        if ($existing) {
            return response()->json([
                'message' => 'Inventory already exists for this drug in this branch.',
            ], 422);
        }
        
        $validated['requires_refrigeration'] = $validated['requires_refrigeration'] ?? false;
        $validated['is_controlled_substance'] = $validated['is_controlled_substance'] ?? false;
        
        $inventory = PharmacyInventory::create($validated);
        
        return response()->json($inventory->load(['branch', 'drug']), 201);
    }
    
    public function show(string $id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::PHARMACY)) {
            return $error;
        }

        $inventory = PharmacyInventory::with(['branch', 'drug', 'stockLots', 'transactions'])
            ->findOrFail($id);
        
        return response()->json($inventory);
    }
    
    public function update(Request $request, string $id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::PHARMACY)) {
            return $error;
        }

        $inventory = PharmacyInventory::findOrFail($id);
        
        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:0',
            'minimum_stock_level' => 'sometimes|integer|min:0',
            'maximum_stock_level' => 'nullable|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'requires_refrigeration' => 'sometimes|boolean',
            'is_controlled_substance' => 'sometimes|boolean',
            'storage_notes' => 'nullable|string',
        ]);
        
        $inventory->update($validated);
        
        return response()->json($inventory->load(['branch', 'drug']));
    }
    
    public function destroy(string $id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::PHARMACY)) {
            return $error;
        }

        $inventory = PharmacyInventory::findOrFail($id);
        $inventory->delete();
        
        return response()->json(['message' => 'Inventory item deleted successfully']);
    }
}
