<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Liste des techniciens
     */
    public function findTechniciens(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_TECHNICIEN%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Nombre de techniciens
     */
    public function countTechniciens(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_TECHNICIEN%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Nombre de clients
     */
    public function countClients(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_CLIENT%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Comptes anonymisés récemment (48h) — pour notification admin
     */
    public function findRecemmentAnonymises(int $heures = 48): array
    {
        $seuil = new \DateTimeImmutable("-{$heures} hours");

        return $this->createQueryBuilder('u')
            ->andWhere('u.date_anonymisation IS NOT NULL')
            ->andWhere('u.date_anonymisation >= :seuil')
            ->setParameter('seuil', $seuil)
            ->orderBy('u.date_anonymisation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
