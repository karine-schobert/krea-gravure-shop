<?php

namespace App\Service\Review;

use App\Entity\Order;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\OrderItemRepository;
use App\Repository\ReviewRepository;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Service métier responsable de la création d'un avis.
 *
 * Objectif :
 * centraliser toutes les règles métier pour éviter
 * de les dupliquer dans le contrôleur API.
 */
class ReviewCreator
{
    public function __construct(
        private readonly OrderItemRepository $orderItemRepository,
        private readonly ReviewRepository $reviewRepository,
    ) {}

    /**
     * Crée un avis à partir d'une vraie ligne de commande.
     *
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     */
    public function createFromOrderItem(
        User $user,
        int $orderItemId,
        int $rating,
        string $comment,
    ): Review {
        /**
         * 1) Vérification simple de la note
         */
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('La note doit être comprise entre 1 et 5.');
        }

        /**
         * 2) Nettoyage du commentaire
         */
        $comment = trim($comment);

        if ($comment === '') {
            throw new InvalidArgumentException('Le commentaire est obligatoire.');
        }

        /**
         * 3) Chargement de la ligne de commande
         */
        $orderItem = $this->orderItemRepository->find($orderItemId);

        if (!$orderItem) {
            throw new InvalidArgumentException('Ligne de commande introuvable.');
        }

        /**
         * 4) Vérification de la commande parente
         */
        $order = $orderItem->getOrder();

        if (!$order) {
            throw new InvalidArgumentException('Commande liée introuvable.');
        }

        /**
         * 5) Vérification de propriété :
         * la commande doit appartenir au user connecté
         */
        if ($order->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedException('Vous ne pouvez pas laisser un avis sur cette commande.');
        }

        /**
         * 6) Vérification du statut de paiement
         *
         * Ici on prend une logique souple :
         * - soit la commande a un paidAt
         * - soit son statut est PAID
         *
         * Tu pourras resserrer ensuite si tu veux une seule règle.
         */
        $isPaid =
            $order->getPaidAt() !== null ||
            $order->getStatus() === Order::STATUS_PAID;

        if (!$isPaid) {
            throw new InvalidArgumentException('Un avis ne peut être laissé que sur une commande payée.');
        }

        /**
         * 7) Vérification que la ligne porte bien un produit
         */
        $product = $orderItem->getProduct();

        if (!$product) {
            throw new InvalidArgumentException('Aucun produit n’est lié à cette ligne de commande.');
        }

        /**
         * 8) Anti-doublon :
         * un seul avis par ligne de commande
         */
        $existingReview = $this->reviewRepository->findOneBy([
            'orderItem' => $orderItem,
        ]);

        if ($existingReview) {
            throw new InvalidArgumentException('Un avis existe déjà pour cette ligne de commande.');
        }

        /**
         * 9) Création de l'avis
         */
        $review = new Review();
        $review
            ->setUser($user)
            ->setProduct($product)
            ->setOrderItem($orderItem)
            ->setRating($rating)
            ->setComment($comment)
            ->setStatus(Review::STATUS_PENDING);

        /**
         * 10) Persistance via le repository
         */
        $this->reviewRepository->save($review, true);

        return $review;
    }
}
