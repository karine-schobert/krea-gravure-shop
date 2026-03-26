<?php

namespace App\Entity;

use App\Repository\ProductOfferRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductOfferRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ProductOffer
{
    public const SALE_TYPE_UNIT = 'unit';
    public const SALE_TYPE_BUNDLE = 'bundle';
    public const SALE_TYPE_SEASONAL = 'seasonal';
    public const SALE_TYPE_SPECIAL = 'special';
    public const SALE_TYPE_FULL_COLLECTION = 'full_collection';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Produit principal lié à cette offre.
     * Exemple :
     * - Produit : "Marguerite"
     * - Offre : "Lot de 4"
     */
    #[ORM\ManyToOne(inversedBy: 'offers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    /**
     * Nom commercial de l'offre.
     * Exemples :
     * - À l’unité
     * - Lot de 4
     * - Offre Noël
     * - Collection complète
     */
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    /**
     * Type d’offre.
     * Valeurs conseillées :
     * - unit
     * - bundle
     * - seasonal
     * - special
     * - full_collection
     */
    #[ORM\Column(length: 50)]
    private ?string $saleType = self::SALE_TYPE_UNIT;

    /**
     * Quantité de pièces incluses dans l’offre.
     * Exemples :
     * - 1
     * - 4
     * - 8
     */
    #[ORM\Column(options: ['default' => 1])]
    private int $quantity = 1;

    /**
     * Prix réel de l’offre en centimes.
     * Exemples :
     * - 1290 = 12,90 €
     * - 1790 = 17,90 €
     */
    #[ORM\Column(options: ['default' => 0])]
    private int $priceCents = 0;

    /**
     * Indique si cette offre permet une personnalisation.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $isCustomizable = false;

    /**
     * Libellé du champ de personnalisation affiché au client.
     * Exemple :
     * - Texte à graver
     * - Prénom
     * - Mot à inscrire
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customizationLabel = null;

    /**
     * Placeholder affiché dans le champ de personnalisation.
     * Exemple :
     * - Ex. Charlotte
     * - Ex. Papa
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customizationPlaceholder = null;

    /**
     * Nombre maximal de caractères autorisés pour la personnalisation.
     * Exemple :
     * - 10
     * - 12
     * - 20
     */
    #[ORM\Column(nullable: true)]
    private ?int $customizationMaxLength = null;

    /**
     * Indique si la personnalisation est obligatoire.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $isCustomizationRequired = false;

    /**
     * Permet d’activer ou désactiver l’offre sans la supprimer.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /**
     * Ordre d’affichage des offres sur la fiche produit.
     */
    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    /**
     * Date de début de validité de l’offre.
     * Utile pour les offres temporaires / saisonnières.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startsAt = null;

    /**
     * Date de fin de validité de l’offre.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endsAt = null;

    /**
     * Date de création de l’offre.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * Date de dernière mise à jour de l’offre.
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    // =========================
    // LIFECYCLE CALLBACKS
    // =========================

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Permet un affichage lisible dans EasyAdmin / formulaires.
     */
    public function __toString(): string
    {
        return $this->title ?? 'Offre';
    }

    /**
     * Retourne la liste des types d’offres disponibles.
     * Très utile pour les formulaires.
     */
    public static function getSaleTypeChoices(): array
    {
        return [
            'À l’unité' => self::SALE_TYPE_UNIT,
            'Lot' => self::SALE_TYPE_BUNDLE,
            'Offre saisonnière' => self::SALE_TYPE_SEASONAL,
            'Offre spéciale' => self::SALE_TYPE_SPECIAL,
            'Collection complète' => self::SALE_TYPE_FULL_COLLECTION,
        ];
    }

    /**
     * Permet de savoir si l’offre est actuellement disponible
     * en tenant compte de son activation et des dates éventuelles.
     */
    public function isCurrentlyAvailable(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = new \DateTime();

        if ($this->startsAt !== null && $this->startsAt > $now) {
            return false;
        }

        if ($this->endsAt !== null && $this->endsAt < $now) {
            return false;
        }

        return true;
    }

    /**
     * Retourne l’identifiant de l’offre.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le produit lié à cette offre.
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * Définit le produit lié à cette offre.
     */
    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Retourne le titre commercial de l’offre.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Définit le titre commercial de l’offre.
     */
    public function setTitle(string $title): static
    {
        $this->title = trim($title);

        return $this;
    }

    /**
     * Retourne le type d’offre.
     */
    public function getSaleType(): ?string
    {
        return $this->saleType;
    }

    /**
     * Définit le type d’offre.
     */
    public function setSaleType(string $saleType): static
    {
        $this->saleType = trim($saleType);

        return $this;
    }

    /**
     * Retourne la quantité incluse dans l’offre.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Définit la quantité incluse dans l’offre.
     */
    public function setQuantity(int $quantity): static
    {
        $this->quantity = max(1, $quantity);

        return $this;
    }

    /**
     * Retourne le prix de l’offre en centimes.
     */
    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    /**
     * Définit le prix de l’offre en centimes.
     */
    public function setPriceCents(int $priceCents): static
    {
        $this->priceCents = max(0, $priceCents);

        return $this;
    }

    /**
     * Indique si l’offre est personnalisable.
     */
    public function isCustomizable(): bool
    {
        return $this->isCustomizable;
    }

    /**
     * Définit si l’offre est personnalisable.
     * Si on désactive la personnalisation, on nettoie les champs liés.
     */
    public function setIsCustomizable(bool $isCustomizable): static
    {
        $this->isCustomizable = $isCustomizable;

        if ($isCustomizable === false) {
            $this->customizationLabel = null;
            $this->customizationPlaceholder = null;
            $this->customizationMaxLength = null;
            $this->isCustomizationRequired = false;
        }

        return $this;
    }

    /**
     * Retourne le libellé du champ de personnalisation.
     */
    public function getCustomizationLabel(): ?string
    {
        return $this->customizationLabel;
    }

    /**
     * Définit le libellé du champ de personnalisation.
     */
    public function setCustomizationLabel(?string $customizationLabel): static
    {
        $this->customizationLabel = $customizationLabel !== null ? trim($customizationLabel) : null;

        return $this;
    }

    /**
     * Retourne le placeholder du champ de personnalisation.
     */
    public function getCustomizationPlaceholder(): ?string
    {
        return $this->customizationPlaceholder;
    }

    /**
     * Définit le placeholder du champ de personnalisation.
     */
    public function setCustomizationPlaceholder(?string $customizationPlaceholder): static
    {
        $this->customizationPlaceholder = $customizationPlaceholder !== null ? trim($customizationPlaceholder) : null;

        return $this;
    }

    /**
     * Retourne la longueur maximale autorisée pour la personnalisation.
     */
    public function getCustomizationMaxLength(): ?int
    {
        return $this->customizationMaxLength;
    }

    /**
     * Définit la longueur maximale autorisée pour la personnalisation.
     */
    public function setCustomizationMaxLength(?int $customizationMaxLength): static
    {
        $this->customizationMaxLength = $customizationMaxLength !== null
            ? max(1, $customizationMaxLength)
            : null;

        return $this;
    }

    /**
     * Indique si la personnalisation est obligatoire.
     */
    public function isCustomizationRequired(): bool
    {
        return $this->isCustomizationRequired;
    }

    /**
     * Définit si la personnalisation est obligatoire.
     * Impossible d’avoir une personnalisation obligatoire
     * si l’offre n’est pas personnalisable.
     */
    public function setIsCustomizationRequired(bool $isCustomizationRequired): static
    {
        $this->isCustomizationRequired = $this->isCustomizable
            ? $isCustomizationRequired
            : false;

        return $this;
    }

    /**
     * Indique si l’offre est active.
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Active ou désactive l’offre.
     */
    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Retourne la position d’affichage.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Définit la position d’affichage.
     */
    public function setPosition(?int $position): static
    {
        $this->position = $position ?? 0;

        return $this;
    }

    /**
     * Retourne la date de début de l’offre.
     */
    public function getStartsAt(): ?\DateTimeInterface
    {
        return $this->startsAt;
    }

    /**
     * Définit la date de début de l’offre.
     */
    public function setStartsAt(?\DateTimeInterface $startsAt): static
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    /**
     * Retourne la date de fin de l’offre.
     */
    public function getEndsAt(): ?\DateTimeInterface
    {
        return $this->endsAt;
    }

    /**
     * Définit la date de fin de l’offre.
     */
    public function setEndsAt(?\DateTimeInterface $endsAt): static
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    /**
     * Retourne la date de création.
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Définit la date de création.
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Retourne la date de mise à jour.
     */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Définit la date de mise à jour.
     */
    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}