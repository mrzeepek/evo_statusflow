<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Evolutive\Module\EvoStatusFlow\Install\Installer;
use Psr\Log\LoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Evo Status Flow Module.
 *
 * Manages order status transitions and workflows.
 */
class evo_statusflow extends Module
{
    /**
     * Module constructor.
     */
    public function __construct()
    {
        $this->name = 'evo_statusflow';
        $this->tab = 'administration';
        $this->author = 'Evolutive';
        $this->version = '1.0.0';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->ps_versions_compliancy = [
            'min' => '8.0',
            'max' => _PS_VERSION_,
        ];

        $this->displayName = $this->trans('Advanced Order Status Management', [], 'Modules.Evostatusflow.Admin');
        $this->description = $this->trans('Manage order statuses and automate status transitions with rules and cron jobs', [], 'Modules.Evostatusflow.Admin');
        $this->confirmUninstall = $this->trans('Are you sure that you want to uninstall? This will remove all transition rules and logs.', [], 'Modules.Evostatusflow.Admin');
    }

    /**
     * Install the module
     *
     * @return bool Success status
     */
    public function install(): bool
    {
        try {
            if (!parent::install()) {
                $this->_errors[] = 'Parent::install() failed.';

                return false;
            }

            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');

            return (new Installer($logger))->install($this);
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();

            return false;
        }
    }

    /**
     * Uninstall the module
     *
     * @return bool Success status
     */
    public function uninstall(): bool
    {
        try {
            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');

            return parent::uninstall() && (new Installer($logger))->uninstall($this);
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();

            return false;
        }
    }

    /**
     * Upgrade the module to a new version
     *
     * @param string $version The version to upgrade to
     *
     * @return bool Success status
     */
    public function upgrade(string $version): bool
    {
        try {
            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');

            return (new Installer($logger))->upgrade($this, $version);
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();

            return false;
        }
    }

    /**
     * Indicates if this module uses the new translation system
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    /**
     * Redirect to the module configuration page
     */
    public function getContent(): void
    {
        $route = $this->get('router')->generate('evo_statusflow_configuration');
        Tools::redirectAdmin($route);
    }
}
