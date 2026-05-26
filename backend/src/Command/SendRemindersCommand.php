<?php

namespace App\Command;

use App\Repository\EvenementRepository;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:send-reminders', description: 'Envoie les rappels email du jour (événements dans rappelJoursAvant jours)')]
class SendRemindersCommand extends Command
{
    public function __construct(
        private EvenementRepository $evenementRepository,
        private MailerService $mailerService,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Envoi des rappels du jour…');

        $evenements = $this->evenementRepository->findRappelsDuJour();

        if (empty($evenements)) {
            $io->info('Aucun rappel à envoyer aujourd\'hui.');
            return Command::SUCCESS;
        }

        $sent = 0;
        $errors = 0;

        foreach ($evenements as $evenement) {
            $animal = $evenement->getAnimal();
            $user = $animal?->getProprietaire();
            $label = sprintf(
                '%s pour %s → %s',
                $evenement->getTypeEvenement()?->getLibelle() ?? '?',
                $animal?->getNom() ?? '?',
                $user?->getEmail() ?? '?'
            );

            try {
                $this->mailerService->sendReminderEmail($evenement);
                $evenement->setRappelEnvoye(true);
                $io->writeln(sprintf('  ✓ %s', $label));
                $sent++;
            } catch (\Throwable $e) {
                $io->writeln(sprintf('  ✗ %s — %s', $label, $e->getMessage()));
                $errors++;
            }
        }

        if ($sent > 0) {
            $this->em->flush();
        }

        if ($errors === 0) {
            $io->success(sprintf('%d rappel(s) envoyé(s) avec succès.', $sent));
        } else {
            $io->warning(sprintf('%d envoyé(s), %d échec(s).', $sent, $errors));
        }

        return $errors === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
