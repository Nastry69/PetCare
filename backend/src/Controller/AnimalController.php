<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Service\AnimalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/animals')]
class AnimalController extends AbstractController
{
    private const MAX_PHOTO_SIZE = 5 * 1024 * 1024;

    #[Route('', name: 'animal_list', methods: ['GET'])]
    public function index(AnimalRepository $animalRepository, AnimalService $animalService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $animals = $animalRepository->findAccessibleByUser($user);

        return $this->json(array_map([$animalService, 'serialize'], $animals));
    }

    #[Route('/{id}', name: 'animal_show', methods: ['GET'])]
    public function show(int $id, AnimalRepository $animalRepository, AnimalService $animalService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $animal = $animalRepository->find($id);

        if (!$animal) {
            return $this->json(['message' => 'Animal introuvable.'], 404);
        }

        if (!$animalRepository->isAccessibleByUser($animal, $user)) {
            return $this->json(['message' => 'Accès refusé.'], 403);
        }

        return $this->json($animalService->serialize($animal));
    }

    #[Route('', name: 'animal_create', methods: ['POST'])]
    public function create(Request $request, AnimalService $animalService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $animal = $animalService->create($data, $user);
            return $this->json($animalService->serialize($animal), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'animal_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request, AnimalService $animalService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $animal = $animalService->update($id, $data, $user);
            return $this->json($animalService->serialize($animal));
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'refusé') ? 403 : 404;
            return $this->json(['message' => $e->getMessage()], $code);
        }
    }

    #[Route('/{id}/photo', name: 'animal_upload_photo', methods: ['POST'])]
    public function uploadPhoto(
        int $id,
        Request $request,
        AnimalRepository $animalRepository,
        AnimalService $animalService,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $animal = $animalRepository->find($id);

        if (!$animal) {
            return $this->json(['message' => 'Animal introuvable.'], Response::HTTP_NOT_FOUND);
        }

        if ($animal->getProprietaire() !== $user) {
            return $this->json(['message' => 'Accès refusé.'], Response::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('photo');
        if (!$file instanceof UploadedFile) {
            return $this->json(['message' => 'Aucune photo reçue.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$file->isValid()) {
            return $this->json([
                'message' => 'La photo est invalide ou trop volumineuse. Taille maximum : 5 Mo.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!str_starts_with((string) $file->getMimeType(), 'image/')) {
            return $this->json(['message' => 'Le fichier doit être une image.'], Response::HTTP_BAD_REQUEST);
        }

        if ($file->getSize() !== false && $file->getSize() > self::MAX_PHOTO_SIZE) {
            return $this->json(['message' => 'La photo ne doit pas dépasser 5 Mo.'], Response::HTTP_BAD_REQUEST);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/animals';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $extension = $file->guessExtension() ?: 'jpg';
        $filename = sprintf('animal-%d-%s.%s', $animal->getId(), bin2hex(random_bytes(8)), $extension);
        $file->move($uploadDir, $filename);

        $oldPhotoUrl = $animal->getPhotoUrl();
        $animal->setPhotoUrl($request->getSchemeAndHttpHost() . '/uploads/animals/' . $filename);
        $em->flush();

        if ($oldPhotoUrl && str_starts_with($oldPhotoUrl, $request->getSchemeAndHttpHost() . '/uploads/animals/')) {
            $oldFilename = basename(parse_url($oldPhotoUrl, PHP_URL_PATH) ?: '');
            $oldPath = $uploadDir . '/' . $oldFilename;
            if ($oldFilename && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        return $this->json($animalService->serialize($animal));
    }

    #[Route('/{id}', name: 'animal_delete', methods: ['DELETE'])]
    public function delete(int $id, AnimalService $animalService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $animalService->delete($id, $user);
            return $this->json(['message' => 'Animal supprimé avec succès.']);
        } catch (\RuntimeException $e) {
            $code = str_contains($e->getMessage(), 'refusé') ? 403 : 404;
            return $this->json(['message' => $e->getMessage()], $code);
        }
    }
}
