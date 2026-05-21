<?php

namespace App\Repository;

use App\Entity\CustomerOrder;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerOrder>
 */
class CustomerOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerOrder::class);
    }

    /**
     * @return CustomerOrder[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->leftJoin('o.items', 'i')
            ->addSelect('i')
            ->leftJoin('i.product', 'p')
            ->addSelect('p')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForUser(int $id, User $user): ?CustomerOrder
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.id = :id')
            ->andWhere('o.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->leftJoin('o.items', 'i')
            ->addSelect('i')
            ->leftJoin('i.product', 'p')
            ->addSelect('p')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return CustomerOrder[]
     */
    public function findForAdmin(?string $status = null, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->leftJoin('o.items', 'i')
            ->addSelect('i')
            ->leftJoin('i.product', 'p')
            ->addSelect('p')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($status !== null && $status !== '') {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneForAdmin(int $id): ?CustomerOrder
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.id = :id')
            ->setParameter('id', $id)
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->leftJoin('o.items', 'i')
            ->addSelect('i')
            ->leftJoin('i.product', 'p')
            ->addSelect('p')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
