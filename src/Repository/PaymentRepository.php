<?php

namespace App\Repository;

use App\Entity\CustomerOrder;
use App\Entity\Payment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * @return Payment[]
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->leftJoin('p.customerOrder', 'o')
            ->addSelect('o')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForUser(int $id, User $user): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id = :id')
            ->andWhere('p.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->leftJoin('p.customerOrder', 'o')
            ->addSelect('o')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPaidForOrder(CustomerOrder $order): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.customerOrder = :order')
            ->andWhere('p.status = :status')
            ->setParameter('order', $order)
            ->setParameter('status', Payment::STATUS_PAID)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
