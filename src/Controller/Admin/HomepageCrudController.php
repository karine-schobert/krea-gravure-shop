<?php

namespace App\Controller\Admin;

use App\Entity\Homepage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

// 🔥 Fields EasyAdmin
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

class HomepageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Homepage::class;
    }

    /**
     * =========================
     * CONFIG GLOBALE CRUD
     * =========================
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Homepage')
            ->setEntityLabelInPlural('Homepage')

            // 🔥 titres des pages admin
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion de la homepage')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la homepage')

            // 🔥 affichage des actions (edit inline)
            ->showEntityActionsInlined()

            // 🔥 on force 1 seule ligne (CMS unique)
            ->setPaginatorPageSize(1)

            // 🔥 tri sécurité
            ->setDefaultSort(['id' => 'ASC']);
    }

    /**
     * =========================
     * FIELDS (FORMULAIRE ADMIN)
     * =========================
     */
    public function configureFields(string $pageName): iterable
    {
        return [

            /**
             * =========================
             * HERO
             * =========================
             */
            TextField::new('heroEyebrow', 'Petit titre (eyebrow)'),
            TextField::new('heroTitle', 'Titre principal'),
            TextareaField::new('heroDescription', 'Description'),

            // 🔥 CTA PRINCIPAL (bouton)
            TextField::new('heroPrimaryCtaLabel', 'Texte bouton principal'),
            TextField::new('heroPrimaryCtaLink', 'Lien bouton principal'),

            // 🔥 CTA SECONDAIRE (lien)
            TextField::new('heroSecondaryCtaLabel', 'Texte lien secondaire'),
            TextField::new('heroSecondaryCtaLink', 'Lien lien secondaire'),

            // 🔥 IMAGE HERO (URL ou chemin)
            ImageField::new('heroImage', 'Image Hero')
                ->setBasePath('/images')
                ->setUploadDir('public/images')
                ->setUploadedFileNamePattern('[timestamp]-[randomhash].[extension]')
                ->setRequired(false),
            

            /**
             * =========================
             * ABOUT
             * =========================
             */
            TextField::new('aboutTitle', 'Titre section About'),
            TextareaField::new('aboutText1', 'Texte 1'),
            TextareaField::new('aboutText2', 'Texte 2'),

            // 🔥 IMAGE ABOUT
          ImageField::new('aboutImage', 'Image About')
            ->setBasePath('/images')
            ->setUploadDir('public/images')
            ->setUploadedFileNamePattern('[timestamp]-[randomhash].[extension]')
            ->setRequired(false),
       

            // 🔥 BENEFITS (liste dynamique)
            ArrayField::new('benefits', 'Points forts (liste)'),


            /**
             * =========================
             * BOUTIQUE
             * =========================
             */
            TextField::new('shopTitle', 'Titre section boutique'),
            TextareaField::new('shopSubtitle', 'Sous-titre boutique'),
            TextareaField::new('shopDescription', 'Description boutique'),

            // 🔥 PRODUITS MIS EN AVANT
            AssociationField::new('featuredProducts', 'Produits affichés')
                ->setFormTypeOptions([
                    'by_reference' => false,
                ])
                ->autocomplete()
                ->formatValue(function ($value, $entity) {
                    if ($entity->getFeaturedProducts()->isEmpty()) {
                        return 'Aucun produit';
                    }

                    return implode('<br>', array_map(
                        fn($product) => $product->getTitle(),
                        $entity->getFeaturedProducts()->toArray()
                    ));
                }),
        ];
    }

    /**
     * =========================
     * ACTIONS ADMIN
     * =========================
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }
}
