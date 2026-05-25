<?php

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels de la sécurité JWT.
 * Vérifie que les routes protégées rejettent les requêtes non authentifiées
 * et acceptent les tokens valides.
 */
class JwtSecurityTest extends WebTestCase
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

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ── Routes protégées sans token ──────────────────────────────────────────

    public function testGetMeWithoutTokenReturns401(): void
    {
        $this->client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testPutMeWithoutTokenReturns401(): void
    {
        $this->client->request('PUT', '/api/me', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteMeWithoutTokenReturns401(): void
    {
        $this->client->request('DELETE', '/api/me');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetAnimalsWithoutTokenReturns401(): void
    {
        $this->client->request('GET', '/api/animals');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testPostAnimalsWithoutTokenReturns401(): void
    {
        $this->client->request('POST', '/api/animals', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $this->assertResponseStatusCodeSame(401);
    }

    // ── Token invalide ───────────────────────────────────────────────────────

    public function testGetMeWithGarbageTokenReturns401(): void
    {
        $this->client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer token.invalide.ici',
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetMeWithExpiredStyleTokenReturns401(): void
    {
        // JWT avec signature incorrecte
        $fakeJwt = base64_encode('{"alg":"RS256"}') . '.' . base64_encode('{"email":"x@x.fr"}') . '.fakesignature';

        $this->client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $fakeJwt,
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    // ── Routes publiques accessibles sans token ──────────────────────────────

    public function testRegisterIsAccessibleWithoutToken(): void
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'pub@petcare.fr', 'password' => 'motdepasse123', 'nom' => 'Pub', 'prenom' => 'Test'])
        );
        // 201 ou 400, mais surtout pas 401
        $this->assertNotSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testLoginCheckIsAccessibleWithoutToken(): void
    {
        $this->client->request('POST', '/api/auth/login_check', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'x@x.fr', 'password' => 'x'])
        );
        $this->assertNotSame(401, $this->client->getResponse()->getStatusCode());
    }

    public function testResetPasswordIsAccessibleWithoutToken(): void
    {
        $this->client->request('POST', '/api/auth/reset-password', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'x@x.fr', 'newPassword' => 'nouveaumdp123'])
        );
        $this->assertNotSame(401, $this->client->getResponse()->getStatusCode());
    }

    // ── Token valide ─────────────────────────────────────────────────────────

    public function testGetMeWithValidTokenReturnsUserData(): void
    {
        // Inscription → token réel
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'valid@petcare.fr', 'password' => 'motdepasse123', 'nom' => 'Valid', 'prenom' => 'User'])
        );
        $this->assertResponseStatusCodeSame(201);
        $token = json_decode($this->client->getResponse()->getContent(), true)['token'];

        // Accès à /api/me avec le token
        $this->client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('valid@petcare.fr', $data['email']);
    }

    public function testTokenFromLoginCheckGrantsAccess(): void
    {
        // Inscription
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'token@petcare.fr', 'password' => 'motdepasse123', 'nom' => 'Tok', 'prenom' => 'En'])
        );

        // Connexion → nouveau token
        $this->client->request('POST', '/api/auth/login_check', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'token@petcare.fr', 'password' => 'motdepasse123'])
        );
        $loginToken = json_decode($this->client->getResponse()->getContent(), true)['token'];

        // Ce token doit aussi fonctionner sur /api/me
        $this->client->request('GET', '/api/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginToken,
        ]);
        $this->assertResponseIsSuccessful();
    }
}
