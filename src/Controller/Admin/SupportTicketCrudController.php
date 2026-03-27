<?php

namespace App\Controller\Admin;

use App\Entity\SupportTicket;
use App\Support\SupportReplyTemplates;
use Doctrine\ORM\EntityManagerInterface;
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
    /**
     * Indique à EasyAdmin quelle entité est gérée par ce CRUD.
     */
    public static function getEntityFqcn(): string
    {
        return SupportTicket::class;
    }

    /**
     * Configuration générale du CRUD :
     * - titres
     * - ordre par défaut
     * - champs de recherche
     * - pagination
     * - affichage inline des actions
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Ticket support')
            ->setEntityLabelInPlural('Tickets support')
            ->setPageTitle(Crud::PAGE_INDEX, 'Tickets support')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du ticket')
            ->setPageTitle(Crud::PAGE_EDIT, 'Répondre au ticket')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un ticket')

            // On affiche d'abord les tickets les plus récents
            ->setDefaultSort(['createdAt' => 'DESC'])

            // Champs utilisés dans la barre de recherche EasyAdmin
            ->setSearchFields([
                'id',
                'category',
                'subject',
                'message',
                'adminReply',
                'status',
                'user.email',
                'user.firstName',
                'user.lastName',
                'order.id',
            ])

            // Nombre d'éléments par page
            ->setPaginatorPageSize(20)

            // Affiche les actions directement sur la ligne
            ->showEntityActionsInlined();
    }

    /**
     * Configuration des boutons d'action EasyAdmin.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE])

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

            // Les tickets doivent être créés côté client
            ->disable(Action::NEW);
    }

    /**
     * Déclare les champs affichés selon la page.
     */
    public function configureFields(string $pageName): iterable
    {
        // ID technique
        $id = IdField::new('id')
            ->hideOnForm();

        // Client lié au ticket
        $user = AssociationField::new('user', 'Client')
            ->autocomplete()
            ->setFormTypeOption('disabled', true);

        // Commande liée au ticket
        $order = AssociationField::new('order', 'Commande')
            ->autocomplete()
            ->setFormTypeOption('disabled', true);

        // Catégorie du ticket
        $category = ChoiceField::new('category', 'Catégorie')
            ->setChoices(SupportTicket::getAvailableCategories())
            ->renderAsBadges([
                SupportTicket::CATEGORY_NOT_RECEIVED => 'warning',
                SupportTicket::CATEGORY_LATE_DELIVERY => 'warning',
                SupportTicket::CATEGORY_DAMAGED_PRODUCT => 'danger',
                SupportTicket::CATEGORY_WRONG_PRODUCT => 'danger',
                SupportTicket::CATEGORY_ORDER_ERROR => 'danger',
                SupportTicket::CATEGORY_MISSING_ITEM => 'warning',
                SupportTicket::CATEGORY_CUSTOMIZATION_PROBLEM => 'info',
                SupportTicket::CATEGORY_ORDER_MODIFICATION => 'info',
                SupportTicket::CATEGORY_ORDER_CANCELLATION => 'secondary',
                SupportTicket::CATEGORY_REFUND_REQUEST => 'secondary',
                SupportTicket::CATEGORY_PAYMENT_PROBLEM => 'danger',
                SupportTicket::CATEGORY_OTHER => 'secondary',
            ])
            ->setFormTypeOption('disabled', true);

        // Sujet du ticket
        $subject = TextField::new('subject', 'Sujet')
            ->setFormTypeOption('disabled', true);

        // Message client
        $message = TextareaField::new('message', 'Message client')
            ->hideOnIndex()
            ->setNumOfRows(8)
            ->setFormTypeOption('disabled', true);

        // Statut géré par l'admin
        $status = ChoiceField::new('status', 'Statut')
            ->setChoices(SupportTicket::getAvailableStatuses())
            ->renderExpanded(false)
            ->renderAsBadges([
                SupportTicket::STATUS_OPEN => 'warning',
                SupportTicket::STATUS_IN_PROGRESS => 'info',
                SupportTicket::STATUS_ANSWERED => 'primary',
                SupportTicket::STATUS_RESOLVED => 'success',
                SupportTicket::STATUS_CLOSED => 'secondary',
            ]);

        /**
         * Champ temporaire de sélection d'un modèle de réponse.
         *
         * Ici on ne charge plus tous les modèles :
         * on filtre selon la catégorie du ticket actuellement ouvert.
         */
        $replyTemplateCode = ChoiceField::new('replyTemplateCode', 'Réponse type')
            ->setChoices($this->buildReplyTemplateChoicesForCurrentTicket())
            ->setHelp('Seuls les modèles correspondant à la catégorie de ce ticket sont proposés.')
            ->hideOnIndex()
            ->onlyOnForms();

        // Réponse admin
        $adminReply = TextareaField::new('adminReply', 'Réponse admin')
            ->hideOnIndex()
            ->setNumOfRows(10)
            ->setHelp('Vous pouvez rédiger manuellement la réponse ou utiliser un modèle.');

        // Dates
        $createdAt = DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();

        $updatedAt = DateTimeField::new('updatedAt', 'Mis à jour le')
            ->hideOnForm();

        $answeredAt = DateTimeField::new('answeredAt', 'Répondu le')
            ->hideOnForm();

        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $user,
                $order,
                $category,
                $subject,
                $status,
                $createdAt,
                $answeredAt,
            ];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $id,
                $user,
                $order,
                $category,
                $subject,
                $message,
                $status,
                $adminReply,
                $createdAt,
                $updatedAt,
                $answeredAt,
            ];
        }

        return [
            $user,
            $order,
            $category,
            $subject,
            $message,
            $replyTemplateCode,
            $status,
            $adminReply,
        ];
    }

    /**
     * Filtres disponibles dans la liste admin.
     */
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('user')
            ->add('order')
            ->add('category')
            ->add('status')
            ->add('createdAt')
            ->add('answeredAt');
    }

    /**
     * Construit la liste des templates à afficher
     * uniquement pour la catégorie du ticket actuellement édité.
     *
     * On lit l'entité courante via le contexte EasyAdmin.
     */
    private function buildReplyTemplateChoicesForCurrentTicket(): array
    {
        $choices = [
            '— Aucun modèle —' => '',
        ];

        // Récupère le contexte EasyAdmin courant
        $context = $this->getContext();

        // Sécurité : si on n'a pas de contexte, on retourne juste l'option vide
        if (null === $context) {
            return $choices;
        }

        // Récupère l'entité en cours dans EasyAdmin
        $entity = $context->getEntity()?->getInstance();

        // Sécurité : si ce n'est pas un SupportTicket, on arrête
        if (!$entity instanceof SupportTicket) {
            return $choices;
        }

        // Récupère la catégorie réelle du ticket
        $category = $entity->getCategory();

        if (!$category) {
            return $choices;
        }

        // Charge uniquement les templates de cette catégorie
        $templates = SupportReplyTemplates::forCategory($category);

        foreach ($templates as $templateCode => $templateData) {
            $label = $templateData['title'] ?? $templateCode;
            $choices[$label] = $category . '::' . $templateCode;
        }

        return $choices;
    }

    /**
     * Logique exécutée lorsqu'un ticket est modifié en admin.
     *
     * Objectif :
     * - injecter le contenu du template choisi si présent
     * - mettre à jour updatedAt
     * - renseigner answeredAt à la première vraie réponse admin
     * - passer automatiquement le ticket à ANSWERED
     *   si le ticket était OPEN et qu'une réponse admin existe
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof SupportTicket) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        /**
         * Si l'admin a choisi une réponse type,
         * on découpe la valeur "category::templateCode"
         * puis on remplit adminReply avec le contenu du modèle.
         */
        $selectedTemplate = $entityInstance->getReplyTemplateCode();

        if ($selectedTemplate && str_contains($selectedTemplate, '::')) {
            [$category, $templateCode] = explode('::', $selectedTemplate, 2);

            $templateContent = SupportReplyTemplates::getContent($category, $templateCode);

            if ($templateContent) {
                $entityInstance->setAdminReply($templateContent);
            }
        }

        // Mise à jour de la date de modification
        $entityInstance->setUpdatedAt(new \DateTimeImmutable());

        // Si une réponse admin existe
        if ($entityInstance->getAdminReply()) {
            // Première date de réponse uniquement
            if (null === $entityInstance->getAnsweredAt()) {
                $entityInstance->setAnsweredAt(new \DateTimeImmutable());
            }

            // Passage automatique à ANSWERED si encore OPEN
            if ($entityInstance->getStatus() === SupportTicket::STATUS_OPEN) {
                $entityInstance->setStatus(SupportTicket::STATUS_ANSWERED);
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}