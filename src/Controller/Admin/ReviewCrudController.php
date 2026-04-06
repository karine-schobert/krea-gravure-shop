<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
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
                'status',
                'user.email',
                'user.firstName',
                'user.lastName',
                'product.title',
                'product.slug',
                'orderItem.id',
            ])
            ->setPaginatorPageSize(20)

            /**
             * On garde l’affichage inline des actions
             * pour respecter ton rendu admin actuel.
             */
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            /**
             * Bouton détail sur l’index
             */
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            /**
             * On garde ton ordre visuel actuel
             */
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE])

            /**
             * Respect de tes classes CSS custom
             */
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action
                    ->setLabel('Consulter')
                    ->addCssClass('crud-action-show');
            })

            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action
                    ->setLabel('Modifier')
                    ->addCssClass('crud-action-edit');
            })

            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action
                    ->setLabel('Supprimer')
                    ->addCssClass('crud-action-delete');
            })

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

            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, function (Action $action) {
                return $action->setLabel('Enregistrer et revenir');
            })

            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, function (Action $action) {
                return $action->setLabel('Enregistrer et continuer');
            })

            /**
             * Les avis viennent du front client.
             * On empêche donc la création manuelle côté admin.
             */
            ->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id')
            ->hideOnForm();

        /**
         * User et Product peuvent rester en autocomplete
         * si leurs CRUD existent déjà dans l’admin.
         */
        $user = AssociationField::new('user', 'Client')
            ->autocomplete();

        $product = AssociationField::new('product', 'Produit')
            ->autocomplete();

        /**
         * Important :
         * orderItem ne doit PAS être en autocomplete ici,
         * sinon EasyAdmin cherche un CRUD dédié.
         *
         * On l’affiche seulement en détail.
         */
        $orderItem = AssociationField::new('orderItem', 'Ligne de commande')
            ->onlyOnDetail();

        $rating = IntegerField::new('rating', 'Note');

        $status = ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => Review::STATUS_PENDING,
                'Approuvé' => Review::STATUS_APPROVED,
                'Refusé' => Review::STATUS_REJECTED,
            ])
            ->renderExpanded(false)
            ->renderAsNativeWidget(false);

        $comment = TextareaField::new('comment', 'Commentaire')
            ->hideOnIndex();

        $createdAt = DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();

        $updatedAt = DateTimeField::new('updatedAt', 'Modifié le')
            ->hideOnForm();

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $user,
                $product,
                $rating,
                $status,
                $createdAt,
            ];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $user,
                $product,
                $orderItem,
                $rating,
                $status,
                $comment,
                $createdAt,
                $updatedAt,
            ];
        }

        /**
         * PAGE_EDIT :
         * on limite volontairement l’édition admin
         * aux champs utiles de modération.
         */
        return [
            $status,
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('user')
            ->add('product')
            ->add('orderItem')
            ->add('rating')
            ->add('status')
            ->add('createdAt')
            ->add('updatedAt');
    }
}