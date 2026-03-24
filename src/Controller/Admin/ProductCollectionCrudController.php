<?php

namespace App\Controller\Admin;

use App\Entity\ProductCollection;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductCollectionCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly SluggerInterface $slugger
    ) {}

    public static function getEntityFqcn(): string
    {
        return ProductCollection::class;
    }

    // =========================
    // CONFIG CRUD
    // =========================
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Collection')
            ->setEntityLabelInPlural('Collections')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des collections')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une collection')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier une collection')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de la collection')
            ->setDefaultSort([
                'position' => 'ASC',
                'id' => 'DESC',
            ])
            ->showEntityActionsInlined()
            ->setSearchFields(['id', 'name', 'slug', 'description']);
    }

    // =========================
    // ACTIONS
    // =========================
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)

            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $action) => $action
                ->setLabel('Voir')
                ->addCssClass('crud-action-show'))

            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $action) => $action
                ->setLabel('Modifier')
                ->addCssClass('crud-action-edit'))

            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action) => $action
                ->setLabel('Supprimer')
                ->addCssClass('crud-action-delete'))

            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $action) => $action
                ->setLabel('Ajouter une collection')
                ->addCssClass('crud-action-new'))

            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            ->update(Crud::PAGE_NEW, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'));
    }

    // =========================
    // FILTRES
    // =========================
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'Nom'))
            ->add(TextFilter::new('slug', 'Slug'))
            ->add(BooleanFilter::new('isActive', 'Active'));
    }

    // =========================
    // CHAMPS
    // =========================
    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id', 'ID')
            ->hideOnForm();

        $name = TextField::new('name', 'Nom')
            ->setHelp('Ex : Chic Noir, Naturelle, Noël Tradition');

        $slug = TextField::new('slug', 'Slug')
            ->setHelp('Laisse vide pour auto-générer depuis le nom.');

        $description = TextareaField::new('description', 'Description')
            ->setRequired(false)
            ->hideOnIndex();

        $image = ImageField::new('image', 'Image')
            ->setBasePath('/uploads/collections')
            ->setUploadDir('public/uploads/collections')
            ->setUploadedFileNamePattern('[slug].[extension]')
            ->setRequired(false);

        $position = IntegerField::new('position', 'Position')
            ->setHelp('Plus le chiffre est petit, plus la collection remonte.');

        $isActive = BooleanField::new('isActive', 'Active');

        $createdAt = DateTimeField::new('createdAt', 'Créée le')
            ->setFormTypeOption('disabled', true);

        $products = AssociationField::new('products', 'Produits associés')
            ->onlyOnDetail();

        // =========================
        // INDEX
        // =========================
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $name,
                $slug,
                $image,
                $position,
                $isActive,
                $createdAt,
            ];
        }

        // =========================
        // DETAIL
        // =========================
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                FormField::addFieldset('Informations principales'),

                $id,
                $name,
                $slug,
                $description,
                $image,

                FormField::addFieldset('Organisation'),

                $position,
                $isActive,

                FormField::addFieldset('Informations techniques'),

                $createdAt,
                $products,
            ];
        }

        // =========================
        // NEW / EDIT
        // =========================
        return [
            FormField::addFieldset('Informations principales'),

            $name,
            $slug,
            $description,

            FormField::addFieldset('Image'),

            $image,

            FormField::addFieldset('Organisation'),

            $position,
            $isActive,

            FormField::addFieldset('Informations techniques'),

            $createdAt,
        ];
    }

    // =========================
    // CREATE
    // =========================
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ProductCollection) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        // createdAt auto
        if (null === $entityInstance->getCreatedAt()) {
            $entityInstance->setCreatedAt(new \DateTimeImmutable());
        }

        // position par défaut
        if (null === $entityInstance->getPosition()) {
            $entityInstance->setPosition(0);
        }

        // slug auto si vide
        if (!$entityInstance->getSlug() && $entityInstance->getName()) {
            $slug = $this->slugger->slug($entityInstance->getName())->lower();
            $entityInstance->setSlug($slug);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    // =========================
    // UPDATE
    // =========================
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ProductCollection) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        // slug auto si vide
        if (!$entityInstance->getSlug() && $entityInstance->getName()) {
            $slug = $this->slugger->slug($entityInstance->getName())->lower();
            $entityInstance->setSlug($slug);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}