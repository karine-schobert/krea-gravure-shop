<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    #[ORM\Column(length: 180, unique :true)]
    private ?string $email = null;

    /**
     * Rôles en base (ex: ["ROLE_ADMIN"])
     *
     * ROLE_USER est ajouté automatiquement dans getRoles()
     *
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * Mot de passe hashé (jamais stocker en clair)
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * Mot de passe en clair (NON persisté en base)
     *
     * Utilisé uniquement :
     * - par les formulaires (EasyAdmin / registration)
     * - puis hashé pour alimenter $password
     */
    private ?string $plainPassword = null;

    /**
     * Liste des commandes liées à cet utilisateur
     *
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class)]
    private Collection $orders;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

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
     * Identifiant utilisé par Symfony Security
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Affichage pratique dans EasyAdmin / relations
     */
    public function __toString(): string
    {
        return (string) $this->email;
    }

    /**
     * Retourne les rôles
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Définit les rôles en base
     *
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Retourne le mot de passe hashé
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Définit le mot de passe hashé
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Retourne le mot de passe en clair (non persisté)
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * Définit le mot de passe en clair (non persisté)
     */
    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * Nettoyage des données sensibles temporaires
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setUser($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            if ($order->getUser() === $this) {
                $order->setUser(null);
            }
        }

        return $this;
    }
}