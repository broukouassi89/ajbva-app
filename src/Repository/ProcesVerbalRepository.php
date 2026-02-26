<?php
namespace App\Repository;
use App\Entity\ProcesVerbal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
class ProcesVerbalRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ProcesVerbal::class); }
}
