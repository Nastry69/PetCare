<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/** Utilisateur authentifié — implémente UserInterface (JWT) et PasswordAuthenticatedUserInterface (bcrypt). */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateInscription = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $photoUrl = null;

    /** Token hex-64 pour la réinitialisation de mot de passe (valide 1 h, null si aucune demande en cours). */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    /**
     * @var Collection<int, Animal>
     */
    #[ORM\OneToMany(targetEntity: Animal::class, mappedBy: 'proprietaire', cascade: ['remove'])]
    private Collection $animals;

    /**
     * @var Collection<int, Evenement>
     */
    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'createur', cascade: ['remove'])]
    private Collection $evenements;

    /**
     * @var Collection<int, PartageAnimal>
     */
    #[ORM\OneToMany(targetEntity: PartageAnimal::class, mappedBy: 'utilisateur', cascade: ['remove'])]
    private Collection $partageAnimals;

    public function __construct()
    {
        $this->animals = new ArrayCollection();
        $this->evenements = new ArrayCollection();
        $this->partageAnimals = new ArrayCollection();
        $this->dateInscription = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getDateInscription(): ?\DateTimeImmutable
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeImmutable $dateInscription): static
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): static
    {
        $this->photoUrl = $photoUrl;
        return $this;
    }

    /**
     * @return Collection<int, Animal>
     */
    public function getAnimals(): Collection
    {
        return $this->animals;
    }

    public function addAnimal(Animal $animal): static
    {
        if (!$this->animals->contains($animal)) {
            $this->animals->add($animal);
            $animal->setProprietaire($this);
        }
        return $this;
    }

    public function removeAnimal(Animal $animal): static
    {
        if ($this->animals->removeElement($animal)) {
            if ($animal->getProprietaire() === $this) {
                $animal->setProprietaire(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        return $this->evenements;
    }

    public function addEvenement(Evenement $evenement): static
    {
        if (!$this->evenements->contains($evenement)) {
            $this->evenements->add($evenement);
            $evenement->setCreateur($this);
        }
        return $this;
    }

    public function removeEvenement(Evenement $evenement): static
    {
        if ($this->evenements->removeElement($evenement)) {
            if ($evenement->getCreateur() === $this) {
                $evenement->setCreateur(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, PartageAnimal>
     */
    public function getPartageAnimals(): Collection
    {
        return $this->partageAnimals;
    }

    public function addPartageAnimal(PartageAnimal $partageAnimal): static
    {
        if (!$this->partageAnimals->contains($partageAnimal)) {
            $this->partageAnimals->add($partageAnimal);
            $partageAnimal->setUtilisateur($this);
        }
        return $this;
    }

    public function removePartageAnimal(PartageAnimal $partageAnimal): static
    {
        if ($this->partageAnimals->removeElement($partageAnimal)) {
            if ($partageAnimal->getUtilisateur() === $this) {
                $partageAnimal->setUtilisateur(null);
            }
        }
        return $this;
    }

    // ── Reset password token ─────────────────────────────────────────────────

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeImmutable $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }
}
