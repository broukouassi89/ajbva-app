<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\Patrimoine;
use App\Form\ProjetType;
use App\Repository\ProjetRepository;
use App\Repository\PatrimoineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ProjetPatrimoineController extends AbstractController
{
    #[Route('/projets', name: 'projet_index', methods: ['GET'])]
    public function index(ProjetRepository $repo): Response
    {
        return $this->render('projet/index.html.twig', [
            'projets'  => $repo->findBy([], ['createdAt' => 'DESC']),
            'total'    => $repo->count([]),
        ]);
    }

    #[Route('/projets/nouveau', name: 'projet_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BUREAU')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $projet = new Projet();
        $form = $this->createForm(\App\Form\ProjetType::class, $projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($projet);
            $em->flush();
            $this->addFlash('success', 'Projet créé avec succès.');
            return $this->redirectToRoute('projet_show', ['id' => $projet->getId()]);
        }

        return $this->render('projet/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/projets/{id}', name: 'projet_show', methods: ['GET'])]
    public function show(Projet $projet): Response
    {
        return $this->render('projet/show.html.twig', ['projet' => $projet]);
    }
}
