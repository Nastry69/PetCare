<?php

namespace App\Entity;

use App\Repository\AnimalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnimalRepository::class)]
class Animal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $espece = null;

    #[ORM\Column(length: 100)]
    private ?string $race = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateNaissance = null;

    #[ORM\Column(length: 20)]
    private ?string $sexe = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\ManyToOne(inversedBy: 'animals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $proprietaire = null;

    /**
     * @var Collection<int, Evenement>
     */
    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'animal')]
    private Collection $evenements;

    /**
     * @var Collection<int, PartageAnimal>
     */
    #[ORM\OneToMany(targetEntity: PartageAnimal::class, mappedBy: 'animal')]
    private Collection $partageAnimals;

    public function __construct()
    {
        $this->evenements = new ArrayCollection();
        $this->partageAnimals = new ArrayCollection();
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

    public function getEspece(): ?string
    {
        return $this->espece;
    }

    public function setEspece(string $espece): static
    {
        $this->espece = $espece;

        return $this;
    }

    public function getRace(): ?string
    {
        return $this->race;
    }

    public function setRace(string $race): static
    {
        $this->race = $race;

        return $this;
    }

    public function getDateNaissance(): ?\DateTime
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTime $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
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

    public function getProprietaire(): ?User
    {
        return $this->proprietaire;
    }

    public function setProprietaire(?User $proprietaire): static
    {
        $this->proprietaire = $proprietaire;

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
            $evenement->setAnimal($this);
        }

        return $this;
    }

    public function removeEvenement(Evenement $evenement): static
    {
        if ($this->evenements->removeElement($evenement)) {
            // set the owning side to null (unless already changed)
            if ($evenement->getAnimal() === $this) {
                $evenement->setAnimal(null);
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
            $partageAnimal->setAnimal($this);
        }

        return $this;
    }

    public function removePartageAnimal(PartageAnimal $partageAnimal): static
    {
        if ($this->partageAnimals->removeElement($partageAnimal)) {
            // set the owning side to null (unless already changed)
            if ($partageAnimal->getAnimal() === $this) {
                $partageAnimal->setAnimal(null);
            }
        }

        return $this;
    }
}
