<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountApiController extends AbstractController
{
    /**
     * Retourne les informations du compte actuellement connecté.
     */
    #[Route('/api/account', name: 'api_account_me', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Utilisateur non authentifié',
            ], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }

    /**
     * Retourne la liste des commandes de l'utilisateur connecté.
     */
    #[Route('/api/account/orders', name: 'api_account_orders', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function orders(OrderRepository $orderRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Utilisateur non authentifié',
            ], 401);
        }

        $orders = $orderRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        $data = array_map(static function (Order $order): array {
            return [
                'id' => $order->getId(),
                'status' => $order->getStatus(),
                'totalCents' => $order->getTotalCents(),
                'currency' => $order->getCurrency(),
                'createdAt' => $order->getCreatedAt()?->format(DATE_ATOM),
            ];
        }, $orders);

        return $this->json([
            'orders' => $data,
        ]);
    }

    /**
     * Retourne le détail d'une commande précise de l'utilisateur connecté.
     */
    #[Route('/api/account/orders/{id}', name: 'api_account_order_show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function showOrder(int $id, OrderRepository $orderRepository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Utilisateur non authentifié',
            ], 401);
        }

        $order = $orderRepository->find($id);

        if (!$order instanceof Order) {
            return $this->json([
                'message' => 'Commande introuvable',
            ], 404);
        }

        if ($order->getUser()?->getId() !== $user->getId()) {
            return $this->json([
                'message' => 'Accès refusé à cette commande',
            ], 403);
        }

        return $this->json([
            'order' => [
                'id' => $order->getId(),
                'status' => $order->getStatus(),
                'totalCents' => $order->getTotalCents(),
                'currency' => $order->getCurrency(),
                'createdAt' => $order->getCreatedAt()?->format(DATE_ATOM),
            ],
        ]);
    }
}