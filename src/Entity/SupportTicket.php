<?php

namespace App\Entity;

use App\Repository\SupportTicketRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: SupportTicketRepository::class)]
class SupportTicket
{
    public const CATEGORY_NOT_RECEIVED = 'not_received';
    public const CATEGORY_LATE_DELIVERY = 'late_delivery';
    public const CATEGORY_DAMAGED_PRODUCT = 'damaged_product';
    public const CATEGORY_WRONG_PRODUCT = 'wrong_product';
    public const CATEGORY_ORDER_ERROR = 'order_error';
    public const CATEGORY_MISSING_ITEM = 'missing_item';
    public const CATEGORY_CUSTOMIZATION_PROBLEM = 'customization_problem';
    public const CATEGORY_ORDER_MODIFICATION = 'order_modification';
    public const CATEGORY_ORDER_CANCELLATION = 'order_cancellation';
    public const CATEGORY_REFUND_REQUEST = 'refund_request';
    public const CATEGORY_PAYMENT_PROBLEM = 'payment_problem';
    public const CATEGORY_OTHER = 'other';

    public const CATEGORIES = [
        self::CATEGORY_NOT_RECEIVED => 'Commande non reçue',
        self::CATEGORY_LATE_DELIVERY => 'Retard de livraison',
        self::CATEGORY_DAMAGED_PRODUCT => 'Produit abîmé à la réception',
        self::CATEGORY_WRONG_PRODUCT => 'Produit non conforme',
        self::CATEGORY_ORDER_ERROR => 'Erreur dans la commande',
        self::CATEGORY_MISSING_ITEM => 'Article manquant',
        self::CATEGORY_CUSTOMIZATION_PROBLEM => 'Problème de personnalisation',
        self::CATEGORY_ORDER_MODIFICATION => 'Demande de modification de commande',
        self::CATEGORY_ORDER_CANCELLATION => 'Demande d’annulation',
        self::CATEGORY_REFUND_REQUEST => 'Demande de remboursement',
        self::CATEGORY_PAYMENT_PROBLEM => 'Problème de paiement',
        self::CATEGORY_OTHER => 'Autre',
    ];

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_ANSWERED = 'ANSWERED';
    public const STATUS_RESOLVED = 'RESOLVED';
    public const STATUS_CLOSED = 'CLOSED';

    public const STATUSES = [
        self::STATUS_OPEN => 'Ouvert',
        self::STATUS_IN_PROGRESS => 'En cours',
        self::STATUS_ANSWERED => 'Répondu',
        self::STATUS_RESOLVED => 'Résolu',
        self::STATUS_CLOSED => 'Fermé',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $category = null;

    /**
     * Utilisateur qui crée le ticket
     */
    #[ORM\ManyToOne(inversedBy: 'supportTickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * Commande concernée
     */
    #[ORM\ManyToOne(inversedBy: 'supportTickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    /**
     * Sujet du problème
     */
    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    /**
     * Message détaillé du client
     */
    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    /**
     * Statut du ticket
     */
    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_OPEN;

    /**
     * Réponse de l'administrateur
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminReply = null;

    /**
     * Code temporaire du modèle de réponse choisi dans l’admin.
     *
     * Important :
     * - cette propriété n'est PAS persistée en base
     * - il n'y a donc volontairement aucune annotation #[ORM\Column]
     * - elle sert uniquement à EasyAdmin pour stocker temporairement
     *   le choix d’un template de réponse
     * - ensuite, le CRUD l’utilise pour remplir adminReply
     */
    private ?string $replyTemplateCode = null;

    /**
     * Date de création du ticket
     */
    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière modification
     */
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * Date de première réponse admin
     */
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $answeredAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->status = self::STATUS_OPEN;
    }

    public static function getAvailableStatuses(): array
    {
        return array_flip(self::STATUSES);
    }

    public static function getAvailableCategories(): array
    {
        return array_flip(self::CATEGORIES);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getCategoryLabel(): ?string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getAdminReply(): ?string
    {
        return $this->adminReply;
    }

    public function setAdminReply(?string $adminReply): static
    {
        $this->adminReply = $adminReply;

        return $this;
    }

    /**
     * Retourne le code temporaire du template sélectionné dans l’admin.
     */
    public function getReplyTemplateCode(): ?string
    {
        return $this->replyTemplateCode;
    }

    /**
     * Définit le code temporaire du template sélectionné dans l’admin.
     *
     * Exemple de valeur attendue :
     * "damaged_product::request_photos"
     */
    public function setReplyTemplateCode(?string $replyTemplateCode): static
    {
        $this->replyTemplateCode = $replyTemplateCode;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAnsweredAt(): ?DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function setAnsweredAt(?DateTimeImmutable $answeredAt): static
    {
        $this->answeredAt = $answeredAt;

        return $this;
    }
}