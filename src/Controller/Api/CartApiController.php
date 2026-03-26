<?php

namespace App\Controller\Api;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\ProductOffer;
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
     * Vérifie si user connecté.
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

            $sessionCart = $this->normalizeSessionCartStructure(
                $session->get('cart', [])
            );

            // Récupère ou crée panier user
            $cart = $this->getOrCreateCart($user, $em);

            // Fusion session -> DB
            if (!empty($sessionCart)) {
                foreach ($sessionCart as $line) {
                    $productId = (int) ($line['productId'] ?? 0);
                    $quantity = (int) ($line['quantity'] ?? 0);
                    $offerId = isset($line['offerId']) ? (int) $line['offerId'] : null;
                    $customization = $this->normalizeCustomization(
                        $line['customization'] ?? null
                    );

                    if ($productId <= 0 || $quantity <= 0) {
                        continue;
                    }

                    $product = $em->getRepository(Product::class)->find($productId);

                    if (!$product) {
                        continue;
                    }

                    $offer = null;
                    if ($offerId) {
                        $offer = $em->getRepository(ProductOffer::class)->find($offerId);

                        if (
                            !$offer
                            || !$offer->isActive()
                            || $offer->getProduct()?->getId() !== $product->getId()
                        ) {
                            continue;
                        }
                    }

                    $existingItem = $this->findMatchingCartItem(
                        $cart,
                        $productId,
                        $offer?->getId(),
                        $customization
                    );

                    if ($existingItem) {
                        $existingItem->setQuantity(
                            $existingItem->getQuantity() + $quantity
                        );
                    } else {
                        $item = new CartItem();
                        $item->setProduct($product);
                        $item->setOffer($offer);
                        $item->setCustomization($customization);
                        $item->setQuantity($quantity);
                        $item->setCart($cart);

                        $em->persist($item);
                    }
                }

                // Vide la session après fusion
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

        if (!$session->isStarted()) {
            $session->start();
        }

        $cart = $this->normalizeSessionCartStructure(
            $session->get('cart', [])
        );

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
        $offerId = isset($data['offerId']) && $data['offerId'] !== null
            ? (int) $data['offerId']
            : null;
        $customization = $this->normalizeCustomization(
            $data['customization'] ?? null
        );

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

        $offer = null;
        if ($offerId !== null) {
            $offer = $em->getRepository(ProductOffer::class)->find($offerId);

            if (!$offer) {
                return $this->json(['message' => 'Offre introuvable'], 404);
            }

            if (!$offer->isActive()) {
                return $this->json(['message' => 'Offre inactive'], 400);
            }

            if ($offer->getProduct()?->getId() !== $product->getId()) {
                return $this->json(['message' => 'Cette offre ne correspond pas à ce produit'], 400);
            }
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

            $cart = $this->normalizeSessionCartStructure(
                $session->get('cart', [])
            );

            $index = $this->findMatchingSessionCartIndex(
                $cart,
                $productId,
                $offer?->getId(),
                $customization
            );

            if ($index !== null) {
                $cart[$index]['quantity'] += $quantity;
            } else {
                $cart[] = [
                    'productId' => $productId,
                    'offerId' => $offer?->getId(),
                    'customization' => $customization,
                    'quantity' => $quantity,
                ];
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

        $existingItem = $this->findMatchingCartItem(
            $cart,
            $productId,
            $offer?->getId(),
            $customization
        );

        if ($existingItem) {
            $existingItem->setQuantity(
                $existingItem->getQuantity() + $quantity
            );
        } else {
            $item = new CartItem();
            $item->setProduct($product);
            $item->setOffer($offer);
            $item->setCustomization($customization);
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
     * ✏️ UPDATE CART LINE
     * =========================
     *
     * Payload attendu :
     * {
     *   "productId": 12,
     *   "quantity": 3,
     *   "offerId": 5,
     *   "customization": "Anthony"
     * }
     *
     * Si quantity <= 0, la ligne est supprimée.
     */
    #[Route('/update', name: 'update', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $productId = (int) ($data['productId'] ?? 0);
        $quantity = (int) ($data['quantity'] ?? 0);
        $offerId = isset($data['offerId']) && $data['offerId'] !== null
            ? (int) $data['offerId']
            : null;
        $customization = $this->normalizeCustomization(
            $data['customization'] ?? null
        );

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

            $cart = $this->normalizeSessionCartStructure(
                $session->get('cart', [])
            );

            $index = $this->findMatchingSessionCartIndex(
                $cart,
                $productId,
                $offerId,
                $customization
            );

            if ($index === null) {
                return $this->json(['message' => 'Produit absent'], 404);
            }

            if ($quantity <= 0) {
                unset($cart[$index]);
                $cart = array_values($cart);
            } else {
                $cart[$index]['quantity'] = $quantity;
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

        $item = $this->findMatchingCartItem(
            $cart,
            $productId,
            $offerId,
            $customization
        );

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
     * =========================
     * ❌ REMOVE CART LINE
     * =========================
     *
     * Payload attendu :
     * {
     *   "productId": 12,
     *   "offerId": 5,
     *   "customization": "Anthony"
     * }
     */
    #[Route('/remove', name: 'remove', methods: ['POST'])]
    public function remove(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $productId = (int) ($data['productId'] ?? 0);
        $offerId = isset($data['offerId']) && $data['offerId'] !== null
            ? (int) $data['offerId']
            : null;
        $customization = $this->normalizeCustomization(
            $data['customization'] ?? null
        );

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

            $cart = $this->normalizeSessionCartStructure(
                $session->get('cart', [])
            );

            $index = $this->findMatchingSessionCartIndex(
                $cart,
                $productId,
                $offerId,
                $customization
            );

            if ($index !== null) {
                unset($cart[$index]);
                $cart = array_values($cart);
            }

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

        $item = $this->findMatchingCartItem(
            $cart,
            $productId,
            $offerId,
            $customization
        );

        if ($item) {
            $em->remove($item);
        }

        $em->flush();
        $em->refresh($cart);

        return $this->json($this->serializeCart($cart));
    }

    /**
     * =========================
     * 🗑️ CLEAR CART
     * =========================
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
     * =========================
     * 🧱 BUILD SESSION CART
     * =========================
     * Centralise la logique guest.
     */
    private function buildSessionCartResponse(array $cart, EntityManagerInterface $em): array
    {
        $cart = $this->normalizeSessionCartStructure($cart);

        $items = [];
        $totalQuantity = 0;
        $totalCents = 0;

        foreach ($cart as $index => $line) {
            $productId = (int) ($line['productId'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);
            $offerId = isset($line['offerId']) ? (int) $line['offerId'] : null;
            $customization = $this->normalizeCustomization(
                $line['customization'] ?? null
            );

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $product = $em->getRepository(Product::class)->find($productId);

            if (!$product) {
                continue;
            }

            $offer = null;
            if ($offerId) {
                $offer = $em->getRepository(ProductOffer::class)->find($offerId);

                if (
                    !$offer
                    || !$offer->isActive()
                    || $offer->getProduct()?->getId() !== $product->getId()
                ) {
                    $offer = null;
                }
            }

            $unitPriceCents = $offer?->getPriceCents() ?? $product->getPriceCents();
            $lineTotalCents = $unitPriceCents * $quantity;

            $items[] = [
                'id' => $index,
                'productId' => $product->getId(),
                'title' => $product->getTitle(),
                'slug' => $product->getSlug(),
                'image' => $product->getImage(),
                'offerId' => $offer?->getId(),
                'offerTitle' => $offer?->getTitle(),
                'customization' => $customization,
                'unitPriceCents' => $unitPriceCents,
                'quantity' => $quantity,
                'lineTotalCents' => $lineTotalCents,
            ];

            $totalQuantity += $quantity;
            $totalCents += $lineTotalCents;
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
     * Cherche une ligne panier DB par combinaison réelle.
     */
    private function findMatchingCartItem(
        Cart $cart,
        int $productId,
        ?int $offerId = null,
        ?string $customization = null
    ): ?CartItem {
        $normalizedCustomization = $this->normalizeCustomization($customization);

        foreach ($cart->getItems() as $item) {
            $product = $item->getProduct();

            if (!$product instanceof Product || $product->getId() !== $productId) {
                continue;
            }

            $itemOfferId = $item->getOffer()?->getId();
            $itemCustomization = $this->normalizeCustomization(
                $item->getCustomization()
            );

            if (
                $itemOfferId === $offerId
                && $itemCustomization === $normalizedCustomization
            ) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Cherche une ligne panier session par combinaison réelle.
     */
    private function findMatchingSessionCartIndex(
        array $cart,
        int $productId,
        ?int $offerId = null,
        ?string $customization = null
    ): ?int {
        $normalizedCustomization = $this->normalizeCustomization($customization);

        foreach ($cart as $index => $line) {
            $lineProductId = (int) ($line['productId'] ?? 0);
            $lineOfferId = isset($line['offerId']) && $line['offerId'] !== null
                ? (int) $line['offerId']
                : null;
            $lineCustomization = $this->normalizeCustomization(
                $line['customization'] ?? null
            );

            if (
                $lineProductId === $productId
                && $lineOfferId === $offerId
                && $lineCustomization === $normalizedCustomization
            ) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Normalise la personnalisation.
     */
    private function normalizeCustomization(?string $customization): ?string
    {
        if ($customization === null) {
            return null;
        }

        $customization = trim($customization);

        return $customization === '' ? null : $customization;
    }

    /**
     * Normalise la structure session.
     *
     * Compatibilité :
     * - ancien format : [productId => quantity]
     * - nouveau format : tableau de lignes
     */
    private function normalizeSessionCartStructure(array $cart): array
    {
        if ($cart === []) {
            return [];
        }

        $first = reset($cart);

        // Ancien format : [productId => qty]
        if (!is_array($first)) {
            $normalized = [];

            foreach ($cart as $productId => $qty) {
                $normalized[] = [
                    'productId' => (int) $productId,
                    'offerId' => null,
                    'customization' => null,
                    'quantity' => (int) $qty,
                ];
            }

            return $normalized;
        }

        // Nouveau format
        return array_values(array_map(function (array $line) {
            return [
                'productId' => (int) ($line['productId'] ?? 0),
                'offerId' => isset($line['offerId']) && $line['offerId'] !== null
                    ? (int) $line['offerId']
                    : null,
                'customization' => $this->normalizeCustomization(
                    $line['customization'] ?? null
                ),
                'quantity' => max(1, (int) ($line['quantity'] ?? 1)),
            ];
        }, $cart));
    }

    /**
     * Sérialise le panier DB dans le format attendu par le front.
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

            $offer = $item->getOffer();
            $unitPriceCents = $item->getUnitPriceCents();
            $lineTotalCents = $item->getLineTotalCents();

            $items[] = [
                'id' => $item->getId(),
                'productId' => $product->getId(),
                'title' => $product->getTitle(),
                'slug' => $product->getSlug(),
                'image' => $product->getImage(),
                'offerId' => $offer?->getId(),
                'offerTitle' => $offer?->getTitle(),
                'customization' => $item->getCustomization(),
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