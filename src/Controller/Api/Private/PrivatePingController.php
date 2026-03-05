<?php

namespace App\Controller\Api\Private;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class PrivatePingController extends AbstractController
{
    #[Route('/api/private/ping', name: 'api_private_ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return $this->json([
            'ok' => true,
            'user' => $this->getUser()?->getUserIdentifier(),
            'roles' => $this->getUser()?->getRoles(),
        ]);
    }
}