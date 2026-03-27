<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Repository\AnimalRepository;
use App\Repository\EvenementRepository;
use App\Repository\TypeEvenementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class EvenementService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EvenementRepository $evenementRepository,
        private AnimalRepository $animalRepository,
        private TypeEvenementRepository $typeEvenementRepository,
        private UserRepository $userRepository
    ) {
    }

    public function create(array $data): Evenement
    {
        if (
            !isset($data['animal_id']) ||
            !isset($data['type_evenement_id']) ||
            !isset($data['createur_id']) ||
            !isset($data['dateHeureEvenement'])
        ) {
            throw new \InvalidArgumentException(
                'Les champs animal_id, type_evenement_id, createur_id et dateHeureEvenement sont obligatoires.'
            );
        }

        $animal = $this->animalRepository->find($data['animal_id']);
        $typeEvenement = $this->typeEvenementRepository->find($data['type_evenement_id']);
        $createur = $this->userRepository->find($data['createur_id']);

        if (!$animal) {
            throw new \RuntimeException('Animal introuvable.');
        }

        if (!$typeEvenement) {
            throw new \RuntimeException('Type d’événement introuvable.');
        }

        if (!$createur) {
            throw new \RuntimeException('Créateur introuvable.');
        }

        $evenement = new Evenement();
        $evenement->setAnimal($animal);
        $evenement->setTypeEvenement($typeEvenement);
        $evenement->setCreateur($createur);
        $evenement->setDateHeureEvenement(new \DateTime($data['dateHeureEvenement']));
        $evenement->setStatut($data['statut'] ?? 'a_confirmer');
        $evenement->setRappelJoursAvant($data['rappelJoursAvant'] ?? 0);
        $evenement->setRappelActif($data['rappelActif'] ?? false);
        $evenement->setCommentaire($data['commentaire'] ?? null);
        $evenement->setDateCreation(new \DateTime());

        if (!empty($data['dateModification'])) {
            $evenement->setDateModification(new \DateTime($data['dateModification']));
        }

        $this->em->persist($evenement);
        $this->em->flush();

        return $evenement;
    }

    public function update(int $id, array $data): Evenement
    {
        $evenement = $this->evenementRepository->find($id);

        if (!$evenement) {
            throw new \RuntimeException('Événement introuvable.');
        }

        if (isset($data['animal_id'])) {
            $animal = $this->animalRepository->find($data['animal_id']);

            if (!$animal) {
                throw new \RuntimeException('Animal introuvable.');
            }

            $evenement->setAnimal($animal);
        }

        if (isset($data['type_evenement_id'])) {
            $typeEvenement = $this->typeEvenementRepository->find($data['type_evenement_id']);

            if (!$typeEvenement) {
                throw new \RuntimeException('Type d’événement introuvable.');
            }

            $evenement->setTypeEvenement($typeEvenement);
        }

        if (isset($data['createur_id'])) {
            $createur = $this->userRepository->find($data['createur_id']);

            if (!$createur) {
                throw new \RuntimeException('Créateur introuvable.');
            }

            $evenement->setCreateur($createur);
        }

        if (!empty($data['dateHeureEvenement'])) {
            $evenement->setDateHeureEvenement(new \DateTime($data['dateHeureEvenement']));
        }

        if (isset($data['statut'])) {
            $evenement->setStatut($data['statut']);
        }

        if (isset($data['rappelJoursAvant'])) {
            $evenement->setRappelJoursAvant((int) $data['rappelJoursAvant']);
        }

        if (isset($data['rappelActif'])) {
            $evenement->setRappelActif((bool) $data['rappelActif']);
        }

        if (array_key_exists('commentaire', $data)) {
            $evenement->setCommentaire($data['commentaire']);
        }

        $evenement->setDateModification(new \DateTime());

        $this->em->flush();

        return $evenement;
    }
}