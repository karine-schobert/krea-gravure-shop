<?php

namespace App\Entity;

use App\Repository\CartItemRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Ligne de panier.
 *
 * Rôle :
 * - représenter un produit présent dans un panier
 * - stocker la quantité choisie
 * - permettre le calcul du total de la ligne
 *
 * Règle métier :
 * - un même produit ne doit apparaître qu'une seule fois par panier
 *   (contrainte unique cart_id + product_id)
 */
#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_cart_product', columns: ['cart_id', 'product_id'])]
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
     *
     * Plusieurs lignes peuvent appartenir au même panier.
     */
    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Cart $cart = null;

    /**
     * Produit associé à cette ligne.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    /**
     * Quantité demandée pour ce produit.
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
     * Retourne l'identifiant de la ligne panier.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le panier auquel appartient cette ligne.
     */
    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    /**
     * Définit le panier auquel appartient cette ligne.
     */
    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }

    /**
     * Retourne le produit associé à cette ligne.
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * Définit le produit associé à cette ligne.
     *
     * On met à jour updatedAt car la ligne change.
     */
    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        $this->touch();

        return $this;
    }

    /**
     * Retourne la quantité de cette ligne.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Définit la quantité de cette ligne.
     *
     * Sécurité :
     * - on empêche une quantité inférieure à 1
     * - si besoin de supprimer la ligne, cela doit être fait
     *   explicitement dans le controller/service
     */
    public function setQuantity(int $quantity): static
    {
        $this->quantity = max(1, $quantity);
        $this->touch();

        return $this;
    }

    /**
     * Calcule le total de la ligne en centimes.
     *
     * Exemple :
     * - produit = 990 cents
     * - quantity = 3
     * => line total = 2970 cents
     */
    public function getLineTotalCents(): int
    {
        if ($this->product === null) {
            return 0;
        }

        return $this->product->getPriceCents() * $this->quantity;
    }

    /**
     * Retourne la date de création de la ligne panier.
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Définit la date de création de la ligne panier.
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Retourne la date de dernière mise à jour de la ligne panier.
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Définit la date de dernière mise à jour de la ligne panier.
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