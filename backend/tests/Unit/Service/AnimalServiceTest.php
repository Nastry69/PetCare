<?php

namespace App\Tests\Unit\Service;

use App\Entity\Animal;
use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Service\AnimalService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires du service AnimalService.
 * Toutes les dépendances (EntityManager, Repository) sont mockées —
 * aucune base de données n'est nécessaire.
 */
class AnimalServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private AnimalRepository&MockObject $animalRepository;
    private AnimalService $service;

    protected function setUp(): void
    {
        $this->em               = $this->createMock(EntityManagerInterface::class);
        $this->animalRepository = $this->createMock(AnimalRepository::class);

        // '/tmp' est utilisé comme projectDir fictif (inutilisé dans les tests simples)
        $this->service = new AnimalService($this->em, $this->animalRepository, '/tmp');
    }

    // ── create() ─────────────────────────────────────────────────────────────

    public function testCreateThrowsWhenNomIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les champs nom et espece sont obligatoires.');

        $this->service->create(['espece' => 'Chien'], new User());
    }

    public function testCreateThrowsWhenEspeceIsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les champs nom et espece sont obligatoires.');

        $this->service->create(['nom' => 'Rex'], new User());
    }

    public function testCreatePersistsAndFlushes(): void
    {
        // persist() et flush() doivent être appelés exactement une fois
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $user = $this->makeUser();
        $this->service->create(['nom' => 'Rex', 'espece' => 'Chien'], $user);
    }

    public function testCreateReturnsAnimalWithCorrectData(): void
    {
        $this->em->method('persist');
        $this->em->method('flush');

        $user   = $this->makeUser();
        $animal = $this->service->create([
            'nom'          => 'Mimi',
            'espece'       => 'Chat',
            'race'         => 'Siamois',
            'sexe'         => 'femelle',
            'dateNaissance' => '2022-03-15',
        ], $user);

        $this->assertInstanceOf(Animal::class, $animal);
        $this->assertSame('Mimi', $animal->getNom());
        $this->assertSame('Chat', $animal->getEspece());
        $this->assertSame('Siamois', $animal->getRace());
        $this->assertSame('femelle', $animal->getSexe());
        $this->assertSame($user, $animal->getProprietaire());
        $this->assertNotNull($animal->getDateNaissance());
    }

    public function testCreateWithOptionalFieldsNull(): void
    {
        $this->em->method('persist');
        $this->em->method('flush');

        $animal = $this->service->create(['nom' => 'Rex', 'espece' => 'Chien'], $this->makeUser());

        // Les champs optionnels doivent être null s'ils ne sont pas fournis
        $this->assertNull($animal->getRace());
        $this->assertNull($animal->getSexe());
        $this->assertNull($animal->getPhotoUrl());
        $this->assertNull($animal->getDateNaissance());
    }

    // ── update() ─────────────────────────────────────────────────────────────

    public function testUpdateThrowsWhenAnimalNotFound(): void
    {
        $this->animalRepository->method('find')->with(999)->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Animal introuvable.');

        $this->service->update(999, ['nom' => 'Rex'], $this->makeUser());
    }

    public function testUpdateThrowsWhenUserIsNotOwner(): void
    {
        $owner  = $this->makeUser('owner@petcare.fr');
        $animal = $this->makeAnimal($owner);

        $this->animalRepository->method('find')->with(1)->willReturn($animal);

        $anotherUser = $this->makeUser('other@petcare.fr');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Accès refusé.');

        $this->service->update(1, ['nom' => 'Max'], $anotherUser);
    }

    public function testUpdateFlushesWithPartialData(): void
    {
        $owner  = $this->makeUser();
        $animal = $this->makeAnimal($owner);

        $this->animalRepository->method('find')->with(1)->willReturn($animal);
        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->update(1, ['nom' => 'MaxUpdated'], $owner);

        $this->assertSame('MaxUpdated', $updated->getNom());
        // Les autres champs doivent être inchangés
        $this->assertSame('Chien', $updated->getEspece());
    }

    // ── delete() ─────────────────────────────────────────────────────────────

    public function testDeleteThrowsWhenAnimalNotFound(): void
    {
        $this->animalRepository->method('find')->with(999)->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Animal introuvable.');

        $this->service->delete(999, $this->makeUser());
    }

    public function testDeleteThrowsWhenUserIsNotOwner(): void
    {
        $owner  = $this->makeUser('owner@petcare.fr');
        $animal = $this->makeAnimal($owner);

        $this->animalRepository->method('find')->willReturn($animal);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Accès refusé. Seul le propriétaire peut supprimer cet animal.');

        $this->service->delete(1, $this->makeUser('other@petcare.fr'));
    }

    public function testDeleteCallsRemoveAndFlush(): void
    {
        $owner  = $this->makeUser();
        $animal = $this->makeAnimal($owner);

        $this->animalRepository->method('find')->willReturn($animal);
        $this->em->expects($this->once())->method('remove')->with($animal);
        $this->em->expects($this->once())->method('flush');

        $this->service->delete(1, $owner);
    }

    // ── serialize() ──────────────────────────────────────────────────────────

    public function testSerializeReturnsExpectedKeys(): void
    {
        $animal = $this->makeAnimal($this->makeUser());

        $result = $this->service->serialize($animal);

        $expectedKeys = ['id', 'nom', 'espece', 'race', 'dateNaissance', 'sexe', 'photoUrl', 'proprietaireId'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result, "La clé '$key' est absente de la sérialisation.");
        }
    }

    public function testSerializeNeverExposesSensitiveData(): void
    {
        $result = $this->service->serialize($this->makeAnimal($this->makeUser()));

        // Aucune donnée sensible du propriétaire (email, mot de passe) ne doit fuiter
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('email', $result);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(string $email = 'tristan@petcare.fr'): User
    {
        $user = new User();
        $user->setNom('Dzioch')->setPrenom('Tristan')->setEmail($email)->setRoles(['ROLE_USER']);
        return $user;
    }

    private function makeAnimal(User $owner, string $nom = 'Rex', string $espece = 'Chien'): Animal
    {
        $animal = new Animal();
        $animal->setNom($nom)->setEspece($espece)->setProprietaire($owner);
        return $animal;
    }
}
