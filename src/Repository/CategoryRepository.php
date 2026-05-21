<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Search categories by name
     */
    public function search(string $query, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('c');
        
        if (!empty($query)) {
            $qb->andWhere('c.Name LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }
        
        // If user is provided and not admin, filter by ownership
        if ($user !== null && !in_array('ROLE_ADMIN', $user->getRoles())) {
            $qb->andWhere('c.createdBy = :user')
               ->setParameter('user', $user);
        }
        
        return $qb->orderBy('c.id', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find categories by user (for staff to see their own records)
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Category[] Returns an array of Category objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Category
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
