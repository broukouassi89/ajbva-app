<?php

namespace App\Controller;

use App\Service\FinanceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/finance/stats')]
#[IsGranted('ROLE_BUREAU')]
class FinanceStatsController extends AbstractController
{
    #[Route('', name: 'finance_stats_index', methods: ['GET'])]
    public function index(Request $request, FinanceService $financeService): Response
    {
        $annee = $request->query->getInt('annee', (int) date('Y'));
        
        // Données pour les graphiques
        $statsMensuelles = $financeService->getStatsFinancieresMensuelles($annee);
        $evolutionFondsSocial = $financeService->getEvolutionFondsSocial($annee);
        
        return $this->render('finance_stats/index.html.twig', [
            'annee' => $annee,
            'statsMensuelles' => $statsMensuelles,
            'evolutionFondsSocial' => $evolutionFondsSocial,
        ]);
    }
}
