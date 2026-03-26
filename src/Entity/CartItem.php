<?php

namespace App\Entity;

use App\Repository\CartItemRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Ligne de panier.
 *
 * Rôle :
 * - représenter une ligne commerciale réelle dans un panier
 * - stocker le produit
 * - stocker éventuellement l'offre choisie
 * - stocker éventuellement une personnalisation
 * - stocker la quantité choisie
 *
 * Nouvelle règle métier :
 * - un même produit peut apparaître plusieurs fois dans le panier
 *   si l’offre ou la personnalisation diffère
 */
#[ORM\Entity(repositoryClass: CartItemRepository::class)]
class CartItem
{
    /**
     * Identifiant technique de la ligne panier.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Panier propriétaire de cette ligne.
     */
    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Cart $cart = null;

    /**
     * Produit principal associé à cette ligne.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    /**
     * Offre commerciale choisie pour ce produit.
     *
     * Nullable :
     * - null = achat simple au prix produit
     * - non null = achat via une ProductOffer
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProductOffer $offer = null;

    /**
     * Texte de personnalisation saisi par le client.
     *
     * Nullable :
     * - null = aucune personnalisation
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customization = null;

    /**
     * Quantité demandée pour cette ligne.
     */
    #[ORM\Column]
    private int $quantity = 1;

    /**
     * Date de création de la ligne panier.
     */
    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière mise à jour de la ligne panier.
     */
    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * Initialise les dates par défaut à la création.
     */
    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Retourne l'identifiant technique.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le panier propriétaire.
     */
    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    /**
     * Définit le panier propriétaire.
     */
    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;
        $this->touch();

        return $this;
    }

    /**
     * Retourne le produit de la ligne.
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * Définit le produit de la ligne.
     */
    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        $this->touch();

        return $this;
    }

    /**
     * Retourne l'offre choisie.
     */
    public function getOffer(): ?ProductOffer
    {
        return $this->offer;
    }

    /**
     * Définit l'offre choisie.
     */
    public function setOffer(?ProductOffer $offer): static
    {
        $this->offer = $offer;
        $this->touch();

        return $this;
    }

    /**
     * Retourne la personnalisation saisie.
     */
    public function getCustomization(): ?string
    {
        return $this->customization;
    }

    /**
     * Définit la personnalisation.
     *
     * Nettoyage :
     * - trim automatique
     * - chaîne vide transformée en null
     */
    public function setCustomization(?string $customization): static
    {
        $customization = $customization !== null ? trim($customization) : null;
        $this->customization = $customization === '' ? null : $customization;
        $this->touch();

        return $this;
    }

    /**
     * Retourne la quantité.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Définit la quantité.
     *
     * Sécurité :
     * - minimum 1
     * - la suppression doit rester gérée ailleurs
     */
    public function setQuantity(int $quantity): static
    {
        $this->quantity = max(1, $quantity);
        $this->touch();

        return $this;
    }

    /**
     * Retourne le prix unitaire réel en centimes.
     *
     * Priorité :
     * - prix de l'offre si elle existe
     * - sinon prix du produit
     */
    public function getUnitPriceCents(): int
    {
        if ($this->offer !== null) {
            return $this->offer->getPriceCents();
        }

        if ($this->product !== null) {
            return $this->product->getPriceCents();
        }

        return 0;
    }

    /**
     * Calcule le total de la ligne en centimes.
     */
    public function getLineTotalCents(): int
    {
        return $this->getUnitPriceCents() * $this->quantity;
    }

    /**
     * Retourne la date de création.
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Définit la date de création.
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Retourne la date de dernière modification.
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Définit la date de dernière modification.
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Met à jour automatiquement la date de modification.
     */
    public function touch(): static
    {
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }
}