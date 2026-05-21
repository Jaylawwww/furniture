<?php

namespace App\Controller;

use App\Entity\CustomerOrder;
use App\Repository\ActivityLogRepository;
use App\Repository\CategoryRepository;
use App\Repository\CustomerOrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        CustomerOrderRepository $orderRepository,
    ): Response {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof \App\Entity\User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Access denied. Only administrators can access the dashboard.');
        }

        // Get statistics
        $totalUsers = $userRepository->count([]);
        $totalProducts = $productRepository->count([]);
        $totalCategories = $categoryRepository->count([]);
        
        // Calculate total staff (users with ROLE_STAFF but not ROLE_ADMIN)
        $allUsers = $userRepository->findAll();
        $staffCount = 0;
        $adminCount = 0;
        foreach ($allUsers as $user) {
            $roles = $user->getRawRoles(); // Use raw roles property to check actual stored roles
            if (in_array('ROLE_ADMIN', $roles)) {
                $adminCount++;
            } elseif (in_array('ROLE_STAFF', $roles)) {
                $staffCount++;
            }
        }
        
        // Get recent users (last 10)
        $recentUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 10);
        
        // Get recent products (last 10) - ordered by ID descending (newest first)
        $recentProducts = $productRepository->findBy([], ['id' => 'DESC'], 10);
        
        // Get recent categories (last 10)
        $recentCategories = $categoryRepository->findBy([], ['id' => 'DESC'], 10);
        
        // Create recent activities from recent users, products, and categories
        $recentActivities = [];
        
        // Add user creation activities
        foreach ($recentUsers as $user) {
            $role = in_array('ROLE_ADMIN', $user->getRoles()) ? 'Admin' : 'Staff';
            $recentActivities[] = [
                'type' => 'user_created',
                'message' => 'New ' . $role . ' account created: ' . ($user->getName() ?? $user->getEmail()),
                'time' => $user->getCreatedAt(),
                'icon' => 'bi-person',
            ];
        }
        
        // Add product activities (using ID as proxy for recency)
        foreach ($recentProducts as $index => $product) {
            // Use a relative time based on position (most recent = now, older = further back)
            $hoursAgo = $index * 3; // 0, 3, 6, 9 hours ago, etc.
            $recentActivities[] = [
                'type' => 'product_created',
                'message' => 'Product added: ' . $product->getName(),
                'time' => (new \DateTime())->modify('-' . $hoursAgo . ' hours'),
                'icon' => 'bi-box',
            ];
        }
        
        // Add category activities
        foreach ($recentCategories as $index => $category) {
            $hoursAgo = ($index + count($recentProducts)) * 3;
            $recentActivities[] = [
                'type' => 'category_created',
                'message' => 'Category added: ' . $category->getName(),
                'time' => (new \DateTime())->modify('-' . $hoursAgo . ' hours'),
                'icon' => 'bi-folder',
            ];
        }
        
        // Sort activities by time (most recent first)
        usort($recentActivities, function($a, $b) {
            return $b['time'] <=> $a['time'];
        });
        
        // Limit to 10 most recent activities
        $recentActivities = array_slice($recentActivities, 0, 10);
        
        $recentOrders = $orderRepository->findForAdmin(null, 5);
        $pendingOrderCount = $orderRepository->countByStatus(CustomerOrder::STATUS_PENDING);

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalStaff' => $staffCount,
            'totalAdmins' => $adminCount,
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalRecords' => $totalProducts + $totalCategories,
            'recentActivities' => $recentActivities,
            'recentOrders' => $recentOrders,
            'pendingOrderCount' => $pendingOrderCount,
        ]);
    }

    #[Route('/admin/activity-logs', name: 'app_activity_logs')]
    public function activityLogs(
        Request $request,
        ActivityLogRepository $activityLogRepository
    ): Response {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof \App\Entity\User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Access denied. Only administrators can view activity logs.');
        }

        // Get filter parameters
        $searchQuery = $request->query->get('search', '');
        $actionFilter = $request->query->get('action', '');
        $roleFilter = $request->query->get('role', '');
        $orderFilter = $request->query->get('order', 'DESC'); // Default to DESC

        // Validate order parameter
        if (!in_array(strtoupper($orderFilter), ['ASC', 'DESC'])) {
            $orderFilter = 'DESC';
        }

        // Build query
        $qb = $activityLogRepository->createQueryBuilder('a')
            ->orderBy('a.dateTime', strtoupper($orderFilter));

        if (!empty($searchQuery)) {
            $qb->andWhere('a.username LIKE :search OR a.targetData LIKE :search')
               ->setParameter('search', '%' . $searchQuery . '%');
        }

        if (!empty($actionFilter)) {
            $qb->andWhere('a.action = :action')
               ->setParameter('action', $actionFilter);
        }

        if (!empty($roleFilter)) {
            // Handle both ROLE_STAFF and legacy ROLE_USER for staff filtering
            if ($roleFilter === 'ROLE_STAFF') {
                $qb->andWhere('a.role = :role OR a.role = :roleUser')
                   ->setParameter('role', 'ROLE_STAFF')
                   ->setParameter('roleUser', 'ROLE_USER');
            } else {
                $qb->andWhere('a.role = :role')
                   ->setParameter('role', $roleFilter);
            }
        }

        $activityLogs = $qb->getQuery()->getResult();

        return $this->render('admin/activity_logs.html.twig', [
            'activityLogs' => $activityLogs,
            'searchQuery' => $searchQuery,
            'actionFilter' => $actionFilter,
            'roleFilter' => $roleFilter,
            'orderFilter' => strtoupper($orderFilter),
        ]);
    }

    #[Route('/admin/search', name: 'app_admin_search')]
    public function search(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        UserRepository $userRepository
    ): Response {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof \App\Entity\User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Access denied. Only administrators can search.');
        }

        $query = trim($request->query->get('q', ''));
        $results = [
            'products' => [],
            'categories' => [],
            'users' => [],
        ];

        if (!empty($query)) {
            // Search products
            $results['products'] = $productRepository->createQueryBuilder('p')
                ->where('p.name LIKE :query OR p.description LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->orderBy('p.id', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();

            // Search categories
            $results['categories'] = $categoryRepository->createQueryBuilder('c')
                ->where('c.Name LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->orderBy('c.id', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();

            // Search users
            $results['users'] = $userRepository->createQueryBuilder('u')
                ->where('u.email LIKE :query OR u.name LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->orderBy('u.createdAt', 'DESC')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();
        }

        $isAdmin = $currentUser instanceof \App\Entity\User && in_array('ROLE_ADMIN', $currentUser->getRoles());
        
        return $this->render('admin/search_results.html.twig', [
            'query' => $query,
            'results' => $results,
            'totalResults' => count($results['products']) + count($results['categories']) + count($results['users']),
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/admin/reports', name: 'app_admin_reports')]
    public function reports(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        UserRepository $userRepository,
        ActivityLogRepository $activityLogRepository
    ): Response {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof \App\Entity\User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Access denied. Only administrators can view reports.');
        }

        // Product Statistics
        $allProducts = $productRepository->findAll();
        $totalProducts = count($allProducts);
        $productsByCategory = [];
        $priceRanges = [
            'low' => 0,      // 0-1000
            'medium' => 0,   // 1001-5000
            'high' => 0,     // 5001-10000
            'premium' => 0   // 10000+
        ];
        $totalValue = 0;
        $highestPrice = 0;
        $lowestPrice = PHP_INT_MAX;

        foreach ($allProducts as $product) {
            $price = (float) $product->getPrice();
            $totalValue += $price;
            
            if ($price > $highestPrice) {
                $highestPrice = $price;
            }
            if ($price < $lowestPrice) {
                $lowestPrice = $price;
            }

            // Price range categorization
            if ($price <= 1000) {
                $priceRanges['low']++;
            } elseif ($price <= 5000) {
                $priceRanges['medium']++;
            } elseif ($price <= 10000) {
                $priceRanges['high']++;
            } else {
                $priceRanges['premium']++;
            }

            // Products by category
            $categoryName = $product->getCategory() ? $product->getCategory()->getName() : 'Uncategorized';
            if (!isset($productsByCategory[$categoryName])) {
                $productsByCategory[$categoryName] = 0;
            }
            $productsByCategory[$categoryName]++;
        }

        $averagePrice = $totalProducts > 0 ? $totalValue / $totalProducts : 0;

        // Category Statistics
        $allCategories = $categoryRepository->findAll();
        $categoryStats = [];
        foreach ($allCategories as $category) {
            $productCount = count($category->getProducts());
            $categoryStats[] = [
                'name' => $category->getName(),
                'productCount' => $productCount,
            ];
        }
        usort($categoryStats, function($a, $b) {
            return $b['productCount'] <=> $a['productCount'];
        });

        // User Statistics
        $allUsers = $userRepository->findAll();
        $userStats = [
            'total' => count($allUsers),
            'active' => 0,
            'disabled' => 0,
            'archived' => 0,
            'admins' => 0,
            'staff' => 0,
        ];

        foreach ($allUsers as $user) {
            $status = $user->getStatus();
            if ($status === 'active') {
                $userStats['active']++;
            } elseif ($status === 'disabled') {
                $userStats['disabled']++;
            } else {
                $userStats['archived']++;
            }

            $roles = $user->getRawRoles();
            if (in_array('ROLE_ADMIN', $roles)) {
                $userStats['admins']++;
            } elseif (in_array('ROLE_STAFF', $roles)) {
                $userStats['staff']++;
            }
        }

        // Activity Statistics
        $allLogs = $activityLogRepository->findAll();
        $activityStats = [
            'total' => count($allLogs),
            'LOGIN' => 0,
            'LOGOUT' => 0,
            'CREATE' => 0,
            'UPDATE' => 0,
            'DELETE' => 0,
        ];

        $mostActiveUsers = [];
        foreach ($allLogs as $log) {
            $action = $log->getAction();
            if (isset($activityStats[$action])) {
                $activityStats[$action]++;
            }

            $username = $log->getUsername();
            if (!isset($mostActiveUsers[$username])) {
                $mostActiveUsers[$username] = 0;
            }
            $mostActiveUsers[$username]++;
        }

        arsort($mostActiveUsers);
        $topUsers = array_slice($mostActiveUsers, 0, 5, true);

        // Recent activity trends (last 7 days)
        $sevenDaysAgo = new \DateTime('-7 days');
        $recentLogs = $activityLogRepository->createQueryBuilder('a')
            ->where('a.dateTime >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->orderBy('a.dateTime', 'DESC')
            ->getQuery()
            ->getResult();

        $dailyActivity = [];
        foreach ($recentLogs as $log) {
            $date = $log->getDateTime()->format('Y-m-d');
            if (!isset($dailyActivity[$date])) {
                $dailyActivity[$date] = 0;
            }
            $dailyActivity[$date]++;
        }

        return $this->render('admin/reports.html.twig', [
            'totalProducts' => $totalProducts,
            'productsByCategory' => $productsByCategory,
            'priceRanges' => $priceRanges,
            'totalValue' => $totalValue,
            'averagePrice' => $averagePrice,
            'highestPrice' => $highestPrice > 0 ? $highestPrice : 0,
            'lowestPrice' => $lowestPrice < PHP_INT_MAX ? $lowestPrice : 0,
            'categoryStats' => $categoryStats,
            'userStats' => $userStats,
            'activityStats' => $activityStats,
            'topUsers' => $topUsers,
            'dailyActivity' => $dailyActivity,
        ]);
    }
}
