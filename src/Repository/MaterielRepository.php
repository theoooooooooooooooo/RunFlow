<?php

namespace App\Repository;

use App\Entity\Materiel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Materiel>
 */
class MaterielRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Materiel::class);
    }

    /**
     * Matériels en stock critique (quantité <= seuil)
     */
    public function findStockCritique(int $seuil = 5): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.quantite_stock <= :seuil')
            ->setParameter('seuil', $seuil)
            ->orderBy('m.quantite_stock', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Nombre total de références en stock
     */
    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
