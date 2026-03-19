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
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cart', name: 'api_cart_')]
class CartApiController extends AbstractController
{
    /**
     * Vérifie si user connecté
     */
    private function isAuthenticatedUser(): bool
    {
        return $this->getUser() instanceof User;
    }

    /**
     * =========================
     * 🛒 GET CART
     * =========================
     */
    #[Route('', name: 'show', methods: ['GET'])]
    public function show(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /**
         * =========================
         * 👤 USER CONNECTÉ (DB)
         * =========================
         */
        if ($this->isAuthenticatedUser()) {

    $user = $this->getAuthenticatedUser();

    $session = $request->getSession();
    if (!$session->isStarted()) {
        $session->start();
    }

    $sessionCart = $session->get('cart', []);

    // 🔥 récupère ou crée panier user
    $cart = $this->getOrCreateCart($user, $em);

    // 💥 SI panier session existe → fusion
    if (!empty($sessionCart)) {

        foreach ($sessionCart as $productId => $qty) {

            $product = $em->getRepository(Product::class)->find((int)$productId);

            if (!$product) {
                continue;
            }

            $existingItem = $this->findCartItemByProductId($cart, (int)$productId);

            if ($existingItem) {
                $existingItem->setQuantity(
                    $existingItem->getQuantity() + $qty
                );
            } else {
                $item = new CartItem();
                $item->setProduct($product);
                $item->setQuantity($qty);
                $item->setCart($cart);

                $em->persist($item);
            }
        }

        // 🔥 vide la session après fusion
        $session->set('cart', []);
        $session->save();

        $em->flush();
        $em->refresh($cart);
    }

    return $this->json($this->serializeCart($cart));
}

        /**
         * =========================
         * 👤 VISITEUR (SESSION)
         * =========================
         */
        $session = $request->getSession();

        // 🔥 Sécurise la session (important)
        if (!$session->isStarted()) {
            $session->start();
        }

        // 💥 IMPORTANT : force la lecture/écriture
        //$session = $request->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        $cart = $session->get('cart', []);
        dump('CART SESSION', $session->get('cart'));

        return $this->json(
            $this->buildSessionCartResponse($cart, $em)
        );
    }

    /**
     * =========================
     * ➕ ADD TO CART
     * =========================
     */
    #[Route('/add', name: 'add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $productId = (int) ($data['productId'] ?? 0);
        $quantity = (int) ($data['quantity'] ?? 1);

        if ($productId <= 0) {
            return $this->json(['message' => 'productId requis'], 400);
        }

        if ($quantity <= 0) {
            return $this->json(['message' => 'quantity invalide'], 400);
        }

        $product = $em->getRepository(Product::class)->find($productId);

        if (!$product) {
            return $this->json(['message' => 'Produit introuvable'], 404);
        }

        /**
         * =========================
         * 👤 VISITEUR (SESSION)
         * =========================
         */
        if (!$this->isAuthenticatedUser()) {

            $session = $request->getSession();

            // 🔥 démarre la session si besoin
            if (!$session->isStarted()) {
                $session->start();
            }

            $cart = $session->get('cart', []);

            if (!isset($cart[$productId])) {
                $cart[$productId] = 0;
            }

            $cart[$productId] += $quantity;

            // 💥 SAUVEGARDE SESSION (CRITIQUE)
            $session->set('cart', $cart);
            dump('CART SESSION', $session->get('cart'));

            // 💥 FIX IMPORTANT → force écriture immédiate
            $session->save();

            return $this->json(
                $this->buildSessionCartResponse($cart, $em)
            );
        }

        /**
         * =========================
         * 👤 USER CONNECTÉ (DB)
         * =========================
         */
        $user = $this->getAuthenticatedUser();
        $cart = $this->getOrCreateCart($user, $em);

        $existingItem = $this->findCartItemByProductId($cart, $productId);

        if ($existingItem) {
            $existingItem->setQuantity(
                $existingItem->getQuantity() + $quantity
            );
        } else {
            $item = new CartItem();
            $item->setProduct($product);
            $item->setQuantity($quantity);
            $item->setCart($cart);

            $em->persist($item);
        }

        $em->flush();
        $em->refresh($cart);

