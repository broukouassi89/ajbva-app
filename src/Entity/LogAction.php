<?php

namespace App\Entity;

use App\Repository\LogActionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogActionRepository::class)]
class LogAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $action = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $entityName = null;

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $details = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }
    public function getAction(): ?string { return $this->action; }
    public function setAction(string $action): static { $this->action = $action; return $this; }
    public function getEntityName(): ?string { return $this->entityName; }
    public function setEntityName(?string $entityName): static { $this->entityName = $entityName; return $this; }
    public function getEntityId(): ?int { return $this->entityId; }
    public function setEntityId(?int $entityId): static { $this->entityId = $entityId; return $this; }
    public function getDetails(): ?string { return $this->details; }
    public function setDetails(?string $details): static { $this->details = $details; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
