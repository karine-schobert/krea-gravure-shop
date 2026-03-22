<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Product
{
    // =========================
    // ID
    // =========================
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================
    // INFOS PRODUIT
    // =========================

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $priceCents = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    // =========================
    // RELATIONS
    // =========================

    // Catégorie
    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    // Seasons (Noël, Pâques, etc.)
    #[ORM\ManyToMany(targetEntity: Season::class, inversedBy: 'products')]
    private Collection $seasons;

    // =========================
    // IMAGE
    // =========================
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    // =========================
    // DATES
    // =========================
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    // =========================
    // CONSTRUCTEUR
    // =========================
    public function __construct()
    {
        $this->seasons = new ArrayCollection();

        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    // =========================
    // LIFECYCLE CALLBACKS
    // =========================
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // =========================
    // GETTERS / SETTERS
    // =========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = trim($title);
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug !== null ? trim($slug) : null;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function setPriceCents(int $priceCents): static
    {
        $this->priceCents = max(0, $priceCents);
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

    // =========================
    // CATEGORY
    // =========================
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    // =========================
    // SEASONS
    // =========================

    /**
     * @return Collection<int, Season>
     */
    public function getSeasons(): Collection
    {
        return $this->seasons;
    }

    public function addSeason(Season $season): static
    {
        if (!$this->seasons->contains($season)) {
            $this->seasons->add($season);
        }
        return $this;
    }

    public function removeSeason(Season $season): static
    {
        $this->seasons->removeElement($season);
        return $this;
    }

    // =========================
    // IMAGE
    // =========================
    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $image = $image !== null ? trim($image) : null;
        $this->image = $image === '' ? null : $image;
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->image ? '/uploads/products/' . $this->image : null;
    }

    // =========================
    // DATES
    // =========================
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // =========================
    // ADMIN DISPLAY
    // =========================
    public function __toString(): string
    {
        return $this->getTitle() ?: 'Produit';
    }
}