<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Grid\Definition\Factory;

use PrestaShop\PrestaShop\Core\Grid\Action\GridActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Type\LinkGridAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Type\SimpleGridAction;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\BulkActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ToggleColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use PrestaShop\PrestaShop\Core\Hook\HookDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RuleGridDefinitionFactory extends AbstractGridDefinitionFactory
{
    /**
     * @param HookDispatcherInterface $hookDispatcher Service de dispatching des hooks
     * @param TranslatorInterface $translator Service de traduction
     */
    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        protected $translator,
    ) {
        parent::__construct($hookDispatcher);
    }

    /**
     * Gets the unique grid identifier
     *
     * @return string
     */
    protected function getId(): string
    {
        return 'rule';
    }

    /**
     * Gets the grid name
     *
     * @return string
     */
    protected function getName(): string
    {
        return $this->translator->trans('Status Flow Rules', [], 'Modules.Evostatusflow.Admin');
    }

    /**
     * Configure grid columns
     *
     * @return ColumnCollection
     */
    protected function getColumns(): ColumnCollection
    {
        return (new ColumnCollection())
            ->add(
                (new BulkActionColumn('bulk'))
                    ->setOptions([
                        'bulk_field' => 'id_rule',
                    ])
            )
            ->add(
                (new DataColumn('id_rule'))
                    ->setName($this->translator->trans('ID', [], 'Admin.Global'))
                    ->setOptions([
                        'field' => 'id_rule',
                    ])
            )
            ->add(
                (new DataColumn('from_order_state_name'))
                    ->setName($this->translator->trans('From Status', [], 'Modules.Evostatusflow.Admin'))
                    ->setOptions([
                        'field' => 'from_order_state_name',
                    ])
            )
            ->add(
                (new DataColumn('to_order_state_name'))
                    ->setName($this->translator->trans('To Status', [], 'Modules.Evostatusflow.Admin'))
                    ->setOptions([
                        'field' => 'to_order_state_name',
                    ])
            )
            ->add(
                (new DataColumn('delay_hours'))
                    ->setName($this->translator->trans('Delay (hours)', [], 'Modules.Evostatusflow.Admin'))
                    ->setOptions([
                        'field' => 'delay_hours',
                    ])
            )
            ->add(
                (new ToggleColumn('auto_execute'))
                    ->setName($this->translator->trans('Auto-execute', [], 'Modules.Evostatusflow.Admin'))
                    ->setOptions([
                        'field' => 'auto_execute',
                        'primary_field' => 'id_rule',
                        'route' => 'evo_statusflow_rule_toggle_auto_execute',
                        'route_param_name' => 'ruleId',
                    ])
            )
            ->add(
                (new ToggleColumn('active'))
                    ->setName($this->translator->trans('Active', [], 'Admin.Global'))
                    ->setOptions([
                        'field' => 'active',
                        'primary_field' => 'id_rule',
                        'route' => 'evo_statusflow_rule_toggle_active',
                        'route_param_name' => 'ruleId',
                    ])
            )
            ->add(
                (new ActionColumn('actions'))
                    ->setName($this->translator->trans('Actions', [], 'Admin.Global'))
                    ->setOptions([
                        'actions' => $this->getRowActions(),
                    ])
            );
    }

    /**
     * Configure row actions
     *
     * @return RowActionCollection
     */
    protected function getRowActions(): RowActionCollection
    {
        return (new RowActionCollection())
            ->add(
                (new LinkRowAction('edit'))
                    ->setName($this->translator->trans('Edit', [], 'Admin.Actions'))
                    ->setIcon('edit')
                    ->setOptions([
                        'route' => 'evo_statusflow_rule_edit',
                        'route_param_name' => 'ruleId',
                        'route_param_field' => 'id_rule',
                    ])
            )
            ->add(
                (new LinkRowAction('delete'))
                    ->setName($this->translator->trans('Delete', [], 'Admin.Actions'))
                    ->setIcon('delete')
                    ->setOptions([
                        'route' => 'evo_statusflow_rule_delete',
                        'route_param_name' => 'ruleId',
                        'route_param_field' => 'id_rule',
                        'confirm_message' => $this->translator->trans(
                            'Delete selected item?',
                            [],
                            'Admin.Notifications.Warning'
                        ),
                    ])
            )
            ->add(
                (new LinkRowAction('run'))
                    ->setName($this->translator->trans('Run Now', [], 'Modules.Evostatusflow.Admin'))
                    ->setIcon('play_arrow')
                    ->setOptions([
                        'route' => 'evo_statusflow_run_command',
                        'route_param_name' => 'rule_id',
                        'route_param_field' => 'id_rule',
                    ])
            );
    }

    /**
     * CConfigure grid actions
     *
     * @return GridActionCollection
     */
    protected function getGridActions(): GridActionCollection
    {
        return (new GridActionCollection())
            ->add(
                (new SimpleGridAction('common_refresh_list'))
                    ->setName($this->translator->trans('Refresh list', [], 'Admin.Advparameters.Feature'))
                    ->setIcon('refresh')
            )
            ->add(
                (new LinkGridAction('run_all'))
                    ->setName($this->translator->trans('Run All Rules', [], 'Modules.Evostatusflow.Admin'))
                    ->setIcon('play_arrow')
                    ->setOptions([
                        'route' => 'evo_statusflow_run_command',
                        'route_params' => [],
                    ])
            );
    }

    /**
     * Configure filters
     *
     * @return FilterCollection
     */
    protected function getFilters(): FilterCollection
    {
        return new FilterCollection();
    }
}
