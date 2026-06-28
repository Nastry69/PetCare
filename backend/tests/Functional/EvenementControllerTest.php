<?php

namespace App\Tests\Functional;

use App\Entity\TypeEvenement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du EvenementController.
 *
 * Routes testées :
 *   GET    /api/evenements
 *   GET    /api/evenements/upcoming
 *   POST   /api/evenements
 *   GET    /api/evenements/{id}
 *   PUT    /api/evenements/{id}
 *   DELETE /api/evenements/{id}
 */
class EvenementControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private string $authToken;
    private string $otherToken;
    private int $typeId;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get(EntityManagerInterface::class);

        $tool  = new SchemaTool($this->em);
        $metas = $this->em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($metas);
        $tool->createSchema($metas);

        $this->authToken  = $this->registerAndGetToken('owner@evenement.fr');
        $this->otherToken = $this->registerAndGetToken('other@evenement.fr');

        // Crée un TypeEvenement directement en BDD (indépendant du TypeEvenementController)
        $type = new TypeEvenement();
        $type->setLibelle('Vétérinaire');
        $this->em->persist($type);
        $this->em->flush();
        $this->typeId = $type->getId();
    }

    // ── GET /api/evenements ──────────────────────────────────────────────────

    public function testGetEvenementsRequiresAuth(): void
    {
        $this->client->request('GET', '/api/evenements');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetEvenementsReturnsEmptyListInitially(): void
    {
        $this->get('/api/evenements', $this->authToken);
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->json());
    }

    public function testGetEvenementsListContainsCreatedEvenement(): void
    {
        $this->createEvenement();

        $this->get('/api/evenements', $this->authToken);
        $this->assertCount(1, $this->json());
    }

    // ── GET /api/evenements/upcoming ─────────────────────────────────────────

    public function testGetUpcomingRequiresAuth(): void
    {
        $this->client->request('GET', '/api/evenements/upcoming');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetUpcomingReturnsEmptyListInitially(): void
    {
        $this->get('/api/evenements/upcoming', $this->authToken);
        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $this->json());
    }

    // ── POST /api/evenements ─────────────────────────────────────────────────

    public function testCreateEvenementRequiresAuth(): void
    {
        $this->post('/api/evenements', []);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateEvenementWithValidDataReturns201(): void
    {
        $animalId = $this->createAnimal('Rex');

        $this->post('/api/evenements', [
            'animal_id'          => $animalId,
            'type_evenement_id'  => $this->typeId,
            'dateHeureEvenement' => '2027-01-15 10:00:00',
        ], $this->authToken);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json();
        $this->assertSame('a_confirmer', $data['statut']);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('animal', $data);
        $this->assertArrayHasKey('typeEvenement', $data);
    }

    public function testCreateEvenementWithMissingFieldsReturns400(): void
    {
        $this->post('/api/evenements', ['animal_id' => 1], $this->authToken);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateEvenementWithUnknownAnimalReturns404(): void
    {
        $this->post('/api/evenements', [
            'animal_id'          => 999999,
            'type_evenement_id'  => $this->typeId,
            'dateHeureEvenement' => '2027-01-15 10:00:00',
        ], $this->authToken);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateEvenementForbiddenForOtherUser(): void
    {
        $animalId = $this->createAnimal('Rex');

        $this->post('/api/evenements', [
            'animal_id'          => $animalId,
            'type_evenement_id'  => $this->typeId,
            'dateHeureEvenement' => '2027-01-15 10:00:00',
        ], $this->otherToken);
        $this->assertResponseStatusCodeSame(403);
    }

    // ── GET /api/evenements/{id} ─────────────────────────────────────────────

    public function testGetEvenementByIdReturnsData(): void
    {
        $id = $this->createEvenement();

        $this->get("/api/evenements/$id", $this->authToken);
        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('statut', $data);
    }

    public function testGetEvenementByIdReturns404WhenNotFound(): void
    {
        $this->get('/api/evenements/999999', $this->authToken);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetEvenementByIdReturns403ForOtherUser(): void
    {
        $id = $this->createEvenement();

        $this->get("/api/evenements/$id", $this->otherToken);
        $this->assertResponseStatusCodeSame(403);
    }

    // ── PUT /api/evenements/{id} ─────────────────────────────────────────────

    public function testUpdateEvenementChangesStatut(): void
    {
        $id = $this->createEvenement();

        $this->put("/api/evenements/$id", ['statut' => 'confirme'], $this->authToken);
        $this->assertResponseIsSuccessful();
        $this->assertSame('confirme', $this->json()['statut']);
    }

    public function testUpdateEvenementChangesCommentaire(): void
    {
        $id = $this->createEvenement();

        $this->put("/api/evenements/$id", ['commentaire' => 'Rappel vaccin annuel'], $this->authToken);
        $this->assertResponseIsSuccessful();
        $this->assertSame('Rappel vaccin annuel', $this->json()['commentaire']);
    }

    public function testUpdateEvenementReturns404WhenNotFound(): void
    {
        $this->put('/api/evenements/999999', ['statut' => 'confirme'], $this->authToken);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateEvenementReturns403ForOtherUser(): void
    {
        $id = $this->createEvenement();

        $this->put("/api/evenements/$id", ['statut' => 'confirme'], $this->otherToken);
        $this->assertResponseStatusCodeSame(403);
    }

    // ── DELETE /api/evenements/{id} ──────────────────────────────────────────

    public function testDeleteEvenementReturns200AndRemovesIt(): void
    {
        $id = $this->createEvenement();

        $this->client->request('DELETE', "/api/evenements/$id", [], [], $this->headers($this->authToken));
        $this->assertResponseStatusCodeSame(200);

        // Vérification : le GET retourne 404
        $this->get("/api/evenements/$id", $this->authToken);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteEvenementReturns404WhenNotFound(): void
    {
        $this->client->request('DELETE', '/api/evenements/999999', [], [], $this->headers($this->authToken));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteEvenementReturns403ForOtherUser(): void
    {
        $id = $this->createEvenement();

        $this->client->request('DELETE', "/api/evenements/$id", [], [], $this->headers($this->otherToken));
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

    private function createAnimal(string $nom): int
    {
        $this->post('/api/animals', ['nom' => $nom, 'espece' => 'Chien'], $this->authToken);
        return $this->json()['id'];
    }

    private function createEvenement(): int
    {
        $animalId = $this->createAnimal('TestAnimal');
        $this->post('/api/evenements', [
            'animal_id'          => $animalId,
            'type_evenement_id'  => $this->typeId,
            'dateHeureEvenement' => '2027-06-01 09:00:00',
        ], $this->authToken);
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
