<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleIdTokenVerifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(OAUTH_GOOGLE_CLIENT_ID)%')]
        private readonly string $googleClientId,
    ) {
    }

    /**
     * @return array{email: string, name: ?string, given_name: ?string, family_name: ?string}
     */
    public function verify(string $idToken): array
    {
        $idToken = trim($idToken);
        if ($idToken === '') {
            throw new \InvalidArgumentException('Google ID token is required.');
        }

        try {
            $response = $this->httpClient->request('GET', 'https://oauth2.googleapis.com/tokeninfo', [
                'query' => ['id_token' => $idToken],
            ]);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Could not verify Google sign-in token. Check server internet/SSL settings.');
        }

        if ($response->getStatusCode() !== 200) {
            throw new \InvalidArgumentException('Invalid Google sign-in token.');
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->toArray(false);

        $aud = (string) ($payload['aud'] ?? '');
        $azp = (string) ($payload['azp'] ?? '');
        if ($aud !== $this->googleClientId && $azp !== $this->googleClientId) {
            throw new \InvalidArgumentException(
                'Google token audience does not match this app. Check OAUTH_GOOGLE_CLIENT_ID is the Web client ID.',
            );
        }

        if (($payload['email_verified'] ?? 'false') !== 'true' && ($payload['email_verified'] ?? false) !== true) {
            throw new \InvalidArgumentException('Google account email is not verified.');
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Google did not return a valid email.');
        }

        $name = isset($payload['name']) && is_string($payload['name']) ? trim($payload['name']) : null;
        $given = isset($payload['given_name']) && is_string($payload['given_name']) ? trim($payload['given_name']) : null;
        $family = isset($payload['family_name']) && is_string($payload['family_name']) ? trim($payload['family_name']) : null;

        return [
            'email' => $email,
            'name' => $name !== '' ? $name : null,
            'given_name' => $given !== '' ? $given : null,
            'family_name' => $family !== '' ? $family : null,
        ];
    }
}
