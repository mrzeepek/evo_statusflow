<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\Handler;

use Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\CreateRuleCommand;
use Evolutive\Module\EvoStatusFlow\Repository\RuleRepository;

/**
 * Handler for rule creation command
 */
class CreateRuleCommandHandler
{
    /**
     * @param RuleRepository $ruleRepository Repository for rule operations
     */
    public function __construct(
        private readonly RuleRepository $ruleRepository,
    ) {
    }

    /**
     * Handles the rule creation command
     *
     * Creates a new rule based on the data provided in the command.
     *
     * @param CreateRuleCommand $command Command containing rule creation data
     *
     * @return int ID of the newly created rule
     */
    public function handle(CreateRuleCommand $command): int
    {
        return $this->ruleRepository->create([
            'id_order_state_from' => $command->idOrderStateFrom,
            'id_order_state_to' => $command->idOrderStateTo,
            'delay_hours' => $command->delayHours,
            'condition_sql' => $command->conditionSql,
            'auto_execute' => $command->autoExecute,
            'active' => $command->active,
        ]);
    }
}
