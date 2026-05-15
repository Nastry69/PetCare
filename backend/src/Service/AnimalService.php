<?php

namespace App\Service;

use App\Entity\Animal;
use App\Entity\User;
use App\Repository\AnimalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AnimalService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AnimalRepository $animalRepository,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir
    ) {
    }

    public function create(array $data, User $proprietaire): Animal
    {
        if (!isset($data['nom']) || !isset($data['espece'])) {
            throw new \InvalidArgumentException('Les champs nom et espece sont obligatoires.');
        }

        $animal = new Animal();
        $animal->setNom($data['nom']);
        $animal->setEspece($data['espece']);
        $animal->setRace($data['race'] ?? null);
        $animal->setPhotoUrl($data['photoUrl'] ?? null);
        $animal->setSexe($data['sexe'] ?? null);
        $animal->setProprietaire($proprietaire);

        if (!empty($data['dateNaissance'])) {
            $animal->setDateNaissance(new \DateTime($data['dateNaissance']));
        }

        $this->em->persist($animal);
        $this->em->flush();

        return $animal;
    }

    public function update(int $id, array $data, User $user): Animal
    {
        $animal = $this->animalRepository->find($id);

        if (!$animal) {
            throw new \RuntimeException('Animal introuvable.');
        }

        if ($animal->getProprietaire() !== $user) {
            throw new \RuntimeException('Accès refusé.');
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
        if (array_key_exists('sexe', $data)) {
            $animal->setSexe($data['sexe']);
        }
        if (!empty($data['dateNaissance'])) {
            $animal->setDateNaissance(new \DateTime($data['dateNaissance']));
        }

        $this->em->flush();

        return $animal;
    }

    public function delete(int $id, User $user): void
    {
        $animal = $this->animalRepository->find($id);

        if (!$animal) {
            throw new \RuntimeException('Animal introuvable.');
        }

        if ($animal->getProprietaire() !== $user) {
            throw new \RuntimeException('Accès refusé. Seul le propriétaire peut supprimer cet animal.');
        }

        $this->deleteLocalPhoto($animal->getPhotoUrl());
        $this->em->remove($animal);
        $this->em->flush();
    }

    public function serialize(Animal $animal): array
    {
        return [
            'id' => $animal->getId(),
            'nom' => $animal->getNom(),
            'espece' => $animal->getEspece(),
            'race' => $animal->getRace(),
            'dateNaissance' => $animal->getDateNaissance()?->format('Y-m-d'),
            'sexe' => $animal->getSexe(),
            'photoUrl' => $animal->getPhotoUrl(),
            'proprietaireId' => $animal->getProprietaire()?->getId(),
        ];
    }

    private function deleteLocalPhoto(?string $photoUrl): void
    {
        if (!$photoUrl) {
            return;
        }

        $path = parse_url($photoUrl, PHP_URL_PATH);
        if (!$path || !str_starts_with($path, '/uploads/animals/')) {
            return;
        }

        $filePath = $this->projectDir . '/public' . $path;
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
}
