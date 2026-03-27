<?php

namespace App\Controller\Admin;

use App\Entity\SupportTicket;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SupportTicketCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SupportTicket::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Ticket support')
            ->setEntityLabelInPlural('Tickets support')
            ->setPageTitle(Crud::PAGE_INDEX, 'Tickets support')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du ticket')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le ticket')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un ticket')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields([
                'id',
                'subject',
                'message',
                'status',
                'user.email',
                'order.id',
            ])
            ->setPaginatorPageSize(20);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Ajoute le bouton "Consulter" dans l'index
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

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

            // Bouton consulter sur page détail
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

            // Bouton retour
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

            // Si les tickets doivent venir uniquement du front client
            ->disable(Action::NEW);
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id')->hideOnForm();

        $user = AssociationField::new('user', 'Client')
            ->autocomplete();

        $order = AssociationField::new('order', 'Commande')
            ->autocomplete();

        $subject = TextField::new('subject', 'Sujet');

        $message = TextareaField::new('message', 'Message')
            ->hideOnIndex();

        $status = ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Ouvert' => 'OPEN',
                'Fermé' => 'CLOSED',
            ])
            ->renderExpanded(false)
            ->renderAsBadges([
                'OPEN' => 'warning',
                'CLOSED' => 'success',
            ]);

        $createdAt = DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $user,
                $order,
                $subject,
                $status,
                $createdAt,
            ];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $user,
                $order,
                $subject,
                $message,
                $status,
                $createdAt,
            ];
        }

        return [
            $user,
            $order,
            $subject,
            $message,
            $status,
            $createdAt,
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('user')
            ->add('order')
            ->add('status')
            ->add('createdAt');
    }
}