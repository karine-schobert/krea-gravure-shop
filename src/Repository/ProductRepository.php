<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ProductRepository
 *
 * Centralise les requêtes Doctrine pour l'entité Product.
 * Objectif : garder les Controllers simples et lisibles.
 *
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * ✅ Trouve 1 produit via son slug (avec sa catégorie join)
     * Utilisé typiquement par : GET /api/products/{slug}
     */
    public function findOneBySlug(string $slug): ?Product
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * ✅ Liste tous les produits (avec catégories), tri id DESC
     * Utilisé en admin / debug / endpoints internes
     *
     * @return Product[]
     */
    public function findAllWithCategoryDesc(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ✅ Liste tous les produits PUBLICS (actifs uniquement), tri id DESC
     * Utilisé typiquement par : GET /api/products
     *
     * @return Product[]
     */
    public function findAllActiveWithCategoryDesc(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ✅ Liste des produits PUBLICS (actifs) d'une catégorie (par slug), tri id DESC
     * Utilisé typiquement par : GET /api/categories/{slug}/products
     *
     * Exemple :
     *  - slug catégorie = "bijoux"
     *
     * @return Product[]
     */
    public function findAllActiveByCategorySlugDesc(string $categorySlug): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $categorySlug)
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}