<?php
namespace App\Repository;
use App\Entity\Patrimoine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PatrimoineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Patrimoine::class); }

    public function getValeurTotale(): float
    {
        $result = $this->createQueryBuilder('p')->select('SUM(p.valeurAchat)')
            ->getQuery()->getSingleScalarResult();
        return (float)($result ?? 0);
    }
}
