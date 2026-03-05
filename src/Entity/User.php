<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Entité User (sécurité Symfony)
 *
 * Objectifs :
 * - Authentification via email + mot de passe hashé
 * - Gestion des rôles (ROLE_USER forcé, ROLE_ADMIN possible)
 * - Champ "plainPassword" NON persisté (uniquement formulaire / EasyAdmin)
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * Identifiant technique (auto-incrément)
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Email utilisateur (sert aussi d'identifiant de connexion)
     */
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * Rôles en base (ex: ["ROLE_ADMIN"])
     *
     * ⚠️ Note : ROLE_USER est ajouté automatiquement dans getRoles()
     *
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * Mot de passe hashé (jamais stocker en clair)
     *
     * @var string|null
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * Mot de passe en clair (NON persisté en base)
     *
     * Utilisé uniquement :
     * - par les formulaires (EasyAdmin / registration)
     * - puis hashé pour alimenter $password
     *
     * ⚠️ Ne JAMAIS mapper en Doctrine, sinon tu stockes du clair.
     */
    private ?string $plainPassword = null;

    /**
     * Retourne l'ID utilisateur
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne l'email
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Définit l'email
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Identifiant "visible" pour Symfony Security.
     * Ici on utilise l'email.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Alias pratique : quand EasyAdmin ou Symfony doit afficher un User
     * (ex: dans une relation), ça affiche l'email.
     */
    public function __toString(): string
    {
        return (string) $this->email;
    }

    /**
     * Retourne les rôles.
     *
     * - On garantit toujours ROLE_USER même si non stocké en base.
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        // Garantie : tout user a au minimum ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Définit les rôles en base.
     *
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Retourne le mot de passe hashé (stocké en base)
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Définit le mot de passe hashé (stocké en base)
     *
     * ⚠️ Doit recevoir un HASH (jamais du clair).
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Retourne le mot de passe en clair (NON persisté)
     * Sert uniquement pour formulaire, puis hash.
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * Définit le mot de passe en clair (NON persisté)
     */
    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * Nettoyage des données sensibles temporaires.
     *
     * Symfony appelle cette méthode après l'auth ou quand nécessaire.
     * Ici on efface le plainPassword pour éviter qu'il traîne en mémoire.
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }
}