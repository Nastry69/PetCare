<?php

namespace App\Tests\Unit\Service;

use App\Entity\Animal;
use App\Entity\Evenement;
use App\Entity\TypeEvenement;
use App\Entity\User;
use App\Service\MailerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * Tests unitaires du MailerService.
 * Vérifie que chaque méthode construit et envoie un email correct
 * sans dépendance à un serveur SMTP réel.
 */
class MailerServiceTest extends TestCase
{
    private MailerInterface&MockObject $mailer;
    private MailerService $service;

    protected function setUp(): void
    {
        $this->mailer  = $this->createMock(MailerInterface::class);
        $this->service = new MailerService($this->mailer, 'test@petcare.fr', 'PetCare Test');
    }

    // ── sendWelcomeEmail ─────────────────────────────────────────────────────

    public function testSendWelcomeEmailCallsMailerOnce(): void
    {
        $this->mailer->expects($this->once())->method('send');
        $this->service->sendWelcomeEmail($this->makeUser());
    }

    public function testSendWelcomeEmailSentToCorrectRecipient(): void
    {
        $captured = $this->captureEmail();

        $this->service->sendWelcomeEmail($this->makeUser('jean@example.fr', 'Jean', 'Dupont'));

        $this->assertSame('jean@example.fr', $captured[0]->getTo()[0]->getAddress());
    }

    public function testSendWelcomeEmailSubjectContainsPrenom(): void
    {
        $captured = $this->captureEmail();

        $this->service->sendWelcomeEmail($this->makeUser('a@b.fr', 'Marie', 'Curie'));

        $this->assertStringContainsString('Marie', $captured[0]->getSubject());
    }

    public function testSendWelcomeEmailHtmlContainsPrenomAndCta(): void
    {
        $captured = $this->captureEmail();

        $this->service->sendWelcomeEmail($this->makeUser('a@b.fr', 'Marie', 'Curie'));

        $html = $captured[0]->getHtmlBody();
        $this->assertStringContainsString('Marie', $html);
        $this->assertStringContainsString('PetCare', $html);
        $this->assertStringContainsString('http://localhost:5173/login', $html);
    }

    public function testSendWelcomeEmailFromAddressMatchesConfig(): void
    {
        $captured = $this->captureEmail();

        $this->service->sendWelcomeEmail($this->makeUser());

        $from = $captured[0]->getFrom()[0];
        $this->assertSame('test@petcare.fr', $from->getAddress());
        $this->assertStringContainsString('PetCare Test', $from->getName());
    }

    // ── sendReminderEmail ────────────────────────────────────────────────────

    public function testSendReminderEmailDoesNothingWhenNoAnimal(): void
    {
        $this->mailer->expects($this->never())->method('send');

        $evenement = new Evenement(); // pas d'animal → pas de propriétaire
        $this->service->sendReminderEmail($evenement);
    }

    public function testSendReminderEmailSubjectContainsTypeAndAnimal(): void
    {
        $captured = $this->captureEmail();

        $this->service->sendReminderEmail($this->makeEvenement());

        $subject = $captured[0]->getSubject();
        $this->assertStringContainsString('Rappel PetCare', $subject);
        $this->assertStringContainsString('Vaccin', $subject);
        $this->assertStringContainsString('Rex', $subject);
    }

    public function testSendReminderEmailHtmlContainsEventDetails(): void
    {
        $captured = $this->captureEmail();

        $this->service->sendReminderEmail($this->makeEvenement());

        $html = $captured[0]->getHtmlBody();
        $this->assertStringContainsString('Rex', $html);
        $this->assertStringContainsString('Vaccin', $html);
        $this->assertStringContainsString('http://localhost:5173/dashboard', $html);
    }

    // ── sendInvitationEmail ──────────────────────────────────────────────────

    public function testSendInvitationEmailCallsMailerOnce(): void
    {
        $this->mailer->expects($this->once())->method('send');

        $this->service->sendInvitationEmail(
            $this->makeUser('invite@example.fr', 'Sophie', 'Martin'),
            $this->makeUser('owner@example.fr', 'Jean', 'Dupont'),
            'Rex',
            'lecture'
        );
    }

    public function testSendInvitationEmailHtmlContainsEcritureRoleLabel(): void
    {
        $captured = $this->captureEmail();

        $this->service->sendInvitationEmail(
            $this->makeUser('invite@example.fr', 'Sophie', 'Martin'),
            $this->makeUser('owner@example.fr', 'Jean', 'Dupont'),
            'Rex',
            'ecriture'
        );

        $html = $captured[0]->getHtmlBody();
        $this->assertStringContainsString('Lecture', $html);
        $this->assertStringContainsString('criture', $html); // "&" varie selon encodage
        $this->assertStringContainsString('Rex', $html);
        $this->assertStringContainsString('Sophie', $html);
    }

    public function testSendInvitationEmailHtmlContainsLectureRoleLabel(): void
    {
        $captured = $this->captureEmail();

        $this->service->sendInvitationEmail(
            $this->makeUser('invite@example.fr', 'Sophie', 'Martin'),
            $this->makeUser('owner@example.fr', 'Jean', 'Dupont'),
            'Rex',
            'lecture'
        );

        $this->assertStringContainsString('Lecture seule', $captured[0]->getHtmlBody());
    }

    public function testSendInvitationEmailSentToInvitedUser(): void
    {
        $captured = $this->captureEmail();

        $this->service->sendInvitationEmail(
            $this->makeUser('invite@example.fr', 'Sophie', 'Martin'),
            $this->makeUser('owner@example.fr', 'Jean', 'Dupont'),
            'Rex',
            'lecture'
        );

        $this->assertSame('invite@example.fr', $captured[0]->getTo()[0]->getAddress());
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Capture l'objet Email passé à send() dans un tableau par référence.
     *
     * @return Email[] (rempli après l'appel à la méthode testée)
     */
    private function captureEmail(): array
    {
        $captured = [];
        $this->mailer
            ->method('send')
            ->willReturnCallback(static function (RawMessage $msg) use (&$captured): void {
                if ($msg instanceof Email) {
                    $captured[] = $msg;
                }
            });
        return $captured;
    }

    private function makeUser(
        string $email  = 'tristan@petcare.fr',
        string $prenom = 'Tristan',
        string $nom    = 'Dzioch'
    ): User {
        $user = new User();
        $user->setNom($nom)->setPrenom($prenom)->setEmail($email)->setRoles(['ROLE_USER']);
        return $user;
    }

    private function makeEvenement(): Evenement
    {
        $owner  = $this->makeUser();
        $animal = new Animal();
        $animal->setNom('Rex')->setEspece('Chien')->setProprietaire($owner);

        $type = new TypeEvenement();
        $type->setLibelle('Vaccin')->setCouleur('#8B5CF6');

        $evenement = new Evenement();
        $evenement
            ->setAnimal($animal)
            ->setTypeEvenement($type)
            ->setCreateur($owner)
            ->setDateHeureEvenement(new \DateTime('+2 days'))
            ->setStatut('prevu')
            ->setRappelActif(true)
            ->setRappelJoursAvant(2)
            ->setDateCreation(new \DateTime());

        return $evenement;
    }
}
