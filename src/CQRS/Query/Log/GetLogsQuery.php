<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Log;

/**
 * Query to retrieve a filtered list of logs
 */
class GetLogsQuery
{
    /**
     * @param array $filters Filters to apply
     * @param int $limit Maximum number of logs to return
     * @param int $offset Pagination offset
     * @param string $orderBy Field to sort by
     * @param string $orderDirection Sort direction
     */
    public function __construct(
        public readonly array $filters = [],
        public readonly int $limit = 50,
        public readonly int $offset = 0,
        public readonly string $orderBy = 'date_add',
        public readonly string $orderDirection = 'DESC',
    ) {
    }
}
