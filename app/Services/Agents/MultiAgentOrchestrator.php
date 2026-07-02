<?php

namespace App\Services\Agents;

class MultiAgentOrchestrator
{
    /** @var array<int, AgentSkill> */
    private array $skills;

    public function __construct(array $skills = [])
    {
        $this->skills = $skills ?: [
            new ClinicalReviewSkill(),
            new MedicationSafetySkill(),
        ];
    }

    public function run(array $context): array
    {
        $results = [];
        foreach ($this->skills as $skill) {
            $results[] = $skill->run($context);
        }

        return [
            'agents' => $results,
            'approval_required' => $this->requiresApproval($context),
            'tooling' => [
                'data_sources' => ['vitals', 'medications', 'behavior_charts', 'sleep', 'appointments'],
                'mcp_style_context' => true,
            ],
        ];
    }

    private function requiresApproval(array $context): bool
    {
        $vitals = $context['vitals'] ?? [];
        $criticalCount = (int) ($vitals['critical_count'] ?? 0);
        $medications = $context['medications'] ?? [];
        $missedCount = (int) ($medications['missed_count'] ?? 0);

        return $criticalCount > 0 || $missedCount > 0;
    }
}
