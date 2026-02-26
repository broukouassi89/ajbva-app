<?php

namespace App\Entity;

use App\Repository\CasSocialRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CasSocialRepository::class)]
#[ORM\Table(name: 'cas_social')]
class CasSocial
{
    public const TYPES = [
        'Décès membre'      => 'Décès membre',
        'Décès conjoint(e)' => 'Décès conjoint(e)',
        'Décès enfant'      => 'Décès enfant',
        'Décès père/mère'   => 'Décès père/mère',
        'Mariage'           => 'Mariage',
        'Naissance'         => 'Naissance',
    ];

    public const STATUTS = [
        'En attente' => 'En attente',
        'Validée'    => 'Validée',
        'Payée'      => 'Payée',
        'Refusée'    => 'Refusée',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private ?string $type = null;

    #[ORM\ManyToOne(targetEntity: Membre::class, inversedBy: 'casSociaux')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Membre $membre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateEvenement = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $montantAssistance = null;

    #[ORM\Column(length: 20)]
    private string $statutAssistance = 'En attente';

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $datePaiementAssistance = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $declaredBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $validatedBy = null;

    #[ORM\OneToMany(targetEntity: Cotisation::class, mappedBy: 'casSocial')]
    private Collection $cotisationsSociales;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->cotisationsSociales = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->dateEvenement = new \DateTime();
    }

    public function getTypeIcon(): string
    {
        if (str_contains($this->type ?? '', 'Décès')) return '⚫';
        return match ($this->type) {
            'Mariage'   => '💍',
            'Naissance' => '👶',
            default     => '📋',
        };
    }

    public function getStatutBadgeClass(): string
    {
        return match ($this->statutAssistance) {
            'En attente' => 'badge-warning',
            'Validée'    => 'badge-info',
            'Payée'      => 'badge-success',
            'Refusée'    => 'badge-danger',
            default      => 'badge-secondary',
        };
    }

    public function getMontantAssistanceFormate(): string
    {
        if (!$this->montantAssistance) return '—';
        return number_format((float) $this->montantAssistance, 0, ',', ' ') . ' F CFA';
    }

    public function getNbCotisationsSocialesPayees(): int
    {
        return $this->cotisationsSociales->filter(
            fn($c) => $c->getStatut() === Cotisation::STATUT_PAYEE
        )->count();
    }

    public function getTotalCotisationsSocialesCollectees(): float
    {
        return $this->cotisationsSociales->filter(
            fn($c) => $c->getStatut() === Cotisation::STATUT_PAYEE
        )->reduce(fn($carry, $c) => $carry + (float) $c->getMontant(), 0.0);
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): static { $this->type = $type; return $this; }

    public function getMembre(): ?Membre { return $this->membre; }
    public function setMembre(?Membre $membre): static { $this->membre = $membre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getDateEvenement(): ?\DateTimeInterface { return $this->dateEvenement; }
    public function setDateEvenement(?\DateTimeInterface $date): static { $this->dateEvenement = $date; return $this; }

    public function getMontantAssistance(): ?string { return $this->montantAssistance; }
    public function setMontantAssistance(string|float|null $montant): static { $this->montantAssistance = $montant !== null ? (string) $montant : null; return $this; }

    public function getStatutAssistance(): string { return $this->statutAssistance; }
    public function setStatutAssistance(string $statut): static { $this->statutAssistance = $statut; return $this; }

    public function getDatePaiementAssistance(): ?\DateTimeInterface { return $this->datePaiementAssistance; }
    public function setDatePaiementAssistance(?\DateTimeInterface $date): static { $this->datePaiementAssistance = $date; return $this; }

    public function getDeclaredBy(): ?User { return $this->declaredBy; }
    public function setDeclaredBy(?User $user): static { $this->declaredBy = $user; return $this; }

    public function getValidatedBy(): ?User { return $this->validatedBy; }
    public function setValidatedBy(?User $user): static { $this->validatedBy = $user; return $this; }

    public function getCotisationsSociales(): Collection { return $this->cotisationsSociales; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
