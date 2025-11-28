<?php

namespace App\Http\Controllers\Api;

use App\Models\ExpenseCategory;
use App\Constants\Modules;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExpenseCategoryController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $query = ExpenseCategory::query();

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        // Filter by active status
        if ($request->has('active_only') && $request->get('active_only') === 'true') {
            $query->where('is_active', true);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $categories = $query->orderBy('name')->get();

        return $this->success($categories);
    }

    public function store(Request $request): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:operational,resident_billing,staff,vendor,other',
            'is_active' => 'boolean',
        ]);

        $validated['facility_id'] = auth()->user()->facility_id;

        $category = ExpenseCategory::create($validated);

        return $this->success($category->load('facility'), 'Expense category created successfully', 201);
    }

    public function show($id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $category = ExpenseCategory::with('facility')->findOrFail($id);

        return $this->success($category);
    }

    public function update(Request $request, $id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $category = ExpenseCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:operational,resident_billing,staff,vendor,other',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return $this->success($category->load('facility'), 'Expense category updated successfully');
    }

    public function destroy($id): JsonResponse
    {
        if ($error = $this->requireModuleAccess(Modules::BILLING_EXPENSES)) {
            return $error;
        }

        $category = ExpenseCategory::findOrFail($id);

        // Check if category has expenses
        if ($category->expenses()->count() > 0) {
            return $this->error('Cannot delete category with associated expenses', 400);
        }

        $category->delete();

        return $this->success(null, 'Expense category deleted successfully');
    }
}

