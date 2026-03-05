<?php

namespace App\Controller;

use App\Entity\Document;
use App\Repository\DocumentRepository;
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

#[Route('/documents')]
#[IsGranted('ROLE_USER')]
class DocumentController extends AbstractController
{
    public function __construct(
        private readonly string $uploadDir
    ) {}

    #[Route('', name: 'document_index', methods: ['GET'])]
    public function index(DocumentRepository $repo): Response
    {
        return $this->render('document/index.html.twig', [
            'documents' => $repo->findBy([], ['createdAt' => 'DESC']),
            'categories' => Document::CATEGORIES,
        ]);
    }

    #[Route('/nouveau', name: 'document_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_BUREAU')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, AuditService $audit): Response
    {
        if ($request->isMethod('POST')) {
            $titre = $request->request->get('titre');
            $categorie = $request->request->get('categorie');
            $description = $request->request->get('description');
            /** @var UploadedFile $file */
            $file = $request->files->get('document');

            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    $file->move($this->uploadDir . '/documents', $newFilename);
                    
                    $doc = new Document();
                    $doc->setTitre($titre)
                        ->setCategorie($categorie)
                        ->setDescription($description)
                        ->setFilename($newFilename)
                        ->setUploadedBy($this->getUser());

                    $em->persist($doc);
                    $em->flush();

                    $audit->log('Upload document', 'Document', $doc->getId(), $doc->getTitre());
                    $this->addFlash('success', 'Document ajouté avec succès.');
                    return $this->redirectToRoute('document_index');
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'envoi du fichier.');
                }
            }
        }

        return $this->render('document/new.html.twig', [
            'categories' => Document::CATEGORIES,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'document_delete', methods: ['POST'])]
    #[IsGranted('ROLE_BUREAU')]
    public function delete(Document $doc, EntityManagerInterface $em, AuditService $audit): Response
    {
        $audit->log('Suppression document', 'Document', $doc->getId(), $doc->getTitre());
        
        $filePath = $this->uploadDir . '/documents/' . $doc->getFilename();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $em->remove($doc);
        $em->flush();

        $this->addFlash('success', 'Document supprimé.');
        return $this->redirectToRoute('document_index');
    }
}
