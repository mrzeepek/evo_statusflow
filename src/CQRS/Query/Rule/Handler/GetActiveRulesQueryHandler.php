<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule\Handler;

use Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule\DTO\RuleCollection;
use Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule\GetActiveRulesQuery;
use Evolutive\Module\EvoStatusFlow\Repository\RuleRepository;

/**
 * Handler for retrieving active rules query
 */
class GetActiveRulesQueryHandler
{
    /**
     * @param RuleRepository $ruleRepository Repository for rule operations
     */
    public function __construct(
        private readonly RuleRepository $ruleRepository,
    ) {
    }

    /**
     * Handles the active rules retrieval query
     *
     * Fetches all active rules, optionally filtered by specific rule ID.
     *
     * @param GetActiveRulesQuery $query Query containing optional rule ID filter
     *
     * @return RuleCollection Collection of active rules
     */
    public function handle(GetActiveRulesQuery $query): RuleCollection
    {
        $rulesData = $this->ruleRepository->getActiveRules($query->ruleId);

        return RuleCollection::createFromArray($rulesData);
    }
}
