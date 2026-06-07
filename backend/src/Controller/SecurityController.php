<?php

namespace App\Controller;

use App\Entity\PartageAnimal;
use App\Entity\User;
use App\Repository\InvitationEnAttenteRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Gestion des comptes et de l'authentification — préfixe /api.
 * Routes publiques : register, login, forgot/reset-password.
 * Routes JWT : /me (GET/PUT/DELETE/export/photo).
 */
#[Route('/api')]
class SecurityController extends AbstractController
{
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        InvitationEnAttenteRepository $invitationRepository,
        JWTTokenManagerInterface $jwtManager,
        MailerService $mailerService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['nom'], $data['prenom'])) {
            return $this->json(['message' => 'Les champs email, password, nom et prenom sont obligatoires.'], 400);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Email invalide.'], 400);
        }

        if (strlen($data['password']) < 8) {
            return $this->json(['message' => 'Le mot de passe doit contenir au moins 8 caractères.'], 400);
        }

        if ($userRepository->emailExists($data['email'])) {
            return $this->json(['message' => 'Cet email est déjà utilisé.'], 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setNom($data['nom']);
        $user->setPrenom($data['prenom']);
        $user->setPassword($hasher->hashPassword($user, $data['password']));
        $user->setRoles(['ROLE_USER']);

        $em->persist($user);
        $em->flush();

        // Applique les invitations en attente pour cet email
        $invitations = $invitationRepository->findByEmail($data['email']);
        foreach ($invitations as $invitation) {
            if ($invitation->isExpired()) {
                $em->remove($invitation);
                continue;
            }
            $existant = $em->getRepository(\App\Entity\PartageAnimal::class)->findOneBy([
                'animal' => $invitation->getAnimal(),
                'utilisateur' => $user,
            ]);
            if (!$existant) {
                $partage = new PartageAnimal();
                $partage->setAnimal($invitation->getAnimal());
                $partage->setUtilisateur($user);
                $partage->setRolePartage($invitation->getRolePartage());
                $partage->setDateInvitation(new \DateTime());
                $em->persist($partage);
            }
            $em->remove($invitation);
        }
        $em->flush();

        try {
            $mailerService->sendWelcomeEmail($user);
        } catch (\Throwable) {}

        $token = $jwtManager->create($user);

        return $this->json([
            'token' => $token,
            'user' => $this->serializeUser($user),
        ], 201);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Non authentifié.'], 401);
        }

        return $this->json($this->serializeUser($user));
    }

    #[Route('/me', name: 'api_me_update', methods: ['PUT', 'PATCH'])]
    public function updateMe(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Non authentifié.'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $user->setNom($data['nom']);
        }
        if (isset($data['prenom'])) {
            $user->setPrenom($data['prenom']);
        }
        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            if ($userRepository->emailExists($data['email'])) {
                return $this->json(['message' => 'Cet email est déjà utilisé.'], 409);
            }
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json(['message' => 'Email invalide.'], 400);
            }
            $user->setEmail($data['email']);
        }
        if (isset($data['photoUrl'])) {
            $user->setPhotoUrl($data['photoUrl']);
        }
        if (isset($data['password'])) {
            if (strlen($data['password']) < 8) {
                return $this->json(['message' => 'Le mot de passe doit contenir au moins 8 caractères.'], 400);
            }
            $user->setPassword($hasher->hashPassword($user, $data['password']));
        }

        $em->flush();

        return $this->json($this->serializeUser($user));
    }

    #[Route('/me', name: 'api_me_delete', methods: ['DELETE'])]
    public function deleteMe(EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Non authentifié.'], 401);
        }

        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'Compte supprimé avec succès.'], 200);
    }

    #[Route('/me/export', name: 'api_me_export', methods: ['GET'])]
    public function exportMe(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Non authentifié.'], 401);
        }

        $animals = [];
        foreach ($user->getAnimals() as $animal) {
            $animals[] = [
                'id' => $animal->getId(),
                'nom' => $animal->getNom(),
                'espece' => $animal->getEspece(),
                'race' => $animal->getRace(),
                'dateNaissance' => $animal->getDateNaissance()?->format('Y-m-d'),
                'sexe' => $animal->getSexe(),
                'photoUrl' => $animal->getPhotoUrl(),
            ];
        }

        $evenements = [];
        foreach ($user->getEvenements() as $evenement) {
            $evenements[] = [
                'id' => $evenement->getId(),
                'dateHeureEvenement' => $evenement->getDateHeureEvenement()?->format('Y-m-d H:i:s'),
                'statut' => $evenement->getStatut(),
                'commentaire' => $evenement->getCommentaire(),
                'animal' => $evenement->getAnimal()?->getNom(),
                'typeEvenement' => $evenement->getTypeEvenement()?->getLibelle(),
            ];
        }

        return $this->json([
            'user' => $this->serializeUser($user),
            'animals' => $animals,
            'evenements' => $evenements,
            'exportedAt' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Étape 1 — Demande de réinitialisation.
     * Génère un token sécurisé, le sauvegarde et envoie l'email.
     * Retourne TOUJOURS 200 pour éviter l'énumération des emails (OWASP A07).
     */
    #[Route('/auth/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerService $mailerService
    ): JsonResponse {
        $data  = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');

        $user = $userRepository->findByEmail($email);

        if ($user) {
            $token     = bin2hex(random_bytes(32)); // 64 caractères hex — cryptographiquement sûr
            $expiresAt = new \DateTimeImmutable('+1 hour');

            $user->setResetToken($token);
            $user->setResetTokenExpiresAt($expiresAt);
            $em->flush();

            try {
                $mailerService->sendPasswordResetEmail($user, $token);
            } catch (\Throwable) {
                // L'envoi de l'email ne bloque pas la réponse
            }
        }

        // Réponse identique que l'email existe ou non (anti-énumération)
        return $this->json([
            'message' => 'Si un compte est associé à cet email, un lien de réinitialisation vous a été envoyé.',
        ]);
    }

    /**
     * Étape 2 — Réinitialisation via token.
     * Valide le token (existence + non-expiration), met à jour le mot de passe, invalide le token.
     */
    #[Route('/auth/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $data        = json_decode($request->getContent(), true);
        $token       = trim($data['token'] ?? '');
        $newPassword = $data['newPassword'] ?? '';

        if (empty($token)) {
            return $this->json(['message' => 'Token manquant.'], 400);
        }

        if (strlen($newPassword) < 8) {
            return $this->json(['message' => 'Le mot de passe doit contenir au moins 8 caractères.'], 400);
        }

        $user = $userRepository->findByResetToken($token);

        if (!$user) {
            return $this->json(['message' => 'Ce lien est invalide ou a expiré. Veuillez faire une nouvelle demande.'], 400);
        }

        $user->setPassword($hasher->hashPassword($user, $newPassword));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $em->flush();

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }

    #[Route('/auth/login_check', name: 'api_login_check', methods: ['POST'])]
    public function loginCheck(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['username'] ?? $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $user = $userRepository->findByEmail($email);
        if (!$user || !$hasher->isPasswordValid($user, $password)) {
            return $this->json(['message' => 'Identifiants invalides.'], 401);
        }

        $token = $jwtManager->create($user);
        return $this->json(['token' => $token]);
    }

    #[Route('/me/photo', name: 'api_me_upload_photo', methods: ['POST'])]
    public function uploadMePhoto(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $file = $request->files->get('photo');
        if (!$file instanceof UploadedFile) {
            return $this->json(['message' => 'Aucune photo reçue.'], 400);
        }

        if (!$file->isValid() || !str_starts_with((string) $file->getMimeType(), 'image/')) {
            return $this->json(['message' => 'Fichier invalide. Envoyez une image (max 5 Mo).'], 400);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json(['message' => 'La photo ne doit pas dépasser 5 Mo.'], 400);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/users';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $extension = $file->guessExtension() ?: 'jpg';
        $filename = sprintf('user-%d-%s.%s', $user->getId(), bin2hex(random_bytes(8)), $extension);
        $file->move($uploadDir, $filename);

        $oldPhotoUrl = $user->getPhotoUrl();
        $user->setPhotoUrl($request->getSchemeAndHttpHost() . '/uploads/users/' . $filename);
        $em->flush();

        if ($oldPhotoUrl && str_contains($oldPhotoUrl, '/uploads/users/')) {
            $oldFilename = basename(parse_url($oldPhotoUrl, PHP_URL_PATH) ?: '');
            $oldPath = $uploadDir . '/' . $oldFilename;
            if ($oldFilename && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        return $this->json($this->serializeUser($user));
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'photoUrl' => $user->getPhotoUrl(),
            'dateInscription' => $user->getDateInscription()?->format('Y-m-d'),
        ];
    }
}
