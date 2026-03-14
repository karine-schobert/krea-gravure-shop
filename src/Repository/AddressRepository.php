<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des adresses client.
 *
 * Rôle :
 * - récupérer les adresses d'un utilisateur
 * - retrouver une adresse appartenant à un utilisateur
 * - gérer la logique d'adresse par défaut
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    /**
     * Retourne toutes les adresses d'un utilisateur,
     * triées avec l'adresse par défaut en premier.
     *
     * @return Address[]
     */
    public function findByUserOrdered(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('a.isDefault', 'DESC')
            ->addOrderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne une adresse appartenant à un utilisateur.
     */
    public function findOneByIdAndUser(int $id, User $user): ?Address
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :id')
            ->andWhere('a.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retire le statut "par défaut" de toutes les adresses
     * d'un utilisateur.
     */
    public function clearDefaultForUser(User $user): void
    {
        $this->createQueryBuilder('a')
            ->update()
            ->set('a.isDefault', ':isDefault')
            ->andWhere('a.user = :user')
            ->setParameter('isDefault', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}