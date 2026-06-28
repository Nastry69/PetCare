<?php

namespace App\Repository;

use App\Entity\Animal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Requêtes Doctrine pour Animal — gestion des accès propriétaire et partagé.
 * @extends ServiceEntityRepository<Animal>
 */
class AnimalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Animal::class);
    }

    /** @return Animal[] */
    public function findByProprietaire(User $user): array
    {
        return $this->findBy(['proprietaire' => $user], ['id' => 'DESC']);
    }

    /** Returns animals owned by user OR shared with user */
    public function findAccessibleByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.partageAnimals', 'p')
            ->where('a.proprietaire = :user')
            ->orWhere('p.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function isAccessibleByUser(Animal $animal, User $user): bool
    {
        if ($animal->getProprietaire() === $user) {
            return true;
        }

        $partage = $this->getEntityManager()
            ->getRepository(\App\Entity\PartageAnimal::class)
            ->findOneBy(['animal' => $animal, 'utilisateur' => $user]);

        return $partage !== null;
    }
}
