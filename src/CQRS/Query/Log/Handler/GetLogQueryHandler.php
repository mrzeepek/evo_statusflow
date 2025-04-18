<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Log\Handler;

use Evolutive\Module\EvoStatusFlow\CQRS\Query\Log\DTO\LogDTO;
use Evolutive\Module\EvoStatusFlow\CQRS\Query\Log\GetLogQuery;
use Evolutive\Module\EvoStatusFlow\Repository\LogRepository;

/**
 * Handler for retrieving a single log query
 */
class GetLogQueryHandler
{
    /**
     * @param LogRepository $logRepository Repository for log operations
     */
    public function __construct(
        private readonly LogRepository $logRepository,
    ) {
    }

    /**
     * Handles the log retrieval query
     *
     * Fetches a log from the repository by its ID and transforms it into a DTO.
     *
     * @param GetLogQuery $query Query containing the log ID to retrieve
     *
     * @return LogDTO|null Data object representing the log, or null if not found
     */
    public function handle(GetLogQuery $query): ?LogDTO
    {
        $logData = $this->logRepository->getById($query->logId);

        if (!$logData) {
            return null;
        }

        return LogDTO::createFromArray($logData);
    }
}
