<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class UserStatusListener implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        
        if (!$user instanceof User) {
            return;
        }

        // Check if account is disabled or archived
        if ($user->getStatus() === 'disabled' || $user->getStatus() === 'archived') {
            // Invalidate session
            $event->getRequest()->getSession()->invalidate();
            
            // Clear token
            $this->tokenStorage->setToken(null);
            
            // Set error message in session
            $event->getRequest()->getSession()->getFlashBag()->add(
                'error',
                $user->getStatus() === 'disabled' 
                    ? 'Your account has been disabled. Please contact an administrator.'
                    : 'Your account has been archived. Please contact an administrator.'
            );
            
            // Redirect to login
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login')));
        }
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        if (!$user instanceof User) {
            return;
        }

        // Double check on login success
        if ($user->getStatus() === 'disabled' || $user->getStatus() === 'archived') {
            $event->getRequest()->getSession()->invalidate();
            $this->tokenStorage->setToken(null);
            
            $event->getRequest()->getSession()->getFlashBag()->add(
                'error',
                $user->getStatus() === 'disabled' 
                    ? 'Your account has been disabled. Please contact an administrator.'
                    : 'Your account has been archived. Please contact an administrator.'
            );
            
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_login')));
        }
    }
}

