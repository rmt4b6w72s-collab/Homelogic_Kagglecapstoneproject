<?php

namespace App\Http\Controllers\Api;

use App\Constants\UserRoles;
use App\Models\Resident;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseApiController
{
    public function __construct(
        private DashboardService $dashboardService
    ) {
    }

    public function stats(): JsonResponse
    {
        $user = auth()->user();
        $stats = $this->dashboardService->getStatsForUser($user);

        return $this->success($stats);
    }
    
    public function residentVitalsTrend($residentId): JsonResponse
    {
        $user = auth()->user();
        
        // Verify caregiver has access to this resident (must be in same branch)
        if (UserRoles::isCaregiverRole($user->role)) {
            $hasAccess = Resident::where('id', $residentId)
                ->where('branch_id', $user->assigned_branch_id)
                ->where('is_active', true)
                ->exists();
            
            if (!$hasAccess) {
                return $this->error('Unauthorized', 403);
            }
        }

        $trend = $this->dashboardService->getResidentVitalsTrend($residentId);
        return $this->success($trend);
    }

    public function dailyActivities(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = auth()->user();
        $days = (int) $request->get('days', 30);
        
        $activities = $this->dashboardService->getDailyActivities($user, $days);
        
        return $this->success($activities);
    }
}

