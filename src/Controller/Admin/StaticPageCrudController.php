<?php

namespace App\Controller\Admin;

use App\Entity\StaticPage;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class StaticPageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return StaticPage::class;
    }

    // =========================
    // CONFIG CRUD
    // =========================
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Page statique')
            ->setEntityLabelInPlural('Pages statiques')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des pages statiques')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une page statique')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier une page statique')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de la page statique')
            ->setDefaultSort([
                'title' => 'ASC',
                'id' => 'DESC',
            ])
            ->showEntityActionsInlined()
            ->setSearchFields([
                'id',
                'title',
                'slug',
                'metaTitle',
            ]);
    }

    // =========================
    // ACTIONS
    // =========================
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)

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
                ->setLabel('Ajouter une page')
                ->addCssClass('crud-action-new'))

            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            ->update(Crud::PAGE_NEW, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'));
    }

    // =========================
    // FILTRES
    // =========================
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', 'Titre'))
            ->add(TextFilter::new('slug', 'Slug'))
            ->add(TextFilter::new('metaTitle', 'Meta title'))
            ->add(BooleanFilter::new('isActive', 'Active'));
    }

    // =========================
    // FIELDS
    // =========================
    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id', 'ID')
            ->hideOnForm();

        $title = TextField::new('title', 'Titre')
            ->setHelp('Ex : À propos, Contact, Mentions légales.');

        $slug = SlugField::new('slug', 'Slug')
            ->setTargetFieldName('title')
            ->setHelp('Ex : a-propos, contact, mentions-legales.');

        $content = TextareaField::new('content', 'Contenu')
            ->setNumOfRows(20)
            ->setHelp('Contenu principal affiché sur la page côté front.');

        $isActive = BooleanField::new('isActive', 'Active')
            ->setHelp('Si désactivée, la page ne sera plus accessible côté front.');

        $metaTitle = TextField::new('metaTitle', 'Meta title')
            ->setRequired(false)
            ->setHelp('Titre SEO optionnel pour l’onglet navigateur et les moteurs de recherche.');

        $metaDescription = TextareaField::new('metaDescription', 'Meta description')
            ->setRequired(false)
            ->setNumOfRows(4)
            ->setHelp('Description SEO optionnelle pour améliorer l’aperçu dans les résultats Google.');

        $createdAt = DateTimeField::new('createdAt', 'Créée le')
            ->setFormTypeOption('disabled', true);

        $updatedAt = DateTimeField::new('updatedAt', 'Modifiée le')
            ->setFormTypeOption('disabled', true);

        // =========================
        // INDEX
        // =========================
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                $id,
                $title,
                $slug,
                $isActive,
                $metaTitle,
                $updatedAt,
            ];
        }

        // =========================
        // DETAIL
        // =========================
        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                FormField::addFieldset('Informations principales'),

                $id,
                $title,
                $slug,
                $isActive,

                FormField::addFieldset('Contenu'),

                $content,

                FormField::addFieldset('Référencement SEO'),

                $metaTitle,
                $metaDescription,

                FormField::addFieldset('Informations techniques'),

                $createdAt,
                $updatedAt,
            ];
        }

        // =========================
        // NEW / EDIT
        // =========================
        return [
            FormField::addFieldset('Informations principales'),

            $title,
            $slug,
            $isActive,

            FormField::addFieldset('Contenu'),

            $content,

            FormField::addFieldset('Référencement SEO'),

            $metaTitle,
            $metaDescription,

            FormField::addFieldset('Informations techniques'),

            $createdAt,
            $updatedAt,
        ];
    }

    // =========================
    // CREATE
    // =========================
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof StaticPage) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        // Active par défaut si rien n’a été défini
        if (null === $entityInstance->isActive()) {
            $entityInstance->setIsActive(true);
        }

        // Si aucun meta title n’est saisi, on reprend le titre
        if (!$entityInstance->getMetaTitle() && $entityInstance->getTitle()) {
            $entityInstance->setMetaTitle($entityInstance->getTitle());
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    // =========================
    // UPDATE
    // =========================
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof StaticPage) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        // Si le meta title est vidé, on peut reprendre le titre principal
        if (!$entityInstance->getMetaTitle() && $entityInstance->getTitle()) {
            $entityInstance->setMetaTitle($entityInstance->getTitle());
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}