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

    #[Route('/api/products', name: 'api_products_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // ✅ choisis l’une des deux lignes :
        // $products = $this->repo->findAllWithCategoryDesc(); // tout
        $products = $this->repo->findAllActiveWithCategoryDesc(); // public: actifs uniquement

        $base = $this->getBaseUrl();

        $data = array_map(fn(Product $p) => $this->toArray($p, $base), $products);

        return $this->json($data);
    }

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

    private function getBaseUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        // en CLI/tests, pas de requête HTTP
        if (!$request) {
            return '';
        }

        return $request->getSchemeAndHttpHost();
    }

    private function toArray(Product $p, string $base): array
    {
        $imagePath = $p->getImagePath();

        return [
            'id' => $p->getId(),
            'title' => $p->getTitle(),
            'slug' => $p->getSlug(),
            'priceCents' => $p->getPriceCents(),
            'isActive' => $p->isActive(),
            'category' => $p->getCategory()?->getName(),
            'categoryId' => $p->getCategory()?->getId(),
            'imageUrl' => $imagePath ? $base . $imagePath : null,
            'imagePath' => $imagePath,
        ];
    }
}