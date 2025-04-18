<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Log\DTO;

/**
 * Collection of Log DTOs
 */
class LogCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array<LogDTO>
     */
    private array $logs;

    /**
     * @param LogDTO ...$logs Logs to add to the collection
     */
    public function __construct(LogDTO ...$logs)
    {
        $this->logs = $logs;
    }

    /**
     * Creates a collection from an array of log data
     *
     * @param array $logsData Array containing log data
     *
     * @return self New collection instance
     */
    public static function createFromArray(array $logsData): self
    {
        $logs = [];
        foreach ($logsData as $logData) {
            $logs[] = LogDTO::createFromArray($logData);
        }

        return new self(...$logs);
    }

    /**
     * @return \ArrayIterator<int,LogDTO>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->logs);
    }

    /**
     * Returns the number of logs in the collection
     *
     * @return int Count of logs
     */
    public function count(): int
    {
        return count($this->logs);
    }

    /**
     * Checks if the collection is empty
     *
     * @return bool True if the collection contains no logs
     */
    public function isEmpty(): bool
    {
        return empty($this->logs);
    }

    /**
     * Returns the logs as an array
     *
     * @return array<LogDTO> Array of log DTOs
     */
    public function toArray(): array
    {
        return $this->logs;
    }
}
