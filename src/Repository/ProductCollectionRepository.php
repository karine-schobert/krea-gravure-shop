<?php

namespace App\Repository;

use App\Entity\ProductCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductCollection::class);
    }

    /**
     * Retourne les collections actives triées par position puis nom.
     */
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('pc.position', 'ASC')
            ->addOrderBy('pc.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneActiveBySlug(string $slug): ?ProductCollection
    {
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.slug = :slug')
            ->andWhere('pc.isActive = :active')
            ->setParameter('slug', $slug)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}