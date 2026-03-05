<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * CRUD EasyAdmin pour gérer les utilisateurs :
 * - Email + rôles (ROLE_ADMIN / ROLE_USER)
 * - Mot de passe : champ plainPassword (non persisté) -> hash au save
 * - Sécurité : empêche de retirer ROLE_ADMIN au dernier admin
 * - Actions : affichées "inline" + action DETAIL sur la liste (comme Product)
 */
class UserCrudController extends AbstractCrudController
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
     * Configuration globale du CRUD :
     * - labels FR
     * - tri par défaut
     * - actions inline (comme Product)
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs') // ✅ correction : pluriel
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined();
    }

    /**
     * Actions sur la page index :
     * - ajoute "Détail" en plus d'Edit/Delete (comme Product)
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    /**
     * Champs affichés :
     * - Index : id, email, rôles (badges)
     * - Form : email, rôles (choix), plainPassword (facultatif en edit)
     */
    public function configureFields(string $pageName): iterable
    {
        // ID : visible uniquement en listing (et détails), caché en formulaire
        yield IdField::new('id')->hideOnForm();

        // Email
        yield EmailField::new('email', 'Email');

        // Rôles : multi-choix + badges en listing
        $rolesField = ChoiceField::new('roles', 'Rôles')
            ->setChoices([
                'Admin' => 'ROLE_ADMIN',
                'User'  => 'ROLE_USER',
            ])
            ->allowMultipleChoices()
            ->renderExpanded(false)
            ->setHelp('ROLE_USER est toujours garanti automatiquement.');

        // Sur la liste : affichage badges
        $rolesField->renderAsBadges([
            'ROLE_ADMIN' => 'danger',
            'ROLE_USER'  => 'success',
        ]);

        yield $rolesField;

        // Mot de passe en clair (non persisté) : seulement sur les forms
        // - obligatoire en création
        // - facultatif en édition (laisser vide = ne change pas)
        yield TextField::new('plainPassword', 'Nouveau mot de passe')
            ->setFormType(PasswordType::class)
            ->onlyOnForms()
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->setHelp('Laisse vide pour ne pas changer le mot de passe.');
    }

    /**
     * Création :
     * - hash le mot de passe si fourni (en NEW il est requis)
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            $this->hashPasswordIfProvided($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Édition :
     * - empêche de retirer ROLE_ADMIN au dernier admin
     * - hash le mot de passe si un nouveau est fourni
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
     * Hash le mot de passe uniquement si plainPassword est rempli.
     * Ensuite on nettoie plainPassword (eraseCredentials).
     */
    private function hashPasswordIfProvided(User $user): void
    {
        $plain = $user->getPlainPassword();

        if (is_string($plain) && trim($plain) !== '') {
            $user->setPassword($this->hasher->hashPassword($user, $plain));
            $user->eraseCredentials();
        }
    }

    /**
     * Sécurité :
     * - compare l'user "original" (base) et l'user "édité" (form)
     * - si on retire ROLE_ADMIN à un user qui était admin
     * - et qu'il n'y a qu'1 admin total -> on bloque
     */
    private function preventRemovingLastAdmin(User $editedUser): void
    {
        // Recharge en base pour connaître l'état "avant" modification
        $originalUser = $editedUser->getId()
            ? $this->userRepository->find($editedUser->getId())
            : null;

        if (!$originalUser instanceof User) {
            return; // création ou user introuvable
        }

        $wasAdmin = in_array('ROLE_ADMIN', $originalUser->getRoles(), true);
        $isStillAdmin = in_array('ROLE_ADMIN', $editedUser->getRoles(), true);

        // On ne bloque que si on retire ROLE_ADMIN à quelqu'un qui était admin
        if ($wasAdmin && !$isStillAdmin) {
            $adminsCount = $this->userRepository->countUsersWithRoleAdmin();

            // Si c'est le dernier admin => interdit
            if ($adminsCount <= 1) {
                throw new BadRequestHttpException(
                    'Impossible : tu ne peux pas retirer ROLE_ADMIN au dernier administrateur.'
                );
            }
        }
    }
}