<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private ParameterBagInterface $params,
        private BrevoApiMailer $brevoApiMailer,
    ) {}

    public function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $verificationUrl = preg_replace('/\s+/', '', $verificationUrl);

        $fromAddress = (string) $this->params->get('app.mailer_from_address');
        $fromName = (string) $this->params->get('app.mailer_from_name');
        $username = $user->getUsername() ?? 'there';
        $toEmail = (string) $user->getEmail();

        $subject = 'Confirm your FurniStyle account';

        $textBody = "Hello {$username},\n\n"
            ."Thanks for registering with FurniStyle. Confirm your email by opening this link (valid 24 hours):\n\n"
            .$verificationUrl."\n\n"
            ."If you did not create an account, ignore this email.\n";

        $safeUrl = htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8');
        $htmlBody = '<p>Hello <strong>'.htmlspecialchars($username, ENT_QUOTES, 'UTF-8')."</strong>,</p>\n"
            ."<p>Thanks for registering with FurniStyle. Tap the button below to confirm your email:</p>\n"
            .'<p style="margin:28px 0;"><a href="'.$safeUrl.'" style="display:inline-block;padding:14px 28px;background:#111;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">Confirm my email</a></p>'."\n"
            .'<p style="font-size:14px;color:#555;">This link expires in 24 hours. Open it in Chrome on this computer (http://127.0.0.1:8000).</p>'."\n"
            .'<p>If you did not create an account, ignore this email.</p>';

        try {
            if ($this->brevoApiMailer->isConfigured()) {
                $this->brevoApiMailer->send($toEmail, $fromAddress, $fromName, $subject, $textBody, $htmlBody);
            } else {
                $this->sendViaSymfonyMailer($fromAddress, $fromName, $toEmail, $subject, $textBody, $htmlBody);
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Could not send verification email. Verify sender ' . $fromAddress
                . ' in Brevo, or set BREVO_API_KEY in .env.local. Error: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    private function sendViaSymfonyMailer(
        string $fromAddress,
        string $fromName,
        string $toEmail,
        string $subject,
        string $textBody,
        string $htmlBody,
    ): void {
        $email = (new Email())
            ->from(new Address($fromAddress, $fromName))
            ->to(new Address($toEmail))
            ->subject($subject)
            ->text($textBody)
            ->html($htmlBody);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Build verify link using APP_PUBLIC_URL (not the API request host).
     */
    public function buildVerificationUrl(string $token, RouterInterface $router): string
    {
        $publicUrl = trim((string) $this->params->get('app.public_url'));
        $parsed = parse_url($publicUrl);
        if (!\is_array($parsed) || !isset($parsed['host'])) {
            throw new \RuntimeException('Invalid APP_PUBLIC_URL: '.$publicUrl);
        }

        $scheme = $parsed['scheme'] ?? 'http';
        $port = isset($parsed['port']) ? (int) $parsed['port'] : ($scheme === 'https' ? 443 : 80);

        $context = $router->getContext();
        $context->setScheme($scheme);
        $context->setHost($parsed['host']);
        if ($scheme === 'https') {
            $context->setHttpsPort($port);
        } else {
            $context->setHttpPort($port);
        }

        $url = $router->generate('app_verify_email', [
            'token' => $token,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return preg_replace('/\s+/', '', $url);
    }

    public function issueAndSendVerification(User $user, RouterInterface $router): void
    {
        $user->setIsVerified(false);
        $user->setVerificationToken($this->generateVerificationToken());
        $user->setVerificationTokenExpiresAt((new \DateTimeImmutable())->modify('+24 hours'));
        $this->entityManager->flush();

        $this->sendVerificationEmail(
            $user,
            $this->buildVerificationUrl((string) $user->getVerificationToken(), $router),
        );
    }

    public function verifyToken(string $token): ?User
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return null;
        }

        $expiresAt = $user->getVerificationTokenExpiresAt();
        if ($expiresAt !== null && $expiresAt < new \DateTimeImmutable()) {
            return null;
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);

        $this->entityManager->flush();

        return $user;
    }

    public function needsVerification(User $user): bool
    {
        return !$user->isVerified();
    }
}
