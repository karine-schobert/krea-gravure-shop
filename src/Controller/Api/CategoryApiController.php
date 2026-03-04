<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryApiController extends AbstractController
{
    /**
     * ✅ GET /api/categories
     * Liste toutes les catégories (id DESC)
     *
     * Retour :
     * [
     *   { id, name, slug, productsCount },
     *   ...
     * ]
     */
    #[Route('/api/categories', name: 'api_categories_list', methods: ['GET'])]
    public function list(CategoryRepository $repo): JsonResponse
    {
        $categories = $repo->findBy([], ['id' => 'DESC']);

        $data = array_map(function ($c) {
            /** @var Category $c */
            return [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'productsCount' => method_exists($c, 'getProducts') && $c->getProducts()
                    ? $c->getProducts()->count()
                    : 0,
            ];
        }, $categories);

        return $this->json($data);
    }

    /**
     * ✅ GET /api/categories/{slug}
     * Détail d'une catégorie via son slug
     *
     * Exemple :
     * /api/categories/bijoux
     *
     * Retour :
     * { id, name, slug, productsCount }
     */
    #[Route('/api/categories/{slug}', name: 'api_categories_show', methods: ['GET'])]
    public function show(string $slug, CategoryRepository $repo): JsonResponse
    {
        $category = $repo->findOneBy(['slug' => $slug]);

        if (!$category) {
            return $this->json(['message' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'productsCount' => method_exists($category, 'getProducts') && $category->getProducts()
                ? $category->getProducts()->count()
                : 0,
        ]);
    }

    /**
     * ✅ GET /api/categories/{slug}/products
     * Produits actifs d'une catégorie (via slug)
     *
     * Supporte pagination :
     * - ?page=1&limit=12
     *
     * Exemple :
     * /api/categories/bijoux/products?page=1&limit=12
     *
     * Retour :
     * {
     *   category: { id, name, slug },
     *   products: [
     *     { id, title, slug, priceCents, imageUrl, imagePath, category: {...} },
     *     ...
     *   ],
     *   meta: { total, page, limit, pages }
     * }
     */
    #[Route('/api/categories/{slug}/products', name: 'api_categories_products', methods: ['GET'])]
    public function productsBySlug(
        string $slug,
        CategoryRepository $categoryRepo,
        ProductRepository $productRepo,
        Request $request
    ): JsonResponse {
        $category = $categoryRepo->findOneBy(['slug' => $slug]);

        if (!$category) {
            return $this->json(['message' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        // ✅ Pagination (hors du array_map)
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 12);

        // ✅ Produits actifs paginés, filtrés par category slug
        $result = $productRepo->findActivePaginated($slug, $page, $limit);
        $products = $result['items'];

        $base = $request->getSchemeAndHttpHost();

        $items = array_map(function (Product $p) use ($base) {
            $imagePath = $p->getImagePath();
            $cat = $p->getCategory();

            return [
                'id' => $p->getId(),
                'title' => $p->getTitle(),
                'slug' => $p->getSlug(),
                'priceCents' => $p->getPriceCents(),
                'imageUrl' => $imagePath ? $base . $imagePath : null,
                'imagePath' => $imagePath,
                'category' => $cat ? [
                    'id' => $cat->getId(),
                    'name' => $cat->getName(),
                    'slug' => $cat->getSlug(),
                ] : null,
            ];
        }, $products);

        return $this->json([
            'category' => [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
            ],
            'items' => $items,
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'pages' => $result['pages'],
            ],
        ]);
    }
}