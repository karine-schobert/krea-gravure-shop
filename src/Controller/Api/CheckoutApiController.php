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
    /**
     * Crée une session Stripe Checkout pour une commande existante.
     *
     * Conditions :
     * - utilisateur connecté
     * - commande appartenant à l'utilisateur
     * - statut PENDING_PAYMENT
     * - commande non vide
     */
    #[Route('/session/{id}', name: 'session', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createSession(
        Order $order,
        StripeCheckoutService $stripeCheckoutService,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur non authentifié',
            ], 401);
        }

        // Sécurité : la commande doit appartenir à l'utilisateur connecté
        if ($order->getUser() !== $user) {
            return $this->json([
                'error' => 'Commande non autorisée',
            ], 403);
        }

        // On n'autorise le paiement que sur une commande en attente
        if ($order->getStatus() !== Order::STATUS_PENDING_PAYMENT) {
            return $this->json([
                'error' => 'Statut de commande invalide',
            ], 400);
        }

        // Sécurité : impossible de payer une commande vide
        if ($order->getItems()->isEmpty()) {
            return $this->json([
                'error' => 'Commande vide',
            ], 400);
        }

        // Création de la session Stripe Checkout
        $session = $stripeCheckoutService->createCheckoutSession($order);

        // On sauvegarde l'id de session Stripe sur la commande
        $order->setStripeSessionId($session->id);
        $entityManager->flush();

        return $this->json([
            'message' => 'Session Stripe créée avec succès',
            'sessionId' => $session->id,
            'checkoutUrl' => $session->url,
        ]);
    }
}