<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\Review\ReviewCreator;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * API de création d'avis côté compte client.
 */
#[Route('/api/account/reviews', name: 'api_account_reviews_')]
class ReviewApiController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        ReviewCreator $reviewCreator,
    ): JsonResponse {
        /**
         * On sécurise l'accès :
         * seul un utilisateur connecté peut créer un avis.
         */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Authentification requise.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        /**
         * On récupère le JSON envoyé par le front.
         */
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'message' => 'Payload JSON invalide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $orderItemId = isset($data['orderItemId']) ? (int) $data['orderItemId'] : 0;
        $rating = isset($data['rating']) ? (int) $data['rating'] : 0;
        $comment = isset($data['comment']) ? (string) $data['comment'] : '';

        try {
            $review = $reviewCreator->createFromOrderItem(
                $user,
                $orderItemId,
                $rating,
                $comment,
            );

            return $this->json([
                'message' => 'Avis créé avec succès.',
                'review' => [
                    'id' => $review->getId(),
                    'productId' => $review->getProduct()?->getId(),
                    'orderItemId' => $review->getOrderItem()?->getId(),
                    'rating' => $review->getRating(),
                    'comment' => $review->getComment(),
                    'status' => $review->getStatus(),
                    'createdAt' => $review->getCreatedAt()?->format(DATE_ATOM),
                ],
            ], Response::HTTP_CREATED);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (AccessDeniedException $e) {
            return $this->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        }
    }
}