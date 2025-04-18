<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Evolutive\Module\EvoStatusFlow\Exception\EvoStatusFlowException;

/**
 * Repository for managing status flow logs
 */
class LogRepository
{
    /**
     * @param Connection $connection Database connection
     * @param string $dbPrefix Database prefix
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly string $dbPrefix,
    ) {
    }

    /**
     * Add a new log entry
     *
     * @param string $logType Type of log (info, warning, error)
     * @param string $logMessage Log message
     * @param string $objectType Type of object (order, etc.)
     * @param int $objectId ID of the object
     * @param int|null $ruleId ID of the rule that generated this log (optional)
     * @param array|null $additionalData Additional data to store with the log (will be JSON encoded)
     *
     * @return int The ID of the new log entry
     *
     * @throws EvoStatusFlowException If database insertion fails
     * @throws \JsonException If JSON encoding of additional data fails
     */
    public function add(
        string $logType,
        string $logMessage,
        string $objectType,
        int $objectId,
        ?int $ruleId = null,
        ?array $additionalData = null,
    ): int {
        try {
            $jsonData = null;
            if ($additionalData !== null) {
                $jsonData = json_encode($additionalData, JSON_THROW_ON_ERROR);
            }

            $this->connection->insert(
                $this->dbPrefix . 'evo_statusflow_log',
                [
                    'log_type' => $logType,
                    'log_message' => $logMessage,
                    'object_type' => $objectType,
                    'object_id' => $objectId,
                    'id_rule' => $ruleId,
                    'additional_data' => $jsonData,
                    'date_add' => (new \DateTime())->format('Y-m-d H:i:s'),
                ]
            );

            return (int) $this->connection->lastInsertId();
        } catch (\JsonException $e) {
            throw new EvoStatusFlowException('Failed to encode additional data: ' . $e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Failed to add log entry: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get logs with optional filtering
     *
     * @param array $filters Optional filters to apply
     * @param int $limit Maximum number of logs to return
     * @param int $offset Starting offset for pagination
     * @param string $orderBy Field to order by
     * @param string $orderDirection Direction of ordering (ASC or DESC)
     *
     * @return array List of logs
     *
     * @throws EvoStatusFlowException If database query fails
     * @throws \InvalidArgumentException If invalid order field or direction is provided
     */
    public function getLogs(
        array $filters = [],
        int $limit = 50,
        int $offset = 0,
        string $orderBy = 'date_add',
        string $orderDirection = 'DESC',
    ): array {
        try {
            // Validate orderBy field to prevent SQL injection
            $allowedOrderFields = ['id_log', 'log_type', 'log_message', 'object_type', 'object_id', 'id_rule', 'date_add'];
            if (!in_array($orderBy, $allowedOrderFields, true)) {
                throw new \InvalidArgumentException('Invalid order field: ' . $orderBy);
            }

            // Validate orderDirection
            $orderDirection = strtoupper($orderDirection);
            if (!in_array($orderDirection, ['ASC', 'DESC'], true)) {
                throw new \InvalidArgumentException('Invalid order direction: ' . $orderDirection);
            }

            $qb = $this->createLogsQueryBuilder($filters);

            $qb->setFirstResult($offset)
                ->setMaxResults($limit)
                ->orderBy('l.' . $orderBy, $orderDirection);

            return $qb->execute()->fetchAllAssociative();
        } catch (\InvalidArgumentException $e) {
            throw $e; // Rethrow this specific exception
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Failed to retrieve logs: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get a log by its ID
     *
     * @param int $logId Log ID
     *
     * @return array|null Log data or null if not found
     */
    public function getById(int $logId): ?array
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('l.*')
            ->from($this->dbPrefix . 'evo_statusflow_log', 'l')
            ->where('l.id_log = :logId')
            ->setParameter('logId', $logId);

        $result = $qb->execute()->fetchAssociative();

        return $result ?: null;
    }

    /**
     * Delete logs older than a specified number of days
     *
     * @param int $days Number of days to keep logs
     *
     * @return int Number of deleted logs
     *
     * @throws EvoStatusFlowException If database operation fails
     */
    public function deleteOldLogs(int $days): int
    {
        try {
            if ($days <= 0) {
                $days = 30;
            }

            $date = new \DateTime();
            $date->modify("-{$days} days");
            $formattedDate = $date->format('Y-m-d H:i:s');

            // First count records that will be deleted
            $countSql = 'SELECT COUNT(*) FROM ' . $this->dbPrefix . 'evo_statusflow_log WHERE date_add < :date';
            $count = (int) $this->connection->executeQuery(
                $countSql,
                ['date' => $formattedDate]
            )->fetchOne();

            // Then delete them
            $sql = 'DELETE FROM ' . $this->dbPrefix . 'evo_statusflow_log WHERE date_add < :date';
            $this->connection->executeStatement(
                $sql,
                ['date' => $formattedDate]
            );

            return $count;
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Failed to delete old logs: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a log by its ID
     *
     * @param int $logId Log ID
     *
     * @return bool Success status
     *
     * @throws EvoStatusFlowException If database operation fails
     */
    public function deleteLog(int $logId): bool
    {
        try {
            if (class_exists('PrestaShopLogger')) {
                \PrestaShopLogger::addLog(
                    'Attempting to delete log ID: ' . $logId,
                    1,
                    null,
                    'EvoStatusFlow',
                    $logId,
                    true
                );
            }

            $result = $this->connection->delete(
                $this->dbPrefix . 'evo_statusflow_log',
                ['id_log' => $logId]
            );

            return $result > 0;
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Failed to delete log: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete all logs
     *
     * @return int Number of deleted logs
     *
     * @throws EvoStatusFlowException If database operation fails
     */
    public function deleteAllLogs(): int
    {
        try {
            // First count records that will be deleted
            $countSql = 'SELECT COUNT(*) FROM ' . $this->dbPrefix . 'evo_statusflow_log';
            $count = (int) $this->connection->executeQuery($countSql)->fetchOne();

            // Then delete them
            $sql = 'DELETE FROM ' . $this->dbPrefix . 'evo_statusflow_log';
            $this->connection->executeStatement($sql);

            return $count;
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Failed to delete all logs: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a query builder for logs with filters
     *
     * @param array $filters Filters to apply
     *
     * @return QueryBuilder
     */
    private function createLogsQueryBuilder(array $filters = []): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder();
        $qb->select('l.*')
            ->from($this->dbPrefix . 'evo_statusflow_log', 'l');

        if (isset($filters['include_rule_info']) && $filters['include_rule_info']) {
            $qb->addSelect('r.id_order_state_from, r.id_order_state_to')
                ->leftJoin(
                    'l',
                    $this->dbPrefix . 'evo_statusflow_rule',
                    'r',
                    'l.id_rule = r.id_rule'
                );
        }

        if (!empty($filters['log_type'])) {
            $qb->andWhere('l.log_type = :logType')
                ->setParameter('logType', $filters['log_type']);
        }

        if (!empty($filters['object_type'])) {
            $qb->andWhere('l.object_type = :objectType')
                ->setParameter('objectType', $filters['object_type']);
        }

        if (!empty($filters['object_id'])) {
            $qb->andWhere('l.object_id = :objectId')
                ->setParameter('objectId', $filters['object_id']);
        }

        if (!empty($filters['id_rule'])) {
            $qb->andWhere('l.id_rule = :ruleId')
                ->setParameter('ruleId', $filters['id_rule']);
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('l.date_add >= :dateFrom')
                ->setParameter('dateFrom', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('l.date_add <= :dateTo')
                ->setParameter('dateTo', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('l.log_message LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb;
    }
}
