<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Log;

/**
 * Query to retrieve a log by its ID
 */
class GetLogQuery
{
    /**
     * @param int $logId ID of the log to retrieve
     */
    public function __construct(
        public readonly int $logId,
    ) {
    }
}
