<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Service\StripeCheckoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/checkout', name: 'api_checkout_')]
class CheckoutApiController extends AbstractController
{
    #[Route('', name: 'from_cart', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function checkoutFromCart(
        Request $request,
        ProductRepository $productRepository,
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

        $data = json_decode($request->getContent(), true);

        if (
            !is_array($data) ||
            !isset($data['items']) ||
            !is_array($data['items']) ||
            count($data['items']) === 0
        ) {
            return $this->json([
                'error' => 'Panier invalide ou vide',
            ], 400);
        }

        $order = new Order();
        $order->setUser($user);
        $order->setEmail($user->getEmail() ?? '');
        $order->setStatus(Order::STATUS_PENDING_PAYMENT);
        $order->setCurrency('eur');

        $totalCents = 0;

        foreach ($data['items'] as $row) {
            $productId = $row['productId'] ?? null;
            $quantity = (int) ($row['quantity'] ?? 0);

            if (!$productId || $quantity < 1) {
                return $this->json([
                    'error' => 'Ligne panier invalide',
                    'row' => $row,
                ], 400);
            }

            $product = $productRepository->find($productId);

            if (!$product) {
                return $this->json([
                    'error' => sprintf('Produit introuvable : %s', $productId),
                ], 404);
            }

            $unitPriceCents = $product->getPriceCents();
            $lineTotalCents = $unitPriceCents * $quantity;

            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($product);
            $orderItem->setProductTitle($product->getTitle());
            $orderItem->setUnitPriceCents($unitPriceCents);
            $orderItem->setQuantity($quantity);
            $orderItem->setLineTotalCents($lineTotalCents);

            $order->addItem($orderItem);

            $totalCents += $lineTotalCents;
        }

        $order->setTotalCents($totalCents);

        $entityManager->persist($order);
        $entityManager->flush();

        $session = $stripeCheckoutService->createCheckoutSession($order);

        $order->setStripeSessionId($session->id);
        $entityManager->flush();

        return $this->json([
            'message' => 'Session Stripe créée depuis le panier',
            'orderId' => $order->getId(),
            'sessionId' => $session->id,
            'checkoutUrl' => $session->url,
        ]);
    }

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

        if ($order->getUser() !== $user) {
            return $this->json([
                'error' => 'Commande non autorisée',
            ], 403);
        }

        if ($order->getStatus() !== Order::STATUS_PENDING_PAYMENT) {
            return $this->json([
                'error' => 'Commande non payable',
            ], 400);
        }

        if ($order->getItems()->isEmpty()) {
            return $this->json([
                'error' => 'Commande vide',
            ], 400);
        }

        $session = $stripeCheckoutService->createCheckoutSession($order);

        $order->setStripeSessionId($session->id);

        $entityManager->flush();

        return $this->json([
            'message' => 'Session Stripe créée',
            'sessionId' => $session->id,
            'checkoutUrl' => $session->url,
        ]);
    }
}