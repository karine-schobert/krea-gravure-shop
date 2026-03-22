<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class OrderCrudController extends AbstractCrudController
{
    // =========================
    // ENTITY
    // =========================
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    // =========================
    // CRUD
    // =========================
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined()
            ->setSearchFields([
                'id',
                'email',
                'status',
                'currency',
                'stripeSessionId',
                'stripePaymentIntentId',
                'shippingFullName',
                'shippingCity',
                'shippingPostalCode',
            ]);
    }

    // =========================
    // ACTIONS
    // =========================
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // on ajoute le bouton "voir" sur la liste
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // on supprime la création manuelle d'une commande
            ->disable(Action::NEW)

            // labels + classes CSS homogènes
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $action) => $action
                ->setLabel('Voir')
                ->addCssClass('crud-action-show'))

            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $action) => $action
                ->setLabel('Modifier')
                ->addCssClass('crud-action-edit'))

            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action) => $action
                ->setLabel('Supprimer')
                ->addCssClass('crud-action-delete'))

            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn(Action $action) => $action
                ->setLabel('Modifier')
                ->addCssClass('crud-action-edit'))

            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            ->update(Crud::PAGE_DETAIL, Action::DELETE, fn(Action $action) => $action
            ->setLabel('Supprimer')
            ->addCssClass('crud-action-delete'));
    }

    // =========================
    // FILTERS
    // =========================
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('user', 'Client'))
            ->add(TextFilter::new('email', 'Email'))
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices([
                'Brouillon' => Order::STATUS_DRAFT,
                'En attente de paiement' => Order::STATUS_PENDING_PAYMENT,
                'Payée' => Order::STATUS_PAID,
                'Échouée' => Order::STATUS_FAILED,
                'Annulée' => Order::STATUS_CANCELLED,
                'Remboursée' => Order::STATUS_REFUNDED,
            ]))
            ->add(TextFilter::new('currency', 'Devise'))
            ->add(TextFilter::new('shippingCity', 'Ville'))
            ->add(TextFilter::new('shippingPostalCode', 'Code postal'));
    }

    // =========================
    // FIELDS
    // =========================
    public function configureFields(string $pageName): iterable
    {
        // =========================
        // Champs communs
        // =========================
        $id = IdField::new('id', 'ID')
            ->hideOnForm();

        $user = AssociationField::new('user', 'Client')
            ->setHelp('Utilisateur lié à cette commande')
            ->hideOnForm();

        $email = TextField::new('email', 'Email')
            ->setFormTypeOption('disabled', true)
            ->setHelp('Email figé au moment du passage de commande');

        $status = ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Brouillon' => Order::STATUS_DRAFT,
                'En attente de paiement' => Order::STATUS_PENDING_PAYMENT,
                'Payée' => Order::STATUS_PAID,
                'Échouée' => Order::STATUS_FAILED,
                'Annulée' => Order::STATUS_CANCELLED,
                'Remboursée' => Order::STATUS_REFUNDED,
            ])
            ->renderAsNativeWidget();

        $total = MoneyField::new('totalCents', 'Total')
            ->setCurrency('EUR')
            ->setStoredAsCents(true)
            ->setFormTypeOption('disabled', true)
            ->setHelp('Montant total figé de la commande');

        $currency = TextField::new('currency', 'Devise')
            ->setFormTypeOption('disabled', true);

        $address = AssociationField::new('address', 'Adresse liée')
            ->hideOnForm();

        $stripeSessionId = TextField::new('stripeSessionId', 'Stripe session')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', true);

        $stripePaymentIntentId = TextField::new('stripePaymentIntentId', 'PaymentIntent')
            ->hideOnIndex()
            ->setFormTypeOption('disabled', true);

        $createdAt = DateTimeField::new('createdAt', 'Créée le')
            ->setFormTypeOption('disabled', true);

        $updatedAt = DateTimeField::new('updatedAt', 'Modifiée le')
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex();

        $paidAt = DateTimeField::new('paidAt', 'Payée le')
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex();

        // =========================
        // Snapshot livraison
        // =========================
        $shippingFullName = TextField::new('shippingFullName', 'Nom livraison')
            ->setFormTypeOption('disabled', true);

        $shippingAddressLine = TextField::new('shippingAddressLine', 'Adresse livraison')
            ->setFormTypeOption('disabled', true);

        $shippingPostalCode = TextField::new('shippingPostalCode', 'Code postal')
            ->setFormTypeOption('disabled', true);

        $shippingCity = TextField::new('shippingCity', 'Ville')
            ->setFormTypeOption('disabled', true);

        $shippingCountry = TextField::new('shippingCountry', 'Pays')
            ->setFormTypeOption('disabled', true);

        $shippingPhone = TextField::new('shippingPhone', 'Téléphone')
            ->setFormTypeOption('disabled', true);

        $shippingInstructions = TextareaField::new('shippingInstructions', 'Instructions')
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex();

        // =========================
        // Lignes de commande
        // =========================
        $items = CollectionField::new('items', 'Lignes de commande')
            ->onlyOnDetail();

        // =========================
        // PAGE INDEX
        // =========================
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $user,
                $email,
                $status,
                $total,
                $createdAt,
            ];
        }

        // =========================
        // PAGE DETAIL
        // =========================
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $user,
                $email,
                $status,
                $total,
                $currency,
                $createdAt,
                $updatedAt,
                $paidAt,
                $address,
                $shippingFullName,
                $shippingAddressLine,
                $shippingPostalCode,
                $shippingCity,
                $shippingCountry,
                $shippingPhone,
                $shippingInstructions,
                $stripeSessionId,
                $stripePaymentIntentId,
                $items,
            ];
        }

        // =========================
        // PAGE EDIT
        // =========================
        return [
            $status,
        ];
    }
}