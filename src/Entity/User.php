<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $fullName = null;

    #[ORM\ManyToOne(targetEntity: Membre::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Membre $membre = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private bool $mustChangePassword = false;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
    }

    public function getId(): ?int { return $this->id; }

    public function isMustChangePassword(): bool { return $this->mustChangePassword; }
    public function setMustChangePassword(bool $mustChangePassword): static { $this->mustChangePassword = $mustChangePassword; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getFullName(): ?string { return $this->fullName; }
    public function setFullName(?string $fullName): static { $this->fullName = $fullName; return $this; }

    public function getMembre(): ?Membre { return $this->membre; }
    public function setMembre(?Membre $membre): static { $this->membre = $membre; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getRoleLabel(): string
    {
        if (in_array('ROLE_SUPER_ADMIN', $this->roles)) return 'Super Administrateur';
        if (in_array('ROLE_ADMIN', $this->roles)) return 'Administrateur';
        if (in_array('ROLE_BUREAU', $this->roles)) return 'Bureau';
        if (in_array('ROLE_VP_SOCIAL', $this->roles)) return 'VP Affaires Sociales';
        return 'Membre';
    }

    public function getDisplayName(): string
    {
        return $this->fullName ?? $this->email;
    }

    public function getInitials(): string
    {
        if ($this->fullName) {
            $parts = explode(' ', $this->fullName);
            return strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
        }
        return strtoupper(substr($this->email, 0, 2));
    }
}
