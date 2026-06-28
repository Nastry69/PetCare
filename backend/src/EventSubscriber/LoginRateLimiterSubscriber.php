<?php

namespace App\EventSubscriber;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Limite les tentatives de connexion à 5 par minute par IP (OWASP A07 — brute force).
 * Utilise le cache Symfony (filesystem par défaut) — aucune nouvelle dépendance requise.
 */
class LoginRateLimiterSubscriber implements EventSubscriberInterface
{
    private const MAX_ATTEMPTS   = 5;
    private const WINDOW_SECONDS = 300; // 5 minutes

    public function __construct(
        #[Autowire(service: 'cache.app')]
        private CacheItemPoolInterface $cache,
        #[Autowire('%kernel.environment%')]
        private string $env,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Priorité 10 : s'exécute avant le routing Symfony (priorité 0)
        return [KernelEvents::REQUEST => ['onKernelRequest', 10]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Désactivé en environnement de test pour ne pas bloquer les tests fonctionnels
        if ($this->env === 'test') {
            return;
        }

        $request = $event->getRequest();

        // N'intercepte que POST /api/auth/login_check
        if ($request->getPathInfo() !== '/api/auth/login_check' || $request->getMethod() !== 'POST') {
            return;
        }

        $ip  = $request->getClientIp() ?? 'unknown';
        // sha1 pour avoir une clé de cache valide (pas de caractères spéciaux)
        $key = 'login_rl_' . sha1($ip);

        $item     = $this->cache->getItem($key);
        $attempts = $item->isHit() ? (int) $item->get() : 0;

        if ($attempts >= self::MAX_ATTEMPTS) {
            // HTTP 429 Too Many Requests + header Retry-After standard
            $event->setResponse(new JsonResponse(
                ['message' => 'Trop de tentatives de connexion. Veuillez réessayer dans 5 minutes.'],
                429,
                ['Retry-After' => self::WINDOW_SECONDS]
            ));

            return;
        }

        // Incrémente le compteur, expire après WINDOW_SECONDS
        $item->set($attempts + 1)->expiresAfter(self::WINDOW_SECONDS);
        $this->cache->save($item);
    }
}
