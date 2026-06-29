<?php

namespace App\Repository;

use App\Entity\Intervention;
use App\Entity\Utilisateur;
use App\Enum\StatutInterventionEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Intervention>
 */
class InterventionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Intervention::class);
    }

    /**
     * Nombre d'interventions par statut (pour stats admin)
     */
    public function countByStatut(): array
    {
        $results = $this->createQueryBuilder('i')
            ->select('i.statut, COUNT(i.id) as total')
            ->groupBy('i.statut')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['statut']->value] = (int) $row['total'];
        }
        return $counts;
    }

    /**
     * Dernières interventions (pour dashboard admin)
     */
    public function findDernieres(int $limit = 5): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.date_demande', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Interventions en attente (admin)
     */
    public function findEnAttente(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.statut = :statut')
            ->setParameter('statut', StatutInterventionEnum::EN_ATTENTE)
            ->orderBy('i.date_demande', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Interventions du client connecté
     */
    public function findByClient(Utilisateur $client): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.client = :client')
            ->setParameter('client', $client)
            ->orderBy('i.date_demande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Interventions du technicien connecté (actives)
     */
    public function findByTechnicien(Utilisateur $technicien): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.technicien = :technicien')
            ->setParameter('technicien', $technicien)
            ->andWhere('i.statut NOT IN (:statuts)')
            ->setParameter('statuts', [StatutInterventionEnum::TERMINEE, StatutInterventionEnum::ANNULEE])
            ->orderBy('i.date_planifiee', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Historique des interventions du technicien (terminées)
     */
    public function findHistoriqueTechnicien(Utilisateur $technicien): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.technicien = :technicien')
            ->setParameter('technicien', $technicien)
            ->andWhere('i.statut = :statut')
            ->setParameter('statut', StatutInterventionEnum::TERMINEE)
            ->orderBy('i.date_planifiee', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par le status demande
     */
    public function findByStatutString(string $statut): array
    {
        $statutEnum = StatutInterventionEnum::from($statut);

        return $this->createQueryBuilder('i')
            ->andWhere('i.statut = :statut')
            ->setParameter('statut', $statutEnum)
            ->orderBy('i.date_demande', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
