<?php

namespace App\MessageHandler;

use App\Message\DailyReminderMessage;
use App\Repository\EvenementRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DailyReminderMessageHandler
{
    public function __construct(
        private EvenementRepository $evenementRepository,
        private MailerService $mailerService,
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(DailyReminderMessage $message): void
    {
        $evenements = $this->evenementRepository->findRappelsDuJour();

        foreach ($evenements as $evenement) {
            try {
                $this->mailerService->sendReminderEmail($evenement);
                // Marquer le rappel comme envoyé pour éviter les doublons
                $evenement->setRappelEnvoye(true);
            } catch (\Throwable) {
                // Continue with other reminders if one fails
            }
        }

        // Flush une seule fois hors de la boucle
        if (!empty($evenements)) {
            $this->em->flush();
        }
    }
}
