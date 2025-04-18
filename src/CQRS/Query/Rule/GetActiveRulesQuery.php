<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule;

/**
 * Query to retrieve active rules
 */
class GetActiveRulesQuery
{
    /**
     * @param int|null $ruleId Specific rule ID (optional)
     */
    public function __construct(
        public readonly ?int $ruleId = null,
    ) {
    }
}
