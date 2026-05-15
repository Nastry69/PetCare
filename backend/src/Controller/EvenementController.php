<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Repository\EvenementRepository;
use App\Service\EvenementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/evenements')]
class EvenementController extends AbstractController
{
    #[Route('', name: 'evenement_list', methods: ['GET'])]
    public function index(EvenementRepository $evenementRepository, EvenementService $evenementService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $evenements = $evenementRepository->findAccessibleByUser($user);

        return $this->json(array_map([$evenementService, 'serialize'], $evenements));
    }

    #[Route('/upcoming', name: 'evenement_upcoming', methods: ['GET'])]
    public function upcoming(EvenementRepository $evenementRepository, EvenementService $evenementService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $evenements = $evenementRepository->findUpcomingByUser($user);

        return $this->json(array_map([$evenementService, 'serialize'], $evenements));
    }

    #[Route('/{id}', name: 'evenement_show', methods: ['GET'])]
    public function show(int $id, EvenementRepository $evenementRepository, AnimalRepository $animalRepository, EvenementService $evenementService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $evenement = $evenementRepository->find($id);

        if (!$evenement) {
            return $this->json(['message' => 'Événement introuvable.'], 404);
        }

        $animal = $evenement->getAnimal();
        if (!$animal || !$animalRepository->isAccessibleByUser($animal, $user)) {
            return $this->json(['message' => 'Accès refusé.'], 403);
        }

        return $this->json($evenementService->serialize($evenement));
    }

    #[Route('', name: 'evenement_create', methods: ['POST'])]
    public function create(Request $request, EvenementService $evenementService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $evenement = $evenementService->create($data, $user);
            return $this->json($evenementService->serialize($evenement), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'refusé') ? 403 : 404;
            return $this->json(['message' => $e->getMessage()], $code);
        }
    }

    #[Route('/{id}', name: 'evenement_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request, EvenementService $evenementService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $evenement = $evenementService->update($id, $data, $user);
            return $this->json($evenementService->serialize($evenement));
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'refusé') ? 403 : 404;
            return $this->json(['message' => $e->getMessage()], $code);
        }
    }

    #[Route('/{id}', name: 'evenement_delete', methods: ['DELETE'])]
    public function delete(int $id, EvenementService $evenementService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $evenementService->delete($id, $user);
            return $this->json(['message' => 'Événement supprimé avec succès.']);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'refusé') ? 403 : 404;
            return $this->json(['message' => $e->getMessage()], $code);
        }
    }

}
