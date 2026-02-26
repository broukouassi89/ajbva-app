<?php

namespace App\Controller;

use App\Service\FinanceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/finances')]
#[IsGranted('ROLE_BUREAU')]
class FinanceController extends AbstractController
{
    public function __construct(
        private readonly FinanceService $financeService,
    ) {}

    #[Route('', name: 'finance_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('finance/index.html.twig', [
            'stats' => $this->financeService->getDashboardStats(),
            'derniersFlux' => $this->financeService->getFluxFinanciers(10),
        ]);
    }

    #[Route('/flux', name: 'finance_flux', methods: ['GET'])]
    public function flux(Request $request): Response
    {
        return $this->render('finance/flux.html.twig', [
            'flux' => $this->financeService->getFluxFinanciers(),
        ]);
    }

    #[Route('/rapports', name: 'finance_rapports', methods: ['GET'])]
    public function rapports(Request $request): Response
    {
        $annee = $request->query->getInt('annee', (int) date('Y'));
        
        return $this->render('finance/rapports.html.twig', [
            'annee' => $annee,
            'rapportAnnuel' => $this->financeService->getRapportAnnuel($annee),
            'stats' => $this->financeService->getDashboardStats(),
        ]);
    }
}
