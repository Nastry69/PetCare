<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Commande CLI pour vérifier la configuration SMTP (Brevo ou Mailpit).
 * Usage : docker compose exec app php bin/console app:test-mail destinataire@email.com
 */
#[AsCommand(name: 'app:test-mail', description: 'Envoie un email de test pour vérifier la config Brevo')]
class TestMailCommand extends Command
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM_EMAIL)%')]
        private string $fromEmail,
        #[Autowire('%env(MAILER_FROM_NAME)%')]
        private string $fromName,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('to', InputArgument::REQUIRED, 'Adresse email destinataire');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $to = $input->getArgument('to');

        $io->section("Envoi d'un email de test à $to …");

        try {
            $email = (new Email())
                ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
                ->to($to)
                ->subject('✅ PetCare — Test de configuration Brevo')
                ->html(
                    '<div style="font-family:sans-serif;max-width:480px;margin:auto;padding:32px">'
                    . '<h2 style="color:#1377EC">Configuration Brevo ✅</h2>'
                    . '<p>Si vous recevez cet email, votre configuration SMTP Brevo fonctionne correctement.</p>'
                    . '<hr style="border:none;border-top:1px solid #E5EAF3;margin:24px 0">'
                    . '<p style="color:#64748B;font-size:13px">Envoyé depuis PetCare · ' . date('d/m/Y H:i:s') . '</p>'
                    . '</div>'
                );

            $this->mailer->send($email);
            $io->success("Email envoyé avec succès à $to");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error("Échec de l'envoi : " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
