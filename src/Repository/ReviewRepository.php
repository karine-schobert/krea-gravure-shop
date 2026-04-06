<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * Sauvegarde un avis.
     */
    public function save(Review $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un avis.
     */
    public function remove(Review $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Retourne les avis approuvés d’un produit,
     * triés du plus récent au plus ancien.
     */
    public function findApprovedByProduct(Product $product): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.product = :product')
            ->andWhere('r.status = :status')
            ->setParameter('product', $product)
            ->setParameter('status', Review::STATUS_APPROVED)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d’avis approuvés d’un produit.
     */
    public function countApprovedByProduct(Product $product): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.product = :product')
            ->andWhere('r.status = :status')
            ->setParameter('product', $product)
            ->setParameter('status', Review::STATUS_APPROVED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule la moyenne des notes d’un produit
     * sur les avis approuvés uniquement.
     */
    public function getAverageRatingForProduct(Product $product): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS averageRating')
            ->andWhere('r.product = :product')
            ->andWhere('r.status = :status')
            ->setParameter('product', $product)
            ->setParameter('status', Review::STATUS_APPROVED)
            ->getQuery()
            ->getSingleScalarResult();

        if ($result === null) {
            return null;
        }

        return round((float) $result, 1);
    }
}