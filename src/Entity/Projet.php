<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjetRepository::class)]
class Projet
{
    public const STATUTS = ['En cours' => 'En cours', 'Terminé' => 'Terminé', 'Annulé' => 'Annulé'];

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
    private string $budgetTotal = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $montantCollecte = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $benefices = '0.00';

    #[ORM\Column(length: 20)]
    private string $statut = 'En cours';

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: Patrimoine::class, mappedBy: 'projet')]
    private Collection $patrimoines;

    public function __construct()
    {
        $this->patrimoines = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getPourcentageAvancement(): int
    {
        if ((float) $this->budgetTotal <= 0) return 0;
        return min(100, (int) round(((float) $this->montantCollecte / (float) $this->budgetTotal) * 100));
    }

    public function getResteAFinancer(): float
    {
        return max(0, (float) $this->budgetTotal - (float) $this->montantCollecte);
    }

    public function getStatutBadgeClass(): string
    {
        return match ($this->statut) {
            'En cours' => 'badge-primary',
            'Terminé'  => 'badge-success',
            'Annulé'   => 'badge-danger',
            default    => 'badge-secondary',
        };
    }

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): static { $this->nom = $nom; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getBudgetTotal(): string { return $this->budgetTotal; }
    public function setBudgetTotal(string|float $budgetTotal): static { $this->budgetTotal = (string) $budgetTotal; return $this; }
    public function getMontantCollecte(): string { return $this->montantCollecte; }
    public function setMontantCollecte(string|float $montant): static { $this->montantCollecte = (string) $montant; return $this; }
    public function getBenefices(): string { return $this->benefices; }
    public function setBenefices(string|float $benefices): static { $this->benefices = (string) $benefices; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeInterface $date): static { $this->dateDebut = $date; return $this; }
    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(?\DateTimeInterface $date): static { $this->dateFin = $date; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getPatrimoines(): Collection { return $this->patrimoines; }
}
