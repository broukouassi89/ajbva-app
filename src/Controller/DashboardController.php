<?php

namespace App\Controller;

use App\Repository\CotisationRepository;
use App\Repository\MembreRepository;
use App\Service\FinanceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(
        FinanceService $financeService,
        MembreRepository $membreRepo,
        CotisationRepository $cotisationRepo,
    ): Response {
        // Sécurité : Un simple membre est redirigé vers sa propre fiche
        if ($this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_VP_SOCIAL')) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user->getMembre()) {
                return $this->redirectToRoute('membre_show', ['id' => $user->getMembre()->getId()]);
            }
        }

        $stats = $financeService->getDashboardStats();

        // Derniers membres inscrits
        $derniersMembres = $membreRepo->findBy([], ['createdAt' => 'DESC'], 6);

        // Dernières transactions
        $dernieresTransactions = $cotisationRepo->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('dashboard/index.html.twig', [
            'stats'                 => $stats,
            'derniersMembres'       => $derniersMembres,
            'dernieresTransactions' => $dernieresTransactions,
        ]);
    }

    // API endpoint pour graphiques AJAX
    #[Route('/api/stats/evolution/{annee}', name: 'api_stats_evolution')]
    public function apiEvolution(int $annee, CotisationRepository $cotisationRepo): Response
    {
        $data = $cotisationRepo->getEvolutionMensuelle($annee);
        return $this->json($data);
    }

    #[Route('/api/stats/membres', name: 'api_stats_membres')]
    public function apiMembres(MembreRepository $membreRepo): Response
    {
        return $this->json([
            'statuts'  => $membreRepo->countByStatut(),
            'villages' => $membreRepo->countByVillage(),
            'genres'   => $membreRepo->countByGenre(),
        ]);
    }
}
