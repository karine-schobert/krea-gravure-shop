<?php

namespace App\Controller\Api;

use App\Repository\ShopPageSettingsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ShopPageApiController extends AbstractController
{
    #[Route('/api/shop/page', name: 'api_shop_page', methods: ['GET'])]
    public function __invoke(ShopPageSettingsRepository $repository): JsonResponse
    {
        $page = $repository->findOneBy(['isActive' => true], ['id' => 'DESC']);

        if (!$page) {
            return $this->json([
                'eyebrow' => 'Atelier artisanal · Bois gravé',
                'title' => 'La boutique Krea Gravure',
                'description' => 'Découvre les différentes collections de l’atelier : boules de Noël, porte-clés, décorations murales, petites attentions personnalisées… Chaque création est réalisée sur commande, avec ton texte ou tes prénoms.',
            ]);
        }

        return $this->json([
            'eyebrow' => $page->getEyebrow(),
            'title' => $page->getTitle(),
            'description' => $page->getDescription(),
        ]);
    }
}