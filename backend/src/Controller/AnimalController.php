<?php

namespace App\Controller;

use App\Repository\AnimalRepository;
use App\Service\AnimalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class AnimalController extends AbstractController
{
    #[Route('/api/animals', name: 'animal_list', methods: ['GET'])]
    public function index(AnimalRepository $animalRepository): JsonResponse
    {
        $animals = $animalRepository->findAll();

        return $this->json($animals);
    }

    #[Route('/api/animals', name: 'animal_create', methods: ['POST'])]
    public function create(Request $request, AnimalService $animalService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $animal = $animalService->create($data);

            return $this->json($animal, 201);

        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/api/animals/{id}', name: 'animal_update', methods: ['PATCH'])]
    public function update(int $id, Request $request, AnimalService $animalService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $animal = $animalService->update($id, $data);

            return $this->json($animal);

        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}