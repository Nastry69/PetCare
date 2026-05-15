<?php

namespace App\Repository;

use App\Entity\Animal;
use App\Entity\PartageAnimal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PartageAnimal>
 */
class PartageAnimalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PartageAnimal::class);
    }

    /** @return PartageAnimal[] */
    public function findByUtilisateur(User $user): array
    {
        return $this->findBy(['utilisateur' => $user]);
    }

    /** @return PartageAnimal[] */
    public function findByAnimal(Animal $animal): array
    {
        return $this->findBy(['animal' => $animal]);
    }

    public function findExistingPartage(Animal $animal, User $utilisateur): ?PartageAnimal
    {
        return $this->findOneBy(['animal' => $animal, 'utilisateur' => $utilisateur]);
    }

    public function canUserWriteAnimal(Animal $animal, User $user): bool
    {
        if ($animal->getProprietaire() === $user) {
            return true;
        }

        $partage = $this->findOneBy(['animal' => $animal, 'utilisateur' => $user]);
        return $partage !== null && $partage->getRolePartage() === 'ecriture';
    }
}
