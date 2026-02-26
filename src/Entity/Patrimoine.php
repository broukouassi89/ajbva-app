<?php

namespace App\Entity;

use App\Repository\PatrimoineRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PatrimoineRepository::class)]
class Patrimoine
{
    public const ETATS = ['Bon état' => 'Bon état', 'À entretenir' => 'À entretenir', 'Hors service' => 'Hors service'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\Positive]
    private string $valeurAchat = '0.00';

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateAcquisition = null;

    #[ORM\ManyToOne(targetEntity: Projet::class, inversedBy: 'patrimoines')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Projet $projet = null;

    #[ORM\Column(length: 20)]
    private string $etat = 'Bon état';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->dateAcquisition = new \DateTime();
    }

    public function getEtatBadgeClass(): string
    {
        return match ($this->etat) {
            'Bon état'     => 'badge-success',
            'À entretenir' => 'badge-warning',
            'Hors service' => 'badge-danger',
            default        => 'badge-secondary',
        };
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): static { $this->nom = $nom; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getValeurAchat(): string { return $this->valeurAchat; }
    public function setValeurAchat(string|float $valeur): static { $this->valeurAchat = (string) $valeur; return $this; }
    public function getDateAcquisition(): ?\DateTimeInterface { return $this->dateAcquisition; }
    public function setDateAcquisition(?\DateTimeInterface $date): static { $this->dateAcquisition = $date; return $this; }
    public function getProjet(): ?Projet { return $this->projet; }
    public function setProjet(?Projet $projet): static { $this->projet = $projet; return $this; }
    public function getEtat(): string { return $this->etat; }
    public function setEtat(string $etat): static { $this->etat = $etat; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
