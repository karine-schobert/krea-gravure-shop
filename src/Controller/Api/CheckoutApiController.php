<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\AddressRepository;
use App\Repository\ProductOfferRepository;
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
     * associe une adresse de livraison,
     * puis génère une session Stripe.
     *
     * Attendu côté front :
     * {
     *   "items": [
     *     {
     *       "productId": 1,
     *       "quantity": 2,
     *       "offerId": 5,
     *       "customization": "Charlotte"
     *     },
     *     {
     *       "productId": 4,
     *       "quantity": 1
     *     }
     *   ],
     *   "addressId": 3
     * }
     */
    #[Route('', name: 'from_cart', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function checkoutFromCart(
        Request $request,
        ProductRepository $productRepository,
        ProductOfferRepository $productOfferRepository,
        AddressRepository $addressRepository,
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
         * Lecture du JSON envoyé par le front.
         */
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'error' => 'Payload JSON invalide',
            ], 400);
        }

        /**
         * Vérifie la présence des lignes du panier.
         */
        if (
            !isset($data['items']) ||
            !is_array($data['items']) ||
            count($data['items']) === 0
        ) {
            return $this->json([
                'error' => 'Panier invalide ou vide',
            ], 400);
        }

        /**
         * Vérifie la présence de l'adresse de livraison.
         */
        $addressId = $data['addressId'] ?? null;

        if (!$addressId) {
            return $this->json([
                'error' => 'Aucune adresse de livraison sélectionnée',
            ], 400);
        }

        /**
         * On garde l'email du client dans la commande
         * comme snapshot du moment de l'achat.
         */
        $email = $user->getEmail();

        if (!$email) {
            return $this->json([
                'error' => 'Aucun email utilisateur disponible',
            ], 400);
        }

        /**
         * Recherche de l'adresse sélectionnée.
         */
        $address = $addressRepository->find($addressId);

        if (!$address) {
            return $this->json([
                'error' => 'Adresse introuvable',
            ], 404);
        }

        /**
         * Sécurité : l'adresse doit appartenir
         * à l'utilisateur connecté.
         */
        if ($address->getUser() !== $user) {
            return $this->json([
                'error' => 'Adresse non autorisée',
            ], 403);
        }

        /**
         * Création de la commande.
         */
        $order = new Order();
        $order->setUser($user);
        $order->setEmail($email);
        $order->setStatus(Order::STATUS_PENDING_PAYMENT);
        $order->setCurrency('eur');
        $order->setUpdatedAt(new \DateTimeImmutable());

        /**
         * Association de l'adresse du carnet.
         */
        $order->setAddress($address);

        /**
         * Snapshot figé de l'adresse de livraison.
         * Cela permet de conserver les informations
         * même si le client modifie ensuite son carnet.
         */
        $fullName = trim(sprintf(
            '%s %s',
            $address->getFirstName() ?? '',
            $address->getLastName() ?? ''
        ));

        $order->setShippingFullName($fullName !== '' ? $fullName : null);
        $order->setShippingAddressLine($address->getAddress());
        $order->setShippingPostalCode($address->getPostalCode());
        $order->setShippingCity($address->getCity());
        $order->setShippingCountry($address->getCountry());
        $order->setShippingPhone($address->getPhone());
        $order->setShippingInstructions($address->getInstructions());

        $totalCents = 0;

        /**
         * Parcours des lignes panier
         * pour construire les OrderItem.
         */
        foreach ($data['items'] as $row) {
            $productId = $row['productId'] ?? null;
            $quantity = (int) ($row['quantity'] ?? 0);
            $offerId = $row['offerId'] ?? null;
            $customization = $row['customization'] ?? null;

            if (!$productId || $quantity < 1) {
                return $this->json([
                    'error' => 'Ligne panier invalide',
                    'row' => $row,
                ], 400);
            }

            /**
             * Recherche du produit.
             */
            $product = $productRepository->find($productId);

            if (!$product) {
                return $this->json([
                    'error' => sprintf('Produit introuvable : %s', $productId),
                ], 404);
            }

            /**
             * Par défaut :
             * - on garde le prix du produit
             * - le titre affiché reste celui du produit
             */
            $unitPriceCents = $product->getPriceCents();
            $productTitleSnapshot = $product->getTitle();
            $offerTitle = '';

            /**
             * Si une offre est fournie,
             * elle devient la référence commerciale
             * pour le prix et le libellé.
             */
            if ($offerId !== null) {
                $offer = $productOfferRepository->find($offerId);

                if (!$offer) {
                    return $this->json([
                        'error' => sprintf('Offre introuvable : %s', $offerId),
                    ], 404);
                }

                /**
                 * Sécurité : l'offre doit appartenir
                 * au produit envoyé par le front.
                 */
                if ($offer->getProduct()?->getId() !== $product->getId()) {
                    return $this->json([
                        'error' => 'Offre non liée au produit sélectionné',
                        'row' => $row,
                    ], 400);
                }

                /**
                 * Sécurité : on évite de laisser passer
                 * une offre désactivée au checkout.
                 */
                if (!$offer->isActive()) {
                    return $this->json([
                        'error' => 'Offre non disponible',
                        'row' => $row,
                    ], 400);
                }

                /**
                 * Si une offre est présente,
                 * c'est son prix qui fait foi.
                 */
                $unitPriceCents = $offer->getPriceCents();
                $offerTitle = trim((string) $offer->getTitle());

                if ($offerTitle !== '') {
                    $productTitleSnapshot .= ' - ' . $offerTitle;
                }
            }

            /**
             * Si une personnalisation est présente,
             * on l'ajoute au titre snapshot pour Stripe
             * et pour la lisibilité de la commande.
             */
            if (is_string($customization) && trim($customization) !== '') {
                $productTitleSnapshot .= ' - Personnalisation : ' . trim($customization);
            }

            /**
             * Calcul du total de ligne.
             */
            $lineTotalCents = $unitPriceCents * $quantity;

            /**
             * Création de la ligne de commande.
             */
            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($product);
            $orderItem->setProductTitle($productTitleSnapshot);
            $orderItem->setProductImage($product->getImage());
            $orderItem->setProductSlug($product->getSlug());
            $orderItem->setUnitPriceCents($unitPriceCents);
            $orderItem->setQuantity($quantity);
            $orderItem->setLineTotalCents($lineTotalCents);

            /**
             * Pour l'instant, la personnalisation est bien lue
             * depuis le front et visible dans le titre snapshot,
             * mais elle n'est pas encore stockée dans un champ dédié
             * de OrderItem tant que l'entité ne le prévoit pas.
             */

            $order->addItem($orderItem);

            $totalCents += $lineTotalCents;
        }

        /**
         * Enregistre le total final.
         */
        $order->setTotalCents($totalCents);

        /**
         * Sauvegarde initiale de la commande
         * avant génération de la session Stripe.
         */
        $entityManager->persist($order);
        $entityManager->flush();

        /**
         * Création de la session Stripe.
         */
        $session = $stripeCheckoutService->createCheckoutSession($order);

        /**
         * Sauvegarde de l'ID de session Stripe
         * pour relier Stripe à la commande locale.
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