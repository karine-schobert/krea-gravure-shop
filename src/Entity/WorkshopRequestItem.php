<?php

namespace App\Entity;

use App\Repository\WorkshopRequestItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: WorkshopRequestItemRepository::class)]
#[ORM\Table(name: 'workshop_request_item')]
#[ORM\HasLifecycleCallbacks]
class WorkshopRequestItem
{
    /*
    |--------------------------------------------------------------------------
    | Clé primaire
    |--------------------------------------------------------------------------
    */

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /*
    |--------------------------------------------------------------------------
    | Demande principale liée à cette ligne
    |--------------------------------------------------------------------------
    | Une ligne de besoin doit toujours appartenir à une demande atelier.
    |--------------------------------------------------------------------------
    */

    #[Assert\NotNull(message: 'La demande atelier parente est obligatoire.')]
    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?WorkshopRequest $workshopRequest = null;

    /*
    |--------------------------------------------------------------------------
    | Liens optionnels vers le catalogue
    |--------------------------------------------------------------------------
    | Le client peut :
    | - choisir une catégorie seulement
    | - choisir un produit précis
    | - ou décrire une idée libre sans rien sélectionner
    |--------------------------------------------------------------------------
    */

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    /*
    |--------------------------------------------------------------------------
    | Libellé libre
    |--------------------------------------------------------------------------
    | Exemple :
    | - "porte-clé pour une maîtresse"
    | - "plaque gravée entreprise"
    |--------------------------------------------------------------------------
    */

    #[Assert\Length(
        max: 180,
        maxMessage: 'Le libellé libre ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $customLabel = null;

    /*
    |--------------------------------------------------------------------------
    | Quantité souhaitée
    |--------------------------------------------------------------------------
    | Optionnelle en V1, mais si renseignée elle doit être strictement positive.
    |--------------------------------------------------------------------------
    */

    #[Assert\Positive(message: 'La quantité doit être un nombre positif.')]
    #[ORM\Column(nullable: true)]
    private ?int $quantity = null;

    /*
    |--------------------------------------------------------------------------
    | Notes de personnalisation et de fabrication
    |--------------------------------------------------------------------------
    */

    #[Assert\Length(
        max: 5000,
        maxMessage: 'Le texte de personnalisation ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $personalizationText = null;

    #[Assert\Length(
        max: 255,
        maxMessage: 'Les notes matière ne peuvent pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $materialNotes = null;

    #[Assert\Length(
        max: 255,
        maxMessage: 'Les notes de format ne peuvent pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $formatNotes = null;

    #[Assert\Length(
        max: 255,
        maxMessage: 'Les notes de couleur ne peuvent pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $colorNotes = null;

    #[Assert\Length(
        max: 255,
        maxMessage: 'Les notes de dimensions ne peuvent pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dimensionsNotes = null;

    /*
    |--------------------------------------------------------------------------
    | Message libre propre à cette ligne
    |--------------------------------------------------------------------------
    | Permet d’ajouter un détail spécifique à un besoin produit.
    |--------------------------------------------------------------------------
    */

    #[Assert\Length(
        max: 5000,
        maxMessage: 'Le message de ligne ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $lineMessage = null;

    /*
    |--------------------------------------------------------------------------
    | Ordre d’affichage
    |--------------------------------------------------------------------------
    */

    #[Assert\PositiveOrZero(message: 'La position doit être positive ou nulle.')]
    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    /*
    |--------------------------------------------------------------------------
    | Dates techniques
    |--------------------------------------------------------------------------
    */

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
    | Validation métier transverse
    |--------------------------------------------------------------------------
    | Une ligne ne doit pas être "vide".
    |
    | En V1 on impose au moins un des éléments suivants :
    | - un produit
    | - une catégorie
    | - un libellé libre
    |--------------------------------------------------------------------------
    */

    #[Assert\Callback]
    public function validateContent(ExecutionContextInterface $context): void
    {
        $hasProduct = $this->product !== null;
        $hasCategory = $this->category !== null;
        $hasCustomLabel = !empty(trim((string) $this->customLabel));

        if (!$hasProduct && !$hasCategory && !$hasCustomLabel) {
            $context->buildViolation('Une ligne doit contenir au moins un produit, une catégorie ou un libellé libre.')
                ->atPath('customLabel')
                ->addViolation();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers métier simples
    |--------------------------------------------------------------------------
    */

    /**
     * Met à jour manuellement la date de modification.
     * Utile si tu modifies l’entité en dehors du cycle habituel.
     */
    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    /**
     * Retourne un libellé lisible côté admin.
     */
    public function getDisplayLabel(): string
    {
        if ($this->product?->getTitle()) {
            return $this->product->getTitle();
        }

        if (!empty($this->customLabel)) {
            return $this->customLabel;
        }

        if ($this->category?->getName()) {
            return $this->category->getName();
        }

        return 'Ligne de demande';
    }

    public function __toString(): string
    {
        return $this->getDisplayLabel();
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        $this->touch();

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        $this->touch();

        return $this;
    }

    public function getCustomLabel(): ?string
    {
        return $this->customLabel;
    }

    public function setCustomLabel(?string $customLabel): static
    {
        $this->customLabel = $customLabel;
        $this->touch();

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;
        $this->touch();

        return $this;
    }

    public function getPersonalizationText(): ?string
    {
        return $this->personalizationText;
    }

    public function setPersonalizationText(?string $personalizationText): static
    {
        $this->personalizationText = $personalizationText;
        $this->touch();

        return $this;
    }

    public function getMaterialNotes(): ?string
    {
        return $this->materialNotes;
    }

    public function setMaterialNotes(?string $materialNotes): static
    {
        $this->materialNotes = $materialNotes;
        $this->touch();

        return $this;
    }

    public function getFormatNotes(): ?string
    {
        return $this->formatNotes;
    }

    public function setFormatNotes(?string $formatNotes): static
    {
        $this->formatNotes = $formatNotes;
        $this->touch();

        return $this;
    }

    public function getColorNotes(): ?string
    {
        return $this->colorNotes;
    }

    public function setColorNotes(?string $colorNotes): static
    {
        $this->colorNotes = $colorNotes;
        $this->touch();

        return $this;
    }

    public function getDimensionsNotes(): ?string
    {
        return $this->dimensionsNotes;
    }

    public function setDimensionsNotes(?string $dimensionsNotes): static
    {
        $this->dimensionsNotes = $dimensionsNotes;
        $this->touch();

        return $this;
    }

    public function getLineMessage(): ?string
    {
        return $this->lineMessage;
    }

    public function setLineMessage(?string $lineMessage): static
    {
        $this->lineMessage = $lineMessage;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
{
    $this->createdAt = $createdAt;

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