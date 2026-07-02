<?php

namespace App\Services\Agents;

abstract class AgentSkill
{
    abstract public function name(): string;

    abstract public function run(array $context): array;
}
