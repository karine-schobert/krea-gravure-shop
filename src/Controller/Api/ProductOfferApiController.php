<?php

namespace App\Controller\Api;

use App\Entity\ProductOffer;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_products_')]
class ProductOfferApiController extends AbstractController
{
    #[Route('/{slug}/offers', name: 'offers', methods: ['GET'])]
    public function offers(string $slug, ProductRepository $productRepository): JsonResponse
    {
        $product = $productRepository->findOneBy(['slug' => $slug]);

        if (!$product) {
            return $this->json([
                'message' => 'Produit introuvable.',
            ], 404);
        }

        $now = new \DateTime();

        $offers = $product->getOffers()->filter(function (ProductOffer $offer) use ($now) {
            if (!$offer->isActive()) {
                return false;
            }

            if ($offer->getStartsAt() && $offer->getStartsAt() > $now) {
                return false;
            }

            if ($offer->getEndsAt() && $offer->getEndsAt() < $now) {
                return false;
            }

            return true;
        })->toArray();

        usort($offers, function (ProductOffer $a, ProductOffer $b) {
            return ($a->getPosition() ?? 0) <=> ($b->getPosition() ?? 0);
        });

        $data = array_map(function (ProductOffer $offer) {
            return [
                'id' => $offer->getId(),
                'title' => $offer->getTitle(),
                'saleType' => $offer->getSaleType(),
                'quantity' => $offer->getQuantity(),
                'priceCents' => $offer->getPriceCents(),
                'isCustomizable' => $offer->isCustomizable(),
                'isActive' => $offer->isActive(),
                'position' => $offer->getPosition(),
                'startsAt' => $offer->getStartsAt()?->format('Y-m-d H:i:s'),
                'endsAt' => $offer->getEndsAt()?->format('Y-m-d H:i:s'),
            ];
        }, $offers);

        return $this->json([
            'product' => [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'slug' => $product->getSlug(),
            ],
            'offers' => $data,
        ]);
    }
}