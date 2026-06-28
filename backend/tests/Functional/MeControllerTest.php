<?php

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Tests fonctionnels des routes profil utilisateur restantes.
 *
 * Routes testées :
 *   PUT  /api/me          (mise à jour du profil)
 *   GET  /api/me/export   (export RGPD)
 *   POST /api/me/photo    (upload photo — auth + validation)
 *   POST /api/animals/{id}/photo (upload photo animal — auth + validation)
 */
class MeControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);

        $tool  = new SchemaTool($this->em);
        $metas = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metas);
        $tool->createSchema($metas);
    }

    // ── PUT /api/me ──────────────────────────────────────────────────────────

    public function testUpdateMeRequiresAuth(): void
    {
        $this->client->request('PUT', '/api/me', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateMeChangesNomAndPrenom(): void
    {
        $token = $this->registerAndGetToken('update@me.fr');

        $this->client->request('PUT', '/api/me', [], [], $this->headers($token),
            json_encode(['nom' => 'Nouveau', 'prenom' => 'Prenom'])
        );

        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertSame('Nouveau', $data['nom']);
        $this->assertSame('Prenom', $data['prenom']);
    }

    public function testUpdateMeChangesEmail(): void
    {
        $token = $this->registerAndGetToken('old@me.fr');

        $this->client->request('PUT', '/api/me', [], [], $this->headers($token),
            json_encode(['email' => 'new@me.fr'])
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame('new@me.fr', $this->json()['email']);
    }

    public function testUpdateMeWithDuplicateEmailReturns409(): void
    {
        $this->registerAndGetToken('existing@me.fr');
        $token = $this->registerAndGetToken('current@me.fr');

        $this->client->request('PUT', '/api/me', [], [], $this->headers($token),
            json_encode(['email' => 'existing@me.fr'])
        );

        $this->assertResponseStatusCodeSame(409);
    }

    public function testUpdateMeWithShortPasswordReturns400(): void
    {
        $token = $this->registerAndGetToken('pwdtest@me.fr');

        $this->client->request('PUT', '/api/me', [], [], $this->headers($token),
            json_encode(['password' => 'court'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateMeChangesPassword(): void
    {
        $token = $this->registerAndGetToken('chgpwd@me.fr');

        $this->client->request('PUT', '/api/me', [], [], $this->headers($token),
            json_encode(['password' => 'nouveaumdp456'])
        );
        $this->assertResponseIsSuccessful();

        // L'ancien mot de passe ne doit plus fonctionner
        $this->client->request('POST', '/api/auth/login_check', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'chgpwd@me.fr', 'password' => 'motdepasse123'])
        );
        $this->assertResponseStatusCodeSame(401);

        // Le nouveau fonctionne
        $this->client->request('POST', '/api/auth/login_check', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'chgpwd@me.fr', 'password' => 'nouveaumdp456'])
        );
        $this->assertResponseStatusCodeSame(200);
    }

    // ── GET /api/me/export ───────────────────────────────────────────────────

    public function testExportMeRequiresAuth(): void
    {
        $this->client->request('GET', '/api/me/export');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testExportMeReturnsUserData(): void
    {
        $token = $this->registerAndGetToken('export@me.fr');

        $this->client->request('GET', '/api/me/export', [], [], $this->headers($token));

        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('animals', $data);
        $this->assertArrayHasKey('evenements', $data);
        $this->assertArrayHasKey('exportedAt', $data);
        $this->assertSame('export@me.fr', $data['user']['email']);
    }

    public function testExportMeIncludesCreatedAnimals(): void
    {
        $token = $this->registerAndGetToken('exportanimals@me.fr');

        // Crée un animal
        $this->client->request('POST', '/api/animals', [], [], $this->headers($token),
            json_encode(['nom' => 'Mimi', 'espece' => 'Chat'])
        );

        $this->client->request('GET', '/api/me/export', [], [], $this->headers($token));
        $data = $this->json();

        $this->assertCount(1, $data['animals']);
        $this->assertSame('Mimi', $data['animals'][0]['nom']);
    }

    // ── POST /api/me/photo ───────────────────────────────────────────────────

    public function testUploadMePhotoRequiresAuth(): void
    {
        $this->client->request('POST', '/api/me/photo');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUploadMePhotoWithNoFileReturns400(): void
    {
        $token = $this->registerAndGetToken('photo@me.fr');

        // Requête sans fichier → 400
        $this->client->request('POST', '/api/me/photo', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString('photo', $this->json()['message']);
    }

    public function testUploadMePhotoWithValidImageReturns200(): void
    {
        $token = $this->registerAndGetToken('photovalid@me.fr');

        // PNG 1×1 px valide sans dépendance GD (bytes bruts)
        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk' .
            'YPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        );
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_photo_') . '.png';
        file_put_contents($tmpFile, $pngBytes);

        $uploadedFile = new UploadedFile($tmpFile, 'test.png', 'image/png', null, true);

        $this->client->request('POST', '/api/me/photo', [], ['photo' => $uploadedFile], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        @unlink($tmpFile);
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('photoUrl', $this->json());
    }

    // ── POST /api/animals/{id}/photo ─────────────────────────────────────────

    public function testUploadAnimalPhotoRequiresAuth(): void
    {
        $this->client->request('POST', '/api/animals/1/photo');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUploadAnimalPhotoWithNoFileReturns400(): void
    {
        $token = $this->registerAndGetToken('animalphoto@me.fr');

        // Crée un animal
        $this->client->request('POST', '/api/animals', [], [], $this->headers($token),
            json_encode(['nom' => 'Rex', 'espece' => 'Chien'])
        );
        $animalId = $this->json()['id'];

        // Requête sans fichier → 400
        $this->client->request('POST', "/api/animals/$animalId/photo", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUploadAnimalPhotoReturns404ForUnknownAnimal(): void
    {
        $token = $this->registerAndGetToken('animalphoto404@me.fr');

        $this->client->request('POST', '/api/animals/999999/photo', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUploadAnimalPhotoReturns403ForNonOwner(): void
    {
        $ownerToken = $this->registerAndGetToken('animalowner@me.fr');
        $otherToken = $this->registerAndGetToken('notowner@me.fr');

        // Crée un animal avec le propriétaire
        $this->client->request('POST', '/api/animals', [], [], $this->headers($ownerToken),
            json_encode(['nom' => 'Rex', 'espece' => 'Chien'])
        );
        $animalId = $this->json()['id'];

        // L'autre utilisateur tente d'uploader
        $this->client->request('POST', "/api/animals/$animalId/photo", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $otherToken,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUploadAnimalPhotoWithValidImageReturns200(): void
    {
        $token = $this->registerAndGetToken('animalimgvalid@me.fr');

        $this->client->request('POST', '/api/animals', [], [], $this->headers($token),
            json_encode(['nom' => 'Mimi', 'espece' => 'Chat'])
        );
        $animalId = $this->json()['id'];

        // PNG 1×1 px valide sans dépendance GD (bytes bruts)
        $pngBytes = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk' .
            'YPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
        );
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_animal_') . '.png';
        file_put_contents($tmpFile, $pngBytes);

        $uploadedFile = new UploadedFile($tmpFile, 'animal.png', 'image/png', null, true);

        $this->client->request('POST', "/api/animals/$animalId/photo", [], ['photo' => $uploadedFile], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        @unlink($tmpFile);
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('photoUrl', $this->json());
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function registerAndGetToken(string $email): string
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => 'motdepasse123', 'nom' => 'Test', 'prenom' => 'User'])
        );
        return json_decode($this->client->getResponse()->getContent(), true)['token'];
    }

    private function headers(?string $token): array
    {
        $h = ['CONTENT_TYPE' => 'application/json'];
        if ($token) {
            $h['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }
        return $h;
    }

    private function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
    }
}
