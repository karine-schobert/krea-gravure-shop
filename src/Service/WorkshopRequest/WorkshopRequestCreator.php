<?php

namespace App\Service\WorkshopRequest;

use App\Entity\WorkshopRequest;
use App\Entity\WorkshopRequestAttachment;
use App\Entity\WorkshopRequestItem;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service central de création d'une demande atelier.
 *
 * Objectif :
 * - centraliser toute la logique métier de création
 * - éviter de dupliquer cette logique entre le contrôleur Twig de test
 *   et le contrôleur API
 * - garder un point d'entrée unique, propre et maintenable
 */
class WorkshopRequestCreator
{
    /**
     * Liste blanche des types MIME autorisés en V1.
     */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly string $workshopRequestUploadDir
    ) {
    }

    /**
     * Crée une demande atelier complète à partir d'un payload normalisé.
     *
     * Payload attendu :
     * - champs simples de la demande
     * - items déjà décodé en tableau PHP
     *
     * Fichiers :
     * - tableau de UploadedFile transmis séparément
     *
     * Retour :
     * - succès :
     *   [
     *     'success' => true,
     *     'workshopRequest' => WorkshopRequest
     *   ]
     *
     * - erreur :
     *   [
     *     'success' => false,
     *     'errors' => [
     *         'field' => ['message 1', 'message 2']
     *     ]
     *   ]
     */
    public function createFromPayload(array $payload, array $uploadedFiles = []): array
    {
        /*
        |------------------------------------------------------------------
        | Pré-validation légère du payload API
        |------------------------------------------------------------------
        |
        | Ici on contrôle surtout ce que la validation Symfony ne peut pas
        | déduire proprement toute seule :
        | - items doit être un tableau
        |
        */
        $preValidationErrors = [];

        $itemsPayload = $payload['items'] ?? [];

        if (!is_array($itemsPayload)) {
            $preValidationErrors['items'][] = 'Le champ items doit être un tableau JSON valide.';
        }

        if (!empty($preValidationErrors)) {
            return [
                'success' => false,
                'errors' => $preValidationErrors,
            ];
        }

        /*
        |------------------------------------------------------------------
        | Création de l'entité principale
        |------------------------------------------------------------------
        */
        $workshopRequest = new WorkshopRequest();

        /*
        |------------------------------------------------------------------
        | Valeurs métier par défaut
        |------------------------------------------------------------------
        */
        $workshopRequest
            ->setStatus(WorkshopRequest::STATUS_NEW)
            ->setPriority(WorkshopRequest::PRIORITY_NORMAL)
            ->setSource(WorkshopRequest::SOURCE_WEBSITE_CONTACT_FORM);

        /*
        |------------------------------------------------------------------
        | Hydratation des champs principaux
        |------------------------------------------------------------------
        */
        $this->hydrateMainRequest($workshopRequest, $payload);

        /*
        |------------------------------------------------------------------
        | Hydratation des lignes métier
        |------------------------------------------------------------------
        */
        $itemErrors = $this->hydrateItems($workshopRequest, $itemsPayload);

        /*
        |------------------------------------------------------------------
        | Hydratation des pièces jointes
        |------------------------------------------------------------------
        */
        $attachmentErrors = $this->hydrateAttachments($workshopRequest, $uploadedFiles);

        /*
        |------------------------------------------------------------------
        | Retour immédiat si erreurs techniques/métier détectées en amont
        |------------------------------------------------------------------
        */
        $errors = $this->mergeErrors($itemErrors, $attachmentErrors);

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        /*
        |------------------------------------------------------------------
        | Validation Symfony complète
        |------------------------------------------------------------------
        */
        $violations = $this->validator->validate($workshopRequest);

        if (count($violations) > 0) {
            return [
                'success' => false,
                'errors' => $this->formatViolations($violations),
            ];
        }

        /*
        |------------------------------------------------------------------
        | Persistance
        |------------------------------------------------------------------
        */
        $this->entityManager->persist($workshopRequest);
        $this->entityManager->flush();

        return [
            'success' => true,
            'workshopRequest' => $workshopRequest,
        ];
    }

    /**
     * Hydrate les champs principaux de la demande.
     */
    private function hydrateMainRequest(WorkshopRequest $workshopRequest, array $payload): void
    {
        $workshopRequest
            ->setCustomerType($this->normalizeNullableString($payload['customerType'] ?? null))
            ->setFullName($this->normalizeNullableString($payload['fullName'] ?? null))
            ->setEmail($this->normalizeNullableString($payload['email'] ?? null))
            ->setPhone($this->normalizeNullableString($payload['phone'] ?? null))
            ->setPreferredContactMethod($this->normalizeNullableString($payload['preferredContactMethod'] ?? null))
            ->setCompanyName($this->normalizeNullableString($payload['companyName'] ?? null))
            ->setContactPerson($this->normalizeNullableString($payload['contactPerson'] ?? null))
            ->setRequiresInvoice($this->normalizeBoolean($payload['requiresInvoice'] ?? false))
            ->setRequestType($this->normalizeNullableString($payload['requestType'] ?? null))
            ->setNeedType($this->normalizeNullableString($payload['needType'] ?? null))
            ->setSubject($this->normalizeNullableString($payload['subject'] ?? null))
            ->setMessage($this->normalizeNullableString($payload['message'] ?? null))
            ->setEventType($this->normalizeNullableString($payload['eventType'] ?? null))
            ->setEventName($this->normalizeNullableString($payload['eventName'] ?? null))
            ->setEventDate($this->parseDateOrNull($payload['eventDate'] ?? null))
            ->setDesiredDate($this->parseDateOrNull($payload['desiredDate'] ?? null))
            ->setDeadlineNotes($this->normalizeNullableString($payload['deadlineNotes'] ?? null))
            ->setDesiredQuantityRange($this->normalizeNullableString($payload['desiredQuantityRange'] ?? null))
            ->setBudgetNotes($this->normalizeNullableString($payload['budgetNotes'] ?? null))
            ->setDeliveryMethod($this->normalizeNullableString($payload['deliveryMethod'] ?? null))
            ->setRequiresQuote($this->normalizeBoolean($payload['requiresQuote'] ?? false))
            ->setProjectStage($this->normalizeNullableString($payload['projectStage'] ?? null))
            ->setConsentRgpd($this->normalizeBoolean($payload['consentRgpd'] ?? false))
            ->setIpAddress($this->normalizeNullableString($payload['ipAddress'] ?? null))
            ->setUserAgent($this->normalizeNullableString($payload['userAgent'] ?? null));
    }

    /**
     * Hydrate les lignes de besoin et retourne les erreurs éventuelles.
     *
     * Chaque item peut contenir :
     * - categoryId
     * - productId
     * - customLabel
     * - quantity
     * - personalizationText
     * - materialNotes
     * - formatNotes
     * - colorNotes
     * - dimensionsNotes
     * - lineMessage
     * - position
     */
    private function hydrateItems(WorkshopRequest $workshopRequest, array $itemsPayload): array
    {
        $errors = [];

        foreach ($itemsPayload as $index => $itemData) {
            $itemPath = sprintf('items[%d]', $index);

            if (!is_array($itemData)) {
                $errors[$itemPath][] = 'Chaque ligne doit être un objet JSON valide.';
                continue;
            }

            $item = new WorkshopRequestItem();

            /*
            |--------------------------------------------------------------
            | Rattachement catégorie optionnel
            |--------------------------------------------------------------
            */
            $categoryId = $itemData['categoryId'] ?? null;
            if ($categoryId !== null && $categoryId !== '') {
                $category = $this->categoryRepository->find((int) $categoryId);

                if ($category === null) {
                    $errors[$itemPath . '.categoryId'][] = 'La catégorie demandée est introuvable.';
                } else {
                    $item->setCategory($category);
                }
            }

            /*
            |--------------------------------------------------------------
            | Rattachement produit optionnel
            |--------------------------------------------------------------
            */
            $productId = $itemData['productId'] ?? null;
            if ($productId !== null && $productId !== '') {
                $product = $this->productRepository->find((int) $productId);

                if ($product === null) {
                    $errors[$itemPath . '.productId'][] = 'Le produit demandé est introuvable.';
                } else {
                    $item->setProduct($product);
                }
            }

            /*
            |--------------------------------------------------------------
            | Hydratation des champs métier de ligne
            |--------------------------------------------------------------
            */
            $item
                ->setCustomLabel($this->normalizeNullableString($itemData['customLabel'] ?? null))
                ->setQuantity($this->normalizePositiveIntOrNull($itemData['quantity'] ?? null))
                ->setPersonalizationText($this->normalizeNullableString($itemData['personalizationText'] ?? null))
                ->setMaterialNotes($this->normalizeNullableString($itemData['materialNotes'] ?? null))
                ->setFormatNotes($this->normalizeNullableString($itemData['formatNotes'] ?? null))
                ->setColorNotes($this->normalizeNullableString($itemData['colorNotes'] ?? null))
                ->setDimensionsNotes($this->normalizeNullableString($itemData['dimensionsNotes'] ?? null))
                ->setLineMessage($this->normalizeNullableString($itemData['lineMessage'] ?? null))
                ->setPosition($this->normalizeNonNegativeInt($itemData['position'] ?? $index));

            /*
            |--------------------------------------------------------------
            | Validation légère spécifique à l'API
            |--------------------------------------------------------------
            |
            | On remonte ici des erreurs lisibles avant la validation Symfony
            | finale.
            |
            */
            if (
                $item->getCategory() === null
                && $item->getProduct() === null
                && $item->getCustomLabel() === null
            ) {
                $errors[$itemPath][] = 'Chaque ligne doit contenir au moins une catégorie, un produit ou un libellé libre.';
            }

            /*
            |--------------------------------------------------------------
            | On n'ajoute la ligne que si elle n'a pas d'erreur bloquante
            |--------------------------------------------------------------
            */
            $hasBlockingError = isset($errors[$itemPath]) || isset($errors[$itemPath . '.categoryId']) || isset($errors[$itemPath . '.productId']);

            if ($hasBlockingError) {
                continue;
            }

            $workshopRequest->addItem($item);
        }

        return $errors;
    }

    /**
     * Hydrate les pièces jointes et retourne les erreurs éventuelles.
     */
    private function hydrateAttachments(WorkshopRequest $workshopRequest, array $uploadedFiles): array
    {
        $errors = [];

        foreach ($uploadedFiles as $index => $uploadedFile) {
            $fieldPath = sprintf('attachmentFiles[%d]', $index);

            if (!$uploadedFile instanceof UploadedFile) {
                $errors[$fieldPath][] = 'Le fichier transmis est invalide.';
                continue;
            }

            if (!$uploadedFile->isValid()) {
                $errors[$fieldPath][] = 'Le fichier uploadé est invalide.';
                continue;
            }

            /*
            |--------------------------------------------------------------
            | Métadonnées AVANT move()
            |--------------------------------------------------------------
            */
            $originalName = $uploadedFile->getClientOriginalName();
            $mimeType = $uploadedFile->getMimeType();
            $size = $uploadedFile->getSize();

            if ($mimeType === null || !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
                $errors[$fieldPath][] = sprintf(
                    'Le fichier "%s" n\'a pas un format autorisé.',
                    $originalName
                );
                continue;
            }

            $attachmentType = $this->guessAttachmentTypeFromMimeType($mimeType);

            $extension = $uploadedFile->guessExtension()
                ?: $uploadedFile->getClientOriginalExtension()
                ?: 'bin';

            $storedName = sprintf(
                'wr_%s_%d_%s.%s',
                (new \DateTimeImmutable())->format('Ymd_His'),
                $index + 1,
                bin2hex(random_bytes(6)),
                strtolower($extension)
            );

            try {
                $uploadedFile->move($this->workshopRequestUploadDir, $storedName);
            } catch (FileException|\Throwable) {
                $errors[$fieldPath][] = sprintf(
                    'Le fichier "%s" n\'a pas pu être enregistré sur le serveur.',
                    $originalName
                );
                continue;
            }

            $attachment = new WorkshopRequestAttachment();

            $attachment
                ->setOriginalName($originalName)
                ->setStoredName($storedName)
                ->setPath('uploads/workshop-requests/' . $storedName)
                ->setMimeType($mimeType)
                ->setSize($size !== false ? (int) $size : null)
                ->setAttachmentType($attachmentType)
                ->setPosition($index);

            /*
            |--------------------------------------------------------------
            | Hash du fichier final
            |--------------------------------------------------------------
            */
            $absolutePath = rtrim($this->workshopRequestUploadDir, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . $storedName;

            if (is_file($absolutePath) && is_readable($absolutePath)) {
                $hash = hash_file('sha256', $absolutePath);
                if ($hash !== false) {
                    $attachment->setFileHash($hash);
                }
            }

            $workshopRequest->addAttachment($attachment);
        }

        return $errors;
    }

    /**
     * Fusionne plusieurs tableaux d'erreurs au format :
     * [
     *   'field' => ['msg1', 'msg2']
     * ]
     */
    private function mergeErrors(array ...$errorsSets): array
    {
        $merged = [];

        foreach ($errorsSets as $errors) {
            foreach ($errors as $field => $messages) {
                if (!isset($merged[$field])) {
                    $merged[$field] = [];
                }

                foreach ($messages as $message) {
                    $merged[$field][] = $message;
                }
            }
        }

        return $merged;
    }

    /**
     * Convertit les violations Symfony en tableau JSON exploitable.
     */
    private function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $path = (string) $violation->getPropertyPath();

            if ($path === '') {
                $path = 'global';
            }

            if (!isset($errors[$path])) {
                $errors[$path] = [];
            }

            $errors[$path][] = $violation->getMessage();
        }

        return $errors;
    }

    /**
     * Normalise une valeur texte :
     * - trim
     * - chaîne vide => null
     */
    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Normalise un booléen issu d'un multipart/form-data.
     */
    private function normalizeBoolean(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }

    /**
     * Convertit une valeur en int >= 0.
     */
    private function normalizeNonNegativeInt(mixed $value): int
    {
        $intValue = (int) $value;

        return max(0, $intValue);
    }

    /**
     * Convertit une valeur en entier strictement positif, sinon null.
     */
    private function normalizePositiveIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $intValue = (int) $value;

        return $intValue > 0 ? $intValue : null;
    }

    /**
     * Parse une date ou retourne null si vide / invalide.
     */
    private function parseDateOrNull(?string $value): ?\DateTimeImmutable
    {
        $value = $this->normalizeNullableString($value);

        if ($value === null) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Déduit le type métier de pièce jointe à partir du MIME type.
     */
    private function guessAttachmentTypeFromMimeType(string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => WorkshopRequestAttachment::TYPE_DOCUMENT,
            'image/jpeg', 'image/png', 'image/webp' => WorkshopRequestAttachment::TYPE_VISUAL,
            default => WorkshopRequestAttachment::TYPE_OTHER,
        };
    }
}