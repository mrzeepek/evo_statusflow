<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule;

/**
 * Command to toggle active status of a status flow rule
 */
class ToggleRuleActiveCommand
{
    /**
     * @param int $ruleId ID of the rule to toggle active status
     */
    public function __construct(
        public readonly int $ruleId,
    ) {
    }
}
