<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class ReviewCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Avis')
            ->setEntityLabelInPlural('Avis clients')
            ->setPageTitle(Crud::PAGE_INDEX, 'Avis clients')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de l’avis')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l’avis')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un avis')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields([
                'id',
                'rating',
                'comment',
                'user.email',
                'user.firstName',
                'user.lastName',
                'product.title',
                'product.slug',
            ])
            ->setPaginatorPageSize(20)

            // Affiche les actions directement sur la ligne
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Ajoute le bouton "Consulter" sur la page index
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // Force l'ordre visible des actions sur l'index
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE])

            // Bouton consulter
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action
                    ->setLabel('Consulter')
                    ->addCssClass('crud-action-show');
            })

            // Bouton modifier
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action
                    ->setLabel('Modifier')
                    ->addCssClass('crud-action-edit');
            })

            // Bouton supprimer
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action
                    ->setLabel('Supprimer')
                    ->addCssClass('crud-action-delete');
            })

            // Boutons sur la page détail
            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) {
                return $action
                    ->setLabel('Modifier')
                    ->addCssClass('crud-action-edit');
            })

            ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
                return $action
                    ->setLabel('Supprimer')
                    ->addCssClass('crud-action-delete');
            })

            ->update(Crud::PAGE_DETAIL, Action::INDEX, function (Action $action) {
                return $action
                    ->setLabel('Retour')
                    ->addCssClass('crud-action-back');
            })

            // Boutons sur la page édition
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action->setLabel('Enregistrer et revenir');
            })

            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, function (Action $action) {
                return $action->setLabel('Enregistrer et continuer');
            })

            // Les avis viennent du front client, pas de création manuelle en admin
            ->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id')
            ->hideOnForm();

        $user = AssociationField::new('user', 'Client')
            ->autocomplete();

        $product = AssociationField::new('product', 'Produit')
            ->autocomplete();

        $rating = IntegerField::new('rating', 'Note');

        $comment = TextareaField::new('comment', 'Commentaire')
            ->hideOnIndex();

        $createdAt = DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $user,
                $product,
                $rating,
                $createdAt,
            ];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $user,
                $product,
                $rating,
                $comment,
                $createdAt,
            ];
        }

        return [
            $user,
            $product,
            $rating,
            $comment,
            $createdAt,
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('user')
            ->add('product')
            ->add('rating')
            ->add('createdAt');
    }
}