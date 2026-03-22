<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * CRUD EasyAdmin des administrateurs
 *
 * Important :
 * - même entité User que les clients
 * - ici on n'affiche QUE les users qui ont ROLE_ADMIN
 * - le mot de passe est saisi en clair via plainPassword puis hashé
 * - on bloque la suppression du dernier admin logique
 */
class AdminCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserRepository $userRepository,
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    /**
     * Configuration générale du CRUD
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Administrateur')
            ->setEntityLabelInPlural('Administrateurs')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des administrateurs')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter un administrateur')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier un administrateur')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de l’administrateur')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined();
    }

    /**
     * Actions EasyAdmin
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)

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
                ->setLabel('Ajouter un administrateur')
                ->addCssClass('crud-action-new'))

            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn(Action $action) => $action
                ->setLabel('Modifier')
                ->addCssClass('crud-action-edit'))

            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'))

            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_NEW, Action::INDEX, fn(Action $action) => $action
                ->setLabel('Retour à la liste')
                ->addCssClass('crud-action-back'));
    }

    /**
     * Champs affichés
     */
    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Informations administrateur');

        yield IdField::new('id', 'ID')->hideOnForm();

        yield EmailField::new('email', 'Email');

        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices([
                'Administrateur' => 'ROLE_ADMIN',
                'Utilisateur' => 'ROLE_USER',
            ])
            ->allowMultipleChoices()
            ->renderExpanded(false)
            ->renderAsBadges([
                'ROLE_ADMIN' => 'danger',
                'ROLE_USER' => 'success',
            ])
            ->setHelp('ROLE_USER est conservé automatiquement.');

        yield FormField::addFieldset('Sécurité')->onlyOnForms();

        yield TextField::new('plainPassword', 'Nouveau mot de passe')
            ->setFormType(PasswordType::class)
            ->onlyOnForms()
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->setHelp(
                $pageName === Crud::PAGE_NEW
                    ? 'Champ obligatoire à la création.'
                    : 'Laisse vide pour conserver le mot de passe actuel.'
            );
    }

    /**
     * Création d'un admin
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $this->hashPasswordIfProvided($entityInstance);

            // Sécurité : si on crée un admin depuis ce CRUD,
            // on force ROLE_ADMIN dans les rôles
            $roles = $entityInstance->getRoles();
            if (!in_array('ROLE_ADMIN', $roles, true)) {
                $roles[] = 'ROLE_ADMIN';
                $entityInstance->setRoles(array_unique($roles));
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Mise à jour d'un admin
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $this->preventRemovingLastAdmin($entityInstance);
            $this->hashPasswordIfProvided($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Filtre la liste pour n'afficher que les administrateurs
     */
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        return $qb
            ->andWhere('entity.roles LIKE :adminRole')
            ->setParameter('adminRole', '%ROLE_ADMIN%');
    }

    /**
     * Hash le mot de passe uniquement si un plainPassword est saisi
     */
    private function hashPasswordIfProvided(User $user): void
    {
        $plainPassword = $user->getPlainPassword();

        if (is_string($plainPassword) && trim($plainPassword) !== '') {
            $user->setPassword(
                $this->hasher->hashPassword($user, $plainPassword)
            );

            $user->eraseCredentials();
        }
    }

    /**
     * Empêche de retirer ROLE_ADMIN au dernier administrateur
     */
    private function preventRemovingLastAdmin(User $editedUser): void
    {
        if (!$editedUser->getId()) {
            return;
        }

        $originalUser = $this->userRepository->find($editedUser->getId());

        if (!$originalUser instanceof User) {
            return;
        }

        $wasAdmin = in_array('ROLE_ADMIN', $originalUser->getRoles(), true);
        $isStillAdmin = in_array('ROLE_ADMIN', $editedUser->getRoles(), true);

        if ($wasAdmin && !$isStillAdmin) {
            $adminsCount = $this->userRepository->countUsersWithRoleAdmin();

            if ($adminsCount <= 1) {
                throw new BadRequestHttpException(
                    'Impossible : tu ne peux pas retirer ROLE_ADMIN au dernier administrateur.'
                );
            }
        }
    }
}