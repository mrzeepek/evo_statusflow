<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule;

/**
 * Command to create a new status flow rule
 */
class CreateRuleCommand
{
    /**
     * @param int $idOrderStateFrom Origin order state ID
     * @param int $idOrderStateTo Destination order state ID
     * @param int $delayHours Delay in hours before applying the rule
     * @param string|null $conditionSql Optional SQL condition to filter orders
     * @param bool $autoExecute Whether the rule should be automatically executed during batch processing
     * @param bool $active Whether the rule is active
     */
    public function __construct(
        public readonly int $idOrderStateFrom,
        public readonly int $idOrderStateTo,
        public readonly int $delayHours,
        public readonly ?string $conditionSql = null,
        public readonly bool $autoExecute = true,
        public readonly bool $active = true,
    ) {
    }
}
