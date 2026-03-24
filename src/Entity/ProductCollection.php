<?php

namespace App\Entity;

use App\Repository\ProductCollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductCollectionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ProductCollection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom de la collection.
     * Ex : Chic Noir, Naturelle, Noël Tradition
     */
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * Slug unique pour les URLs ou l'identification interne.
     * Ex : chic-noir, naturelle, noel-tradition
     */
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    /**
     * Petite description de la collection.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Nom du fichier image ou chemin relatif.
     * Ex : chic-noir.jpg
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    /**
     * Position pour trier les collections dans l'admin ou le front.
     * Plus le chiffre est petit, plus la collection remonte.
     */
    #[ORM\Column]
    private int $position = 0;

    /**
     * Permet d'activer / désactiver une collection.
     */
    #[ORM\Column]
    private bool $isActive = true;

    /**
     * Date de création.
     */
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Produits liés à cette collection.
     */
    #[ORM\OneToMany(mappedBy: 'productCollection', targetEntity: Product::class)]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function __toString(): string
    {
        return $this->name ?? 'Collection sans nom';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = trim($name);

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = trim($slug);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description !== null ? trim($description) : null;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image !== null ? trim($image) : null;

        return $this;
    }

    /**
     * Retourne le chemin web complet relatif si une image existe.
     * Ex : /uploads/collections/chic-noir.jpg
     */
    public function getImagePath(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return '/uploads/collections/' . $this->image;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

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
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setProductCollection($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            if ($product->getProductCollection() === $this) {
                $product->setProductCollection(null);
            }
        }

        return $this;
    }
}