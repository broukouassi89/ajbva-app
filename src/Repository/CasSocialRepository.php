<?php
namespace App\Repository;
use App\Entity\CasSocial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CasSocialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, CasSocial::class); }

    public function countEnAttente(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.statutAssistance = :statut')
            ->setParameter('statut', 'En attente')
            ->getQuery()->getSingleScalarResult();
    }

    public function getTotalAssistancesPayees(): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.montantAssistance)')
            ->where('c.statutAssistance = :statut')
            ->setParameter('statut', 'Payée')
            ->getQuery()->getSingleScalarResult();
        return (float) ($result ?? 0);
    }

    public function findWithFilters(array $filters = []): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('cs')
            ->join('cs.membre', 'm')->addSelect('m')
            ->orderBy('cs.dateEvenement', 'DESC');
        if (!empty($filters['type'])) $qb->andWhere('cs.type = :type')->setParameter('type', $filters['type']);
        if (!empty($filters['statut'])) $qb->andWhere('cs.statutAssistance = :statut')->setParameter('statut', $filters['statut']);
        if (!empty($filters['membre_id'])) $qb->andWhere('m.id = :membre_id')->setParameter('membre_id', $filters['membre_id']);
        return $qb;
    }

    public function getStatsByType(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.type, COUNT(c.id) as total, SUM(c.montantAssistance) as totalMontant')
            ->where('c.statutAssistance = :statut')
            ->setParameter('statut', 'Payée')
            ->groupBy('c.type')
            ->getQuery()->getArrayResult();
    }

    public function findByYear(int $annee): array
    {
        $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $annee));
        $end   = $start->modify('+1 year');

        return $this->createQueryBuilder('cs')
            ->where('cs.dateEvenement >= :start')
            ->andWhere('cs.dateEvenement < :end')
            ->andWhere('cs.statutAssistance = :statut')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statut', 'Payée')
            ->orderBy('cs.dateEvenement', 'ASC')
            ->getQuery()->getResult();
    }
}
