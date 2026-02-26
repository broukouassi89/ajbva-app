<?php

namespace App\Entity;

use App\Repository\CotisationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CotisationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Cotisation
{
    public const TYPE_MENSUELLE     = 'mensuelle';
    public const TYPE_EXCEPTIONNELLE = 'exceptionnelle';
    public const TYPE_SOCIALE       = 'sociale';
    public const TYPE_ADHESION      = 'adhesion';

    public const STATUT_PAYEE      = 'payee';
    public const STATUT_PARTIELLE  = 'partielle';
    public const STATUT_EN_ATTENTE = 'en_attente';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Membre::class, inversedBy: 'cotisations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Membre $membre = null;

    #[ORM\Column(length: 30)]
    private string $type = self::TYPE_MENSUELLE;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\Positive(message: 'Le montant doit être positif.')]
    private string $montant = '0.00';

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $datePaiement = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $moisConcerne = null; // Format: 2024-01

    #[ORM\ManyToOne(targetEntity: CasSocial::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CasSocial $casSocial = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(length: 30, nullable: true, unique: true)]
    private ?string $recuNumero = null;

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUT_PAYEE;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->datePaiement = new \DateTime();
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_MENSUELLE      => 'Cotisation Mensuelle',
            self::TYPE_EXCEPTIONNELLE => 'Cotisation Exceptionnelle',
            self::TYPE_SOCIALE        => 'Cotisation Sociale',
            self::TYPE_ADHESION       => "Carte d'Adhésion",
            default => $this->type,
        };
    }

    public function getMontantFormate(): string
    {
        return number_format((float) $this->montant, 0, ',', ' ') . ' F CFA';
    }

    public function getStatutLabel(): string
    {
        return match ($this->statut) {
            self::STATUT_PAYEE      => 'Payée',
            self::STATUT_PARTIELLE  => 'Partielle',
            self::STATUT_EN_ATTENTE => 'En attente',
            default => $this->statut,
        };
    }

    public function getStatutBadgeClass(): string
    {
        return match ($this->statut) {
            self::STATUT_PAYEE      => 'badge-success',
            self::STATUT_PARTIELLE  => 'badge-warning',
            self::STATUT_EN_ATTENTE => 'badge-secondary',
            default => 'badge-secondary',
        };
    }

    public function getTypeBadgeClass(): string
    {
        return match ($this->type) {
            self::TYPE_MENSUELLE      => 'badge-primary',
            self::TYPE_EXCEPTIONNELLE => 'badge-warning',
            self::TYPE_SOCIALE        => 'badge-danger',
            self::TYPE_ADHESION       => 'badge-success',
            default => 'badge-secondary',
        };
    }

    // Standard getters/setters
    public function getId(): ?int { return $this->id; }

    public function getMembre(): ?Membre { return $this->membre; }
    public function setMembre(?Membre $membre): static { $this->membre = $membre; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getMontant(): string { return $this->montant; }
    public function setMontant(string|float $montant): static { $this->montant = (string) $montant; return $this; }

    public function getDatePaiement(): ?\DateTimeInterface { return $this->datePaiement; }
    public function setDatePaiement(?\DateTimeInterface $datePaiement): static { $this->datePaiement = $datePaiement; return $this; }

    public function getMoisConcerne(): ?string { return $this->moisConcerne; }
    public function setMoisConcerne(?string $moisConcerne): static { $this->moisConcerne = $moisConcerne; return $this; }

    public function getCasSocial(): ?CasSocial { return $this->casSocial; }
    public function setCasSocial(?CasSocial $casSocial): static { $this->casSocial = $casSocial; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): static { $this->note = $note; return $this; }

    public function getRecuNumero(): ?string { return $this->recuNumero; }
    public function setRecuNumero(?string $recuNumero): static { $this->recuNumero = $recuNumero; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
