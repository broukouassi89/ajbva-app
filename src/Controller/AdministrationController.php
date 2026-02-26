<?php

namespace App\Controller;

use App\Entity\Mandat;
use App\Form\MandatType;
use App\Repository\MandatRepository;
use App\Repository\MembreRepository;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/administration')]
#[IsGranted('ROLE_BUREAU')]
class AdministrationController extends AbstractController
{
    #[Route('/mandats', name: 'admin_mandat_index', methods: ['GET'])]
    public function indexMandats(MandatRepository $repo): Response
    {
        return $this->render('administration/mandats.html.twig', [
            'mandats' => $repo->findBy([], ['dateDebut' => 'DESC']),
        ]);
    }

    #[Route('/mandats/nouveau', name: 'admin_mandat_new', methods: ['GET', 'POST'])]
    public function newMandat(Request $request, EntityManagerInterface $em, AuditService $audit): Response
    {
        $mandat = new Mandat();
        $form = $this->createForm(MandatType::class, $mandat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($mandat);
            $em->flush();

            $audit->log('Attribution mandat (via Form)', 'Mandat', $mandat->getId(), $mandat->getPoste() . ' pour ' . $mandat->getMembre()->getNomComplet());

            $this->addFlash('success', 'Mandat attribué avec succès.');
            return $this->redirectToRoute('admin_mandat_index');
        }

        return $this->render('administration/mandat_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
