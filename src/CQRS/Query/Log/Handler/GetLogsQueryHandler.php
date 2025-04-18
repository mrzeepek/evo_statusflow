<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Log\Handler;

use Evolutive\Module\EvoStatusFlow\CQRS\Query\Log\DTO\LogCollection;
use Evolutive\Module\EvoStatusFlow\CQRS\Query\Log\GetLogsQuery;
use Evolutive\Module\EvoStatusFlow\Repository\LogRepository;

/**
 * Handler for retrieving logs query
 */
class GetLogsQueryHandler
{
    /**
     * @param LogRepository $logRepository Repository for log operations
     */
    public function __construct(
        private readonly LogRepository $logRepository,
    ) {
    }

    /**
     * Handles the logs retrieval query
     *
     * Fetches logs from the repository based on filters and pagination parameters.
     *
     * @param GetLogsQuery $query Query containing filters and pagination settings
     *
     * @return LogCollection Collection of logs matching the criteria
     */
    public function handle(GetLogsQuery $query): LogCollection
    {
        $logsData = $this->logRepository->getLogs(
            $query->filters,
            $query->limit,
            $query->offset,
            $query->orderBy,
            $query->orderDirection
        );

        return LogCollection::createFromArray($logsData);
    }
}
