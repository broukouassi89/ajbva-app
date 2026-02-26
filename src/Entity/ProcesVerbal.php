<?php

namespace App\Entity;

use App\Repository\ProcesVerbalRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProcesVerbalRepository::class)]
class ProcesVerbal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'procesVerbal', targetEntity: Evenement::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Evenement $evenement = null;

    #[ORM\Column(type: 'text')]
    private ?string $contenu = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $redacteur = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getEvenement(): ?Evenement { return $this->evenement; }
    public function setEvenement(Evenement $evenement): static { $this->evenement = $evenement; return $this; }
    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = $contenu; return $this; }
    public function getRedacteur(): ?User { return $this->redacteur; }
    public function setRedacteur(?User $redacteur): static { $this->redacteur = $redacteur; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
