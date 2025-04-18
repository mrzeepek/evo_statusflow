<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\Log\Handler;

use Evolutive\Module\EvoStatusFlow\CQRS\Command\Log\CleanLogsCommand;
use Evolutive\Module\EvoStatusFlow\Service\LoggingService;

/**
 * Handler for log cleanup command
 */
class CleanLogsCommandHandler
{
    /**
     * @param LoggingService $loggingService Service for log management
     */
    public function __construct(
        private readonly LoggingService $loggingService,
    ) {
    }

    /**
     * Handles the log cleanup command
     *
     * Deletes old logs from the system based on the retention period.
     *
     * @param CleanLogsCommand $command Command containing optional retention period
     *
     * @return int Number of deleted log entries
     *
     * @throws \Exception If an unexpected error occurs
     */
    public function handle(CleanLogsCommand $command): int
    {
        try {
            return $this->loggingService->cleanOldLogs($command->days);
        } catch (\Exception $e) {
            // Log the error
            \PrestaShopLogger::addLog(
                'Error during log cleanup: ' . $e->getMessage(),
                3,
                null,
                'EvoStatusFlow',
                0,
                true
            );

            throw $e;
        }
    }
}
