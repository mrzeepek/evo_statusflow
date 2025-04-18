<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class ConfigurationType extends AbstractType
{
    /**
     * Build the configuration form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cron_frequency', ChoiceType::class, [
                'label' => 'Cron Frequency',
                'choices' => [
                    'Every hour' => 'hourly',
                    'Every day' => 'daily',
                    'Every week' => 'weekly',
                ],
                'required' => true,
                'help' => 'How often should the status flow processor run via cron?',
                'translation_domain' => 'Modules.Evostatusflow.Admin',
            ])
            ->add('batch_size', IntegerType::class, [
                'label' => 'Batch Size',
                'required' => true,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Batch size must be at least 1',
                    ]),
                ],
                'data' => 50,
                'help' => 'Maximum number of orders to process in a single run',
                'translation_domain' => 'Modules.Evostatusflow.Admin',
            ]);

        // Logging section
        $builder
            ->add('enable_logging', CheckboxType::class, [
                'label' => 'Enable Detailed Logging',
                'required' => false,
                'help' => 'Log detailed information about status changes to Monolog',
                'translation_domain' => 'Modules.Evostatusflow.Admin',
            ])
            ->add('enable_db_logging', CheckboxType::class, [
                'label' => 'Enable Database Logging',
                'required' => false,
                'help' => 'Store logs in database for viewing in the back office',
                'translation_domain' => 'Modules.Evostatusflow.Admin',
            ])
            ->add('log_retention_days', IntegerType::class, [
                'label' => 'Log Retention Period (days)',
                'required' => true,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 1,
                        'message' => 'Retention period must be at least 1 day',
                    ]),
                ],
                'data' => 30,
                'help' => 'How many days to keep logs before automatic cleanup',
                'translation_domain' => 'Modules.Evostatusflow.Admin',
            ]);

        // Notification section
        $builder
            ->add('notification_email', TextType::class, [
                'label' => 'Notification Email',
                'required' => false,
                'help' => 'Email address to notify on important status changes (leave empty to disable)',
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
