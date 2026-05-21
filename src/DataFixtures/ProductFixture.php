<?php

namespace App\DataFixtures;

use App\DataFixtures\Trait\AssignsEntityIdTrait;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixture extends Fixture implements DependentFixtureInterface
{
    use AssignsEntityIdTrait;

    public const REF_PRODUCT_CHAIR = 'product-chair';
    public const REF_PRODUCT_DOOR = 'product-door';

    public function load(ObjectManager $manager): void
    {
        $rows = [
            [
                'id' => 2,
                'ref' => self::REF_PRODUCT_CHAIR,
                'name' => 'Chair',
                'description' => 'For sitting',
                'price' => 299.0,
                'image' => 'Uratex-classic-101-6a0da8e4aa4fe.jpg',
                'category' => CategoryFixture::REF_CATEGORY_PLASTIC,
                'stock' => 19,
            ],
            [
                'id' => 3,
                'ref' => self::REF_PRODUCT_DOOR,
                'name' => 'Door',
                'description' => null,
                'price' => 199.0,
                'image' => '261683078_421984186241357_3291080517835591349_n_2e92ac55-4f81-4f10-a55b-88ca6be2a93a_2048x-6a0ebabf03553.webp',
                'category' => CategoryFixture::REF_CATEGORY_WOOD,
                'stock' => 7,
            ],
        ];

        foreach ($rows as $row) {
            $product = (new Product())
                ->setName($row['name'])
                ->setDescription($row['description'])
                ->setPrice($row['price'])
                ->setImage($row['image'])
                ->setCategory($this->getReference($row['category'], \App\Entity\Category::class))
                ->setStock($row['stock']);

            $this->persistWithId($manager, $product, $row['id']);
            $this->addReference($row['ref'], $product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CategoryFixture::class];
    }
}
