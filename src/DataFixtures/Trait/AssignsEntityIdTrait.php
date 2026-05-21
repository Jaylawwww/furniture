<?php

namespace App\DataFixtures\Trait;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;

trait AssignsEntityIdTrait
{
    private function persistWithId(ObjectManager $manager, object $entity, int $id): void
    {
        $metadata = $manager->getClassMetadata($entity::class);
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $id);

        $manager->persist($entity);
    }
}
