<?php

namespace App\Controller;


use App\Repository\EvenementRepository;
use App\Service\EvenementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class EvenementController extends AbstractController
{
    #[Route('/api/evenements', name: 'evenement_list', methods: ['GET'])]
    public function index(EvenementRepository $evenementRepository): JsonResponse
    {
        $evenements = $evenementRepository->findAll();

        return $this->json($evenements);
    }

    #[Route('/api/evenements', name: 'evenement_create', methods: ['POST'])]
    public function create(Request $request, EvenementService $evenementService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $evenement = $evenementService->create($data);

            return $this->json($evenement, 201);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/api/evenements/{id}', name: 'evenement_update', methods: ['PATCH'])]
    public function update(int $id, Request $request, EvenementService $evenementService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $evenement = $evenementService->update($id, $data);

            return $this->json($evenement);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
