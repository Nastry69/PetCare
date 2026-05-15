<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail,
        private string $fromName
    ) {
    }

    public function sendWelcomeEmail(User $user): void
    {
        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($user->getEmail())
            ->subject('Bienvenue sur PetCare !')
            ->html(sprintf(
                '<h1>Bonjour %s !</h1>
                <p>Bienvenue sur PetCare, votre gestionnaire d\'animaux de compagnie.</p>
                <p>Vous pouvez dès maintenant ajouter vos animaux et suivre leurs soins.</p>',
                htmlspecialchars($user->getPrenom())
            ));

        $this->mailer->send($email);
    }

    public function sendReminderEmail(Evenement $evenement): void
    {
        $user = $evenement->getAnimal()?->getProprietaire();
        if (!$user) {
            return;
        }

        $animal = $evenement->getAnimal();
        $type = $evenement->getTypeEvenement();
        $joursAvant = $evenement->getRappelJoursAvant() ?? 1;
        $date = $evenement->getDateHeureEvenement()?->format('d/m/Y');
        $heure = $evenement->getDateHeureEvenement()?->format('H:i');
        $delai = $joursAvant === 1 ? 'demain' : "dans $joursAvant jours";
        $commentaireRow = $evenement->getCommentaire()
            ? sprintf('<tr><td style="padding:6px 0;color:#64748B;font-size:13px">Note</td><td style="padding:6px 0;font-size:13px;font-weight:600;color:#0F172A">%s</td></tr>', htmlspecialchars($evenement->getCommentaire()))
            : '';

        $html = sprintf(
            '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#F1F5F9;font-family:sans-serif">
            <div style="max-width:520px;margin:40px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)">
                <div style="background:#1377EC;padding:28px 32px">
                    <p style="margin:0;color:rgba(255,255,255,.75);font-size:12px;font-weight:600;letter-spacing:.08em;text-transform:uppercase">PetCare · Rappel</p>
                    <h1 style="margin:8px 0 0;color:#fff;font-size:22px;font-weight:700">%s pour %s</h1>
                    <p style="margin:6px 0 0;color:rgba(255,255,255,.8);font-size:14px">Prévu %s</p>
                </div>
                <div style="padding:28px 32px">
                    <p style="margin:0 0 20px;color:#334155;font-size:14px">Bonjour <strong>%s</strong>,</p>
                    <p style="margin:0 0 20px;color:#334155;font-size:14px">Voici un rappel pour l\'événement suivant :</p>
                    <table style="width:100%%;border-collapse:collapse;background:#F8FAFC;border-radius:10px;padding:4px 16px;display:block">
                        <tr><td style="padding:10px 0 6px;color:#64748B;font-size:13px;width:120px">Animal</td><td style="padding:10px 0 6px;font-size:13px;font-weight:600;color:#0F172A">%s</td></tr>
                        <tr><td style="padding:6px 0;color:#64748B;font-size:13px">Type</td><td style="padding:6px 0;font-size:13px;font-weight:600;color:#0F172A">%s</td></tr>
                        <tr><td style="padding:6px 0;color:#64748B;font-size:13px">Date</td><td style="padding:6px 0;font-size:13px;font-weight:600;color:#0F172A">%s à %s</td></tr>
                        %s
                    </table>
                    <p style="margin:24px 0 0;color:#94A3B8;font-size:12px;text-align:center">PetCare · Votre gestionnaire d\'animaux de compagnie</p>
                </div>
            </div></body></html>',
            htmlspecialchars($type?->getLibelle() ?? 'Événement'),
            htmlspecialchars($animal?->getNom() ?? ''),
            $delai,
            htmlspecialchars($user->getPrenom()),
            htmlspecialchars($animal?->getNom() ?? ''),
            htmlspecialchars($type?->getLibelle() ?? ''),
            htmlspecialchars($date ?? ''),
            htmlspecialchars($heure ?? ''),
            $commentaireRow
        );

        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($user->getEmail())
            ->subject(sprintf('[PetCare] Rappel : %s pour %s %s', $type?->getLibelle(), $animal?->getNom(), $delai))
            ->html($html);

        $this->mailer->send($email);
    }

    public function sendInvitationEmail(User $invitedUser, User $owner, string $animalNom, string $role): void
    {
        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($invitedUser->getEmail())
            ->subject(sprintf('%s vous partage l\'accès à %s sur PetCare', $owner->getPrenom(), $animalNom))
            ->html(sprintf(
                '<h2>Partage d\'animal sur PetCare</h2>
                <p>Bonjour %s,</p>
                <p><strong>%s %s</strong> vous a partagé l\'accès à <strong>%s</strong> avec le rôle <strong>%s</strong>.</p>
                <p>Connectez-vous à PetCare pour consulter les informations de cet animal.</p>',
                htmlspecialchars($invitedUser->getPrenom()),
                htmlspecialchars($owner->getPrenom()),
                htmlspecialchars($owner->getNom()),
                htmlspecialchars($animalNom),
                $role === 'ecriture' ? 'Lecture / Écriture' : 'Lecture seule'
            ));

        $this->mailer->send($email);
    }
}
