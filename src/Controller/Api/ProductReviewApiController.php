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
class ProductReviewApiController extends AbstractController
{
    #[Route('/{id}/review', name: 'api_product_review', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createReview(
        Product $product,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur non authentifié.'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'error' => 'JSON invalide.'
            ], 400);
        }

        $rating = $data['rating'] ?? null;
        $comment = trim((string) ($data['comment'] ?? ''));

        // Validation stricte de la note
        if (!is_numeric($rating)) {
            return $this->json([
                'error' => 'La note doit être un nombre.'
            ], 400);
        }

        $rating = (int) $rating;

        if ($rating < 1 || $rating > 5) {
            return $this->json([
                'error' => 'La note doit être comprise entre 1 et 5.'
            ], 400);
        }

        // Si tu veux rendre le commentaire obligatoire
        if ($comment === '') {
            return $this->json([
                'error' => 'Le commentaire est obligatoire.'
            ], 400);
        }

        // Empêche un utilisateur de laisser plusieurs avis sur le même produit
        $existingReview = $em->getRepository(Review::class)->findOneBy([
            'user' => $user,
            'product' => $product,
        ]);

        if ($existingReview) {
            return $this->json([
                'error' => 'Vous avez déjà laissé un avis sur ce produit.'
            ], 400);
        }

        $review = new Review();
        $review->setUser($user);
        $review->setProduct($product);
        $review->setRating($rating);
        $review->setComment($comment);
        $review->setCreatedAt(new \DateTimeImmutable());

        $em->persist($review);
        $em->flush();

        return $this->json([
            'message' => 'Avis ajouté avec succès.',
            'review' => [
                'id' => $review->getId(),
                'rating' => $review->getRating(),
                'comment' => $review->getComment(),
                'createdAt' => $review->getCreatedAt()?->format(DATE_ATOM),
                'user' => (string) $review->getUser(),
                'productId' => $product->getId(),
            ]
        ], 201);
    }
}