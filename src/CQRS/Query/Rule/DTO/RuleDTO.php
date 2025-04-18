<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Rule\DTO;

/**
 * Data Transfer Object (DTO) representing a rule
 */
class RuleDTO
{
    /**
     * @param int $id Rule ID
     * @param int $idOrderStateFrom Origin order state ID
     * @param int $idOrderStateTo Destination order state ID
     * @param int $delayHours Delay in hours
     * @param string|null $conditionSql Optional SQL condition
     * @param bool $autoExecute Whether the rule is automatically executed
     * @param bool $active Whether the rule is active
     * @param string $dateAdd Creation date
     * @param string $dateUpd Last update date
     * @param string|null $fromOrderStateName Name of the origin order state (for display)
     * @param string|null $toOrderStateName Name of the destination order state (for display)
     */
    public function __construct(
        public readonly int $id,
        public readonly int $idOrderStateFrom,
        public readonly int $idOrderStateTo,
        public readonly int $delayHours,
        public readonly ?string $conditionSql,
        public readonly bool $autoExecute,
        public readonly bool $active,
        public readonly string $dateAdd,
        public readonly string $dateUpd,
        public readonly ?string $fromOrderStateName = null,
        public readonly ?string $toOrderStateName = null,
    ) {
    }

    /**
     * Creates a RuleDTO instance from an array of data
     *
     * @param array $data Rule data array
     *
     * @return self New RuleDTO instance
     */
    public static function createFromArray(array $data): self
    {
        return new self(
            id: (int) $data['id_rule'],
            idOrderStateFrom: (int) $data['id_order_state_from'],
            idOrderStateTo: (int) $data['id_order_state_to'],
            delayHours: (int) $data['delay_hours'],
            conditionSql: $data['condition_sql'],
            autoExecute: (bool) $data['auto_execute'],
            active: (bool) $data['active'],
            dateAdd: (string) $data['date_add'],
            dateUpd: (string) $data['date_upd'],
            fromOrderStateName: $data['from_order_state_name'] ?? null,
            toOrderStateName: $data['to_order_state_name'] ?? null
        );
    }
}
