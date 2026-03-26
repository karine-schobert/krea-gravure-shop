<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductOffer;
use App\Form\ProductOfferType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly SluggerInterface $slugger
    ) {}

    /**
     * Entité gérée par ce CRUD.
     */
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    /**
     * Configuration générale du CRUD.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des produits')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter un produit')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier un produit')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du produit')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined()
            ->setSearchFields([
                'id',
                'title',
                'slug',
                'description',
            ]);
    }

    /**
     * Configuration des actions EasyAdmin.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Ajout du bouton détail dans la liste
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // Personnalisation des boutons sur la liste
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $action) => $action
                ->setLabel('Voir')
                ->setIcon('fa fa-eye')
                ->addCssClass('crud-action-show'))

            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $action) => $action
                ->setLabel('Modifier')
                ->setIcon('fa fa-pen')
                ->addCssClass('crud-action-edit'))

            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action) => $action
                ->setLabel('Supprimer')
                ->setIcon('fa fa-trash')
                ->addCssClass('crud-action-delete'))

            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $action) => $action
                ->setLabel('Ajouter un produit')
                ->setIcon('fa fa-plus')
                ->addCssClass('crud-action-new'))

            // Personnalisation des boutons sur la page détail
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn(Action $action) => $action
                ->setLabel('Modifier')
                ->setIcon('fa fa-pen')
                ->addCssClass('crud-action-edit'))

            ->update(Crud::PAGE_DETAIL, Action::DELETE, fn(Action $action) => $action
                ->setLabel('Supprimer')
                ->setIcon('fa fa-trash')
                ->addCssClass('crud-action-delete'))

            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->setIcon('fa fa-arrow-left')
                ->addCssClass('crud-action-back'))

            // Bouton retour sur édition
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->setIcon('fa fa-arrow-left')
                ->addCssClass('crud-action-back'))

            // Bouton retour sur création
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->setIcon('fa fa-arrow-left')
                ->addCssClass('crud-action-back'))

            // Libellés des boutons de sauvegarde
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, fn(Action $action) => $action
                ->setLabel('Enregistrer les modifications'))

            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, fn(Action $action) => $action
                ->setLabel('Enregistrer et continuer'))

            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, fn(Action $action) => $action
                ->setLabel('Créer le produit'));
    }

    /**
     * Filtres disponibles dans le listing admin.
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', 'Titre'))
            ->add(TextFilter::new('slug', 'Slug'))
            ->add(EntityFilter::new('category', 'Catégorie principale'))
            ->add(EntityFilter::new('productCollection', 'Collection principale'))
            ->add(EntityFilter::new('seasons', 'Saisons'))
            ->add(NumericFilter::new('priceCents', 'Prix catalogue (centimes)'))
            ->add(BooleanFilter::new('isActive', 'Actif'));
    }

    /**
     * Champs affichés selon la page EasyAdmin.
     *
     * Objectifs :
     * - conserver ton architecture actuelle
     * - ne rien casser côté base / API / front
     * - alléger l'expérience admin
     * - préparer l'automatisation du Bloc 2
     */
    public function configureFields(string $pageName): iterable
    {
        // =========================================================
        // CHAMPS TECHNIQUES / DE BASE
        // =========================================================

        $id = IdField::new('id', 'ID')
            ->hideOnForm();

        $title = TextField::new('title', 'Titre')
            ->setHelp('Nom principal affiché sur la boutique.');

        $slug = TextField::new('slug', 'Slug')
            ->setFormTypeOption('disabled', true)
            ->setHelp('Slug généré automatiquement à partir du titre.');

        $description = TextareaField::new('description', 'Description')
            ->hideOnIndex()
            ->setHelp('Description affichée sur la fiche produit.');

        $price = MoneyField::new('priceCents', 'Prix catalogue')
            ->setCurrency('EUR')
            ->setStoredAsCents(true)
            ->setHelp('Prix catalogue interne. Les vrais prix de vente sont gérés dans les offres.');

        $isActive = BooleanField::new('isActive', 'Actif')
            ->renderAsSwitch(false)
            ->setHelp('Produit visible et exploitable dans ton catalogue.');

        $image = ImageField::new('image', 'Image')
            ->setBasePath('/uploads/products')
            ->setUploadDir('public/uploads/products')
            ->setUploadedFileNamePattern('[timestamp]-[randomhash].[extension]')
            ->setRequired(false)
            ->setHelp('Image principale du produit.');

        $createdAt = DateTimeField::new('createdAt', 'Créé le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();

        $updatedAt = DateTimeField::new('updatedAt', 'Modifié le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();

        // =========================================================
        // ORGANISATION PRINCIPALE
        // =========================================================

        $category = AssociationField::new('category', 'Catégorie principale')
            ->setRequired(false)
            ->autocomplete()
            ->setHelp('Catégorie principale utilisée pour classer le produit.');

        $productCollection = AssociationField::new('productCollection', 'Collection principale')
            ->setRequired(false)
            ->autocomplete()
            ->setHelp('Collection principale si le produit appartient à une série.');

        // =========================================================
        // ORGANISATION AVANCÉE - FORMULAIRE
        // =========================================================

        $additionalCategoriesForm = AssociationField::new('additionalCategories', 'Catégories secondaires')
            ->setRequired(false)
            ->autocomplete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Option avancée : ajoute ici des catégories secondaires.');

        $additionalCollectionsForm = AssociationField::new('additionalCollections', 'Collections secondaires')
            ->setRequired(false)
            ->autocomplete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Option avancée : ajoute ici des collections secondaires.');

        $seasonsForm = AssociationField::new('seasons', 'Saisons')
            ->setRequired(false)
            ->autocomplete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Option avancée : associe ce produit à une ou plusieurs saisons.');

        // =========================================================
        // ORGANISATION AVANCÉE - AFFICHAGE DETAIL
        // =========================================================

        $additionalCategoriesDisplay = AssociationField::new('additionalCategories', 'Catégories secondaires')
            ->formatValue(function ($value, $entity) {
                $categories = $entity->getAdditionalCategories()->toArray();

                if (empty($categories)) {
                    return '—';
                }

                return implode(', ', array_map(
                    fn($category) => $category->getName(),
                    $categories
                ));
            });

        $additionalCollectionsDisplay = AssociationField::new('additionalCollections', 'Collections secondaires')
            ->formatValue(function ($value, $entity) {
                $collections = $entity->getAdditionalCollections()->toArray();

                if (empty($collections)) {
                    return '—';
                }

                return implode(', ', array_map(
                    fn($collection) => $collection->getName(),
                    $collections
                ));
            });

        $seasonsDisplay = AssociationField::new('seasons', 'Saisons')
            ->formatValue(function ($value, $entity) {
                $seasons = $entity->getSeasons()->toArray();

                if (empty($seasons)) {
                    return '—';
                }

                return implode(', ', array_map(
                    fn($season) => $season->getName(),
                    $seasons
                ));
            });

        // =========================================================
        // OFFRES COMMERCIALES
        // =========================================================
        // La relation Doctrine du produit est "offers".
        // Chaque Product possède plusieurs ProductOffer.
        // =========================================================

        $offers = CollectionField::new('offers', 'Offres commerciales')
            ->setEntryType(ProductOfferType::class)
            ->setFormTypeOption('by_reference', false)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded(false)
            ->setHelp('Ajoute ici les offres de vente : unité, lot, collection complète, offre spéciale, etc.')
            ->addCssClass('product-offers-collection')
            ->onlyOnForms();

        // =========================================================
        // PAGE INDEX
        // =========================================================

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $image,
                $title,
                $category,
                $productCollection,
                $price,
                $isActive,
                $createdAt,
            ];
        }

        // =========================================================
        // PAGE DETAIL
        // =========================================================

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                FormField::addFieldset('Informations principales'),
                $id,
                $image,
                $title,
                $slug,
                $description,

                FormField::addFieldset('Organisation'),
                $category,
                $productCollection,
                $additionalCategoriesDisplay,
                $additionalCollectionsDisplay,
                $seasonsDisplay,

                FormField::addFieldset('Configuration'),
                $price,
                $isActive,

                FormField::addFieldset('Informations techniques'),
                $createdAt,
                $updatedAt,
            ];
        }

        // =========================================================
        // PAGE NEW
        // =========================================================
        // Création allégée : l'objectif est d'aller vite.
        // Si aucune offre n'est ajoutée manuellement,
        // une offre "À l’unité" sera créée automatiquement à la sauvegarde.
        // =========================================================

        if (Crud::PAGE_NEW === $pageName) {
            return [
                FormField::addFieldset('Informations principales'),
                $title,
                $slug,
                $description,

                FormField::addFieldset('Organisation principale'),
                $category,
                $productCollection,

                FormField::addFieldset('Configuration'),
                $price,
                $isActive,

                FormField::addFieldset('Offres commerciales'),
                $offers,

                FormField::addFieldset('Image'),
                $image,
            ];
        }

        // =========================================================
        // PAGE EDIT
        // =========================================================
        // Édition plus complète avec une partie avancée séparée.
        // =========================================================

        return [
            FormField::addFieldset('Informations principales'),
            $title,
            $slug,
            $description,

            FormField::addFieldset('Organisation principale'),
            $category,
            $productCollection,

            FormField::addFieldset('Organisation avancée'),
            $additionalCategoriesForm,
            $additionalCollectionsForm,
            $seasonsForm,

            FormField::addFieldset('Configuration'),
            $price,
            $isActive,

            FormField::addFieldset('Offres commerciales'),
            $offers,

            FormField::addFieldset('Image'),
            $image,

            FormField::addFieldset('Informations techniques'),
            $createdAt,
            $updatedAt,
        ];
    }

    /**
     * Génère un slug propre à partir du titre.
     */
    private function generateSlugFromTitle(string $title): string
    {
        return $this->slugger->slug($title)->lower()->toString();
    }

    /**
     * Crée une offre par défaut "À l’unité" si le produit n'a encore aucune offre.
     *
     * Cette méthode sert de première automatisation du Bloc 2.
     * Elle évite d'avoir un produit enregistré sans offre commerciale exploitable.
     */
    private function createDefaultOfferIfNeeded(Product $product, EntityManagerInterface $entityManager): void
    {
        // Si une offre existe déjà, on ne fait rien.
        if (!$product->getOffers()->isEmpty()) {
            return;
        }

        $defaultOffer = new ProductOffer();

        $defaultOffer->setTitle('À l’unité');
        $defaultOffer->setSaleType(ProductOffer::SALE_TYPE_UNIT);
        $defaultOffer->setQuantity(1);
        $defaultOffer->setPriceCents($product->getPriceCents() ?? 0);
        $defaultOffer->setIsActive(true);

        // Liaison avec le produit
        $product->addOffer($defaultOffer);

        $entityManager->persist($defaultOffer);
    }


    /**
     * Persistance à la création.
     *
     * Logique conservée :
     * - createdAt auto si besoin
     * - slug auto depuis le titre
     *
     * Automatisation ajoutée :
     * - si aucune offre n'a été créée manuellement, création auto
     *   d'une offre "À l’unité"
     * - isActive passe à true par défaut si non défini
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        // Date de création automatique si absente
        if (null === $entityInstance->getCreatedAt()) {
            $entityInstance->setCreatedAt(new \DateTimeImmutable());
        }

        // Activation par défaut pour éviter les oublis en création
        //if (null === $entityInstance->isActive()) {
        //   $entityInstance->setIsActive(true);
        //}

        // Génération automatique du slug depuis le titre
        if ($entityInstance->getTitle()) {
            $entityInstance->setSlug(
                $this->generateSlugFromTitle($entityInstance->getTitle())
            );
        }

        // Sauvegarde du produit d'abord
        parent::persistEntity($entityManager, $entityInstance);

        // Puis création de l'offre par défaut si aucune offre n'existe
        $this->createDefaultOfferIfNeeded($entityInstance, $entityManager);

        // Flush final pour enregistrer l'offre auto si elle a été créée
        $entityManager->flush();
    }

    /**
     * Persistance à la modification.
     *
     * Si le titre change, le slug est régénéré.
     * On garde aussi le filet de sécurité :
     * si un produit n'a plus aucune offre, on lui recrée une offre par défaut.
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        // Régénération du slug à partir du titre
        if ($entityInstance->getTitle()) {
            $entityInstance->setSlug(
                $this->generateSlugFromTitle($entityInstance->getTitle())
            );
        }

        // Sauvegarde classique des modifications
        parent::updateEntity($entityManager, $entityInstance);

        // Filet de sécurité : si toutes les offres ont été supprimées,
        // on recrée une offre minimale.
        $this->createDefaultOfferIfNeeded($entityInstance, $entityManager);

        // Flush final si une offre a été créée automatiquement
        $entityManager->flush();
    }
}
