<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Orchestrates loading of all entity fixtures exported from the local database.
 */
class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Entity data is loaded by dedicated fixture classes.
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
            CustomerUserFixture::class,
            CategoryFixture::class,
            ProductFixture::class,
            CustomerOrderFixture::class,
            OrderItemFixture::class,
            BookingFixture::class,
            PaymentFixture::class,
            ActivityLogFixture::class,
        ];
    }
}
