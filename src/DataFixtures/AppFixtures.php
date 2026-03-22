<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // =========================
        // USERS (Admin + 2 clients)
        // =========================

        // ADMIN
        $admin = (new User())
            ->setEmail('admin@krea.local')
            ->setFirstName('Admin')
            ->setLastName('Krea')
            ->setRoles(['ROLE_ADMIN']);

        $admin->setPassword(
            $this->hasher->hashPassword($admin, 'Admin123!')
        );

        $manager->persist($admin);

        // CLIENT 1
        $client1 = (new User())
            ->setEmail('client1@krea.local')
            ->setFirstName('Anthony')
            ->setLastName('Schobert')
            ->setRoles(['ROLE_USER']);

        $client1->setPassword(
            $this->hasher->hashPassword($client1, 'Client123!')
        );

        $manager->persist($client1);

        // CLIENT 2
        $client2 = (new User())
            ->setEmail('client2@krea.local')
            ->setFirstName('Client')
            ->setLastName('Deux')
            ->setRoles(['ROLE_USER']);

        $client2->setPassword(
            $this->hasher->hashPassword($client2, 'Client123!')
        );

        $manager->persist($client2);

        // =========================
        // FLUSH
        // =========================
        $manager->flush();
    }
}