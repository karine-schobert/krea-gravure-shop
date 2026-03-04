<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Entity\Category;
use App\Service\SluggerService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

/**
 * SlugListener (Doctrine)
 *
 * Rôle :
 * - Génère automatiquement un slug UNIQUE si le champ slug est vide
 * - Fonctionne sur Product et Category
 *
 * Déclencheurs :
 * - prePersist : avant insertion en DB
 * - preUpdate  : avant update en DB
 *
 * Important :
 * - On ne remplace pas un slug déjà rempli (sécurité)
 * - Si on modifie une entité en preUpdate, on doit recompute le changeset
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class SlugListener
{
    public function __construct(private SluggerService $sluggerService) {}

    /**
     * Avant insertion (INSERT)
     * Si slug vide → on génère un slug unique basé sur :
     * - Product: title
     * - Category: name
     */
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        // ✅ Product: slug basé sur title
        if ($entity instanceof Product && !$entity->getSlug()) {
            $entity->setSlug(
                $this->sluggerService->uniqueSlug(Product::class, (string) $entity->getTitle())
            );
        }

        // ✅ Category: slug basé sur name
        if ($entity instanceof Category && !$entity->getSlug()) {
            $entity->setSlug(
                $this->sluggerService->uniqueSlug(Category::class, (string) $entity->getName())
            );
        }
    }

    /**
     * Avant mise à jour (UPDATE)
     * Si slug vide → on génère un slug unique sans entrer en conflit avec l'entité actuelle
     *
     * ⚠️ Si on modifie une propriété en preUpdate :
     * il faut recalculer le changeset (recomputeSingleEntityChangeSet)
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $changed = false;

        // ✅ Product: slug basé sur title
        if ($entity instanceof Product && !$entity->getSlug()) {
            $entity->setSlug(
                $this->sluggerService->uniqueSlug(
                    Product::class,
                    (string) $entity->getTitle(),
                    'slug',
                    $entity->getId()
                )
            );
            $changed = true;
        }

        // ✅ Category: slug basé sur name
        if ($entity instanceof Category && !$entity->getSlug()) {
            $entity->setSlug(
                $this->sluggerService->uniqueSlug(
                    Category::class,
                    (string) $entity->getName(),
                    'slug',
                    $entity->getId()
                )
            );
            $changed = true;
        }

        // 🔁 Doctrine doit recalculer les changements si on a modifié le slug
        if ($changed) {
            $em = $args->getObjectManager();
            $uow = $em->getUnitOfWork();
            $meta = $em->getClassMetadata($entity::class);
            $uow->recomputeSingleEntityChangeSet($meta, $entity);
        }
    }
}