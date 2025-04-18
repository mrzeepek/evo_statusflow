<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\Handler;

use Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\DeleteRuleCommand;
use Evolutive\Module\EvoStatusFlow\Repository\RuleRepository;

/**
 * Handler for rule deletion command
 */
class DeleteRuleCommandHandler
{
    /**
     * @param RuleRepository $ruleRepository Repository for rule operations
     */
    public function __construct(
        private readonly RuleRepository $ruleRepository,
    ) {
    }

    /**
     * Handles the rule deletion command
     *
     * Deletes the specified rule from the repository.
     *
     * @param DeleteRuleCommand $command Command containing the rule ID to delete
     *
     * @return bool Success status of the operation
     */
    public function handle(DeleteRuleCommand $command): bool
    {
        try {
            return $this->ruleRepository->delete($command->ruleId);
        } catch (\Exception $e) {
            // Log l'erreur pour débogage
            if (class_exists('PrestaShopLogger')) {
                \PrestaShopLogger::addLog(
                    'Error deleting rule: ' . $e->getMessage(),
                    3,
                    null,
                    'EvoStatusFlow',
                    $command->ruleId,
                    true
                );
            }
            throw $e;
        }
    }
}
