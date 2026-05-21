<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Search products by name or description
     */
    public function search(string $query, ?int $categoryId = null, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('p');
        
        if (!empty($query)) {
            $qb->andWhere('p.name LIKE :query OR p.description LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }
        
        if ($categoryId !== null) {
            $qb->andWhere('p.Category = :categoryId')
               ->setParameter('categoryId', $categoryId);
        }
        
        // If user is provided and not admin, filter by ownership
        if ($user !== null && !in_array('ROLE_ADMIN', $user->getRoles())) {
            $qb->andWhere('p.createdBy = :user')
               ->setParameter('user', $user);
        }
        
        return $qb->orderBy('p.id', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find products by user (for staff to see their own records)
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Product[]
     */
    public function findForCustomerCatalog(?int $categoryId = null, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.Category', 'c')
            ->addSelect('c');

        if ($categoryId !== null) {
            $qb->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        return $qb->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Product[] Returns an array of Product objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
