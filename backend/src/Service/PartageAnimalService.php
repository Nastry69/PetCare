<?php

namespace App\Service;

use App\Entity\PartageAnimal;
use App\Repository\AnimalRepository;
use App\Repository\PartageAnimalRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class PartageAnimalService
{
    public function __construct(
        private EntityManagerInterface $em,
        private PartageAnimalRepository $partageAnimalRepository,
        private AnimalRepository $animalRepository,
        private UserRepository $userRepository
    ) {
    }

    public function create(array $data): PartageAnimal
    {
        if (
            !isset($data['animal_id']) ||
            !isset($data['utilisateur_id']) ||
            !isset($data['rolePartage'])
        ) {
            throw new \InvalidArgumentException(
                'Les champs animal_id, utilisateur_id et rolePartage sont obligatoires.'
            );
        }

        $animal = $this->animalRepository->find($data['animal_id']);
        $utilisateur = $this->userRepository->find($data['utilisateur_id']);

        if (!$animal) {
            throw new \RuntimeException('Animal introuvable.');
        }

        if (!$utilisateur) {
            throw new \RuntimeException('Utilisateur introuvable.');
        }

        $partageExistant = $this->partageAnimalRepository->findOneBy([
            'animal' => $animal,
            'utilisateur' => $utilisateur,
        ]);

        if ($partageExistant) {
            throw new \RuntimeException('Ce partage existe déjà.');
        }

        $partage = new PartageAnimal();
        $partage->setAnimal($animal);
        $partage->setUtilisateur($utilisateur);
        $partage->setRolePartage($data['rolePartage']);
        $partage->setDateInvitation(new \DateTime());

        $this->em->persist($partage);
        $this->em->flush();

        return $partage;
    }

    public function update(int $id, array $data): PartageAnimal
    {
        $partage = $this->partageAnimalRepository->find($id);

        if (!$partage) {
            throw new \RuntimeException('Partage introuvable.');
        }

        if (isset($data['rolePartage'])) {
            $partage->setRolePartage($data['rolePartage']);
        }

        $this->em->flush();

        return $partage;
    }

    public function delete(int $id): void
    {
        $partage = $this->partageAnimalRepository->find($id);

        if (!$partage) {
            throw new \RuntimeException('Partage introuvable.');
        }

        $this->em->remove($partage);
        $this->em->flush();
    }
}