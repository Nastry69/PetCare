<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Repository\EvenementRepository;
use App\Repository\PartageAnimalRepository;
use App\Repository\TypeEvenementRepository;
use Doctrine\ORM\EntityManagerInterface;

class EvenementService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EvenementRepository $evenementRepository,
        private AnimalRepository $animalRepository,
        private TypeEvenementRepository $typeEvenementRepository,
        private PartageAnimalRepository $partageAnimalRepository
    ) {
    }

    public function create(array $data, User $createur): Evenement
    {
        if (!isset($data['animal_id'], $data['type_evenement_id'], $data['dateHeureEvenement'])) {
            throw new \InvalidArgumentException(
                'Les champs animal_id, type_evenement_id et dateHeureEvenement sont obligatoires.'
            );
        }

        $animal = $this->animalRepository->find($data['animal_id']);
        if (!$animal) {
            throw new \RuntimeException('Animal introuvable.');
        }

        if (!$this->partageAnimalRepository->canUserWriteAnimal($animal, $createur)) {
            throw new \RuntimeException('Accès refusé à cet animal.');
        }

        $typeEvenement = $this->typeEvenementRepository->find($data['type_evenement_id']);
        if (!$typeEvenement) {
            throw new \RuntimeException('Type d\'événement introuvable.');
        }

        $evenement = new Evenement();
        $evenement->setAnimal($animal);
        $evenement->setTypeEvenement($typeEvenement);
        $evenement->setCreateur($createur);
        $evenement->setDateHeureEvenement(new \DateTime($data['dateHeureEvenement']));
        $evenement->setStatut($data['statut'] ?? 'a_confirmer');
        $evenement->setRappelJoursAvant($data['rappelJoursAvant'] ?? null);
        $evenement->setRappelActif($data['rappelActif'] ?? false);
        $evenement->setCommentaire($data['commentaire'] ?? null);
        $evenement->setDateCreation(new \DateTime());

        $this->em->persist($evenement);
        $this->em->flush();

        return $evenement;
    }

    public function update(int $id, array $data, User $user): Evenement
    {
        $evenement = $this->evenementRepository->find($id);

        if (!$evenement) {
            throw new \RuntimeException('Événement introuvable.');
        }

        if (!$this->partageAnimalRepository->canUserWriteAnimal($evenement->getAnimal(), $user)) {
            throw new \RuntimeException('Accès refusé.');
        }

        if (isset($data['animal_id'])) {
            $animal = $this->animalRepository->find($data['animal_id']);
            if (!$animal) {
                throw new \RuntimeException('Animal introuvable.');
            }
            if (!$this->partageAnimalRepository->canUserWriteAnimal($animal, $user)) {
                throw new \RuntimeException('Accès refusé au nouvel animal.');
            }
            $evenement->setAnimal($animal);
        }

        if (isset($data['type_evenement_id'])) {
            $typeEvenement = $this->typeEvenementRepository->find($data['type_evenement_id']);
            if (!$typeEvenement) {
                throw new \RuntimeException('Type d\'événement introuvable.');
            }
            $evenement->setTypeEvenement($typeEvenement);
        }

        if (!empty($data['dateHeureEvenement'])) {
            $evenement->setDateHeureEvenement(new \DateTime($data['dateHeureEvenement']));
        }
        if (isset($data['statut'])) {
            $evenement->setStatut($data['statut']);
        }
        if (array_key_exists('rappelJoursAvant', $data)) {
            $evenement->setRappelJoursAvant($data['rappelJoursAvant'] !== null ? (int) $data['rappelJoursAvant'] : null);
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

    public function delete(int $id, User $user): void
    {
        $evenement = $this->evenementRepository->find($id);

        if (!$evenement) {
            throw new \RuntimeException('Événement introuvable.');
        }

        if (!$this->partageAnimalRepository->canUserWriteAnimal($evenement->getAnimal(), $user)) {
            throw new \RuntimeException('Accès refusé.');
        }

        $this->em->remove($evenement);
        $this->em->flush();
    }

    public function serialize(Evenement $e): array
    {
        return [
            'id' => $e->getId(),
            'dateHeureEvenement' => $e->getDateHeureEvenement()?->format('Y-m-d H:i:s'),
            'statut' => $e->getStatut(),
            'rappelJoursAvant' => $e->getRappelJoursAvant(),
            'rappelActif' => $e->isRappelActif(),
            'commentaire' => $e->getCommentaire(),
            'dateCreation' => $e->getDateCreation()?->format('Y-m-d H:i:s'),
            'dateModification' => $e->getDateModification()?->format('Y-m-d H:i:s'),
            'animal' => [
                'id' => $e->getAnimal()?->getId(),
                'nom' => $e->getAnimal()?->getNom(),
                'espece' => $e->getAnimal()?->getEspece(),
                'photoUrl' => $e->getAnimal()?->getPhotoUrl(),
            ],
            'typeEvenement' => [
                'id' => $e->getTypeEvenement()?->getId(),
                'libelle' => $e->getTypeEvenement()?->getLibelle(),
                'couleur' => $e->getTypeEvenement()?->getCouleur(),
            ],
            'createurId' => $e->getCreateur()?->getId(),
        ];
    }
}
