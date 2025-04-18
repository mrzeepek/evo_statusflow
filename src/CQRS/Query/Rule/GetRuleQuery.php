<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule;

/**
 * Query to retrieve a rule by its ID
 */
class GetRuleQuery
{
    /**
     * @param int $ruleId ID of the rule to retrieve
     */
    public function __construct(
        public readonly int $ruleId,
    ) {
    }
}
