<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class CustomerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    // =========================
    // CONFIGURATION GÉNÉRALE DU CRUD
    // =========================
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Client')
            ->setEntityLabelInPlural('Clients')
            ->setPageTitle(Crud::PAGE_INDEX, 'Clients')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Fiche client')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le client')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields([
                'id',
                'email',
                'firstName',
                'lastName',
            ])
            ->showEntityActionsInlined();
    }

    // =========================
    // ACTIONS
    // =========================
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // =========================
            // DÉSACTIVATION DES ACTIONS INUTILES
            // =========================

            // On ne crée pas de client manuellement depuis l'admin
            ->disable(Action::NEW)

            // =========================
            // AJOUT DES ACTIONS UTILES
            // =========================

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
            ->add(TextFilter::new('email', 'Email'))
            ->add(TextFilter::new('firstName', 'Prénom'))
            ->add(TextFilter::new('lastName', 'Nom'));
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

        $email = EmailField::new('email', 'Email');

        $firstName = TextField::new('firstName', 'Prénom')
            ->formatValue(fn($value) => $value ?: '—');

        $lastName = TextField::new('lastName', 'Nom')
            ->formatValue(fn($value) => $value ?: '—');

        // =========================
        // CHAMPS CALCULÉS POUR LA LISTE
        // =========================

        $addressesCount = IntegerField::new('addressesCount', 'Nb adresses')
            ->onlyOnIndex();

        $ordersCount = IntegerField::new('ordersCount', 'Nb commandes')
            ->onlyOnIndex();

        $totalSpent = MoneyField::new('totalSpentEuros', 'Total dépensé')
            ->setCurrency('EUR')
            ->setStoredAsCents(false);

        // =========================
        // RELATIONS / DÉTAIL
        // =========================

        $roles = ArrayField::new('roles', 'Rôles')
            ->onlyOnDetail();

        $cart = AssociationField::new('cart', 'Panier')
            ->onlyOnDetail();

        $addresses = AssociationField::new('addresses', 'Adresses')
            ->onlyOnDetail();

        $orders = AssociationField::new('orders', 'Commandes')
            ->onlyOnDetail();

        // =========================
        // PAGE LISTE
        // =========================
        if ($pageName === Crud::PAGE_INDEX) {
            return [
                $id,
                $email,
                $firstName,
                $lastName,
                $addressesCount,
                $ordersCount,
                $totalSpent,
            ];
        }

        // =========================
        // PAGE DÉTAIL
        // =========================
        if ($pageName === Crud::PAGE_DETAIL) {
            return [
                $id,
                $email,
                $firstName,
                $lastName,
                $totalSpent,
                $roles,
                $cart,
                $addresses,
                $orders,
            ];
        }

        // =========================
        // PAGE ÉDITION
        // =========================
        if ($pageName === Crud::PAGE_EDIT) {
            return [
                $firstName,
                $lastName,
            ];
        }

        // =========================
        // FALLBACK
        // =========================
        return [
            $id,
            $email,
            $firstName,
            $lastName,
        ];
    }

    // =========================
    // REQUÊTE DE LA LISTE
    // =========================
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // Exclut les administrateurs du CRUD clients
        return $qb
            ->andWhere('entity.roles NOT LIKE :adminRole')
            ->setParameter('adminRole', '%ROLE_ADMIN%');
    }
}
