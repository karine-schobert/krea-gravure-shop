<?php

namespace App\Controller\Admin;

use App\Entity\WorkshopRequest;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

// ============================================================================
// FILTRES EASYADMIN
// ============================================================================
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

// ============================================================================
// FIELDS EASYADMIN
// ============================================================================
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class WorkshopRequestCrudController extends AbstractCrudController
{
    /**
     * =========================================================================
     * ENTITÉ GÉRÉE PAR CE CRUD
     * =========================================================================
     */
    public static function getEntityFqcn(): string
    {
        return WorkshopRequest::class;
    }

    /**
     * =========================================================================
     * CONFIGURATION GLOBALE DU CRUD
     * =========================================================================
     *
     * On définit ici :
     * - les libellés
     * - les titres de page
     * - le tri par défaut
     * - les champs utilisés par la recherche
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande atelier')
            ->setEntityLabelInPlural('Demandes atelier')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des demandes atelier')
            ->setPageTitle(
                Crud::PAGE_DETAIL,
                fn(WorkshopRequest $request) => sprintf(
                    'Détail de la demande %s',
                    $request->getReference() ?? '#' . $request->getId()
                )
            )
            ->setPageTitle(
                Crud::PAGE_EDIT,
                fn(WorkshopRequest $request) => sprintf(
                    'Traiter la demande %s',
                    $request->getReference() ?? '#' . $request->getId()
                )
            )
            ->setPageTitle(Crud::PAGE_NEW, 'Créer une demande atelier')
            ->showEntityActionsInlined()
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ])
            ->setSearchFields([
                'reference',
                'fullName',
                'email',
                'phone',
                'companyName',
                'contactPerson',
                'subject',
                'message',
                'adminNotes',
                'customerNotes',
            ]);
    }

    /**
     * =========================================================================
     * CONFIGURATION DES ACTIONS
     * =========================================================================
     *
     * On personnalise ici les libellés et les classes CSS des boutons
     * EasyAdmin pour garder une interface admin plus lisible.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // -----------------------------------------------------------------
            // PAGE INDEX
            // -----------------------------------------------------------------
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn(Action $action) => $action
                ->setLabel('Voir')
                ->addCssClass('crud-action-show'))

            ->update(Crud::PAGE_INDEX, Action::EDIT, fn(Action $action) => $action
                ->setLabel('Traiter')
                ->addCssClass('crud-action-edit'))

            ->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action) => $action
                ->setLabel('Supprimer')
                ->addCssClass('crud-action-delete'))

            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $action) => $action
                ->setLabel('Créer une demande')
                ->addCssClass('crud-action-new'))

            // -----------------------------------------------------------------
            // PAGE DETAIL
            // -----------------------------------------------------------------
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn(Action $action) => $action
                ->setLabel('Traiter')
                ->addCssClass('crud-action-edit'))

            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            // -----------------------------------------------------------------
            // PAGE EDIT
            // -----------------------------------------------------------------
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            // -----------------------------------------------------------------
            // PAGE NEW
            // -----------------------------------------------------------------
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'));
    }

    /**
     * =========================================================================
     * CONFIGURATION DES FILTRES
     * =========================================================================
     *
     * Ces filtres servent à retrouver rapidement une demande selon :
     * - son client
     * - son type
     * - son statut
     * - sa priorité
     * - ses dates
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('reference', 'Référence'))
            ->add(TextFilter::new('fullName', 'Nom complet'))
            ->add(TextFilter::new('email', 'Email'))
            ->add(TextFilter::new('companyName', 'Structure'))

            ->add(ChoiceFilter::new('customerType', 'Type de demandeur')->setChoices([
                'Particulier' => WorkshopRequest::CUSTOMER_TYPE_INDIVIDUAL,
                'Entreprise' => WorkshopRequest::CUSTOMER_TYPE_COMPANY,
                'Association' => WorkshopRequest::CUSTOMER_TYPE_ASSOCIATION,
            ]))

            ->add(ChoiceFilter::new('requestType', 'Type de demande')->setChoices([
                'Information' => WorkshopRequest::REQUEST_TYPE_INFORMATION,
                'Demande personnalisée' => WorkshopRequest::REQUEST_TYPE_CUSTOM_REQUEST,
                'Demande professionnelle' => WorkshopRequest::REQUEST_TYPE_PROFESSIONAL_REQUEST,
                'Demande association' => WorkshopRequest::REQUEST_TYPE_ASSOCIATION_REQUEST,
                'Demande événementielle' => WorkshopRequest::REQUEST_TYPE_EVENT_REQUEST,
                'Précommande' => WorkshopRequest::REQUEST_TYPE_PREORDER,
                'Demande de devis' => WorkshopRequest::REQUEST_TYPE_QUOTE_REQUEST,
            ]))

            ->add(ChoiceFilter::new('needType', 'Besoin principal')->setChoices([
                'Cadeau' => WorkshopRequest::NEED_TYPE_GIFT,
                'Décoration' => WorkshopRequest::NEED_TYPE_DECORATION,
                'Événement' => WorkshopRequest::NEED_TYPE_EVENT,
                'Communication entreprise' => WorkshopRequest::NEED_TYPE_BUSINESS_COMMUNICATION,
                'Objet personnalisé' => WorkshopRequest::NEED_TYPE_PERSONALIZED_OBJECT,
                'Commande en quantité' => WorkshopRequest::NEED_TYPE_BULK_ORDER,
                'Autre' => WorkshopRequest::NEED_TYPE_OTHER,
            ]))

            ->add(ChoiceFilter::new('projectStage', 'Niveau d’avancement')->setChoices([
                'Découverte' => WorkshopRequest::PROJECT_STAGE_DISCOVERING,
                'Idée définie' => WorkshopRequest::PROJECT_STAGE_IDEA_DEFINED,
                'Prêt à commander' => WorkshopRequest::PROJECT_STAGE_READY_TO_ORDER,
                'Besoin rapide d’un devis' => WorkshopRequest::PROJECT_STAGE_NEED_QUOTE_FAST,
            ]))

            ->add(ChoiceFilter::new('preferredContactMethod', 'Contact préféré')->setChoices([
                'Email' => WorkshopRequest::CONTACT_METHOD_EMAIL,
                'Téléphone' => WorkshopRequest::CONTACT_METHOD_PHONE,
                'Peu importe' => WorkshopRequest::CONTACT_METHOD_EITHER,
            ]))

            ->add(ChoiceFilter::new('deliveryMethod', 'Livraison / retrait')->setChoices([
                'Retrait' => WorkshopRequest::DELIVERY_METHOD_PICKUP,
                'Livraison' => WorkshopRequest::DELIVERY_METHOD_DELIVERY,
                'À discuter' => WorkshopRequest::DELIVERY_METHOD_TO_DISCUSS,
            ]))

            ->add(ChoiceFilter::new('status', 'Statut')->setChoices([
                'Nouveau' => WorkshopRequest::STATUS_NEW,
                'En revue' => WorkshopRequest::STATUS_IN_REVIEW,
                'En attente client' => WorkshopRequest::STATUS_WAITING_CUSTOMER,
                'Devis envoyé' => WorkshopRequest::STATUS_QUOTED,
                'Accepté' => WorkshopRequest::STATUS_ACCEPTED,
                'Refusé' => WorkshopRequest::STATUS_REJECTED,
                'Archivé' => WorkshopRequest::STATUS_ARCHIVED,
            ]))

            ->add(ChoiceFilter::new('priority', 'Priorité')->setChoices([
                'Basse' => WorkshopRequest::PRIORITY_LOW,
                'Normale' => WorkshopRequest::PRIORITY_NORMAL,
                'Haute' => WorkshopRequest::PRIORITY_HIGH,
                'Urgente' => WorkshopRequest::PRIORITY_URGENT,
            ]))

            ->add(BooleanFilter::new('requiresQuote', 'Demande de devis'))
            ->add(BooleanFilter::new('requiresInvoice', 'Facture demandée'))
            ->add(BooleanFilter::new('isRead', 'Lu'))
            ->add(BooleanFilter::new('isFlagged', 'Signalé'))
            ->add(DateTimeFilter::new('createdAt', 'Créée le'))
            ->add(DateTimeFilter::new('desiredDate', 'Date souhaitée'))
            ->add(DateTimeFilter::new('eventDate', 'Date événement'));
    }

    /**
     * =========================================================================
     * CONFIGURATION DES CHAMPS
     * =========================================================================
     *
     * On prépare ici tous les champs réutilisables, puis on retourne
     * un affichage différent selon la page :
     * - index
     * - detail
     * - new/edit
     */
    public function configureFields(string $pageName): iterable
    {
        // ---------------------------------------------------------------------
        // CHAMPS COMMUNS
        // ---------------------------------------------------------------------
        $id = IdField::new('id', 'ID')->hideOnForm();

        $reference = TextField::new('reference', 'Référence')
            ->hideOnForm();

        $customerType = ChoiceField::new('customerType', 'Type de demandeur')
            ->setChoices([
                'Particulier' => WorkshopRequest::CUSTOMER_TYPE_INDIVIDUAL,
                'Entreprise' => WorkshopRequest::CUSTOMER_TYPE_COMPANY,
                'Association' => WorkshopRequest::CUSTOMER_TYPE_ASSOCIATION,
            ])
            ->hideOnForm();

        $requestType = ChoiceField::new('requestType', 'Type de demande')
            ->setChoices([
                'Information' => WorkshopRequest::REQUEST_TYPE_INFORMATION,
                'Demande personnalisée' => WorkshopRequest::REQUEST_TYPE_CUSTOM_REQUEST,
                'Demande professionnelle' => WorkshopRequest::REQUEST_TYPE_PROFESSIONAL_REQUEST,
                'Demande association' => WorkshopRequest::REQUEST_TYPE_ASSOCIATION_REQUEST,
                'Demande événementielle' => WorkshopRequest::REQUEST_TYPE_EVENT_REQUEST,
                'Précommande' => WorkshopRequest::REQUEST_TYPE_PREORDER,
                'Demande de devis' => WorkshopRequest::REQUEST_TYPE_QUOTE_REQUEST,
            ])
            ->hideOnForm();

        $needType = ChoiceField::new('needType', 'Besoin principal')
            ->setChoices([
                'Cadeau' => WorkshopRequest::NEED_TYPE_GIFT,
                'Décoration' => WorkshopRequest::NEED_TYPE_DECORATION,
                'Événement' => WorkshopRequest::NEED_TYPE_EVENT,
                'Communication entreprise' => WorkshopRequest::NEED_TYPE_BUSINESS_COMMUNICATION,
                'Objet personnalisé' => WorkshopRequest::NEED_TYPE_PERSONALIZED_OBJECT,
                'Commande en quantité' => WorkshopRequest::NEED_TYPE_BULK_ORDER,
                'Autre' => WorkshopRequest::NEED_TYPE_OTHER,
            ])
            ->hideOnForm();

        $projectStage = ChoiceField::new('projectStage', 'Niveau d’avancement')
            ->setChoices([
                'Découverte' => WorkshopRequest::PROJECT_STAGE_DISCOVERING,
                'Idée définie' => WorkshopRequest::PROJECT_STAGE_IDEA_DEFINED,
                'Prêt à commander' => WorkshopRequest::PROJECT_STAGE_READY_TO_ORDER,
                'Besoin rapide d’un devis' => WorkshopRequest::PROJECT_STAGE_NEED_QUOTE_FAST,
            ])
            ->hideOnForm();

        $preferredContactMethod = ChoiceField::new('preferredContactMethod', 'Contact préféré')
            ->setChoices([
                'Email' => WorkshopRequest::CONTACT_METHOD_EMAIL,
                'Téléphone' => WorkshopRequest::CONTACT_METHOD_PHONE,
                'Peu importe' => WorkshopRequest::CONTACT_METHOD_EITHER,
            ])
            ->hideOnForm();

        $deliveryMethod = ChoiceField::new('deliveryMethod', 'Livraison / retrait')
            ->setChoices([
                'Retrait' => WorkshopRequest::DELIVERY_METHOD_PICKUP,
                'Livraison' => WorkshopRequest::DELIVERY_METHOD_DELIVERY,
                'À discuter' => WorkshopRequest::DELIVERY_METHOD_TO_DISCUSS,
            ])
            ->hideOnForm();

        $status = ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Nouveau' => WorkshopRequest::STATUS_NEW,
                'En revue' => WorkshopRequest::STATUS_IN_REVIEW,
                'En attente client' => WorkshopRequest::STATUS_WAITING_CUSTOMER,
                'Devis envoyé' => WorkshopRequest::STATUS_QUOTED,
                'Accepté' => WorkshopRequest::STATUS_ACCEPTED,
                'Refusé' => WorkshopRequest::STATUS_REJECTED,
                'Archivé' => WorkshopRequest::STATUS_ARCHIVED,
            ]);

        $priority = ChoiceField::new('priority', 'Priorité')
            ->setChoices([
                'Basse' => WorkshopRequest::PRIORITY_LOW,
                'Normale' => WorkshopRequest::PRIORITY_NORMAL,
                'Haute' => WorkshopRequest::PRIORITY_HIGH,
                'Urgente' => WorkshopRequest::PRIORITY_URGENT,
            ]);

        $fullName = TextField::new('fullName', 'Nom complet')
            ->hideOnForm();

        $email = EmailField::new('email', 'Email')
            ->hideOnForm();

        $phone = TelephoneField::new('phone', 'Téléphone')
            ->hideOnForm();

        $companyName = TextField::new('companyName', 'Structure')
            ->hideOnForm();

        $contactPerson = TextField::new('contactPerson', 'Contact structure')
            ->hideOnForm();

        $subject = TextField::new('subject', 'Sujet')
            ->hideOnForm();

        $message = TextareaField::new('message', 'Message')
            ->hideOnIndex()
            ->hideOnForm();

        $requiresQuote = BooleanField::new('requiresQuote', 'Demande de devis')
            ->renderAsSwitch(false)
            ->hideOnForm();

        $requiresInvoice = BooleanField::new('requiresInvoice', 'Facture demandée')
            ->renderAsSwitch(false)
            ->hideOnForm();

        $isRead = BooleanField::new('isRead', 'Lu');

        $isFlagged = BooleanField::new('isFlagged', 'Signalé');

        $adminNotes = TextareaField::new('adminNotes', 'Notes internes');

        $customerNotes = TextareaField::new('customerNotes', 'Notes client')
            ->onlyOnDetail();

        $source = ChoiceField::new('source', 'Source')
            ->setChoices([
                'Formulaire contact site' => WorkshopRequest::SOURCE_WEBSITE_CONTACT_FORM,
                'Formulaire devis site' => WorkshopRequest::SOURCE_WEBSITE_QUOTE_FORM,
                'Création manuelle admin' => WorkshopRequest::SOURCE_MANUAL_ADMIN,
                'Autre' => WorkshopRequest::SOURCE_OTHER,
            ])
            ->hideOnForm();

        $submittedAt = DateTimeField::new('submittedAt', 'Soumise le')
            ->hideOnForm();

        $processedAt = DateTimeField::new('processedAt', 'Traitée le');

        $answeredAt = DateTimeField::new('answeredAt', 'Répondue le');

        $archivedAt = DateTimeField::new('archivedAt', 'Archivée le')
            ->hideOnForm();

        $desiredDate = DateTimeField::new('desiredDate', 'Date souhaitée')
            ->hideOnForm()
            ->hideOnIndex();

        $eventDate = DateTimeField::new('eventDate', 'Date événement')
            ->hideOnForm()
            ->hideOnIndex();

        $createdAt = DateTimeField::new('createdAt', 'Créée le')
            ->hideOnForm();

        $updatedAt = DateTimeField::new('updatedAt', 'Modifiée le')
            ->hideOnForm();

        // ---------------------------------------------------------------------
        // CHAMPS CALCULÉS / VIRTUELS
        // ---------------------------------------------------------------------
        /*
        |------------------------------------------------------------------
        | Compteurs admin
        |------------------------------------------------------------------
        |
        | Ces champs reposent idéalement sur des getters dédiés dans
        | l'entité WorkshopRequest :
        | - getItemsCount()
        | - getAttachmentsCount()
        |
        | Cela évite les "Inaccessible" sur les collections Doctrine.
        |
        */
        $itemsCount = IntegerField::new('itemsCount', 'Nb lignes')
            ->onlyOnIndex();

        $attachmentsCount = IntegerField::new('attachmentsCount', 'Nb PJ')
            ->onlyOnIndex();

        /*
        |------------------------------------------------------------------
        | Aperçu des lignes de besoin
        |------------------------------------------------------------------
        |
        | On s'appuie ici sur le getter virtuel getItemsPreview() défini
        | dans l'entité WorkshopRequest.
        |
        */
        $itemsPreview = TextareaField::new('itemsPreview', 'Lignes de besoin')
            ->onlyOnDetail()
            ->hideOnForm();

        /*
        |------------------------------------------------------------------
        | Aperçu des pièces jointes
        |------------------------------------------------------------------
        |
        | Ce champ utilise un template Twig custom pour afficher proprement
        | les pièces jointes depuis l'admin.
        |
        | On garde ici la compatibilité d'affichage avec les formats utiles
        | au module atelier :
        | - PDF
        | - JPG / JPEG
        | - PNG
        | - WEBP
        |
        | Le template peut s'appuyer sur :
        | - originalName
        | - mimeType
        | - size
        | - attachmentType
        | - getPublicUrl()
        |
        */
        $attachmentsPreview = TextareaField::new('attachmentsPreview', 'Pièces jointes')
            ->onlyOnDetail()
            ->setTemplatePath('admin/field/workshop_request_attachments.html.twig');

        // ---------------------------------------------------------------------
        // PAGE INDEX
        // ---------------------------------------------------------------------
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $reference,
                $createdAt,
                $status,
                $priority,
                $customerType,
                $requestType,
                $fullName,
                $companyName,
                $email,
                $requiresQuote,
                $isRead,
                $isFlagged,
                $itemsCount,
                $attachmentsCount,
            ];
        }

        // ---------------------------------------------------------------------
        // PAGE DETAIL
        // ---------------------------------------------------------------------
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                FormField::addFieldset('Synthèse'),
                $id,
                $reference,
                $status,
                $priority,
                $source,
                $createdAt,
                $updatedAt,
                $submittedAt,
                $processedAt,
                $answeredAt,
                $archivedAt,

                FormField::addFieldset('Client / structure'),
                $customerType,
                $fullName,
                $email,
                $phone,
                $companyName,
                $contactPerson,
                $preferredContactMethod,
                $requiresInvoice,

                FormField::addFieldset('Demande'),
                $requestType,
                $needType,
                $projectStage,
                $deliveryMethod,
                $desiredDate,
                $eventDate,
                $subject,
                $message,
                $requiresQuote,

                FormField::addFieldset('Contenu détaillé'),
                $itemsPreview,
                $attachmentsPreview,

                FormField::addFieldset('Suivi interne'),
                $adminNotes,
                $customerNotes,
                $isRead,
                $isFlagged,
            ];
        }

        // ---------------------------------------------------------------------
        // PAGE NEW / EDIT
        // ---------------------------------------------------------------------
        /*
        |------------------------------------------------------------------
        | Formulaire admin
        |------------------------------------------------------------------
        |
        | Ici on reste volontairement sur une logique de traitement admin :
        | on ne réédite pas les données client d'origine dans ce CRUD.
        | On modifie surtout le suivi interne.
        |
        | Mais on utilise quand même toutes les variables utiles au moins
        | sur les autres pages, notamment detail.
        |
        */
        return [
            FormField::addFieldset('Traitement admin'),
            $status,
            $priority,
            $adminNotes,
            $processedAt,
            $answeredAt,
            $isRead,
            $isFlagged,
        ];
    }
}