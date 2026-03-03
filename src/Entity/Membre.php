<?php

namespace App\Entity;

use App\Repository\MembreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MembreRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Membre
{
    public const STATUT_ACTIF = 'Actif';
    public const STATUT_INACTIF = 'Inactif';
    public const STATUT_ANCIEN = 'Ancien Membre';

    public const STATUTS = [
        'Actif' => self::STATUT_ACTIF,
        'Inactif' => self::STATUT_INACTIF,
        'Ancien Membre' => self::STATUT_ANCIEN,
    ];

    public const GENRE_MASCULIN = 'Masculin';
    public const GENRE_FEMININ = 'Féminin';

    public const GRANDES_FAMILLES = [
        'ABOLY KOUAKOU HOSSOU', 'AKO HOSSOU', 'AKOGBY HOSSOU', 'AKRA HOSSOU',
        "GOLY HOSSOU", 'HANGA HOSSOU', 'KAN ADJOUA HOSSOU', 'KOUA HOSSOU', 'KOULEGNAMIEN HOSSOU','PINIKRO',
    ];

    public const VILLAGES = [
        'Botro', 'Village Avoisinant 1', 'Village Avoisinant 2',
        'Village Avoisinant 3', 'Village Avoisinant 4',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30, unique: true)]
    private ?string $identifiant = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 10)]
    #[Assert\Choice(choices: ['Masculin', 'Féminin'])]
    private ?string $genre = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: 'La date de naissance est obligatoire.')]
    #[Assert\LessThan('today', message: 'La date de naissance doit être dans le passé.')]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank(message: "La date d'adhésion est obligatoire.")]
    private ?\DateTimeInterface $dateAdhesion = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire.')]
    private ?string $telephone = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email(message: "L'adresse email n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $grandeFamille = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $villageOrigine = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $profession = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $secteurActivite = null;

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUT_ACTIF;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $soldeCotisations = '0.00';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: Cotisation::class, mappedBy: 'membre', cascade: ['remove'])]
    #[ORM\OrderBy(['datePaiement' => 'DESC'])]
    private Collection $cotisations;

    #[ORM\OneToMany(targetEntity: CasSocial::class, mappedBy: 'membre', cascade: ['remove'])]
    #[ORM\OrderBy(['dateEvenement' => 'DESC'])]
    private Collection $casSociaux;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'membre')]
    private Collection $users;

    public function __construct()
    {
        $this->cotisations = new ArrayCollection();
        $this->casSociaux = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ─── GETTERS CALCULÉS ────────────────────────────────────────────

    public function getAge(): int
    {
        if (!$this->dateNaissance) return 0;
        return (new \DateTime())->diff($this->dateNaissance)->y;
    }

    public function getAnciennete(): string
    {
        if (!$this->dateAdhesion) return '0 an';
        $diff = (new \DateTime())->diff($this->dateAdhesion);
        $years = $diff->y;
        $months = $diff->m;

        if ($years === 0 && $months === 0) return "Moins d'un mois";
        if ($years === 0) return "{$months} mois";
        if ($months === 0) return "{$years} an" . ($years > 1 ? 's' : '');
        return "{$years} an" . ($years > 1 ? 's' : '') . " et {$months} mois";
    }

    public function getNomComplet(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function getPhotoUrl(): string
    {
        if ($this->photo) {
            return '/uploads/photos/' . $this->photo;
        }
        return $this->genre === 'Féminin' ? '/images/avatar-femme.svg' : '/images/avatar-homme.svg';
    }

    public function getStatutBadgeClass(): string
    {
        return match ($this->statut) {
            self::STATUT_ACTIF => 'badge-success',
            self::STATUT_INACTIF => 'badge-danger',
            self::STATUT_ANCIEN => 'badge-secondary',
            default => 'badge-secondary',
        };
    }

    public function getGenreIcon(): string
    {
        return $this->genre === 'Féminin' ? '♀' : '♂';
    }

    public function getGenreCssColor(): string
    {
        return $this->genre === 'Féminin' ? '#ec4899' : '#3b82f6';
    }

    // Getters/Setters standard
    public function getId(): ?int { return $this->id; }

    public function getIdentifiant(): ?string { return $this->identifiant; }
    public function setIdentifiant(string $identifiant): static { $this->identifiant = $identifiant; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(?string $nom): static { $this->nom = strtoupper(trim($nom ?? '')); return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(?string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getGenre(): ?string { return $this->genre; }
    public function setGenre(?string $genre): static { $this->genre = $genre; return $this; }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static { $this->dateNaissance = $dateNaissance; return $this; }

    public function getDateAdhesion(): ?\DateTimeInterface { return $this->dateAdhesion; }
    public function setDateAdhesion(?\DateTimeInterface $dateAdhesion): static { $this->dateAdhesion = $dateAdhesion; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): static { $this->photo = $photo; return $this; }

    public function getGrandeFamille(): ?string { return $this->grandeFamille; }
    public function setGrandeFamille(?string $grandeFamille): static { $this->grandeFamille = $grandeFamille; return $this; }

    public function getProfession(): ?string { return $this->profession; }
    public function setProfession(?string $profession): static { $this->profession = $profession; return $this; }

    public function getSecteurActivite(): ?string { return $this->secteurActivite; }
    public function setSecteurActivite(?string $secteurActivite): static { $this->secteurActivite = $secteurActivite; return $this; }

    public function getVillageOrigine(): ?string { return $this->villageOrigine; }
    public function setVillageOrigine(?string $villageOrigine): static { $this->villageOrigine = $villageOrigine; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getSoldeCotisations(): string { return $this->soldeCotisations; }
    public function setSoldeCotisations(string $soldeCotisations): static { $this->soldeCotisations = $soldeCotisations; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function getCotisations(): Collection { return $this->cotisations; }
    public function getCasSociaux(): Collection { return $this->casSociaux; }
    public function getUsers(): Collection { return $this->users; }

    public function getUser(): ?User
    {
        return $this->users->first() ?: null;
    }

    public function isActif(): bool { return $this->statut === self::STATUT_ACTIF; }
}
