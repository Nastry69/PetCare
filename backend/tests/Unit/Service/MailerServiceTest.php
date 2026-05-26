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
        $bag = $this->captureBag();

        $this->service->sendWelcomeEmail($this->makeUser('jean@example.fr', 'Jean', 'Dupont'));

        $this->assertCount(1, $bag);
        $this->assertSame('jean@example.fr', $bag[0]->getTo()[0]->getAddress());
    }

    public function testSendWelcomeEmailSubjectContainsPrenom(): void
    {
        $bag = $this->captureBag();

        $this->service->sendWelcomeEmail($this->makeUser('a@b.fr', 'Marie', 'Curie'));

        $this->assertStringContainsString('Marie', $bag[0]->getSubject());
    }

    public function testSendWelcomeEmailHtmlContainsPrenomAndCta(): void
    {
        $bag = $this->captureBag();

        $this->service->sendWelcomeEmail($this->makeUser('a@b.fr', 'Marie', 'Curie'));

        $html = $bag[0]->getHtmlBody();
        $this->assertStringContainsString('Marie', $html);
        $this->assertStringContainsString('PetCare', $html);
        $this->assertStringContainsString('http://localhost:3000/login', $html);
    }

    public function testSendWelcomeEmailFromAddressMatchesConfig(): void
    {
        $bag = $this->captureBag();

        $this->service->sendWelcomeEmail($this->makeUser());

        $from = $bag[0]->getFrom()[0];
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
        $bag = $this->captureBag();

        $this->service->sendReminderEmail($this->makeEvenement());

        $subject = $bag[0]->getSubject();
        $this->assertStringContainsString('Rappel PetCare', $subject);
        $this->assertStringContainsString('Vaccin', $subject);
        $this->assertStringContainsString('Rex', $subject);
    }

    public function testSendReminderEmailHtmlContainsEventDetails(): void
    {
        $bag = $this->captureBag();

        $this->service->sendReminderEmail($this->makeEvenement());

        $html = $bag[0]->getHtmlBody();
        $this->assertStringContainsString('Rex', $html);
        $this->assertStringContainsString('Vaccin', $html);
        $this->assertStringContainsString('http://localhost:3000/dashboard', $html);
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
        $bag = $this->captureBag();

        $this->service->sendInvitationEmail(
            $this->makeUser('invite@example.fr', 'Sophie', 'Martin'),
            $this->makeUser('owner@example.fr', 'Jean', 'Dupont'),
            'Rex',
            'ecriture'
        );

        $html = $bag[0]->getHtmlBody();
        $this->assertStringContainsString('Lecture', $html);
        $this->assertStringContainsString('criture', $html); // &amp; ou & selon encodage
        $this->assertStringContainsString('Rex', $html);
        $this->assertStringContainsString('Sophie', $html);
    }

    public function testSendInvitationEmailHtmlContainsLectureRoleLabel(): void
    {
        $bag = $this->captureBag();

        $this->service->sendInvitationEmail(
            $this->makeUser('invite@example.fr', 'Sophie', 'Martin'),
            $this->makeUser('owner@example.fr', 'Jean', 'Dupont'),
            'Rex',
            'lecture'
        );

        $this->assertStringContainsString('Lecture seule', $bag[0]->getHtmlBody());
    }

    public function testSendInvitationEmailSentToInvitedUser(): void
    {
        $bag = $this->captureBag();

        $this->service->sendInvitationEmail(
            $this->makeUser('invite@example.fr', 'Sophie', 'Martin'),
            $this->makeUser('owner@example.fr', 'Jean', 'Dupont'),
            'Rex',
            'lecture'
        );

        $this->assertSame('invite@example.fr', $bag[0]->getTo()[0]->getAddress());
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Prépare le mock pour capturer l'Email envoyé dans un ArrayObject.
     *
     * Pourquoi ArrayObject et pas array ?
     * En PHP, les tableaux sont copiés à l'assignation. Si on retourne $captured
     * (une copie vide) et qu'on peuple $captured dans la closure via use(&$captured),
     * la variable retournée reste vide. Un ArrayObject est un objet : la closure et
     * l'appelant partagent la même instance → les emails ajoutés sont visibles.
     *
     * @return \ArrayObject<int, Email>
     */
    private function captureBag(): \ArrayObject
    {
        $bag = new \ArrayObject();

        $this->mailer
            ->method('send')
            ->willReturnCallback(static function (RawMessage $msg) use ($bag): void {
                if ($msg instanceof Email) {
                    $bag->append($msg);
                }
            });

        return $bag;
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
