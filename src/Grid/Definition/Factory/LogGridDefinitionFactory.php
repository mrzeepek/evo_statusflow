<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Grid\Definition\Factory;

use PrestaShop\PrestaShop\Core\Grid\Action\Bulk\BulkActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Bulk\Type\SubmitBulkAction;
use PrestaShop\PrestaShop\Core\Grid\Action\GridActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Type\LinkGridAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Type\SimpleGridAction;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\BulkActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use PrestaShop\PrestaShop\Core\Hook\HookDispatcherInterface;
use PrestaShopBundle\Form\Admin\Type\DateRangeType;
use PrestaShopBundle\Form\Admin\Type\SearchAndResetType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Translation\TranslatorInterface;

class LogGridDefinitionFactory extends AbstractGridDefinitionFactory
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param HookDispatcherInterface $hookDispatcher Hook dispatcher
     * @param TranslatorInterface $translator Translator
     */
    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        TranslatorInterface $translator,
    ) {
        parent::__construct($hookDispatcher);
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function getId(): string
    {
        return 'evo_statusflow_log';
    }

    /**
     * {@inheritdoc}
     */
    protected function getName(): string
    {
        return $this->translator->trans('Status Flow Logs', [], 'Modules.Evostatusflow.Admin');
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumns(): ColumnCollection
    {
        return (new ColumnCollection())
            ->add(
                (new BulkActionColumn('bulk'))
                    ->setOptions([
                        'bulk_field' => 'id_log',
                    ])
            )
            ->add(
                (new DataColumn('id_log'))
                    ->setName($this->translator->trans('ID', [], 'Admin.Global'))
                    ->setOptions([
                        'field' => 'id_log',
                    ])
            )
            ->add(
                (new DataColumn('date_add'))
                    ->setName($this->translator->trans('Date', [], 'Admin.Global'))
                    ->setOptions([
                        'field' => 'date_add',
                    ])
            )
            ->add(
                (new DataColumn('log_type'))
                    ->setName($this->translator->trans('Level', [], 'Modules.Evostatusflow.Admin'))
                    ->setOptions([
                        'field' => 'log_type',
                    ])
            )
            ->add(
                (new DataColumn('object_type'))
                    ->setName($this->translator->trans('Object Type', [], 'Modules.Evostatusflow.Admin'))
                    ->setOptions([
                        'field' => 'object_type',
                    ])
            )
            ->add(
                (new DataColumn('object_id'))
                    ->setName($this->translator->trans('Object ID', [], 'Modules.Evostatusflow.Admin'))
                    ->setOptions([
                        'field' => 'object_id',
                    ])
            )
            ->add(
                (new DataColumn('log_message'))
                    ->setName($this->translator->trans('Message', [], 'Modules.Evostatusflow.Admin'))
                    ->setOptions([
                        'field' => 'log_message',
                    ])
            )
            ->add(
                (new DataColumn('id_rule'))
                    ->setName($this->translator->trans('Rule ID', [], 'Modules.Evostatusflow.Admin'))
                    ->setOptions([
                        'field' => 'id_rule',
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
     * {@inheritdoc}
     */
    protected function getFilters(): FilterCollection
    {
        return (new FilterCollection())
            ->add(
                (new Filter('id_log', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => $this->translator->trans('ID', [], 'Admin.Global'),
                        ],
                    ])
                    ->setAssociatedColumn('id_log')
            )
            ->add(
                (new Filter('date_add', DateRangeType::class))
                    ->setTypeOptions([
                        'required' => false,
                    ])
                    ->setAssociatedColumn('date_add')
            )
            ->add(
                (new Filter('log_type', ChoiceType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'choices' => [
                            $this->translator->trans('Info', [], 'Modules.Evostatusflow.Admin') => 'info',
                            $this->translator->trans('Warning', [], 'Modules.Evostatusflow.Admin') => 'warning',
                            $this->translator->trans('Error', [], 'Modules.Evostatusflow.Admin') => 'error',
                        ],
                    ])
                    ->setAssociatedColumn('log_type')
            )
            ->add(
                (new Filter('object_type', ChoiceType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'choices' => [
                            $this->translator->trans('Order', [], 'Admin.Global') => 'order',
                            $this->translator->trans('Rule', [], 'Modules.Evostatusflow.Admin') => 'rule',
                            $this->translator->trans('System', [], 'Admin.Global') => 'system',
                        ],
                    ])
                    ->setAssociatedColumn('object_type')
            )
            ->add(
                (new Filter('object_id', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => $this->translator->trans('Object ID', [], 'Modules.Evostatusflow.Admin'),
                        ],
                    ])
                    ->setAssociatedColumn('object_id')
            )
            ->add(
                (new Filter('log_message', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => $this->translator->trans('Message', [], 'Modules.Evostatusflow.Admin'),
                        ],
                    ])
                    ->setAssociatedColumn('log_message')
            )
            ->add(
                (new Filter('id_rule', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => $this->translator->trans('Rule ID', [], 'Modules.Evostatusflow.Admin'),
                        ],
                    ])
                    ->setAssociatedColumn('id_rule')
            )
            ->add(
                (new Filter('actions', SearchAndResetType::class))
                    ->setTypeOptions([
                        'reset_route' => 'evo_statusflow_log_index',
                        'reset_route_params' => [],
                        'redirect_route' => 'evo_statusflow_log_index',
                    ])
                    ->setAssociatedColumn('actions')
            );
    }

    /**
     * {@inheritdoc}
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
                (new LinkGridAction('delete_all'))
                    ->setName($this->translator->trans('Delete all logs', [], 'Modules.Evostatusflow.Admin'))
                    ->setIcon('delete')
                    ->setOptions([
                        'route' => 'evo_statusflow_log_delete_all',
                        'route_params' => [],
                    ])
            );
    }

    /**
     * Get row actions for the grid
     *
     * @return RowActionCollection
     */
    protected function getRowActions(): RowActionCollection
    {
        return (new RowActionCollection())
            ->add(
                (new LinkRowAction('view'))
                    ->setName($this->translator->trans('View', [], 'Admin.Actions'))
                    ->setIcon('zoom_in')
                    ->setOptions([
                        'route' => 'evo_statusflow_log_view',
                        'route_param_name' => 'logId',
                        'route_param_field' => 'id_log',
                    ])
            )
            ->add(
                (new LinkRowAction('delete'))
                    ->setName($this->translator->trans('Delete', [], 'Admin.Actions'))
                    ->setIcon('delete')
                    ->setOptions([
                        'route' => 'evo_statusflow_log_delete',
                        'route_param_name' => 'logId',
                        'route_param_field' => 'id_log',
                        'confirm_message' => $this->translator->trans(
                            'Delete selected log?',
                            [],
                            'Admin.Notifications.Warning'
                        ),
                    ])
            );
    }

    protected function getBulkActions(): BulkActionCollection
    {
        return (new BulkActionCollection())
            ->add(
                (new SubmitBulkAction('delete_selection'))
                    ->setName($this->translator->trans('Delete selected', [], 'Admin.Actions'))
                    ->setOptions([
                        'submit_route' => 'evo_statusflow_log_bulk_delete',
                        'confirm_message' => $this->translator->trans(
                            'Delete selected items?',
                            [],
                            'Admin.Notifications.Warning'
                        ),
                    ])
            );
    }
}
