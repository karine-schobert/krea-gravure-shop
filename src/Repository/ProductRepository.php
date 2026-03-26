<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ProductRepository
 *
 * Centralise les requêtes Doctrine pour l'entité Product.
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
     * Trouve 1 produit via son slug
     * avec ses relations utiles.
     */
    public function findOneBySlug(string $slug): ?Product
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.productCollection', 'pc')->addSelect('pc')
            ->leftJoin('p.additionalCategories', 'ac')->addSelect('ac')
            ->leftJoin('p.additionalCollections', 'apc')->addSelect('apc')
            ->leftJoin('p.offers', 'offers')->addSelect('offers')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Liste tous les produits avec catégories, tri id DESC
     *
     * @return Product[]
     */
    public function findAllWithCategoryDesc(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.productCollection', 'pc')->addSelect('pc')
            ->leftJoin('p.additionalCategories', 'ac')->addSelect('ac')
            ->leftJoin('p.additionalCollections', 'apc')->addSelect('apc')
            ->orderBy('p.id', 'DESC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste tous les produits publics (actifs uniquement), tri id DESC
     *
     * @return Product[]
     */
    public function findAllActiveWithCategoryDesc(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.productCollection', 'pc')->addSelect('pc')
            ->leftJoin('p.additionalCategories', 'ac')->addSelect('ac')
            ->leftJoin('p.additionalCollections', 'apc')->addSelect('apc')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.id', 'DESC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste des produits publics d'une catégorie,
     * via catégorie principale OU catégorie secondaire.
     *
     * @return Product[]
     */
    public function findAllActiveByCategorySlugDesc(string $categorySlug): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.additionalCategories', 'ac')->addSelect('ac')
            ->leftJoin('p.productCollection', 'pc')->addSelect('pc')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->andWhere('(c.slug = :slug OR ac.slug = :slug)')
            ->setParameter('slug', $categorySlug)
            ->orderBy('p.id', 'DESC')
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Produits actifs paginés, filtre optionnel par catégorie.
     * Cherche dans catégorie principale OU secondaire.
     *
     * @return array{items: Product[], total:int, page:int, limit:int, pages:int}
     */
    public function findActivePaginated(?string $categorySlug, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.additionalCategories', 'ac')
            ->leftJoin('p.productCollection', 'pc')->addSelect('pc')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->distinct();

        if ($categorySlug) {
            $qb->andWhere('(c.slug = :cslug OR ac.slug = :cslug)')
                ->setParameter('cslug', $categorySlug);
        }

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(DISTINCT p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->orderBy('p.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $pages = (int) max(1, (int) ceil($total / $limit));

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => $pages,
        ];
    }

    /**
     * Produits actifs pour la boutique,
     * filtre optionnel par catégorie principale OU secondaire.
     *
     * @return Product[]
     */
    public function findAllActiveForShop(?string $categorySlug = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')->addSelect('c')
            ->leftJoin('p.additionalCategories', 'ac')
            ->leftJoin('p.productCollection', 'pc')->addSelect('pc')
            ->leftJoin('p.additionalCollections', 'apc')->addSelect('apc')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.id', 'DESC')
            ->distinct();

        if ($categorySlug) {
            $qb
                ->andWhere('(c.slug = :category OR ac.slug = :category)')
                ->setParameter('category', $categorySlug);
        }

        return $qb->getQuery()->getResult();
    }
}