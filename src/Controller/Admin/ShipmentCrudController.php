<?php

namespace App\Controller\Admin;

use App\Entity\Shipment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class ShipmentCrudController extends AbstractCrudController
{
    /**
     * Entité gérée par ce CRUD.
     */
    public static function getEntityFqcn(): string
    {
        return Shipment::class;
    }

    /**
     * Configuration générale du CRUD.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Expédition')
            ->setEntityLabelInPlural('Expéditions')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des expéditions')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une expédition')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier une expédition')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de l’expédition')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined()
            ->setSearchFields([
                'id',
                'logisticStatus',
                'carrier',
                'trackingNumber',
                'trackingUrl',
                'order.id',
                'order.email',
            ]);
    }

    /**
     * Configuration des actions EasyAdmin.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Ajout du bouton détail dans la liste
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            // Personnalisation des boutons sur la liste
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
                ->setLabel('Ajouter une expédition')
                ->setIcon('fa fa-plus')
                ->addCssClass('crud-action-new'))

            // Personnalisation des boutons sur la page détail
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn(Action $action) => $action
                ->setLabel('Modifier')
                ->setIcon('fa fa-pen')
                ->addCssClass('crud-action-edit'))

            ->update(Crud::PAGE_DETAIL, Action::DELETE, fn(Action $action) => $action
                ->setLabel('Supprimer')
                ->setIcon('fa fa-trash')
                ->addCssClass('crud-action-delete'))

            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->setIcon('fa fa-arrow-left')
                ->addCssClass('crud-action-back'))

            // Bouton retour sur édition
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->setIcon('fa fa-arrow-left')
                ->addCssClass('crud-action-back'))

            // Bouton retour sur création
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->setIcon('fa fa-arrow-left')
                ->addCssClass('crud-action-back'))

            // Libellés des boutons de sauvegarde
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, fn(Action $action) => $action
                ->setLabel('Enregistrer les modifications'))

            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, fn(Action $action) => $action
                ->setLabel('Enregistrer et continuer'))

            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, fn(Action $action) => $action
                ->setLabel('Créer l’expédition'));
    }

    /**
     * Filtres disponibles dans le listing admin.
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('logisticStatus', 'Statut logistique'))
            ->add(TextFilter::new('carrier', 'Transporteur'))
            ->add(TextFilter::new('trackingNumber', 'Numéro de suivi'))
            ->add(EntityFilter::new('order', 'Commande liée'));
    }

    /**
     * Champs affichés selon la page EasyAdmin.
     *
     * Objectifs :
     * - garder une interface simple pour alimenter la logistique
     * - préparer le futur suivi client côté front
     * - ne pas surcharger la gestion admin inutilement
     */
    public function configureFields(string $pageName): iterable
    {
        // =========================================================
        // CHAMPS TECHNIQUES / DE BASE
        // =========================================================

        $id = IdField::new('id', 'ID')
            ->hideOnForm();

        $order = AssociationField::new('order', 'Commande')
            ->autocomplete()
            ->setHelp('Commande liée à cette expédition.');

        $logisticStatus = ChoiceField::new('logisticStatus', 'Statut logistique')
            ->setChoices([
                'Préparation' => Shipment::STATUS_PREPARING,
                'Prête à expédier' => Shipment::STATUS_READY_TO_SHIP,
                'Expédiée' => Shipment::STATUS_SHIPPED,
                'En transit' => Shipment::STATUS_IN_TRANSIT,
                'En cours de livraison' => Shipment::STATUS_OUT_FOR_DELIVERY,
                'Livrée' => Shipment::STATUS_DELIVERED,
                'Incident de livraison' => Shipment::STATUS_DELIVERY_ISSUE,
                'Retournée' => Shipment::STATUS_RETURNED,
                'Annulée' => Shipment::STATUS_CANCELLED,
            ])
            ->setHelp('Statut logistique interne utilisé pour le suivi.');

        $carrier = ChoiceField::new('carrier', 'Transporteur')
            ->setRequired(false)
            ->setChoices([
                'Colissimo' => Shipment::CARRIER_COLISSIMO,
                'Chronopost' => Shipment::CARRIER_CHRONOPOST,
                'Lettre suivie' => Shipment::CARRIER_LETTER_FOLLOWED,
                'Mondial Relay' => Shipment::CARRIER_MONDIAL_RELAY,
                'Autre' => Shipment::CARRIER_OTHER,
            ])
            ->setHelp('Transporteur utilisé pour cette expédition.');

        $trackingNumber = TextField::new('trackingNumber', 'Numéro de suivi')
            ->setRequired(false)
            ->setHelp('Numéro communiqué au client pour suivre le colis.');

        $trackingUrl = TextField::new('trackingUrl', 'URL de suivi')
            ->setRequired(false)
            ->setHelp('Lien direct vers le suivi transporteur si disponible.');

        $shippedAt = DateTimeField::new('shippedAt', 'Date d’expédition')
            ->setRequired(false)
            ->setFormat('dd/MM/yyyy HH:mm');

        $estimatedDeliveryAt = DateTimeField::new('estimatedDeliveryAt', 'Date estimée de livraison')
            ->setRequired(false)
            ->setFormat('dd/MM/yyyy HH:mm');

        $deliveredAt = DateTimeField::new('deliveredAt', 'Date de livraison')
            ->setRequired(false)
            ->setFormat('dd/MM/yyyy HH:mm');

        $lastTrackingSyncAt = DateTimeField::new('lastTrackingSyncAt', 'Dernière synchro')
            ->setRequired(false)
            ->setFormat('dd/MM/yyyy HH:mm');

        $trackingRawPayload = TextareaField::new('trackingRawPayload', 'Payload brut transporteur')
            ->setRequired(false)
            ->setHelp('Donnée brute éventuelle renvoyée par une API de suivi.')
            ->hideOnIndex()
            ->hideOnForm();

        $createdAt = DateTimeField::new('createdAt', 'Créée le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();

        $updatedAt = DateTimeField::new('updatedAt', 'Modifiée le')
            ->setFormat('dd/MM/yyyy HH:mm')
            ->hideOnForm();

        // =========================================================
        // PAGE INDEX
        // =========================================================

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $order,
                $logisticStatus,
                $carrier,
                $trackingNumber,
                $shippedAt,
                $estimatedDeliveryAt,
                $deliveredAt,
                $updatedAt,
            ];
        }

        // =========================================================
        // PAGE DETAIL
        // =========================================================

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                FormField::addFieldset('Commande liée'),
                $id,
                $order,

                FormField::addFieldset('Suivi logistique'),
                $logisticStatus,
                $carrier,
                $trackingNumber,
                $trackingUrl,

                FormField::addFieldset('Dates logistiques'),
                $shippedAt,
                $estimatedDeliveryAt,
                $deliveredAt,
                $lastTrackingSyncAt,

                FormField::addFieldset('Informations techniques'),
                $trackingRawPayload,
                $createdAt,
                $updatedAt,
            ];
        }

        // =========================================================
        // PAGE NEW
        // =========================================================

        if (Crud::PAGE_NEW === $pageName) {
            return [
                FormField::addFieldset('Commande liée'),
                $order,

                FormField::addFieldset('Suivi logistique'),
                $logisticStatus,
                $carrier,
                $trackingNumber,
                $trackingUrl,

                FormField::addFieldset('Dates logistiques'),
                $shippedAt,
                $estimatedDeliveryAt,
                $deliveredAt,
                $lastTrackingSyncAt,
            ];
        }

        // =========================================================
        // PAGE EDIT
        // =========================================================

        return [
            FormField::addFieldset('Commande liée'),
            $order,

            FormField::addFieldset('Suivi logistique'),
            $logisticStatus,
            $carrier,
            $trackingNumber,
            $trackingUrl,

            FormField::addFieldset('Dates logistiques'),
            $shippedAt,
            $estimatedDeliveryAt,
            $deliveredAt,
            $lastTrackingSyncAt,

            FormField::addFieldset('Informations techniques'),
            $createdAt,
            $updatedAt,
        ];
    }
}