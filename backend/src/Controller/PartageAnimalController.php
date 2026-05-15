<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Repository\PartageAnimalRepository;
use App\Repository\UserRepository;
use App\Service\PartageAnimalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/partages')]
class PartageAnimalController extends AbstractController
{
    #[Route('', name: 'partage_list', methods: ['GET'])]
    public function index(PartageAnimalRepository $partageAnimalRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $partages = $partageAnimalRepository->findByUtilisateur($user);

        return $this->json(array_map([$this, 'serialize'], $partages));
    }

    #[Route('/animal/{animalId}', name: 'partage_list_by_animal', methods: ['GET'])]
    public function listByAnimal(
        int $animalId,
        AnimalRepository $animalRepository,
        PartageAnimalRepository $partageAnimalRepository
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $animal = $animalRepository->find($animalId);

        if (!$animal) {
            return $this->json(['message' => 'Animal introuvable.'], 404);
        }

        if ($animal->getProprietaire() !== $user) {
            return $this->json(['message' => 'Accès refusé. Seul le propriétaire peut voir les partages.'], 403);
        }

        $partages = $partageAnimalRepository->findByAnimal($animal);

        return $this->json(array_map([$this, 'serialize'], $partages));
    }

    #[Route('', name: 'partage_create', methods: ['POST'])]
    public function create(
        Request $request,
        AnimalRepository $animalRepository,
        UserRepository $userRepository,
        PartageAnimalRepository $partageAnimalRepository,
        PartageAnimalService $partageAnimalService
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        if (!isset($data['animal_id'], $data['email'], $data['rolePartage'])) {
            return $this->json(['message' => 'Les champs animal_id, email et rolePartage sont obligatoires.'], 400);
        }

        $animal = $animalRepository->find($data['animal_id']);
        if (!$animal) {
            return $this->json(['message' => 'Animal introuvable.'], 404);
        }

        if ($animal->getProprietaire() !== $user) {
            return $this->json(['message' => 'Accès refusé. Seul le propriétaire peut partager cet animal.'], 403);
        }

        $invitedUser = $userRepository->findByEmail($data['email']);
        if (!$invitedUser) {
            return $this->json(['message' => 'Aucun compte trouvé avec cet email.'], 404);
        }

        if ($invitedUser === $user) {
            return $this->json(['message' => 'Vous ne pouvez pas partager un animal avec vous-même.'], 400);
        }

        if (!in_array($data['rolePartage'], ['lecture', 'ecriture'], true)) {
            return $this->json(['message' => 'Le rôle doit être "lecture" ou "ecriture".'], 400);
        }

        try {
            $partage = $partageAnimalService->create([
                'animal_id' => $data['animal_id'],
                'utilisateur_id' => $invitedUser->getId(),
                'rolePartage' => $data['rolePartage'],
            ]);
            return $this->json($this->serialize($partage), 201);
        } catch (\RuntimeException $e) {
            return $this->json(['message' => $e->getMessage()], 409);
        }
    }

    #[Route('/{id}', name: 'partage_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        PartageAnimalRepository $partageAnimalRepository,
        PartageAnimalService $partageAnimalService
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $partage = $partageAnimalRepository->find($id);

        if (!$partage) {
            return $this->json(['message' => 'Partage introuvable.'], 404);
        }

        if ($partage->getAnimal()->getProprietaire() !== $user) {
            return $this->json(['message' => 'Accès refusé.'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['rolePartage']) && !in_array($data['rolePartage'], ['lecture', 'ecriture'], true)) {
            return $this->json(['message' => 'Le rôle doit être "lecture" ou "ecriture".'], 400);
        }

        try {
            $partage = $partageAnimalService->update($id, $data);
            return $this->json($this->serialize($partage));
        } catch (\RuntimeException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'partage_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        PartageAnimalRepository $partageAnimalRepository,
        PartageAnimalService $partageAnimalService
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $partage = $partageAnimalRepository->find($id);

        if (!$partage) {
            return $this->json(['message' => 'Partage introuvable.'], 404);
        }

        if ($partage->getAnimal()->getProprietaire() !== $user) {
            return $this->json(['message' => 'Accès refusé.'], 403);
        }

        try {
            $partageAnimalService->delete($id);
            return $this->json(['message' => 'Partage supprimé avec succès.']);
        } catch (\RuntimeException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    private function serialize(\App\Entity\PartageAnimal $partage): array
    {
        return [
            'id' => $partage->getId(),
            'rolePartage' => $partage->getRolePartage(),
            'dateInvitation' => $partage->getDateInvitation()?->format('Y-m-d H:i:s'),
            'animal' => [
                'id' => $partage->getAnimal()?->getId(),
                'nom' => $partage->getAnimal()?->getNom(),
                'espece' => $partage->getAnimal()?->getEspece(),
            ],
            'utilisateur' => [
                'id' => $partage->getUtilisateur()?->getId(),
                'prenom' => $partage->getUtilisateur()?->getPrenom(),
                'nom' => $partage->getUtilisateur()?->getNom(),
                'email' => $partage->getUtilisateur()?->getEmail(),
            ],
        ];
    }
}
