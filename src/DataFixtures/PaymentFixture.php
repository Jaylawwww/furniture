<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * No rows in local customer_payment table at export time.
 */
class PaymentFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->flush();
    }
}
