<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\Order;
use App\Entity\OrderItem;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

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

        $persistedProducts = [];

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
            $persistedProducts[] = $p;
        }

       // =========================
            // 3) Users (Admin + 2 clients)
            // =========================
            $admin = (new User())
                ->setEmail('admin@krea.local')
                ->setFirstName('Admin')
                ->setLastName('Krea')
                ->setRoles(['ROLE_ADMIN']);
            $admin->setPassword($this->hasher->hashPassword($admin, 'Admin123!'));
            $manager->persist($admin);

            $client1 = (new User())
                ->setEmail('client1@krea.local')
                ->setFirstName('Anthony')
                ->setLastName('Schobert')
                ->setRoles(['ROLE_USER']);
            $client1->setPassword($this->hasher->hashPassword($client1, 'Client123!'));
            $manager->persist($client1);

            $client2 = (new User())
                ->setEmail('client2@krea.local')
                ->setFirstName('Client')
                ->setLastName('Deux')
                ->setRoles(['ROLE_USER']);
            $client2->setPassword($this->hasher->hashPassword($client2, 'Client123!'));
            $manager->persist($client2); 

        // =========================
        // 4) Commande test
        // =========================
        // Commande une
        $order = new Order();
        $order->setUser($client1);
        $order->setEmail($client1->getEmail());
        $order->setStatus(Order::STATUS_PENDING_PAYMENT);
        $order->setCurrency('eur');

        $product1 = $persistedProducts[0] ?? null;
        $product2 = $persistedProducts[4] ?? null;

        $totalCents = 0;
        
        // Produit 1
        if ($product1) {
           $item1 = new OrderItem();
            $item1->setProduct($product1);
            $item1->setProductTitle($product1->getTitle());
            $item1->setUnitPriceCents($product1->getPriceCents());
            $item1->setQuantity(1);
            $item1->setLineTotalCents($product1->getPriceCents() * 1);

            $order->addItem($item1);
            $totalCents += $product1->getPriceCents() * 1;
        }

        // Produit 2
        if ($product2) {
            $item2 = new OrderItem();
            $item2->setProduct($product2);
            $item2->setProductTitle($product2->getTitle());
            $item2->setUnitPriceCents($product2->getPriceCents());
            $item2->setQuantity(2);
            $item2->setLineTotalCents($product2->getPriceCents() * 2);

            $order->addItem($item2);
            $totalCents += $product2->getPriceCents() * 2;
        }

        $order->setTotalCents($totalCents);

        $manager->persist($order);

        // Commande deux
        $order2 = new Order();
        $order2->setUser($client2);
        $order2->setEmail($client2->getEmail());
        $order2->setStatus(Order::STATUS_PAID);
        $order2->setCurrency('eur');

        $totalCents2 = 0;

        $product3 = $persistedProducts[2] ?? null;

        
        if ($product3) {
            $item3 = new OrderItem();
            $item3->setProduct($product3);
            $item3->setProductTitle($product3->getTitle());
            $item3->setUnitPriceCents($product3->getPriceCents());
            $item3->setQuantity(1);
            $item3->setLineTotalCents($product3->getPriceCents());

            $order2->addItem($item3);
            $totalCents2 += $product3->getPriceCents();
        }

        $order2->setTotalCents($totalCents2);
        $manager->persist($order2);

                // =========================
                // Flush final
                // =========================
                $manager->flush();
            }
}