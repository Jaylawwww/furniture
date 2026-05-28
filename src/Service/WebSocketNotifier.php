<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WebSocketNotifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $broadcastUrl,
        private readonly string $secret,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function publish(string $channel, string $type, array $payload): void
    {
        if ($this->broadcastUrl === '' || $this->secret === '') {
            return;
        }

        try {
            $this->httpClient->request('POST', $this->broadcastUrl, [
                'headers' => [
                    'x-ws-secret' => $this->secret,
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'channel' => $channel,
                    'type' => $type,
                    'payload' => $payload,
                ],
                'timeout' => 2.0,
            ]);
        } catch (ExceptionInterface|\Throwable $e) {
            $this->logger->warning('WebSocket broadcast failed.', [
                'channel' => $channel,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

