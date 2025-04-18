<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Repository;

use Doctrine\DBAL\Connection;
use Evolutive\Module\EvoStatusFlow\Exception\StatusNotFoundException;
use Evolutive\Module\EvoStatusFlow\Exception\StatusRepositoryException;

class StatusRepository
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $dbPrefix,
    ) {
    }

    /**
     * Get all statuses
     *
     * @param bool|null $activeOnly Filter by active status
     *
     * @return array
     *
     * @throws StatusRepositoryException If database operation fails
     */
    public function getAll(?bool $activeOnly = null): array
    {
        try {
            $qb = $this->connection->createQueryBuilder()
                ->select('*')
                ->from($this->dbPrefix . 'evo_statusflow_status')
                ->orderBy('position', 'ASC');

            if ($activeOnly !== null) {
                $qb->andWhere('active = :active')
                    ->setParameter('active', $activeOnly ? 1 : 0, \PDO::PARAM_INT);
            }

            return $qb->execute()->fetchAllAssociative();
        } catch (\Exception $e) {
            throw new StatusRepositoryException('Error retrieving statuses: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get status by ID
     *
     * @param int $statusId
     *
     * @return array
     *
     * @throws StatusNotFoundException If status with the given ID is not found
     * @throws StatusRepositoryException If database operation fails
     */
    public function getById(int $statusId): array
    {
        try {
            $qb = $this->connection->createQueryBuilder()
                ->select('*')
                ->from($this->dbPrefix . 'evo_statusflow_status')
                ->where('id_status = :statusId')
                ->setParameter('statusId', $statusId, \PDO::PARAM_INT);

            $result = $qb->execute()->fetchAssociative();

            if (!$result) {
                throw new StatusNotFoundException(sprintf('Status with ID %d not found', $statusId));
            }

            return $result;
        } catch (StatusNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new StatusRepositoryException(sprintf('Error retrieving status %d: %s', $statusId, $e->getMessage()), 0, $e);
        }
    }

    /**
     * Create a new status
     *
     * @param array $data
     *
     * @return int New status ID
     *
     * @throws StatusRepositoryException If database operation fails
     */
    public function create(array $data): int
    {
        try {
            $now = (new \DateTime())->format('Y-m-d H:i:s');

            $this->connection->insert(
                $this->dbPrefix . 'evo_statusflow_status',
                [
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'active' => $data['active'] ?? 1,
                    'position' => $data['position'] ?? $this->getNextPosition(),
                    'date_add' => $now,
                    'date_upd' => $now,
                ]
            );

            return (int) $this->connection->lastInsertId();
        } catch (\Exception $e) {
            throw new StatusRepositoryException('Error creating status: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update an existing status
     *
     * @param int $statusId
     * @param array $data
     *
     * @return bool
     *
     * @throws StatusRepositoryException If database operation fails
     * @throws StatusNotFoundException If status with the given ID is not found (when called by other methods)
     */
    public function update(int $statusId, array $data): bool
    {
        try {
            $now = (new \DateTime())->format('Y-m-d H:i:s');

            $updateData = [
                'date_upd' => $now,
            ];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['code'])) {
                $updateData['code'] = $data['code'];
            }

            if (isset($data['active'])) {
                $updateData['active'] = $data['active'];
            }

            if (isset($data['position'])) {
                $updateData['position'] = $data['position'];
            }

            $updated = $this->connection->update(
                $this->dbPrefix . 'evo_statusflow_status',
                $updateData,
                ['id_status' => $statusId]
            );

            return $updated > 0;
        } catch (\Exception $e) {
            throw new StatusRepositoryException(sprintf('Error updating status %d: %s', $statusId, $e->getMessage()), 0, $e);
        }
    }

    /**
     * Delete a status
     *
     * @param int $statusId
     *
     * @return bool
     *
     * @throws StatusRepositoryException If database operation fails
     */
    public function delete(int $statusId): bool
    {
        try {
            $deleted = $this->connection->delete(
                $this->dbPrefix . 'evo_statusflow_status',
                ['id_status' => $statusId]
            );

            return $deleted > 0;
        } catch (\Exception $e) {
            throw new StatusRepositoryException(sprintf('Error deleting status %d: %s', $statusId, $e->getMessage()), 0, $e);
        }
    }

    /**
     * Toggle the active state of a status
     *
     * @param int $statusId
     *
     * @return bool
     *
     * @throws StatusNotFoundException If status with the given ID is not found
     * @throws StatusRepositoryException If database operation fails
     */
    public function toggleActive(int $statusId): bool
    {
        try {
            $status = $this->getById($statusId);
            $newActiveState = !$status['active'];

            return $this->update($statusId, ['active' => $newActiveState]);
        } catch (StatusNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new StatusRepositoryException(sprintf('Error toggling active state for status %d: %s', $statusId, $e->getMessage()), 0, $e);
        }
    }

    /**
     * Get the next available position
     *
     * @return int
     *
     * @throws StatusRepositoryException If database operation fails (caught internally)
     */
    private function getNextPosition(): int
    {
        try {
            $qb = $this->connection->createQueryBuilder()
                ->select('IFNULL(MAX(position), 0) + 1 as next_position')
                ->from($this->dbPrefix . 'evo_statusflow_status');

            $result = $qb->execute()->fetchAssociative();

            return (int) ($result['next_position'] ?? 1);
        } catch (\Exception) {
            return 1;
        }
    }
}
