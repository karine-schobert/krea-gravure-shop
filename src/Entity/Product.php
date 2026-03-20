<?php

namespace App\Entity;

use App\Repository\ProductRepository;
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
    // Infos produit
    // =========================

    // Titre affiché partout (admin + site)
    #[ORM\Column(length: 255)]
    private string $title = '';

    // Slug pour URL (ex: /produits/mon-produit)
    // Nullable : si tu le génères automatiquement au persist/update
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    // Description longue (peut être vide)
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    // Prix stocké en centimes (ex: 1290 = 12,90€)
    #[ORM\Column(options: ['default' => 0])]
    private int $priceCents = 0;

    // Actif/inactif (pour masquer du catalogue)
    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    // =========================
    // Catégorie (obligatoire)
    // =========================
    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    // =========================
    // Dates (auto)
    // =========================

    // Date de création
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    // Date de mise à jour
    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    // =========================
    // Image (nom de fichier)
    // Exemple: "porte-cle-1700000000.jpg"
    // Upload: public/uploads/products/
    // =========================
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function __construct()
    {
        // Sécurité : initialisation même si les callbacks ne tournent pas
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    // =========================
    // Lifecycle callbacks
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
    // Getters / Setters
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
        // Empêche les valeurs négatives
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Utile si tu importes via fixtures
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    // Correction : trim + null si vide
    public function setImage(?string $image): self
    {
        $image = $image !== null ? trim($image) : null;
        $this->image = $image === '' ? null : $image;
        return $this;
    }

    // Bonus pratique (optionnel) : chemin public complet
    // Exemple: "/uploads/products/xxx.jpg"
    public function getImagePath(): ?string
    {
        return $this->image ? '/uploads/products/' . $this->image : null;
    }

    /**
     * Permet à EasyAdmin (et Symfony en général)
     * de convertir automatiquement un Product en string.
     *
     * 👉 utilisé pour :
     * - les listes déroulantes (relations ManyToMany)
     * - les affichages dans l'admin
     *
     * Sans cette méthode → erreur :
     * "Object of class Product could not be converted to string"
     */
    public function __toString(): string
    {
        return $this->getTitle() ?? 'Produit';
    }
}