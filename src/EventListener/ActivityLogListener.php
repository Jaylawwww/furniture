<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\ActivityLogService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class ActivityLogListener
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }

    #[AsEventListener(event: LoginSuccessEvent::class)]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if ($user instanceof User) {
            $this->activityLogService->logLogin($user);
        }
    }

    #[AsEventListener(event: LogoutEvent::class)]
    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if ($token) {
            $user = $token->getUser();
            if ($user instanceof User) {
                $this->activityLogService->logLogout($user);
            }
        }
    }
}

