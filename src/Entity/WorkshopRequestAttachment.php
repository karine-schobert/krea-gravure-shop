<?php

namespace App\Entity;

use App\Repository\WorkshopRequestAttachmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkshopRequestAttachmentRepository::class)]
#[ORM\Table(name: 'workshop_request_attachment')]
#[ORM\HasLifecycleCallbacks]
class WorkshopRequestAttachment
{
    /**
     * Types métier simples pour la V1.
     * On reste sur des constantes string,
     * faciles à réutiliser dans les formulaires et CRUD.
     */
    public const TYPE_VISUAL = 'visual';
    public const TYPE_LOGO = 'logo';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_INSPIRATION = 'inspiration';
    public const TYPE_OTHER = 'other';

    /**
     * Liste blanche des types autorisés.
     */
    public const ALLOWED_TYPES = [
        self::TYPE_VISUAL,
        self::TYPE_LOGO,
        self::TYPE_DOCUMENT,
        self::TYPE_INSPIRATION,
        self::TYPE_OTHER,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Demande atelier parente.
     * Non nullable : une pièce jointe doit toujours appartenir à une demande.
     */
    #[Assert\NotNull(message: 'La demande atelier parente est obligatoire.')]
    #[ORM\ManyToOne(inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?WorkshopRequest $workshopRequest = null;

    /**
     * Nom original du fichier envoyé par le client.
     * Exemple : logo-entreprise.png
     */
    #[Assert\NotBlank(message: 'Le nom original du fichier est obligatoire.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le nom original ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    /**
     * Nom réellement stocké sur le serveur après renommage sécurisé.
     * Exemple : wr_202604_x8f9k2.png
     */
    #[Assert\NotBlank(message: 'Le nom de stockage du fichier est obligatoire.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le nom stocké ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 255)]
    private ?string $storedName = null;

    /**
     * Chemin relatif vers le fichier stocké.
     * Exemple : uploads/workshop-requests/2026/04/wr_202604_x8f9k2.png
     */
    #[Assert\NotBlank(message: 'Le chemin du fichier est obligatoire.')]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Le chemin du fichier ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 500)]
    private ?string $path = null;

    /**
     * Type MIME du fichier.
     * Exemple : image/png, application/pdf
     */
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le type MIME ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    /**
     * Taille du fichier en octets.
     */
    #[Assert\PositiveOrZero(message: 'La taille du fichier doit être positive ou nulle.')]
    #[ORM\Column(nullable: true)]
    private ?int $size = null;

    /**
     * Type métier simple.
     * Exemples : visual, logo, document...
     */
    #[Assert\NotBlank(message: 'Le type de pièce jointe est obligatoire.')]
    #[Assert\Choice(
        choices: self::ALLOWED_TYPES,
        message: 'Le type de pièce jointe sélectionné est invalide.'
    )]
    #[ORM\Column(length: 50)]
    private string $attachmentType = self::TYPE_OTHER;

    /**
     * Ordre d’affichage/admin.
     */
    #[Assert\PositiveOrZero(message: 'La position doit être positive ou nulle.')]
    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    /**
     * Booléen pratique si un admin veut masquer un fichier sans le supprimer.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $isVisible = true;

    /**
     * Permet d’indiquer si le fichier a été vérifié côté admin.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $isChecked = false;

    /**
     * Éventuelle note admin sur le fichier.
     */
    #[Assert\Length(
        max: 5000,
        maxMessage: 'Les notes admin ne peuvent pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotes = null;

    /**
     * Petit hash optionnel si plus tard tu veux détecter des doublons.
     * Exemple : SHA-256.
     */
    #[Assert\Length(
        min: 32,
        minMessage: 'Le hash semble trop court pour être valide.',
        max: 64,
        maxMessage: 'Le hash ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $fileHash = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle callbacks Doctrine
    |--------------------------------------------------------------------------
    */

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }

        if ($this->updatedAt === null) {
            $this->updatedAt = $now;
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers métier
    |--------------------------------------------------------------------------
    */

    /**
     * Met à jour manuellement updatedAt.
     */
    public function touch(): static
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Libellé lisible en admin.
     */
    public function getDisplayName(): string
    {
        return $this->originalName
            ?? $this->storedName
            ?? ('Pièce jointe #' . $this->id);
    }
    /**
     * Construit une URL web exploitable par le navigateur à partir du chemin stocké.
     *
     * Cas gérés :
     * - public\uploads\workshop-requests\...
     * - public/uploads/workshop-requests/...
     * - uploads/workshop-requests/...
     * - /uploads/workshop-requests/...
     *
     * Objectif final :
     * - toujours retourner une URL web propre du type
     *   /uploads/workshop-requests/2026/04/mon-fichier.jpg
     */
    public function getPublicUrl(): ?string
    {
        if (!$this->path) {
            return null;
        }

        // Uniformise les séparateurs Windows "\" vers "/"
        $normalizedPath = str_replace('\\', '/', trim($this->path));

        // Supprime un éventuel préfixe "public/"
        if (str_starts_with($normalizedPath, 'public/')) {
            $normalizedPath = substr($normalizedPath, strlen('public/'));
        }

        // Supprime aussi un éventuel préfixe "/public/"
        if (str_starts_with($normalizedPath, '/public/')) {
            $normalizedPath = substr($normalizedPath, strlen('/public'));
        }

        // Garantit que l'URL commence bien par "/"
        $normalizedPath = '/' . ltrim($normalizedPath, '/');

        return $normalizedPath;
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }

    /*
    |--------------------------------------------------------------------------
    | Getters / Setters
    |--------------------------------------------------------------------------
    */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkshopRequest(): ?WorkshopRequest
    {
        return $this->workshopRequest;
    }

    public function setWorkshopRequest(?WorkshopRequest $workshopRequest): static
    {
        $this->workshopRequest = $workshopRequest;
        $this->touch();

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;
        $this->touch();

        return $this;
    }

    public function getStoredName(): ?string
    {
        return $this->storedName;
    }

    public function setStoredName(string $storedName): static
    {
        $this->storedName = $storedName;
        $this->touch();

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;
        $this->touch();

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;
        $this->touch();

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;
        $this->touch();

        return $this;
    }

    public function getAttachmentType(): string
    {
        return $this->attachmentType;
    }

    public function setAttachmentType(string $attachmentType): static
    {
        $this->attachmentType = $attachmentType;
        $this->touch();

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;
        $this->touch();

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): static
    {
        $this->isVisible = $isVisible;
        $this->touch();

        return $this;
    }

    public function isChecked(): bool
    {
        return $this->isChecked;
    }

    public function setIsChecked(bool $isChecked): static
    {
        $this->isChecked = $isChecked;
        $this->touch();

        return $this;
    }

    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function setAdminNotes(?string $adminNotes): static
    {
        $this->adminNotes = $adminNotes;
        $this->touch();

        return $this;
    }

    public function getFileHash(): ?string
    {
        return $this->fileHash;
    }

    public function setFileHash(?string $fileHash): static
    {
        $this->fileHash = $fileHash;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        $this->touch();

        return $this;
    }

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
