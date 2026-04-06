<?php

namespace App\Service\Order;

use App\Entity\Order;
use App\Entity\Shipment;

/**
 * Service centralisant la logique métier
 * des actions disponibles sur une commande.
 *
 * Objectif :
 * - éviter de dupliquer la logique dans plusieurs contrôleurs
 * - garder une seule source de vérité côté back
 * - permettre au front d'afficher uniquement les actions autorisées
 *
 * Règle métier générale retenue :
 * - Order = statut global de commande
 * - Shipment = statut logistique
 */
class OrderActionService
{
    /**
     * Retourne toutes les actions disponibles
     * pour une commande donnée.
     *
     * Ce format est pratique pour être renvoyé
     * tel quel dans l'API détail commande.
     */
    public function getAvailableActions(Order $order): array
    {
        return [
            'canEdit' => $this->canEdit($order),
            'canCancel' => $this->canCancel($order),
            'canRequestRefund' => $this->canRequestRefund($order),
            'canTrack' => $this->canTrack($order),
            'canRetryPayment' => $this->canRetryPayment($order),
            'canReportIssue' => $this->canReportIssue($order),
            'canReview' => $this->canReview($order),
        ];
    }

    /**
     * Indique si la commande peut encore être modifiée.
     *
     * Logique actuelle :
     * - possible si la commande est encore "souple" côté métier
     * - et si la logistique n'a pas dépassé le stade préparatoire
     *
     * Cas visés :
     * - correction d'adresse
     * - demande de modification légère
     * - ajustement avant vraie expédition
     *
     * Important :
     * ici on parle de "peut demander une modification"
     * ou "commande encore modifiable métier".
     * On ne parle pas encore d'un vrai PATCH complet
     * des lignes de commande.
     */
    public function canEdit(Order $order): bool
    {
        $orderStatus = $order->getStatus();
        $shipmentStatus = $this->getShipmentStatus($order);

        // Si la commande n'est pas dans un état compatible,
        // la modification n'est pas autorisée.
        if (!in_array($orderStatus, [
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_PAID,
        ], true)) {
            return false;
        }

        // Si aucune expédition n'existe encore,
        // on considère la commande comme encore modifiable.
        if ($shipmentStatus === null) {
            return true;
        }

        // Tant que la logistique n'a pas franchi
        // les étapes réellement bloquantes,
        // la commande reste modifiable.
        return in_array($shipmentStatus, [
            Shipment::STATUS_PREPARING,
            Shipment::STATUS_READY_TO_SHIP,
        ], true);
    }

    /**
     * Indique si la commande peut être annulée.
     *
     * Logique actuelle :
     * - proche de canEdit()
     * - on refuse si la commande est déjà annulée
     *   ou remboursée
     * - on refuse si la logistique est trop avancée
     */
    public function canCancel(Order $order): bool
    {
        $orderStatus = $order->getStatus();
        $shipmentStatus = $this->getShipmentStatus($order);

        // Une commande déjà annulée ou remboursée
        // ne peut plus être annulée une seconde fois.
        if (in_array($orderStatus, [
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
        ], true)) {
            return false;
        }

        // On limite l'annulation aux commandes
        // encore en attente de paiement ou déjà payées
        // mais pas encore réellement parties.
        if (!in_array($orderStatus, [
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_PAID,
        ], true)) {
            return false;
        }

        // Si aucune expédition n'existe,
        // l'annulation reste possible.
        if ($shipmentStatus === null) {
            return true;
        }

        // Tant que le colis est en préparation
        // ou simplement prêt à partir,
        // l'annulation reste autorisée.
        return in_array($shipmentStatus, [
            Shipment::STATUS_PREPARING,
            Shipment::STATUS_READY_TO_SHIP,
        ], true);
    }

    /**
     * Indique si le client peut demander un remboursement.
     *
     * Logique actuelle :
     * - commande payée, expédiée ou livrée = demande recevable
     * - pas si déjà annulée ou remboursée
     *
     * Ici on parle d'une "demande de remboursement"
     * côté métier, pas d'un remboursement Stripe automatique.
     */
    public function canRequestRefund(Order $order): bool
    {
        $orderStatus = $order->getStatus();
        $shipmentStatus = $this->getShipmentStatus($order);

        if (in_array($orderStatus, [
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
        ], true)) {
            return false;
        }

        // Une commande payée peut faire l'objet
        // d'une demande de remboursement.
        if ($orderStatus === Order::STATUS_PAID) {
            return true;
        }

        // Même si le statut Order reste simplement "PAID",
        // on veut accepter la demande si la logistique montre
        // que la commande a déjà circulé ou été livrée.
        return in_array($shipmentStatus, [
            Shipment::STATUS_SHIPPED,
            Shipment::STATUS_IN_TRANSIT,
            Shipment::STATUS_OUT_FOR_DELIVERY,
            Shipment::STATUS_DELIVERED,
            Shipment::STATUS_DELIVERY_ISSUE,
            Shipment::STATUS_RETURNED,
        ], true);
    }

    /**
     * Indique si le client peut suivre sa commande.
     *
     * On autorise le suivi seulement si :
     * - une expédition existe
     * - et qu'elle est assez avancée pour avoir un sens côté client
     */
    public function canTrack(Order $order): bool
    {
        $shipment = $order->getShipment();

        if (!$shipment) {
            return false;
        }

        return in_array($shipment->getLogisticStatus(), [
            Shipment::STATUS_READY_TO_SHIP,
            Shipment::STATUS_SHIPPED,
            Shipment::STATUS_IN_TRANSIT,
            Shipment::STATUS_OUT_FOR_DELIVERY,
            Shipment::STATUS_DELIVERED,
            Shipment::STATUS_DELIVERY_ISSUE,
            Shipment::STATUS_RETURNED,
        ], true);
    }

    /**
     * Indique si le client peut reprendre le paiement.
     *
     * Règle simple :
     * - commande en attente de paiement
     * - ou paiement échoué
     */
    public function canRetryPayment(Order $order): bool
    {
        return in_array($order->getStatus(), [
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_FAILED,
        ], true);
    }

    /**
     * Indique si le client peut signaler un problème.
     *
     * Cette action reste volontairement large :
     * elle sert de filet de sécurité UX.
     */
    public function canReportIssue(Order $order): bool
    {
        return in_array($order->getStatus(), [
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_PAID,
            Order::STATUS_FAILED,
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
        ], true) || $order->getShipment() !== null;
    }

    /**
     * Indique si le client peut laisser un avis.
     *
     * Dans l'état actuel du projet,
     * on reste volontairement permissif :
     * - si la commande est payée
     * - ou si elle est livrée côté logistique
     */
    public function canReview(Order $order): bool
    {
        if ($order->getStatus() === Order::STATUS_PAID) {
            return true;
        }

        return $this->getShipmentStatus($order) === Shipment::STATUS_DELIVERED;
    }

    /**
     * Petit helper interne pour récupérer
     * le statut logistique sans répéter du code.
     */
    private function getShipmentStatus(Order $order): ?string
    {
        return $order->getShipment()?->getLogisticStatus();
    }
}