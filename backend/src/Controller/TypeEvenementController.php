<?php

namespace App\Controller;

use App\Entity\TypeEvenement;
use App\Repository\TypeEvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TypeEvenementController extends AbstractController
{
    #[Route('/api/type-evenement', name: 'type_evenement_list', methods: ['GET'])]
    public function index(TypeEvenementRepository $repository): JsonResponse
    {
        $types = $repository->findAll();

        return $this->json($types);
    }

    #[Route('/api/type-evenement', name: 'type_evenement_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['libelle'])) {
            return $this->json([
                'error' => 'Le champ libelle est obligatoire.'
            ], 400);
        }

        $type = new TypeEvenement();
        $type->setLibelle($data['libelle']);
        $type->setDescription($data['description'] ?? null);
        $type->setCouleur($data['couleur'] ?? null);

        $em->persist($type);
        $em->flush();

        return $this->json($type, 201);
    }

    #[Route('/api/type-evenement/{id}', name: 'type_evenement_update', methods: ['PATCH'])]
    public function update(
        int $id,
        Request $request,
        TypeEvenementRepository $repository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $type = $repository->find($id);

        if (!$type) {
            return $this->json([
                'error' => 'Type d’événement introuvable.'
            ], 404);
        }

        if (isset($data['libelle'])) {
            $type->setLibelle($data['libelle']);
        }

        if (array_key_exists('description', $data)) {
            $type->setDescription($data['description']);
        }

        if (array_key_exists('couleur', $data)) {
            $type->setCouleur($data['couleur']);
        }

        $em->flush();

        return $this->json($type);
    }
}