<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

class SluggerService
{
    public function __construct(
        private SluggerInterface $slugger,
        private EntityManagerInterface $em
    ) {}

    /**
     * Génère un slug unique pour une entité donnée.
     *
     * @param class-string $entityClass Ex: App\Entity\Product::class
     * @param string $sourceTexte       Ex: title ou name
     * @param string $slugField         Ex: "slug"
     * @param int|null $currentId       ID actuel (pour ignorer soi-même en update)
     */
    public function uniqueSlug(
        string $entityClass,
        string $sourceTexte,
        string $slugField = 'slug',
        ?int $currentId = null
    ): string {
        $base = $this->slugify($sourceTexte);

        // fallback si titre vide ou caractères impossibles
        if ($base === '') {
            $base = 'item';
        }

        $repo = $this->em->getRepository($entityClass);

        $slug = $base;
        $i = 2;

        while ($this->slugExists($repo, $slugField, $slug, $currentId)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    public function slugify(string $text): string
    {
        return strtolower($this->slugger->slug($text)->toString());
    }

    private function slugExists(
        ObjectRepository $repo,
        string $slugField,
        string $slug,
        ?int $currentId
    ): bool {
        // On récupère une entité qui match le slug
        $found = $repo->findOneBy([$slugField => $slug]);

        if (!$found) {
            return false;
        }

        // Si on est en update, on ignore l'entité elle-même
        if ($currentId !== null && method_exists($found, 'getId')) {
            return (int) $found->getId() !== (int) $currentId;
        }

        return true;
    }
}