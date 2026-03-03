<?php

namespace App\Repository;

use App\Entity\Cotisation;
use App\Entity\Membre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CotisationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cotisation::class);
    }

    public function getTotalByMembreAndAnnee(Membre $membre, int $annee): float
    {
        $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $annee));
        $end   = $start->modify('+1 year');

        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.montant) as total')
            ->where('c.membre = :membre')
            ->andWhere('c.type = :type')
            ->andWhere('c.datePaiement >= :start')
            ->andWhere('c.datePaiement < :end')
            ->setParameter('membre', $membre)
            ->setParameter('type', Cotisation::TYPE_MENSUELLE)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()->getSingleScalarResult();
        return (float) ($result ?? 0);
    }

    public function getEvolutionMensuelle(int $annee): array
    {
        $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $annee));
        $end   = $start->modify('+1 year');

        $result = $this->createQueryBuilder('c')
            ->select('c.datePaiement, c.montant')
            ->where('c.datePaiement >= :start')
            ->andWhere('c.datePaiement < :end')
            ->andWhere('c.type = :type')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('type', Cotisation::TYPE_MENSUELLE)
            ->orderBy('c.datePaiement', 'ASC')
            ->getQuery()->getArrayResult();

        $moisLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
        $data = array_fill(0, 12, 0);
        foreach ($result as $row) {
            /** @var \DateTimeInterface $date */
            $date = $row['datePaiement'];
            $monthIndex = (int) $date->format('n') - 1;
            $data[$monthIndex] += (float) $row['montant'];
        }
        return ['labels' => $moisLabels, 'data' => $data];
    }

    public function getTotalByType(int $annee): array
    {
        $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $annee));
        $end   = $start->modify('+1 year');

        return $this->createQueryBuilder('c')
            ->select('c.type, SUM(c.montant) as total')
            ->where('c.datePaiement >= :start')
            ->andWhere('c.datePaiement < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('c.type')
            ->getQuery()->getArrayResult();
    }

    public function getTotalEntrees(): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.montant)')
            ->where('c.statut = :statut')
            ->setParameter('statut', Cotisation::STATUT_PAYEE)
            ->getQuery()->getSingleScalarResult();
        return (float) ($result ?? 0);
    }

    public function getTotalMoisCourant(): float
    {
        $annee = (int) date('Y');
        $mois  = (int) date('m');
        $start = new \DateTimeImmutable(sprintf('%d-%02d-01 00:00:00', $annee, $mois));
        $end   = $start->modify('+1 month');

        $result = $this->createQueryBuilder('c')
            ->select('SUM(c.montant)')
            ->where('c.datePaiement >= :start')
            ->andWhere('c.datePaiement < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()->getSingleScalarResult();
        return (float) ($result ?? 0);
    }

    public function findWithFilters(array $filters = []): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.membre', 'm')
            ->addSelect('m')
            ->orderBy('c.datePaiement', 'DESC');

        if (!empty($filters['type'])) {
            $qb->andWhere('c.type = :type')->setParameter('type', $filters['type']);
        }
        if (!empty($filters['statut'])) {
            $qb->andWhere('c.statut = :statut')->setParameter('statut', $filters['statut']);
        }
        if (!empty($filters['annee'])) {
            $annee = (int) $filters['annee'];
            $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $annee));
            $end   = $start->modify('+1 year');
            $qb->andWhere('c.datePaiement >= :start_annee')
               ->andWhere('c.datePaiement < :end_annee')
               ->setParameter('start_annee', $start)
               ->setParameter('end_annee', $end);
        }
        if (!empty($filters['membre_id'])) {
            $qb->andWhere('m.id = :membre_id')->setParameter('membre_id', $filters['membre_id']);
        }

        return $qb;
    }

    public function generateRecuNumero(): string
    {
        $annee = date('Y');
        $count = $this->count([]) + 1;
        return sprintf('RECU-%s-%05d', $annee, $count);
    }

    public function getRapportAnnuel(int $annee): array
    {
        $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $annee));
        $end   = $start->modify('+1 year');

        return $this->createQueryBuilder('c')
            ->select('IDENTITY(c.membre) as membre_id, SUM(c.montant) as total')
            ->where('c.type = :type')
            ->andWhere('c.datePaiement >= :start')
            ->andWhere('c.datePaiement < :end')
            ->andWhere('c.statut = :statut')
            ->setParameter('type', Cotisation::TYPE_MENSUELLE)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statut', Cotisation::STATUT_PAYEE)
            ->groupBy('c.membre')
            ->getQuery()->getArrayResult();
    }

    public function findByYear(int $annee): array
    {
        $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $annee));
        $end   = $start->modify('+1 year');

        return $this->createQueryBuilder('c')
            ->where('c.datePaiement >= :start')
            ->andWhere('c.datePaiement < :end')
            ->andWhere('c.statut = :statut')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statut', Cotisation::STATUT_PAYEE)
            ->orderBy('c.datePaiement', 'ASC')
            ->getQuery()->getResult();
    }

    public function findByTypeAndYear(string $type, int $annee): array
    {
        $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $annee));
        $end   = $start->modify('+1 year');

        return $this->createQueryBuilder('c')
            ->where('c.type = :type')
            ->andWhere('c.datePaiement >= :start')
            ->andWhere('c.datePaiement < :end')
            ->andWhere('c.statut = :statut')
            ->setParameter('type', $type)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('statut', Cotisation::STATUT_PAYEE)
            ->orderBy('c.datePaiement', 'ASC')
            ->getQuery()->getResult();
    }
}
