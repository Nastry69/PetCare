<?php

namespace App\Controller;

use App\Entity\PartageAnimal;
use App\Entity\User;
use App\Repository\InvitationEnAttenteRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Connexion OAuth2 Google — redirige vers Google puis génère un JWT au retour.
 * Un nouveau compte est créé automatiquement si l'email Google est inconnu.
 */
class GoogleAuthController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        #[Autowire('%frontend_url%')]
        private readonly string $frontendUrl,
    ) {
    }

    #[Route('/api/auth/google', name: 'api_auth_google', methods: ['GET'])]
    public function connectGoogle(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile'], []);
    }

    #[Route('/api/auth/google/callback', name: 'api_auth_google_callback', methods: ['GET'])]
    public function googleCallback(
        ClientRegistry $clientRegistry,
        UserRepository $userRepository,
        InvitationEnAttenteRepository $invitationRepository,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $hasher,
        MailerService $mailerService
    ): RedirectResponse {
        try {
            /** @var GoogleUser $googleUser */
            $googleUser = $clientRegistry->getClient('google')->fetchUser();

            $email = $googleUser->getEmail();
            if (!$email) {
                return new RedirectResponse($this->frontendUrl . '/login?error=no_email');
            }

            $user = $userRepository->findByEmail($email);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setNom($googleUser->getLastName() ?? $googleUser->getName() ?? 'Utilisateur');
                $user->setPrenom($googleUser->getFirstName() ?? '');
                $user->setRoles(['ROLE_USER']);
                $user->setPhotoUrl($googleUser->getAvatar());
                $user->setPassword($hasher->hashPassword($user, bin2hex(random_bytes(16))));

                $em->persist($user);
                $em->flush();

                // Applique les invitations en attente pour cet email
                $invitations = $invitationRepository->findByEmail($email);
                foreach ($invitations as $invitation) {
                    if ($invitation->isExpired()) {
                        $em->remove($invitation);
                        continue;
                    }
                    $existant = $em->getRepository(PartageAnimal::class)->findOneBy([
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

            } elseif ($googleUser->getAvatar() && $user->getPhotoUrl() !== $googleUser->getAvatar()) {
                $user->setPhotoUrl($googleUser->getAvatar());
                $em->flush();
            }

            $token = $jwtManager->create($user);

            return new RedirectResponse($this->frontendUrl . '/auth/callback#token=' . $token);

        } catch (\Throwable $exception) {
            $query = ['error' => 'oauth_failed'];

            if ($this->debug) {
                $query['reason'] = $exception->getMessage();
            }

            return new RedirectResponse($this->frontendUrl . '/login?' . http_build_query($query));
        }
    }
}
