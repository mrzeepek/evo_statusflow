<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Controller\Admin;

use Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\CreateRuleCommand;
use Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\DeleteRuleCommand;
use Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\ToggleRuleActiveCommand;
use Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\ToggleRuleAutoExecuteCommand;
use Evolutive\Module\EvoStatusFlow\CQRS\Command\Rule\UpdateRuleCommand;
use Evolutive\Module\EvoStatusFlow\Form\RuleFormHandler;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Grid\GridFactory;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteria;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for managing flow rules (refactorisé pour CQRS)
 */
class RuleController extends FrameworkBundleAdminController
{
    /**
     * @param GridFactory $ruleGridFactory Factory for rule grid
     * @param RuleFormHandler $ruleFormHandler Form handler for rules
     * @param CommandBusInterface $commandBus Command bus responsible for executing CQRS commands
     */
    public function __construct(
        private readonly GridFactory $ruleGridFactory,
        private readonly RuleFormHandler $ruleFormHandler,
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    /**
     * Display the list of rules
     *
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     *
     * @param Request $request Current request
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

        $ruleGrid = $this->ruleGridFactory->getGrid($searchCriteria);

        return $this->render('@Modules/evo_statusflow/views/templates/admin/rule/index.html.twig', [
            'ruleGrid' => $this->presentGrid($ruleGrid),
            'enableSidebar' => true,
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
            'layoutTitle' => $this->trans('Status Flow Rules', 'Modules.Evostatusflow.Admin'),
        ]);
    }

    /**
     * Create a new rule
     *
     * @AdminSecurity("is_granted('create', request.get('_legacy_controller'))")
     *
     * @param Request $request Current request
     *
     * @return Response
     */
    public function createAction(Request $request): Response
    {
        $form = $this->ruleFormHandler->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $command = new CreateRuleCommand(
                idOrderStateFrom: (int) $formData['id_order_state_from'],
                idOrderStateTo: (int) $formData['id_order_state_to'],
                delayHours: (int) $formData['delay_hours'],
                conditionSql: $formData['condition_sql'],
                autoExecute: (bool) $formData['auto_execute'],
                active: (bool) $formData['active']
            );

            try {
                $this->commandBus->handle($command);

                $this->addFlash(
                    'success',
                    $this->trans('Rule created successfully.', 'Admin.Notifications.Success')
                );

                return $this->redirectToRoute('evo_statusflow_rule_index');
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $this->trans('Error: %error%', 'Admin.Notifications.Error', ['%error%' => $e->getMessage()])
                );
            }
        }

        return $this->render('@Modules/evo_statusflow/views/templates/admin/rule/form.html.twig', [
            'ruleForm' => $form->createView(),
            'enableSidebar' => true,
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
            'layoutTitle' => $this->trans('Add New Rule', 'Modules.Evostatusflow.Admin'),
        ]);
    }

    /**
     * Edit an existing rule
     *
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))")
     *
     * @param Request $request Current request
     * @param int $ruleId ID of the rule to edit
     *
     * @return Response
     */
    public function editAction(Request $request, int $ruleId): Response
    {
        $form = $this->ruleFormHandler->getForm($ruleId);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $command = new UpdateRuleCommand(
                ruleId: $ruleId,
                idOrderStateFrom: (int) $formData['id_order_state_from'],
                idOrderStateTo: (int) $formData['id_order_state_to'],
                delayHours: (int) $formData['delay_hours'],
                conditionSql: $formData['condition_sql'],
                autoExecute: (bool) $formData['auto_execute'],
                active: (bool) $formData['active']
            );

            try {
                $this->commandBus->handle($command);

                $this->addFlash(
                    'success',
                    $this->trans('Rule updated successfully.', 'Admin.Notifications.Success')
                );

                return $this->redirectToRoute('evo_statusflow_rule_index');
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $this->trans('Error: %error%', 'Admin.Notifications.Error', ['%error%' => $e->getMessage()])
                );
            }
        }

        return $this->render('@Modules/evo_statusflow/views/templates/admin/rule/form.html.twig', [
            'ruleForm' => $form->createView(),
            'enableSidebar' => true,
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
            'layoutTitle' => $this->trans('Edit Rule', 'Modules.Evostatusflow.Admin'),
        ]);
    }

    /**
     * Delete a rule
     *
     * @AdminSecurity("is_granted('delete', request.get('_legacy_controller'))")
     *
     * @param int $ruleId ID of the rule to delete
     *
     * @return Response
     */
    public function deleteAction(int $ruleId): Response
    {
        $this->get('logger')->info('Delete action called for rule ID: ' . $ruleId);

        try {
            $command = new DeleteRuleCommand($ruleId);
            $result = $this->commandBus->handle($command);

            $this->get('logger')->info('Delete rule result: ' . ($result ? 'success' : 'failure'));

            if ($result) {
                $this->addFlash(
                    'success',
                    $this->trans('Rule deleted successfully.', 'Admin.Notifications.Success')
                );
            } else {
                $this->addFlash(
                    'error',
                    $this->trans('Could not delete rule.', 'Admin.Notifications.Error')
                );
            }
        } catch (\Exception $e) {
            $this->get('logger')->error('Error deleting rule: ' . $e->getMessage());

            $this->addFlash(
                'error',
                $this->trans('Cannot delete rule: %error%', 'Modules.Evostatusflow.Admin', ['%error%' => $e->getMessage()])
            );
        }

        return $this->redirectToRoute('evo_statusflow_rule_index');
    }

    /**
     * Toggle active status for a rule
     *
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))")
     *
     * @param int $ruleId ID of the rule to toggle
     *
     * @return Response
     */
    public function toggleActiveAction(int $ruleId): Response
    {
        try {
            $command = new ToggleRuleActiveCommand($ruleId);
            $success = $this->commandBus->handle($command);

            if ($success) {
                $this->addFlash(
                    'success',
                    $this->trans('Rule status updated successfully.', 'Admin.Notifications.Success')
                );
            } else {
                $this->addFlash(
                    'error',
                    $this->trans('Could not update rule status.', 'Admin.Notifications.Error')
                );
            }
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                $this->trans('Error occurred: %error%', 'Modules.Evostatusflow.Admin', ['%error%' => $e->getMessage()])
            );
        }

        return $this->redirectToRoute('evo_statusflow_rule_index');
    }

    /**
     * Toggle auto-execute status for a rule
     *
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))")
     *
     * @param int $ruleId ID of the rule to toggle
     *
     * @return Response
     */
    public function toggleAutoExecuteAction(int $ruleId): Response
    {
        try {
            $command = new ToggleRuleAutoExecuteCommand($ruleId);

            $success = $this->commandBus->handle($command);

            if ($success) {
                $this->addFlash(
                    'success',
                    $this->trans('Rule auto-execute status updated successfully.', 'Admin.Notifications.Success')
                );
            } else {
                $this->addFlash(
                    'error',
                    $this->trans('Could not update rule auto-execute status.', 'Admin.Notifications.Error')
                );
            }
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                $this->trans('Error occurred: %error%', 'Modules.Evostatusflow.Admin', ['%error%' => $e->getMessage()])
            );
        }

        return $this->redirectToRoute('evo_statusflow_rule_index');
    }
}
