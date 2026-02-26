<?php

namespace App\Controller;

use App\Entity\Annonce;
use App\Form\AnnonceType;
use App\Repository\AnnonceRepository;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/communication')]
#[IsGranted('ROLE_USER')]
class CommunicationController extends AbstractController
{
    #[Route('/annonces', name: 'annonce_index', methods: ['GET'])]
    public function indexAnnonces(AnnonceRepository $repo): Response
    {
        return $this->render('communication/annonces.html.twig', [
            'annonces' => $repo->findBy(['isActive' => true], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/annonces/nouveau', name: 'annonce_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BUREAU')]
    public function newAnnonce(Request $request, EntityManagerInterface $em, AuditService $audit): Response
    {
        $annonce = new Annonce();
        $form = $this->createForm(AnnonceType::class, $annonce);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $annonce->setAuteur($this->getUser());
            $em->persist($annonce);
            $em->flush();

            $audit->log('Création annonce (via Form)', 'Annonce', $annonce->getId(), $annonce->getTitre());

            $this->addFlash('success', 'Annonce publiée avec succès.');
            return $this->redirectToRoute('annonce_index');
        }

        return $this->render('communication/annonce_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
