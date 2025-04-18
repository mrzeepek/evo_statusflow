<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule;

/**
 * Command to delete a status flow rule
 */
class DeleteRuleCommand
{
    /**
     * @param int $ruleId ID of the rule to delete
     */
    public function __construct(
        public readonly int $ruleId,
    ) {
    }
}
