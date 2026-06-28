<?php

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du SecurityController.
 * Utilise SQLite en mémoire et les vraies routes HTTP Symfony.
 *
 * Routes testées :
 *   POST /api/register
 *   POST /api/auth/login_check
 *   GET  /api/me          (protégée)
 *   PUT  /api/me          (protégée)
 *   DELETE /api/me        (protégée)
 *   POST /api/auth/forgot-password
 *   POST /api/auth/reset-password
 */
class SecurityControllerTest extends WebTestCase
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

    // ── POST /api/register ───────────────────────────────────────────────────

    public function testRegisterWithValidDataReturns201WithToken(): void
    {
        $this->post('/api/register', [
            'email'    => 'jean@petcare.fr',
            'password' => 'motdepasse123',
            'nom'      => 'Dupont',
            'prenom'   => 'Jean',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json();
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertSame('jean@petcare.fr', $data['user']['email']);
        $this->assertSame('Jean', $data['user']['prenom']);
    }

    public function testRegisterWithMissingFieldsReturns400(): void
    {
        $this->post('/api/register', ['email' => 'x@petcare.fr']);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterWithInvalidEmailReturns400(): void
    {
        $this->post('/api/register', [
            'email'    => 'pas-un-email',
            'password' => 'motdepasse123',
            'nom'      => 'Dupont',
            'prenom'   => 'Jean',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString('Email invalide', $this->json()['message']);
    }

    public function testRegisterWithShortPasswordReturns400(): void
    {
        $this->post('/api/register', [
            'email'    => 'test@petcare.fr',
            'password' => 'court',
            'nom'      => 'Dupont',
            'prenom'   => 'Jean',
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterWithDuplicateEmailReturns409(): void
    {
        $payload = ['email' => 'dupe@petcare.fr', 'password' => 'motdepasse123', 'nom' => 'Dup', 'prenom' => 'Jean'];
        $this->post('/api/register', $payload);
        $this->assertResponseStatusCodeSame(201);

        // Deuxième tentative avec le même email
        $this->post('/api/register', $payload);
        $this->assertResponseStatusCodeSame(409);
    }

    // ── POST /api/auth/login_check ───────────────────────────────────────────

    public function testLoginWithValidCredentialsReturnsToken(): void
    {
        $this->registerUser('login@petcare.fr', 'motdepasse123');

        $this->post('/api/auth/login_check', ['username' => 'login@petcare.fr', 'password' => 'motdepasse123']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayHasKey('token', $this->json());
    }

    public function testLoginWithWrongPasswordReturns401(): void
    {
        $this->registerUser('auth@petcare.fr', 'bonmotdepasse');

        $this->post('/api/auth/login_check', ['username' => 'auth@petcare.fr', 'password' => 'mauvais']);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginWithUnknownEmailReturns401(): void
    {
        $this->post('/api/auth/login_check', ['username' => 'inconnu@petcare.fr', 'password' => 'nimportequoi']);
        $this->assertResponseStatusCodeSame(401);
    }

    // ── GET /api/me ──────────────────────────────────────────────────────────

    public function testMeRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/me');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeWithValidTokenReturnsUserData(): void
    {
        $token = $this->registerUser('me@petcare.fr', 'motdepasse123');

        $this->client->request('GET', '/api/me', [], [], $this->authHeaders($token));

        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertSame('me@petcare.fr', $data['email']);
        $this->assertArrayNotHasKey('password', $data);
    }

    // ── DELETE /api/me ───────────────────────────────────────────────────────

    public function testDeleteMeRequiresAuthentication(): void
    {
        $this->client->request('DELETE', '/api/me');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteMeWithTokenDeletesAccount(): void
    {
        $token = $this->registerUser('delete@petcare.fr', 'motdepasse123');

        $this->client->request('DELETE', '/api/me', [], [], $this->authHeaders($token));

        $this->assertResponseStatusCodeSame(200);
        $this->assertStringContainsString('supprimé', $this->json()['message']);

        // Le compte ne doit plus être accessible
        $this->client->request('GET', '/api/me', [], [], $this->authHeaders($token));
        $this->assertResponseStatusCodeSame(401);
    }

    // ── POST /api/auth/forgot-password ──────────────────────────────────────

    public function testForgotPasswordWithKnownEmailReturns200(): void
    {
        $this->registerUser('reset@petcare.fr', 'ancienmdp123');

        $this->post('/api/auth/forgot-password', ['email' => 'reset@petcare.fr']);

        // Toujours 200, même si l'email existe (anti-énumération)
        $this->assertResponseStatusCodeSame(200);
        $this->assertStringContainsString('lien', $this->json()['message']);
    }

    public function testForgotPasswordWithUnknownEmailReturns200TooAntiEnumeration(): void
    {
        // OWASP A07 : même réponse pour un email inconnu
        $this->post('/api/auth/forgot-password', ['email' => 'inconnu@petcare.fr']);
        $this->assertResponseStatusCodeSame(200);
    }

    // ── POST /api/auth/reset-password ────────────────────────────────────────

    public function testResetPasswordWithMissingTokenReturns400(): void
    {
        $this->post('/api/auth/reset-password', ['newPassword' => 'nouveaumdp456']);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testResetPasswordWithShortPasswordReturns400(): void
    {
        $this->post('/api/auth/reset-password', ['token' => 'abc123', 'newPassword' => 'court']);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testResetPasswordWithInvalidTokenReturns400(): void
    {
        $this->post('/api/auth/reset-password', ['token' => 'token-invalide-inexistant', 'newPassword' => 'nouveaumdp456']);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testResetPasswordWithValidTokenUpdatesPassword(): void
    {
        // 1. Inscription
        $this->registerUser('tokenreset@petcare.fr', 'ancienmdp123');

        // 2. Demande de reset → le token est créé en base
        $this->post('/api/auth/forgot-password', ['email' => 'tokenreset@petcare.fr']);
        $this->assertResponseStatusCodeSame(200);

        // 3. Récupérer le token directement depuis la base (test uniquement)
        $user = $this->em->getRepository(\App\Entity\User::class)
            ->findOneBy(['email' => 'tokenreset@petcare.fr']);
        $this->assertNotNull($user->getResetToken(), 'Le token doit être défini après forgot-password');

        $token = $user->getResetToken();

        // 4. Réinitialiser avec le token valide
        $this->post('/api/auth/reset-password', ['token' => $token, 'newPassword' => 'nouveaumdp456']);
        $this->assertResponseIsSuccessful();

        // 5. L'ancien mot de passe ne doit plus fonctionner
        $this->post('/api/auth/login_check', ['username' => 'tokenreset@petcare.fr', 'password' => 'ancienmdp123']);
        $this->assertResponseStatusCodeSame(401);

        // 6. Le nouveau mot de passe fonctionne
        $this->post('/api/auth/login_check', ['username' => 'tokenreset@petcare.fr', 'password' => 'nouveaumdp456']);
        $this->assertResponseStatusCodeSame(200);

        // 7. Le token est bien invalidé après usage
        $this->em->refresh($user);
        $this->assertNull($user->getResetToken(), 'Le token doit être null après usage');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Effectue un POST JSON et retourne $this pour chaîner les assertions. */
    private function post(string $uri, array $payload): void
    {
        $this->client->request('POST', $uri, [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($payload));
    }

    /** Inscrit un utilisateur et retourne son JWT token. */
    private function registerUser(string $email, string $password): string
    {
        $this->post('/api/register', [
            'email'    => $email,
            'password' => $password,
            'nom'      => 'Test',
            'prenom'   => 'User',
        ]);
        return $this->json()['token'];
    }

    /** Retourne les server headers avec le token Bearer. */
    private function authHeaders(string $token): array
    {
        return [
            'CONTENT_TYPE'       => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ];
    }

    /** Décode la réponse JSON du dernier appel. */
    private function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
    }
}
