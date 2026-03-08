<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders', name: 'api_orders_')]
class OrderApiController extends AbstractController
{
    #[Route('/from-product/{id}', name: 'from_product', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createFromProduct(
        Product $product,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            /** @var User|null $user */
            $user = $this->getUser();

            if (!$user) {
                return $this->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            $order = new Order();
            $order->setUser($user);
            $order->setEmail($user->getEmail());
            $order->setStatus(Order::STATUS_PENDING_PAYMENT);
            $order->setCurrency('EUR');
            $order->setTotalCents($product->getPriceCents());

            $item = new OrderItem();
            $item->setProduct($product);
            $item->setProductTitle($product->getTitle());
            $item->setQuantity(1);
            $item->setUnitPriceCents($product->getPriceCents());
            $item->setLineTotalCents($product->getPriceCents());

            $order->addItem($item);

            $entityManager->persist($order);
            $entityManager->persist($item);
            $entityManager->flush();

            return $this->json([
                'message' => 'Commande créée',
                'orderId' => $order->getId(),
                'status' => $order->getStatus(),
                'totalCents' => $order->getTotalCents(),
            ], 201);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Erreur interne',
                'exception' => $e->getMessage(),
                'class' => $e::class,
            ], 500);
        }
    }

    #[Route('/my', name: 'my', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myOrders(OrderRepository $orderRepository): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur non authentifié',
            ], 401);
        }

        $orders = $orderRepository->findByUserOrderedByNewest($user);

        $data = array_map(fn (Order $order) => $this->serializeOrderSummary($order), $orders);

        return $this->json([
            'items' => $data,
            'total' => count($data),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(Order $order): JsonResponse
    {
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

        return $this->json($this->serializeOrderDetail($order));
    }

    #[Route('/{id}/cancel', name: 'cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(Order $order, EntityManagerInterface $entityManager): JsonResponse
    {
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
                'error' => 'Une commande payée ne peut pas être annulée',
            ], 400);
        }

        if ($order->getStatus() === Order::STATUS_CANCELLED) {
            return $this->json([
                'message' => 'Commande déjà annulée',
                'id' => $order->getId(),
                'status' => $order->getStatus(),
            ]);
        }

        if ($order->getStatus() !== Order::STATUS_PENDING_PAYMENT) {
            return $this->json([
                'error' => 'Cette commande ne peut pas être annulée dans son état actuel',
                'status' => $order->getStatus(),
            ], 400);
        }

        $order->setStatus(Order::STATUS_CANCELLED);
        $entityManager->flush();

        return $this->json([
            'message' => 'Commande annulée',
            'id' => $order->getId(),
            'status' => $order->getStatus(),
        ]);
    }

    private function serializeOrderSummary(Order $order): array
    {
        return [
            'id' => $order->getId(),
            'email' => $order->getEmail(),
            'status' => $order->getStatus(),
            'totalCents' => $order->getTotalCents(),
            'currency' => $order->getCurrency(),
            'stripeSessionId' => $order->getStripeSessionId(),
            'stripePaymentIntentId' => $order->getStripePaymentIntentId(),
            'createdAt' => $order->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $order->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'paidAt' => $order->getPaidAt()?->format(\DateTimeInterface::ATOM),
            'itemsCount' => $order->getItems()->count(),
        ];
    }

    private function serializeOrderDetail(Order $order): array
    {
        $items = [];

        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();

            $items[] = [
                'id' => $item->getId(),
                'productId' => $product?->getId(),
                'productTitle' => $item->getProductTitle(),
                'quantity' => $item->getQuantity(),
                'unitPriceCents' => $item->getUnitPriceCents(),
                'lineTotalCents' => $item->getLineTotalCents(),
            ];
        }

        return [
            'id' => $order->getId(),
            'email' => $order->getEmail(),
            'status' => $order->getStatus(),
            'totalCents' => $order->getTotalCents(),
            'currency' => $order->getCurrency(),
            'stripeSessionId' => $order->getStripeSessionId(),
            'stripePaymentIntentId' => $order->getStripePaymentIntentId(),
            'createdAt' => $order->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $order->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'paidAt' => $order->getPaidAt()?->format(\DateTimeInterface::ATOM),
            'items' => $items,
        ];
    }
}