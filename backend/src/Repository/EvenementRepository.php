<?php

namespace App\Repository;

use App\Entity\Animal;
use App\Entity\Evenement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Requêtes Doctrine pour Evenement — inclut la logique de sélection des rappels du jour.
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /** @return Evenement[] */
    public function findByAnimal(Animal $animal): array
    {
        return $this->findBy(['animal' => $animal], ['dateHeureEvenement' => 'DESC']);
    }

    /** Events for animals accessible by this user (owned or shared) */
    public function findAccessibleByUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.animal', 'a')
            ->leftJoin('a.partageAnimals', 'p')
            ->where('a.proprietaire = :user')
            ->orWhere('p.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('e.dateHeureEvenement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Upcoming events for a user (from today onwards) */
    public function findUpcomingByUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.animal', 'a')
            ->leftJoin('a.partageAnimals', 'p')
            ->where('(a.proprietaire = :user OR p.utilisateur = :user)')
            ->andWhere('e.dateHeureEvenement >= :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.dateHeureEvenement', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les événements dont le rappel doit être envoyé aujourd'hui.
     *
     * Logique : le rappel se déclenche N jours avant la DATE du RDV (sans tenir
     * compte de l'heure). Le scheduler tourne chaque matin à 8h ; on compare
     * uniquement la date pour éviter tout problème de timezone ou de décalage
     * à la minute.
     * Ex. RDV le 30/05 avec rappelJoursAvant=2 → email envoyé le 28/05 à 8h.
     *
     * @return Evenement[]
     */
    public function findRappelsDuJour(): array
    {
        $now = new \DateTime();
        $inFiveMinutes = (clone $now)->modify('+5 minutes');

        error_log('REMINDER CHECK - now: ' . $now->format('Y-m-d H:i:s') . ' inFive: ' . $inFiveMinutes->format('Y-m-d H:i:s'));

        $candidates = $this->createQueryBuilder('e')
            ->where('e.rappelActif = true')
            ->andWhere('e.rappelEnvoye = false')
            ->andWhere('e.statut != :annule')
            ->andWhere('e.dateHeureEvenement > :now')
            ->setParameter('annule', 'annule')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        error_log('REMINDER CANDIDATES: ' . count($candidates));

        foreach ($candidates as $e) {
            $joursAvant = $e->getRappelJoursAvant() ?? 1;
            $reminderDateTime = (clone $e->getDateHeureEvenement())->modify("-{$joursAvant} days");
            error_log('Event: ' . $e->getDateHeureEvenement()->format('Y-m-d H:i:s') . ' reminderAt: ' . $reminderDateTime->format('Y-m-d H:i:s'));
        }

        return array_values(array_filter(
            $candidates,
            static function (Evenement $e) use ($now, $inFiveMinutes): bool {
                $joursAvant = $e->getRappelJoursAvant() ?? 1;
                $reminderDateTime = (clone $e->getDateHeureEvenement())
                    ->modify("-{$joursAvant} days");

                return $reminderDateTime >= $now && $reminderDateTime < $inFiveMinutes;
            }
        ));
    }
}
