<?php

namespace App\Controller\Admin;

use App\Entity\Season;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class SeasonCrudController extends AbstractCrudController
{
    // =========================
    // ENTITÉ
    // =========================
    public static function getEntityFqcn(): string
    {
        return Season::class;
    }

    // =========================
    // CONFIGURATION GÉNÉRALE DU CRUD
    // =========================
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Saison')
            ->setEntityLabelInPlural('Saisons')
            ->setPageTitle(Crud::PAGE_INDEX, 'Saisons')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de la saison')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la saison')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une saison')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields([
                'id',
                'name',
                'slug',
            ])
            ->showEntityActionsInlined();
    }

    // =========================
    // ACTIONS
    // =========================
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Ajoute le bouton "Voir" dans la liste
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // Ajoute le bouton "Retour à la liste" sur la page édition
            ->add(Crud::PAGE_EDIT, Action::INDEX)

            // =========================
            // PERSONNALISATION DES ACTIONS DE LA LISTE
            // =========================
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
                ->setLabel('Ajouter une saison')
                ->addCssClass('crud-action-new'))

            // =========================
            // PERSONNALISATION DES ACTIONS DES AUTRES PAGES
            // =========================
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn(Action $action) => $action
                ->setLabel('Modifier')
                ->addCssClass('crud-action-edit'))

            ->update(Crud::PAGE_DETAIL, Action::DELETE, fn(Action $action) => $action
                ->setLabel('Supprimer')
                ->addCssClass('crud-action-delete'));
    }

    // =========================
    // FILTRES
    // =========================
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'Nom'))
            ->add(TextFilter::new('slug', 'Slug'));
    }

    // =========================
    // CHAMPS
    // =========================
    public function configureFields(string $pageName): iterable
    {
        // =========================
        // CHAMPS COMMUNS
        // =========================
        $id = IdField::new('id', 'ID')->hideOnForm();

        $name = TextField::new('name', 'Nom')
            ->setHelp('Exemple : Noël, Halloween, Fête des mères');

        $slug = TextField::new('slug', 'Slug')
            ->setHelp('Exemple : noel, halloween, fete-des-meres');

        // =========================
        // PAGE LISTE
        // =========================
        if ($pageName === Crud::PAGE_INDEX) {
            return [
                $id,
                $name,
                $slug,
            ];
        }

        // =========================
        // PAGE DÉTAIL
        // =========================
        if ($pageName === Crud::PAGE_DETAIL) {
            return [
                $id,
                $name,
                $slug,
            ];
        }

        // =========================
        // PAGE CRÉATION / ÉDITION
        // =========================
        return [
            $name,
            $slug,
        ];
    }
}