<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\Handler;

use Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\ToggleRuleAutoExecuteCommand;
use Evolutive\Module\EvoStatusFlow\Repository\RuleRepository;

/**
 * Handler for toggling rule auto-execute status command
 */
class ToggleRuleAutoExecuteCommandHandler
{
    /**
     * @param RuleRepository $ruleRepository Repository for rule operations
     */
    public function __construct(
        private readonly RuleRepository $ruleRepository,
    ) {
    }

    /**
     * Handles the toggle rule auto-execute command
     *
     * Toggles the auto-execute status of a rule between enabled and disabled.
     *
     * @param ToggleRuleAutoExecuteCommand $command Command containing the rule ID
     *
     * @return bool Success status of the operation
     */
    public function handle(ToggleRuleAutoExecuteCommand $command): bool
    {
        return $this->ruleRepository->toggleAutoExecute($command->ruleId);
    }
}
