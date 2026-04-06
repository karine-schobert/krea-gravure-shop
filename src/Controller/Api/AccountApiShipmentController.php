<?php

namespace App\Controller\Api;

use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/account')]
class AccountApiShipmentController extends AbstractController
{
    /**
     * Retourne les informations logistiques d'une commande
     * appartenant au client connecté.
     *
     * Cette route sert de base pour alimenter la timeline
     * côté front sans mélanger toute la logique dans
     * le contrôleur principal des commandes.
     */
    #[Route('/orders/{id}/shipment', name: 'api_account_order_shipment_show', methods: ['GET'])]
    public function show(Order $order): JsonResponse
    {
        $user = $this->getUser();

        /**
         * Sécurité minimale :
         * on bloque si personne n'est connecté
         * ou si la commande n'appartient pas au client courant.
         */
        if (!$user || $order->getUser() !== $user) {
            return $this->json([
                'message' => 'Commande introuvable.',
            ], 404);
        }

        $shipment = $order->getShipment();

        /**
         * Si aucune expédition n'est encore créée,
         * on retourne un bloc neutre plutôt qu'une erreur serveur.
         */
        if (!$shipment) {
            return $this->json([
                'shipment' => null,
            ]);
        }

        return $this->json([
            'shipment' => [
                'id' => $shipment->getId(),
                'logisticStatus' => $shipment->getLogisticStatus(),
                'carrier' => $shipment->getCarrier(),
                'trackingNumber' => $shipment->getTrackingNumber(),
                'trackingUrl' => $shipment->getTrackingUrl(),
                'shippedAt' => $shipment->getShippedAt()?->format(DATE_ATOM),
                'estimatedDeliveryAt' => $shipment->getEstimatedDeliveryAt()?->format(DATE_ATOM),
                'deliveredAt' => $shipment->getDeliveredAt()?->format(DATE_ATOM),
                'lastTrackingSyncAt' => $shipment->getLastTrackingSyncAt()?->format(DATE_ATOM),
                'createdAt' => $shipment->getCreatedAt()?->format(DATE_ATOM),
                'updatedAt' => $shipment->getUpdatedAt()?->format(DATE_ATOM),
            ],
        ]);
    }
}