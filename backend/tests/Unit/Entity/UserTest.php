<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Animal;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de l'entité User.
 * Vérifie les règles métier directement portées par l'entité.
 */
class UserTest extends TestCase
{
    // ── Rôles ────────────────────────────────────────────────────────────────

    public function testGetRolesAlwaysIncludesRoleUser(): void
    {
        $user = new User();
        // Même sans rôle explicite, ROLE_USER doit toujours être présent
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testGetRolesDeduplicatesRoleUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']); // ROLE_USER déjà présent

        $roles = $user->getRoles();

        // Pas de doublon : ROLE_USER ne doit apparaître qu'une fois
        $this->assertSame(count($roles), count(array_unique($roles)));
    }

    public function testGetRolesPreservesAdminRole(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    // ── Identifiant JWT ───────────────────────────────────────────────────────

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = new User();
        $user->setEmail('tristandzioch@hotmail.fr')->setNom('Dzioch')->setPrenom('Tristan');

        // Symfony Security utilise getUserIdentifier() pour le JWT
        $this->assertSame('tristandzioch@hotmail.fr', $user->getUserIdentifier());
    }

    // ── Date d'inscription ────────────────────────────────────────────────────

    public function testDateInscriptionIsSetAutomaticallyInConstructor(): void
    {
        $before = new \DateTimeImmutable();
        $user   = new User();
        $after  = new \DateTimeImmutable();

        $date = $user->getDateInscription();

        $this->assertNotNull($date);
        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        // La date doit être entre $before et $after
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $date->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $date->getTimestamp());
    }

    // ── Relations (Animal) ────────────────────────────────────────────────────

    public function testAddAnimalSetsProprietaireBidirectionally(): void
    {
        $user = new User();
        $user->setNom('Dzioch')->setPrenom('Tristan')->setEmail('tristandzioch@hotmail.fr');

        $animal = new Animal();
        $animal->setNom('Rex')->setEspece('Chien');

        $user->addAnimal($animal);

        // Côté User : l'animal est dans la collection
        $this->assertTrue($user->getAnimals()->contains($animal));
        // Côté Animal : le propriétaire est bien défini (synchronisation bidirectionnelle)
        $this->assertSame($user, $animal->getProprietaire());
    }

    public function testAddAnimalDoesNotAddDuplicate(): void
    {
        $user = new User();
        $user->setNom('Dzioch')->setPrenom('Tristan')->setEmail('tristandzioch@hotmail.fr');

        $animal = new Animal();
        $animal->setNom('Rex')->setEspece('Chien');

        $user->addAnimal($animal);
        $user->addAnimal($animal); // ajout en double

        $this->assertCount(1, $user->getAnimals());
    }

    public function testRemoveAnimalClearsProprietaire(): void
    {
        $user = new User();
        $user->setNom('Dzioch')->setPrenom('Tristan')->setEmail('tristandzioch@hotmail.fr');

        $animal = new Animal();
        $animal->setNom('Rex')->setEspece('Chien');

        $user->addAnimal($animal);
        $user->removeAnimal($animal);

        $this->assertFalse($user->getAnimals()->contains($animal));
        $this->assertNull($animal->getProprietaire());
    }

    // ── Fluent setters (style chaînable) ──────────────────────────────────────

    public function testSettersReturnStaticForChaining(): void
    {
        $user = new User();

        // Tous les setters doivent retourner $this pour le chaînage
        $result = $user
            ->setNom('Dzioch')
            ->setPrenom('Tristan')
            ->setEmail('tristan@petcare.fr')
            ->setRoles(['ROLE_USER'])
            ->setPhotoUrl(null);

        $this->assertSame($user, $result);
    }
}
