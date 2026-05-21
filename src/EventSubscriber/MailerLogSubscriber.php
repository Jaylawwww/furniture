<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;

final class MailerLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SentMessageEvent::class => 'onSent',
            FailedMessageEvent::class => 'onFailed',
        ];
    }

    public function onSent(SentMessageEvent $event): void
    {
        $message = $event->getMessage();
        $this->logger->info('Mailer: message accepted by transport.', [
            'subject' => $message->getOriginalMessage()->getSubject(),
            'to' => array_map(static fn ($a) => $a->getAddress(), $message->getOriginalMessage()->getTo()),
        ]);
    }

    public function onFailed(FailedMessageEvent $event): void
    {
        $this->logger->error('Mailer: failed to send message.', [
            'error' => $event->getError()->getMessage(),
            'exception' => $event->getError(),
        ]);
    }
}
