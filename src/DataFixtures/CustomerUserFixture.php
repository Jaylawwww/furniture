<?php

namespace App\DataFixtures;

use App\DataFixtures\Trait\AssignsEntityIdTrait;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Demo customer account (ROLE_USER) for mobile / customer API login.
 * Password: customer
 */
class CustomerUserFixture extends Fixture
{
    use AssignsEntityIdTrait;

    public const REF_USER_CUSTOMER = 'user-customer';

    public function load(ObjectManager $manager): void
    {
        $user = (new User())
            ->setEmail('customer@example.com')
            ->setUsername('customer')
            ->setRoles(['ROLE_USER'])
            ->setPassword('$2y$13$T3NW4Cr1fsaR5oabQd0ED.UsHfYLwXmqbsMMcFnHtS2Mx/Bc1V5Sy')
            ->setIsVerified(true)
            ->setName('Demo Customer')
            ->setStatus('active')
            ->setCreatedAt(new \DateTime('2026-05-22 00:00:00'));

        $this->persistWithId($manager, $user, 28);
        $this->addReference(self::REF_USER_CUSTOMER, $user);

        $manager->flush();
    }
}
