<?php

namespace App\Tests\Unit\Service;

use App\Entity\Animal;
use App\Entity\PartageAnimal;
use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Repository\PartageAnimalRepository;
use App\Repository\UserRepository;
use App\Service\PartageAnimalService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires du service PartageAnimalService.
 * Couvre la création, la détection de doublons, et la révocation de partage.
 */
class PartageAnimalServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject  $em;
    private PartageAnimalRepository&MockObject $partageAnimalRepository;
    private AnimalRepository&MockObject        $animalRepository;
    private UserRepository&MockObject          $userRepository;
    private PartageAnimalService               $service;

    protected function setUp(): void
    {
        $this->em                      = $this->createMock(EntityManagerInterface::class);
        $this->partageAnimalRepository = $this->createMock(PartageAnimalRepository::class);
        $this->animalRepository        = $this->createMock(AnimalRepository::class);
        $this->userRepository          = $this->createMock(UserRepository::class);

        $this->service = new PartageAnimalService(
            $this->em,
            $this->partageAnimalRepository,
            $this->animalRepository,
            $this->userRepository
        );
    }

    // ── create() — validation ────────────────────────────────────────────────

    public function testCreateThrowsWhenAnimalIdMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create(['utilisateur_id' => 2, 'rolePartage' => 'lecture']);
    }

    public function testCreateThrowsWhenUtilisateurIdMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create(['animal_id' => 1, 'rolePartage' => 'lecture']);
    }

    public function testCreateThrowsWhenRolePartageMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->create(['animal_id' => 1, 'utilisateur_id' => 2]);
    }

    public function testCreateThrowsWhenAnimalNotFound(): void
    {
        $this->animalRepository->method('find')->willReturn(null);
        $this->userRepository->method('find')->willReturn($this->makeUser());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Animal introuvable.');

        $this->service->create(['animal_id' => 999, 'utilisateur_id' => 1, 'rolePartage' => 'lecture']);
    }

    public function testCreateThrowsWhenUserNotFound(): void
    {
        $this->animalRepository->method('find')->willReturn($this->makeAnimal());
        $this->userRepository->method('find')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Utilisateur introuvable.');

        $this->service->create(['animal_id' => 1, 'utilisateur_id' => 999, 'rolePartage' => 'lecture']);
    }

    // ── create() — doublon ───────────────────────────────────────────────────

    public function testCreateThrowsWhenPartageAlreadyExists(): void
    {
        $this->animalRepository->method('find')->willReturn($this->makeAnimal());
        $this->userRepository->method('find')->willReturn($this->makeUser('invite@petcare.fr'));
        // Simule un partage déjà existant
        $this->partageAnimalRepository->method('findOneBy')->willReturn(new PartageAnimal());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Ce partage existe déjà.');

        $this->service->create(['animal_id' => 1, 'utilisateur_id' => 2, 'rolePartage' => 'lecture']);
    }

    // ── create() — succès ────────────────────────────────────────────────────

    public function testCreateReturnsPartageWithCorrectData(): void
    {
        $animal   = $this->makeAnimal();
        $invitee  = $this->makeUser('invite@petcare.fr');

        $this->animalRepository->method('find')->willReturn($animal);
        $this->userRepository->method('find')->willReturn($invitee);
        $this->partageAnimalRepository->method('findOneBy')->willReturn(null); // pas de doublon
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $partage = $this->service->create(['animal_id' => 1, 'utilisateur_id' => 2, 'rolePartage' => 'ecriture']);

        $this->assertInstanceOf(PartageAnimal::class, $partage);
        $this->assertSame('ecriture', $partage->getRolePartage());
        $this->assertSame($animal, $partage->getAnimal());
        $this->assertSame($invitee, $partage->getUtilisateur());
        // La date d'invitation doit être définie automatiquement
        $this->assertNotNull($partage->getDateInvitation());
        $this->assertInstanceOf(\DateTime::class, $partage->getDateInvitation());
    }

    public function testCreateLectureRoleIsValidValue(): void
    {
        $animal  = $this->makeAnimal();
        $invitee = $this->makeUser('invite@petcare.fr');

        $this->animalRepository->method('find')->willReturn($animal);
        $this->userRepository->method('find')->willReturn($invitee);
        $this->partageAnimalRepository->method('findOneBy')->willReturn(null);
        $this->em->method('persist');
        $this->em->method('flush');

        $partage = $this->service->create(['animal_id' => 1, 'utilisateur_id' => 2, 'rolePartage' => 'lecture']);

        $this->assertSame('lecture', $partage->getRolePartage());
    }

    // ── update() ─────────────────────────────────────────────────────────────

    public function testUpdateThrowsWhenPartageNotFound(): void
    {
        $this->partageAnimalRepository->method('find')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Partage introuvable.');

        $this->service->update(999, ['rolePartage' => 'ecriture']);
    }

    public function testUpdateChangesRole(): void
    {
        $partage = $this->makePartage('lecture');
        $this->partageAnimalRepository->method('find')->willReturn($partage);
        $this->em->expects($this->once())->method('flush');

        $updated = $this->service->update(1, ['rolePartage' => 'ecriture']);

        $this->assertSame('ecriture', $updated->getRolePartage());
    }

    // ── delete() ─────────────────────────────────────────────────────────────

    public function testDeleteThrowsWhenPartageNotFound(): void
    {
        $this->partageAnimalRepository->method('find')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Partage introuvable.');

        $this->service->delete(999);
    }

    public function testDeleteCallsRemoveAndFlush(): void
    {
        $partage = $this->makePartage();
        $this->partageAnimalRepository->method('find')->willReturn($partage);

        $this->em->expects($this->once())->method('remove')->with($partage);
        $this->em->expects($this->once())->method('flush');

        $this->service->delete(1);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeUser(string $email = 'tristan@petcare.fr'): User
    {
        $user = new User();
        $user->setNom('Dzioch')->setPrenom('Tristan')->setEmail($email)->setRoles(['ROLE_USER']);
        return $user;
    }

    private function makeAnimal(): Animal
    {
        $animal = new Animal();
        $animal->setNom('Rex')->setEspece('Chien')->setProprietaire($this->makeUser());
        return $animal;
    }

    private function makePartage(string $role = 'lecture'): PartageAnimal
    {
        $partage = new PartageAnimal();
        $partage->setAnimal($this->makeAnimal())
                ->setUtilisateur($this->makeUser('invite@petcare.fr'))
                ->setRolePartage($role)
                ->setDateInvitation(new \DateTime());
        return $partage;
    }
}
