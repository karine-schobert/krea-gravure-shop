<?php

namespace App\Controller\Api;

use App\Service\WorkshopRequest\WorkshopRequestCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class WorkshopRequestApiController extends AbstractController
{
    #[Route('/api/workshop-requests', name: 'api_workshop_requests_create', methods: ['POST'])]
    public function create(
        Request $request,
        WorkshopRequestCreator $workshopRequestCreator
    ): JsonResponse {
        /*
        |------------------------------------------------------------------
        | Lecture brute des champs multipart/form-data
        |------------------------------------------------------------------
        */
        $payload = $request->request->all();

        /*
        |------------------------------------------------------------------
        | Parsing JSON du champ items
        |------------------------------------------------------------------
        |
        | En V1, le front envoie :
        | items = JSON.stringify([...])
        |
        */
        $itemsRaw = $request->request->get('items');

        if ($itemsRaw === null || $itemsRaw === '') {
            $payload['items'] = [];
        } else {
            try {
                $decodedItems = json_decode($itemsRaw, true, 512, JSON_THROW_ON_ERROR);

                if (!is_array($decodedItems)) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Les données envoyées sont invalides.',
                        'errors' => [
                            'items' => [
                                'Le champ items doit contenir un tableau JSON valide.',
                            ],
                        ],
                    ], 400);
                }

                $payload['items'] = $decodedItems;
            } catch (\JsonException) {
                return $this->json([
                    'success' => false,
                    'message' => 'Les données envoyées sont invalides.',
                    'errors' => [
                        'items' => [
                            'Le JSON du champ items est invalide.',
                        ],
                    ],
                ], 400);
            }
        }

        /*
        |------------------------------------------------------------------
        | Métadonnées techniques utiles
        |------------------------------------------------------------------
        */
        $payload['ipAddress'] = $request->getClientIp();
        $payload['userAgent'] = $request->headers->get('User-Agent');

        /*
        |------------------------------------------------------------------
        | Récupération des fichiers
        |------------------------------------------------------------------
        */
        $uploadedFiles = $request->files->all('attachmentFiles');

        /*
        |------------------------------------------------------------------
        | Création via le service métier
        |------------------------------------------------------------------
        */
        $result = $workshopRequestCreator->createFromPayload(
            $payload,
            is_array($uploadedFiles) ? $uploadedFiles : []
        );

        if (!$result['success']) {
            return $this->json([
                'success' => false,
                'message' => 'Les données envoyées sont invalides.',
                'errors' => $result['errors'],
            ], 422);
        }

        $workshopRequest = $result['workshopRequest'];

        return $this->json([
            'success' => true,
            'message' => 'Votre demande atelier a bien été enregistrée.',
            'data' => [
                'id' => $workshopRequest->getId(),
                'reference' => $workshopRequest->getReference(),
                'status' => $workshopRequest->getStatus(),
                'itemsCount' => $workshopRequest->getItems()->count(),
                'attachmentsCount' => $workshopRequest->getAttachments()->count(),
            ],
        ], 201);
    }
}