<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProductApiController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $repo,
        private readonly RequestStack $requestStack,
    ) {}

    /**
     * ✅ GET /api/products
     * Liste des produits PUBLICS (actifs uniquement), tri id DESC
     *
     * Retour :
     * [
     *   {
     *     id, title, slug, priceCents,
     *     imageUrl,
     *     category: { id, name, slug }
     *   },
     *   ...
     * ]
     */
    #[Route('/api/products', name: 'api_products_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // Public = actifs uniquement
        $products = $this->repo->findAllActiveWithCategoryDesc();

        $base = $this->getBaseUrl();

        $data = array_map(
            fn(Product $p) => $this->toArray($p, $base),
            $products
        );

        return $this->json($data);
    }

    /**
     * ✅ GET /api/products/{slug}
     * Détail d’un produit PUBLIC (actif uniquement), via son slug
     *
     * Exemple :
     * /api/products/boucle-d-oreille-chic-noir-aile-boisee
     *
     * Retour :
     * {
     *   id, title, slug, priceCents,
     *   imageUrl,
     *   category: { id, name, slug }
     * }
     *
     * ⚠️ On renvoie 404 si :
     * - slug inconnu
     * - produit inactif (caché en public)
     */
    #[Route(
        '/api/products/{slug}',
        name: 'api_products_show',
        methods: ['GET'],
        requirements: ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*']
    )]
    public function show(string $slug): JsonResponse
    {
        $product = $this->repo->findOneBySlug($slug);

        // ✅ on cache aussi les inactifs en public
        if (!$product || !$product->isActive()) {
            return $this->json(['message' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $base = $this->getBaseUrl();

        return $this->json($this->toArray($product, $base));
    }

    /**
     * Récupère l'URL de base (http://127.0.0.1:8000) pour construire imageUrl
     * En CLI/tests, pas de requête HTTP => renvoie '' (sécurité)
     */
    private function getBaseUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request ? $request->getSchemeAndHttpHost() : '';
    }

    /**
     * Transforme un Product en tableau JSON stable pour le front (Next)
     */
    private function toArray(Product $p, string $base): array
    {
        $imagePath = $p->getImagePath();
        $cat = $p->getCategory();

        return [
            'id' => $p->getId(),
            'title' => $p->getTitle(),
            'slug' => $p->getSlug(),
            'priceCents' => $p->getPriceCents(),

            // Images
            'imageUrl' => $imagePath ? $base . $imagePath : null,
            'imagePath' => $imagePath,

            // Category objet (cohérent avec /api/categories/{slug}/products)
            'category' => $cat ? [
                'id' => $cat->getId(),
                'name' => $cat->getName(),
                'slug' => $cat->getSlug(),
            ] : null,

            // 🔒 optionnel en public (à éviter si tu veux une API clean)
            // 'isActive' => $p->isActive(),
        ];
    }
}