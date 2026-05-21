<?php

namespace App\DataFixtures;

use App\DataFixtures\Trait\AssignsEntityIdTrait;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixture extends Fixture
{
    use AssignsEntityIdTrait;

    public const REF_USER_JELO = 'user-jelorence';
    public const REF_USER_ADMIN = 'user-admin';
    public const REF_USER_STAFF = 'user-staff';

    public function load(ObjectManager $manager): void
    {
        $rows = [
            [
                'id' => 3,
                'ref' => self::REF_USER_JELO,
                'email' => 'jelorence07@gmail.com',
                'username' => 'jelorence07',
                'roles' => ['ROLE_USER'],
                'password' => '$2y$13$VKN37hB7lahzyW1tPEXbOekrn.fErBm1kY8jwZ.3ytjZ.DSDU4Puq',
                'isVerified' => true,
                'name' => 'Oracion, Jay Lawrence C.',
                'status' => 'active',
                'createdAt' => '2026-05-20 14:21:14',
            ],
            [
                'id' => 26,
                'ref' => self::REF_USER_ADMIN,
                'email' => 'admin@gmail.com',
                'username' => 'admin',
                'roles' => ['ROLE_ADMIN'],
                'password' => '$2y$13$8d3uOXba7XrdfW0MFOQso.XR7lUEYm85xAgKzXn17unmAJeUegPvC',
                'isVerified' => true,
                'name' => 'Default Admin',
                'status' => 'active',
                'createdAt' => '2026-05-21 17:00:53',
            ],
            [
                'id' => 27,
                'ref' => self::REF_USER_STAFF,
                'email' => 'calibscch@gmail.com',
                'username' => 'staff',
                'roles' => ['ROLE_STAFF'],
                'password' => '$2y$13$tUNU3ZF2ihER8p/Cq4XyQ.zqGE3XB2Imu4vg3sbjfmt0alA6/ANxC',
                'isVerified' => true,
                'name' => 'staff',
                'status' => 'active',
                'createdAt' => '2026-05-21 17:02:49',
            ],
        ];

        foreach ($rows as $row) {
            $user = (new User())
                ->setEmail($row['email'])
                ->setUsername($row['username'])
                ->setRoles($row['roles'])
                ->setPassword($row['password'])
                ->setIsVerified($row['isVerified'])
                ->setName($row['name'])
                ->setStatus($row['status'])
                ->setCreatedAt(new \DateTime($row['createdAt']));

            $this->persistWithId($manager, $user, $row['id']);
            $this->addReference($row['ref'], $user);
        }

        $manager->flush();
    }
}
