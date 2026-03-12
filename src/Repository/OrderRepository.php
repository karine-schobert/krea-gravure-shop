<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Retourne les commandes d'un utilisateur,
     * des plus récentes aux plus anciennes.
     *
     * @return Order[]
     */
    public function findByUserOrderedByNewest(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
        public function findOneByStripeSessionId(string $sessionId): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.stripeSessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByStripePaymentIntentId(string $paymentIntentId): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.stripePaymentIntentId = :paymentIntentId')
            ->setParameter('paymentIntentId', $paymentIntentId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}