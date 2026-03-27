<?php

namespace App\Controller;

use App\Repository\PartageAnimalRepository;
use App\Service\PartageAnimalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PartageAnimalController extends AbstractController
{
    #[Route('/api/partages', name: 'partage_list', methods: ['GET'])]
    public function index(PartageAnimalRepository $partageAnimalRepository): JsonResponse
    {
        $partages = $partageAnimalRepository->findAll();

        return $this->json($partages);
    }

    #[Route('/api/partages', name: 'partage_create', methods: ['POST'])]
    public function create(Request $request, PartageAnimalService $partageAnimalService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $partage = $partageAnimalService->create($data);

            return $this->json($partage, 201);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/api/partages/{id}', name: 'partage_update', methods: ['PATCH'])]
    public function update(int $id, Request $request, PartageAnimalService $partageAnimalService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $partage = $partageAnimalService->update($id, $data);

            return $this->json($partage);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/api/partages/{id}', name: 'partage_delete', methods: ['DELETE'])]
    public function delete(int $id, PartageAnimalService $partageAnimalService): JsonResponse
    {
        try {
            $partageAnimalService->delete($id);

            return $this->json([
                'message' => 'Partage supprimé avec succès.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
