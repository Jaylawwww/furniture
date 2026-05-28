<?php

namespace App\Service;

use App\Entity\CustomerDeviceToken;
use App\Entity\User;
use App\Repository\CustomerDeviceTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PushNotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CustomerDeviceTokenRepository $deviceTokenRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $fcmServerKey,
    ) {
    }

    public function registerToken(User $user, string $token, string $platform = 'android'): void
    {
        $normalizedToken = trim($token);
        if ($normalizedToken === '') {
            throw new \InvalidArgumentException('Push token is required.');
        }

        $device = $this->deviceTokenRepository->findOneBy(['token' => $normalizedToken]) ?? new CustomerDeviceToken();
        $device->setToken($normalizedToken);
        $device->setUser($user);
        $device->setPlatform($platform);
        $device->touch();

        $this->entityManager->persist($device);
        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function notifyUser(User $user, string $title, string $body, array $data = []): void
    {
        if ($this->fcmServerKey === '') {
            return;
        }

        $tokens = $this->deviceTokenRepository->findTokensForUser($user);
        $tokens = array_values(array_filter($tokens, static fn (string $token): bool => $token !== ''));
        if ($tokens === []) {
            return;
        }

        try {
            $this->httpClient->request('POST', 'https://fcm.googleapis.com/fcm/send', [
                'headers' => [
                    'Authorization' => 'key='.$this->fcmServerKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'registration_ids' => $tokens,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'sound' => 'default',
                    ],
                    'data' => $data,
                    'priority' => 'high',
                ],
                'timeout' => 6.0,
            ]);
        } catch (ExceptionInterface|\Throwable $e) {
            $this->logger->warning('Failed to send push notification.', [
                'userId' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}

