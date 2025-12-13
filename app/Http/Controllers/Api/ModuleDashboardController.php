<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseApiController;
use App\Services\ModuleDashboardService;
use App\Constants\Modules;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ModuleDashboardController extends BaseApiController
{
    protected $moduleDashboardService;

    public function __construct(ModuleDashboardService $moduleDashboardService)
    {
        $this->moduleDashboardService = $moduleDashboardService;
    }

    /**
     * Get stats for all modules (quick overview)
     */
    public function getAllStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->moduleDashboardService->getAllModuleStats($user);
        
        return response()->json($stats);
    }

    /**
     * Get stats for a specific module
     */
    public function getModuleStats(Request $request, string $module): JsonResponse
    {
        if (!Modules::isValid($module)) {
            return response()->json([
                'message' => 'Invalid module',
            ], 404);
        }

        $user = $request->user();
        
        // Check module access
        if (!$user->hasModuleAccess($module) && $user->role !== 'super_admin') {
            return response()->json([
                'message' => 'You do not have access to this module',
            ], 403);
        }

        $stats = $this->moduleDashboardService->getModuleStats($module, $user);
        
        return response()->json($stats);
    }

    /**
     * Get recent activity for a specific module
     */
    public function getRecentActivity(Request $request, string $module): JsonResponse
    {
        if (!Modules::isValid($module)) {
            return response()->json([
                'message' => 'Invalid module',
            ], 404);
        }

        $user = $request->user();
        
        // Check module access
        if (!$user->hasModuleAccess($module) && $user->role !== 'super_admin') {
            return response()->json([
                'message' => 'You do not have access to this module',
            ], 403);
        }

        $limit = $request->get('limit', 10);
        $activity = $this->moduleDashboardService->getRecentActivity($module, $user, $limit);
        
        return response()->json($activity);
    }
}

