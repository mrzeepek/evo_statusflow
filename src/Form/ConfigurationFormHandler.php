<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Form;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigurationFormHandler
{
    private FormFactoryInterface $formFactory;
    private ConfigurationFormDataProvider $formDataProvider;
    private TranslatorInterface $translator;

    public function __construct(
        FormFactoryInterface $formFactory,
        ConfigurationFormDataProvider $formDataProvider,
        TranslatorInterface $translator,
    ) {
        $this->formFactory = $formFactory;
        $this->formDataProvider = $formDataProvider;
        $this->translator = $translator;
    }

    /**
     * Get the configuration form
     *
     * @return FormInterface
     */
    public function getForm(): FormInterface
    {
        $data = $this->formDataProvider->getData();

        $formBuilder = $this->formFactory->createBuilder(ConfigurationType::class, $data);

        return $formBuilder->getForm();
    }

    /**
     * Save the configuration form
     *
     * @param array $data
     *
     * @return array Array of errors, empty if no errors
     */
    public function save(array $data): array
    {
        $errors = [];

        try {
            $errors = $this->formDataProvider->setData($data);
        } catch (\Exception $e) {
            $errors[] = $this->translator->trans(
                'Error saving configuration: %message%',
                [
                    '%message%' => $e->getMessage(),
                ],
                'Modules.Evostatusflow.Admin'
            );
        }

        return $errors;
    }
}
