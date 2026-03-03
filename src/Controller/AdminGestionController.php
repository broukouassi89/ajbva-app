<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\EventPhoto;
use App\Entity\ProcesVerbal;
use App\Form\EvenementType;
use App\Form\ProcesVerbalType;
use App\Repository\EvenementRepository;
use App\Repository\ProcesVerbalRepository;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/gestion')]
#[IsGranted('ROLE_BUREAU')]
class AdminGestionController extends AbstractController
{
    public function __construct(
        private readonly string $uploadDir
    ) {}

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

    #[Route('/evenements/{id}/photos', name: 'admin_evenement_photos', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BUREAU')]
    public function uploadPhotos(Evenement $evenement, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        if ($request->isMethod('POST')) {
            /** @var UploadedFile[] $files */
            $files = $request->files->get('photos');

            if ($files) {
                foreach ($files as $file) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                    try {
                        $file->move($this->uploadDir . '/events', $newFilename);
                        
                        $photo = new EventPhoto();
                        $photo->setFilename($newFilename)
                              ->setEvenement($evenement);

                        $em->persist($photo);
                    } catch (FileException $e) {
                        continue;
                    }
                }
                $em->flush();
                $this->addFlash('success', 'Photos ajoutées à l\'événement.');
            }
            return $this->redirectToRoute('admin_evenement_photos', ['id' => $evenement->getId()]);
        }

        return $this->render('admin_gestion/evenement_photos.html.twig', [
            'evenement' => $evenement,
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
