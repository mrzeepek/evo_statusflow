<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Service;

use Configuration;
use Evolutive\Module\EvoStatusFlow\Exception\EvoStatusFlowException;
use Evolutive\Module\EvoStatusFlow\Repository\LogRepository;
use Monolog\Logger;

/**
 * Service for managing status flow logs
 */
class LoggingService
{
    private const CONFIG_ENABLE_DB_LOGGING = 'EVO_STATUSFLOW_ENABLE_DB_LOGGING';
    private const CONFIG_LOG_RETENTION_DAYS = 'EVO_STATUSFLOW_LOG_RETENTION_DAYS';
    public const TYPE_INFO = 'info';
    public const TYPE_WARNING = 'warning';
    public const TYPE_ERROR = 'error';

    /**
     * @param LogRepository $logRepository Repository for log operations
     * @param Logger $logger Monolog logger service
     */
    public function __construct(
        private readonly LogRepository $logRepository,
        private readonly Logger $logger,
    ) {
    }

    /**
     * Log an informational message
     *
     * @param string $message Log message
     * @param string $objectType Type of object
     * @param int $objectId ID of the object
     * @param int|null $ruleId ID of the rule (optional)
     * @param array|null $context Additional context data
     *
     * @throws EvoStatusFlowException If logging to database fails
     */
    public function info(
        string $message,
        string $objectType,
        int $objectId,
        ?int $ruleId = null,
        ?array $context = null,
    ): void {
        $this->log(self::TYPE_INFO, $message, $objectType, $objectId, $ruleId, $context);
    }

    /**
     * Log a warning message
     *
     * @param string $message Log message
     * @param string $objectType Type of object
     * @param int $objectId ID of the object
     * @param int|null $ruleId ID of the rule (optional)
     * @param array|null $context Additional context data
     *
     * @throws EvoStatusFlowException If logging to database fails
     */
    public function warning(
        string $message,
        string $objectType,
        int $objectId,
        ?int $ruleId = null,
        ?array $context = null,
    ): void {
        $this->log(self::TYPE_WARNING, $message, $objectType, $objectId, $ruleId, $context);
    }

    /**
     * Log an error message
     *
     * @param string $message Log message
     * @param string $objectType Type of object
     * @param int $objectId ID of the object
     * @param int|null $ruleId ID of the rule (optional)
     * @param array|null $context Additional context data
     *
     * @throws EvoStatusFlowException If logging to database fails
     */
    public function error(
        string $message,
        string $objectType,
        int $objectId,
        ?int $ruleId = null,
        ?array $context = null,
    ): void {
        $this->log(self::TYPE_ERROR, $message, $objectType, $objectId, $ruleId, $context);
    }

    /**
     * Check if database logging is enabled in configuration
     *
     * @return bool
     *
     * @throws \PrestaShopException If configuration access fails
     */
    public function isDatabaseLoggingEnabled(): bool
    {
        return (bool) \Configuration::get(self::CONFIG_ENABLE_DB_LOGGING, true);
    }

    /**
     * Get the configured log retention period in days
     *
     * @return int
     *
     * @throws \PrestaShopException If configuration access fails
     */
    public function getLogRetentionDays(): int
    {
        $days = (int) \Configuration::get(self::CONFIG_LOG_RETENTION_DAYS, 30);

        return ($days <= 0) ? 30 : $days; // Ensure valid value
    }

    /**
     * Clean up old logs based on retention period
     *
     * @param int|null $days Optional number of days to override the configured retention period
     *
     * @return int Number of deleted logs
     *
     * @throws EvoStatusFlowException If log deletion fails
     * @throws \PrestaShopException If configuration access fails
     */
    public function cleanOldLogs(?int $days = null): int
    {
        try {
            $retentionDays = $days ?? $this->getLogRetentionDays();

            $this->logger->info('Cleaning old logs', [
                'retention_days' => $retentionDays,
            ]);

            $deletedCount = $this->logRepository->deleteOldLogs($retentionDays);

            $this->logger->info('Logs cleanup completed', [
                'deleted_count' => $deletedCount,
            ]);

            return $deletedCount;
        } catch (\Exception $e) {
            $this->logger->error('Failed to clean old logs: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($e instanceof EvoStatusFlowException) {
                throw $e;
            }

            throw new EvoStatusFlowException('Failed to clean old logs: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get a log by its ID
     *
     * @param int $logId Log ID
     *
     * @return array|null Log data or null if not found
     */
    public function getLogById(int $logId): ?array
    {
        return $this->logRepository->getById($logId);
    }

    /**
     * Delete a log by its ID
     *
     * @param int $logId Log ID
     *
     * @return bool Success status
     *
     * @throws EvoStatusFlowException If deletion fails
     */
    public function deleteLog(int $logId): bool
    {
        return $this->logRepository->deleteLog($logId);
    }

    /**
     * Delete all logs
     *
     * @return int Number of deleted logs
     *
     * @throws EvoStatusFlowException If deletion fails
     */
    public function deleteAllLogs(): int
    {
        return $this->logRepository->deleteAllLogs();
    }

    /**
     * General log method that handles both Monolog and database logging
     *
     * @param string $type Log type
     * @param string $message Log message
     * @param string $objectType Type of object
     * @param int $objectId ID of the object
     * @param int|null $ruleId ID of the rule (optional)
     * @param array|null $context Additional context data
     *
     * @throws EvoStatusFlowException If database logging fails and is crucial
     * @throws \PrestaShopException
     */
    private function log(
        string $type,
        string $message,
        string $objectType,
        int $objectId,
        ?int $ruleId = null,
        ?array $context = null,
    ): void {
        $logContext = [
            'object_type' => $objectType,
            'object_id' => $objectId,
            'rule_id' => $ruleId,
        ];

        if ($context !== null) {
            $logContext = array_merge($logContext, $context);
        }

        // Log to Monolog
        switch ($type) {
            case self::TYPE_INFO:
                $this->logger->info($message, $logContext);
                break;
            case self::TYPE_WARNING:
                $this->logger->warning($message, $logContext);
                break;
            case self::TYPE_ERROR:
                $this->logger->error($message, $logContext);
                break;
            default:
                $this->logger->warning(
                    sprintf('Unknown log type "%s", defaulting to info level', $type),
                    array_merge($logContext, ['original_message' => $message])
                );
                $this->logger->info($message, $logContext);
                break;
        }

        if ($this->isDatabaseLoggingEnabled()) {
            try {
                $this->logRepository->add(
                    $type,
                    $message,
                    $objectType,
                    $objectId,
                    $ruleId,
                    $context
                );
            } catch (\Exception $e) {
                $this->logger->error('Failed to save log to database: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Only re-throw for error logs or critical functionality
                if ($type === self::TYPE_ERROR) {
                    throw new EvoStatusFlowException('Failed to save critical error log to database: ' . $e->getMessage(), 0, $e);
                }
            }
        }
    }
}
