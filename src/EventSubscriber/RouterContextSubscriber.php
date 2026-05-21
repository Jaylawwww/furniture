<?php

namespace App\EventSubscriber;

use App\Service\AppUrlService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * In production (Railway), force the router to use APP_URL so emails and OAuth links use the public host.
 */
class RouterContextSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AppUrlService $appUrlService,
        private RouterInterface $router,
        private KernelInterface $kernel,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $this->kernel->getEnvironment() !== 'prod') {
            return;
        }

        $this->appUrlService->applyToRouter($this->router);
    }
}