        return $this->json($this->serializeCart($cart));
    }

    /**
     * =========================
     * 🧱 BUILD SESSION CART
     * =========================
     * 👉 centralise logique guest
     */
   private function buildSessionCartResponse(array $cart, EntityManagerInterface $em): array
{
    $items = [];
    $totalQuantity = 0;
    $totalCents = 0;

    foreach ($cart as $productId => $qty) {

        $product = $em->getRepository(Product::class)->find((int)$productId);

        if (!$product) {
            continue; // sécurité
        }

        $unit = $product->getPriceCents();
        $line = $unit * $qty;

        $items[] = [
            'id' => (int)$productId,
            'productId' => (int)$productId,
            'title' => $product->getTitle(),
            'slug' => $product->getSlug(),
            'image' => $product->getImage(),
            'unitPriceCents' => $unit,
            'quantity' => $qty,
            'lineTotalCents' => $line,
        ];

        $totalQuantity += $qty;
        $totalCents += $line;
    }

    return [
        'items' => $items,
        'totalQuantity' => $totalQuantity,
        'totalCents' => $totalCents,
    ];
}

    /**
     * =========================
     * 🔐 USER SAFE
     * =========================
     */
    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    /**
     * =========================
     * 🧺 GET OR CREATE CART
     * =========================
     */
    private function getOrCreateCart(User $user, EntityManagerInterface $em): Cart
    {
        $cart = $user->getCart();

        if ($cart) {
            return $cart;
        }

        $cart = new Cart();
        $cart->setUser($user);

        if (method_exists($user, 'setCart')) {
            $user->setCart($cart);
        }

        $em->persist($cart);
        $em->flush();

        return $cart;
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
    public function update(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $productId = (int) ($data['productId'] ?? 0);
        $quantity = (int) ($data['quantity'] ?? 0);

        if ($productId <= 0) {
            return $this->json(['message' => 'productId requis'], 400);
        }

        /**
         * =========================
         * 👤 VISITEUR (SESSION)
         * =========================
         */
        if (!$this->isAuthenticatedUser()) {

            $session = $request->getSession();

            if (!$session->isStarted()) {
                $session->start();
            }

            $cart = $session->get('cart', []);

            if (!isset($cart[$productId])) {
                return $this->json(['message' => 'Produit absent'], 404);
            }

            if ($quantity <= 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId] = $quantity;
            }

            $session->set('cart', $cart);
            $session->save();

            return $this->json(
                $this->buildSessionCartResponse($cart, $em)
            );
        }

        /**
         * =========================
         * 👤 USER CONNECTÉ (DB)
         * =========================
         */
        $user = $this->getAuthenticatedUser();
        $cart = $this->getOrCreateCart($user, $em);

        $item = $this->findCartItemByProductId($cart, $productId);

        if (!$item) {
            return $this->json(['message' => 'Produit absent'], 404);
        }

        if ($quantity <= 0) {
            $em->remove($item);
        } else {
            $item->setQuantity($quantity);
        }

        $em->flush();
        $em->refresh($cart);

        return $this->json($this->serializeCart($cart));
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
    public function remove(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $productId = (int) ($data['productId'] ?? 0);

        if ($productId <= 0) {
            return $this->json(['message' => 'productId requis'], 400);
        }

        /**
         * 👤 SESSION
         */
        if (!$this->isAuthenticatedUser()) {

            $session = $request->getSession();

            if (!$session->isStarted()) {
                $session->start();
            }

            $cart = $session->get('cart', []);

            unset($cart[$productId]);

            $session->set('cart', $cart);
            $session->save();

            return $this->json(
                $this->buildSessionCartResponse($cart, $em)
            );
        }

        /**
         * 👤 USER
         */
        $user = $this->getAuthenticatedUser();
        $cart = $this->getOrCreateCart($user, $em);

        $item = $this->findCartItemByProductId($cart, $productId);

        if ($item) {
            $em->remove($item);
        }

        $em->flush();

        $em->refresh($cart);

        return $this->json($this->serializeCart($cart));
    }
    /**
     * Vide complètement le panier.
     *
     * POST /api/cart/clear
     */
    #[Route('/clear', name: 'clear', methods: ['POST'])]
    public function clear(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /**
         * 👤 SESSION
         */
        if (!$this->isAuthenticatedUser()) {

            $session = $request->getSession();

            if (!$session->isStarted()) {
                $session->start();
            }

            $session->set('cart', []);
            $session->save();

            return $this->json([
                'items' => [],
                'totalQuantity' => 0,
                'totalCents' => 0,
            ]);
        }

        /**
         * 👤 USER
         */
        $user = $this->getAuthenticatedUser();
        $cart = $this->getOrCreateCart($user, $em);

        foreach ($cart->getItems() as $item) {
            $em->remove($item);
        }

        $em->flush();
        $em->refresh($cart);

        return $this->json($this->serializeCart($cart));
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
