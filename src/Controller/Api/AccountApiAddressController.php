<?php

namespace App\Controller\Api;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API des adresses du compte client connecté.
 *
 * Ce controller permet de :
 * - lister les adresses du client
 * - créer une adresse
 * - modifier une adresse
 * - supprimer une adresse
 * - définir une adresse par défaut
 */
#[Route('/api/account/addresses', name: 'api_account_addresses_')]
class AccountApiAddressController extends AbstractController
{
    /**
     * Liste toutes les adresses du client connecté.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(AddressRepository $addressRepository): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $addresses = $addressRepository->findByUserOrdered($user);

        return $this->json([
            'addresses' => array_map(
                fn (Address $address) => $this->serializeAddress($address),
                $addresses
            ),
        ]);
    }

    /**
     * Crée une nouvelle adresse pour le client connecté.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        AddressRepository $addressRepository
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'message' => 'Invalid JSON payload',
            ], Response::HTTP_BAD_REQUEST);
        }

        $validationError = $this->validateAddressPayload($data);

        if ($validationError !== null) {
            return $this->json([
                'message' => $validationError,
            ], Response::HTTP_BAD_REQUEST);
        }

        $address = new Address();
        $address->setUser($user);

        $this->hydrateAddress($address, $data);

        /**
         * Si on coche cette adresse comme par défaut,
         * on retire le défaut des autres.
         */
        if ($address->isDefault()) {
            $addressRepository->clearDefaultForUser($user);
        }

        /**
         * Si c'est la première adresse du client,
         * on peut la définir par défaut automatiquement.
         */
        $existingAddresses = $addressRepository->findByUserOrdered($user);
        if (count($existingAddresses) === 0) {
            $address->setIsDefault(true);
        }

        $entityManager->persist($address);
        $entityManager->flush();

        return $this->json([
            'message' => 'Address created successfully',
            'address' => $this->serializeAddress($address),
        ], Response::HTTP_CREATED);
    }

    /**
     * Modifie une adresse appartenant au client connecté.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        AddressRepository $addressRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $address = $addressRepository->findOneByIdAndUser($id, $user);

        if (!$address) {
            return $this->json([
                'message' => 'Address not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'message' => 'Invalid JSON payload',
            ], Response::HTTP_BAD_REQUEST);
        }

        $validationError = $this->validateAddressPayload($data);

        if ($validationError !== null) {
            return $this->json([
                'message' => $validationError,
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->hydrateAddress($address, $data);

        /**
         * Si l'adresse modifiée devient l'adresse par défaut,
         * on retire le statut par défaut des autres adresses.
         */
        if ($address->isDefault()) {
            $addressRepository->clearDefaultForUser($user);
            $address->setIsDefault(true);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Address updated successfully',
            'address' => $this->serializeAddress($address),
        ]);
    }

    /**
     * Supprime une adresse appartenant au client connecté.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        AddressRepository $addressRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $address = $addressRepository->findOneByIdAndUser($id, $user);

        if (!$address) {
            return $this->json([
                'message' => 'Address not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $wasDefault = $address->isDefault();

        $entityManager->remove($address);
        $entityManager->flush();

        /**
         * Si on a supprimé l'adresse par défaut,
         * on peut en redéfinir une autre automatiquement si elle existe.
         */
        if ($wasDefault) {
            $remainingAddresses = $addressRepository->findByUserOrdered($user);

            if (!empty($remainingAddresses)) {
                $newDefault = $remainingAddresses[0];
                $newDefault->setIsDefault(true);
                $entityManager->flush();
            }
        }

        return $this->json([
            'message' => 'Address deleted successfully',
        ]);
    }

    /**
     * Définit une adresse comme adresse par défaut.
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/{id}/default', name: 'set_default', methods: ['PATCH'])]
    public function setDefault(
        int $id,
        AddressRepository $addressRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $address = $addressRepository->findOneByIdAndUser($id, $user);

        if (!$address) {
            return $this->json([
                'message' => 'Address not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $addressRepository->clearDefaultForUser($user);
        $address->setIsDefault(true);

        $entityManager->flush();

        return $this->json([
            'message' => 'Default address updated successfully',
            'address' => $this->serializeAddress($address),
        ]);
    }

    /**
     * Remplit une entité Address à partir des données reçues.
     */
    private function hydrateAddress(Address $address, array $data): void
    {
        $address->setFirstName(trim((string) ($data['firstName'] ?? '')));
        $address->setLastName(trim((string) ($data['lastName'] ?? '')));
        $address->setAddress(trim((string) ($data['address'] ?? '')));
        $address->setCity(trim((string) ($data['city'] ?? '')));
        $address->setPostalCode(trim((string) ($data['postalCode'] ?? '')));
        $address->setCountry(trim((string) ($data['country'] ?? '')));
        $address->setPhone($this->normalizeNullableString($data['phone'] ?? null));
        $address->setInstructions($this->normalizeNullableString($data['instructions'] ?? null));
        $address->setIsDefault((bool) ($data['isDefault'] ?? false));
    }

    /**
     * Validation simple des champs obligatoires.
     *
     * Retourne :
     * - null si OK
     * - un message d'erreur sinon
     */
    private function validateAddressPayload(array $data): ?string
    {
        $requiredFields = [
            'firstName' => 'First name is required',
            'lastName' => 'Last name is required',
            'address' => 'Address is required',
            'city' => 'City is required',
            'postalCode' => 'Postal code is required',
            'country' => 'Country is required',
        ];

        foreach ($requiredFields as $field => $message) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                return $message;
            }
        }

        return null;
    }

    /**
     * Convertit une valeur nullable en string nettoyée ou null.
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
     * Sérialisation manuelle d'une adresse pour la réponse JSON.
     */
    private function serializeAddress(Address $address): array
    {
        return [
            'id' => $address->getId(),
            'firstName' => $address->getFirstName(),
            'lastName' => $address->getLastName(),
            'address' => $address->getAddress(),
            'city' => $address->getCity(),
            'postalCode' => $address->getPostalCode(),
            'country' => $address->getCountry(),
            'phone' => $address->getPhone(),
            'instructions' => $address->getInstructions(),
            'isDefault' => $address->isDefault(),
            'createdAt' => $address->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $address->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}