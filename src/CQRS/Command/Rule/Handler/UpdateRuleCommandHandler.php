<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\Handler;

use Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\UpdateRuleCommand;
use Evolutive\Module\EvoStatusFlow\Repository\RuleRepository;

/**
 * Handler for rule update command
 */
class UpdateRuleCommandHandler
{
    /**
     * @param RuleRepository $ruleRepository Repository for rule operations
     */
    public function __construct(
        private readonly RuleRepository $ruleRepository,
    ) {
    }

    /**
     * Handles the rule update command
     *
     * Updates an existing rule with the data provided in the command.
     *
     * @param UpdateRuleCommand $command Command containing update data
     *
     * @return bool Success status of the operation
     */
    public function handle(UpdateRuleCommand $command): bool
    {
        return $this->ruleRepository->update($command->ruleId, [
            'id_order_state_from' => $command->idOrderStateFrom,
            'id_order_state_to' => $command->idOrderStateTo,
            'delay_hours' => $command->delayHours,
            'condition_sql' => $command->conditionSql,
            'auto_execute' => $command->autoExecute,
            'active' => $command->active,
        ]);
    }
}
