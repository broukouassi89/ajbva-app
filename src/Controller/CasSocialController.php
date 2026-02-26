<?php

namespace App\Controller;

use App\Entity\CasSocial;
use App\Form\CasSocialType;
use App\Repository\CasSocialRepository;
use App\Repository\MembreRepository;
use App\Service\FinanceService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cas-sociaux')]
#[IsGranted('ROLE_USER')]
class CasSocialController extends AbstractController
{
    #[Route('', name: 'cas_social_index', methods: ['GET'])]
    public function index(
        Request $request,
        CasSocialRepository $repo,
        PaginatorInterface $paginator,
    ): Response {
        $filters = [
            'type'   => $request->query->get('type', ''),
            'statut' => $request->query->get('statut', ''),
        ];

        // Sécurité : Un membre ne voit que ses propres cas sociaux
        if ($this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_VP_SOCIAL')) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user->getMembre()) {
                $filters['membre_id'] = $user->getMembre()->getId();
            }
        }

        $query = $repo->findWithFilters($filters);
        $casSociaux = $paginator->paginate($query, $request->query->getInt('page', 1), 15);

        return $this->render('cas_social/index.html.twig', [
            'casSociaux' => $casSociaux,
            'filters'    => $filters,
            'types'      => array_keys(CasSocial::TYPES),
            'statuts'    => array_keys(CasSocial::STATUTS),
        ]);
    }

    #[Route('/nouveau', name: 'cas_social_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_VP_SOCIAL')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        FinanceService $financeService,
    ): Response {
        $cas = new CasSocial();
        $form = $this->createForm(CasSocialType::class, $cas);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cas->setDeclaredBy($this->getUser());

            // Note spécifique pour le décès d'un membre
            if ($cas->getType() === 'Décès membre') {
                $noteBotro = "Note: La mise à disposition des fonds est conditionnée par l'inhumation du défunt à Botro.";
                $cas->setDescription(trim(($cas->getDescription() ?? '') . "\n\n" . $noteBotro));
            }

            // Calcul automatique du montant d'assistance
            try {
                $montant = $financeService->calculerMontantAssistance($cas);
                $cas->setMontantAssistance($montant);
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->render('cas_social/new.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $em->persist($cas);
            $em->flush();

            // Générer les cotisations sociales pour tous les membres actifs
            $financeService->genererCotisationsSociales($cas);

            $this->addFlash('success', '📋 Cas social déclaré et cotisations sociales générées.');
            return $this->redirectToRoute('cas_social_index');
        }

        return $this->render('cas_social/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'cas_social_show', methods: ['GET'])]
    public function show(CasSocial $cas): Response
    {
        return $this->render('cas_social/show.html.twig', ['cas' => $cas]);
    }

    #[Route('/{id}/assistance', name: 'cas_social_assistance', methods: ['POST'])]
    #[IsGranted('ROLE_BUREAU')]
    public function validerAssistance(
        Request $request,
        CasSocial $cas,
        EntityManagerInterface $em,
    ): Response {
        $action = $request->request->get('action');

        match ($action) {
            'valider' => (function() use ($cas) {
                $cas->setStatutAssistance('Validée');
                $cas->setValidatedBy($this->getUser());
            })(),
            'payer' => (function() use ($cas) {
                $cas->setStatutAssistance('Payée');
                $cas->setDatePaiementAssistance(new \DateTime());
            })(),
            'refuser' => $cas->setStatutAssistance('Refusée'),
            default => null,
        };

        $em->flush();
        $this->addFlash('success', "Statut de l'assistance mis à jour.");
        return $this->redirectToRoute('cas_social_show', ['id' => $cas->getId()]);
    }
}
