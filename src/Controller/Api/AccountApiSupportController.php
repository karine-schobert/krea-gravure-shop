<?php

namespace App\Controller\Api;

use App\Entity\SupportTicket;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/account')]
class AccountApiSupportController extends AbstractController
{

    /**
     * Création d'un ticket support pour une commande
     */
    #[Route('/orders/{id}/support', name: 'api_account_order_support', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function createSupportTicket(
        Order $order,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {

        /** utilisateur connecté */
        $user = $this->getUser();

        /** sécurité : vérifier que la commande appartient au user */
        if ($order->getUser() !== $user) {
            return $this->json([
                'error' => 'Accès refusé à cette commande.'
            ], 403);
        }

        /** récupérer les données envoyées */
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'error' => 'Payload JSON invalide.'
            ], 400);
        }

        /** validation simple */
        if (empty($data['subject']) || empty($data['message'])) {
            return $this->json([
                'error' => 'Sujet et message requis.'
            ], 400);
        }

        /** création du ticket */
        $ticket = new SupportTicket();
        $ticket->setUser($user);
        $ticket->setOrder($order);
        $ticket->setSubject($data['subject']);
        $ticket->setMessage($data['message']);
        $ticket->setStatus('OPEN');
        $ticket->setCreatedAt(new \DateTimeImmutable());

        $em->persist($ticket);
        $em->flush();

        return $this->json([
            'message' => 'Ticket support créé avec succès.'
        ]);
    }
}