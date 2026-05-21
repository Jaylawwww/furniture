<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Twig\Environment;

/**
 * Sends contact form emails (store inbox + sender acknowledgement).
 * Uses Brevo HTTP API when BREVO_API_KEY is set — same path as verification emails.
 */
final class ContactMailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ParameterBagInterface $params,
        private readonly BrevoApiMailer $brevoApiMailer,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array{category: string, name: string, email: string, subject: string, message: string} $data
     */
    public function send(array $data): void
    {
        $fromAddress = (string) $this->params->get('app.mailer_from_address');
        $fromName = (string) $this->params->get('app.mailer_from_name');
        $toAddress = trim((string) $this->params->get('app.contact_to_address'));

        if ($toAddress === '' || !filter_var($toAddress, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('CONTACT_TO_ADDRESS is missing or invalid in .env');
        }

        if ($fromAddress === '' || !filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('MAILER_FROM_ADDRESS is missing or invalid in .env');
        }

        $submittedAt = new \DateTimeImmutable();
        $category = (string) $data['category'];
        $name = (string) $data['name'];
        $senderEmail = (string) $data['email'];
        $subject = (string) $data['subject'];
        $message = (string) $data['message'];

        $inboxSubject = sprintf('[Contact:%s] %s', $category, $subject);
        $inboxContext = [
            'category' => $category,
            'name' => $name,
            'senderEmail' => $senderEmail,
            'subject' => $subject,
            'message' => $message,
            'submittedAt' => $submittedAt,
        ];
        $inboxHtml = $this->twig->render('contact/message_email.html.twig', $inboxContext);
        $inboxText = $this->plainInboxBody($inboxContext);

        $ackSubject = 'We received your message - FurniStyle';
        $ackContext = [
            'name' => $name,
            'subject' => $subject,
            'category' => $category,
            'message' => $message,
        ];
        $ackHtml = $this->twig->render('contact/message_ack_email.html.twig', $ackContext);
        $ackText = $this->plainAckBody($ackContext);

        try {
            if ($this->brevoApiMailer->isConfigured()) {
                $this->brevoApiMailer->send($toAddress, $fromAddress, $fromName, $inboxSubject, $inboxText, $inboxHtml);
                $this->brevoApiMailer->send($senderEmail, $fromAddress, $fromName, $ackSubject, $ackText, $ackHtml);
            } else {
                $this->sendInboxViaSymfony($fromAddress, $fromName, $toAddress, $senderEmail, $name, $inboxSubject, $inboxContext);
                $this->sendAckViaSymfony($fromAddress, $fromName, $senderEmail, $name, $ackSubject, $ackContext);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Contact mail send failed.', [
                'exception' => $e,
                'contact_to' => $toAddress,
                'contact_from' => $senderEmail,
                'category' => $category,
            ]);
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    private function plainInboxBody(array $context): string
    {
        $submitted = $context['submittedAt'] instanceof \DateTimeInterface
            ? $context['submittedAt']->format('Y-m-d H:i:s')
            : (string) $context['submittedAt'];

        return "New contact message\n\n"
            .'Category: '.(string) $context['category']."\n"
            .'Name: '.(string) $context['name']."\n"
            .'Email: '.(string) $context['senderEmail']."\n"
            .'Subject: '.(string) $context['subject']."\n"
            .'Submitted: '.$submitted."\n\n"
            .(string) $context['message'];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function plainAckBody(array $context): string
    {
        return 'Hi '.(string) $context['name'].",\n\n"
            ."We received your message and our team will reply as soon as possible.\n\n"
            .'Category: '.(string) $context['category']."\n"
            .'Subject: '.(string) $context['subject']."\n\n"
            .(string) $context['message']."\n\n"
            ."- FurniStyle Team\n";
    }

    /**
     * @param array<string, mixed> $context
     */
    private function sendInboxViaSymfony(
        string $fromAddress,
        string $fromName,
        string $toAddress,
        string $replyEmail,
        string $replyName,
        string $subject,
        array $context,
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address($fromAddress, $fromName))
            ->to(new Address($toAddress))
            ->replyTo(new Address($replyEmail, $replyName))
            ->subject($subject)
            ->htmlTemplate('contact/message_email.html.twig')
            ->context($context);

        $this->sendSymfony($email);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function sendAckViaSymfony(
        string $fromAddress,
        string $fromName,
        string $toEmail,
        string $toName,
        string $subject,
        array $context,
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address($fromAddress, $fromName))
            ->to(new Address($toEmail, $toName))
            ->subject($subject)
            ->htmlTemplate('contact/message_ack_email.html.twig')
            ->context($context);

        $this->sendSymfony($email);
    }

    private function sendSymfony(TemplatedEmail $email): void
    {
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
    }
}
