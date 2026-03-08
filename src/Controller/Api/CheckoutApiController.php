<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\User;
use App\Service\StripeCheckoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/checkout', name: 'api_checkout_')]
class CheckoutApiController extends AbstractController
{
    #[Route('/session/{id}', name: 'session', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createSession(
        Order $order,
        StripeCheckoutService $stripeCheckoutService,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur non authentifié',
            ], 401);
        }

        // Sécurité : la commande doit appartenir à l'utilisateur
        if ($order->getUser() !== $user) {
            return $this->json([
                'error' => 'Commande non autorisée',
            ], 403);
        }

        // La commande doit être payable
        if ($order->getStatus() !== Order::STATUS_PENDING_PAYMENT) {
            return $this->json([
                'error' => 'Commande non payable',
            ], 400);
        }

        // Impossible de payer une commande vide
        if ($order->getItems()->isEmpty()) {
            return $this->json([
                'error' => 'Commande vide',
            ], 400);
        }

        /*
        Création session Stripe
        */
        $session = $stripeCheckoutService->createCheckoutSession($order);

        /*
        Sauvegarde session Stripe
        */
        $order->setStripeSessionId($session->id);

        $entityManager->flush();

        return $this->json([
            'message' => 'Session Stripe créée',
            'sessionId' => $session->id,
            'checkoutUrl' => $session->url
        ]);
    }
}