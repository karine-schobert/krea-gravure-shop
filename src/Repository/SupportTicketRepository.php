<?php

namespace App\Repository;

use App\Entity\SupportTicket;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportTicket>
 */
class SupportTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportTicket::class);
    }

    /**
     * Retourne les tickets d'un utilisateur,
     * du plus récent au plus ancien.
     *
     * @return SupportTicket[]
     */
    public function findByUserOrderedByNewest(User $user): array
    {
        return $this->createQueryBuilder('ticket')
            ->leftJoin('ticket.order', 'o')
            ->addSelect('o')
            ->andWhere('ticket.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ticket.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByIdAndUser(int $ticketId, User $user): ?SupportTicket
    {
        return $this->createQueryBuilder('ticket')
            ->leftJoin('ticket.order', 'o')
            ->addSelect('o')
            ->andWhere('ticket.id = :ticketId')
            ->andWhere('ticket.user = :user')
            ->setParameter('ticketId', $ticketId)
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}