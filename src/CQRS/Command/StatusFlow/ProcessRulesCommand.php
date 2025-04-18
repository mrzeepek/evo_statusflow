<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Command\StatusFlow;

/**
 * Command to process status flow rules
 */
class ProcessRulesCommand
{
    /**
     * @param string|null $objectType Type of object to process (e.g. 'order')
     * @param bool $dryRun If true, simulates changes without applying them
     * @param int|null $ruleId Specific rule ID to process
     */
    public function __construct(
        public readonly ?string $objectType = null,
        public readonly bool $dryRun = false,
        public readonly ?int $ruleId = null,
    ) {
    }
}
