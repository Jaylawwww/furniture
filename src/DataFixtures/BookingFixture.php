<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * No rows in local customer_booking table at export time.
 */
class BookingFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->flush();
    }
}
