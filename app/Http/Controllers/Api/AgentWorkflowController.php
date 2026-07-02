<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Agents\MultiAgentOrchestrator;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AgentWorkflowController extends Controller
{
    public function __construct(private MultiAgentOrchestrator $orchestrator)
    {
    }

    public function run(Request $request, Resident $resident): JsonResponse
    {
        if (! $request->user()) {
            abort(403, 'Agent access is not permitted.');
        }

        $this->authorizeResidentAccess($resident);

        $context = [
            'resident' => [
                'id' => $resident->id,
                'name' => trim(($resident->first_name ?? '').' '.($resident->last_name ?? '')) ?: 'Resident',
            ],
            'vitals' => $request->input('vitals', []),
            'medications' => $request->input('medications', []),
            'behavior_charts' => $request->input('behavior_charts', []),
            'sleep' => $request->input('sleep', []),
            'appointments' => $request->input('appointments', []),
            'faxes' => $request->input('faxes', []),
        ];

        $result = $this->orchestrator->run($context);

        return response()->json([
            'resident_id' => $resident->id,
            'workflow' => $result,
            'human_in_the_loop' => $result['approval_required'],
            'security' => [
                'requires_authentication' => true,
                'requires_authorization' => true,
                'safe_tool_use' => true,
            ],
        ]);
    }

    private function authorizeResidentAccess(Resident $resident): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(403, 'Agent access is not permitted.');
        }

        if ($user->role === 'super_admin') {
            return;
        }

        $facilityId = $resident->branch?->facility_id;
        if (! $facilityId || $facilityId !== $user->facility_id) {
            abort(404, 'Resident not found.');
        }
    }
}
