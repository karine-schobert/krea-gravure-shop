<?php

namespace App\Entity;

use App\Repository\StaticPageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StaticPageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class StaticPage
{
    // =========================
    // IDENTIFIANT
    // =========================
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // =========================
    // INFORMATIONS PRINCIPALES
    // =========================
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column]
    private bool $isActive = true;

    // =========================
    // SEO
    // =========================
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $metaDescription = null;

    // =========================
    // DATES TECHNIQUES
    // =========================
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    // =========================
    // AFFICHAGE ADMIN
    // =========================
    public function __toString(): string
    {
        return $this->title ?? 'Page statique';
    }

    // =========================
    // LIFECYCLE CALLBACKS
    // =========================
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        // Sécurise les dates à la création
        if (null === $this->createdAt) {
            $this->createdAt = $now;
        }

        if (null === $this->updatedAt) {
            $this->updatedAt = $now;
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        // Met à jour automatiquement la date de modification
        $this->updatedAt = new \DateTimeImmutable();
    }

    // =========================
    // GETTERS / SETTERS
    // =========================
    public function getId(): ?int
    {
        return $this->id;
    }

    // ----- title -----
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    // ----- slug -----
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    // ----- content -----
    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    // ----- isActive -----
    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    // ----- metaTitle -----
    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): static
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    // ----- metaDescription -----
    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): static
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    // ----- createdAt -----
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    // ----- updatedAt -----
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}