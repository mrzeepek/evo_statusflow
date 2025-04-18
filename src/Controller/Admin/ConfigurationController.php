<?php

declare(strict_types=1);

namespace Evolutive\Module\EvoStatusFlow\Controller\Admin;

use Evolutive\Module\EvoStatusFlow\Form\ConfigurationFormHandler;
use Evolutive\Module\EvoStatusFlow\Runner\CommandRunner;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for managing the module configuration
 */
class ConfigurationController extends FrameworkBundleAdminController
{
    /**
     * @param ConfigurationFormHandler $configurationFormHandler Form handler for configuration
     * @param CommandRunner $commandRunner Service to run console commands
     * @param TranslatorInterface $translator Translator service
     */
    public function __construct(
        private readonly ConfigurationFormHandler $configurationFormHandler,
        private readonly CommandRunner $commandRunner,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Display and process the configuration form
     *
     * @AdminSecurity("is_granted('read', request.get('_legacy_controller'))")
     *
     * @param Request $request Request object
     *
     * @return Response
     */
    public function indexAction(Request $request): Response
    {
        $form = $this->configurationFormHandler->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $this->configurationFormHandler->save($form->getData());

            if (empty($errors)) {
                $this->addFlash(
                    'success',
                    $this->translator->trans('Settings saved successfully.', [], 'Admin.Notifications.Success')
                );

                return $this->redirectToRoute('evo_statusflow_configuration');
            }

            $this->flashErrors($errors);
        }

        return $this->render('@Modules/evo_statusflow/views/templates/admin/configuration.html.twig', [
            'configurationForm' => $form->createView(),
            'help_link' => $this->generateSidebarLink($request->attributes->get('_legacy_controller')),
            'enableSidebar' => true,
            'layoutTitle' => $this->translator->trans('Status Flow Configuration', [], 'Modules.Evostatusflow.Admin'),
        ]);
    }

    /**
     * Execute the status flow processing command
     *
     * @AdminSecurity("is_granted('update', request.get('_legacy_controller'))")
     *
     * @param Request $request Request object
     *
     * @return Response
     */
    public function runFlowCommandAction(Request $request): Response
    {
        $ruleId = $request->query->get('rule_id') ? (int) $request->query->get('rule_id') : null;
        $dryRun = (bool) $request->query->get('dry_run', false);

        try {
            $output = $this->commandRunner->run($ruleId, $dryRun);

            $message = $dryRun
                ? $this->translator->trans('Status flow command simulated successfully.', [], 'Modules.Evostatusflow.Admin')
                : $this->translator->trans('Status flow command executed successfully.', [], 'Modules.Evostatusflow.Admin');

            $this->addFlash('success', $message);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => $message,
                    'output' => $output,
                ]);
            }
        } catch (\Exception $e) {
            $errorMessage = $this->translator->trans(
                'Error executing status flow command: %error%',
                ['%error%' => $e->getMessage()],
                'Modules.Evostatusflow.Admin'
            );

            $this->addFlash('error', $errorMessage);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 400);
            }
        }

        return $this->redirectToRoute('evo_statusflow_configuration');
    }
}
