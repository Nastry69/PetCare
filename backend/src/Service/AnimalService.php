<?php

namespace App\Service;

use App\Entity\Animal;
use App\Repository\AnimalRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class AnimalService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private AnimalRepository $animalRepository
    ) {
    }

    public function create(array $data): Animal
    {
        if (
            !isset($data['nom']) ||
            !isset($data['espece']) ||
            !isset($data['proprietaire_id'])
        ) {
            throw new \InvalidArgumentException('Les champs nom, espece et proprietaire_id sont obligatoires.');
        }

        $proprietaire = $this->userRepository->find($data['proprietaire_id']);

        if (!$proprietaire) {
            throw new \RuntimeException('Utilisateur propriétaire introuvable.');
        }

        $animal = new Animal();
        $animal->setNom($data['nom']);
        $animal->setEspece($data['espece']);
        $animal->setRace($data['race'] ?? null);
        $animal->setPhotoUrl($data['photoUrl'] ?? null);
        $animal->setProprietaire($proprietaire);

        if (!empty($data['dateNaissance'])) {
            $animal->setDateNaissance(new \DateTime($data['dateNaissance']));
        }

        if (!empty($data['sexe'])) {
            $animal->setSexe($data['sexe']);
        }

        $this->em->persist($animal);
        $this->em->flush();

        return $animal;
    }

    public function update(int $id, array $data): Animal
    {
        $animal = $this->animalRepository->find($id);

        if (!$animal) {
            throw new \RuntimeException('Animal introuvable.');
        }

        if (isset($data['nom'])) {
            $animal->setNom($data['nom']);
        }

        if (isset($data['espece'])) {
            $animal->setEspece($data['espece']);
        }

        if (array_key_exists('race', $data)) {
            $animal->setRace($data['race']);
        }

        if (array_key_exists('photoUrl', $data)) {
            $animal->setPhotoUrl($data['photoUrl']);
        }

        if (!empty($data['dateNaissance'])) {
            $animal->setDateNaissance(new \DateTime($data['dateNaissance']));
        }

        if (isset($data['sexe'])) {
            $animal->setSexe($data['sexe']);
        }

        if (isset($data['proprietaire_id'])) {
            $proprietaire = $this->userRepository->find($data['proprietaire_id']);

            if (!$proprietaire) {
                throw new \RuntimeException('Nouveau propriétaire introuvable.');
            }

            $animal->setProprietaire($proprietaire);
        }

        $this->em->flush();

        return $animal;
    }
}