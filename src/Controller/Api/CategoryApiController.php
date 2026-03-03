<?php

namespace App\Controller\Api;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class CategoryApiController extends AbstractController
{
    #[Route('/api/categories', name: 'api_categories_list', methods: ['GET'])]
    public function list(CategoryRepository $repo): JsonResponse
    {
        $categories = $repo->findBy([], ['id' => 'DESC']);

        $data = array_map(function ($c) {
            /** @var \App\Entity\Category $c */
            return [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'slug' => method_exists($c, 'getSlug') ? $c->getSlug() : null,
                // optionnel : compteur produits si relation products existe
                'productsCount' => method_exists($c, 'getProducts') && $c->getProducts()
                    ? $c->getProducts()->count()
                    : 0,
            ];
        }, $categories);

        return $this->json($data);
    }
}