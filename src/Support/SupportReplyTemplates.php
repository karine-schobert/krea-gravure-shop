<?php

namespace App\Support;

/**
 * Cette classe centralise les réponses types SAV.
 *
 * Objectif :
 * - éviter de réécrire toujours les mêmes réponses
 * - garder une base cohérente pour le support client
 * - permettre plus tard un préremplissage dans EasyAdmin
 *
 * Structure :
 * - 1er niveau  : catégorie du ticket
 * - 2e niveau   : code du modèle de réponse
 * - title       : nom lisible dans l’admin
 * - content     : texte de réponse type
 *
 * Important :
 * - les textes restent génériques
 * - l’admin peut ensuite les adapter selon le cas réel
 * - on pourra plus tard remplacer certains placeholders
 *   comme {firstName}, {orderId}, etc.
 */
class SupportReplyTemplates
{
    /**
     * Tableau principal des modèles de réponse.
     */
    public const TEMPLATES = [

        /**
         * Commande non reçue
         */
        'not_received' => [
            'verification' => [
                'title' => 'Vérification de livraison',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous sommes désolés d’apprendre que vous n’avez pas encore reçu votre commande.\nNous allons vérifier la situation avec votre numéro de commande et revenir vers vous rapidement.\n\nMerci pour votre patience.\n\nCordialement,\nLe service client",
            ],
            'carrier_delay' => [
                'title' => 'Retard transporteur',
                'content' => "Bonjour,\n\nMerci pour votre message.\nAprès vérification, votre commande semble actuellement en cours d’acheminement avec un retard de livraison.\nNous vous invitons à patienter encore un peu et restons disponibles si la situation n’évolue pas.\n\nCordialement,\nLe service client",
            ],
            'replacement_or_refund' => [
                'title' => 'Solution proposée',
                'content' => "Bonjour,\n\nMerci pour votre retour.\nAprès vérification, nous constatons un problème de livraison sur votre commande.\nNous pouvons vous proposer une solution adaptée, selon le cas : réexpédition, remboursement ou autre prise en charge.\nNous revenons vers vous rapidement pour finaliser cela.\n\nCordialement,\nLe service client",
            ],
        ],

        /**
         * Retard de livraison
         */
        'late_delivery' => [
            'delay_acknowledged' => [
                'title' => 'Retard confirmé',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous sommes désolés pour ce retard de livraison.\nNous comprenons votre gêne et faisons le nécessaire pour suivre votre commande au plus vite.\n\nNous revenons vers vous dès que nous avons plus d’informations.\n\nCordialement,\nLe service client",
            ],
            'in_preparation' => [
                'title' => 'Commande en préparation',
                'content' => "Bonjour,\n\nMerci pour votre message.\nVotre commande est actuellement en préparation / finalisation.\nLe délai est un peu plus long que prévu, et nous vous prions de nous en excuser.\n\nNous faisons au plus vite pour son expédition.\n\nCordialement,\nLe service client",
            ],
            'shipment_soon' => [
                'title' => 'Expédition imminente',
                'content' => "Bonjour,\n\nMerci pour votre message.\nVotre commande va être expédiée très prochainement.\nNous sommes désolés pour ce délai supplémentaire et vous remercions pour votre patience.\n\nCordialement,\nLe service client",
            ],
        ],

        /**
         * Produit abîmé à la réception
         */
        'damaged_product' => [
            'request_photos' => [
                'title' => 'Demande de photos',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous sommes désolés d’apprendre que votre produit est arrivé abîmé.\nAfin de vérifier la situation rapidement, pouvez-vous nous envoyer une ou plusieurs photos du produit reçu ainsi que, si possible, de l’emballage ?\n\nDès réception, nous vous proposerons une solution adaptée.\n\nCordialement,\nLe service client",
            ],
            'damage_confirmed' => [
                'title' => 'Dommage confirmé',
                'content' => "Bonjour,\n\nMerci pour votre retour.\nAprès vérification, nous confirmons que le produit a subi un dommage.\nNous sommes désolés pour ce désagrément et allons vous proposer une solution rapide : remplacement, refabrication ou autre prise en charge adaptée.\n\nCordialement,\nLe service client",
            ],
            'commercial_solution' => [
                'title' => 'Solution commerciale',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous sommes désolés pour ce problème rencontré à la réception de votre commande.\nNous vous proposons une solution commerciale adaptée à la situation et revenons vers vous pour la finaliser.\n\nCordialement,\nLe service client",
            ],
        ],

        /**
         * Produit non conforme / mauvais produit
         */
        'wrong_product' => [
            'verification' => [
                'title' => 'Vérification produit reçu',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous sommes désolés si le produit reçu ne correspond pas à vos attentes ou à votre commande.\nAfin de vérifier précisément la situation, pouvez-vous nous confirmer le produit attendu et, si possible, nous envoyer une photo du produit reçu ?\n\nCordialement,\nLe service client",
            ],
            'error_confirmed' => [
                'title' => 'Erreur confirmée',
                'content' => "Bonjour,\n\nMerci pour votre retour.\nAprès vérification, nous confirmons qu’il y a bien une erreur sur le produit envoyé.\nNous vous prions de nous excuser et allons mettre en place une solution adaptée dans les meilleurs délais.\n\nCordialement,\nLe service client",
            ],
            'solution_sent' => [
                'title' => 'Solution mise en place',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous avons bien pris en compte votre signalement et mettons en place une solution pour corriger cette erreur.\nNous revenons vers vous rapidement avec la suite de la procédure.\n\nCordialement,\nLe service client",
            ],
        ],

        /**
         * Erreur dans la commande
         */
        'order_error' => [
            'details_request' => [
                'title' => 'Demande de précisions',
                'content' => "Bonjour,\n\nMerci pour votre message.\nAfin de vérifier précisément l’erreur signalée sur votre commande, pouvez-vous nous indiquer les éléments concernés ?\nNous étudierons la situation rapidement.\n\nCordialement,\nLe service client",
            ],
            'error_confirmed' => [
                'title' => 'Erreur confirmée',
                'content' => "Bonjour,\n\nMerci pour votre retour.\nAprès vérification, nous confirmons qu’une erreur est présente sur votre commande.\nNous sommes désolés pour ce désagrément et allons vous proposer une solution adaptée.\n\nCordialement,\nLe service client",
            ],
            'correction_in_progress' => [
                'title' => 'Correction en cours',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous avons bien pris en compte votre signalement et la correction est en cours de traitement.\nNous vous tiendrons informé(e) dès que possible.\n\nCordialement,\nLe service client",
            ],
        ],

        /**
         * Article manquant
         */
        'missing_item' => [
            'verification' => [
                'title' => 'Vérification article manquant',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous sommes désolés d’apprendre qu’un article semble manquer dans votre commande.\nNous allons vérifier la préparation et revenir vers vous rapidement.\n\nCordialement,\nLe service client",
            ],
            'missing_confirmed' => [
                'title' => 'Article manquant confirmé',
                'content' => "Bonjour,\n\nMerci pour votre retour.\nAprès vérification, nous confirmons qu’un article est manquant dans votre commande.\nNous vous prions de nous excuser et allons mettre en place une solution adaptée.\n\nCordialement,\nLe service client",
            ],
            'shipment_followup' => [
                'title' => 'Envoi complémentaire',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous avons bien pris en compte votre demande et revenons vers vous avec la suite concernant l’article manquant.\n\nCordialement,\nLe service client",
            ],
        ],

        /**
         * Problème de personnalisation
         */
        'customization_problem' => [
            'verification' => [
                'title' => 'Demande de vérification',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous sommes désolés pour ce souci de personnalisation.\nAfin de vérifier précisément la situation, pouvez-vous nous envoyer une photo du produit reçu ainsi que nous confirmer la personnalisation attendue ?\n\nDès réception de ces éléments, nous reviendrons vers vous rapidement avec une solution.\n\nCordialement,\nLe service client",
            ],
            'atelier_error' => [
                'title' => 'Erreur confirmée de personnalisation',
                'content' => "Bonjour,\n\nMerci pour votre retour.\nAprès vérification, nous confirmons qu’il y a bien une erreur sur la personnalisation réalisée.\nNous sommes désolés pour ce désagrément et allons vous proposer une solution adaptée dans les meilleurs délais.\n\nCordialement,\nLe service client",
            ],
            'matches_order' => [
                'title' => 'Personnalisation conforme à la commande',
                'content' => "Bonjour,\n\nMerci pour votre message.\nAprès vérification, la personnalisation réalisée correspond aux informations enregistrées lors de la commande.\nNous comprenons toutefois votre déception et restons disponibles pour voir avec vous une solution commerciale adaptée si nécessaire.\n\nCordialement,\nLe service client",
            ],
        ],

        /**
         * Demande de modification de commande
         */
        'order_modification' => [
            'request_received' => [
                'title' => 'Demande reçue',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous avons bien reçu votre demande de modification de commande.\nNous allons vérifier si celle-ci est encore possible selon l’état d’avancement de votre commande.\n\nNous revenons vers vous rapidement.\n\nCordialement,\nLe service client",
            ],
            'modification_possible' => [
                'title' => 'Modification possible',
                'content' => "Bonjour,\n\nMerci pour votre message.\nAprès vérification, votre demande de modification peut encore être prise en compte.\nNous allons procéder à l’ajustement demandé.\n\nCordialement,\nLe service client",
            ],
            'modification_not_possible' => [
                'title' => 'Modification impossible',
                'content' => "Bonjour,\n\nMerci pour votre message.\nAprès vérification, votre commande est déjà trop avancée dans le traitement pour permettre une modification.\nNous sommes désolés de ne pas pouvoir accéder à votre demande.\n\nCordialement,\nLe service client",
            ],
        ],

        /**
         * Demande d’annulation
         */
        'order_cancellation' => [
            'request_received' => [
                'title' => 'Demande d’annulation reçue',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous avons bien reçu votre demande d’annulation.\nNous allons vérifier si votre commande peut encore être annulée selon son état d’avancement.\n\nNous revenons vers vous rapidement.\n\nCordialement,\nLe service client",
            ],
            'cancellation_possible' => [
                'title' => 'Annulation acceptée',
                'content' => "Bonjour,\n\nMerci pour votre message.\nAprès vérification, votre commande peut encore être annulée.\nNous procédons à la suite du traitement selon les conditions applicables.\n\nCordialement,\nLe service client",
            ],
            'cancellation_not_possible' => [
                'title' => 'Annulation impossible',
                'content' => "Bonjour,\n\nMerci pour votre message.\nAprès vérification, votre commande est déjà trop avancée dans le traitement pour être annulée.\nNous sommes désolés de ne pas pouvoir répondre favorablement à votre demande.\n\nCordialement,\nLe service client",
            ],
        ],

        /**
         * Demande de remboursement
         */
        'refund_request' => [
            'request_received' => [
                'title' => 'Demande de remboursement reçue',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous avons bien reçu votre demande de remboursement.\nNous allons étudier votre dossier et revenir vers vous dès que possible.\n\nCordialement,\nLe service client",
            ],
            'refund_accepted' => [
                'title' => 'Remboursement accepté',
                'content' => "Bonjour,\n\nMerci pour votre retour.\nAprès vérification, votre demande de remboursement a bien été prise en compte.\nLe traitement va être effectué selon les délais habituels.\n\nCordialement,\nLe service client",
            ],
            'refund_refused' => [
                'title' => 'Remboursement refusé',
                'content' => "Bonjour,\n\nMerci pour votre message.\nAprès étude de votre demande, nous ne pouvons malheureusement pas donner une suite favorable au remboursement.\nNous restons toutefois disponibles pour échanger avec vous si besoin.\n\nCordialement,\nLe service client",
            ],
        ],

        /**
         * Problème de paiement
         */
        'payment_problem' => [
            'details_request' => [
                'title' => 'Demande de précisions paiement',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous sommes désolés pour ce problème de paiement.\nPouvez-vous nous préciser ce qui s’est affiché lors de la tentative de commande afin que nous puissions vérifier la situation ?\n\nCordialement,\nLe service client",
            ],
            'payment_check' => [
                'title' => 'Vérification en cours',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous avons bien pris en compte votre signalement concernant le paiement et vérifions actuellement la situation.\nNous revenons vers vous rapidement.\n\nCordialement,\nLe service client",
            ],
            'payment_resolved' => [
                'title' => 'Problème résolu',
                'content' => "Bonjour,\n\nMerci pour votre message.\nLe problème signalé semble désormais identifié / résolu.\nNous restons disponibles si vous souhaitez effectuer un nouvel essai ou si vous rencontrez encore une difficulté.\n\nCordialement,\nLe service client",
            ],
        ],

        /**
         * Autre
         */
        'other' => [
            'generic_acknowledgement' => [
                'title' => 'Accusé de réception',
                'content' => "Bonjour,\n\nMerci pour votre message.\nNous avons bien pris en compte votre demande et allons l’étudier avec attention.\nNous reviendrons vers vous dès que possible.\n\nCordialement,\nLe service client",
            ],
            'details_request' => [
                'title' => 'Demande de précisions',
                'content' => "Bonjour,\n\nMerci pour votre message.\nAfin de pouvoir vous répondre au mieux, pouvez-vous nous apporter quelques précisions complémentaires sur votre demande ?\n\nCordialement,\nLe service client",
            ],
        ],
    ];

    /**
     * Retourne tous les modèles.
     */
    public static function all(): array
    {
        return self::TEMPLATES;
    }

    /**
     * Retourne les modèles d'une catégorie.
     */
    public static function forCategory(string $category): array
    {
        return self::TEMPLATES[$category] ?? [];
    }

    /**
     * Retourne un modèle précis à partir de la catégorie
     * et du code du template.
     */
    public static function get(string $category, string $templateCode): ?array
    {
        return self::TEMPLATES[$category][$templateCode] ?? null;
    }

    /**
     * Retourne uniquement le contenu texte d’un modèle.
     */
    public static function getContent(string $category, string $templateCode): ?string
    {
        return self::TEMPLATES[$category][$templateCode]['content'] ?? null;
    }

    /**
     * Retourne uniquement le titre d’un modèle.
     */
    public static function getTitle(string $category, string $templateCode): ?string
    {
        return self::TEMPLATES[$category][$templateCode]['title'] ?? null;
    }
}