<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryCrudController extends AbstractCrudController
{
    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined()
            ->setSearchFields(['id', 'name', 'slug']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', 'Nom'))
            ->add(TextFilter::new('slug', 'Slug'));
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id')->onlyOnIndex();

        $name = TextField::new('name', 'Nom');

        $slug = TextField::new('slug', 'Slug')
            ->setHelp('Laisse vide pour auto-générer depuis le nom.');

        $createdAt = DateTimeField::new('createdAt', 'Créé le')
            ->setFormTypeOption('disabled', true);

        // Si tu as bien Category::$products (OneToMany)
        $products = AssociationField::new('products', 'Produits')
            ->onlyOnDetail();

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $name, $slug, $createdAt];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $name, $slug, $createdAt, $products];
        }

        // NEW / EDIT
        return [$name, $slug, $createdAt];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Category) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        // createdAt auto
        if (null === $entityInstance->getCreatedAt()) {
            $entityInstance->setCreatedAt(new \DateTimeImmutable());
        }

        // slug auto si vide
        if (!$entityInstance->getSlug() && $entityInstance->getName()) {
            $slug = $this->slugger->slug($entityInstance->getName())->lower();
            $entityInstance->setSlug($slug);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Category) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        // slug auto si vide (ne remplace pas un slug déjà rempli)
        if (!$entityInstance->getSlug() && $entityInstance->getName()) {
            $slug = $this->slugger->slug($entityInstance->getName())->lower();
            $entityInstance->setSlug($slug);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}