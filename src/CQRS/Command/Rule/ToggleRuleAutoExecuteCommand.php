<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule;

/**
 * Command to toggle auto-execute status of a rule
 */
class ToggleRuleAutoExecuteCommand
{
    /**
     * @param int $ruleId ID of the rule to toggle auto-execute status
     */
    public function __construct(
        public readonly int $ruleId,
    ) {
    }
}
