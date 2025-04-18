<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule\Handler;

use Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule\DTO\RuleCollection;
use Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule\GetAutoExecuteRulesQuery;
use Evolutive\Module\EvoStatusFlow\Repository\RuleRepository;

/**
 * Handler for retrieving auto-executable rules query
 */
class GetAutoExecuteRulesQueryHandler
{
    /**
     * @param RuleRepository $ruleRepository Repository for rule operations
     */
    public function __construct(
        private readonly RuleRepository $ruleRepository,
    ) {
    }

    /**
     * Handles the auto-executable rules retrieval query
     *
     * Fetches all rules that have auto-execution enabled and returns them as a collection.
     *
     * @param GetAutoExecuteRulesQuery $query Query parameters
     *
     * @return RuleCollection Collection of auto-executable rules
     */
    public function handle(GetAutoExecuteRulesQuery $query): RuleCollection
    {
        $rulesData = $this->ruleRepository->getAutoExecuteRules();

        return RuleCollection::createFromArray($rulesData);
    }
}
