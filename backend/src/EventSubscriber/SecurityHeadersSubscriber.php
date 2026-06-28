<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Ajoute les headers de sécurité HTTP sur chaque réponse API (OWASP A05).
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;

        // Empêche le navigateur de deviner le Content-Type — stoppe le MIME sniffing
        $headers->set('X-Content-Type-Options', 'nosniff');

        // Interdit l'affichage dans une <iframe> — protection clickjacking
        $headers->set('X-Frame-Options', 'DENY');

        // Contrôle les informations envoyées dans le header Referer
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Désactive les APIs navigateur non utilisées
        $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');

        // Les réponses API ne doivent pas être mises en cache (contiennent des données privées)
        if (!$headers->has('Cache-Control')) {
            $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        }
    }
}
