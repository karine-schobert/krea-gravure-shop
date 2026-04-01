<?php

namespace App\Controller\Api;

use App\Repository\StaticPageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/pages', name: 'api_pages_')]
class StaticPageApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(StaticPageRepository $staticPageRepository): JsonResponse
    {
        $pages = $staticPageRepository->findActiveOrdered();

        $data = array_map(
            fn ($page) => [
                'id' => $page->getId(),
                'title' => $page->getTitle(),
                'slug' => $page->getSlug(),
                'metaTitle' => $page->getMetaTitle(),
                'metaDescription' => $page->getMetaDescription(),
                'updatedAt' => $page->getUpdatedAt()?->format(DATE_ATOM),
            ],
            $pages
        );

        return $this->json($data);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug, StaticPageRepository $staticPageRepository): JsonResponse
    {
        $page = $staticPageRepository->findOneActiveBySlug($slug);

        if (!$page) {
            return $this->json([
                'message' => 'Page introuvable.',
            ], 404);
        }

        return $this->json([
            'id' => $page->getId(),
            'title' => $page->getTitle(),
            'slug' => $page->getSlug(),
            'content' => $page->getContent(),
            'isActive' => $page->isActive(),
            'metaTitle' => $page->getMetaTitle(),
            'metaDescription' => $page->getMetaDescription(),
            'createdAt' => $page->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $page->getUpdatedAt()?->format(DATE_ATOM),
        ]);
    }
}