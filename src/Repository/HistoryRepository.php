<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Repository;

class HistoryRepository
{
    /**
     * @param LogRepository $logRepository Log repository for recording history
     */
    public function __construct(
        private readonly LogRepository $logRepository,
    ) {
    }

    /**
     * Add a new history entry for a status transition
     *
     * @param int $objectId Object ID (e.g. order ID)
     * @param string $objectType Object type (e.g. 'order')
     * @param int|null $fromStatusId Previous status ID
     * @param int $toStatusId New status ID
     * @param int|null $employeeId Employee who made the change (null for system/automated changes)
     *
     * @return int ID of the created history entry
     */
    public function addEntry(
        int $objectId,
        string $objectType,
        ?int $fromStatusId,
        int $toStatusId,
        ?int $employeeId = null,
    ): int {
        return $this->logRepository->add(
            'info',
            sprintf('Status changed from %d to %d', $fromStatusId ?? 0, $toStatusId), // Message
            $objectType,
            $objectId,
            null,
            [
                'from_status' => $fromStatusId,
                'to_status' => $toStatusId,
                'employee_id' => $employeeId,
                'type' => 'status_change',
            ]
        );
    }
}
