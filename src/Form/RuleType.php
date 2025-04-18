<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class RuleType extends AbstractType
{
    /**
     * Build the rule form with order state selections
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get all PrestaShop order states
        $orderStates = \OrderState::getOrderStates((int) \Context::getContext()->language->id);
        $orderStateChoices = [];

        foreach ($orderStates as $orderState) {
            $orderStateChoices[$orderState['name']] = (int) $orderState['id_order_state'];
        }

        $builder
            ->add('id_order_state_from', ChoiceType::class, [
                'label' => 'From order status',
                'choices' => $orderStateChoices,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a source order status',
                    ]),
                ],
                'translation_domain' => 'Modules.Evostatusflow.Admin',
            ])
            ->add('id_order_state_to', ChoiceType::class, [
                'label' => 'To order status',
                'choices' => $orderStateChoices,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Please select a destination order status',
                    ]),
                ],
                'translation_domain' => 'Modules.Evostatusflow.Admin',
            ])
            ->add('delay_hours', IntegerType::class, [
                'label' => 'Delay in hours',
                'required' => true,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Delay must be a positive number',
                    ]),
                ],
                'data' => 0,
                'translation_domain' => 'Modules.Evostatusflow.Admin',
            ])
            ->add('condition_sql', TextareaType::class, [
                'label' => 'SQL Condition (optional)',
                'required' => false,
                'help' => 'SQL WHERE clause to filter orders. You can use {id_order} as placeholder.',
                'translation_domain' => 'Modules.Evostatusflow.Admin',
            ])
            ->add('auto_execute', CheckboxType::class, [
                'label' => 'Auto-execute',
                'required' => false,
                'help' => 'If checked, this rule will be processed automatically by the cron job',
                'data' => true,
                'translation_domain' => 'Modules.Evostatusflow.Admin',
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'data' => true,
                'translation_domain' => 'Modules.Evostatusflow.Admin',
            ]);
    }

    /**
     * Configure options for this form type
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'Modules.Evostatusflow.Admin',
        ]);
    }
}
