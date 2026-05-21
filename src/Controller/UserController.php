<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\CustomerProfileFormType;
use App\Form\UserType;
use App\Service\EmailVerificationService;
use Symfony\Component\Routing\RouterInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogService;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/admin/users', name: 'app_user_index')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(UserRepository $userRepository): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Access denied. Only administrators can view user accounts.');
        }

        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/admin/users/new', name: 'app_user_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher,
        ActivityLogService $activityLogService,
        EmailVerificationService $emailVerificationService,
        RouterInterface $router,
    ): Response {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Access denied. Only administrators can create user accounts.');
        }

        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password if provided
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );
            }

            // Require email verification
            $user->setIsVerified(false);
            $user->setVerificationToken($emailVerificationService->generateVerificationToken());
            $user->setVerificationTokenExpiresAt((new \DateTimeImmutable())->modify('+24 hours'));

            $entityManager->persist($user);
            $entityManager->flush();

            // Send verification email
            $verificationUrl = $emailVerificationService->buildVerificationUrl(
                (string) $user->getVerificationToken(),
                $router,
            );
            $emailVerificationService->sendVerificationEmail($user, $verificationUrl);

            // Log the activity
            $activityLogService->logUserCreated($currentUser, $user);

            $this->addFlash('success', 'User account created successfully! A verification email has been sent.');

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/admin/users/{id}', name: 'app_user_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(User $user): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Access denied. Only administrators can view user details.');
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Access denied. Only administrators can edit user accounts.');
        }

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Prevent staff from changing roles (extra security check)
            $submittedRoles = $form->get('roles')->getData();
            if (!is_array($submittedRoles)) {
                $submittedRoles = [$submittedRoles];
            }
            
            // Only admins can set admin roles
            $user->setRoles($submittedRoles);
            
            $entityManager->flush();

            $this->addFlash('success', 'User account updated successfully!');

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/admin/users/{id}/reset-password', name: 'app_user_reset_password', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function resetPassword(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher
    ): Response {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Access denied. Only administrators can reset user passwords.');
        }

        if ($this->isCsrfTokenValid('reset_password' . $user->getId(), $request->request->get('_token'))) {
            // Generate a temporary password
            $tempPassword = bin2hex(random_bytes(8));
            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $tempPassword)
            );

            $entityManager->flush();

            $this->addFlash('success', 'Password reset successfully! Temporary password: ' . $tempPassword);
        }

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/admin/users/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function toggleStatus(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Access denied. Only administrators can change user status.');
        }

        if ($this->isCsrfTokenValid('toggle_status' . $user->getId(), $request->request->get('_token'))) {
            $newStatus = $request->request->get('status');
            if (in_array($newStatus, ['active', 'disabled', 'archived'])) {
                $user->setStatus($newStatus);
                $entityManager->flush();

                $this->addFlash('success', 'User status updated successfully!');
            }
        }

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/admin/users/{id}', name: 'app_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        ActivityLogService $activityLogService,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || !in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw $this->createAccessDeniedException('Access denied. Only administrators can delete user accounts.');
        }

        // Prevent deleting yourself
        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'You cannot delete your own account!');
            return $this->redirectToRoute('app_user_index');
        }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            try {
                // Log the activity before deletion
                $activityLogService->logUserDeleted($currentUser, $user);
                
                // Set createdBy to NULL for all products created by this user
                $products = $productRepository->findBy(['createdBy' => $user]);
                foreach ($products as $product) {
                    $product->setCreatedBy(null);
                }
                
                // Set createdBy to NULL for all categories created by this user
                $categories = $categoryRepository->findBy(['createdBy' => $user]);
                foreach ($categories as $category) {
                    $category->setCreatedBy(null);
                }
                
                // Flush the changes to products and categories first
                $entityManager->flush();
                
                // Now delete the user
                $entityManager->remove($user);
                $entityManager->flush();

                $this->addFlash('success', 'User account deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Failed to delete user account: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        // Ensure user is authenticated
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            // Verify current password
            if (!$userPasswordHasher->isPasswordValid($user, $currentPassword)) {
                // Add form error to the currentPassword field
                $form->get('currentPassword')->addError(new FormError('Your current password is incorrect.'));
                // Don't add flash message - form error is enough
                return $this->render('user/change_password.html.twig', [
                    'changePasswordForm' => $form,
                    'isAdmin' => $isAdmin,
                ]);
            }

            // Check if new password and confirm password match
            if ($newPassword !== $confirmPassword) {
                $form->get('confirmPassword')->addError(new FormError('New password and confirm password do not match.'));
                return $this->render('user/change_password.html.twig', [
                    'changePasswordForm' => $form,
                    'isAdmin' => $isAdmin,
                ]);
            }

            // Check if new password is different from current password
            if ($userPasswordHasher->isPasswordValid($user, $newPassword)) {
                $form->get('newPassword')->addError(new FormError('New password must be different from your current password.'));
                return $this->render('user/change_password.html.twig', [
                    'changePasswordForm' => $form,
                    'isAdmin' => $isAdmin,
                ]);
            }

            // Encode and set the new password
            $hashedPassword = $userPasswordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            // Save to database
            $entityManager->flush();

            $this->addFlash('success', 'Your password has been changed successfully!');

            // Redirect back to change password page
            return $this->redirectToRoute('app_change_password');
        }

        return $this->render('user/change_password.html.twig', [
            'changePasswordForm' => $form,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/staff/profile', name: 'app_staff_profile')]
    public function staffProfile(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        
        // Redirect admins to admin dashboard
        if ($isAdmin) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('user/staff_profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    public function customerProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        // Staff and admins use their own dashboards
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->redirectToRoute('app_admin_dashboard');
        }
        if (in_array('ROLE_STAFF', $user->getRoles(), true)) {
            return $this->redirectToRoute('app_staff_profile');
        }

        $form = $this->createForm(CustomerProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check username uniqueness (simple check)
            $existing = $userRepository->findOneBy(['username' => $user->getUsername()]);
            if ($existing && $existing->getId() !== $user->getId()) {
                $form->get('username')->addError(new FormError('This username is already taken.'));
            } else {
                $entityManager->flush();
                $this->addFlash('success', 'Profile updated successfully.');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/staff/my-records', name: 'app_staff_my_records')]
    public function myRecords(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        
        // Redirect admins to admin dashboard
        if ($isAdmin) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        $myProducts = $productRepository->findByUser($user);
        $myCategories = $categoryRepository->findByUser($user);

        return $this->render('user/my_records.html.twig', [
            'user' => $user,
            'products' => $myProducts,
            'categories' => $myCategories,
        ]);
    }

    #[Route('/staff/statistics', name: 'app_staff_statistics')]
    public function statistics(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        
        // Redirect admins to admin dashboard
        if ($isAdmin) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        $myProducts = $productRepository->findByUser($user);
        $myCategories = $categoryRepository->findByUser($user);
        
        // Calculate statistics
        $totalProducts = count($myProducts);
        $totalCategories = count($myCategories);
        
        // Product price statistics
        $totalValue = 0;
        $highestPrice = 0;
        $lowestPrice = PHP_INT_MAX;
        $productsByCategory = [];
        
        foreach ($myProducts as $product) {
            $price = (float) $product->getPrice();
            $totalValue += $price;
            
            if ($price > $highestPrice) {
                $highestPrice = $price;
            }
            if ($price < $lowestPrice) {
                $lowestPrice = $price;
            }
            
            // Products by category
            $categoryName = $product->getCategory() ? $product->getCategory()->getName() : 'Uncategorized';
            if (!isset($productsByCategory[$categoryName])) {
                $productsByCategory[$categoryName] = 0;
            }
            $productsByCategory[$categoryName]++;
        }
        
        $averagePrice = $totalProducts > 0 ? $totalValue / $totalProducts : 0;

        return $this->render('user/staff_statistics.html.twig', [
            'user' => $user,
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'totalValue' => $totalValue,
            'averagePrice' => $averagePrice,
            'highestPrice' => $highestPrice > 0 ? $highestPrice : 0,
            'lowestPrice' => $lowestPrice < PHP_INT_MAX ? $lowestPrice : 0,
            'productsByCategory' => $productsByCategory,
        ]);
    }
}
