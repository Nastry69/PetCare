<?php

namespace App\Repository;

use App\Entity\InvitationEnAttente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvitationEnAttenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvitationEnAttente::class);
    }

    /** @return InvitationEnAttente[] */
    public function findByEmail(string $email): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.email = :email')
            ->setParameter('email', strtolower(trim($email)))
            ->getQuery()
            ->getResult();
    }
}
