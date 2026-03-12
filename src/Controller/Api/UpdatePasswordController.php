<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class UpdatePasswordController extends AbstractController
{
    #[Route('/api/account/password', name: 'api_account_password', methods: ['PATCH'])]
    public function __invoke(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'message' => 'Corps de requête invalide.',
            ], 400);
        }

        $currentPassword = trim((string) ($data['currentPassword'] ?? ''));
        $newPassword = trim((string) ($data['newPassword'] ?? ''));
        $confirmPassword = trim((string) ($data['confirmPassword'] ?? ''));

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            return $this->json([
                'message' => 'Tous les champs sont obligatoires.',
            ], 400);
        }

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->json([
                'message' => 'Le mot de passe actuel est incorrect.',
            ], 400);
        }

        if ($newPassword !== $confirmPassword) {
            return $this->json([
                'message' => 'La confirmation du nouveau mot de passe ne correspond pas.',
            ], 400);
        }

        if (mb_strlen($newPassword) < 8) {
            return $this->json([
                'message' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            ], 400);
        }

        if ($currentPassword === $newPassword) {
            return $this->json([
                'message' => 'Le nouveau mot de passe doit être différent de l’ancien.',
            ], 400);
        }

        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        $entityManager->flush();

        return $this->json([
            'message' => 'Mot de passe mis à jour avec succès.',
        ]);
    }
}