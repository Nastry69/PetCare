<?php

namespace App\Controller;

use App\Message\DailyReminderMessage;
use App\MessageHandler\DailyReminderMessageHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/api/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/trigger-reminders', name: 'trigger_reminders', methods: ['GET'])]
    public function triggerReminders(
        Request $request,
        DailyReminderMessageHandler $handler
    ): JsonResponse {
        $secret = $this->getParameter('kernel.secret');
        $headerSecret = $request->headers->get('X-Reminder-Secret', '');

        if (!hash_equals($secret, $headerSecret)) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $handler->__invoke(new DailyReminderMessage());
        return $this->json(['status' => 'ok']);
    }
}