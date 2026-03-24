<?php

namespace App\Controller\Api;

use App\Repository\ProductCollectionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/collections', name: 'api_collections_')]
class ProductCollectionApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ProductCollectionRepository $productCollectionRepository): JsonResponse
    {
        $collections = $productCollectionRepository->findActiveOrdered();

        $data = array_map(function ($collection) {
            return [
                'id' => $collection->getId(),
                'name' => $collection->getName(),
                'slug' => $collection->getSlug(),
                'description' => $collection->getDescription(),
                'image' => $collection->getImagePath(),
                'position' => $collection->getPosition(),
                'isActive' => $collection->isActive(),
                'createdAt' => $collection->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }, $collections);

        return $this->json($data);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug, ProductCollectionRepository $productCollectionRepository): JsonResponse
    {
        $collection = $productCollectionRepository->findOneActiveBySlug($slug);

        if (!$collection) {
            return $this->json([
                'message' => 'Collection introuvable.',
            ], 404);
        }

        $products = [];

        foreach ($collection->getProducts() as $product) {
            if (!$product->isActive()) {
                continue;
            }

            $products[] = [
                'id' => $product->getId(),
                'name' => $product->getTitle(),
                'slug' => $product->getSlug(),
                'description' => $product->getDescription(),
                'priceCents' => $product->getPriceCents(),
                'image' => $product->getImagePath(),
            ];
        }

        return $this->json([
            'id' => $collection->getId(),
            'name' => $collection->getName(),
            'slug' => $collection->getSlug(),
            'description' => $collection->getDescription(),
            'image' => $collection->getImagePath(),
            'position' => $collection->getPosition(),
            'isActive' => $collection->isActive(),
            'createdAt' => $collection->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'products' => $products,
        ]);
    }
}