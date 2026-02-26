<?php

namespace App\Controller;

use App\Service\SettingService;
use App\Service\AuditService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/parametres')]
#[IsGranted('ROLE_ADMIN')]
class SettingController extends AbstractController
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly AuditService $auditService
    ) {}

    #[Route('', name: 'admin_setting_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        // Initialiser les défauts si vide
        if (empty($this->settingService->all())) {
            $this->settingService->initializeDefaults();
        }

        if ($request->isMethod('POST')) {
            $params = $request->request->all('settings');
            foreach ($params as $cle => $valeur) {
                $this->settingService->update($cle, $valeur);
            }

            $this->auditService->log('Mise à jour des paramètres financiers');
            $this->addFlash('success', 'Les paramètres ont été mis à jour avec succès.');
            
            return $this->redirectToRoute('admin_setting_index');
        }

        return $this->render('admin_setting/index.html.twig', [
            'settings' => $this->settingService->all(),
        ]);
    }
}
