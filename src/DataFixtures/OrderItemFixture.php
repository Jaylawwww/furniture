<?php

namespace App\DataFixtures;

use App\DataFixtures\Trait\AssignsEntityIdTrait;
use App\Entity\CustomerOrder;
use App\Entity\OrderItem;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OrderItemFixture extends Fixture implements DependentFixtureInterface
{
    use AssignsEntityIdTrait;

    public function load(ObjectManager $manager): void
    {
        $rows = [
            [
                'id' => 1,
                'order' => CustomerOrderFixture::REF_ORDER_1,
                'product' => ProductFixture::REF_PRODUCT_CHAIR,
                'quantity' => 1,
                'unitPrice' => '299.00',
                'lineTotal' => '299.00',
            ],
            [
                'id' => 2,
                'order' => CustomerOrderFixture::REF_ORDER_2,
                'product' => ProductFixture::REF_PRODUCT_DOOR,
                'quantity' => 3,
                'unitPrice' => '199.00',
                'lineTotal' => '597.00',
            ],
        ];

        foreach ($rows as $row) {
            $order = $this->getReference($row['order'], CustomerOrder::class);
            $item = (new OrderItem())
                ->setCustomerOrder($order)
                ->setProduct($this->getReference($row['product'], Product::class))
                ->setQuantity($row['quantity'])
                ->setUnitPrice($row['unitPrice']);

            $this->setPrivateProperty($item, 'lineTotal', $row['lineTotal']);
            $order->addItem($item);

            $this->persistWithId($manager, $item, $row['id']);
        }

        $manager->flush();
    }

    private function setPrivateProperty(object $entity, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($entity, $property);
        $reflection->setValue($entity, $value);
    }

    public function getDependencies(): array
    {
        return [
            CustomerOrderFixture::class,
            ProductFixture::class,
        ];
    }
}
