<?php

namespace App\Controller\Admin;

use App\Entity\ProductOffer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;

class ProductOfferCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductOffer::class;
    }

    // =========================
    // CONFIG CRUD
    // =========================
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Offre produit')
            ->setEntityLabelInPlural('Offres produit')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des offres produit')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une offre produit')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier une offre produit')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de l’offre produit')
            ->setDefaultSort([
                'position' => 'ASC',
                'id' => 'DESC',
            ])
            ->showEntityActionsInlined()
            ->setSearchFields([
                'id',
                'title',
                'saleType',
                'product.title',
            ]);
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
                ->setLabel('Ajouter une offre')
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
            ->add(EntityFilter::new('product', 'Produit'))
            ->add(TextFilter::new('title', 'Titre'))
            ->add(ChoiceFilter::new('saleType', 'Type de vente')->setChoices([
                'Unité' => 'unit',
                'Lot' => 'bundle',
                'Saisonnier' => 'seasonal',
                'Spécial' => 'special',
                'Collection complète' => 'full_collection',
            ]))
            /*->add(BooleanFilter::new('isCustomizable', 'Personnalisable'))*/
            ->add(BooleanFilter::new('isActive', 'Active'));
    }

    // =========================
    // FIELDS
    // =========================
    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id', 'ID')
            ->hideOnForm();

        $product = AssociationField::new('product', 'Produit')
            ->setHelp('Produit principal auquel cette offre est rattachée.');

        $title = TextField::new('title', 'Titre')
            ->setHelp('Ex : À l’unité, Lot de 4, Offre Noël, Collection complète.');

        $saleType = ChoiceField::new('saleType', 'Type de vente')
            ->setChoices([
                'Unité' => 'unit',
                'Lot' => 'bundle',
                'Saisonnier' => 'seasonal',
                'Spécial' => 'special',
                'Collection complète' => 'full_collection',
            ])
            ->renderAsNativeWidget();

        $quantity = IntegerField::new('quantity', 'Quantité')
            ->setHelp('Nombre d’articles inclus dans cette offre.');

        $priceCents = MoneyField::new('priceCents', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(true)
            ->setHelp('Prix enregistré en centimes dans la base.');

        $isCustomizable = BooleanField::new('isCustomizable', 'Personnalisable');

        $customizationLabel = TextField::new('customizationLabel', 'Libellé personnalisation')
            ->setRequired(false)
            ->setHelp('Ex : Texte à graver, Prénom, Mot à inscrire.');

        $customizationPlaceholder = TextField::new('customizationPlaceholder', 'Placeholder personnalisation')
            ->setRequired(false)
            ->setHelp('Ex : Ex. Charlotte');

        $customizationMaxLength = IntegerField::new('customizationMaxLength', 'Longueur maximale')
            ->setRequired(false)
            ->setHelp('Nombre maximum de caractères autorisés.');

        $isCustomizationRequired = BooleanField::new('isCustomizationRequired', 'Personnalisation obligatoire');

        $isActive = BooleanField::new('isActive', 'Active');

        $position = IntegerField::new('position', 'Position')
            ->setHelp('Plus le chiffre est petit, plus l’offre remonte.');

        $startsAt = DateTimeField::new('startsAt', 'Début')
            ->setRequired(false);

        $endsAt = DateTimeField::new('endsAt', 'Fin')
            ->setRequired(false);

        $createdAt = DateTimeField::new('createdAt', 'Créée le')
            ->setFormTypeOption('disabled', true);

        $updatedAt = DateTimeField::new('updatedAt', 'Modifiée le')
            ->setFormTypeOption('disabled', true);

        // =========================
        // INDEX
        // =========================
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $product,
                $title,
                $saleType,
                $quantity,
                $priceCents,
                $isCustomizable,
                $isActive,
                $position,
                $startsAt,
                $endsAt,
                $updatedAt,
            ];
        }

        // =========================
        // DETAIL
        // =========================
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                FormField::addFieldset('Informations principales'),

                $id,
                $product,
                $title,
                $saleType,

                FormField::addFieldset('Configuration commerciale'),

                $quantity,
                $priceCents,
                $isCustomizable,
                $isActive,
                $position,

                FormField::addFieldset('Personnalisation'),

                $customizationLabel,
                $customizationPlaceholder,
                $customizationMaxLength,
                $isCustomizationRequired,

                FormField::addFieldset('Période de validité'),

                $startsAt,
                $endsAt,

                FormField::addFieldset('Informations techniques'),

                $createdAt,
                $updatedAt,
            ];
        }

        // =========================
        // NEW / EDIT
        // =========================
        return [
            FormField::addFieldset('Informations principales'),

            $product,
            $title,
            $saleType,

            FormField::addFieldset('Configuration commerciale'),

            $quantity,
            $priceCents,
            $isCustomizable,
            $isActive,
            $position,

            FormField::addFieldset('Personnalisation'),

            $customizationLabel,
            $customizationPlaceholder,
            $customizationMaxLength,
            $isCustomizationRequired,

            FormField::addFieldset('Période de validité'),

            $startsAt,
            $endsAt,

            FormField::addFieldset('Informations techniques'),

            $createdAt,
            $updatedAt,
        ];
    }
    // =========================
    // CREATE
    // =========================
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ProductOffer) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        if (null === $entityInstance->getQuantity()) {
            $entityInstance->setQuantity(1);
        }

        if (null === $entityInstance->getPriceCents()) {
            $entityInstance->setPriceCents(0);
        }

        if (null === $entityInstance->getPosition()) {
            $entityInstance->setPosition(0);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    // =========================
    // UPDATE
    // =========================
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ProductOffer) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        if (null === $entityInstance->getQuantity()) {
            $entityInstance->setQuantity(1);
        }

        if (null === $entityInstance->getPriceCents()) {
            $entityInstance->setPriceCents(0);
        }

        if (null === $entityInstance->getPosition()) {
            $entityInstance->setPosition(0);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}
