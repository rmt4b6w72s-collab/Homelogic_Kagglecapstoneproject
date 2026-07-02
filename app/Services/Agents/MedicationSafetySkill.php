<?php

namespace App\Services\Agents;

class MedicationSafetySkill extends AgentSkill
{
    public function name(): string
    {
        return 'medication-safety';
    }

    public function run(array $context): array
    {
        $medications = $context['medications'] ?? [];
        $missedCount = (int) ($medications['missed_count'] ?? 0);
        $activeCount = (int) ($medications['active_count'] ?? 0);

        $actions = [];
        if ($missedCount > 0) {
            $actions[] = 'Review missed administrations and confirm whether follow-up is required.';
        }
        if ($activeCount > 0) {
            $actions[] = 'Verify the active medication list against the current care plan.';
        }

        return [
            'skill' => $this->name(),
            'actions' => $actions,
        ];
    }
}
