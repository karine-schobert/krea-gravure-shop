<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products')]
class ProductReviewApiController extends AbstractController
{
    #[Route('/{slug}/reviews', name: 'api_product_reviews_list', methods: ['GET'])]
    public function listByProduct(
        string $slug,
        ProductRepository $productRepository,
        ReviewRepository $reviewRepository
    ): JsonResponse {
        $product = $productRepository->findOneBy(['slug' => $slug]);

        if (!$product) {
            return $this->json([
                'message' => 'Produit introuvable.',
            ], 404);
        }

        $reviews = $reviewRepository->findApprovedByProduct($product);
        $reviewCount = $reviewRepository->countApprovedByProduct($product);
        $averageRating = $reviewRepository->getAverageRatingForProduct($product);

        return $this->json([
            'productId' => $product->getId(),
            'productSlug' => $product->getSlug(),
            'averageRating' => $averageRating,
            'reviewCount' => $reviewCount,
            'reviews' => array_map(
                static function ($review) {
                    return [
                        'id' => $review->getId(),
                        'rating' => $review->getRating(),
                        'comment' => $review->getComment(),
                        'status' => $review->getStatus(),
                        'createdAt' => $review->getCreatedAt()?->format(\DateTimeInterface::ATOM),
                        'updatedAt' => $review->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
                        'author' => [
                            /**
                             * Pour l’instant on expose un prénom/nom simplifié.
                             * Si ton User n’a pas getFirstname()/getLastname(),
                             * on adaptera juste après selon ta vraie entité.
                             */
                            'displayName' => method_exists($review->getUser(), 'getFirstname')
                                ? $review->getUser()->getFirstname()
                                : 'Client',
                        ],
                    ];
                },
                $reviews
            ),
        ]);
    }
}