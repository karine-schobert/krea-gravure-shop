<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\String\Slugger\SluggerInterface;


class ProductCrudController extends AbstractCrudController
{
    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    /**
     * Configuration globale du CRUD :
     * - labels FR
     * - tri par défaut
     * - recherche
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined()
            ->setSearchFields(['id', 'title', 'slug', 'description']);
    }

    /**
     * Actions :
     * - Ajoute une action "Détail" sur la page listing
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    /**
     * Filtres sur la page listing
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', 'Titre'))
            ->add(TextFilter::new('slug', 'Slug'))
            ->add(NumericFilter::new('priceCents', 'Prix (centimes)'))
            ->add(BooleanFilter::new('isActive', 'Actif'));
    }

    /**
     * Champs (listing / détail / form new/edit)
     * - Listing : image + titre + catégorie + prix + actif + créé
     * - Détail : tout
     * - Form : upload image + champs essentiels
     */
    public function configureFields(string $pageName): iterable
    {
        // ID uniquement en listing + detail
        $id = IdField::new('id')->onlyOnIndex();

        // Titre produit
        $title = TextField::new('title', 'Titre');

        // Slug (optionnel, auto-généré si vide)
        $slug = TextField::new('slug', 'Slug')
            ->setHelp('Laisse vide pour auto-générer depuis le titre.');

        // Catégorie (ManyToOne)
        $category = AssociationField::new('category', 'Catégorie');

        //Season
            $seasons = AssociationField::new('seasons', 'Saisons')
            ->formatValue(function ($value, $entity) {
                return implode(', ', array_map(
                    fn($season) => $season->getName(),
                    $entity->getSeasons()->toArray()
                ));
            });

        // Description longue (cache en listing)
        $description = TextareaField::new('description', 'Description')
            ->hideOnIndex();

        // Prix en centimes affiché en €
        $price = MoneyField::new('priceCents', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(true);

        // Actif/inactif
        $isActive = BooleanField::new('isActive', 'Actif');

        // Image : upload + affichage (basePath = affichage, uploadDir = stockage)
        $image = ImageField::new('image', 'Image')
            ->setBasePath('/uploads/products')          // URL publique
            ->setUploadDir('public/uploads/products')   // dossier réel
            ->setUploadedFileNamePattern('[timestamp]-[randomhash].[extension]')
            ->setRequired(false);

        // Dates (readonly)
        $createdAt = DateTimeField::new('createdAt', 'Créé le')
            ->setFormTypeOption('disabled', true);

        $updatedAt = DateTimeField::new('updatedAt', 'Modifié le')
            ->setFormTypeOption('disabled', true);

        // ---------- PAGE INDEX (listing) ----------
        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $image, $title, $category, $seasons, $price, $isActive, $createdAt];
        }

        // ---------- PAGE DETAIL ----------
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $image, $title, $slug, $category,  $seasons,$description, $price, $isActive, $createdAt, $updatedAt];
        }

        // ---------- NEW / EDIT ----------
        // Note : sur edit, EasyAdmin permet de remplacer l’image en re-upload
        return [$title, $slug, $image, $category, $seasons, $description, $price, $isActive, $createdAt, $updatedAt];
    }

    /**
     * Persist (création) :
     * - createdAt + updatedAt déjà gérés par tes lifecycle callbacks,
     *   mais on garde des sécurités.
     * - isActive par défaut
     * - slug auto si vide
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        // Sécurité : createdAt si jamais null (normalement non avec ton __construct + PrePersist)
        if (null === $entityInstance->getCreatedAt()) {
            $entityInstance->setCreatedAt(new \DateTimeImmutable());
        }

        // Sécurité : isActive par défaut
        // (dans ton entity c’est déjà true, donc ceci est surtout une double sécurité)
        if (null === $entityInstance->isActive()) {
            $entityInstance->setIsActive(true);
        }

        // Slug auto si vide
        if (!$entityInstance->getSlug() && $entityInstance->getTitle()) {
            $slug = $this->slugger->slug($entityInstance->getTitle())->lower();
            $entityInstance->setSlug($slug);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Update (édition) :
     * - slug auto si vide
     * - updatedAt est déjà géré par PreUpdate dans ton entity
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        // Slug auto si vide (ne remplace pas un slug déjà rempli)
        if (!$entityInstance->getSlug() && $entityInstance->getTitle()) {
            $slug = $this->slugger->slug($entityInstance->getTitle())->lower();
            $entityInstance->setSlug($slug);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}