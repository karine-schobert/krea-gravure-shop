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
    /**
     * Crée une commande à partir du panier front,
     * puis génère une session Stripe associée.
     */
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

        if (!$user instanceof User) {
            return $this->json([
                'error' => 'Utilisateur non authentifié',
            ], 401);
        }

        /**
         * Lecture du contenu JSON envoyé par le front.
         * On attend un tableau "items".
         */
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

        /**
         * L'email est stocké dans la commande
         * pour garder une trace du client au moment de l'achat.
         */
        $email = $user->getEmail();

        if (!$email) {
            return $this->json([
                'error' => 'Aucun email utilisateur disponible',
            ], 400);
        }

        /**
         * Création de la commande.
         * La commande démarre en attente de paiement.
         */
        $order = new Order();
        $order->setUser($user);
        $order->setEmail($email);
        $order->setStatus(Order::STATUS_PENDING_PAYMENT);
        $order->setCurrency('eur');
        $order->setUpdatedAt(new \DateTimeImmutable());

        $totalCents = 0;

        /**
         * Parcours de chaque ligne du panier
         * pour construire les OrderItem.
         */
        foreach ($data['items'] as $row) {
            $productId = $row['productId'] ?? null;
            $quantity = (int) ($row['quantity'] ?? 0);

            if (!$productId || $quantity < 1) {
                return $this->json([
                    'error' => 'Ligne panier invalide',
                    'row' => $row,
                ], 400);
            }

            /**
             * Recherche du produit courant.
             */
            $product = $productRepository->find($productId);

            if (!$product) {
                return $this->json([
                    'error' => sprintf('Produit introuvable : %s', $productId),
                ], 404);
            }

            /**
             * Calcul des montants figés au moment de la commande.
             */
            $unitPriceCents = $product->getPriceCents();
            $lineTotalCents = $unitPriceCents * $quantity;

            /**
             * Création de la ligne de commande.
             *
             * Important :
             * on enregistre un snapshot produit
             * pour garder les bonnes infos dans le temps,
             * même si la fiche produit change plus tard.
             */
            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($product);
            $orderItem->setProductTitle($product->getTitle());
            $orderItem->setProductImage($product->getImage());
            $orderItem->setProductSlug($product->getSlug());
            $orderItem->setUnitPriceCents($unitPriceCents);
            $orderItem->setQuantity($quantity);
            $orderItem->setLineTotalCents($lineTotalCents);

            /**
             * Ajout de la ligne à la commande.
             */
            $order->addItem($orderItem);

            /**
             * Mise à jour du total global.
             */
            $totalCents += $lineTotalCents;
        }

        /**
         * Enregistrement du total final de la commande.
         */
        $order->setTotalCents($totalCents);

        /**
         * Sauvegarde initiale de la commande avant création Stripe.
         */
        $entityManager->persist($order);
        $entityManager->flush();

        /**
         * Création de la session Stripe à partir de la commande.
         * On ne change pas la logique Stripe ici.
         */
        $session = $stripeCheckoutService->createCheckoutSession($order);

        /**
         * On sauvegarde l'identifiant de session Stripe
         * pour relier la commande locale au paiement Stripe.
         */
        $order->setStripeSessionId($session->id);
        $order->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->json([
            'message' => 'Session Stripe créée depuis le panier',
            'orderId' => $order->getId(),
            'sessionId' => $session->id,
            'checkoutUrl' => $session->url,
        ]);
    }

    /**
     * Recrée une session Stripe pour une commande existante
     * encore en attente de paiement.
     */
    #[Route('/session/{id}', name: 'session', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createSession(
        Order $order,
        StripeCheckoutService $stripeCheckoutService,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'error' => 'Utilisateur non authentifié',
            ], 401);
        }

        /**
         * Vérifie que la commande appartient bien
         * à l'utilisateur connecté.
         */
        if ($order->getUser() !== $user) {
            return $this->json([
                'error' => 'Commande non autorisée',
            ], 403);
        }

        /**
         * Une session Stripe ne peut être recréée
         * que pour une commande encore payable.
         */
        if ($order->getStatus() !== Order::STATUS_PENDING_PAYMENT) {
            return $this->json([
                'error' => 'Commande non payable',
            ], 400);
        }

        /**
         * Sécurité : une commande vide ne doit pas être payable.
         */
        if ($order->getItems()->isEmpty()) {
            return $this->json([
                'error' => 'Commande vide',
            ], 400);
        }

        /**
         * Création d'une nouvelle session Stripe.
         */
        $session = $stripeCheckoutService->createCheckoutSession($order);

        /**
         * Mise à jour de la commande avec la nouvelle session.
         */
        $order->setStripeSessionId($session->id);
        $order->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->json([
            'message' => 'Session Stripe créée',
            'orderId' => $order->getId(),
            'sessionId' => $session->id,
            'checkoutUrl' => $session->url,
        ]);
    }
}