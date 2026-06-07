<?php

namespace App\Entity;

use App\Repository\InvitationEnAttenteRepository;
use Doctrine\ORM\Mapping as ORM;

/** Invitation envoyée à un email sans compte PetCare. Appliquée automatiquement à l'inscription. */
#[ORM\Entity(repositoryClass: InvitationEnAttenteRepository::class)]
class InvitationEnAttente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 50)]
    private string $rolePartage;

    #[ORM\Column(length: 64, unique: true)]
    private string $token;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Animal $animal;

    public function getId(): ?int { return $this->id; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getRolePartage(): string { return $this->rolePartage; }
    public function setRolePartage(string $rolePartage): static { $this->rolePartage = $rolePartage; return $this; }

    public function getToken(): string { return $this->token; }
    public function setToken(string $token): static { $this->token = $token; return $this; }

    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(\DateTimeImmutable $expiresAt): static { $this->expiresAt = $expiresAt; return $this; }

    public function getAnimal(): Animal { return $this->animal; }
    public function setAnimal(Animal $animal): static { $this->animal = $animal; return $this; }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }
}
