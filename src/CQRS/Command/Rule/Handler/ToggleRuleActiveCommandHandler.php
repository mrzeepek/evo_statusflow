<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\Handler;

use Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\ToggleRuleActiveCommand;
use Evolutive\Module\EvoStatusFlow\Repository\RuleRepository;

/**
 * Handler for toggling rule active status command
 */
class ToggleRuleActiveCommandHandler
{
    /**
     * @param RuleRepository $ruleRepository Repository for rule operations
     */
    public function __construct(
        private readonly RuleRepository $ruleRepository,
    ) {
    }

    /**
     * Handles the toggle rule active status command
     *
     * Toggles the active status of a rule between enabled and disabled.
     *
     * @param ToggleRuleActiveCommand $command Command containing the rule ID
     *
     * @return bool Success status of the operation
     */
    public function handle(ToggleRuleActiveCommand $command): bool
    {
        return $this->ruleRepository->toggleActive($command->ruleId);
    }
}
