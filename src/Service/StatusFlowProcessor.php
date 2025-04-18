<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Service;

use Doctrine\DBAL\Connection;
use Evolutive\Module\EvoStatusFlow\Exception\StatusFlowCommandException;
use Evolutive\Module\EvoStatusFlow\Repository\HistoryRepository;
use Evolutive\Module\EvoStatusFlow\Repository\RuleRepository;
use Monolog\Logger;
use Order;

class StatusFlowProcessor
{
    /**
     * @param RuleRepository $ruleRepository Repository for status flow rules
     * @param HistoryRepository $historyRepository Repository for status change history
     * @param LoggingService $loggingService Service to handle detailed logging
     * @param Logger $logger Logging service
     * @param Connection $connection Database connection
     * @param string $dbPrefix Database prefix
     */
    public function __construct(
        private readonly RuleRepository $ruleRepository,
        private readonly HistoryRepository $historyRepository,
        private readonly LoggingService $loggingService,
        private readonly Logger $logger,
        private readonly Connection $connection,
        private readonly string $dbPrefix,
    ) {
    }

    /**
     * Process status flow rules based on criteria
     *
     * @param string|null $objectType Filter by object type (e.g. 'order')
     * @param bool $dryRun Whether to only simulate changes
     * @param int|null $ruleId Process only a specific rule
     *
     * @return int Number of processed transitions
     *
     * @throws StatusFlowCommandException On processing error
     * @throws \PrestaShopException If an error occurs with PrestaShop objects
     * @throws \PrestaShopDatabaseException If a database error occurs
     */
    public function processRules(?string $objectType = null, bool $dryRun = false, ?int $ruleId = null): int
    {
        try {
            $rules = $ruleId ?
                $this->ruleRepository->getActiveRules($ruleId) :
                $this->ruleRepository->getAutoExecuteRules();

            if (empty($rules)) {
                $this->logger->warning('No active rules found for processing', [
                    'rule_id' => $ruleId,
                    'object_type' => $objectType,
                ]);

                return 0;
            }

            $processedCount = 0;

            foreach ($rules as $rule) {
                $processedCount += $this->processRule($rule, $objectType, $dryRun);
            }

            // Log the overall processing results
            $this->loggingService->info(
                sprintf('Processed %d status transitions', $processedCount),
                'system',
                0,
                $ruleId,
                [
                    'dry_run' => $dryRun,
                    'object_type' => $objectType,
                ]
            );

            return $processedCount;
        } catch (\Exception $e) {
            $this->logger->error('Error processing status flow rules', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Log detailed error information
            $this->loggingService->error(
                'Error processing status flow rules: ' . $e->getMessage(),
                'system',
                0,
                $ruleId,
                [
                    'trace' => $e->getTraceAsString(),
                    'dry_run' => $dryRun,
                    'object_type' => $objectType,
                ]
            );

            throw new StatusFlowCommandException('Error processing status flow rules: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Process a single rule
     *
     * @param array $rule The rule to process
     * @param string|null $objectType Filter by object type
     * @param bool $dryRun Whether to only simulate changes
     *
     * @return int Number of processed transitions
     *
     * @throws \PrestaShopDatabaseException If a database error occurs
     * @throws \PrestaShopException If a PrestaShop error occurs
     * @throws \Exception If any other error occurs
     */
    private function processRule(array $rule, ?string $objectType, bool $dryRun): int
    {
        $this->logger->info('Processing rule', [
            'rule_id' => $rule['id_rule'],
            'from_status' => $rule['id_order_state_from'],
            'to_status' => $rule['id_order_state_to'],
            'delay_hours' => $rule['delay_hours'],
            'dry_run' => $dryRun,
        ]);

        // Get objects eligible for status transition based on the rule
        $objects = $this->getEligibleObjects($rule, $objectType);

        if (empty($objects)) {
            $this->logger->info('No eligible objects found for rule', [
                'rule_id' => $rule['id_rule'],
            ]);

            // Log that no eligible objects were found
            $this->loggingService->info(
                'No eligible objects found for this rule',
                'rule',
                (int) $rule['id_rule'],
                (int) $rule['id_rule'],
                [
                    'from_status' => $rule['id_order_state_from'],
                    'to_status' => $rule['id_order_state_to'],
                    'dry_run' => $dryRun,
                ]
            );

            return 0;
        }

        $processedCount = 0;

        foreach ($objects as $object) {
            if ($this->updateObjectStatus($object, (int) $rule['id_order_state_to'], $dryRun, (int) $rule['id_rule'])) {
                ++$processedCount;
            }
        }

        $this->logger->info('Rule processing completed', [
            'rule_id' => $rule['id_rule'],
            'processed_count' => $processedCount,
            'dry_run' => $dryRun,
        ]);

        // Log rule processing summary
        $this->loggingService->info(
            sprintf('Rule processed %d status transitions', $processedCount),
            'rule',
            (int) $rule['id_rule'],
            (int) $rule['id_rule'],
            [
                'from_status' => $rule['id_order_state_from'],
                'to_status' => $rule['id_order_state_to'],
                'eligible_objects' => count($objects),
                'dry_run' => $dryRun,
            ]
        );

        return $processedCount;
    }

    /**
     * Get objects eligible for a status transition
     *
     * @param array $rule The rule to apply
     * @param string|null $objectType Filter by object type
     *
     * @return array List of eligible objects
     *
     * @throws \Doctrine\DBAL\Exception If a database error occurs
     */
    private function getEligibleObjects(array $rule, ?string $objectType): array
    {
        $orderTable = $this->dbPrefix . 'orders';
        $orderHistoryTable = $this->dbPrefix . 'order_history';

        $qb = $this->connection->createQueryBuilder();
        $qb->select('o.id_order, o.current_state')
            ->from($orderTable, 'o')
            ->where('o.current_state = :current_state')
            ->setParameter('current_state', $rule['id_order_state_from']);

        // Add delay condition if needed
        if ((int) $rule['delay_hours'] > 0) {
            $delayTime = new \DateTime();
            $delayTime->modify('-' . (int) $rule['delay_hours'] . ' hours');

            $qb->innerJoin(
                'o',
                $orderHistoryTable,
                'oh',
                'o.id_order = oh.id_order AND oh.id_order_state = :from_state'
            )
                ->andWhere('oh.date_add <= :delay_time')
                ->setParameter('from_state', $rule['id_order_state_from'])
                ->setParameter('delay_time', $delayTime->format('Y-m-d H:i:s'));
        }

        if (!empty($rule['condition_sql'])) {
            $condition = str_replace('{id_order}', 'o.id_order', $rule['condition_sql']);
            $qb->andWhere($condition);
        }

        if ($objectType && $objectType !== 'order') {
            return [];
        }

        return $qb->execute()->fetchAllAssociative();
    }

    /**
     * Update the status of an object
     *
     * @param array $object The object data
     * @param int $newStatusId The new status ID
     * @param bool $dryRun Whether to only simulate changes
     * @param int $ruleId The rule ID that triggered this update
     *
     * @return bool Success status
     *
     * @throws \PrestaShopException If an error occurs with PrestaShop objects
     */
    private function updateObjectStatus(array $object, int $newStatusId, bool $dryRun, int $ruleId): bool
    {
        $idOrder = (int) $object['id_order'];
        $oldStatusId = (int) $object['current_state'];

        $this->logger->info('Updating object status', [
            'id_order' => $idOrder,
            'from_status' => $oldStatusId,
            'to_status' => $newStatusId,
            'dry_run' => $dryRun,
        ]);

        if ($dryRun) {
            // Log simulated status change
            $this->loggingService->info(
                sprintf('Simulated status change from %d to %d', $oldStatusId, $newStatusId),
                'order',
                $idOrder,
                $ruleId,
                [
                    'from_status' => $oldStatusId,
                    'to_status' => $newStatusId,
                    'dry_run' => true,
                ]
            );

            return true;
        }

        try {
            $order = new \Order($idOrder);
            if (!$order->id) {
                $this->logger->warning('Order not found', ['id_order' => $idOrder]);

                // Log error for order not found
                $this->loggingService->warning(
                    'Order not found',
                    'order',
                    $idOrder,
                    $ruleId
                );

                return false;
            }

            // Check if order is already in the target state
            if ($order->current_state === $newStatusId) {
                $this->logger->info('Order already in target state', [
                    'id_order' => $idOrder,
                    'status' => $newStatusId,
                ]);

                // Log info for order already in target state
                $this->loggingService->info(
                    'Order already in target state',
                    'order',
                    $idOrder,
                    $ruleId,
                    [
                        'status' => $newStatusId,
                    ]
                );

                return false;
            }

            // Update order status using PrestaShop's API
            $history = new \OrderHistory();
            $history->id_order = $idOrder;
            $history->changeIdOrderState($newStatusId, $idOrder);
            $history->add();

            // Record the transition in our history table
            $this->historyRepository->addEntry($idOrder, 'order', $oldStatusId, $newStatusId);

            $this->logger->info('Order status updated successfully', [
                'id_order' => $idOrder,
                'from_status' => $oldStatusId,
                'to_status' => $newStatusId,
            ]);

            // Log successful status change
            $this->loggingService->info(
                sprintf('Status changed from %d to %d', $oldStatusId, $newStatusId),
                'order',
                $idOrder,
                $ruleId,
                [
                    'from_status' => $oldStatusId,
                    'to_status' => $newStatusId,
                    'reference' => $order->reference,
                ]
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error updating order status', [
                'id_order' => $idOrder,
                'message' => $e->getMessage(),
            ]);

            // Log detailed error information
            $this->loggingService->error(
                'Error updating order status: ' . $e->getMessage(),
                'order',
                $idOrder,
                $ruleId,
                [
                    'from_status' => $oldStatusId,
                    'to_status' => $newStatusId,
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return false;
        }
    }
}
