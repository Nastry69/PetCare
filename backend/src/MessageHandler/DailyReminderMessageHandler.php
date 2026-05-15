<?php

namespace App\MessageHandler;

use App\Message\DailyReminderMessage;
use App\Repository\EvenementRepository;
use App\Service\MailerService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DailyReminderMessageHandler
{
    public function __construct(
        private EvenementRepository $evenementRepository,
        private MailerService $mailerService
    ) {
    }

    public function __invoke(DailyReminderMessage $message): void
    {
        $evenements = $this->evenementRepository->findRappelsDuJour();

        foreach ($evenements as $evenement) {
            try {
                $this->mailerService->sendReminderEmail($evenement);
            } catch (\Throwable) {
                // Continue with other reminders if one fails
            }
        }
    }
}
