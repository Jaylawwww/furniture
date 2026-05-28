<?php

namespace App\Repository;

use App\Entity\CustomerDeviceToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerDeviceToken>
 */
class CustomerDeviceTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerDeviceToken::class);
    }

    /**
     * @return list<string>
     */
    public function findTokensForUser(User $user): array
    {
        $rows = $this->createQueryBuilder('d')
            ->select('d.token')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        return array_values(array_unique(array_map(
            static fn (array $row): string => (string) ($row['token'] ?? ''),
            $rows,
        )));
    }
}

