<?php

namespace App\Controller\Api;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API du panier client connecté.
 *
 * Contrat JSON attendu par le front Next.js :
 * {
 *   "items": [],
 *   "totalQuantity": 0,
 *   "totalCents": 0
 * }
 *
 * Cette API repose sur un panier stocké en base,
 * lié à l'utilisateur authentifié.
 */
#[Route('/api/cart', name: 'api_cart_')]
#[IsGranted('ROLE_USER')]
class CartApiController extends AbstractController
{
    /**
     * Retourne le panier courant.
     *
     * GET /api/cart
     */
    #[Route('', name: 'show', methods: ['GET'])]
    public function show(EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $cart = $this->getOrCreateCart($user, $entityManager);

        return $this->json(
            $this->serializeCart($cart),
            Response::HTTP_OK
        );
    }

    /**
     * Ajoute un produit au panier.
     *
     * Payload attendu :
     * {
     *   "productId": 12,
     *   "quantity": 2
     * }
     *
     * POST /api/cart/add
     */
    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $cart = $this->getOrCreateCart($user, $entityManager);

        $data = json_decode($request->getContent(), true) ?? [];

        $productId = isset($data['productId']) ? (int) $data['productId'] : 0;
        $quantity = isset($data['quantity']) ? (int) $data['quantity'] : 1;

