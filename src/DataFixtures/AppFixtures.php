<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // =========================
        // 1) Catégories (3)
        // =========================
        $catBijoux = (new Category())
            ->setName('Bijoux')
            ->setSlug('bijoux');

        $catDeco = (new Category())
            ->setName('Déco')
            ->setSlug('deco');

        $catLecture = (new Category())
            ->setName('Lecture')
            ->setSlug('lecture');

        $manager->persist($catBijoux);
        $manager->persist($catDeco);
        $manager->persist($catLecture);

        // =========================
        // 2) Produits (10)
        // =========================
        $products = [
            // Bijoux (4)
            [
                'title' => "Boucles d’oreilles Naturelle — Feuille",
                'slug'  => "boucles-oreilles-naturelle-feuille",
                'description' => "Boucles d’oreilles en bois gravé, collection Naturelle. Légères, élégantes, parfaites au quotidien.",
                'priceCents' => 990,
                'image' => "placeholder-1.jpg",
                'category' => $catBijoux,
            ],
            [
                'title' => "Boucles d’oreilles Naturelle — Fleur",
                'slug'  => "boucles-oreilles-naturelle-fleur",
                'description' => "Boucles d’oreilles en bois gravé, motif floral délicat. Idée cadeau simple et raffinée.",
                'priceCents' => 990,
                'image' => "placeholder-2.jpg",
                'category' => $catBijoux,
            ],
            [
                'title' => "Boucles d’oreilles Noir Chic — Goutte",
                'slug'  => "boucles-oreilles-noir-chic-goutte",
                'description' => "Collection Noir Chic : silhouette goutte, style moderne et minimaliste. Look habillé instantané.",
                'priceCents' => 1290,
                'image' => "placeholder-3.jpg",
                'category' => $catBijoux,
            ],
            [
                'title' => "Boucles d’oreilles Noir Chic — Cercle",
                'slug'  => "boucles-oreilles-noir-chic-cercle",
                'description' => "Collection Noir Chic : forme cercle, intemporel. Ultra légères, idéales en journée comme en soirée.",
                'priceCents' => 1290,
                'image' => "placeholder-4.jpg",
                'category' => $catBijoux,
            ],

            // Déco (4)
            [
                'title' => "Dessous de verre — C’est la vie",
                'slug'  => "dessous-de-verre-cest-la-vie",
                'description' => "Dessous de verre gravé en bois, motif typographique. Parfait pour déco salon et idées cadeaux.",
                'priceCents' => 790,
                'image' => "placeholder-5.jpg",
                'category' => $catDeco,
            ],
            [
                'title' => "Dessous de verre — La lune et ses étoiles",
                'slug'  => "dessous-de-verre-lune-etoiles",
                'description' => "Dessous de verre gravé, motif lune & étoiles. Ambiance douce, poétique, très apprécié en cadeau.",
                'priceCents' => 790,
                'image' => "placeholder-6.jpg",
                'category' => $catDeco,
            ],
            [
                'title' => "Déco jardin — Cœur « Bienvenue »",
                'slug'  => "deco-jardin-coeur-bienvenue",
                'description' => "Décoration en bois pour jardin ou entrée. Message Bienvenue gravé, style naturel.",
                'priceCents' => 1490,
                'image' => "placeholder-7.jpg",
                'category' => $catDeco,
            ],
            [
                'title' => "Mini pancarte — Home",
                'slug'  => "mini-pancarte-home",
                'description' => "Petite pancarte déco en bois gravé, à poser ou accrocher. Style cocooning, simple et efficace.",
                'priceCents' => 1190,
                'image' => "placeholder-8.jpg",
                'category' => $catDeco,
            ],

            // Lecture (2)
            [
                'title' => "Marque-page — Floralys Lavande",
                'slug'  => "marque-page-floralys-lavande",
                'description' => "Marque-page en bois gravé, collection Floralys (Lavande). Idéal lecture et cadeau léger.",
                'priceCents' => 690,
                'image' => "placeholder-9.jpg",
                'category' => $catLecture,
            ],
            [
                'title' => "Marque-page — Floralys Marguerite",
                'slug'  => "marque-page-floralys-marguerite",
                'description' => "Marque-page en bois gravé, collection Floralys (Marguerite). Élégant, fin, durable.",
                'priceCents' => 690,
                'image' => "placeholder-10.jpg",
                'category' => $catLecture,
            ],
        ];

        foreach ($products as $data) {
            $p = (new Product())
                ->setTitle($data['title'])
                ->setSlug($data['slug'])
                ->setDescription($data['description'])
                ->setPriceCents($data['priceCents'])
                ->setIsActive(true)
                ->setCategory($data['category'])
                ->setImage($data['image']);

            $manager->persist($p);
        }

        // =========================
        // 3) Users (Admin + 2 clients)
        // =========================
        $admin = (new User())
            ->setEmail('admin@krea.local')
            ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'Admin123!'));
        $manager->persist($admin);

        $client1 = (new User())
            ->setEmail('client1@krea.local')
            ->setRoles(['ROLE_USER']);
        $client1->setPassword($this->hasher->hashPassword($client1, 'Client123!'));
        $manager->persist($client1);

        $client2 = (new User())
            ->setEmail('client2@krea.local')
            ->setRoles(['ROLE_USER']);
        $client2->setPassword($this->hasher->hashPassword($client2, 'Client123!'));
        $manager->persist($client2);

        // ✅ 1 seul flush
        $manager->flush();
    }
}