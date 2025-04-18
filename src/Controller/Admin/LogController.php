<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Controller\Admin;

use Evolutive\Module\EvoStatusFlow\CQRS\Command\Log\CleanLogsCommand;
use Evolutive\Module\EvoStatusFlow\CQRS\Query\Log\GetLogQuery;
use Evolutive\Module\EvoStatusFlow\Service\LoggingService;
use PrestaShop\PrestaShop\Core\Grid\GridFactory;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteria;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for managing logs
 */
class LogController extends FrameworkBundleAdminController
{
    /**
     * @param GridFactory $logGridFactory Grid factory for logs
     * @param LoggingService $loggingService Logging service
     */
    public function __construct(
        private readonly GridFactory $logGridFactory,
        private readonly LoggingService $loggingService,
    ) {
    }

    /**
     * Display the list of logs
     *
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $searchCriteria = new SearchCriteria(
            [],
            [],
            '',
            0,
            50
        );

        $logGrid = $this->logGridFactory->getGrid($searchCriteria);

        return $this->render('@Modules/evo_statusflow/views/templates/admin/log/index.html.twig', [
            'logGrid' => $this->presentGrid($logGrid),
            'enableSidebar' => true,
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
            'layoutTitle' => $this->trans('Status Flow Logs', 'Modules.Evostatusflow.Admin'),
            'isDatabaseLoggingEnabled' => $this->loggingService->isDatabaseLoggingEnabled(),
            'logRetentionDays' => $this->loggingService->getLogRetentionDays(),
        ]);
    }

    /**
     * View log details
     *
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     *
     * @param Request $request
     * @param int $logId
     *
     * @return Response
     */
    public function viewAction(Request $request, int $logId): Response
    {
        // Use CQRS query to retrieve log details
        $query = new GetLogQuery($logId);
        $log = $this->get('prestashop.core.query_bus')->handle($query);

        if (!$log) {
            $this->addFlash(
                'error',
                $this->trans('Log entry not found', 'Modules.Evostatusflow.Admin')
            );

            return $this->redirectToRoute('evo_statusflow_log_index');
        }

        $logData = [
            'id_log' => $log->id,
            'log_type' => $log->logType,
            'log_message' => $log->logMessage,
            'object_type' => $log->objectType,
            'object_id' => $log->objectId,
            'id_rule' => $log->ruleId,
            'date_add' => $log->dateAdd,
            'additional_data' => json_encode($log->additionalData, JSON_THROW_ON_ERROR),
        ];

        return $this->render('@Modules/evo_statusflow/views/templates/admin/log/view.html.twig', [
            'log' => $logData,
            'additionalData' => $log->additionalData,
            'enableSidebar' => true,
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
            'layoutTitle' => $this->trans('Log Details', 'Modules.Evostatusflow.Admin'),
        ]);
    }

    /**
     * Clean old logs
     *
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))")
     *
     * @return RedirectResponse
     */
    public function cleanAction(): RedirectResponse
    {
        try {
            if (!$this->loggingService->isDatabaseLoggingEnabled()) {
                $this->addFlash(
                    'warning',
                    $this->trans('Database logging is currently disabled. No logs to clean up.', 'Modules.Evostatusflow.Admin')
                );

                return $this->redirectToRoute('evo_statusflow_log_index');
            }

            $retentionDays = $this->loggingService->getLogRetentionDays();
            $command = new CleanLogsCommand($retentionDays);
            $deletedLogs = $this->get('prestashop.core.command_bus')->handle($command);

            $this->addFlash(
                'success',
                $this->trans(
                    'Successfully deleted %count% old log entries',
                    'Modules.Evostatusflow.Admin',
                    ['%count%' => $deletedLogs]
                )
            );
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog(
                'Error cleaning logs: ' . $e->getMessage(),
                3,
                null,
                'EvoStatusFlow',
                0,
                true
            );

            $this->addFlash(
                'error',
                $this->trans(
                    'Error when cleaning logs: %error%',
                    'Modules.Evostatusflow.Admin',
                    ['%error%' => $e->getMessage()]
                )
            );
        }

        return $this->redirectToRoute('evo_statusflow_log_index');
    }

