<?php

namespace App\Repository;

use App\Entity\StaticPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StaticPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StaticPage::class);
    }

    // =========================
    // LISTE DES PAGES ACTIVES
    // =========================
    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('p')
            // On ne remonte que les pages actives
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)

            // Tri alphabétique par titre pour l’admin ou les listes API
            ->orderBy('p.title', 'ASC')

            ->getQuery()
            ->getResult();
    }

    // =========================
    // PAGE ACTIVE PAR SLUG
    // =========================
    public function findOneActiveBySlug(string $slug): ?StaticPage
    {
        return $this->createQueryBuilder('p')
            // Recherche exacte sur le slug
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)

            // On ne retourne que les pages publiées / actives
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)

            ->getQuery()
            ->getOneOrNullResult();
    }
}