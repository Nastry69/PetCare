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
        private string $fromName,
        private string $frontendUrl = 'http://localhost:3000'
    ) {
    }

    // ── Email de bienvenue ───────────────────────────────────────────────────

    public function sendWelcomeEmail(User $user): void
    {
        $prenom = htmlspecialchars($user->getPrenom());
        $loginUrl = $this->frontendUrl . '/login';

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background:#F1F5F9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif">
          <div style="max-width:560px;margin:40px auto;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 4px 32px rgba(0,0,0,.08)">

            <!-- Header -->
            <div style="background:linear-gradient(135deg,#2A8BF2 0%,#1377EC 100%);padding:40px 40px 36px;text-align:center">
              <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:#ffffff;margin-bottom:14px;font-size:32px;box-shadow:0 4px 16px rgba(0,0,0,0.15)">🐾</div>
              <h1 style="margin:0;color:#ffffff;font-size:28px;font-weight:800;letter-spacing:-0.5px;text-shadow:0 1px 4px rgba(0,0,0,0.15)">PetCare</h1>
              <p style="margin:6px 0 0;color:rgba(255,255,255,0.9);font-size:13px;font-weight:500;letter-spacing:0.05em;text-transform:uppercase">Votre gestionnaire d'animaux</p>
            </div>

            <!-- Body -->
            <div style="padding:40px">
              <h2 style="margin:0 0 8px;color:#0F172A;font-size:22px;font-weight:700">Bonjour {$prenom} ! 👋</h2>
              <p style="margin:0 0 24px;color:#475569;font-size:15px;line-height:1.6">
                Bienvenue sur <strong style="color:#1377EC">PetCare</strong>, votre espace dédié au suivi de la santé et du bien-être de vos animaux de compagnie.
              </p>

              <!-- Features -->
              <div style="background:#F8FAFC;border-radius:14px;padding:24px;margin-bottom:28px">
                <p style="margin:0 0 16px;color:#0F172A;font-size:14px;font-weight:600">Vous pouvez dès maintenant :</p>
                <div style="display:flex;flex-direction:column;gap:10px">
                  <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:18px">🐶</span>
                    <span style="color:#334155;font-size:14px">Ajouter vos animaux et leurs informations</span>
                  </div>
                  <div style="display:flex;align-items:center;gap:10px;margin-top:8px">
                    <span style="font-size:18px">📅</span>
                    <span style="color:#334155;font-size:14px">Planifier vaccins, consultations et traitements</span>
                  </div>
                  <div style="display:flex;align-items:center;gap:10px;margin-top:8px">
                    <span style="font-size:18px">🔔</span>
                    <span style="color:#334155;font-size:14px">Recevoir des rappels avant chaque événement</span>
                  </div>
                  <div style="display:flex;align-items:center;gap:10px;margin-top:8px">
                    <span style="font-size:18px">👥</span>
                    <span style="color:#334155;font-size:14px">Partager l'accès avec un vétérinaire ou un proche</span>
                  </div>
                </div>
              </div>

              <!-- CTA -->
              <div style="text-align:center">
                <a href="{$loginUrl}"
                   style="display:inline-block;background:#1377EC;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:12px;font-size:15px;font-weight:700;letter-spacing:0.02em">
                  Accéder à mon espace →
                </a>
              </div>
            </div>

            <!-- Footer -->
            <div style="background:#F8FAFC;padding:20px 40px;text-align:center;border-top:1px solid #E2E8F0">
              <p style="margin:0;color:#94A3B8;font-size:12px">
                PetCare · Votre gestionnaire d'animaux de compagnie<br>
                <span style="color:#CBD5E1">Cet email vous a été envoyé suite à votre inscription.</span>
              </p>
            </div>

          </div>
        </body>
        </html>
        HTML;

        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($user->getEmail())
            ->subject('🐾 Bienvenue sur PetCare, ' . $user->getPrenom() . ' !')
            ->html($html);

        $this->mailer->send($email);
    }

    // ── Email de rappel ──────────────────────────────────────────────────────

    public function sendReminderEmail(Evenement $evenement): void
    {
        $user = $evenement->getAnimal()?->getProprietaire();
        if (!$user) {
            return;
        }

        $animal      = $evenement->getAnimal();
        $type        = $evenement->getTypeEvenement();
        $joursAvant  = $evenement->getRappelJoursAvant() ?? 1;
        $date        = $evenement->getDateHeureEvenement()?->format('d/m/Y') ?? '';
        $heure       = $evenement->getDateHeureEvenement()?->format('H\hi') ?? '';
        $delai       = $joursAvant === 1 ? 'demain' : "dans $joursAvant jours";
        $delaiTableau = $joursAvant === 1 ? '1 jour' : "$joursAvant jours";
        $prenom      = htmlspecialchars($user->getPrenom());
        $animalNom   = htmlspecialchars($animal?->getNom() ?? '');
        $typeLibelle = htmlspecialchars($type?->getLibelle() ?? 'Événement');
        $couleur     = htmlspecialchars($type?->getCouleur() ?? '#1377EC');
        $dashUrl     = $this->frontendUrl . '/dashboard';

        $commentaireRow = $evenement->getCommentaire()
            ? '<tr>
                <td style="padding:8px 16px;color:#64748B;font-size:13px;white-space:nowrap">📝 Note</td>
                <td style="padding:8px 16px;font-size:13px;color:#0F172A">' . htmlspecialchars($evenement->getCommentaire()) . '</td>
               </tr>'
            : '';

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background:#F1F5F9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif">
          <div style="max-width:560px;margin:40px auto;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 4px 32px rgba(0,0,0,.08)">

            <!-- Header -->
            <div style="background:linear-gradient(135deg,#2A8BF2 0%,#1377EC 100%);padding:32px 40px">
              <div style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;background:#ffffff;margin-bottom:12px;font-size:22px;box-shadow:0 2px 8px rgba(0,0,0,0.15)">🐾</div>
              <p style="margin:0 0 4px;color:rgba(255,255,255,0.9);font-size:12px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase">PetCare · Rappel</p>
              <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:800;text-shadow:0 1px 4px rgba(0,0,0,0.15)">{$typeLibelle} pour {$animalNom}</h1>
              <p style="margin:8px 0 0;color:rgba(255,255,255,0.9);font-size:14px">📅 Prévu <strong>{$delai}</strong>, le {$date} à {$heure}</p>
            </div>

            <!-- Body -->
            <div style="padding:36px 40px">
              <p style="margin:0 0 24px;color:#475569;font-size:15px;line-height:1.6">
                Bonjour <strong style="color:#0F172A">{$prenom}</strong>,<br>
                Voici un rappel pour l'événement à venir de votre animal.
              </p>

              <!-- Event card -->
              <div style="border:2px solid #E2E8F0;border-radius:14px;overflow:hidden;margin-bottom:28px">
                <div style="background:{$couleur};padding:10px 16px">
                  <span style="color:#ffffff;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em">{$typeLibelle}</span>
                </div>
                <table style="width:100%;border-collapse:collapse">
                  <tr style="border-bottom:1px solid #F1F5F9">
                    <td style="padding:12px 16px;color:#64748B;font-size:13px;white-space:nowrap;width:120px">🐾 Animal</td>
                    <td style="padding:12px 16px;font-size:14px;font-weight:700;color:#0F172A">{$animalNom}</td>
                  </tr>
                  <tr style="border-bottom:1px solid #F1F5F9">
                    <td style="padding:12px 16px;color:#64748B;font-size:13px;white-space:nowrap">📅 Date</td>
                    <td style="padding:12px 16px;font-size:14px;font-weight:700;color:#0F172A">{$date} à {$heure}</td>
                  </tr>
                  <tr style="border-bottom:1px solid #F1F5F9">
                    <td style="padding:12px 16px;color:#64748B;font-size:13px;white-space:nowrap">⏰ Dans</td>
                    <td style="padding:12px 16px;font-size:14px;font-weight:700;color:#1377EC">{$delaiTableau}</td>
                  </tr>
                  {$commentaireRow}
                </table>
              </div>

              <!-- CTA -->
              <div style="text-align:center;margin-bottom:8px">
                <a href="{$dashUrl}"
                   style="display:inline-block;background:#1377EC;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:12px;font-size:14px;font-weight:700;margin-right:8px">
                  Voir le calendrier →
                </a>
              </div>
            </div>

            <!-- Footer -->
            <div style="background:#F8FAFC;padding:20px 40px;text-align:center;border-top:1px solid #E2E8F0">
              <p style="margin:0;color:#94A3B8;font-size:12px">
                PetCare · Votre gestionnaire d'animaux de compagnie<br>
                <span style="color:#CBD5E1">Vous recevez cet email car un rappel est activé pour cet événement.</span>
              </p>
            </div>

          </div>
        </body>
        </html>
        HTML;

        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($user->getEmail())
            ->subject(sprintf('🔔 Rappel PetCare : %s pour %s %s', $typeLibelle, $animalNom, $delai))
            ->html($html);

        $this->mailer->send($email);
    }

    // ── Email de réinitialisation de mot de passe ────────────────────────────

    public function sendPasswordResetEmail(User $user, string $token): void
    {
        $prenom    = htmlspecialchars($user->getPrenom());
        $resetUrl  = $this->frontendUrl . '/reset-password?token=' . urlencode($token);

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background:#F1F5F9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif">
          <div style="max-width:560px;margin:40px auto;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 4px 32px rgba(0,0,0,.08)">

            <!-- Header -->
            <div style="background:linear-gradient(135deg,#2A8BF2 0%,#1377EC 100%);padding:40px 40px 36px;text-align:center">
              <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:#ffffff;margin-bottom:14px;font-size:32px;box-shadow:0 4px 16px rgba(0,0,0,0.15)">🔐</div>
              <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:800;letter-spacing:-0.5px;text-shadow:0 1px 4px rgba(0,0,0,0.15)">Réinitialisation du mot de passe</h1>
              <p style="margin:8px 0 0;color:rgba(255,255,255,0.9);font-size:13px">PetCare — Sécurité du compte</p>
            </div>

            <!-- Body -->
            <div style="padding:40px">
              <h2 style="margin:0 0 8px;color:#0F172A;font-size:20px;font-weight:700">Bonjour {$prenom},</h2>
              <p style="margin:0 0 24px;color:#475569;font-size:15px;line-height:1.6">
                Vous avez demandé la réinitialisation de votre mot de passe PetCare.<br>
                Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.
              </p>

              <!-- Warning box -->
              <div style="background:#FFF4E5;border-radius:12px;padding:16px 20px;margin-bottom:28px;border-left:4px solid #F59E0B">
                <p style="margin:0;color:#92400E;font-size:13px;line-height:1.5">
                  ⏰ <strong>Ce lien est valable 1 heure.</strong><br>
                  Si vous n'avez pas demandé cette réinitialisation, ignorez cet email — votre mot de passe restera inchangé.
                </p>
              </div>

              <!-- CTA -->
              <div style="text-align:center;margin-bottom:28px">
                <a href="{$resetUrl}"
                   style="display:inline-block;background:#1377EC;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:12px;font-size:15px;font-weight:700;letter-spacing:0.02em">
                  Réinitialiser mon mot de passe →
                </a>
              </div>

              <!-- Fallback link -->
              <p style="margin:0;color:#94A3B8;font-size:12px;text-align:center;word-break:break-all">
                Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
                <span style="color:#1377EC">{$resetUrl}</span>
              </p>
            </div>

            <!-- Footer -->
            <div style="background:#F8FAFC;padding:20px 40px;text-align:center;border-top:1px solid #E2E8F0">
              <p style="margin:0;color:#94A3B8;font-size:12px">
                PetCare · Votre gestionnaire d'animaux de compagnie<br>
                <span style="color:#CBD5E1">Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</span>
              </p>
            </div>

          </div>
        </body>
        </html>
        HTML;

        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($user->getEmail())
            ->subject('🔐 Réinitialisation de votre mot de passe PetCare')
            ->html($html);

        $this->mailer->send($email);
    }

    // ── Email d'invitation (partage) ─────────────────────────────────────────

    public function sendInvitationEmail(User $invitedUser, User $owner, string $animalNom, string $role): void
    {
        $prenom      = htmlspecialchars($invitedUser->getPrenom());
        $ownerNom    = htmlspecialchars($owner->getPrenom() . ' ' . $owner->getNom());
        $animal      = htmlspecialchars($animalNom);
        $roleLabel   = $role === 'ecriture' ? 'Lecture & Écriture' : 'Lecture seule';
        $loginUrl    = $this->frontendUrl . '/login';

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background:#F1F5F9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif">
          <div style="max-width:560px;margin:40px auto;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 4px 32px rgba(0,0,0,.08)">

            <!-- Header -->
            <div style="background:linear-gradient(135deg,#2A8BF2 0%,#1377EC 100%);padding:40px 40px 36px;text-align:center">
              <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:#ffffff;margin-bottom:14px;font-size:32px;box-shadow:0 4px 16px rgba(0,0,0,0.15)">🐾</div>
              <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:800;text-shadow:0 1px 4px rgba(0,0,0,0.15)">Partage d'animal</h1>
              <p style="margin:8px 0 0;color:rgba(255,255,255,0.9);font-size:14px">{$ownerNom} vous invite sur PetCare</p>
            </div>

            <!-- Body -->
            <div style="padding:40px">
              <p style="margin:0 0 20px;color:#475569;font-size:15px;line-height:1.6">
                Bonjour <strong style="color:#0F172A">{$prenom}</strong>,
              </p>
              <p style="margin:0 0 28px;color:#475569;font-size:15px;line-height:1.6">
                <strong style="color:#1377EC">{$ownerNom}</strong> vous partage l'accès à <strong style="color:#0F172A">{$animal}</strong> sur PetCare.
              </p>

              <!-- Role badge -->
              <div style="background:#EAF3FF;border-radius:12px;padding:16px 20px;margin-bottom:28px;text-align:center">
                <p style="margin:0 0 4px;color:#64748B;font-size:12px;text-transform:uppercase;letter-spacing:0.08em;font-weight:600">Votre rôle</p>
                <p style="margin:0;color:#1377EC;font-size:18px;font-weight:800">{$roleLabel}</p>
              </div>

              <!-- CTA -->
              <div style="text-align:center">
                <a href="{$loginUrl}"
                   style="display:inline-block;background:#1377EC;color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:12px;font-size:15px;font-weight:700">
                  Accéder à PetCare →
                </a>
              </div>
            </div>

            <!-- Footer -->
            <div style="background:#F8FAFC;padding:20px 40px;text-align:center;border-top:1px solid #E2E8F0">
              <p style="margin:0;color:#94A3B8;font-size:12px">
                PetCare · Votre gestionnaire d'animaux de compagnie
              </p>
            </div>

          </div>
        </body>
        </html>
        HTML;

        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromEmail))
            ->to($invitedUser->getEmail())
            ->subject(sprintf('🐾 %s vous partage l\'accès à %s sur PetCare', $owner->getPrenom(), $animalNom))
            ->html($html);

        $this->mailer->send($email);
    }
}
