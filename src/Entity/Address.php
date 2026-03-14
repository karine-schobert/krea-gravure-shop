<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant une adresse client.
 *
 * Cette table servira pour :
 * - les adresses de livraison
 * - les adresses de facturation si besoin plus tard
 * - la sélection d'une adresse par défaut
 */
#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Address
{
    /**
     * Identifiant unique de l'adresse.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Utilisateur propriétaire de cette adresse.
     */
    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * Prénom du destinataire.
     */
    #[ORM\Column(length: 150)]
    private ?string $firstName = null;

    /**
     * Nom du destinataire.
     */
    #[ORM\Column(length: 150)]
    private ?string $lastName = null;

    /**
     * Ligne principale de l'adresse.
     * Exemple : 12 rue des Lilas
     */
    #[ORM\Column(length: 255)]
    private ?string $address = null;

    /**
     * Ville.
     */
    #[ORM\Column(length: 150)]
    private ?string $city = null;

    /**
     * Code postal.
     */
    #[ORM\Column(length: 20)]
    private ?string $postalCode = null;

    /**
     * Pays.
     */
    #[ORM\Column(length: 100)]
    private ?string $country = null;

    /**
     * Téléphone du destinataire.
     * Nullable car facultatif.
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    /**
     * Instructions de livraison.
     * Exemple : laisser au portail, sonner chez le voisin, etc.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $instructions = null;

    /**
     * Indique si cette adresse est l'adresse par défaut du client.
     */
    #[ORM\Column]
    private bool $isDefault = false;

    /**
     * Date de création.
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière mise à jour.
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Initialisation automatique des dates à la création.
     */
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /**
     * Mise à jour automatique de updatedAt avant modification.
     */
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Retourne l'identifiant.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne l'utilisateur propriétaire.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Définit l'utilisateur propriétaire.
     */
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Retourne le prénom.
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Définit le prénom.
     */
    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Retourne le nom.
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * Définit le nom.
     */
    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Retourne l'adresse principale.
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * Définit l'adresse principale.
     */
    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Retourne la ville.
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * Définit la ville.
     */
    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Retourne le code postal.
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * Définit le code postal.
     */
    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Retourne le pays.
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * Définit le pays.
     */
    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Retourne le téléphone.
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Définit le téléphone.
     */
    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Retourne les instructions de livraison.
     */
    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    /**
     * Définit les instructions de livraison.
     */
    public function setInstructions(?string $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * Retourne si l'adresse est l'adresse par défaut.
     */
    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    /**
     * Définit si l'adresse est l'adresse par défaut.
     */
    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Retourne la date de création.
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Définit la date de création.
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Retourne la date de mise à jour.
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Définit la date de mise à jour.
     */
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}