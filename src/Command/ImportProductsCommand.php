<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\SeasonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'app:import-products',
    description: 'Importe ou met à jour automatiquement les catégories, produits et saisons depuis /public/uploads'
)]
class ImportProductsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private CategoryRepository $categoryRepository,
        private ProductRepository $productRepository,
        private SeasonRepository $seasonRepository,
        private SluggerInterface $slugger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // =========================
            // DOSSIERS DE BASE
            // =========================
            $productPath = __DIR__ . '/../../public/uploads/products';
            $categoryPath = __DIR__ . '/../../public/uploads/categories';

            if (!is_dir($productPath)) {
                $output->writeln('<error>❌ Dossier products introuvable</error>');
                return Command::FAILURE;
            }

            if (!is_dir($categoryPath)) {
                $output->writeln('<error>❌ Dossier categories introuvable</error>');
                return Command::FAILURE;
            }

            $output->writeln("📁 Products path : $productPath");
            $output->writeln("📁 Categories path : $categoryPath");

            // =========================
            // LISTE DES DOSSIERS SAISONNIERS
            // Si le nom du dossier principal correspond
            // à un slug de saison existant, on l'ajoute
            // automatiquement aux produits importés.
            // =========================
            $seasonSlugs = [
                'noel',
                'halloween',
                'paques',
                'saint-valentin',
                'fete-des-meres',
                'fete-des-peres',
            ];

            // =========================
            // DOSSIERS PRINCIPAUX
            // Chaque dossier principal dans /products
            // correspond à une catégorie.
            // Exemple :
            // - products/bijoux
            // - products/noel
            // - products/maison
            // =========================
            $mainFolders = scandir($productPath);

            foreach ($mainFolders as $mainFolder) {
                if ($mainFolder === '.' || $mainFolder === '..') {
                    continue;
                }

                $mainFolderPath = $productPath . '/' . $mainFolder;

                if (!is_dir($mainFolderPath)) {
                    continue;
                }

                $output->writeln('');
                $output->writeln("<comment>=========================</comment>");
                $output->writeln("<comment>📂 Dossier principal : $mainFolder</comment>");
                $output->writeln("<comment>=========================</comment>");

                // =========================
                // CATÉGORIE
                // Le slug catégorie = nom du dossier principal
                // Exemple : noel, bijoux, maison...
                // =========================
                $categorySlug = $mainFolder;
                $categoryName = ucwords(str_replace('-', ' ', $categorySlug));

                $category = $this->categoryRepository->findOneBy([
                    'slug' => $categorySlug
                ]);

                if (!$category) {
                    $category = new Category();
                    $category->setName($categoryName);
                    $category->setSlug($categorySlug);

                    $this->em->persist($category);

                    $output->writeln("<info>✔ Catégorie créée : $categorySlug</info>");
                } else {
                    $output->writeln("<info>ℹ Catégorie trouvée : $categorySlug</info>");
                }

                // =========================
                // IMAGE DE CATÉGORIE
                // On cherche un fichier dans /uploads/categories
                // avec le même nom que le slug catégorie.
                // Ordre de priorité :
                // webp > png > jpg > jpeg
                // =========================
                $extensions = ['webp', 'png', 'jpg', 'jpeg'];
                $categoryImageFile = null;

                foreach ($extensions as $ext) {
                    $testFile = $categorySlug . '.' . $ext;
                    $fullPath = $categoryPath . '/' . $testFile;

                    $output->writeln("🔍 Test image catégorie : $fullPath");

                    if (file_exists($fullPath)) {
                        $categoryImageFile = $testFile;
                        break;
                    }
                }

                if ($categoryImageFile) {
                    $category->setImage($categoryImageFile);
                    $output->writeln("<info>🖼 Image catégorie enregistrée : $categoryImageFile</info>");
                } else {
                    $output->writeln("<comment>ℹ Aucune image catégorie trouvée pour : $categorySlug</comment>");
                }

                // =========================
                // SAISON
                // Si le dossier principal fait partie des slugs
                // saisonniers autorisés, on cherche une saison
                // avec le même slug en base.
                // Exemple :
                // dossier "noel" -> cherche season.slug = noel
                // =========================
                $season = null;

                if (in_array($mainFolder, $seasonSlugs, true)) {
                    $season = $this->seasonRepository->findOneBy([
                        'slug' => $mainFolder
                    ]);

                    if ($season) {
                        $output->writeln("<info>🎄 Saison trouvée : {$season->getName()} ({$season->getSlug()})</info>");
                    } else {
                        $output->writeln("<comment>ℹ Dossier saisonnier détecté mais aucune saison trouvée en base pour : $mainFolder</comment>");
                    }
                } else {
                    $output->writeln("<comment>ℹ Pas de saison attendue pour : $mainFolder</comment>");
                }

                // =========================
                // PARCOURS RÉCURSIF DU DOSSIER
                // Permet de lire aussi les sous-dossiers :
                // ex: /noel/village-de-noel/village-rouge.webp
                // ex: /bijoux/collection-naturelle/aile-boisee.webp
                // =========================
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($mainFolderPath, \FilesystemIterator::SKIP_DOTS)
                );

                foreach ($iterator as $fileInfo) {
                    if (!$fileInfo->isFile()) {
                        continue;
                    }

                    $extension = strtolower($fileInfo->getExtension());

                    // On ne garde que les fichiers image
                    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                        continue;
                    }

                    $filename = $fileInfo->getFilename();
                    $name = pathinfo($filename, PATHINFO_FILENAME);

                    // =========================
                    // PRIORITÉ AU WEBP
                    // Si un fichier .webp existe pour le même nom,
                    // on ignore jpg/jpeg/png pour éviter les doublons.
                    // Exemple :
                    // bonhomme-bleu.jpg + bonhomme-bleu.webp
                    // => on garde bonhomme-bleu.webp
                    // =========================
                    if ($extension !== 'webp') {
                        $webpEquivalent = $fileInfo->getPath() . '/' . $name . '.webp';

                        if (file_exists($webpEquivalent)) {
                            $output->writeln("⏭ Fichier ignoré : $filename (version webp présente)");
                            continue;
                        }
                    }

                    // =========================
                    // CHEMIN RELATIF PRODUIT
                    // Exemple :
                    // noel/village-de-noel/village-rouge.webp
                    // =========================
                    $absolutePath = $fileInfo->getPathname();
                    $relativePath = str_replace($productPath . '/', '', $absolutePath);

                    // =========================
                    // SLUG PRODUIT
                    // Basé sur le nom du fichier
                    // =========================
                    $slug = (string) $this->slugger->slug($name)->lower();

                    $product = $this->productRepository->findOneBy([
                        'slug' => $slug
                    ]);

                    if (!$product) {
                        $product = new Product();
                        $product->setSlug($slug);

                        $output->writeln("➕ Création produit : $slug");
                    } else {
                        $output->writeln("🔄 Mise à jour produit : $slug");
                    }

                    // =========================
                    // DONNÉES PRODUIT
                    // =========================
                    $product->setTitle(ucwords(str_replace('-', ' ', $name)));
                    $product->setImage($relativePath);
                    $product->setPriceCents(990); // prix par défaut
                    $product->setIsActive(true);
                    $product->setCategory($category);

                    // =========================
                    // SAISON SUR LE PRODUIT
                    // Ajoute la saison trouvée au produit
                    // si elle n'est pas déjà liée.
                    // =========================
                    if ($season && !$product->getSeasons()->contains($season)) {
                        $product->addSeason($season);
                        $output->writeln("<info>   ➕ Saison '{$season->getSlug()}' ajoutée à {$product->getSlug()}</info>");
                    }

                    $this->em->persist($product);
                }
            }

            // =========================
            // ENREGISTREMENT FINAL
            // =========================
            $this->em->flush();

            $output->writeln('');
            $output->writeln('<info>🔥 IMPORT COMPLET TERMINÉ</info>');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>❌ Erreur : ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}