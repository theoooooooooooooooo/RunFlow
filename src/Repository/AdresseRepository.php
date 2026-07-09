<?php

namespace App\Repository;

use App\Entity\Adresse;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Adresse>
 */
class AdresseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Adresse::class);
    }

    /**
     * Adresses déjà utilisées par ce client dans ses interventions précédentes
     */
    public function findByClient(Utilisateur $client): array
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.interventions', 'i')
            ->andWhere('i.client = :client')
            ->setParameter('client', $client)
            ->distinct()
            ->getQuery()
            ->getResult();
    }
}
