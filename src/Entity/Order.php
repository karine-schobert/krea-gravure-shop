<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';
    public const STATUS_PAID = 'PAID';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_REFUNDED = 'REFUNDED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $user = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_PENDING_PAYMENT;

    #[ORM\Column]
    private ?int $totalCents = 0;

    #[ORM\Column(length: 10)]
    private ?string $currency = 'eur';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    /**
     * Lignes de la commande.
     *
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->status = self::STATUS_PENDING_PAYMENT;
        $this->currency = 'eur';
        $this->totalCents = 0;
    }

    /**
     * Représentation texte de la commande
     * utile pour EasyAdmin, les relations et les listes.
     */
    public function __toString(): string
    {
        return sprintf(
            '#%d - %s - %s',
            $this->id ?? 0,
            $this->email ?? 'sans email',
            $this->status ?? 'sans statut'
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Utilisateur lié à la commande
     * (peut être null selon la logique métier choisie).
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Email utilisé pour la commande.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Statut métier de la commande
     * (pending, paid, failed, cancelled, etc.).
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Total de la commande en centimes.
     */
    public function getTotalCents(): ?int
    {
        return $this->totalCents;
    }

    public function setTotalCents(int $totalCents): static
    {
        $this->totalCents = $totalCents;

        return $this;
    }

    /**
     * Devise de la commande
     * (ex : eur).
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * ID de session Stripe lié au checkout.
     */
    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    /**
     * ID du PaymentIntent Stripe.
     */
    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    /**
     * Date de création de la commande.
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Date de dernière mise à jour.
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Date de paiement confirmée.
     */
    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    /**
     * Retourne les lignes de commande.
     *
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Ajoute une ligne à la commande
     * et synchronise la relation côté OrderItem.
     */
    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    /**
     * Supprime une ligne de commande
     * et nettoie la relation inverse.
     */
    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }

        return $this;
    }
}