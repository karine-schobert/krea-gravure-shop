<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ProductApiController extends AbstractController
{
    #[Route('/api/products', name: 'api_products_list', methods: ['GET'])]
    public function list(ProductRepository $repo, Request $request): JsonResponse
    {
        $products = $repo->findBy([], ['id' => 'DESC']);

        $base = $request->getSchemeAndHttpHost();

        $data = array_map(function ($p) use ($base) {
            /** @var \App\Entity\Product $p */
            return [
                'id' => $p->getId(),
                'title' => $p->getTitle(),
                'slug' => $p->getSlug(),
                'priceCents' => $p->getPriceCents(),
                'isActive' => $p->isActive(),
                'category' => $p->getCategory()?->getName(),
                'categoryId' => $p->getCategory()?->getId(),
                // URL absolue (pratique côté front)
                'imageUrl' => $p->getImagePath() ? $base . $p->getImagePath() : null,
                // URL relative (pratique aussi)
                'imagePath' => $p->getImagePath(),
            ];
        }, $products);

        return $this->json($data);
    }
}