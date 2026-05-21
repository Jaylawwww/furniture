<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sends mail via Brevo HTTP API (better delivery feedback than SMTP alone).
 */
final class BrevoApiMailer
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey = '',
    ) {}

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function send(
        string $toEmail,
        string $fromEmail,
        string $fromName,
        string $subject,
        string $textContent,
        string $htmlContent,
    ): void {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('BREVO_API_KEY is not configured in .env.local');
        }

        $response = $this->httpClient->request('POST', 'https://api.brevo.com/v3/smtp/email', [
            'timeout' => 20,
            'max_duration' => 25,
            'headers' => [
                'api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'sender' => ['email' => $fromEmail, 'name' => $fromName],
                'to' => [['email' => $toEmail]],
                'subject' => $subject,
                'textContent' => $textContent,
                'htmlContent' => $htmlContent,
                // Avoid Brevo click-tracking breaking long localhost URLs
                'headers' => [
                    'X-Mailin-Track' => 'false',
                ],
            ],
        ]);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $body = $response->getContent(false);
            throw new \RuntimeException('Brevo API rejected the email (HTTP ' . $status . '): ' . $body);
        }
    }
}
