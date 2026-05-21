<?php

namespace App\DataFixtures;

use App\DataFixtures\Trait\AssignsEntityIdTrait;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixture extends Fixture
{
    use AssignsEntityIdTrait;

    public const REF_CATEGORY_WOOD = 'category-wood';
    public const REF_CATEGORY_METAL = 'category-metal';
    public const REF_CATEGORY_PLASTIC = 'category-plastic';

    public function load(ObjectManager $manager): void
    {
        $rows = [
            ['id' => 1, 'ref' => self::REF_CATEGORY_WOOD, 'name' => 'Wood'],
            ['id' => 2, 'ref' => self::REF_CATEGORY_METAL, 'name' => 'Metal'],
            ['id' => 3, 'ref' => self::REF_CATEGORY_PLASTIC, 'name' => 'Plastic'],
        ];

        foreach ($rows as $row) {
            $category = (new Category())->setName($row['name']);
            $this->persistWithId($manager, $category, $row['id']);
            $this->addReference($row['ref'], $category);
        }

        $manager->flush();
    }
}
