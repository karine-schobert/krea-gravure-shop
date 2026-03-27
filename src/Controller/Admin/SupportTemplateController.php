<?php

namespace App\Controller\Admin;

use App\Support\SupportReplyTemplates;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SupportTemplateController extends AbstractController
{
    /**
     * Retourne les templates d'une catégorie de ticket support.
     *
     * Exemple d'URL :
     * /admin/support/templates/customization_problem
     */
    #[Route('/admin/support/templates/{category}', name: 'admin_support_templates', methods: ['GET'])]
    public function templates(string $category): JsonResponse
    {
        return $this->json([
            'category' => $category,
            'templates' => SupportReplyTemplates::forCategory($category),
        ]);
    }
}