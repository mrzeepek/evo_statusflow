<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\Log;

/**
 * Command to clean up old logs
 */
class CleanLogsCommand
{
    /**
     * @param int|null $days Retention period in days (optional, uses default configuration if not specified)
     */
    public function __construct(
        public readonly ?int $days = null,
    ) {
    }
}
