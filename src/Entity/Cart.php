<?php

namespace App\Entity;

use App\Repository\CartRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité Panier.
 *
 * Rôle :
 * - stocker le panier actif d'un utilisateur
 * - contenir les lignes de panier (CartItem)
 * - calculer des totaux utiles (quantité, montant)
 *
 * Règle métier retenue :
 * - 1 utilisateur = 1 panier actif
 */
#[ORM\Entity(repositoryClass: CartRepository::class)]
class Cart
{
    /**
     * Identifiant technique du panier.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Utilisateur propriétaire du panier.
     *
     * OneToOne :
     * - un utilisateur possède un seul panier
     * - si l'utilisateur est supprimé, le panier l'est aussi
     */
    #[ORM\OneToOne(inversedBy: 'cart')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * Lignes du panier.
     *
     * mappedBy = "cart" car la relation est portée côté CartItem.
     * orphanRemoval = true permet de supprimer automatiquement
     * une ligne retirée de la collection si elle n'est plus rattachée.
     *
     * @var Collection<int, CartItem>
     */
    #[ORM\OneToMany(
        mappedBy: 'cart',
        targetEntity: CartItem::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $items;

    /**
     * Date de création du panier.
     */
    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière modification du panier.
     *
     * À mettre à jour dès qu'une ligne est ajoutée, modifiée ou supprimée.
     */
    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * Initialise le panier.
     *
     * On prépare la collection des lignes et les dates par défaut.
     */
    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

     /**
     * Affichage pratique dans EasyAdmin / relations.
     */
        public function __toString(): string
    {
        return sprintf(
            'Panier #%d - %d article(s) - %.2f €',
            $this->id ?? 0,
            $this->getTotalQuantity(),
            $this->getTotalCents() / 100
        );
    }


    /**
     * Retourne l'identifiant du panier.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne l'utilisateur lié à ce panier.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Définit l'utilisateur propriétaire du panier.
     */
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Retourne toutes les lignes du panier.
     *
     * @return Collection<int, CartItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Ajoute une ligne au panier si elle n'existe pas déjà dans la collection.
     *
     * Important :
     * - on maintient la relation bidirectionnelle
     * - on met à jour updatedAt via touch()
     */
    public function addItem(CartItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCart($this);
        }

        $this->touch();

        return $this;
    }

    /**
     * Retire une ligne du panier.
     *
     * Important :
     * - on casse aussi le lien côté CartItem si nécessaire
     * - on met à jour updatedAt via touch()
     */
    public function removeItem(CartItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getCart() === $this) {
                $item->setCart(null);
            }
        }

        $this->touch();

        return $this;
    }

    /**
     * Calcule la quantité totale d'articles dans le panier.
     *
     * Exemple :
     * - 2 x produit A
     * - 3 x produit B
     * => totalQuantity = 5
     */
    public function getTotalQuantity(): int
    {
        $total = 0;

        foreach ($this->items as $item) {
            $total += $item->getQuantity();
        }

        return $total;
    }

    /**
     * Calcule le montant total du panier en centimes.
     *
     * On s'appuie sur getLineTotalCents() de chaque ligne.
     */
    public function getTotalCents(): int
    {
        $total = 0;

        foreach ($this->items as $item) {
            $total += $item->getLineTotalCents();
        }

        return $total;
    }

    /**
     * Retourne la date de création du panier.
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Définit la date de création du panier.
     *
     * Utile surtout lors d'une création manuelle explicite.
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Retourne la date de dernière mise à jour du panier.
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Définit la date de dernière mise à jour du panier.
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Met à jour automatiquement la date de modification du panier.
     *
     * À appeler dès qu'une action impacte le contenu du panier.
     */
    public function touch(): static
    {
        $this->updatedAt = new DateTimeImmutable();

        return $this;
    }
}