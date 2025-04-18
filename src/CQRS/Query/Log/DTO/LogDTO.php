<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\CQRS\Query\Log\DTO;

/**
 * Data Transfer Object (DTO) representing a log entry
 */
class LogDTO
{
    /**
     * @param int $id Log ID
     * @param string $logType Log type (info, warning, error)
     * @param string $logMessage Log message
     * @param string $objectType Type of the related object
     * @param int $objectId ID of the related object
     * @param int|null $ruleId ID of the associated rule (optional)
     * @param array|null $additionalData Additional data (optional)
     * @param string $dateAdd Creation date
     */
    public function __construct(
        public readonly int $id,
        public readonly string $logType,
        public readonly string $logMessage,
        public readonly string $objectType,
        public readonly int $objectId,
        public readonly ?int $ruleId,
        public readonly ?array $additionalData,
        public readonly string $dateAdd,
    ) {
    }

    /**
     * Creates a LogDTO instance from an array of data
     *
     * @param array $data Log data array
     *
     * @return self New LogDTO instance
     */
    public static function createFromArray(array $data): self
    {
        $additionalData = null;
        if (!empty($data['additional_data'])) {
            $additionalData = json_decode($data['additional_data'], true, 512, JSON_THROW_ON_ERROR);
        }

        return new self(
            id: (int) $data['id_log'],
            logType: (string) $data['log_type'],
            logMessage: (string) $data['log_message'],
            objectType: (string) $data['object_type'],
            objectId: (int) $data['object_id'],
            ruleId: isset($data['id_rule']) ? (int) $data['id_rule'] : null,
            additionalData: $additionalData,
            dateAdd: (string) $data['date_add']
        );
    }
}
