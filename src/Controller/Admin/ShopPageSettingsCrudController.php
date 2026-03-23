<?php

namespace App\Controller\Admin;

use App\Entity\ShopPageSettings;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ShopPageSettingsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ShopPageSettings::class;
    }
    /**
     * Actions et boutons colorés
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
                ->setLabel('Ajouter un texte')
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

            // DETAIL : souvent déjà présente, donc update
            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->setIcon('fa fa-arrow-left')
                ->addCssClass('crud-action-back'))

            // EDIT : on ajoute l'action
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->setIcon('fa fa-arrow-left')
                ->addCssClass('crud-action-back'))

            // NEW : on ajoute l'action
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


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            TextField::new('eyebrow', 'Petit titre')
                ->setHelp('Ex: Atelier artisanal · Bois gravé'),

            TextField::new('title', 'Titre principal')
                ->setHelp('Ex: La boutique Krea Gravure'),

            TextareaField::new('description', 'Description')
                ->setHelp('Texte d’introduction affiché en haut de la page produits'),

            BooleanField::new('isActive', 'Actif'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Configuration boutique')
            ->setEntityLabelInPlural('Configuration boutique');
    }
}