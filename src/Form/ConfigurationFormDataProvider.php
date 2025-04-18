<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Form;

use Configuration;

class ConfigurationFormDataProvider
{
    private const CONFIG_PREFIX = 'EVO_STATUSFLOW_';

    /**
     * Get data for the configuration form
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'cron_frequency' => \Configuration::get(self::CONFIG_PREFIX . 'CRON_FREQUENCY', 'daily'),
            'batch_size' => (int) \Configuration::get(self::CONFIG_PREFIX . 'BATCH_SIZE', 50),

            'enable_logging' => (bool) \Configuration::get(self::CONFIG_PREFIX . 'ENABLE_LOGGING', false),
            'enable_db_logging' => (bool) \Configuration::get(self::CONFIG_PREFIX . 'ENABLE_DB_LOGGING', true),
            'log_retention_days' => (int) \Configuration::get(self::CONFIG_PREFIX . 'LOG_RETENTION_DAYS', 30),

            'notification_email' => \Configuration::get(self::CONFIG_PREFIX . 'NOTIFICATION_EMAIL', ''),
        ];
    }

    /**
     * Save the configuration data
     *
     * @param array $data Configuration data to save
     *
     * @return array Array of errors, empty if no errors
     */
    public function setData(array $data): array
    {
        $errors = [];
        $result = true;

        // Save general settings
        $result &= \Configuration::updateValue(
            self::CONFIG_PREFIX . 'CRON_FREQUENCY',
            $data['cron_frequency']
        );

        $result &= \Configuration::updateValue(
            self::CONFIG_PREFIX . 'BATCH_SIZE',
            (int) $data['batch_size']
        );

        // Save logging settings
        $result &= \Configuration::updateValue(
            self::CONFIG_PREFIX . 'ENABLE_LOGGING',
            (bool) $data['enable_logging']
        );

        $result &= \Configuration::updateValue(
            self::CONFIG_PREFIX . 'ENABLE_DB_LOGGING',
            (bool) $data['enable_db_logging']
        );

        $result &= \Configuration::updateValue(
            self::CONFIG_PREFIX . 'LOG_RETENTION_DAYS',
            (int) $data['log_retention_days']
        );

        // Save notification settings
        $result &= \Configuration::updateValue(
            self::CONFIG_PREFIX . 'NOTIFICATION_EMAIL',
            $data['notification_email']
        );

        if (!$result) {
            $errors[] = 'Failed to save one or more configuration values';
        }

        return $errors;
    }
}
