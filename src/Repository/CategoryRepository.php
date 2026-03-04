<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CategoryRepository
 *
 * Centralise les requêtes Doctrine pour l'entité Category.
 *
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * ✅ Récupère une catégorie via son slug
     * Utilisé typiquement par :
     * - GET /api/categories/{slug}
     * - GET /api/categories/{slug}/products
     */
    public function findOneBySlug(string $slug): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * ✅ Liste toutes les catégories triées par id DESC
     * Utilisé typiquement par : GET /api/categories
     *
     * @return Category[]
     */
    public function findAllDesc(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}