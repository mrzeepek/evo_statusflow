<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Form;

use Evolutive\Module\EvoStatusFlow\Repository\RuleRepository;

class RuleFormDataProvider
{
    /**
     * @param RuleRepository $ruleRepository Repository for rule operations
     */
    public function __construct(
        private readonly RuleRepository $ruleRepository,
    ) {
    }

    /**
     * Get data for a form
     *
     * @param int|null $ruleId
     *
     * @return array
     *
     * @throws \RuntimeException If the rule cannot be found
     * @throws \PDOException If there is a database connection issue
     */
    public function getData(?int $ruleId = null): array
    {
        if ($ruleId === null) {
            return [
                'id_order_state_from' => null,
                'id_order_state_to' => null,
                'delay_hours' => 0,
                'condition_sql' => '',
                'auto_execute' => true,
                'active' => true,
            ];
        }

        try {
            $rule = $this->ruleRepository->getById($ruleId);

            if (!$rule) {
                throw new \RuntimeException(sprintf('Rule with ID %d not found', $ruleId));
            }

            return [
                'id_order_state_from' => (int) $rule['id_order_state_from'],
                'id_order_state_to' => (int) $rule['id_order_state_to'],
                'delay_hours' => (int) $rule['delay_hours'],
                'condition_sql' => $rule['condition_sql'],
                'auto_execute' => (bool) $rule['auto_execute'],
                'active' => (bool) $rule['active'],
            ];
        } catch (\PDOException $e) {
            // Log the database error
            \PrestaShopLogger::addLog(
                'Error fetching rule data: ' . $e->getMessage(),
                3,
                null,
                'EvoStatusFlow',
                $ruleId ?? 0,
                true
            );
            throw $e;
        }
    }

    /**
     * Save form data
     *
     * @param array $data Form data
     * @param int|null $ruleId Rule ID for updates
     *
     * @return bool|int Returns the new ID or true for updates
     */
    public function saveData(array $data, ?int $ruleId = null): bool|int
    {
        if ($ruleId === null) {
            return $this->ruleRepository->create($data);
        }

        return $this->ruleRepository->update($ruleId, $data);
    }
}
