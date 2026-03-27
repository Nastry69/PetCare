<?php

namespace App\Entity;

use App\Repository\PartageAnimalRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartageAnimalRepository::class)]
class PartageAnimal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $rolePartage = null;

    #[ORM\Column]
    private ?\DateTime $dateInvitation = null;

    #[ORM\ManyToOne(inversedBy: 'partageAnimals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Animal $animal = null;

    #[ORM\ManyToOne(inversedBy: 'partageAnimals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRolePartage(): ?string
    {
        return $this->rolePartage;
    }

    public function setRolePartage(string $rolePartage): static
    {
        $this->rolePartage = $rolePartage;

        return $this;
    }

    public function getDateInvitation(): ?\DateTime
    {
        return $this->dateInvitation;
    }

    public function setDateInvitation(\DateTime $dateInvitation): static
    {
        $this->dateInvitation = $dateInvitation;

        return $this;
    }

    public function getAnimal(): ?Animal
    {
        return $this->animal;
    }

    public function setAnimal(?Animal $animal): static
    {
        $this->animal = $animal;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
