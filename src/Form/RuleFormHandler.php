<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Form;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RuleFormHandler
{
    /**
     * @param FormFactoryInterface $formFactory Factory for form creation
     * @param RuleFormDataProvider $formDataProvider Provider for form data
     * @param TranslatorInterface $translator Translation service
     */
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly RuleFormDataProvider $formDataProvider,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @var int|null
     */
    private ?int $ruleId;

    /**
     * Get the rule form
     *
     * @param int|null $ruleId
     *
     * @return FormInterface
     */
    public function getForm(?int $ruleId = null): FormInterface
    {
        $this->ruleId = $ruleId;
        $data = $this->formDataProvider->getData($ruleId);

        return $this->formFactory->createBuilder(RuleType::class, $data)->getForm();
    }

    /**
     * Save the rule form
     *
     * @param array $data
     *
     * @return array Array of errors, empty if no errors
     */
    public function save(array $data): array
    {
        $errors = [];

        try {
            $this->formDataProvider->saveData($data, $this->ruleId);
        } catch (\Exception $e) {
            $errors[] = $this->translator->trans(
                'Error saving rule: %message%',
                [
                    '%message%' => $e->getMessage(),
                ],
                'Modules.Evostatusflow.Admin'
            );
        }

        return $errors;
    }
}
