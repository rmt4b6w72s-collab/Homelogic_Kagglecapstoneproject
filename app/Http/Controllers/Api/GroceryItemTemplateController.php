<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\GroceryItemTemplate\StoreGroceryItemTemplateRequest;
use App\Http\Requests\Api\GroceryItemTemplate\UpdateGroceryItemTemplateRequest;
use App\Http\Resources\Api\GroceryItemTemplateResource;
use App\Models\GroceryItemTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GroceryItemTemplateController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $branchId = null;
        
        // Determine branch ID for filtering
        if ($this->isCaregiver($user) && $user->assigned_branch_id) {
            $branchId = $user->assigned_branch_id;
        } elseif ($request->has('branch_id')) {
            $branchId = $request->get('branch_id');
        }

        $query = GroceryItemTemplate::with(['branch', 'createdBy'])
            ->forBranch($branchId)
            ->byCategory($request->get('category'))
            ->search($request->get('search'))
            ->orderBy('category')
            ->orderBy('name');

        $perPage = (int) $request->get('per_page', 50);
        $perPage = max(1, min(100, $perPage));
        $templates = $query->paginate($perPage);
        
        return response()->json([
            'data' => GroceryItemTemplateResource::collection($templates->items()),
            'current_page' => $templates->currentPage(),
            'per_page' => $templates->perPage(),
            'total' => $templates->total(),
            'last_page' => $templates->lastPage(),
            'from' => $templates->firstItem(),
            'to' => $templates->lastItem(),
        ]);
    }

    public function store(StoreGroceryItemTemplateRequest $request): JsonResponse
    {
        $template = GroceryItemTemplate::create($request->validated());

        return $this->success(
            new GroceryItemTemplateResource($template->load(['branch', 'createdBy'])),
            'Grocery item template created successfully',
            201
        );
    }

    public function show(string $id): JsonResponse
    {
        $template = GroceryItemTemplate::with(['branch', 'createdBy'])->findOrFail($id);
        
        return $this->success(new GroceryItemTemplateResource($template));
    }

    public function update(UpdateGroceryItemTemplateRequest $request, string $id): JsonResponse
    {
        $template = GroceryItemTemplate::findOrFail($id);
        $template->update($request->validated());

        return $this->success(
            new GroceryItemTemplateResource($template->load(['branch', 'createdBy'])),
            'Grocery item template updated successfully'
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $template = GroceryItemTemplate::findOrFail($id);
        $template->delete();

        return $this->success(null, 'Grocery item template deleted successfully');
    }
}
