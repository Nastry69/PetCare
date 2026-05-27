<?php

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du TypeEvenementController.
 *
 * Routes testées :
 *   GET  /api/type-evenements
 *   POST /api/type-evenements
 *   PUT  /api/type-evenements/{id}
 */
class TypeEvenementControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private string $authToken;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);

        $tool  = new SchemaTool($this->em);
        $metas = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metas);
        $tool->createSchema($metas);

        $this->authToken = $this->registerAndGetToken('user@typevent.fr');
    }

    // ── GET /api/type-evenements ─────────────────────────────────────────────

    public function testGetTypeEvenementsRequiresAuth(): void
    {
        $this->client->request('GET', '/api/type-evenements');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetTypeEvenementsReturnsEmptyListInitially(): void
    {
        $this->get('/api/type-evenements', $this->authToken);
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->json());
    }

    public function testGetTypeEvenementsContainsCreatedType(): void
    {
        $this->post('/api/type-evenements', ['libelle' => 'Vétérinaire'], $this->authToken);

        $this->get('/api/type-evenements', $this->authToken);
        $data = $this->json();
        $this->assertCount(1, $data);
        $this->assertSame('Vétérinaire', $data[0]['libelle']);
    }

    // ── POST /api/type-evenements ────────────────────────────────────────────

    public function testCreateTypeEvenementRequiresAuth(): void
    {
        $this->post('/api/type-evenements', ['libelle' => 'Vaccin']);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateTypeEvenementWithValidDataReturns201(): void
    {
        $this->post('/api/type-evenements', [
            'libelle'     => 'Vaccin',
            'description' => 'Vaccination annuelle',
            'couleur'     => '#FF5733',
        ], $this->authToken);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json();
        $this->assertSame('Vaccin', $data['libelle']);
        $this->assertSame('Vaccination annuelle', $data['description']);
        $this->assertSame('#FF5733', $data['couleur']);
        $this->assertArrayHasKey('id', $data);
    }

    public function testCreateTypeEvenementWithOnlyLabelReturns201(): void
    {
        $this->post('/api/type-evenements', ['libelle' => 'Toilettage'], $this->authToken);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json();
        $this->assertSame('Toilettage', $data['libelle']);
        $this->assertNull($data['description']);
        $this->assertNull($data['couleur']);
    }

    public function testCreateTypeEvenementWithMissingLabelReturns400(): void
    {
        $this->post('/api/type-evenements', ['description' => 'sans libelle'], $this->authToken);
        $this->assertResponseStatusCodeSame(400);
    }

    // ── PUT /api/type-evenements/{id} ────────────────────────────────────────

    public function testUpdateTypeEvenementChangesLibelle(): void
    {
        $this->post('/api/type-evenements', ['libelle' => 'Vétérinaire'], $this->authToken);
        $id = $this->json()['id'];

        $this->put("/api/type-evenements/$id", ['libelle' => 'Toilettage'], $this->authToken);
        $this->assertResponseIsSuccessful();
        $this->assertSame('Toilettage', $this->json()['libelle']);
    }

    public function testUpdateTypeEvenementChangesCouleur(): void
    {
        $this->post('/api/type-evenements', ['libelle' => 'Vaccin'], $this->authToken);
        $id = $this->json()['id'];

        $this->put("/api/type-evenements/$id", ['couleur' => '#00FF00'], $this->authToken);
        $this->assertResponseIsSuccessful();
        $this->assertSame('#00FF00', $this->json()['couleur']);
        $this->assertSame('Vaccin', $this->json()['libelle']); // inchangé
    }

    public function testUpdateTypeEvenementReturns404WhenNotFound(): void
    {
        $this->put('/api/type-evenements/999999', ['libelle' => 'X'], $this->authToken);
        $this->assertResponseStatusCodeSame(404);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function registerAndGetToken(string $email): string
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => 'motdepasse123', 'nom' => 'Test', 'prenom' => 'User'])
        );
        return json_decode($this->client->getResponse()->getContent(), true)['token'];
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
