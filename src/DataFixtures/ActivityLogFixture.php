<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\ActivityLogData;
use App\DataFixtures\Trait\AssignsEntityIdTrait;
use App\Entity\ActivityLog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ActivityLogFixture extends Fixture
{
    use AssignsEntityIdTrait;

    public function load(ObjectManager $manager): void
    {
        foreach (ActivityLogData::rows() as $row) {
            $log = (new ActivityLog())
                ->setUserId((int) $row['user_id'])
                ->setUsername($row['username'])
                ->setRole($row['role'])
                ->setAction($row['action'])
                ->setTargetData($row['target_data'])
                ->setDateTime(new \DateTime($row['date_time']));

            $this->persistWithId($manager, $log, (int) $row['id']);
        }

        $manager->flush();
    }
}
