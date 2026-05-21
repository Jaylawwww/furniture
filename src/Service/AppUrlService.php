<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\RouterInterface;

/**
 * Canonical public base URL for absolute links (email verification, OAuth, redirects).
 */
class AppUrlService
{
    public function __construct(
        #[Autowire('%app.public_url%')]
        private string $appUrl,
    ) {}

    public function getAppUrl(): string
    {
        return rtrim($this->appUrl, '/');
    }

    public function applyToRouter(RouterInterface $router): void
    {
        $parsed = parse_url($this->getAppUrl());
        if (!\is_array($parsed) || !isset($parsed['host'])) {
            return;
        }

        $scheme = $parsed['scheme'] ?? 'https';
        $port = isset($parsed['port']) ? (int) $parsed['port'] : ($scheme === 'https' ? 443 : 80);

        $context = $router->getContext();
        $context->setScheme($scheme);
        $context->setHost($parsed['host']);
        if ($scheme === 'https') {
            $context->setHttpsPort($port);
        } else {
            $context->setHttpPort($port);
        }
    }
}
