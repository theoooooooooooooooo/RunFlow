<?php

declare(strict_types=1);

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
     * Adresses déjà associées à ce client via ses interventions précédentes, pour lui permettre de
     * réutiliser une adresse existante plutôt que d'en ressaisir une nouvelle (voir
     * {@see \App\Controller\InterventionController::new()}).
     *
     * Le distinct() est nécessaire car la jointure sur les interventions renverrait sinon la même
     * adresse plusieurs fois si le client a plusieurs interventions à cette adresse.
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
