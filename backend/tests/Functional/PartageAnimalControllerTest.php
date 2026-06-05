<?php

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du PartageAnimalController.
 *
 * Routes testées :
 *   GET    /api/partages
 *   GET    /api/partages/animal/{animalId}
 *   POST   /api/partages
 *   PUT    /api/partages/{id}
 *   DELETE /api/partages/{id}
 */
class PartageAnimalControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private string $ownerToken;
    private string $invitedToken;
    private string $otherToken;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);

        $tool  = new SchemaTool($this->em);
        $metas = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metas);
        $tool->createSchema($metas);

        $this->ownerToken   = $this->registerAndGetToken('owner@partage.fr');
        $this->invitedToken = $this->registerAndGetToken('invited@partage.fr');
        $this->otherToken   = $this->registerAndGetToken('other@partage.fr');
    }

    // ── GET /api/partages ────────────────────────────────────────────────────

    public function testGetPartagesRequiresAuth(): void
    {
        $this->client->request('GET', '/api/partages');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetPartagesReturnsEmptyListInitially(): void
    {
        $this->get('/api/partages', $this->ownerToken);
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->json());
    }

    public function testGetPartagesListContainsCreatedPartage(): void
    {
        $this->createPartage();

        // L'invité doit voir le partage dans sa liste
        $this->get('/api/partages', $this->invitedToken);
        $this->assertCount(1, $this->json());
    }

    // ── GET /api/partages/animal/{animalId} ──────────────────────────────────

    public function testGetPartagesByAnimalRequiresAuth(): void
    {
        $animalId = $this->createAnimal();
        $this->client->request('GET', "/api/partages/animal/$animalId");
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetPartagesByAnimalReturnsEmptyListForOwner(): void
    {
        $animalId = $this->createAnimal();
        $this->get("/api/partages/animal/$animalId", $this->ownerToken);
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->json());
    }

    public function testGetPartagesByAnimalReturns403ForNonOwner(): void
    {
        $animalId = $this->createAnimal();
        $this->get("/api/partages/animal/$animalId", $this->invitedToken);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetPartagesByAnimalReturns404ForUnknownAnimal(): void
    {
        $this->get('/api/partages/animal/999999', $this->ownerToken);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetPartagesByAnimalContainsCreatedPartage(): void
    {
        $animalId = $this->createAnimal();
        $this->post('/api/partages', [
            'animal_id'   => $animalId,
            'email'       => 'invited@partage.fr',
            'rolePartage' => 'lecture',
        ], $this->ownerToken);

        $this->get("/api/partages/animal/$animalId", $this->ownerToken);
        $this->assertCount(1, $this->json());
    }

    // ── POST /api/partages ───────────────────────────────────────────────────

    public function testCreatePartageRequiresAuth(): void
    {
        $this->post('/api/partages', []);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreatePartageWithValidDataReturns201(): void
    {
        $animalId = $this->createAnimal();

        $this->post('/api/partages', [
            'animal_id'   => $animalId,
            'email'       => 'invited@partage.fr',
            'rolePartage' => 'lecture',
        ], $this->ownerToken);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json();
        $this->assertSame('lecture', $data['rolePartage']);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('animal', $data);
        $this->assertArrayHasKey('utilisateur', $data);
    }

    public function testCreatePartageWithEcritureRole(): void
    {
        $animalId = $this->createAnimal();

        $this->post('/api/partages', [
            'animal_id'   => $animalId,
            'email'       => 'invited@partage.fr',
            'rolePartage' => 'ecriture',
        ], $this->ownerToken);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('ecriture', $this->json()['rolePartage']);
    }

    public function testCreatePartageWithMissingFieldsReturns400(): void
    {
        $this->post('/api/partages', ['animal_id' => 1], $this->ownerToken);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreatePartageWithUnknownAnimalReturns404(): void
    {
        $this->post('/api/partages', [
            'animal_id'   => 999999,
            'email'       => 'invited@partage.fr',
            'rolePartage' => 'lecture',
        ], $this->ownerToken);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreatePartageWithUnknownEmailReturns404(): void
    {
        $animalId = $this->createAnimal();
        $this->post('/api/partages', [
            'animal_id'   => $animalId,
            'email'       => 'inconnu@nobody.fr',
            'rolePartage' => 'lecture',
        ], $this->ownerToken);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreatePartageByNonOwnerReturns403(): void
    {
        $animalId = $this->createAnimal();
        $this->post('/api/partages', [
            'animal_id'   => $animalId,
            'email'       => 'other@partage.fr',
            'rolePartage' => 'lecture',
        ], $this->invitedToken); // invité ne peut pas partager
        $this->assertResponseStatusCodeSame(403);
    }

    // ── PUT /api/partages/{id} ───────────────────────────────────────────────

    public function testUpdatePartageChangesRoleToEcriture(): void
    {
        $id = $this->createPartage();

        $this->put("/api/partages/$id", ['rolePartage' => 'ecriture'], $this->ownerToken);
        $this->assertResponseIsSuccessful();
        $this->assertSame('ecriture', $this->json()['rolePartage']);
    }

    public function testUpdatePartageReturns404WhenNotFound(): void
    {
        $this->put('/api/partages/999999', ['rolePartage' => 'ecriture'], $this->ownerToken);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdatePartageReturns403ForNonOwner(): void
    {
        $id = $this->createPartage();
        $this->put("/api/partages/$id", ['rolePartage' => 'ecriture'], $this->invitedToken);
        $this->assertResponseStatusCodeSame(403);
    }

    // ── DELETE /api/partages/{id} ────────────────────────────────────────────

    public function testDeletePartageReturns200AndRemovesIt(): void
    {
        $animalId = $this->createAnimal();
        $this->post('/api/partages', [
            'animal_id'   => $animalId,
            'email'       => 'invited@partage.fr',
            'rolePartage' => 'lecture',
        ], $this->ownerToken);
        $id = $this->json()['id'];

        $this->client->request('DELETE', "/api/partages/$id", [], [], $this->headers($this->ownerToken));
        $this->assertResponseStatusCodeSame(200);

        // Vérification : plus de partage sur cet animal
        $this->get("/api/partages/animal/$animalId", $this->ownerToken);
        $this->assertCount(0, $this->json());
    }

    public function testDeletePartageReturns404WhenNotFound(): void
    {
        $this->client->request('DELETE', '/api/partages/999999', [], [], $this->headers($this->ownerToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeletePartageReturns403ForNonOwner(): void
    {
        // L'invité peut quitter (200) — ce test vérifie qu'un tiers sans lien reçoit 403
        $id = $this->createPartage();
        $this->client->request('DELETE', "/api/partages/$id", [], [], $this->headers($this->otherToken));
        $this->assertResponseStatusCodeSame(403);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function registerAndGetToken(string $email): string
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => 'motdepasse123', 'nom' => 'Test', 'prenom' => 'User'])
        );
        return json_decode($this->client->getResponse()->getContent(), true)['token'];
    }

    private function createAnimal(): int
    {
        $this->post('/api/animals', ['nom' => 'TestAnimal', 'espece' => 'Chien'], $this->ownerToken);
        return $this->json()['id'];
    }

    private function createPartage(): int
    {
        $animalId = $this->createAnimal();
        $this->post('/api/partages', [
            'animal_id'   => $animalId,
            'email'       => 'invited@partage.fr',
            'rolePartage' => 'lecture',
        ], $this->ownerToken);
        return $this->json()['id'];
    }

    private function get(string $uri, ?string $token = null): void
    {
        $this->client->request('GET', $uri, [], [], $this->headers($token));
    }

    private function post(string $uri, array $payload, ?string $token = null): void
    {
        $this->client->request('POST', $uri, [], [], $this->headers($token), json_encode($payload));
    }

    private function put(string $uri, array $payload, ?string $token = null): void
    {
        $this->client->request('PUT', $uri, [], [], $this->headers($token), json_encode($payload));
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
