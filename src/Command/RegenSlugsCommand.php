<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\Category;
use App\Service\SluggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:regen-slugs',
    description: 'Régénère les slugs (Products + Categories) en utilisant SluggerService->uniqueSlug()'
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

        // --- 1) Categories ---
        $categories = $categoryRepo->findAll();
        $catCount = 0;

        foreach ($categories as $c) {
            /** @var Category $c */
            $newSlug = $this->sluggerService->uniqueSlug(
                Category::class,
                (string) $c->getName(),
                'slug',
                $c->getId()
            );

            if ($c->getSlug() !== $newSlug) {
                $output->writeln(sprintf('Category #%d: "%s" => %s', $c->getId(), $c->getSlug(), $newSlug));
                $c->setSlug($newSlug);
                $catCount++;
            }
        }

        // --- 2) Products ---
        $products = $productRepo->findAll();
        $prodCount = 0;

        foreach ($products as $p) {
            /** @var Product $p */
            $newSlug = $this->sluggerService->uniqueSlug(
                Product::class,
                (string) $p->getTitle(),
                'slug',
                $p->getId()
            );

            if ($p->getSlug() !== $newSlug) {
                $output->writeln(sprintf('Product #%d: "%s" => %s', $p->getId(), $p->getSlug(), $newSlug));
                $p->setSlug($newSlug);
                $prodCount++;
            }
        }

        $this->em->flush();

        $output->writeln('');
        $output->writeln(sprintf('✅ Slugs régénérés : %d catégories, %d produits', $catCount, $prodCount));

        return Command::SUCCESS;
    }
}