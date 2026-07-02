<?php

namespace App\Services\Agents;

class ClinicalReviewSkill extends AgentSkill
{
    public function name(): string
    {
        return 'clinical-review';
    }

    public function run(array $context): array
    {
        $vitals = $context['vitals'] ?? [];
        $criticalCount = (int) ($vitals['critical_count'] ?? 0);
        $warningCount = (int) ($vitals['warning_count'] ?? 0);
        $latestStatus = $vitals['latest']['status'] ?? null;

        $findings = [];
        if ($criticalCount > 0) {
            $findings[] = 'Critical readings are present and require immediate clinician attention.';
        }
        if ($warningCount > 0) {
            $findings[] = 'Warning-level values indicate closer monitoring is needed.';
        }
        if ($latestStatus && $latestStatus !== 'approved') {
            $findings[] = 'Latest status is '.$latestStatus.' and should be reviewed.';
        }

        return [
            'skill' => $this->name(),
            'findings' => $findings,
        ];
    }
}
