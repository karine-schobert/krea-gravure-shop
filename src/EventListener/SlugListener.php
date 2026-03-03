<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Entity\Category;
use App\Service\SluggerService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class SlugListener
{
    public function __construct(private SluggerService $sluggerService) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Product && !$entity->getSlug()) {
            $entity->setSlug(
                $this->sluggerService->uniqueSlug(Product::class, (string) $entity->getTitle())
            );
        }

        if ($entity instanceof Category && !$entity->getSlug()) {
            $entity->setSlug(
                $this->sluggerService->uniqueSlug(Category::class, (string) $entity->getName())
            );
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $changed = false;

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

        if ($changed) {
            $em = $args->getObjectManager();
            $uow = $em->getUnitOfWork();
            $meta = $em->getClassMetadata($entity::class);
            $uow->recomputeSingleEntityChangeSet($meta, $entity);
        }
    }
}