    /**
     * Delete a specific log
     *
     * @AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")
     *
     * @param int $logId Log ID to delete
     *
     * @return RedirectResponse
     */
    public function deleteAction(int $logId): RedirectResponse
    {
        try {
            // Log l'opération pour le débogage
            $this->get('logger')->info('Delete action called for log ID: ' . $logId);

            // Obtenir le log pour vérifier qu'il existe
            $log = $this->loggingService->getLogById($logId);

            if (!$log) {
                $this->addFlash(
                    'error',
                    $this->trans('Log entry not found', 'Modules.Evostatusflow.Admin')
                );

                return $this->redirectToRoute('evo_statusflow_log_index');
            }

            // Suppression du log
            $result = $this->loggingService->deleteLog($logId);

            if ($result) {
                $this->addFlash(
                    'success',
                    $this->trans('Log deleted successfully.', 'Admin.Notifications.Success')
                );
            } else {
                $this->addFlash(
                    'error',
                    $this->trans('Could not delete log.', 'Admin.Notifications.Error')
                );
            }
        } catch (\Exception $e) {
            $this->get('logger')->error('Error deleting log: ' . $e->getMessage());

            $this->addFlash(
                'error',
                $this->trans('Cannot delete log: %error%', 'Modules.Evostatusflow.Admin', ['%error%' => $e->getMessage()])
            );
        }

        return $this->redirectToRoute('evo_statusflow_log_index');
    }

    /**
     * Delete all logs
     *
     * @AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")
     *
     * @return RedirectResponse
     */
    public function deleteAllAction(): RedirectResponse
    {
        try {
            if (!$this->loggingService->isDatabaseLoggingEnabled()) {
                $this->addFlash(
                    'warning',
                    $this->trans('Database logging is currently disabled.', 'Modules.Evostatusflow.Admin')
                );

                return $this->redirectToRoute('evo_statusflow_log_index');
            }

            $count = $this->loggingService->deleteAllLogs();

            $this->addFlash(
                'success',
                $this->trans(
                    'Successfully deleted %count% log entries',
                    'Modules.Evostatusflow.Admin',
                    ['%count%' => $count]
                )
            );
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog(
                'Error deleting all logs: ' . $e->getMessage(),
                3,
                null,
                'EvoStatusFlow',
                0,
                true
            );

            $this->addFlash(
                'error',
                $this->trans(
                    'Error when deleting logs: %error%',
                    'Modules.Evostatusflow.Admin',
                    ['%error%' => $e->getMessage()]
                )
            );
        }

        return $this->redirectToRoute('evo_statusflow_log_index');
    }

    /**
     * Bulk delete logs
     *
     * @AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function bulkDeleteAction(Request $request): RedirectResponse
    {
        $logIds = $request->request->get('log_bulk');

        if (!$logIds || !is_array($logIds)) {
            $this->addFlash(
                'error',
                $this->trans('No logs selected for deletion', 'Modules.Evostatusflow.Admin')
            );

            return $this->redirectToRoute('evo_statusflow_log_index');
        }

        try {
            $count = 0;
            foreach ($logIds as $logId) {
                $result = $this->loggingService->deleteLog((int) $logId);
                if ($result) {
                    ++$count;
                }
            }

            $this->addFlash(
                'success',
                $this->trans(
                    'Successfully deleted %count% selected log entries',
                    'Modules.Evostatusflow.Admin',
                    ['%count%' => $count]
                )
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                $this->trans(
                    'Error when deleting selected logs: %error%',
                    'Modules.Evostatusflow.Admin',
                    ['%error%' => $e->getMessage()]
                )
            );
        }

        return $this->redirectToRoute('evo_statusflow_log_index');
    }
}
