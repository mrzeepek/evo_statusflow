<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule\Handler;

use Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule\DTO\RuleDTO;
use Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule\GetRuleQuery;
use Evolutive\Module\EvoStatusFlow\Exception\RuleNotFoundException;
use Evolutive\Module\EvoStatusFlow\Repository\RuleRepository;

/**
 * Handler for retrieving a single rule query
 */
class GetRuleQueryHandler
{
    /**
     * @param RuleRepository $ruleRepository Repository for rule operations
     */
    public function __construct(
        private readonly RuleRepository $ruleRepository,
    ) {
    }

    /**
     * Handles the rule retrieval query
     *
     * Fetches a rule from the repository by its ID and transforms it into a DTO.
     *
     * @param GetRuleQuery $query Query containing the rule ID to retrieve
     *
     * @return RuleDTO Data object representing the rule
     *
     * @throws RuleNotFoundException If the requested rule does not exist
     */
    public function handle(GetRuleQuery $query): RuleDTO
    {
        $ruleData = $this->ruleRepository->getById($query->ruleId);

        return RuleDTO::createFromArray($ruleData);
    }
}
