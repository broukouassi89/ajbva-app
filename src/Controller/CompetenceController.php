<?php

namespace App\Controller;

use App\Repository\MembreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/annuaire-competences')]
#[IsGranted('ROLE_USER')]
class CompetenceController extends AbstractController
{
    #[Route('', name: 'competence_index', methods: ['GET'])]
    public function index(Request $request, MembreRepository $membreRepo): Response
    {
        $secteur = $request->query->get('secteur');
        $search = $request->query->get('q');

        $query = $membreRepo->createQueryBuilder('m')
            ->where('m.statut = :statut')
            ->setParameter('statut', 'Actif')
            ->andWhere('m.profession IS NOT NULL OR m.secteurActivite IS NOT NULL');

        if ($secteur) {
            $query->andWhere('m.secteurActivite = :secteur')
                  ->setParameter('secteur', $secteur);
        }

        if ($search) {
            $query->andWhere('m.nom LIKE :search OR m.prenom LIKE :search OR m.profession LIKE :search')
                  ->setParameter('search', '%'.$search.'%');
        }

        $membres = $query->orderBy('m.secteurActivite', 'ASC')
                         ->addOrderBy('m.nom', 'ASC')
                         ->getQuery()
                         ->getResult();

        $secteurs = $membreRepo->createQueryBuilder('m')
            ->select('DISTINCT m.secteurActivite')
            ->where('m.secteurActivite IS NOT NULL')
            ->getQuery()
            ->getSingleColumnResult();

        return $this->render('competence/index.html.twig', [
            'membres' => $membres,
            'secteurs' => $secteurs,
            'currentSecteur' => $secteur,
            'search' => $search,
        ]);
    }
}
