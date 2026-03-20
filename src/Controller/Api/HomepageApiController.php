<?php

namespace App\Controller\Api;

use App\Repository\CategoryRepository;
use App\Repository\HomepageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HomepageApiController extends AbstractController
{
    #[Route('/api/homepage', name: 'api_homepage', methods: ['GET'])]
    public function index(
        HomepageRepository $homepageRepository,
        CategoryRepository $categoryRepository
    ): JsonResponse {
        $homepage = $homepageRepository->findOneBy([]);

        if (!$homepage) {
            return $this->json([
                'error' => 'Homepage non trouvée'
            ], 404);
        }

        /**
         * =========================
         * 🔥 RÉCUP CATÉGORIES
         * =========================
         */
        $categories = $categoryRepository->findAll();

        $categoriesData = array_map(function ($category) {
            return [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),

                // ✅ IMPORTANT
                'image' => $category->getImagePath(),
            ];
        }, $categories);

        return $this->json([
            /**
             * =========================
             * HERO
             * =========================
             */
            'hero' => [
                'eyebrow' => $homepage->getHeroEyebrow(),
                'title' => $homepage->getHeroTitle(),
                'description' => $homepage->getHeroDescription(),

                'primaryCta' => [
                    'label' => $homepage->getHeroPrimaryCtaLabel(),
                    'link' => $homepage->getHeroPrimaryCtaLink(),
                ],

                'secondaryCta' => [
                    'label' => $homepage->getHeroSecondaryCtaLabel(),
                    'link' => $homepage->getHeroSecondaryCtaLink(),
                ],

                'image' => $homepage->getHeroImage(),
            ],

            /**
             * =========================
             * ABOUT
             * =========================
             */
            'about' => [
                'title' => $homepage->getAboutTitle(),
                'text1' => $homepage->getAboutText1(),
                'text2' => $homepage->getAboutText2(),
                'image' => $homepage->getAboutImage(),
                'benefits' => $homepage->getBenefits(),
            ],

            /**
             * =========================
             * SHOP
             * =========================
             */
            'shop' => [
                'title' => $homepage->getShopTitle(),
                'subtitle' => $homepage->getShopSubtitle(),
                'description' => $homepage->getShopDescription(),

                // 🔥 NOUVEAU
                'categories' => $categoriesData,

                // 🔥 ON GARDE
                'products' => array_map(function ($product) {
                    return [
                        'id' => $product->getId(),
                        'name' => $product->getTitle(),
                        'price' => $product->getPriceCents(),

                        // ⚠️ OPTION PRO
                        'image' => $product->getImagePath(),

                        'slug' => $product->getSlug(),
                    ];
                }, $homepage->getFeaturedProducts()->toArray()),
            ],
        ]);
    }
}