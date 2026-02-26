<?php

namespace App\Service;

use App\Entity\LogAction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class AuditService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
    ) {}

    public function log(string $action, ?string $entityName = null, ?int $entityId = null, ?string $details = null): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        $log = new LogAction();
        $log->setUser($user)
            ->setAction($action)
            ->setEntityName($entityName)
            ->setEntityId($entityId)
            ->setDetails($details);

        $this->em->persist($log);
        $this->em->flush();
    }
}
