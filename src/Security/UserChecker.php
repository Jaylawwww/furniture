<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Block login until email is verified
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                'Please verify your email address before logging in.'
            );
        }

        // Check if account is disabled
        if ($user->getStatus() === 'disabled') {
            throw new CustomUserMessageAccountStatusException(
                'Your account has been disabled. Please contact an administrator.'
            );
        }

        // Check if account is archived
        if ($user->getStatus() === 'archived') {
            throw new CustomUserMessageAccountStatusException(
                'Your account has been archived. Please contact an administrator.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Additional checks after authentication if needed
    }
}

