<?php

namespace App\Entity;

use App\Repository\MandatRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MandatRepository::class)]
class Mandat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Membre::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Membre $membre = null;

    #[ORM\Column(length: 100)]
    private ?string $poste = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 20)]
    private string $statut = 'En cours'; // En cours, Terminé

    public function getId(): ?int { return $this->id; }
    public function getMembre(): ?Membre { return $this->membre; }
    public function setMembre(?Membre $membre): static { $this->membre = $membre; return $this; }
    public function getPoste(): ?string { return $this->poste; }
    public function setPoste(string $poste): static { $this->poste = $poste; return $this; }
    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }
    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(?\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
}
