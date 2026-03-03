<?php

namespace App\Controller;

use App\Entity\Cotisation;
use App\Form\CotisationType;
use App\Repository\CotisationRepository;
use App\Repository\MembreRepository;
use App\Service\FinanceService;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cotisations')]
#[IsGranted('ROLE_USER')]
class CotisationController extends AbstractController
{
    #[Route('', name: 'cotisation_index', methods: ['GET'])]
    public function index(
        Request $request,
        CotisationRepository $cotisationRepo,
        PaginatorInterface $paginator,
    ): Response {
        $filters = [
            'type'   => $request->query->get('type', ''),
            'statut' => $request->query->get('statut', ''),
            'annee'  => $request->query->get('annee', date('Y')),
        ];

        // Sécurité : Un membre ne voit que ses propres cotisations
        if ($this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_BUREAU')) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user->getMembre()) {
                $filters['membre_id'] = $user->getMembre()->getId();
            }
        }

        $query = $cotisationRepo->findWithFilters($filters);
        $cotisations = $paginator->paginate($query, $request->query->getInt('page', 1), 20);

        // Stats rapides
        $totalFiltree = 0;
        foreach ($cotisations as $c) $totalFiltree += (float) $c->getMontant();

        return $this->render('cotisation/index.html.twig', [
            'cotisations'  => $cotisations,
            'filters'      => $filters,
            'totalFiltree' => $totalFiltree,
        ]);
    }

    #[Route('/nouveau', name: 'cotisation_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BUREAU')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        MembreRepository $membreRepo,
        CotisationRepository $cotisationRepo,
    ): Response {
        $cotisation = new Cotisation();
        
        if ($membreId = $request->query->getInt('membre_id')) {
            $membre = $membreRepo->find($membreId);
            if ($membre) {
                $cotisation->setMembre($membre);
            }
        }

        $form = $this->createForm(CotisationType::class, $cotisation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cotisation->setRecuNumero($cotisationRepo->generateRecuNumero());
            $em->persist($cotisation);
            $em->flush();

            $this->addFlash('success', sprintf(
                '💰 Paiement de %s F CFA enregistré pour %s. Reçu : %s',
                number_format((float) $cotisation->getMontant(), 0, ',', ' '),
                $cotisation->getMembre()->getNomComplet(),
                $cotisation->getRecuNumero()
            ));

            return $this->redirectToRoute('membre_show', ['id' => $cotisation->getMembre()->getId()]);
        }

        return $this->render('cotisation/new.html.twig', [
            'form' => $form->createView(),
            'membrePreselectionne' => $cotisation->getMembre(),
        ]);
    }

    #[Route('/rapport', name: 'cotisation_rapport', methods: ['GET'])]
    #[IsGranted('ROLE_BUREAU')]
    public function rapport(
        Request $request,
        FinanceService $financeService,
    ): Response {
        $annee = $request->query->getInt('annee', (int) date('Y'));
        $rapport = $financeService->getRapportAnnuel($annee);

        $stats = [
            'complet'  => count(array_filter($rapport, fn($r) => $r['statut'] === 'Complet')),
            'partiel'  => count(array_filter($rapport, fn($r) => $r['statut'] === 'Partiel')),
            'nonPaye'  => count(array_filter($rapport, fn($r) => $r['statut'] === 'Non payé')),
            'total'    => array_sum(array_column($rapport, 'totalPaye')),
            'objectif' => count($rapport) * 18000,
        ];

        return $this->render('cotisation/rapport.html.twig', [
            'rapport' => $rapport,
            'annee'   => $annee,
            'stats'   => $stats,
        ]);
    }

    #[Route('/rapport/pdf', name: 'cotisation_rapport_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_BUREAU')]
    public function exportPdf(
        Request $request,
        FinanceService $financeService,
        PdfService $pdfService,
    ): Response {
        $annee = $request->query->getInt('annee', (int) date('Y'));
        $rapport = $financeService->getRapportAnnuel($annee);

        $html = $this->renderView('cotisation/rapport_pdf.html.twig', [
            'rapport' => $rapport,
            'annee'   => $annee,
        ]);

        return new Response(
            $pdfService->getOutput($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="Rapport_Cotisations_%d.pdf"', $annee),
            ]
        );
    }

    #[Route('/{id}', name: 'cotisation_show', methods: ['GET'])]
    public function show(Cotisation $cotisation): Response
    {
        return $this->render('cotisation/show.html.twig', ['cotisation' => $cotisation]);
    }
}
