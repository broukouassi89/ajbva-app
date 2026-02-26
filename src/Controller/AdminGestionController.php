<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\ProcesVerbal;
use App\Form\EvenementType;
use App\Form\ProcesVerbalType;
use App\Repository\EvenementRepository;
use App\Repository\ProcesVerbalRepository;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/gestion')]
#[IsGranted('ROLE_BUREAU')]
class AdminGestionController extends AbstractController
{
    #[Route('/evenements', name: 'admin_evenement_index', methods: ['GET'])]
    public function indexEvenements(EvenementRepository $repo): Response
    {
        return $this->render('admin_gestion/evenements.html.twig', [
            'evenements' => $repo->findBy([], ['dateDebut' => 'DESC']),
        ]);
    }

    #[Route('/evenements/nouveau', name: 'admin_evenement_new', methods: ['GET', 'POST'])]
    public function newEvenement(Request $request, EntityManagerInterface $em, AuditService $audit): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($evenement);
            $em->flush();

            $audit->log('Création événement (via Form)', 'Evenement', $evenement->getId(), $evenement->getTitre());

            $this->addFlash('success', 'Événement créé avec succès.');
            return $this->redirectToRoute('admin_evenement_index');
        }

        return $this->render('admin_gestion/evenement_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/evenements/{id}/pv', name: 'admin_pv_new', methods: ['GET', 'POST'])]
    public function newPV(Evenement $evenement, Request $request, EntityManagerInterface $em, AuditService $audit): Response
    {
        $pv = $evenement->getProcesVerbal() ?? new ProcesVerbal();
        $pv->setEvenement($evenement);
        
        $form = $this->createForm(ProcesVerbalType::class, $pv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pv->setRedacteur($this->getUser());
            $em->persist($pv);
            $em->flush();

            $audit->log('Rédaction PV (via Form)', 'ProcesVerbal', $pv->getId(), 'PV pour : ' . $evenement->getTitre());

            $this->addFlash('success', 'Procès-verbal enregistré avec succès.');
            return $this->redirectToRoute('admin_evenement_index');
        }

        return $this->render('admin_gestion/pv_new.html.twig', [
            'evenement' => $evenement,
            'form' => $form->createView(),
        ]);
    }
}
