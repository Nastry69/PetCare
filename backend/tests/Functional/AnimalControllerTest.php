<?php

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du AnimalController.
 * Vérifie le CRUD complet des animaux et le contrôle d'accès.
 *
 * Routes testées :
 *   GET    /api/animals
 *   POST   /api/animals
 *   GET    /api/animals/{id}
 *   PUT    /api/animals/{id}
 *   DELETE /api/animals/{id}
 */
class AnimalControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private string $authToken;
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

        // Inscrit deux utilisateurs pour les tests de contrôle d'accès
        $this->authToken  = $this->registerAndGetToken('owner@petcare.fr');
        $this->otherToken = $this->registerAndGetToken('other@petcare.fr');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }

    // ── GET /api/animals ─────────────────────────────────────────────────────

    public function testGetAnimalsRequiresAuth(): void
    {
        $this->client->request('GET', '/api/animals');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetAnimalsReturnsEmptyListInitially(): void
    {
        $this->get('/api/animals', $this->authToken);

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->json());
    }

    // ── POST /api/animals ────────────────────────────────────────────────────

    public function testCreateAnimalWithValidDataReturns201(): void
    {
        $this->post('/api/animals', ['nom' => 'Rex', 'espece' => 'Chien', 'race' => 'Labrador', 'sexe' => 'male'], $this->authToken);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json();
        $this->assertSame('Rex', $data['nom']);
        $this->assertSame('Chien', $data['espece']);
        $this->assertSame('Labrador', $data['race']);
        $this->assertArrayHasKey('id', $data);
    }

    public function testCreateAnimalWithMissingFieldsReturns400(): void
    {
        $this->post('/api/animals', ['nom' => 'Rex'], $this->authToken); // espece manquant
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateAnimalRequiresAuth(): void
    {
        $this->post('/api/animals', ['nom' => 'Rex', 'espece' => 'Chien']);
        $this->assertResponseStatusCodeSame(401);
    }

    // ── GET /api/animals/{id} ────────────────────────────────────────────────

    public function testGetAnimalByIdReturnsCorrectData(): void
    {
        $id = $this->createAnimal('Mimi', 'Chat');

        $this->get("/api/animals/$id", $this->authToken);

        $this->assertResponseIsSuccessful();
        $this->assertSame('Mimi', $this->json()['nom']);
    }

    public function testGetAnimalByIdReturns404WhenNotFound(): void
    {
        $this->get('/api/animals/999999', $this->authToken);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetAnimalByIdReturns403ForOtherUser(): void
    {
        $id = $this->createAnimal('Bob', 'Lapin');

        // L'autre utilisateur tente d'accéder
        $this->get("/api/animals/$id", $this->otherToken);
        $this->assertResponseStatusCodeSame(403);
    }

    // ── PUT /api/animals/{id} ────────────────────────────────────────────────

    public function testUpdateAnimalChangesFields(): void
    {
        $id = $this->createAnimal('Rex', 'Chien');

        $this->put("/api/animals/$id", ['nom' => 'Max', 'race' => 'Berger'], $this->authToken);

        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertSame('Max', $data['nom']);
        $this->assertSame('Berger', $data['race']);
        $this->assertSame('Chien', $data['espece']); // inchangé
    }

    public function testUpdateAnimalReturns403ForOtherUser(): void
    {
        $id = $this->createAnimal('Rex', 'Chien');

        $this->put("/api/animals/$id", ['nom' => 'Max'], $this->otherToken);
        $this->assertResponseStatusCodeSame(403);
    }

    // ── DELETE /api/animals/{id} ─────────────────────────────────────────────

    public function testDeleteAnimalReturns200AndRemovesIt(): void
    {
        $id = $this->createAnimal('Nemo', 'Poisson');

        $this->client->request('DELETE', "/api/animals/$id", [], [], $this->headers($this->authToken));
        $this->assertResponseStatusCodeSame(200);

        // Vérification : le GET retourne 404
        $this->get("/api/animals/$id", $this->authToken);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteAnimalReturns403ForOtherUser(): void
    {
        $id = $this->createAnimal('Bob', 'Lapin');

        $this->client->request('DELETE', "/api/animals/$id", [], [], $this->headers($this->otherToken));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetAnimalsListContainsCreatedAnimals(): void
    {
        $this->createAnimal('Rex', 'Chien');
        $this->createAnimal('Mimi', 'Chat');

        $this->get('/api/animals', $this->authToken);

        $this->assertCount(2, $this->json());
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function registerAndGetToken(string $email): string
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => 'motdepasse123', 'nom' => 'Test', 'prenom' => 'User'])
        );
        return json_decode($this->client->getResponse()->getContent(), true)['token'];
    }

    /** Crée un animal via l'API et retourne son id. */
    private function createAnimal(string $nom, string $espece): int
    {
        $this->post('/api/animals', ['nom' => $nom, 'espece' => $espece], $this->authToken);
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
