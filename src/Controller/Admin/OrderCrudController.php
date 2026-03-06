<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

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
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::DELETE, Action::EDIT);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('email', 'Email'))
            ->add(TextFilter::new('status', 'Statut'))
            ->add(TextFilter::new('currency', 'Devise'));
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id', 'ID');

        $user = AssociationField::new('user', 'Utilisateur')
            ->hideOnForm();

        $email = TextField::new('email', 'Email');

        $status = TextField::new('status', 'Statut');

        $total = MoneyField::new('totalCents', 'Total')
            ->setCurrency('EUR')
            ->setStoredAsCents(true);

        $currency = TextField::new('currency', 'Devise');

        $stripeSessionId = TextField::new('stripeSessionId', 'Stripe session')
            ->hideOnIndex();

        $stripePaymentIntentId = TextField::new('stripePaymentIntentId', 'PaymentIntent')
            ->hideOnIndex();

        $createdAt = DateTimeField::new('createdAt', 'Créée le')
            ->setFormTypeOption('disabled', true);

        $updatedAt = DateTimeField::new('updatedAt', 'Modifiée le')
            ->hideOnIndex();

        $paidAt = DateTimeField::new('paidAt', 'Payée le')
            ->hideOnIndex();

        $items = CollectionField::new('items', 'Lignes de commande')
            ->onlyOnDetail();

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $user,
                $email,
                $status,
                $total,
                $currency,
                $createdAt,
            ];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $user,
                $email,
                $status,
                $total,
                $currency,
                $stripeSessionId,
                $stripePaymentIntentId,
                $createdAt,
                $updatedAt,
                $paidAt,
                $items,
            ];
        }

        return [
            $user,
            $email,
            $status,
            $total,
            $currency,
            $stripeSessionId,
            $stripePaymentIntentId,
            $createdAt,
            $updatedAt,
            $paidAt,
        ];
    }
}