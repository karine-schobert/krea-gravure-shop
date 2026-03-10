<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterApiController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function __invoke(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        RateLimiterFactory $registerLimiter,
    ): JsonResponse {
        $key = $request->getClientIp() ?? 'unknown';
        $limiter = $registerLimiter->create($key);

        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json([
                'message' => 'Trop de créations de compte. Réessaie plus tard.',
            ], 429);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'message' => 'JSON invalide.',
            ], 400);
        }

        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $violations = $validator->validate(
            [
                'email' => $email,
                'password' => $password,
            ],
            new Assert\Collection([
                'email' => [
                    new Assert\NotBlank(message: 'L’email est obligatoire.'),
                    new Assert\Email(message: 'L’email n’est pas valide.'),
                    new Assert\Length(max: 180),
                ],
                'password' => [
                    new Assert\NotBlank(message: 'Le mot de passe est obligatoire.'),
                    new Assert\Length(
                        min: 8,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'
                    ),
                ],
            ])
        );

        if (count($violations) > 0) {
            $errors = [];

            foreach ($violations as $violation) {
                $field = trim((string) $violation->getPropertyPath(), '[]');
                $errors[$field][] = $violation->getMessage();
            }

            return $this->json([
                'message' => 'Données invalides.',
                'errors' => $errors,
            ], 422);
        }

        $normalizedEmail = mb_strtolower($email);

        $existingUser = $userRepository->findOneBy([
            'email' => $normalizedEmail,
        ]);

        if ($existingUser) {
            return $this->json([
                'message' => 'Cet email est déjà utilisé.',
                'errors' => [
                    'email' => ['Cet email est déjà utilisé.'],
                ],
            ], 409);
        }

        $user = new User();
        $user->setEmail($normalizedEmail);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword(
            $passwordHasher->hashPassword($user, $password)
        );

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'Compte créé avec succès.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ], 201);
    }
}