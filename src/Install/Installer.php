<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Install;

use Evolutive\Module\EvoStatusFlow\Exception\EvoStatusFlowException;
use PrestaShopBundle\Install\SqlLoader;
use Psr\Log\LoggerInterface;
use Tab;

/**
 * Handles installation, uninstallation and upgrade operations for the module.
 */
class Installer
{
    /**
     * @param LoggerInterface $logger Logger service for recording installation events
     */
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Install the module with hooks, tabs and database tables
     *
     * @param \evo_statusflow $module Module instance
     *
     * @return bool Success status
     *
     * @throws EvoStatusFlowException If installation fails
     */
    public function install(\evo_statusflow $module): bool
    {
        try {
            return $this->addTabs()
                && $this->executeSqlFromFile($module->getLocalPath() . 'src/Resources/data/install.sql');
        } catch (\Exception $e) {
            $this->logger->error('Module installation failed', [
                'error' => $e->getMessage(),
                'module' => $module->name,
            ]);

            throw new EvoStatusFlowException('Failed to install module: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Upgrade the module to a new version
     *
     * @param \evo_statusflow $module Module instance
     * @param string $version Version to upgrade to
     *
     * @return bool Success status
     *
     * @throws EvoStatusFlowException If upgrade fails
     */
    public function upgrade(\evo_statusflow $module, string $version): bool
    {
        try {
            return $module->registerHook($this->getHooks())
                && $this->removeTabs()
                && $this->addTabs();
        } catch (\Exception $e) {
            $this->logger->error('Module upgrade failed', [
                'error' => $e->getMessage(),
                'module' => $module->name,
                'version' => $version,
            ]);

            throw new EvoStatusFlowException('Failed to upgrade module: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Uninstall the module with database cleanup
     *
     * @param \evo_statusflow $module Module instance
     *
     * @return bool Success status
     *
     * @throws EvoStatusFlowException If uninstallation fails
     */
    public function uninstall(\evo_statusflow $module): bool
    {
        try {
            return $this->removeTabs()
                && $this->executeSqlFromFile($module->getLocalPath() . 'src/Resources/data/uninstall.sql');
        } catch (\Exception $e) {
            $this->logger->error('Module uninstallation failed', [
                'error' => $e->getMessage(),
                'module' => $module->name,
            ]);

            throw new EvoStatusFlowException('Failed to uninstall module: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get tab definitions for the module
     *
     * @return array Tab configuration data
     */
    private function getTabDefinitions(): array
    {
        $mainTab = \Tab::getInstanceFromClassName('AdminParentModulesSf');
        $mainTabId = $mainTab->id;

        $tabNames = [];
        $tabConfigurationName = [];
        $tabRulesName = [];
        $tabLogsName = [];

        foreach (\Language::getLanguages() as $language) {
            $tabNames[$language['id_lang']] = 'Status Flow Manager';
            $tabConfigurationName[$language['id_lang']] = 'Configuration';
            $tabRulesName[$language['id_lang']] = 'Flow Rules';
            $tabLogsName[$language['id_lang']] = 'Logs';
        }

        return [
            [
                'class_name' => 'AdminEvoStatusFlow',
                'id_parent' => $mainTabId,
                'module' => 'evo_statusflow',
                'name' => $tabNames,
                'wording' => 'Status Flow Manager',
                'wording_domain' => 'Modules.Evostatusflow.Admin',
            ],
            [
                'class_name' => 'AdminEvoStatusFlowConfiguration',
                'route_name' => 'evo_statusflow_configuration',
                'id_parent' => null, // Will be set dynamically
                'module' => 'evo_statusflow',
                'name' => $tabConfigurationName,
                'wording' => 'Configuration',
                'wording_domain' => 'Modules.Evostatusflow.Admin',
            ],
            [
                'class_name' => 'AdminEvoStatusFlowRules',
                'route_name' => 'evo_statusflow_rule_index',
                'id_parent' => null, // Will be set dynamically
                'module' => 'evo_statusflow',
                'name' => $tabRulesName,
                'wording' => 'Flow Rules',
                'wording_domain' => 'Modules.Evostatusflow.Admin',
            ],
            [
                'class_name' => 'AdminEvoStatusFlowLogs',
                'route_name' => 'evo_statusflow_log_index',
                'id_parent' => null, // Will be set dynamically
                'module' => 'evo_statusflow',
                'name' => $tabLogsName,
                'wording' => 'Logs',
                'wording_domain' => 'Modules.Evostatusflow.Admin',
            ],
        ];
    }

    /**
     * Add tabs for the module administration
     *
     * @return bool Success status
     *
     * @throws EvoStatusFlowException If tab creation fails
     */
    private function addTabs(): bool
    {
        try {
            $tabs = $this->getTabDefinitions();
            $parentTabId = null;

            // First pass: install the parent tab
            foreach ($tabs as $tabData) {
                if ($tabData['class_name'] === 'AdminEvoStatusFlow') {
                    $tab = \Tab::getInstanceFromClassName($tabData['class_name']);

                    if (null === $tab->id) {
                        $tab = new \Tab();
                        $tab->class_name = $tabData['class_name'];
                        $tab->id_parent = $tabData['id_parent'];
                        $tab->module = $tabData['module'];
                        $tab->name = $tabData['name'];
                        $tab->wording = $tabData['wording'];
                        $tab->wording_domain = $tabData['wording_domain'];
                        $tab->add();

                        $this->logger->info(
                            sprintf('Created parent tab %s with ID: %d', $tabData['class_name'], $tab->id)
                        );
                    }

                    $parentTabId = $tab->id;
                    break;
                }
            }

            if (!$parentTabId) {
                throw new EvoStatusFlowException('Failed to get parent tab ID');
            }

            foreach ($tabs as $tabData) {
                if ($tabData['class_name'] !== 'AdminEvoStatusFlow') {
                    $tab = \Tab::getInstanceFromClassName($tabData['class_name']);

                    if (null === $tab->id) {
                        $tab = new \Tab();
                        $tab->class_name = $tabData['class_name'];
                        $tab->route_name = $tabData['route_name'];
                        $tab->id_parent = $parentTabId;
                        $tab->module = $tabData['module'];
                        $tab->name = $tabData['name'];
                        $tab->wording = $tabData['wording'];
                        $tab->wording_domain = $tabData['wording_domain'];
                        $tab->add();

                        $this->logger->info(
                            sprintf('Created tab %s with ID: %d', $tabData['class_name'], $tab->id)
                        );
                    }
                }
            }

            return true;
        } catch (EvoStatusFlowException $e) {
            $this->logger->error(
                sprintf('Error adding tabs: %s', $e->getMessage())
            );
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error adding tabs: %s', $e->getMessage())
            );
            throw new EvoStatusFlowException('Failed to add tabs: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Remove tabs for the module
     *
     * @return bool Success status
     *
     * @throws EvoStatusFlowException If tab removal fails
     */
    private function removeTabs(): bool
    {
        try {
            $tabs = $this->getTabDefinitions();

            foreach ($tabs as $tabData) {
                if ($tabData['class_name'] !== 'AdminEvoStatusFlow') {
                    $id_tab = (int) \Tab::getIdFromClassName($tabData['class_name']);

                    if ($id_tab) {
                        $tab = new \Tab($id_tab);
                        $tab->delete();

                        $this->logger->info(
                            sprintf('Removed tab %s', $tabData['class_name'])
                        );
                    }
                }
            }

            // Second pass: remove parent tab
            foreach ($tabs as $tabData) {
                if ($tabData['class_name'] === 'AdminEvoStatusFlow') {
                    $id_tab = (int) \Tab::getIdFromClassName($tabData['class_name']);

                    if ($id_tab) {
                        $tab = new \Tab($id_tab);
                        $tab->delete();

                        $this->logger->info(
                            sprintf('Removed parent tab %s', $tabData['class_name'])
                        );
                    }

                    break;
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Error removing tabs: %s', $e->getMessage())
            );
            throw new EvoStatusFlowException('Failed to remove tabs: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute SQL from a file
     *
     * @param string $filepath Path to the SQL file
     *
     * @return bool Success status
     *
     * @throws EvoStatusFlowException If SQL execution fails
     */
    private function executeSqlFromFile(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            $this->logger->error('SQL file not found', ['filepath' => $filepath]);
            throw new EvoStatusFlowException('SQL file not found: ' . $filepath);
        }

        $allowedCollations = ['utf8mb4_general_ci', 'utf8mb4_unicode_ci'];
        $databaseCollation = \Db::getInstance()->getValue('SELECT @@collation_database');

        $sqlLoader = new SqlLoader();
        $sqlLoader->setMetaData([
            'PREFIX_' => _DB_PREFIX_,
            'ENGINE_TYPE' => _MYSQL_ENGINE_,
            'COLLATION' => (empty($databaseCollation) || !in_array($databaseCollation, $allowedCollations))
                ? ''
                : 'COLLATE ' . $databaseCollation,
        ]);

        try {
            $queries = $sqlLoader->parseFile($filepath);

            if (!is_array($queries)) {
                $this->logger->error('Failed to parse SQL file', ['filepath' => $filepath]);

                // Fallback to manual parsing
                $sqlContent = file_get_contents($filepath);
                $sqlFallback = str_replace(
                    ['PREFIX_', 'ENGINE_TYPE'],
                    [_DB_PREFIX_, _MYSQL_ENGINE_],
                    $sqlContent
                );
                $manualQueries = preg_split('/;\s*[\r\n]+/', $sqlFallback);

                foreach ($manualQueries as $query) {
                    $query = trim($query);
                    if (!empty($query) && !\Db::getInstance()->execute($query)) {
                        $error = \Db::getInstance()->getMsgError();
                        $this->logger->error('SQL execution error', [
                            'error' => $error,
                            'query' => $query,
                        ]);
                        throw new EvoStatusFlowException('SQL execution error: ' . $error);
                    }
                }

                $this->logger->info('SQL executed successfully via fallback');

                return true;
            }

            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query) && !\Db::getInstance()->execute($query)) {
                    $error = \Db::getInstance()->getMsgError();
                    $this->logger->error('SQL execution error', [
                        'error' => $error,
                        'query' => $query,
                    ]);
                    throw new EvoStatusFlowException('SQL execution error: ' . $error);
                }
            }

            $this->logger->info('SQL executed successfully');

            return true;
        } catch (EvoStatusFlowException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('SQL execution exception', [
                'message' => $e->getMessage(),
                'filepath' => $filepath,
            ]);
            throw new EvoStatusFlowException('SQL execution failed: ' . $e->getMessage(), 0, $e);
        }
    }
}
