<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session as StripeSession;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/checkout', name: 'api_checkout_')]
class CheckoutConfirmController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(STRIPE_SECRET_KEY)%')]
        private readonly string $stripeSecretKey
    ) {
    }

    #[Route('/confirm/{id}', name: 'confirm', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function confirm(
        Order $order,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur non authentifié',
            ], 401);
        }

        if ($order->getUser() !== $user) {
            return $this->json([
                'error' => 'Commande non autorisée',
            ], 403);
        }

        if ($order->getStatus() === Order::STATUS_PAID) {
            return $this->json([
                'message' => 'Commande déjà payée',
                'status' => $order->getStatus(),
            ]);
        }

        $sessionId = $order->getStripeSessionId();

        if (!$sessionId) {
            return $this->json([
                'error' => 'Aucune session Stripe liée à cette commande',
            ], 400);
        }

        $stripe = new StripeClient($this->stripeSecretKey);

        /** @var StripeSession $session */
        $session = $stripe->checkout->sessions->retrieve($sessionId, []);

        if (($session->payment_status ?? null) !== 'paid') {
            return $this->json([
                'message' => 'Paiement non confirmé',
                'stripe_payment_status' => $session->payment_status ?? null,
                'order_status' => $order->getStatus(),
            ], 400);
        }

        $paymentIntent = $session->payment_intent ?? null;

        $order->setStatus(Order::STATUS_PAID);
        $order->setPaidAt(new \DateTimeImmutable());
        $order->setUpdatedAt(new \DateTimeImmutable());
        $order->setStripePaymentIntentId(is_string($paymentIntent) ? $paymentIntent : null);

        $em->flush();

        return $this->json([
            'message' => 'Commande confirmée comme payée',
            'status' => $order->getStatus(),
            'sessionId' => $session->id,
            'paymentStatus' => $session->payment_status,
            'paymentIntent' => $paymentIntent,
        ]);
    }
}