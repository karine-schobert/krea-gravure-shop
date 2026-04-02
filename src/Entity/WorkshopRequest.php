<?php

namespace App\Entity;

use App\Repository\WorkshopRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: WorkshopRequestRepository::class)]
#[ORM\Table(name: 'workshop_request')]
#[ORM\HasLifecycleCallbacks]
class WorkshopRequest
{
    /*
    |--------------------------------------------------------------------------
    | Constantes métier - type de demandeur
    |--------------------------------------------------------------------------
    */

    public const CUSTOMER_TYPE_INDIVIDUAL = 'individual';
    public const CUSTOMER_TYPE_COMPANY = 'company';
    public const CUSTOMER_TYPE_ASSOCIATION = 'association';

    /*
    |--------------------------------------------------------------------------
    | Constantes métier - type de demande
    |--------------------------------------------------------------------------
    */

    public const REQUEST_TYPE_INFORMATION = 'information';
    public const REQUEST_TYPE_CUSTOM_REQUEST = 'custom_request';
    public const REQUEST_TYPE_PROFESSIONAL_REQUEST = 'professional_request';
    public const REQUEST_TYPE_ASSOCIATION_REQUEST = 'association_request';
    public const REQUEST_TYPE_EVENT_REQUEST = 'event_request';
    public const REQUEST_TYPE_PREORDER = 'preorder';
    public const REQUEST_TYPE_QUOTE_REQUEST = 'quote_request';

    /*
    |--------------------------------------------------------------------------
    | Constantes métier - besoin principal
    |--------------------------------------------------------------------------
    */

    public const NEED_TYPE_GIFT = 'gift';
    public const NEED_TYPE_DECORATION = 'decoration';
    public const NEED_TYPE_EVENT = 'event';
    public const NEED_TYPE_BUSINESS_COMMUNICATION = 'business_communication';
    public const NEED_TYPE_PERSONALIZED_OBJECT = 'personalized_object';
    public const NEED_TYPE_BULK_ORDER = 'bulk_order';
    public const NEED_TYPE_OTHER = 'other';

    /*
    |--------------------------------------------------------------------------
    | Constantes métier - niveau d’avancement du projet
    |--------------------------------------------------------------------------
    */

    public const PROJECT_STAGE_DISCOVERING = 'discovering';
    public const PROJECT_STAGE_IDEA_DEFINED = 'idea_defined';
    public const PROJECT_STAGE_READY_TO_ORDER = 'ready_to_order';
    public const PROJECT_STAGE_NEED_QUOTE_FAST = 'need_quote_fast';

    /*
    |--------------------------------------------------------------------------
    | Constantes métier - méthode de contact préférée
    |--------------------------------------------------------------------------
    */

    public const CONTACT_METHOD_EMAIL = 'email';
    public const CONTACT_METHOD_PHONE = 'phone';
    public const CONTACT_METHOD_EITHER = 'either';

    /*
    |--------------------------------------------------------------------------
    | Constantes métier - méthode de livraison / retrait
    |--------------------------------------------------------------------------
    */

    public const DELIVERY_METHOD_PICKUP = 'pickup';
    public const DELIVERY_METHOD_DELIVERY = 'delivery';
    public const DELIVERY_METHOD_TO_DISCUSS = 'to_discuss';

    /*
    |--------------------------------------------------------------------------
    | Constantes métier - statut admin
    |--------------------------------------------------------------------------
    */

    public const STATUS_NEW = 'new';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_WAITING_CUSTOMER = 'waiting_customer';
    public const STATUS_QUOTED = 'quoted';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    /*
    |--------------------------------------------------------------------------
    | Constantes métier - priorité admin
    |--------------------------------------------------------------------------
    */

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /*
    |--------------------------------------------------------------------------
    | Constantes métier - source de création
    |--------------------------------------------------------------------------
    */

