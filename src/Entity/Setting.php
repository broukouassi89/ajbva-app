<?php

namespace App\Entity;

use App\Repository\SettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingRepository::class)]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $cle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $valeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(length: 50)]
    private string $type = 'string'; // string, integer, decimal

    public function getId(): ?int { return $this->id; }
    public function getCle(): ?string { return $this->cle; }
    public function setCle(string $cle): static { $this->cle = $cle; return $this; }
    public function getValeur(): ?string { return $this->valeur; }
    public function setValeur(?string $valeur): static { $this->valeur = $valeur; return $this; }
    public function getLabel(): ?string { return $this->label; }
    public function setLabel(?string $label): static { $this->label = $label; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
}
