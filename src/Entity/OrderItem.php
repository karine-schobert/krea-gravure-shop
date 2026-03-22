<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\Column(length: 255)]
    private ?string $productTitle = null;

    #[ORM\Column]
    private ?int $unitPriceCents = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?int $lineTotalCents = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $productImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $productSlug = null;

    /**
     * Représentation texte utile pour EasyAdmin
     * et l'affichage des collections.
     */
    public function __toString(): string
    {
        $title = $this->productTitle ?? 'Produit';
        $quantity = $this->quantity ?? 0;
        $unit = number_format(($this->unitPriceCents ?? 0) / 100, 2, ',', ' ');
        $total = number_format(($this->lineTotalCents ?? 0) / 100, 2, ',', ' ');

        return sprintf('%s x%d - %s € / total %s €', $title, $quantity, $unit, $total);
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getProductTitle(): ?string
    {
        return $this->productTitle;
    }

    public function setProductTitle(string $productTitle): static
    {
        $this->productTitle = $productTitle;
        return $this;
    }

    public function getUnitPriceCents(): ?int
    {
        return $this->unitPriceCents;
    }

    public function setUnitPriceCents(int $unitPriceCents): static
    {
        $this->unitPriceCents = $unitPriceCents;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getLineTotalCents(): ?int
    {
        return $this->lineTotalCents;
    }

    public function setLineTotalCents(int $lineTotalCents): static
    {
        $this->lineTotalCents = $lineTotalCents;
        return $this;
    }
    public function getProductImage(): ?string
    {
        return $this->productImage;
    }

    public function setProductImage(?string $productImage): self
    {
        $this->productImage = $productImage;

        return $this;
    }

    public function getProductSlug(): ?string
    {
        return $this->productSlug;
    }

    public function setProductSlug(?string $productSlug): self
    {
        $this->productSlug = $productSlug;

        return $this;
    }
}
