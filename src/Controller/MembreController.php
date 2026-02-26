<?php

namespace App\Controller;

use App\Entity\Membre;
use App\Form\MembreType;
use App\Repository\CotisationRepository;
use App\Repository\MembreRepository;
use App\Service\ExcelService;
use App\Service\FinanceService;
use App\Service\MembreService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/membres')]
#[IsGranted('ROLE_USER')]
class MembreController extends AbstractController
{
    #[Route('', name: 'membre_index', methods: ['GET'])]
    public function index(
        Request $request,
        MembreRepository $membreRepo,
        PaginatorInterface $paginator,
    ): Response {
        // Sécurité : Un simple membre est redirigé vers sa propre fiche
        if ($this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_VP_SOCIAL')) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if ($user->getMembre()) {
                return $this->redirectToRoute('membre_show', ['id' => $user->getMembre()->getId()]);
            }
        }

        $filters = [
            'search'  => $request->query->get('search', ''),
            'statut'  => $request->query->get('statut', ''),
            'village' => $request->query->get('village', ''),
            'famille' => $request->query->get('famille', ''),
            'genre'   => $request->query->get('genre', ''),
        ];

        $query = $membreRepo->findWithFilters($filters);
        $stats = $membreRepo->countByStatut();

        $membres = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('membre/index.html.twig', [
            'membres'  => $membres,
            'stats'    => $stats,
            'filters'  => $filters,
            'villages' => Membre::VILLAGES,
            'familles' => Membre::GRANDES_FAMILLES,
        ]);
    }

    #[Route('/nouveau', name: 'membre_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BUREAU')]
    public function new(
        Request $request,
        MembreService $membreService,
    ): Response {
        $membre = new Membre();
        $membre->setDateAdhesion(new \DateTime());

        $form = $this->createForm(MembreType::class, $membre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photoFile')->getData();
            $result = $membreService->creerMembre($membre, $photoFile);

            $this->addFlash('success', sprintf(
                '✅ Membre %s créé avec succès ! Identifiant : %s',
                $membre->getNomComplet(),
                $membre->getIdentifiant()
            ));

            $this->addFlash('warning', sprintf(
                '🔐 Compte créé ! Login : %s | Mot de passe : %s (À noter, affiché une seule fois)',
                $result['user']->getEmail(),
                $result['plainPassword']
            ));

            return $this->redirectToRoute('membre_show', ['id' => $membre->getId()]);
        }

        return $this->render('membre/new.html.twig', [
            'form'   => $form,
            'membre' => $membre,
        ]);
    }

    #[Route('/export/excel', name: 'membre_export_excel', methods: ['GET'])]
    #[IsGranted('ROLE_BUREAU')]
    public function exportExcel(ExcelService $excelService): Response
    {
        return $excelService->exportMembres();
    }

    #[Route('/familles', name: 'membre_famille_index', methods: ['GET'])]
    public function indexFamilles(MembreRepository $membreRepo): Response
    {
        $familles = [];
        foreach (Membre::GRANDES_FAMILLES as $nomFamille) {
            $familles[$nomFamille] = $membreRepo->findBy(['grandeFamille' => $nomFamille], ['prenom' => 'ASC']);
        }

        return $this->render('membre/familles.html.twig', [
            'familles' => $familles,
        ]);
    }

    #[Route('/{id}/reset-password', name: 'membre_reset_password', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function resetPassword(Membre $membre, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $membre->getUser();
        if (!$user) {
            $this->addFlash('error', 'Aucun compte utilisateur associé à ce membre.');
            return $this->redirectToRoute('membre_show', ['id' => $membre->getId()]);
        }

        // Générer un nouveau mot de passe aléatoire
        $plainPassword = bin2hex(random_bytes(4));
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $user->setMustChangePassword(true);
        
        $em->flush();

        $this->addFlash('warning', sprintf(
            '🔐 Nouveau mot de passe temporaire pour %s : %s (À noter, affiché une seule fois)',
            $membre->getNomComplet(),
            $plainPassword
        ));

        return $this->redirectToRoute('membre_show', ['id' => $membre->getId()]);
    }

    #[Route('/{id}', name: 'membre_show', methods: ['GET'])]
    public function show(
        Membre $membre,
        CotisationRepository $cotisationRepo,
        FinanceService $financeService,
    ): Response {
        // Sécurité : Un membre ne peut voir que sa propre fiche
        if ($this->isGranted('ROLE_MEMBRE') && !$this->isGranted('ROLE_VP_SOCIAL')) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            if (!$user->getMembre() || $user->getMembre()->getId() !== $membre->getId()) {
                throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à consulter cette fiche.");
            }
        }

        $annee = (int) date('Y');
        $cotisationsAnnee = $cotisationRepo->getTotalByMembreAndAnnee($membre, $annee);
        $objectif = 18000;
        $arrieres = $financeService->getArrieresByMembre($membre);

        return $this->render('membre/show.html.twig', [
            'membre'           => $membre,
            'cotisationsAnnee' => $cotisationsAnnee,
            'objectif'         => $objectif,
            'pourcentage'      => min(100, (int) round($cotisationsAnnee / $objectif * 100)),
            'arrieres'         => $arrieres,
            'annee'            => $annee,
        ]);
    }

    #[Route('/{id}/modifier', name: 'membre_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BUREAU')]
    public function edit(
        Request $request,
        Membre $membre,
        EntityManagerInterface $em,
        MembreService $membreService,
    ): Response {
        $form = $this->createForm(MembreType::class, $membre, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photoFile')->getData();
            if ($photoFile) {
                $membreService->updatePhoto($membre, $photoFile);
            }
            $em->flush();

            $this->addFlash('success', 'Fiche membre mise à jour avec succès.');
            return $this->redirectToRoute('membre_show', ['id' => $membre->getId()]);
        }

        return $this->render('membre/edit.html.twig', [
            'form'   => $form,
            'membre' => $membre,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'membre_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        Membre $membre,
        MembreService $membreService,
    ): Response {
        if ($this->isCsrfTokenValid('delete_membre_' . $membre->getId(), $request->getPayload()->getString('_token'))) {
            $membreService->supprimerMembre($membre);
            $this->addFlash('success', 'Membre supprimé avec succès.');
        }
        return $this->redirectToRoute('membre_index');
    }

    #[Route('/{id}/statut', name: 'membre_statut', methods: ['POST'])]
    #[IsGranted('ROLE_BUREAU')]
    public function changerStatut(
        Request $request,
        Membre $membre,
        EntityManagerInterface $em,
    ): Response {
        $statut = $request->request->get('statut');
        if (in_array($statut, [Membre::STATUT_ACTIF, Membre::STATUT_INACTIF, Membre::STATUT_ANCIEN])) {
            $membre->setStatut($statut);
            $em->flush();
            $this->addFlash('success', sprintf('Statut de %s changé en "%s".', $membre->getNomComplet(), $statut));
        }
        return $this->redirectToRoute('membre_show', ['id' => $membre->getId()]);
    }

    // API pour calcul âge/ancienneté en temps réel (AJAX)
    #[Route('/api/calcul', name: 'membre_api_calcul', methods: ['GET'])]
    public function apiCalcul(Request $request): Response
    {
        $dateNaissance = $request->query->get('date_naissance');
        $dateAdhesion = $request->query->get('date_adhesion');
        $result = [];

        if ($dateNaissance) {
            try {
                $dn = new \DateTime($dateNaissance);
                $result['age'] = (new \DateTime())->diff($dn)->y;
            } catch (\Exception) {}
        }

        if ($dateAdhesion) {
            try {
                $da = new \DateTime($dateAdhesion);
                $diff = (new \DateTime())->diff($da);
                $years = $diff->y; $months = $diff->m;
                if ($years === 0 && $months === 0) $result['anciennete'] = "Moins d'un mois";
                elseif ($years === 0) $result['anciennete'] = "{$months} mois";
                elseif ($months === 0) $result['anciennete'] = "{$years} an" . ($years > 1 ? 's' : '');
                else $result['anciennete'] = "{$years} an" . ($years > 1 ? 's' : '') . " et {$months} mois";
            } catch (\Exception) {}
        }

        return $this->json($result);
    }
}
