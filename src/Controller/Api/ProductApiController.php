<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Entity\ProductOffer;
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
     * Supporte :
     * - ?page=1&limit=200
     * - ?category=bijoux
     *
     * Retour :
     * { items: [...], meta: {...} }
     */
    #[Route('/api/products', name: 'api_products_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();

        $page = max(1, (int) ($request?->query->get('page', 1) ?? 1));
        $limit = max(1, (int) ($request?->query->get('limit', 200) ?? 200));
        $category = $request?->query->get('category');

        $result = $this->repo->findActivePaginated($category ?: null, $page, $limit);

        $base = $this->getBaseUrl();
        $items = array_map(
            fn(Product $p) => $this->toArray($p, $base, false),
            $result['items']
        );

        return $this->json([
            'items' => $items,
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'pages' => $result['pages'],
                'category' => $category ?: null,
            ],
        ]);
    }

    /**
     * ✅ GET /api/shop/products
     * Route dédiée à la boutique front
     * - sans pagination
     * - produits actifs uniquement
     * - filtre catégorie optionnel via ?category=bijoux
     */
    #[Route('/api/shop/products', name: 'api_shop_products_list', methods: ['GET'])]
    public function shopList(): JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        $category = $request?->query->get('category');

        $products = $this->repo->findAllActiveForShop($category ?: null);

        $base = $this->getBaseUrl();
        $items = array_map(
            fn(Product $p) => $this->toArray($p, $base, false),
            $products
        );

        return $this->json([
            'items' => $items,
            'meta' => [
                'total' => count($items),
                'category' => $category ?: null,
            ],
        ]);
    }

    /**
     * ✅ GET /api/products/{slug}
     * Détail d’un produit PUBLIC (actif uniquement), via son slug
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

        if (!$product || !$product->isActive()) {
            return $this->json(['message' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $base = $this->getBaseUrl();

        return $this->json($this->toArray($product, $base, true));
    }

    /**
     * Récupère l'URL de base (http://127.0.0.1:8000) pour construire imageUrl
     */
    private function getBaseUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request ? $request->getSchemeAndHttpHost() : '';
    }

    /**
     * Transforme un Product en tableau JSON stable pour le front (Next)
     */
    private function toArray(Product $p, string $base, bool $includeOffers = false): array
{
    $imagePath = $p->getImagePath();
    $cat = $p->getCategory();
    $productCollection = $p->getProductCollection();

    $data = [
        'id' => $p->getId(),
        'title' => $p->getTitle(),
        'slug' => $p->getSlug(),
        'priceCents' => $p->getPriceCents(),
        'description' => $p->getDescription(),

        'imageUrl' => $imagePath ? $base . str_replace('\\', '/', $imagePath) : null,
        'imagePath' => $imagePath ? str_replace('\\', '/', $imagePath) : null,

        'category' => $cat ? [
            'id' => $cat->getId(),
            'name' => $cat->getName(),
            'slug' => $cat->getSlug(),
        ] : null,

        'collection' => $productCollection ? [
            'id' => $productCollection->getId(),
            'name' => $productCollection->getName(),
            'slug' => $productCollection->getSlug(),
        ] : null,
    ];

    if ($includeOffers) {
        $offers = $p->getOffers()->toArray();

        usort(
            $offers,
            fn(ProductOffer $a, ProductOffer $b) => $a->getPosition() <=> $b->getPosition()
        );

        $offers = array_filter(
            $offers,
            fn(ProductOffer $offer) => $offer->isActive()
        );

        $data['offers'] = array_map(
            fn(ProductOffer $offer) => $this->offerToArray($offer),
            $offers
        );
    }

    return $data;
}

    /**
     * Transforme une offre commerciale en tableau JSON
     */
   /**
 * Transforme une offre commerciale en tableau JSON
 */
private function offerToArray(ProductOffer $offer): array
{
    return [
        'id' => $offer->getId(),
        'title' => $offer->getTitle(),
        'saleType' => $offer->getSaleType(),
        'quantity' => $offer->getQuantity(),
        'priceCents' => $offer->getPriceCents(),

        // Indique si l'offre peut recevoir une personnalisation
        'isCustomizable' => $offer->isCustomizable(),

        // Paramètres d'affichage du champ de personnalisation côté front
        'customizationLabel' => $offer->getCustomizationLabel(),
        'customizationPlaceholder' => $offer->getCustomizationPlaceholder(),
        'customizationMaxLength' => $offer->getCustomizationMaxLength(),
        'isCustomizationRequired' => $offer->isCustomizationRequired(),

        'isActive' => $offer->isActive(),
        'position' => $offer->getPosition(),
        'startsAt' => $offer->getStartsAt()?->format('Y-m-d H:i:s'),
        'endsAt' => $offer->getEndsAt()?->format('Y-m-d H:i:s'),
    ];
}
}