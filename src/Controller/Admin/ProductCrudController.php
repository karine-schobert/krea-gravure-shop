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
    public function __construct(private readonly SluggerInterface $slugger) {}

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    /**
     * Configuration générale du CRUD
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
            ->setSearchFields(['id','title','slug','description']);
    }

    /**
     * Actions et boutons personnalisés
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

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

            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->setIcon('fa fa-arrow-left')
                ->addCssClass('crud-action-back'))

            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->setIcon('fa fa-arrow-left')
                ->addCssClass('crud-action-back'))

            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, fn(Action $action) => $action
                ->setLabel('Enregistrer les modifications'))

            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, fn(Action $action) => $action
                ->setLabel('Enregistrer et continuer'));
    }

    /**
     * Filtres utiles sur le listing
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', 'Titre'))
            ->add(TextFilter::new('slug', 'Slug'))
            ->add(EntityFilter::new('category', 'Catégorie'))
            ->add(EntityFilter::new('productCollection', 'Collection'))
            ->add(EntityFilter::new('seasons', 'Saisons'))
            ->add(NumericFilter::new('priceCents', 'Prix (centimes)'))
            ->add(BooleanFilter::new('isActive', 'Actif'));
    }

    /**
     * Champs affichés selon la page
     */
    public function configureFields(string $pageName): iterable
    {
        // =========================
        // Champs communs
        // =========================

        $id = IdField::new('id', 'ID')->hideOnForm();

        $title = TextField::new('title', 'Titre')
            ->setHelp('Nom affiché sur la boutique.');

        $slug = TextField::new('slug', 'Slug')
            ->setHelp('Laisse vide pour le générer automatiquement depuis le titre.');

        $category = AssociationField::new('category', 'Catégorie')
            ->setHelp('Choisis la catégorie principale du produit.');

        $productCollection = AssociationField::new('productCollection', 'Collection')
            ->setRequired(false)
            ->autocomplete()
            ->setHelp('Associe ce produit à une collection si besoin.');

        // Affichage texte des saisons sur index / détail
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

        // Champ relation saisons sur formulaire
        $seasonsForm = AssociationField::new('seasons', 'Saisons')
            ->setHelp('Associe ce produit à une ou plusieurs saisons.')
            ->setFormTypeOption('by_reference', false);

        $description = TextareaField::new('description', 'Description')
            ->hideOnIndex()
            ->setHelp('Description visible sur la fiche produit.');

        $price = MoneyField::new('priceCents', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(true)
            ->setHelp('Prix enregistré en centimes dans la base.');

        $isActive = BooleanField::new('isActive', 'Actif');

        $image = ImageField::new('image', 'Image')
            ->setBasePath('/uploads/products')
            ->setUploadDir('public/uploads/products')
            ->setUploadedFileNamePattern('[timestamp]-[randomhash].[extension]')
            ->setRequired(false)
            ->setHelp('Tu peux envoyer une nouvelle image si besoin.');

        $createdAt = DateTimeField::new('createdAt', 'Créé le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();

        $updatedAt = DateTimeField::new('updatedAt', 'Modifié le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();

        // =========================
        // PAGE INDEX
        // =========================
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $image,
                $title,
                $category,
                $productCollection,
                $seasonsDisplay,
                $price,
                $isActive,
                $createdAt,
            ];
        }

        // =========================
        // PAGE DETAIL
        // =========================
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $image,
                $title,
                $slug,
                $category,
                $productCollection,
                $seasonsDisplay,
                $description,
                $price,
                $isActive,
                $createdAt,
                $updatedAt,
            ];
        }

        // =========================
        // PAGE NEW / EDIT
        // =========================
        return [
            FormField::addFieldset('Informations principales'),

            $title,
            $slug,
            $description,

            FormField::addFieldset('Organisation'),

            $category,
            $productCollection,
            $seasonsForm,
            $price,
            $isActive,

            FormField::addFieldset('Image'),

            $image,

            FormField::addFieldset('Informations techniques')
                ->hideWhenCreating(),

            $createdAt,
            $updatedAt,
        ];
    }

    /**
     * Création
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        if (null === $entityInstance->getCreatedAt()) {
            $entityInstance->setCreatedAt(new \DateTimeImmutable());
        }

        if (null === $entityInstance->isActive()) {
            $entityInstance->setIsActive(true);
        }

        if (!$entityInstance->getSlug() && $entityInstance->getTitle()) {
            $slug = $this->slugger->slug($entityInstance->getTitle())->lower();
            $entityInstance->setSlug($slug);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Édition
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        if (!$entityInstance->getSlug() && $entityInstance->getTitle()) {
            $slug = $this->slugger->slug($entityInstance->getTitle())->lower();
            $entityInstance->setSlug($slug);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}