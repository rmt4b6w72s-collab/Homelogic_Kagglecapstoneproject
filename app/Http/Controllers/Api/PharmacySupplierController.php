<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PharmacySupplier;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PharmacySupplierController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = PharmacySupplier::with(['createdBy'])->withCount('orders');
        
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        $perPage = (int) $request->get('per_page', 50);
        $perPage = max(1, min(100, $perPage));
        $suppliers = $query->orderBy('name')->paginate($perPage);
        
        return response()->json($suppliers);
    }
    
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:2',
            'zip' => 'nullable|string|max:10',
            'fax' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'default_discount' => 'nullable|numeric|min:0|max:100',
            'payment_terms_days' => 'nullable|integer|min:0',
        ]);
        
        $validated['created_by'] = auth()->id();
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['payment_terms_days'] = $validated['payment_terms_days'] ?? 30;
        
        $supplier = PharmacySupplier::create($validated);
        
        return response()->json($supplier->load(['createdBy']), 201);
    }
    
    public function show(string $id): JsonResponse
    {
        $supplier = PharmacySupplier::with(['createdBy', 'orders'])
            ->withCount('orders')
            ->findOrFail($id);
        
        return response()->json($supplier);
    }
    
    public function update(Request $request, string $id): JsonResponse
    {
        $supplier = PharmacySupplier::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'sometimes|email|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:2',
            'zip' => 'nullable|string|max:10',
            'fax' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'default_discount' => 'nullable|numeric|min:0|max:100',
            'payment_terms_days' => 'nullable|integer|min:0',
        ]);
        
        $supplier->update($validated);
        
        return response()->json($supplier->load(['createdBy']));
    }
    
    public function destroy(string $id): JsonResponse
    {
        $supplier = PharmacySupplier::findOrFail($id);
        
        // Check if supplier has orders
        if ($supplier->orders()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete supplier with existing orders.',
            ], 422);
        }
        
        $supplier->delete();
        
        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
