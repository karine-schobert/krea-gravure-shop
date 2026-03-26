<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\ProductCollection;
use App\Service\SluggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:regen-slugs',
    description: 'Régénère les slugs (Products + Categories + Collections) en utilisant SluggerService->uniqueSlug()'
)]
class RegenSlugsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SluggerService $sluggerService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $productRepo = $this->em->getRepository(Product::class);
        $categoryRepo = $this->em->getRepository(Category::class);
        $collectionRepo = $this->em->getRepository(ProductCollection::class);

        // =========================
        // 1) Categories
        // =========================
        $categories = $categoryRepo->findAll();
        $catCount = 0;

        foreach ($categories as $c) {
            /** @var Category $c */
            $name = trim((string) $c->getName());

            if ($name === '') {
                continue;
            }

            $newSlug = $this->sluggerService->uniqueSlug(
                Category::class,
                $name,
                'slug',
                $c->getId()
            );

            if ($c->getSlug() !== $newSlug) {
                $output->writeln(sprintf(
                    'Category #%d: "%s" => "%s"',
                    $c->getId(),
                    (string) $c->getSlug(),
                    $newSlug
                ));
                $c->setSlug($newSlug);
                $catCount++;
            }
        }

        // =========================
        // 2) Collections
        // =========================
        $collections = $collectionRepo->findAll();
        $collectionCount = 0;

        foreach ($collections as $collection) {
            /** @var ProductCollection $collection */
            $name = trim((string) $collection->getName());

            if ($name === '') {
                continue;
            }

            $newSlug = $this->sluggerService->uniqueSlug(
                ProductCollection::class,
                $name,
                'slug',
                $collection->getId()
            );

            if ($collection->getSlug() !== $newSlug) {
                $output->writeln(sprintf(
                    'Collection #%d: "%s" => "%s"',
                    $collection->getId(),
                    (string) $collection->getSlug(),
                    $newSlug
                ));
                $collection->setSlug($newSlug);
                $collectionCount++;
            }
        }

        // =========================
        // 3) Products
        // =========================
        $products = $productRepo->findAll();
        $prodCount = 0;

        foreach ($products as $p) {
            /** @var Product $p */
            $title = trim((string) $p->getTitle());

            if ($title === '') {
                continue;
            }

            $newSlug = $this->sluggerService->uniqueSlug(
                Product::class,
                $title,
                'slug',
                $p->getId()
            );

            if ($p->getSlug() !== $newSlug) {
                $output->writeln(sprintf(
                    'Product #%d: "%s" => "%s"',
                    $p->getId(),
                    (string) $p->getSlug(),
                    $newSlug
                ));
                $p->setSlug($newSlug);
                $prodCount++;
            }
        }

        $this->em->flush();

        $output->writeln('');
        $output->writeln(sprintf(
            '✅ Slugs régénérés : %d catégories, %d collections, %d produits',
            $catCount,
            $collectionCount,
            $prodCount
        ));

        return Command::SUCCESS;
    }
}