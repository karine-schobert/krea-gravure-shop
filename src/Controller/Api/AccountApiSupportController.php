<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\SupportTicket;
use App\Entity\User;
use App\Repository\SupportTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/account')]
class AccountApiSupportController extends AbstractController
{
    #[Route('/orders/{id}/support', name: 'api_account_order_support', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createSupportTicket(
        Order $order,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        if ($order->getUser() !== $user) {
            return $this->json([
                'message' => 'Accès refusé à cette commande.',
            ], 403);
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->json([
                'message' => 'Payload JSON invalide.',
            ], 400);
        }

        $category = trim((string) ($data['category'] ?? ''));
        $subject = trim((string) ($data['subject'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if ($category === '') {
            return $this->json([
                'message' => 'La catégorie est obligatoire.',
            ], 400);
        }

        if (!array_key_exists($category, SupportTicket::CATEGORIES)) {
            return $this->json([
                'message' => 'Catégorie invalide.',
            ], 400);
        }

        if ($message === '') {
            return $this->json([
                'message' => 'Le message est obligatoire.',
            ], 400);
        }

        /*
         * Si le sujet n'est pas envoyé par le front,
         * on le génère automatiquement à partir de la catégorie.
         */
        if ($subject === '') {
            $subject = SupportTicket::CATEGORIES[$category];
        }

        $ticket = new SupportTicket();
        $ticket->setUser($user);
        $ticket->setOrder($order);
        $ticket->setCategory($category);
        $ticket->setSubject($subject);
        $ticket->setMessage($message);
        $ticket->setStatus('OPEN');
        $ticket->setCreatedAt(new \DateTimeImmutable());

        $em->persist($ticket);
        $em->flush();

        return $this->json([
            'message' => 'Ticket support créé avec succès.',
            'ticket' => $this->serializeSupportTicket($ticket),
        ], 201);
    }

    #[Route('/support-tickets', name: 'api_account_support_ticket_list', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listSupportTickets(
        SupportTicketRepository $supportTicketRepository
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        $tickets = $supportTicketRepository->findByUserOrderedByNewest($user);

        return $this->json([
            'items' => array_map(
                fn(SupportTicket $ticket): array => $this->serializeSupportTicket($ticket),
                $tickets
            ),
            'total' => count($tickets),
        ]);
    }

    #[Route('/support-tickets/{id}', name: 'api_account_support_ticket_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function showSupportTicket(
        int $id,
        SupportTicketRepository $supportTicketRepository
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        $ticket = $supportTicketRepository->findOneByIdAndUser($id, $user);

        if (!$ticket instanceof SupportTicket) {
            return $this->json([
                'message' => 'Ticket introuvable.',
            ], 404);
        }

        return $this->json($this->serializeSupportTicket($ticket));
    }

    /**
     * Structure JSON stable renvoyée au front.
     *
     * On normalise ici :
     * - les informations principales du ticket
     * - les infos utiles de commande
     * - le statut front simplifié
     * - les dates ISO 8601
     */
    private function serializeSupportTicket(SupportTicket $ticket): array
    {
        $order = $ticket->getOrder();

        return [
            'id' => $ticket->getId(),
            'orderId' => $order?->getId(),
            'orderNumber' => $order?->getId() !== null
                ? sprintf('#%d', $order->getId())
                : null,

            'category' => $ticket->getCategory(),
            'categoryLabel' => $ticket->getCategory() !== null
                ? (SupportTicket::CATEGORIES[$ticket->getCategory()] ?? $ticket->getCategory())
                : null,

            'subject' => $ticket->getSubject(),
            'message' => $ticket->getMessage(),
            'status' => $this->normalizeSupportStatus($ticket->getStatus()),
            'rawStatus' => $ticket->getStatus(),
            'adminReply' => $ticket->getAdminReply(),
            'createdAt' => $ticket->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $ticket->getUpdatedAt()?->format(DATE_ATOM),
            'answeredAt' => $ticket->getAnsweredAt()?->format(DATE_ATOM),
        ];
    }

    /**
     * Mapping des statuts back vers des statuts front simples.
     */
    private function normalizeSupportStatus(?string $status): string
    {
        return match ($status) {
            'OPEN' => 'pending',
            'IN_PROGRESS' => 'in_progress',
            'ANSWERED' => 'answered',
            'RESOLVED', 'CLOSED' => 'closed',
            default => 'pending',
        };
    }
}
