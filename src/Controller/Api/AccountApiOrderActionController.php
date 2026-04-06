<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\Shipment;
use App\Entity\User;
use App\Service\Order\OrderActionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/account/orders')]
class AccountApiOrderActionController extends AbstractController
{
    public function __construct(
        private readonly OrderActionService $orderActionService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Annule réellement une commande côté métier.
     *
     * Règles actuelles :
     * - la commande doit appartenir au client connecté
     * - l'annulation doit être autorisée par OrderActionService
     * - si une expédition existe encore à un stade précoce,
     *   on la bascule aussi en statut logistique "annulée"
     *
     * Important :
     * cette action ne traite pas encore Stripe,
     * remboursement auto ou logique financière avancée.
     * Elle traite ici l'annulation métier de la commande.
     */
    #[Route('/{id}/cancel', name: 'api_account_order_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(Order $order): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Utilisateur non authentifié.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        /**
         * Sécurité :
         * on vérifie que la commande appartient bien
         * à l'utilisateur connecté.
         */
        if ($order->getUser()?->getId() !== $user->getId()) {
            return $this->json([
                'message' => 'Accès refusé à cette commande.',
            ], Response::HTTP_FORBIDDEN);
        }

        /**
         * Vérification métier centralisée :
         * si l'annulation n'est pas autorisée,
         * on renvoie une erreur métier explicite.
         */
        if (!$this->orderActionService->canCancel($order)) {
            return $this->json([
                'message' => 'Cette commande ne peut plus être annulée.',
            ], Response::HTTP_CONFLICT);
        }

        // On passe le statut global de commande à "annulée".
        $order->setStatus(Order::STATUS_CANCELLED);
        $order->setUpdatedAt(new \DateTimeImmutable());

        /**
         * Si une expédition existe encore,
         * on la marque aussi comme annulée.
         *
         * Comme l'accès est déjà protégé par canCancel(),
         * on sait normalement qu'on est encore dans un état précoce.
         */
        $shipment = $order->getShipment();

        if ($shipment instanceof Shipment) {
            $shipment->setLogisticStatus(Shipment::STATUS_CANCELLED);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Commande annulée avec succès.',
            'order' => [
                'id' => $order->getId(),
                'status' => $order->getStatus(),
                'updatedAt' => $order->getUpdatedAt()?->format(DATE_ATOM),
                'availableActions' => $this->orderActionService->getAvailableActions($order),
            ],
        ], Response::HTTP_OK);
    }
}