        if ($productId <= 0) {
            return $this->json([
                'message' => 'Le productId est requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($quantity <= 0) {
            return $this->json([
                'message' => 'La quantité doit être supérieure à 0.',
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var Product|null $product */
        $product = $entityManager->getRepository(Product::class)->find($productId);

        if (!$product instanceof Product) {
            return $this->json([
                'message' => 'Produit introuvable.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (method_exists($product, 'isActive') && !$product->isActive()) {
            return $this->json([
                'message' => 'Ce produit n’est pas disponible.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // On cherche si le produit existe déjà dans le panier.
        $existingItem = $this->findCartItemByProductId($cart, $productId);

        if ($existingItem instanceof CartItem) {
            // Si la ligne existe déjà, on augmente simplement la quantité.
            $existingItem->setQuantity($existingItem->getQuantity() + $quantity);

            if (method_exists($existingItem, 'setUpdatedAt')) {
                $existingItem->setUpdatedAt(new \DateTimeImmutable());
            }
        } else {
            // Sinon, on crée une nouvelle ligne.
            $cartItem = new CartItem();
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);

            /**
             * Si l'entité Cart possède une vraie méthode addItem(),
             * on l'utilise pour bien synchroniser la relation en mémoire.
             */
            if (method_exists($cart, 'addItem')) {
                $cart->addItem($cartItem);
            } else {
                $cartItem->setCart($cart);
            }

            $entityManager->persist($cartItem);
        }

        if (method_exists($cart, 'setUpdatedAt')) {
            $cart->setUpdatedAt(new \DateTimeImmutable());
        }

        $entityManager->flush();

        /**
         * Important :
         * on recharge le panier après flush pour éviter les collections
         * Doctrine non synchronisées en mémoire.
         */
        $entityManager->refresh($cart);

        return $this->json(
            $this->serializeCart($cart),
            Response::HTTP_OK
        );
    }

    /**
     * Met à jour la quantité d'une ligne de panier.
     *
     * Payload attendu :
     * {
     *   "productId": 12,
     *   "quantity": 3
     * }
     *
     * Si quantity <= 0, la ligne est supprimée.
     *
     * POST /api/cart/update
     */
    #[Route('/update', name: 'update', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $cart = $this->getOrCreateCart($user, $entityManager);

        $data = json_decode($request->getContent(), true) ?? [];

        $productId = isset($data['productId']) ? (int) $data['productId'] : 0;
        $quantity = isset($data['quantity']) ? (int) $data['quantity'] : 0;

        if ($productId <= 0) {
            return $this->json([
                'message' => 'Le productId est requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $targetItem = $this->findCartItemByProductId($cart, $productId);

        if (!$targetItem instanceof CartItem) {
            return $this->json([
                'message' => 'Produit absent du panier.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Si la quantité est <= 0, on supprime la ligne.
        if ($quantity <= 0) {
            if (method_exists($cart, 'removeItem')) {
                $cart->removeItem($targetItem);
            }

            $entityManager->remove($targetItem);
        } else {
            $targetItem->setQuantity($quantity);

            if (method_exists($targetItem, 'setUpdatedAt')) {
                $targetItem->setUpdatedAt(new \DateTimeImmutable());
            }
        }

        if (method_exists($cart, 'setUpdatedAt')) {
            $cart->setUpdatedAt(new \DateTimeImmutable());
        }

        $entityManager->flush();
        $entityManager->refresh($cart);

        return $this->json(
            $this->serializeCart($cart),
            Response::HTTP_OK
        );
    }

    /**
     * Supprime complètement une ligne de panier.
     *
     * Payload attendu :
     * {
     *   "productId": 12
     * }
     *
     * POST /api/cart/remove
     */
    #[Route('/remove', name: 'remove', methods: ['POST'])]
    public function remove(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $cart = $this->getOrCreateCart($user, $entityManager);

        $data = json_decode($request->getContent(), true) ?? [];
        $productId = isset($data['productId']) ? (int) $data['productId'] : 0;

        if ($productId <= 0) {
            return $this->json([
                'message' => 'Le productId est requis.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $targetItem = $this->findCartItemByProductId($cart, $productId);

        if (!$targetItem instanceof CartItem) {
            return $this->json([
                'message' => 'Produit absent du panier.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (method_exists($cart, 'removeItem')) {
            $cart->removeItem($targetItem);
        }

        $entityManager->remove($targetItem);

        if (method_exists($cart, 'setUpdatedAt')) {
            $cart->setUpdatedAt(new \DateTimeImmutable());
        }

        $entityManager->flush();
        $entityManager->refresh($cart);

        return $this->json(
            $this->serializeCart($cart),
            Response::HTTP_OK
        );
    }

    /**
     * Vide complètement le panier.
     *
     * POST /api/cart/clear
     */
    #[Route('/clear', name: 'clear', methods: ['POST'])]
    public function clear(EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $cart = $this->getOrCreateCart($user, $entityManager);

        foreach ($cart->getItems() as $item) {
            if (method_exists($cart, 'removeItem')) {
                $cart->removeItem($item);
            }

            $entityManager->remove($item);
        }

        if (method_exists($cart, 'setUpdatedAt')) {
            $cart->setUpdatedAt(new \DateTimeImmutable());
        }

        $entityManager->flush();
        $entityManager->refresh($cart);

        return $this->json(
            $this->serializeCart($cart),
            Response::HTTP_OK
        );
    }

    /**
     * Retourne l'utilisateur connecté en garantissant
     * qu'il s'agit bien de notre entité User.
     */
    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifié.');
        }

        return $user;
    }

    /**
     * Récupère le panier lié à l'utilisateur.
     * S'il n'existe pas encore, on le crée.
     */
    private function getOrCreateCart(User $user, EntityManagerInterface $entityManager): Cart
    {
        $cart = $user->getCart();

        if ($cart instanceof Cart) {
            return $cart;
        }

        $cart = new Cart();
        $cart->setUser($user);

        /**
         * Si ton entité User possède setCart(),
         * on synchronise aussi le côté inverse.
         */
        if (method_exists($user, 'setCart')) {
            $user->setCart($cart);
        }

        $entityManager->persist($cart);
        $entityManager->flush();

        return $cart;
    }

    /**
     * Cherche une ligne de panier à partir d'un productId.
     */
    private function findCartItemByProductId(Cart $cart, int $productId): ?CartItem
    {
        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();

            if ($product instanceof Product && $product->getId() === $productId) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Sérialise le panier exactement dans le format attendu par le front.
     *
     * Important :
     * - unitPriceCents
     * - totalQuantity
     * - totalCents
     *
     * Pas de wrapper "cart".
     */
    private function serializeCart(Cart $cart): array
    {
        $items = [];
        $totalQuantity = 0;
        $totalCents = 0;

        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();

            if (!$product instanceof Product) {
                continue;
            }

            $unitPriceCents = $product->getPriceCents();
            $lineTotalCents = $unitPriceCents * $item->getQuantity();

            $items[] = [
                'id' => $item->getId(),
                'productId' => $product->getId(),
                'title' => $product->getTitle(),
                'slug' => $product->getSlug(),
                'image' => $product->getImage(),
                'unitPriceCents' => $unitPriceCents,
                'quantity' => $item->getQuantity(),
                'lineTotalCents' => $lineTotalCents,
            ];

            $totalQuantity += $item->getQuantity();
            $totalCents += $lineTotalCents;
        }

        return [
            'items' => $items,
            'totalQuantity' => $totalQuantity,
            'totalCents' => $totalCents,
        ];
    }
}