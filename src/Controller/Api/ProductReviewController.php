<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/products')]
class ProductReviewController extends AbstractController
{
    #[Route('/{id}/review', name: 'api_product_review', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createReview(
        Product $product,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {

        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['rating'])) {
            return $this->json([
                'error' => 'Note requise'
            ], 400);
        }

        /** empêcher plusieurs avis */
        $existingReview = $em->getRepository(Review::class)->findOneBy([
            'user' => $user,
            'product' => $product
        ]);

        if ($existingReview) {
            return $this->json([
                'error' => 'Vous avez déjà laissé un avis.'
            ], 400);
        }

        $review = new Review();
        $review->setUser($user);
        $review->setProduct($product);
        $review->setRating($data['rating']);
        $review->setComment($data['comment'] ?? '');
        $review->setCreatedAt(new \DateTimeImmutable());

        $em->persist($review);
        $em->flush();

        return $this->json([
            'message' => 'Avis ajouté'
        ]);
    }
}