<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Log an activity
     */
    public function log(
        User $user,
        string $action,
        string $targetData
    ): void {
        $activityLog = new ActivityLog();
        
        // Get user's primary role (not ROLE_USER which is always added by getRoles())
        $roles = $user->getRawRoles(); // Use raw roles property
        $primaryRole = 'ROLE_STAFF'; // Default to staff
        foreach ($roles as $role) {
            if ($role === 'ROLE_ADMIN') {
                $primaryRole = 'ROLE_ADMIN';
                break;
            } elseif ($role === 'ROLE_STAFF') {
                $primaryRole = 'ROLE_STAFF';
                // Don't break, continue to check for ROLE_ADMIN
            }
        }
        
        $activityLog->setUserId($user->getId());
        $activityLog->setUsername($user->getEmail()); // Using email as username
        $activityLog->setRole($primaryRole);
        $activityLog->setAction($action);
        $activityLog->setTargetData($targetData);
        $activityLog->setDateTime(new \DateTime());

        $this->entityManager->persist($activityLog);
        $this->entityManager->flush();
    }

    /**
     * Log user login
     */
    public function logLogin(User $user): void
    {
        $this->log($user, 'LOGIN', 'User logged in');
    }

    /**
     * Log user logout
     */
    public function logLogout(User $user): void
    {
        $this->log($user, 'LOGOUT', 'User logged out');
    }

    /**
     * Log user creation
     */
    public function logUserCreated(User $actor, User $createdUser): void
    {
        $targetData = sprintf('User: %s (ID: %d)', $createdUser->getEmail(), $createdUser->getId());
        $this->log($actor, 'CREATE', $targetData);
    }

    /**
     * Log user deletion
     */
    public function logUserDeleted(User $actor, User $deletedUser): void
    {
        $targetData = sprintf('User: %s (ID: %d)', $deletedUser->getEmail(), $deletedUser->getId());
        $this->log($actor, 'DELETE', $targetData);
    }

    /**
     * Log product creation
     */
    public function logProductCreated(User $user, $product): void
    {
        $targetData = sprintf('Product: %s (ID: %d)', $product->getName(), $product->getId());
        $this->log($user, 'CREATE', $targetData);
    }

    /**
     * Log product update
     */
    public function logProductUpdated(User $user, $product): void
    {
        $targetData = sprintf('Product: %s (ID: %d)', $product->getName(), $product->getId());
        $this->log($user, 'UPDATE', $targetData);
    }

    /**
     * Log product deletion
     */
    public function logProductDeleted(User $user, $product): void
    {
        $targetData = sprintf('Product: %s (ID: %d)', $product->getName(), $product->getId());
        $this->log($user, 'DELETE', $targetData);
    }

    /**
     * Log category creation
     */
    public function logCategoryCreated(User $user, $category): void
    {
        $targetData = sprintf('Category: %s (ID: %d)', $category->getName(), $category->getId());
        $this->log($user, 'CREATE', $targetData);
    }

    /**
     * Log category update
     */
    public function logCategoryUpdated(User $user, $category): void
    {
        $targetData = sprintf('Category: %s (ID: %d)', $category->getName(), $category->getId());
        $this->log($user, 'UPDATE', $targetData);
    }

    /**
     * Log category deletion
     */
    public function logCategoryDeleted(User $user, $category): void
    {
        $targetData = sprintf('Category: %s (ID: %d)', $category->getName(), $category->getId());
        $this->log($user, 'DELETE', $targetData);
    }
}

