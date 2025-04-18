<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Repository;

use Doctrine\DBAL\Connection;
use Evolutive\Module\EvoStatusFlow\Exception\EvoStatusFlowException;
use Evolutive\Module\EvoStatusFlow\Exception\RuleNotFoundException;

class RuleRepository
{
    /**
     * @param Connection $connection Database connection
     * @param string $dbPrefix Database table prefix
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly string $dbPrefix,
    ) {
    }

    /**
     * Get rule by ID
     *
     * @param int $ruleId Rule ID
     *
     * @return array Rule data
     *
     * @throws RuleNotFoundException If rule not found
     * @throws EvoStatusFlowException If database operation fails
     */
    public function getById(int $ruleId): array
    {
        try {
            $tableName = $this->dbPrefix . 'evo_statusflow_rule';

            $qb = $this->connection->createQueryBuilder();
            $qb->select('*')
                ->from($tableName)
                ->where('id_rule = :id_rule')
                ->setParameter('id_rule', $ruleId);

            $result = $qb->execute()->fetchAssociative();

            if (!$result) {
                throw new RuleNotFoundException(sprintf('Rule with ID %d not found', $ruleId));
            }

            return $result;
        } catch (RuleNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Error retrieving rule: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create a new rule
     *
     * @param array $data Rule data
     *
     * @return int ID of the created rule
     *
     * @throws EvoStatusFlowException If database operation fails
     */
    public function create(array $data): int
    {
        try {
            $tableName = $this->dbPrefix . 'evo_statusflow_rule';
            $now = new \DateTime();

            $this->connection->insert($tableName, [
                'id_order_state_from' => $data['id_order_state_from'],
                'id_order_state_to' => $data['id_order_state_to'],
                'delay_hours' => $data['delay_hours'],
                'condition_sql' => $data['condition_sql'],
                'auto_execute' => (int) $data['auto_execute'],
                'active' => (int) $data['active'],
                'date_add' => $now->format('Y-m-d H:i:s'),
                'date_upd' => $now->format('Y-m-d H:i:s'),
            ]);

            return (int) $this->connection->lastInsertId();
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Failed to create rule: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Update an existing rule
     *
     * @param int $ruleId Rule ID
     * @param array $data Rule data
     *
     * @return bool Success status
     *
     * @throws EvoStatusFlowException If database operation fails
     * @throws RuleNotFoundException If rule not found
     */
    public function update(int $ruleId, array $data): bool
    {
        try {
            $tableName = $this->dbPrefix . 'evo_statusflow_rule';
            $now = new \DateTime();

            // Check if rule exists first
            $this->getById($ruleId);

            $result = $this->connection->update($tableName, [
                'id_order_state_from' => $data['id_order_state_from'],
                'id_order_state_to' => $data['id_order_state_to'],
                'delay_hours' => $data['delay_hours'],
                'condition_sql' => $data['condition_sql'],
                'auto_execute' => (int) $data['auto_execute'],
                'active' => (int) $data['active'],
                'date_upd' => $now->format('Y-m-d H:i:s'),
            ], [
                'id_rule' => $ruleId,
            ]);

            return $result > 0;
        } catch (RuleNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Failed to update rule: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete a rule
     *
     * @param int $ruleId Rule ID
     *
     * @return bool Success status
     *
     * @throws EvoStatusFlowException If database operation fails
     * @throws RuleNotFoundException If rule not found
     */
    public function delete(int $ruleId): bool
    {
        try {
            // Check if rule exists first
            $this->getById($ruleId);

            $tableName = $this->dbPrefix . 'evo_statusflow_rule';

            // Log l'opération pour débogage
            if (class_exists('PrestaShopLogger')) {
                \PrestaShopLogger::addLog(
                    'Attempting to delete rule ID: ' . $ruleId,
                    1,
                    null,
                    'EvoStatusFlow',
                    $ruleId,
                    true
                );
            }

            $result = $this->connection->delete($tableName, [
                'id_rule' => $ruleId,
            ]);

            // Log le résultat pour débogage
            if (class_exists('PrestaShopLogger')) {
                \PrestaShopLogger::addLog(
                    'Delete result for rule ID ' . $ruleId . ': ' . ($result > 0 ? 'success' : 'failure'),
                    1,
                    null,
                    'EvoStatusFlow',
                    $ruleId,
                    true
                );
            }

            return $result > 0;
        } catch (RuleNotFoundException $e) {
            // Log l'erreur pour débogage
            if (class_exists('PrestaShopLogger')) {
                \PrestaShopLogger::addLog(
                    'Rule not found during delete: ' . $e->getMessage(),
                    3,
                    null,
                    'EvoStatusFlow',
                    $ruleId,
                    true
                );
            }
            throw $e;
        } catch (\Exception $e) {
            // Log l'erreur pour débogage
            if (class_exists('PrestaShopLogger')) {
                \PrestaShopLogger::addLog(
                    'Failed to delete rule: ' . $e->getMessage(),
                    3,
                    null,
                    'EvoStatusFlow',
                    $ruleId,
                    true
                );
            }
            throw new EvoStatusFlowException('Failed to delete rule: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Toggle active status for a rule
     *
     * @param int $ruleId Rule ID
     *
     * @return bool Success status
     *
     * @throws EvoStatusFlowException If database operation fails
     * @throws RuleNotFoundException If rule not found
     */
    public function toggleActive(int $ruleId): bool
    {
        try {
            $rule = $this->getById($ruleId);
            $newActiveStatus = !$rule['active'];

            $tableName = $this->dbPrefix . 'evo_statusflow_rule';
            $now = new \DateTime();

            $result = $this->connection->update($tableName, [
                'active' => (int) $newActiveStatus,
                'date_upd' => $now->format('Y-m-d H:i:s'),
            ], [
                'id_rule' => $ruleId,
            ]);

            return $result > 0;
        } catch (RuleNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Failed to toggle rule active status: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Toggle auto-execute status for a rule
     *
     * @param int $ruleId Rule ID
     *
     * @return bool Success status
     *
     * @throws EvoStatusFlowException If database operation fails
     * @throws RuleNotFoundException If rule not found
     */
    public function toggleAutoExecute(int $ruleId): bool
    {
        try {
            $rule = $this->getById($ruleId);
            $newAutoExecuteStatus = !$rule['auto_execute'];

            $tableName = $this->dbPrefix . 'evo_statusflow_rule';
            $now = new \DateTime();

            $result = $this->connection->update($tableName, [
                'auto_execute' => (int) $newAutoExecuteStatus,
                'date_upd' => $now->format('Y-m-d H:i:s'),
            ], [
                'id_rule' => $ruleId,
            ]);

            return $result > 0;
        } catch (RuleNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Failed to toggle rule auto-execute status: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get all active rules
     *
     * @param int|null $ruleId Optional specific rule ID
     *
     * @return array List of rules
     *
     * @throws EvoStatusFlowException If database operation fails
     */
    public function getActiveRules(?int $ruleId = null): array
    {
        try {
            $tableName = $this->dbPrefix . 'evo_statusflow_rule';

            $qb = $this->connection->createQueryBuilder();
            $qb->select('*')
                ->from($tableName)
                ->where('active = 1');

            if ($ruleId !== null) {
                $qb->andWhere('id_rule = :id_rule')
                    ->setParameter('id_rule', $ruleId);
            }

            return $qb->execute()->fetchAllAssociative();
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Failed to retrieve active rules: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get all auto-execute rules
     *
     * @return array List of rules
     *
     * @throws EvoStatusFlowException If database operation fails
     */
    public function getAutoExecuteRules(): array
    {
        try {
            $tableName = $this->dbPrefix . 'evo_statusflow_rule';

            $qb = $this->connection->createQueryBuilder();
            $qb->select('*')
                ->from($tableName)
                ->where('active = 1')
                ->andWhere('auto_execute = 1');

            return $qb->execute()->fetchAllAssociative();
        } catch (\Exception $e) {
            throw new EvoStatusFlowException('Failed to retrieve auto-execute rules: ' . $e->getMessage(), 0, $e);
        }
    }
}
