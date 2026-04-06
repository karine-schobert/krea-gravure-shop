<?php

namespace App\Entity;

use App\Repository\ShipmentRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShipmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Shipment
{
    public const STATUS_PREPARING = 'PREPARING';
    public const STATUS_READY_TO_SHIP = 'READY_TO_SHIP';
    public const STATUS_SHIPPED = 'SHIPPED';
    public const STATUS_IN_TRANSIT = 'IN_TRANSIT';
    public const STATUS_OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_DELIVERY_ISSUE = 'DELIVERY_ISSUE';
    public const STATUS_RETURNED = 'RETURNED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const CARRIER_COLISSIMO = 'COLISSIMO';
    public const CARRIER_CHRONOPOST = 'CHRONOPOST';
    public const CARRIER_LETTER_FOLLOWED = 'LETTER_FOLLOWED';
    public const CARRIER_MONDIAL_RELAY = 'MONDIAL_RELAY';
    public const CARRIER_OTHER = 'OTHER';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Commande liée à cette expédition.
     *
     * On part sur un modèle simple :
     * une commande = zéro ou une expédition.
     *
     * Plus tard, si besoin, cette structure pourra évoluer
     * vers plusieurs expéditions par commande.
     */
    #[ORM\OneToOne(inversedBy: 'shipment')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    /**
     * Statut logistique interne.
     *
     * Ce statut sert à piloter la timeline côté front
     * sans dépendre directement des codes bruts d’un transporteur.
     */
    #[ORM\Column(length: 50)]
    private string $logisticStatus = self::STATUS_PREPARING;

    /**
     * Transporteur utilisé.
     *
     * Exemples :
     * - COLISSIMO
     * - CHRONOPOST
     * - LETTER_FOLLOWED
     * - MONDIAL_RELAY
     * - OTHER
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $carrier = null;

    /**
     * Numéro de suivi fourni par le transporteur.
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $trackingNumber = null;

    /**
     * URL publique de suivi.
     *
     * Optionnelle, utile pour afficher un bouton
     * "Suivre mon colis" côté compte client.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackingUrl = null;

    /**
     * Date d’expédition réelle.
     */
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $shippedAt = null;

    /**
     * Date estimée de livraison.
     */
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $estimatedDeliveryAt = null;

    /**
     * Date de livraison réelle.
     */
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $deliveredAt = null;

    /**
     * Date de dernière synchronisation avec un service externe
     * de suivi transporteur.
     */
    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $lastTrackingSyncAt = null;

    /**
     * Donnée brute renvoyée par une API externe.
     *
     * On la garde pour debug, audit ou futur mapping.
     * On peut stocker ici du JSON brut sous forme texte.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $trackingRawPayload = null;

    /**
     * Date de création de l’expédition.
     */
    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière mise à jour de l’expédition.
     */
    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->logisticStatus = self::STATUS_PREPARING;
    }
    /**
     * Initialisation automatique des dates à la création.
     */
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    /**
     * Mise à jour automatique de la date de modification.
     */
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Représentation texte utile dans EasyAdmin
     * ou dans certaines listes Doctrine.
     */
    public function __toString(): string
    {
        return sprintf(
            'Expédition #%d - %s',
            $this->id ?? 0,
            $this->trackingNumber ?? $this->logisticStatus
        );
    }



    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne la commande liée à cette expédition.
     */
    public function getOrder(): ?Order
    {
        return $this->order;
    }

    /**
     * Lie l’expédition à une commande.
     *
     * On synchronise aussi la relation inverse
     * pour garder un objet cohérent en mémoire.
     */
    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        if ($order !== null && $order->getShipment() !== $this) {
            $order->setShipment($this);
        }

        return $this;
    }

    /**
     * Retourne le statut logistique interne.
     */
    public function getLogisticStatus(): string
    {
        return $this->logisticStatus;
    }

    public function setLogisticStatus(string $logisticStatus): static
    {
        $this->logisticStatus = $logisticStatus;

        return $this;
    }

    /**
     * Retourne le transporteur utilisé.
     */
    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(?string $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }

    /**
     * Retourne le numéro de suivi.
     */
    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    /**
     * Retourne l’URL publique de suivi.
     */
    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    public function setTrackingUrl(?string $trackingUrl): static
    {
        $this->trackingUrl = $trackingUrl;

        return $this;
    }

    /**
     * Retourne la date d’expédition réelle.
     */
    public function getShippedAt(): ?DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function setShippedAt(?DateTimeImmutable $shippedAt): static
    {
        $this->shippedAt = $shippedAt;

        return $this;
    }

    /**
     * Retourne la date estimée de livraison.
     */
    public function getEstimatedDeliveryAt(): ?DateTimeImmutable
    {
        return $this->estimatedDeliveryAt;
    }

    public function setEstimatedDeliveryAt(?DateTimeImmutable $estimatedDeliveryAt): static
    {
        $this->estimatedDeliveryAt = $estimatedDeliveryAt;

        return $this;
    }

    /**
     * Retourne la date de livraison réelle.
     */
    public function getDeliveredAt(): ?DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;

        return $this;
    }

    /**
     * Retourne la date de dernière synchro externe.
     */
    public function getLastTrackingSyncAt(): ?DateTimeImmutable
    {
        return $this->lastTrackingSyncAt;
    }

    public function setLastTrackingSyncAt(?DateTimeImmutable $lastTrackingSyncAt): static
    {
        $this->lastTrackingSyncAt = $lastTrackingSyncAt;

        return $this;
    }

    /**
     * Retourne la charge brute de suivi externe.
     */
    public function getTrackingRawPayload(): ?string
    {
        return $this->trackingRawPayload;
    }

    public function setTrackingRawPayload(?string $trackingRawPayload): static
    {
        $this->trackingRawPayload = $trackingRawPayload;

        return $this;
    }

    /**
     * Retourne la date de création.
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Retourne la date de dernière mise à jour.
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
