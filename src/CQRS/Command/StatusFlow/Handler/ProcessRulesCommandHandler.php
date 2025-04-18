<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\StatusFlow\Handler;

use Evolutive\Module\EvoStatusFlow\CQRS\Command\StatusFlow\ProcessRulesCommand;
use Evolutive\Module\EvoStatusFlow\Exception\StatusFlowCommandException;
use Evolutive\Module\EvoStatusFlow\Service\StatusFlowProcessor;

/**
 * Handler for status flow rules processing command
 */
class ProcessRulesCommandHandler
{
    /**
     * @param StatusFlowProcessor $statusFlowProcessor Service for processing status flow rules
     */
    public function __construct(
        private readonly StatusFlowProcessor $statusFlowProcessor,
    ) {
    }

    /**
     * Handles the status flow rules processing command
     *
     * This method delegates to the StatusFlowProcessor service to process
     * rules based on the parameters provided in the command.
     *
     * @param ProcessRulesCommand $command Command to handle
     *
     * @return int Number of processed transitions
     *
     * @throws StatusFlowCommandException When processing fails
     */
    public function handle(ProcessRulesCommand $command): int
    {
        return $this->statusFlowProcessor->processRules(
            $command->objectType,
            $command->dryRun,
            $command->ruleId
        );
    }
}
