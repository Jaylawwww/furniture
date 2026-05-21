<?php

namespace App\DataFixtures;

use App\DataFixtures\Trait\AssignsEntityIdTrait;
use App\Entity\CustomerOrder;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CustomerOrderFixture extends Fixture implements DependentFixtureInterface
{
    use AssignsEntityIdTrait;

    public const REF_ORDER_1 = 'customer-order-1';
    public const REF_ORDER_2 = 'customer-order-2';

    public function load(ObjectManager $manager): void
    {
        $rows = [
            [
                'id' => 1,
                'ref' => self::REF_ORDER_1,
                'user' => UserFixture::REF_USER_JELO,
                'status' => CustomerOrder::STATUS_CONFIRMED,
                'totalAmount' => '299.00',
                'notes' => 'hello',
                'createdAt' => '2026-05-20 14:30:56',
                'updatedAt' => '2026-05-20 14:31:33',
            ],
            [
                'id' => 2,
                'ref' => self::REF_ORDER_2,
                'user' => UserFixture::REF_USER_JELO,
                'status' => CustomerOrder::STATUS_CONFIRMED,
                'totalAmount' => '597.00',
                'notes' => null,
                'createdAt' => '2026-05-21 07:58:25',
                'updatedAt' => '2026-05-21 07:58:53',
            ],
        ];

        foreach ($rows as $row) {
            $order = (new CustomerOrder())
                ->setUser($this->getReference($row['user'], User::class))
                ->setStatus($row['status'])
                ->setTotalAmount($row['totalAmount'])
                ->setNotes($row['notes']);

            $this->setPrivateDateTimeImmutable($order, 'createdAt', $row['createdAt']);
            $this->setPrivateDateTimeImmutable($order, 'updatedAt', $row['updatedAt']);

            $this->persistWithId($manager, $order, $row['id']);
            $this->addReference($row['ref'], $order);
        }

        $manager->flush();
    }

    private function setPrivateDateTimeImmutable(object $entity, string $property, string $value): void
    {
        $reflection = new \ReflectionProperty($entity, $property);
        $reflection->setValue($entity, new \DateTimeImmutable($value));
    }

    public function getDependencies(): array
    {
        return [UserFixture::class];
    }
}
