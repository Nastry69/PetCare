<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Journalise les événements de sécurité dans var/log/security.log (OWASP A09).
 * Format : une ligne JSON par événement — compatible avec jq, ELK, Grafana Loki.
 *
 * Événements tracés :
 *  - login_failed          : POST /api/auth/login_check → 401
 *  - jwt_invalid           : toute route /api/* → 401 (token absent, expiré, mal formé)
 *  - access_denied         : toute route /api/* → 403 (utilisateur authentifié mais non autorisé)
 *  - login_rate_limited    : POST /api/auth/login_check → 429 (bloqué par LoginRateLimiterSubscriber)
 */
class SecurityAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%kernel.logs_dir%')]
        private string $logsDir,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Priorité négative : s'exécute après que la réponse a été construite
        return [KernelEvents::RESPONSE => ['onKernelResponse', -10]];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // N'enregistre que la requête principale (pas les sous-requêtes Symfony)
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $status  = $event->getResponse()->getStatusCode();
        $path    = $request->getPathInfo();
        $method  = $request->getMethod();
        $ip      = $request->getClientIp() ?? 'unknown';

        $entry = null;

        if ($path === '/api/auth/login_check' && $method === 'POST') {
            if ($status === 401) {
                // Tentative de connexion échouée (mauvais email ou mot de passe)
                $body  = json_decode($request->getContent(), true) ?? [];
                $email = $body['username'] ?? $body['email'] ?? 'unknown';

                $entry = [
                    'event'      => 'login_failed',
                    'email'      => $email,
                    'ip'         => $ip,
                    'user_agent' => $request->headers->get('User-Agent', 'unknown'),
                ];
            } elseif ($status === 429) {
                // Bloqué par le rate limiter
                $entry = [
                    'event' => 'login_rate_limited',
                    'ip'    => $ip,
                ];
            }
        } elseif ($status === 401 && str_starts_with($path, '/api/')) {
            // JWT absent, expiré ou avec signature invalide
            $entry = [
                'event'  => 'jwt_invalid',
                'path'   => $path,
                'method' => $method,
                'ip'     => $ip,
            ];
        } elseif ($status === 403 && str_starts_with($path, '/api/')) {
            // Utilisateur authentifié mais sans les droits nécessaires
            $entry = [
                'event'  => 'access_denied',
                'path'   => $path,
                'method' => $method,
                'ip'     => $ip,
            ];
        }

        if ($entry !== null) {
            $entry['at'] = date('c'); // ISO 8601 avec timezone

            $logFile = $this->logsDir . '/security.log';
            // FILE_APPEND : ajoute sans écraser | LOCK_EX : verrou exclusif contre les écritures concurrentes
            file_put_contents($logFile, json_encode($entry) . "\n", FILE_APPEND | LOCK_EX);
        }
    }
}
