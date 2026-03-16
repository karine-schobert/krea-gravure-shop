<?php

namespace App\Controller\Api;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
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
     * Permet de recommander une ancienne commande :
     * - on relit les OrderItem
     * - on récupère les Product encore valides
     * - on les ajoute dans le panier courant
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/api/account/orders/{id}/reorder', name: 'api_account_order_reorder', methods: ['POST'])]
    public function reorder(
        int $id,
        OrderRepository $orderRepository,
        CartRepository $cartRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        // Sécurité de base : on vérifie qu'on a bien un utilisateur authentifié.
        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Utilisateur non authentifié.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // On charge la commande demandée.
        $order = $orderRepository->find($id);

        // Si la commande n'existe pas, on renvoie 404.
        if (!$order instanceof Order) {
            return $this->json([
                'message' => 'Commande introuvable.',
            ], Response::HTTP_NOT_FOUND);
        }

        // On vérifie que la commande appartient bien à l'utilisateur connecté.
        if ($order->getUser()?->getId() !== $user->getId()) {
            return $this->json([
                'message' => 'Cette commande ne vous appartient pas.',
            ], Response::HTTP_FORBIDDEN);
        }

        // On récupère le panier courant de l'utilisateur.
        $cart = $cartRepository->findOneBy(['user' => $user]);

        // Si aucun panier n'existe encore, on le crée.
        if (!$cart instanceof Cart) {
            $cart = new Cart();
            $cart->setUser($user);

            $entityManager->persist($cart);
        }

        $addedCount = 0;
        $ignoredCount = 0;
        $ignoredProducts = [];

        // On parcourt chaque ligne de l'ancienne commande.
        foreach ($order->getItems() as $orderItem) {
            $product = $orderItem->getProduct();
            $quantity = $orderItem->getQuantity() ?? 0;

            // Sécurité : si le produit n'existe plus, on l'ignore.
            if (!$product instanceof Product) {
                $ignoredCount++;
                $ignoredProducts[] = [
                    'reason' => 'deleted_product',
                    'productTitle' => $orderItem->getProductTitle(),
                    'productSlug' => $orderItem->getProductSlug(),
                ];
                continue;
            }

            // Sécurité : si le produit n'est plus actif, on l'ignore.
            if (method_exists($product, 'isActive') && !$product->isActive()) {
                $ignoredCount++;
                $ignoredProducts[] = [
                    'reason' => 'inactive_product',
                    'productId' => $product->getId(),
                    'productTitle' => $product->getTitle(),
                    'productSlug' => $product->getSlug(),
                ];
                continue;
            }

            // Sécurité : si la quantité est invalide, on l'ignore.
            if ($quantity <= 0) {
                $ignoredCount++;
                $ignoredProducts[] = [
                    'reason' => 'invalid_quantity',
                    'productId' => $product->getId(),
                    'productTitle' => $product->getTitle(),
                    'productSlug' => $product->getSlug(),
                ];
                continue;
            }

            // Ajout réel dans le panier.
            $this->addProductToCart($cart, $product, $quantity, $entityManager);
            $addedCount += $quantity;
        }

        // On flush une seule fois à la fin pour garder un code propre et performant.
        $entityManager->flush();

        return $this->json([
            'message' => 'Recommandation terminée.',
            'orderId' => $order->getId(),
            'addedCount' => $addedCount,
            'ignoredCount' => $ignoredCount,
            'ignoredProducts' => $ignoredProducts,
            'cart' => $this->serializeCart($cart),
        ], Response::HTTP_OK);
    }

    /**
     * Ajoute un produit dans le panier.
     *
     * Si le produit existe déjà dans le panier :
     * - on augmente simplement la quantité
     *
     * Sinon :
     * - on crée une nouvelle ligne CartItem
     */
    private function addProductToCart(
        Cart $cart,
        Product $product,
        int $quantity,
        EntityManagerInterface $entityManager
    ): void {
        // On cherche si le produit existe déjà dans le panier.
        foreach ($cart->getItems() as $existingItem) {
            if ($existingItem->getProduct()?->getId() === $product->getId()) {
                $existingItem->setQuantity($existingItem->getQuantity() + $quantity);
                return;
            }
        }

        // Sinon, on crée une nouvelle ligne de panier.
        $cartItem = new CartItem();
        $cartItem->setCart($cart);
        $cartItem->setProduct($product);
        $cartItem->setQuantity($quantity);

        $entityManager->persist($cartItem);

        // Selon la façon dont ton entité Cart est faite,
        // tu peux garder cette ligne si tu as une méthode addItem().
        if (method_exists($cart, 'addItem')) {
            $cart->addItem($cartItem);
        }
    }

    /**
     * Sérialise rapidement le panier pour retour JSON.
     */
    private function serializeCart(Cart $cart): array
    {
        $items = [];
        $totalCents = 0;

        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();

            if (!$product instanceof Product) {
                continue;
            }

            $unitPriceCents = $product->getPriceCents();
            $lineTotalCents = $unitPriceCents * $item->getQuantity();
            $totalCents += $lineTotalCents;

            $items[] = [
                'id' => $item->getId(),
                'productId' => $product->getId(),
                'title' => $product->getTitle(),
                'slug' => $product->getSlug(),
                'image' => method_exists($product, 'getImage') ? $product->getImage() : null,
                'quantity' => $item->getQuantity(),
                'unitPriceCents' => $unitPriceCents,
                'lineTotalCents' => $lineTotalCents,
            ];
        }

        return [
            'id' => $cart->getId(),
            'items' => $items,
            'totalCents' => $totalCents,
            'itemsCount' => count($items),
        ];
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
            $items = array_map(
                static function ($orderItem): array {
                    return [
                        'id' => $orderItem->getId(),
                        'productId' => $orderItem->getProduct()?->getId(),
                        'productTitle' => $orderItem->getProductTitle(),
                        'productSlug' => $orderItem->getProductSlug(),
                        'productImage' => $orderItem->getProductImage(),
                        'quantity' => $orderItem->getQuantity(),
                        'unitPriceCents' => $orderItem->getUnitPriceCents(),
                        'lineTotalCents' => $orderItem->getLineTotalCents(),
                    ];
                },
                $order->getItems()->toArray()
            );

            return [
                'id' => $order->getId(),
                'email' => $order->getEmail(),
                'status' => $order->getStatus(),
                'totalCents' => $order->getTotalCents(),
                'currency' => $order->getCurrency(),
                'createdAt' => $order->getCreatedAt()?->format(DATE_ATOM),

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