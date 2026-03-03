<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductCrudController extends AbstractCrudController
{
    public function __construct(private readonly SluggerInterface $slugger)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined()
            ->setSearchFields(['id', 'title', 'slug', 'description']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', 'Titre'))
            ->add(TextFilter::new('slug', 'Slug'))
            ->add(NumericFilter::new('priceCents', 'Prix (centimes)'))
            ->add(BooleanFilter::new('isActive', 'Actif'));
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id')->onlyOnIndex();

        $title = TextField::new('title', 'Titre');

        $slug = TextField::new('slug', 'Slug')
            ->setHelp('Laisse vide pour auto-générer depuis le titre.');

        $description = TextareaField::new('description', 'Description')
            ->hideOnIndex();

        $price = MoneyField::new('priceCents', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(true);

        $isActive = BooleanField::new('isActive', 'Actif');

        $createdAt = DateTimeField::new('createdAt', 'Créé le')
            ->setFormTypeOption('disabled', true);

        if (Crud::PAGE_INDEX === $pageName) {
            return [$id, $title, $price, $isActive, $createdAt];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [$id, $title, $slug, $description, $price, $isActive, $createdAt];
        }

        // NEW / EDIT
        return [$title, $slug, $description, $price, $isActive, $createdAt];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        // createdAt auto
        if (null === $entityInstance->getCreatedAt()) {
            $entityInstance->setCreatedAt(new \DateTimeImmutable());
        }

        // isActive par défaut (choisis ce qui te convient)
        if (null === $entityInstance->isActive()) {
            $entityInstance->setIsActive(true);
        }

        // slug auto si vide
        if (!$entityInstance->getSlug() && $entityInstance->getTitle()) {
            $slug = $this->slugger->slug($entityInstance->getTitle())->lower();
            $entityInstance->setSlug($slug);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Product) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        // slug auto si vide (ne remplace pas un slug déjà rempli)
        if (!$entityInstance->getSlug() && $entityInstance->getTitle()) {
            $slug = $this->slugger->slug($entityInstance->getTitle())->lower();
            $entityInstance->setSlug($slug);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }
}