    public const SOURCE_WEBSITE_CONTACT_FORM = 'website_contact_form';
    public const SOURCE_WEBSITE_QUOTE_FORM = 'website_quote_form';
    public const SOURCE_MANUAL_ADMIN = 'manual_admin';
    public const SOURCE_OTHER = 'other';

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
    | Référence métier lisible
    |--------------------------------------------------------------------------
    | Exemple : WR-202604-0001
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(length: 30, unique: true)]
    #[Assert\Length(
        max: 30,
        maxMessage: 'La référence ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $reference = null;

    /*
    |--------------------------------------------------------------------------
    | Type de demandeur
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le type de demandeur est obligatoire.')]
    #[Assert\Choice(
        callback: [self::class, 'getCustomerTypeChoices'],
        message: 'Le type de demandeur sélectionné est invalide.'
    )]
    private ?string $customerType = null;

    /*
    |--------------------------------------------------------------------------
    | Coordonnées principales
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Le nom complet est obligatoire.')]
    #[Assert\Length(
        min: 2,
        minMessage: 'Le nom complet doit contenir au moins {{ limit }} caractères.',
        max: 180,
        maxMessage: 'Le nom complet ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $fullName = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L’adresse e-mail est obligatoire.')]
    #[Assert\Email(message: 'L’adresse e-mail saisie n’est pas valide.')]
    #[Assert\Length(
        max: 180,
        maxMessage: 'L’adresse e-mail ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Le téléphone ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $phone = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(
        callback: [self::class, 'getPreferredContactMethodChoices'],
        message: 'La méthode de contact préférée est invalide.'
    )]
    private ?string $preferredContactMethod = null;

    /*
    |--------------------------------------------------------------------------
    | Informations structure / organisation
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(
        max: 180,
        maxMessage: 'Le nom de structure ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $companyName = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(
        max: 180,
        maxMessage: 'Le nom du contact dans la structure ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $contactPerson = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $requiresInvoice = false;

    /*
    |--------------------------------------------------------------------------
    | Nature de la demande
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le type de demande est obligatoire.')]
    #[Assert\Choice(
        callback: [self::class, 'getRequestTypeChoices'],
        message: 'Le type de demande sélectionné est invalide.'
    )]
    private ?string $requestType = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(
        callback: [self::class, 'getNeedTypeChoices'],
        message: 'Le besoin principal sélectionné est invalide.'
    )]
    private ?string $needType = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le sujet est obligatoire.')]
    #[Assert\Length(
        min: 3,
        minMessage: 'Le sujet doit contenir au moins {{ limit }} caractères.',
        max: 255,
        maxMessage: 'Le sujet ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $subject = null;

    /*
    |--------------------------------------------------------------------------
    | Détails du projet
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le message est obligatoire.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Le message doit contenir au moins {{ limit }} caractères.',
        max: 10000,
        maxMessage: 'Le message ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $message = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le type d’événement ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $eventType = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(
        max: 180,
        maxMessage: 'Le nom de l’événement ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $eventName = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $eventDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $desiredDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'Les notes d’échéance ne doivent pas dépasser {{ limit }} caractères.'
    )]
    private ?string $deadlineNotes = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(
        max: 100,
        maxMessage: 'La plage de quantité souhaitée ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $desiredQuantityRange = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'Les notes de budget ne doivent pas dépasser {{ limit }} caractères.'
    )]
    private ?string $budgetNotes = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(
        callback: [self::class, 'getDeliveryMethodChoices'],
        message: 'La méthode de livraison / retrait est invalide.'
    )]
    private ?string $deliveryMethod = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $requiresQuote = false;

    /*
    |--------------------------------------------------------------------------
    | Niveau d’avancement du projet
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(
        callback: [self::class, 'getProjectStageChoices'],
        message: 'Le niveau d’avancement du projet est invalide.'
    )]
    private ?string $projectStage = null;

    /*
    |--------------------------------------------------------------------------
    | Suivi admin
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(length: 30, options: ['default' => self::STATUS_NEW])]
    #[Assert\Choice(
        callback: [self::class, 'getStatusChoices'],
        message: 'Le statut est invalide.'
    )]
    private string $status = self::STATUS_NEW;

    #[ORM\Column(length: 20, options: ['default' => self::PRIORITY_NORMAL])]
    #[Assert\Choice(
        callback: [self::class, 'getPriorityChoices'],
        message: 'La priorité est invalide.'
    )]
    private string $priority = self::PRIORITY_NORMAL;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 10000,
        maxMessage: 'Les notes admin ne doivent pas dépasser {{ limit }} caractères.'
    )]
    private ?string $adminNotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 10000,
        maxMessage: 'Les notes client ne doivent pas dépasser {{ limit }} caractères.'
    )]
    private ?string $customerNotes = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isFlagged = false;

    /*
    |--------------------------------------------------------------------------
    | Consentement / sécurité / traçabilité
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(options: ['default' => false])]
    #[Assert\IsTrue(message: 'Le consentement RGPD est obligatoire.')]
    private bool $consentRgpd = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $consentRgpdAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Choice(
        callback: [self::class, 'getSourceChoices'],
        message: 'La source de création est invalide.'
    )]
    #[Assert\Length(
        max: 50,
        maxMessage: 'La source ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $source = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Assert\Ip(message: 'L’adresse IP n’est pas valide.')]
    #[Assert\Length(
        max: 45,
        maxMessage: 'L’adresse IP ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'Le user agent ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $userAgent = null;

    /*
    |--------------------------------------------------------------------------
    | Dates métier de suivi
    |--------------------------------------------------------------------------
    */

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $answeredAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $firstAdminReplyAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastAdminReplyAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastCustomerReplyAt = null;

    /*
    |--------------------------------------------------------------------------
    | Dates techniques
    |--------------------------------------------------------------------------
    |
    | Important :
    | le formulaire Symfony valide l'entité avant le PrePersist Doctrine.
    | On initialise donc createdAt / updatedAt dès le constructeur pour éviter
    | l'erreur "Cette valeur ne doit pas être nulle." sur un submit valide.
    |
    */

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Lignes de besoin / idées produit liées à la demande.
     */
    #[ORM\OneToMany(
        mappedBy: 'workshopRequest',
        targetEntity: WorkshopRequestItem::class,
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Assert\Valid]
    private Collection $items;

    /**
     * Pièces jointes associées à la demande.
     */
    #[ORM\OneToMany(
        mappedBy: 'workshopRequest',
        targetEntity: WorkshopRequestAttachment::class,
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    #[Assert\Valid]
    private Collection $attachments;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        // On initialise immédiatement les dates techniques
        // pour que l'entité soit déjà valide au moment du submit.
        $this->createdAt = $now;
        $this->updatedAt = $now;

        $this->items = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle callbacks Doctrine
    |--------------------------------------------------------------------------
    */
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        /*
    |----------------------------------------------------------------------
    | Date de référence unique pour cette insertion
    |----------------------------------------------------------------------
    |
    | On capture "maintenant" une seule fois pour garder une cohérence
    | parfaite entre tous les champs automatiques générés au PrePersist.
    |
    */
        $now = new \DateTimeImmutable();

        /*
    |----------------------------------------------------------------------
    | Dates techniques
    |----------------------------------------------------------------------
    */
        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }

        $this->updatedAt = $now;

        /*
    |----------------------------------------------------------------------
    | Génération automatique de la référence métier
    |----------------------------------------------------------------------
    |
    | On génère une référence lisible si elle n'a pas déjà été définie.
    | Format exemple :
    | WR-20260402-153045-482
    |
    */
        if ($this->reference === null || '' === trim($this->reference)) {
            $this->reference = 'WR-' . $now->format('Ymd-His') . '-' . random_int(100, 999);
        }

        /*
    |----------------------------------------------------------------------
    | Consentement RGPD
    |----------------------------------------------------------------------
    |
    | Si le consentement est coché mais qu'aucune date n'est encore
    | enregistrée, on mémorise la date du consentement.
    |
    */
        if ($this->consentRgpd && $this->consentRgpdAt === null) {
            $this->consentRgpdAt = $now;
        }

        /*
    |----------------------------------------------------------------------
    | Date métier de soumission
    |----------------------------------------------------------------------
    */
        if ($this->submittedAt === null) {
            $this->submittedAt = $now;
        }

        /*
    |----------------------------------------------------------------------
    | Archivage automatique si statut déjà archivé à l'insertion
    |----------------------------------------------------------------------
    */
        if ($this->status === self::STATUS_ARCHIVED && $this->archivedAt === null) {
            $this->archivedAt = $now;
        }
    }

    /**
     * Met à jour automatiquement updatedAt avant chaque modification.
     */
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $now = new \DateTimeImmutable();

        $this->updatedAt = $now;

        if ($this->consentRgpd && $this->consentRgpdAt === null) {
            $this->consentRgpdAt = $now;
        }

        if ($this->status === self::STATUS_ARCHIVED && $this->archivedAt === null) {
            $this->archivedAt = $now;
        }

        if ($this->status !== self::STATUS_ARCHIVED && $this->archivedAt !== null) {
            $this->archivedAt = null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validation métier transverse
    |--------------------------------------------------------------------------
    */

    #[Assert\Callback]
    public function validateBusinessRules(ExecutionContextInterface $context): void
    {
        /*
        |--------------------------------------------------------------------------
        | Entreprise / association => nom de structure obligatoire
        |--------------------------------------------------------------------------
        */
        if (\in_array($this->customerType, [
            self::CUSTOMER_TYPE_COMPANY,
            self::CUSTOMER_TYPE_ASSOCIATION,
        ], true)) {
            if ($this->companyName === null || '' === trim($this->companyName)) {
                $context->buildViolation('Le nom de la structure est obligatoire pour ce type de demandeur.')
                    ->atPath('companyName')
                    ->addViolation();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Contact par téléphone => téléphone obligatoire
        |--------------------------------------------------------------------------
        */
        if ($this->preferredContactMethod === self::CONTACT_METHOD_PHONE) {
            if ($this->phone === null || '' === trim($this->phone)) {
                $context->buildViolation('Le téléphone est obligatoire si ce mode de contact est choisi.')
                    ->atPath('phone')
                    ->addViolation();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Demande événementielle => date d’événement obligatoire
        |--------------------------------------------------------------------------
        */
        if ($this->requestType === self::REQUEST_TYPE_EVENT_REQUEST) {
            if ($this->eventDate === null) {
                $context->buildViolation('La date de l’événement est obligatoire pour une demande événementielle.')
                    ->atPath('eventDate')
                    ->addViolation();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Demandes avancées => au moins une ligne de besoin
        |--------------------------------------------------------------------------
        */
        if (
            \in_array($this->requestType, [
                self::REQUEST_TYPE_CUSTOM_REQUEST,
                self::REQUEST_TYPE_PROFESSIONAL_REQUEST,
                self::REQUEST_TYPE_ASSOCIATION_REQUEST,
                self::REQUEST_TYPE_EVENT_REQUEST,
                self::REQUEST_TYPE_PREORDER,
                self::REQUEST_TYPE_QUOTE_REQUEST,
            ], true)
            && $this->items->count() < 1
        ) {
            $context->buildViolation('Ajoute au moins une idée produit ou une ligne de besoin.')
                ->atPath('items')
                ->addViolation();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers statiques - listes de choix
    |--------------------------------------------------------------------------
    */

    public static function getCustomerTypeChoices(): array
    {
        return [
            self::CUSTOMER_TYPE_INDIVIDUAL,
            self::CUSTOMER_TYPE_COMPANY,
            self::CUSTOMER_TYPE_ASSOCIATION,
        ];
    }

    public static function getRequestTypeChoices(): array
    {
        return [
            self::REQUEST_TYPE_INFORMATION,
            self::REQUEST_TYPE_CUSTOM_REQUEST,
            self::REQUEST_TYPE_PROFESSIONAL_REQUEST,
            self::REQUEST_TYPE_ASSOCIATION_REQUEST,
            self::REQUEST_TYPE_EVENT_REQUEST,
            self::REQUEST_TYPE_PREORDER,
            self::REQUEST_TYPE_QUOTE_REQUEST,
        ];
    }

    public static function getNeedTypeChoices(): array
    {
        return [
            self::NEED_TYPE_GIFT,
            self::NEED_TYPE_DECORATION,
            self::NEED_TYPE_EVENT,
            self::NEED_TYPE_BUSINESS_COMMUNICATION,
            self::NEED_TYPE_PERSONALIZED_OBJECT,
            self::NEED_TYPE_BULK_ORDER,
            self::NEED_TYPE_OTHER,
        ];
    }

    public static function getProjectStageChoices(): array
    {
        return [
            self::PROJECT_STAGE_DISCOVERING,
            self::PROJECT_STAGE_IDEA_DEFINED,
            self::PROJECT_STAGE_READY_TO_ORDER,
            self::PROJECT_STAGE_NEED_QUOTE_FAST,
        ];
    }

    public static function getPreferredContactMethodChoices(): array
    {
        return [
            self::CONTACT_METHOD_EMAIL,
            self::CONTACT_METHOD_PHONE,
            self::CONTACT_METHOD_EITHER,
        ];
    }

    public static function getDeliveryMethodChoices(): array
    {
        return [
            self::DELIVERY_METHOD_PICKUP,
            self::DELIVERY_METHOD_DELIVERY,
            self::DELIVERY_METHOD_TO_DISCUSS,
        ];
    }

    public static function getStatusChoices(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_IN_REVIEW,
            self::STATUS_WAITING_CUSTOMER,
            self::STATUS_QUOTED,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
            self::STATUS_ARCHIVED,
        ];
    }

    public static function getPriorityChoices(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_NORMAL,
            self::PRIORITY_HIGH,
            self::PRIORITY_URGENT,
        ];
    }

    public static function getSourceChoices(): array
    {
        return [
            self::SOURCE_WEBSITE_CONTACT_FORM,
            self::SOURCE_WEBSITE_QUOTE_FORM,
            self::SOURCE_MANUAL_ADMIN,
            self::SOURCE_OTHER,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers métier simples
    |--------------------------------------------------------------------------
    */

    /**
     * Permet de savoir si la demande est portée par une structure.
     */
    public function isProfessionalContext(): bool
    {
        return \in_array($this->customerType, [
            self::CUSTOMER_TYPE_COMPANY,
            self::CUSTOMER_TYPE_ASSOCIATION,
        ], true);
    }

    /**
     * Indique si la demande nécessite une ou plusieurs lignes détaillées.
     */
    public function requiresDetailedItems(): bool
    {
        return \in_array($this->requestType, [
            self::REQUEST_TYPE_CUSTOM_REQUEST,
            self::REQUEST_TYPE_PROFESSIONAL_REQUEST,
            self::REQUEST_TYPE_ASSOCIATION_REQUEST,
            self::REQUEST_TYPE_EVENT_REQUEST,
            self::REQUEST_TYPE_PREORDER,
            self::REQUEST_TYPE_QUOTE_REQUEST,
        ], true);
    }

    /**
     * Marque la demande comme lue.
     */
    public function markAsRead(): self
    {
        $this->isRead = true;
        $this->touch();

        return $this;
    }

    /**
     * Marque la demande comme non lue.
     */
    public function markAsUnread(): self
    {
        $this->isRead = false;
        $this->touch();

        return $this;
    }

    /**
     * Archive la demande.
     */
    public function archive(): self
    {
        $this->status = self::STATUS_ARCHIVED;
        $this->archivedAt = new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    /**
     * Met à jour manuellement la date de modification.
     */
    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
        $this->touch();

        return $this;
    }

    public function getCustomerType(): ?string
    {
        return $this->customerType;
    }

    public function setCustomerType(?string $customerType): static
    {
        $this->customerType = $customerType;
        $this->touch();

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;
        $this->touch();

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        $this->touch();

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        $this->touch();

        return $this;
    }

    public function getPreferredContactMethod(): ?string
    {
        return $this->preferredContactMethod;
    }

    public function setPreferredContactMethod(?string $preferredContactMethod): static
    {
        $this->preferredContactMethod = $preferredContactMethod;
        $this->touch();

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;
        $this->touch();

        return $this;
    }

    public function getContactPerson(): ?string
    {
        return $this->contactPerson;
    }

    public function setContactPerson(?string $contactPerson): static
    {
        $this->contactPerson = $contactPerson;
        $this->touch();

        return $this;
    }

    public function isRequiresInvoice(): bool
    {
        return $this->requiresInvoice;
    }

    public function setRequiresInvoice(bool $requiresInvoice): static
    {
        $this->requiresInvoice = $requiresInvoice;
        $this->touch();

        return $this;
    }

    public function getRequestType(): ?string
    {
        return $this->requestType;
    }

    public function setRequestType(?string $requestType): static
    {
        $this->requestType = $requestType;
        $this->touch();

        return $this;
    }

    public function getNeedType(): ?string
    {
        return $this->needType;
    }

    public function setNeedType(?string $needType): static
    {
        $this->needType = $needType;
        $this->touch();

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;
        $this->touch();

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        $this->touch();

        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(?string $eventType): static
    {
        $this->eventType = $eventType;
        $this->touch();

        return $this;
    }

    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    public function setEventName(?string $eventName): static
    {
        $this->eventName = $eventName;
        $this->touch();

        return $this;
    }

    public function getEventDate(): ?\DateTimeImmutable
    {
        return $this->eventDate;
    }

    public function setEventDate(?\DateTimeImmutable $eventDate): static
    {
        $this->eventDate = $eventDate;
        $this->touch();

        return $this;
    }

    public function getDesiredDate(): ?\DateTimeImmutable
    {
        return $this->desiredDate;
    }

    public function setDesiredDate(?\DateTimeImmutable $desiredDate): static
    {
        $this->desiredDate = $desiredDate;
        $this->touch();

        return $this;
    }

    public function getDeadlineNotes(): ?string
    {
        return $this->deadlineNotes;
    }

    public function setDeadlineNotes(?string $deadlineNotes): static
    {
        $this->deadlineNotes = $deadlineNotes;
        $this->touch();

        return $this;
    }

    public function getDesiredQuantityRange(): ?string
    {
        return $this->desiredQuantityRange;
    }

    public function setDesiredQuantityRange(?string $desiredQuantityRange): static
    {
        $this->desiredQuantityRange = $desiredQuantityRange;
        $this->touch();

        return $this;
    }

    public function getBudgetNotes(): ?string
    {
        return $this->budgetNotes;
    }

    public function setBudgetNotes(?string $budgetNotes): static
    {
        $this->budgetNotes = $budgetNotes;
        $this->touch();

        return $this;
    }

    public function getDeliveryMethod(): ?string
    {
        return $this->deliveryMethod;
    }

    public function setDeliveryMethod(?string $deliveryMethod): static
    {
        $this->deliveryMethod = $deliveryMethod;
        $this->touch();

        return $this;
    }

    public function isRequiresQuote(): bool
    {
        return $this->requiresQuote;
    }

    public function setRequiresQuote(bool $requiresQuote): static
    {
        $this->requiresQuote = $requiresQuote;
        $this->touch();

        return $this;
    }

    public function getProjectStage(): ?string
    {
        return $this->projectStage;
    }

    public function setProjectStage(?string $projectStage): static
    {
        $this->projectStage = $projectStage;
        $this->touch();

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        if ($status === self::STATUS_ARCHIVED && $this->archivedAt === null) {
            $this->archivedAt = new \DateTimeImmutable();
        }

        if ($status !== self::STATUS_ARCHIVED && $this->archivedAt !== null) {
            $this->archivedAt = null;
        }

        $this->touch();

        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
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

    public function getCustomerNotes(): ?string
    {
        return $this->customerNotes;
    }

    public function setCustomerNotes(?string $customerNotes): static
    {
        $this->customerNotes = $customerNotes;
        $this->touch();

        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        $this->touch();

        return $this;
    }

    public function isFlagged(): bool
    {
        return $this->isFlagged;
    }

    public function setIsFlagged(bool $isFlagged): static
    {
        $this->isFlagged = $isFlagged;
        $this->touch();

        return $this;
    }

    public function isConsentRgpd(): bool
    {
        return $this->consentRgpd;
    }

    public function setConsentRgpd(bool $consentRgpd): static
    {
        $this->consentRgpd = $consentRgpd;

        if ($consentRgpd && $this->consentRgpdAt === null) {
            $this->consentRgpdAt = new \DateTimeImmutable();
        }

        if (!$consentRgpd) {
            $this->consentRgpdAt = null;
        }

        $this->touch();

        return $this;
    }

    public function getConsentRgpdAt(): ?\DateTimeImmutable
    {
        return $this->consentRgpdAt;
    }

    public function setConsentRgpdAt(?\DateTimeImmutable $consentRgpdAt): static
    {
        $this->consentRgpdAt = $consentRgpdAt;
        $this->touch();

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;
        $this->touch();

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        $this->touch();

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        $this->touch();

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;
        $this->touch();

        return $this;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;
        $this->touch();

        return $this;
    }

    public function getAnsweredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function setAnsweredAt(?\DateTimeImmutable $answeredAt): static
    {
        $this->answeredAt = $answeredAt;
        $this->touch();

        return $this;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTimeImmutable $archivedAt): static
    {
        $this->archivedAt = $archivedAt;
        $this->touch();

        return $this;
    }

    public function getFirstAdminReplyAt(): ?\DateTimeImmutable
    {
        return $this->firstAdminReplyAt;
    }

    public function setFirstAdminReplyAt(?\DateTimeImmutable $firstAdminReplyAt): static
    {
        $this->firstAdminReplyAt = $firstAdminReplyAt;
        $this->touch();

        return $this;
    }

    public function getLastAdminReplyAt(): ?\DateTimeImmutable
    {
        return $this->lastAdminReplyAt;
    }

    public function setLastAdminReplyAt(?\DateTimeImmutable $lastAdminReplyAt): static
    {
        $this->lastAdminReplyAt = $lastAdminReplyAt;
        $this->touch();

        return $this;
    }

    public function getLastCustomerReplyAt(): ?\DateTimeImmutable
    {
        return $this->lastCustomerReplyAt;
    }

    public function setLastCustomerReplyAt(?\DateTimeImmutable $lastCustomerReplyAt): static
    {
        $this->lastCustomerReplyAt = $lastCustomerReplyAt;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Setter disponible si on veut forcer une date depuis un import / script.
     * On ne fait pas de touch() ici pour éviter d'altérer updatedAt inutilement.
     */
    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Setter technique. Pas de touch() ici pour éviter une boucle logique.
     */
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, WorkshopRequestItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(WorkshopRequestItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setWorkshopRequest($this);
            $this->touch();
        }

        return $this;
    }

    public function removeItem(WorkshopRequestItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // Important :
            // si la relation côté enfant est non nullable, on évite de faire
            // setWorkshopRequest(null) ici. Doctrine gérera la suppression
            // grâce à orphanRemoval.
            $this->touch();
        }

        return $this;
    }

    /**
     * @return Collection<int, WorkshopRequestAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(WorkshopRequestAttachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setWorkshopRequest($this);
            $this->touch();
        }

        return $this;
    }

    public function removeAttachment(WorkshopRequestAttachment $attachment): static
    {
        if ($this->attachments->removeElement($attachment)) {
            // Même logique que pour les items :
            // si la relation enfant -> parent est non nullable,
            // on ne remet pas la relation à null ici.
            $this->touch();
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->reference ?? sprintf('Demande #%s', $this->id ?? 'new');
    }
}
