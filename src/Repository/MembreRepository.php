<?php

namespace App\Repository;

use App\Entity\Membre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MembreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membre::class);
    }

    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.prenom', 'ASC')
            ->addOrderBy('m.nom', 'ASC');

        if (!empty($filters['search'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('m.nom', ':search'),
                    $qb->expr()->like('m.prenom', ':search'),
                    $qb->expr()->like('m.identifiant', ':search'),
                    $qb->expr()->like('m.telephone', ':search'),
                )
            )->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['statut'])) {
            $qb->andWhere('m.statut = :statut')->setParameter('statut', $filters['statut']);
        }
        if (!empty($filters['village'])) {
            $qb->andWhere('m.villageOrigine = :village')->setParameter('village', $filters['village']);
        }
        if (!empty($filters['famille'])) {
            $qb->andWhere('m.grandeFamille = :famille')->setParameter('famille', $filters['famille']);
        }
        if (!empty($filters['genre'])) {
            $qb->andWhere('m.genre = :genre')->setParameter('genre', $filters['genre']);
        }

        return $qb->getQuery()->getResult();
    }

    public function findActifs(): array
    {
        return $this->findBy(['statut' => Membre::STATUT_ACTIF], ['prenom' => 'ASC']);
    }

    public function countByStatut(): array
    {
        $result = $this->createQueryBuilder('m')
            ->select('m.statut, COUNT(m.id) as total')
            ->groupBy('m.statut')
            ->getQuery()->getArrayResult();

        $counts = ['Actif' => 0, 'Inactif' => 0, 'Ancien Membre' => 0, 'total' => 0];
        foreach ($result as $row) {
            $counts[$row['statut']] = (int) $row['total'];
            $counts['total'] += (int) $row['total'];
        }
        return $counts;
    }

    public function countByVillage(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.villageOrigine as village, COUNT(m.id) as total')
            ->where('m.villageOrigine IS NOT NULL')
            ->groupBy('m.villageOrigine')
            ->orderBy('total', 'DESC')
            ->getQuery()->getArrayResult();
    }

    public function countByGenre(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.genre, COUNT(m.id) as total')
            ->groupBy('m.genre')
            ->getQuery()->getArrayResult();
    }

    public function findNouveauxMois(int $mois = 1): array
    {
        $date = new \DateTime("-{$mois} month");
        return $this->createQueryBuilder('m')
            ->where('m.dateAdhesion >= :date')
            ->setParameter('date', $date)
            ->orderBy('m.dateAdhesion', 'DESC')
            ->getQuery()->getResult();
    }

    public function generateIdentifiant(): string
    {
        $annee = date('Y');
        $count = $this->count([]) + 1;
        return sprintf('AJBVA-%s-%04d', $annee, $count);
    }
}
