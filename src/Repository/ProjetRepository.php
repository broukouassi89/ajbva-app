<?php
namespace App\Repository;
use App\Entity\Projet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Projet::class); }

    public function getTotalBenefices(): float
    {
        $result = $this->createQueryBuilder('p')->select('SUM(p.benefices)')
            ->getQuery()->getSingleScalarResult();
        return (float)($result ?? 0);
    }

    public function findAllWithStats(): array
    {
        return $this->findAll();
    }
}
