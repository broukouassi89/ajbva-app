<?php

namespace App\Controller;

use App\Entity\Patrimoine;
use App\Form\PatrimoineType;
use App\Repository\PatrimoineRepository;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patrimoine')]
#[IsGranted('ROLE_USER')]
class PatrimoineController extends AbstractController
{
    #[Route('', name: 'patrimoine_index', methods: ['GET'])]
    public function index(PatrimoineRepository $repo): Response
    {
        return $this->render('patrimoine/index.html.twig', [
            'patrimoines'  => $repo->findBy([], ['dateAcquisition' => 'DESC']),
            'valeurTotale' => $repo->getValeurTotale(),
        ]);
    }

    #[Route('/nouveau', name: 'patrimoine_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BUREAU')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $patrimoine = new Patrimoine();
        $form = $this->createForm(PatrimoineType::class, $patrimoine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($patrimoine);
            $em->flush();
            $this->addFlash('success', 'Bien patrimonial enregistré avec succès.');
            return $this->redirectToRoute('patrimoine_index');
        }

        return $this->render('patrimoine/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

