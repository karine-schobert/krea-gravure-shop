<?php

namespace App\Controller\Api;

use App\Entity\Product;
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
            /** @var \App\Entity\Category $c */
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
     * Exemple :
     * /api/categories/bijoux/products
     *
     * Retour :
     * {
     *   category: { id, name, slug },
     *   products: [
     *     { id, title, slug, priceCents, imageUrl, imagePath, category: {...} },
     *     ...
     *   ]
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

        // Produits actifs de cette catégorie (méthode repo qu'on a ajoutée)
        $products = $productRepo->findAllActiveByCategorySlugDesc($slug);

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
            'products' => $items,
        ]);
    }
}