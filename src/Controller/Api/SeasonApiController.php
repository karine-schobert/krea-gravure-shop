<?php

namespace App\Controller\Api;

use App\Repository\SeasonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class SeasonApiController extends AbstractController
{
    #[Route('/api/seasons', name: 'api_seasons', methods: ['GET'])]
    public function index(SeasonRepository $seasonRepository): JsonResponse
    {
        $seasons = $seasonRepository->findAll();

        $data = [];

        foreach ($seasons as $season) {
            $data[] = [
                'id' => $season->getId(),
                'name' => $season->getName(),
                'slug' => $season->getSlug(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/api/seasons/{slug}', name: 'api_season_show', methods: ['GET'])]
    public function show(string $slug, SeasonRepository $seasonRepository): JsonResponse
    {
        $season = $seasonRepository->findOneBy(['slug' => $slug]);

        if (!$season) {
            return $this->json(['error' => 'Season not found'], 404);
        }

        return $this->json([
            'id' => $season->getId(),
            'name' => $season->getName(),
            'slug' => $season->getSlug(),
        ]);
    }

    #[Route('/api/seasons/{slug}/products', name: 'api_season_products', methods: ['GET'])]
    public function products(string $slug, SeasonRepository $seasonRepository): JsonResponse
    {
        $season = $seasonRepository->findOneBy(['slug' => $slug]);

        if (!$season) {
            return $this->json(['error' => 'Season not found'], 404);
        }

        $products = $season->getProducts();

        $data = [];

        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'slug' => $product->getSlug(),
                'price' => $product->getPriceCents(),
                'image' => $product->getImagePath(),
                'category' => $product->getCategory()?->getName(),
            ];
        }

        return $this->json([
            'season' => [
                'name' => $season->getName(),
                'slug' => $season->getSlug(),
            ],
            'products' => $data,
        ]);
    }
}