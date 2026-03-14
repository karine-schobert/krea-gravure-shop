<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderItem;
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
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
        ]);
    }

    /**
     * Retourne la liste des commandes de l'utilisateur connecté.
     *
     * On renvoie les commandes avec :
     * - les informations principales
     * - les lignes de commande
     * - le snapshot figé de livraison
     *
     * Important :
     * on lit les données figées de la commande
     * pour ne pas dépendre d'éventuelles modifications futures.
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

        $data = array_map(
            fn (Order $order): array => $this->serializeOrder($order),
            $orders
        );

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

        /**
         * Vérifie que la commande appartient bien
         * à l'utilisateur connecté.
         */
        if ($order->getUser()?->getId() !== $user->getId()) {
            return $this->json([
                'message' => 'Accès refusé à cette commande',
            ], 403);
        }

        return $this->json([
            'order' => $this->serializeOrder($order),
        ]);
    }

    /**
     * Sérialise une commande pour le front.
     *
     * On inclut :
     * - les infos principales de la commande
     * - le snapshot de livraison figé dans Order
     * - les lignes figées dans OrderItem
     */
    private function serializeOrder(Order $order): array
    {
        $items = array_map(static function (OrderItem $item): array {
            /**
             * On utilise les données figées dans order_item
             * pour ne pas dépendre de la fiche produit actuelle.
             */
            return [
                'id' => $item->getId(),
                'productId' => $item->getProduct()?->getId(),
                'productTitle' => $item->getProductTitle(),
                'productSlug' => $item->getProductSlug(),
                'productImage' => $item->getProductImage(),
                'unitPriceCents' => $item->getUnitPriceCents(),
                'quantity' => $item->getQuantity(),
                'lineTotalCents' => $item->getLineTotalCents(),
            ];
        }, $order->getItems()->toArray());

        return [
            'id' => $order->getId(),
            'email' => $order->getEmail(),
            'status' => $order->getStatus(),
            'totalCents' => $order->getTotalCents(),
            'currency' => $order->getCurrency(),
            'createdAt' => $order->getCreatedAt()?->format(DATE_ATOM),

            /**
             * Snapshot figé de livraison.
             * C'est ce bloc que la success page doit afficher,
             * pas les adresses actuelles du carnet client.
             */
            'shippingFullName' => $order->getShippingFullName(),
            'shippingAddressLine' => $order->getShippingAddressLine(),
            'shippingPostalCode' => $order->getShippingPostalCode(),
            'shippingCity' => $order->getShippingCity(),
            'shippingCountry' => $order->getShippingCountry(),
            'shippingPhone' => $order->getShippingPhone(),
            'shippingInstructions' => $order->getShippingInstructions(),

            'items' => $items,
        ];
    }
}