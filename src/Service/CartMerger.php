<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartMerger
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * 🔥 Fusion panier session → user (login)
     */
    public function merge(User $user, SessionInterface $session): void
    {
        // 🔥 Toujours partir du user
        $cart = $user->getCart();

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->em->persist($cart);
        }

        // 🧺 panier session
        $sessionCart = $session->get('cart', []);

        if (empty($sessionCart)) {
            return;
        }

        foreach ($sessionCart as $item) {

            if (!isset($item['productId'], $item['quantity'])) {
                continue;
            }

            $productId = (int) $item['productId'];
            $quantity = (int) $item['quantity'];

            if ($quantity <= 0) {
                continue;
            }

            $product = $this->em->getReference(Product::class, $productId);

            $existingItem = null;

            foreach ($cart->getItems() as $cartItem) {
                if ($cartItem->getProduct()->getId() === $productId) {
                    $existingItem = $cartItem;
                    break;
                }
            }

            if ($existingItem) {
                $existingItem->setQuantity(
                    $existingItem->getQuantity() + $quantity
                );
            } else {
                $newItem = new CartItem();
                $newItem->setCart($cart);
                $newItem->setProduct($product);
                $newItem->setQuantity($quantity);

                $this->em->persist($newItem);
            }
        }

        // 💾 save
        $this->em->flush();

        // 🧹 clean session
        $session->remove('cart');
    }

    /**
     * 🔥 Fusion panier invité → user (register)
     */
    public function mergeFromArray(User $user, array $cartData): void
    {
        if (empty($cartData)) {
            return;
        }

        // 🔥 CRUCIAL : utiliser le user directement
        $cart = $user->getCart();

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->em->persist($cart);
        }

        foreach ($cartData as $item) {

            if (!isset($item['productId'], $item['quantity'])) {
                continue;
            }

            $productId = (int) $item['productId'];
            $quantity = (int) $item['quantity'];

            if ($quantity <= 0) {
                continue;
            }

            $product = $this->em->getReference(Product::class, $productId);

            $existingItem = null;

            foreach ($cart->getItems() as $cartItem) {
                if ($cartItem->getProduct()->getId() === $productId) {
                    $existingItem = $cartItem;
                    break;
                }
            }

            if ($existingItem) {
                $existingItem->setQuantity(
                    $existingItem->getQuantity() + $quantity
                );
            } else {
                $newItem = new CartItem();
                $newItem->setCart($cart);
                $newItem->setProduct($product);
                $newItem->setQuantity($quantity);

                $this->em->persist($newItem);
            }
        }

        // 🔥 TRÈS IMPORTANT
        $this->em->flush();
    }
}