<?php

namespace App\Controller\Admin;

use App\Entity\Homepage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

// =========================
// FIELDS EASYADMIN
// =========================
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

class HomepageCrudController extends AbstractCrudController
{
    // =========================
    // ENTITÉ
    // =========================
    public static function getEntityFqcn(): string
    {
        return Homepage::class;
    }

    /**
     * =========================
     * CONFIGURATION GLOBALE
     * =========================
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Homepage')
            ->setEntityLabelInPlural('Homepage')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion de la homepage')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de la homepage')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la homepage')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer la homepage')
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(1)
            ->setDefaultSort(['id' => 'ASC']);
    }

    /**
     * =========================
     * ACTIONS
     * =========================
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // INDEX
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

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
                ->setLabel('Créer la homepage')
                ->addCssClass('crud-action-new'))

            // DETAIL
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn(Action $action) => $action
                ->setLabel('Modifier')
                ->addCssClass('crud-action-edit'))

            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            // EDIT
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            // NEW
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'));
    }

    /**
     * =========================
     * FILTRES
     * =========================
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters;
    }

    /**
     * =========================
     * CHAMPS
     * =========================
     */
    public function configureFields(string $pageName): iterable
    {
        // =========================
        // CHAMPS COMMUNS
        // =========================
        $id = IdField::new('id', 'ID')->hideOnForm();

        // =========================
        // HERO
        // =========================
        $heroEyebrow = TextField::new('heroEyebrow', 'Petit titre')
            ->setHelp('Texte affiché au-dessus du titre principal');

        $heroTitle = TextField::new('heroTitle', 'Titre principal');

        $heroDescription = TextareaField::new('heroDescription', 'Description');

        $heroPrimaryCtaLabel = TextField::new('heroPrimaryCtaLabel', 'Texte bouton principal');

        $heroPrimaryCtaLink = TextField::new('heroPrimaryCtaLink', 'Lien bouton principal')
            ->setHelp('Exemple : /shop');

        $heroSecondaryCtaLabel = TextField::new('heroSecondaryCtaLabel', 'Texte lien secondaire');

        $heroSecondaryCtaLink = TextField::new('heroSecondaryCtaLink', 'Lien secondaire')
            ->setHelp('Exemple : /about');

        $heroImage = ImageField::new('heroImage', 'Image hero')
            ->setBasePath('/images')
            ->setUploadDir('public/images')
            ->setUploadedFileNamePattern('[timestamp]-[randomhash].[extension]')
            ->setRequired(false);

        // =========================
        // ABOUT
        // =========================
        $aboutTitle = TextField::new('aboutTitle', 'Titre section about');

        $aboutText1 = TextareaField::new('aboutText1', 'Texte 1');

        $aboutText2 = TextareaField::new('aboutText2', 'Texte 2');

        $aboutImage = ImageField::new('aboutImage', 'Image about')
            ->setBasePath('/images')
            ->setUploadDir('public/images')
            ->setUploadedFileNamePattern('[timestamp]-[randomhash].[extension]')
            ->setRequired(false);

        $benefits = ArrayField::new('benefits', 'Points forts')
            ->setHelp('Liste des avantages affichés sur la homepage');

        // =========================
        // SHOP
        // =========================
        $shopTitle = TextField::new('shopTitle', 'Titre section boutique');

        $shopSubtitle = TextareaField::new('shopSubtitle', 'Sous-titre boutique');

        $shopDescription = TextareaField::new('shopDescription', 'Description boutique');

        $featuredProducts = AssociationField::new('featuredProducts', 'Produits mis en avant')
            ->setFormTypeOptions([
                'by_reference' => false,
            ])
            ->autocomplete()
            ->formatValue(function ($value, $entity) {
                if ($entity->getFeaturedProducts()->isEmpty()) {
                    return 'Aucun produit';
                }

                return implode(', ', array_map(
                    fn($product) => $product->getTitle(),
                    $entity->getFeaturedProducts()->toArray()
                ));
            });

        // =========================
        // INDEX
        // =========================
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $heroTitle,
                $aboutTitle,
                $shopTitle,
                $featuredProducts,
            ];
        }

        // =========================
        // DETAIL
        // =========================
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,

                FormField::addFieldset('Bloc hero'),
                $heroEyebrow,
                $heroTitle,
                $heroDescription,
                $heroPrimaryCtaLabel,
                $heroPrimaryCtaLink,
                $heroSecondaryCtaLabel,
                $heroSecondaryCtaLink,
                $heroImage,

                FormField::addFieldset('Bloc about'),
                $aboutTitle,
                $aboutText1,
                $aboutText2,
                $aboutImage,
                $benefits,

                FormField::addFieldset('Bloc boutique'),
                $shopTitle,
                $shopSubtitle,
                $shopDescription,
                $featuredProducts,
            ];
        }

        // =========================
        // NEW / EDIT
        // =========================
        return [
            FormField::addFieldset('Bloc hero'),
            $heroEyebrow,
            $heroTitle,
            $heroDescription,
            $heroPrimaryCtaLabel,
            $heroPrimaryCtaLink,
            $heroSecondaryCtaLabel,
            $heroSecondaryCtaLink,
            $heroImage,

            FormField::addFieldset('Bloc about'),
            $aboutTitle,
            $aboutText1,
            $aboutText2,
            $aboutImage,
            $benefits,

            FormField::addFieldset('Bloc boutique'),
            $shopTitle,
            $shopSubtitle,
            $shopDescription,
            $featuredProducts,
        ];
    }
}
