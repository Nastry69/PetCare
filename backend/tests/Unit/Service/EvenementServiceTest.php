<?php

namespace App\Tests\Unit\Service;

use App\Entity\Animal;
use App\Entity\Evenement;
use App\Entity\TypeEvenement;
use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Repository\EvenementRepository;
use App\Repository\PartageAnimalRepository;
use App\Repository\TypeEvenementRepository;
use App\Service\EvenementService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires du service EvenementService.
 * Porte sur la validation, le contrôle d'accès et la sérialisation.
 */
class EvenementServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject    $em;
    private EvenementRepository&MockObject       $evenementRepository;
    private AnimalRepository&MockObject          $animalRepository;
    private TypeEvenementRepository&MockObject   $typeEvenementRepository;
    private PartageAnimalRepository&MockObject   $partageAnimalRepository;
    private EvenementService                     $service;

    protected function setUp(): void
    {
        $this->em                      = $this->createMock(EntityManagerInterface::class);
        $this->evenementRepository     = $this->createMock(EvenementRepository::class);
        $this->animalRepository        = $this->createMock(AnimalRepository::class);
        $this->typeEvenementRepository = $this->createMock(TypeEvenementRepository::class);
        $this->partageAnimalRepository = $this->createMock(PartageAnimalRepository::class);

        $this->service = new EvenementService(
            $this->em,
            $this->evenementRepository,
            $this->animalRepository,
            $this->typeEvenementRepository,
            $this->partageAnimalRepository
        );
    }

    // ── create() — validation ────────────────────────────────────────────────

    public function testCreateThrowsWhenRequiredFieldsMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les champs animal_id, type_evenement_id et dateHeureEvenement sont obligatoires.');

        // Seulement animal_id fourni, les autres champs manquent
        $this->service->create(['animal_id' => 1], new User());
    }

    public function testCreateThrowsWhenAnimalNotFound(): void
    {
        $this->animalRepository->method('find')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Animal introuvable.');

        $this->service->create($this->validPayload(), new User());
    }

    public function testCreateThrowsWhenUserHasNoWriteAccess(): void
    {
        // L'animal existe mais l'utilisateur n'a pas le droit d'écriture
        $this->animalRepository->method('find')->willReturn($this->makeAnimal());
        $this->partageAnimalRepository->method('canUserWriteAnimal')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Accès refusé à cet animal.');

        $this->service->create($this->validPayload(), new User());
    }

    public function testCreateThrowsWhenTypeEvenementNotFound(): void
    {
        $this->animalRepository->method('find')->willReturn($this->makeAnimal());
        $this->partageAnimalRepository->method('canUserWriteAnimal')->willReturn(true);
        $this->typeEvenementRepository->method('find')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Type d'événement introuvable.");

        $this->service->create($this->validPayload(), new User());
    }

    // ── create() — succès ────────────────────────────────────────────────────

    public function testCreateEvenementSuccessfully(): void
    {
        $owner  = $this->makeUser();
        $animal = $this->makeAnimal($owner);
        $type   = $this->makeTypeEvenement('Vaccin');

        $this->animalRepository->method('find')->willReturn($animal);
        $this->partageAnimalRepository->method('canUserWriteAnimal')->willReturn(true);
        $this->typeEvenementRepository->method('find')->willReturn($type);
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $evenement = $this->service->create([
            'animal_id'           => 1,
            'type_evenement_id'   => 1,
            'dateHeureEvenement'  => '2026-06-20 14:00:00',
            'statut'              => 'prevu',
            'rappelActif'         => true,
            'rappelJoursAvant'    => 3,
            'commentaire'         => 'Rappel annuel obligatoire',
        ], $owner);

        $this->assertInstanceOf(Evenement::class, $evenement);
        $this->assertSame('prevu', $evenement->getStatut());
        $this->assertTrue($evenement->isRappelActif());
        $this->assertSame(3, $evenement->getRappelJoursAvant());
        $this->assertSame('Rappel annuel obligatoire', $evenement->getCommentaire());
        $this->assertSame($animal, $evenement->getAnimal());
        $this->assertSame($type, $evenement->getTypeEvenement());
        $this->assertSame($owner, $evenement->getCreateur());
        $this->assertNotNull($evenement->getDateCreation());
    }

    public function testCreateUsesDefaultStatutWhenNotProvided(): void
    {
        $this->animalRepository->method('find')->willReturn($this->makeAnimal($this->makeUser()));
        $this->partageAnimalRepository->method('canUserWriteAnimal')->willReturn(true);
        $this->typeEvenementRepository->method('find')->willReturn($this->makeTypeEvenement());
        $this->em->method('persist');
        $this->em->method('flush');

        $evenement = $this->service->create($this->validPayload(), $this->makeUser());

        // Statut par défaut défini dans le service
        $this->assertSame('a_confirmer', $evenement->getStatut());
    }

    // ── delete() — contrôle d'accès ──────────────────────────────────────────

    public function testDeleteThrowsWhenEvenementNotFound(): void
    {
        $this->evenementRepository->method('find')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Événement introuvable.");

        $this->service->delete(999, new User());
    }

    public function testDeleteThrowsWhenUserHasNoWriteAccess(): void
    {
        $evenement = $this->makeEvenement();
        $this->evenementRepository->method('find')->willReturn($evenement);
        $this->partageAnimalRepository->method('canUserWriteAnimal')->willReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Accès refusé.');

        $this->service->delete(1, new User());
    }

    public function testDeleteCallsRemoveAndFlush(): void
    {
        $evenement = $this->makeEvenement();
        $this->evenementRepository->method('find')->willReturn($evenement);
        $this->partageAnimalRepository->method('canUserWriteAnimal')->willReturn(true);

        $this->em->expects($this->once())->method('remove')->with($evenement);
        $this->em->expects($this->once())->method('flush');

        $this->service->delete(1, $this->makeUser());
    }

    // ── serialize() ──────────────────────────────────────────────────────────

    public function testSerializeContainsNestedAnimalAndType(): void
    {
        $evenement = $this->makeEvenement();
        $result    = $this->service->serialize($evenement);

        // Structure imbriquée attendue par le frontend
        $this->assertArrayHasKey('animal', $result);
        $this->assertArrayHasKey('typeEvenement', $result);
        $this->assertSame('Rex', $result['animal']['nom']);
        $this->assertSame('Vaccin', $result['typeEvenement']['libelle']);
        $this->assertSame('prevu', $result['statut']);
        $this->assertFalse($result['rappelActif']);
    }

    public function testSerializeContainsAllExpectedKeys(): void
    {
        $result = $this->service->serialize($this->makeEvenement());

        $keys = ['id', 'dateHeureEvenement', 'statut', 'rappelJoursAvant', 'rappelActif',
                 'commentaire', 'dateCreation', 'dateModification', 'animal', 'typeEvenement', 'createurId'];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $result, "Clé '$key' absente de la sérialisation.");
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function validPayload(): array
    {
        return [
            'animal_id'          => 1,
            'type_evenement_id'  => 1,
            'dateHeureEvenement' => '2026-06-20 14:00:00',
        ];
    }

    private function makeUser(string $email = 'tristan@petcare.fr'): User
    {
        $user = new User();
        $user->setNom('Dzioch')->setPrenom('Tristan')->setEmail($email)->setRoles(['ROLE_USER']);
        return $user;
    }

    private function makeAnimal(?User $owner = null): Animal
    {
        $owner ??= $this->makeUser();
        $animal = new Animal();
        $animal->setNom('Rex')->setEspece('Chien')->setProprietaire($owner);
        return $animal;
    }

    private function makeTypeEvenement(string $libelle = 'Vaccin'): TypeEvenement
    {
        $type = new TypeEvenement();
        $type->setLibelle($libelle)->setCouleur('#8B5CF6');
        return $type;
    }

    private function makeEvenement(): Evenement
    {
        $owner    = $this->makeUser();
        $animal   = $this->makeAnimal($owner);
        $type     = $this->makeTypeEvenement();
        $evenement = new Evenement();
        $evenement
            ->setAnimal($animal)
            ->setTypeEvenement($type)
            ->setCreateur($owner)
            ->setDateHeureEvenement(new \DateTime('2026-06-20 14:00:00'))
            ->setStatut('prevu')
            ->setRappelActif(false)
            ->setDateCreation(new \DateTime());
        return $evenement;
    }
}
