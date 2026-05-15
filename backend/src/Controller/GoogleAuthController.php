<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GoogleUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class GoogleAuthController extends AbstractController
{
    private const FRONTEND_URL = 'http://localhost:5173';

    public function __construct(
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug
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
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
        UserPasswordHasherInterface $hasher
    ): RedirectResponse {
        try {
            /** @var GoogleUser $googleUser */
            $googleUser = $clientRegistry->getClient('google')->fetchUser();

            $email = $googleUser->getEmail();
            if (!$email) {
                return new RedirectResponse(self::FRONTEND_URL . '/login?error=no_email');
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
            } elseif ($googleUser->getAvatar() && $user->getPhotoUrl() !== $googleUser->getAvatar()) {
                $user->setPhotoUrl($googleUser->getAvatar());
                $em->flush();
            }

            $token = $jwtManager->create($user);

            return new RedirectResponse(self::FRONTEND_URL . '/auth/callback#token=' . $token);

        } catch (\Throwable $exception) {
            $query = ['error' => 'oauth_failed'];

            if ($this->debug) {
                $query['reason'] = $exception->getMessage();
            }

            return new RedirectResponse(self::FRONTEND_URL . '/login?' . http_build_query($query));
        }
    }
}
