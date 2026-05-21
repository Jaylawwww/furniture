<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:mailer:test',
    description: 'Send a test email through Brevo SMTP and report transport errors',
)]
final class MailerTestCommand extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('to', InputArgument::REQUIRED, 'Recipient email address');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $to = (string) $input->getArgument('to');
        $fromAddress = (string) $this->params->get('app.mailer_from_address');
        $fromName = (string) $this->params->get('app.mailer_from_name');

        $io->title('FurniStyle mailer test');
        $io->text([
            'From: ' . $fromAddress . ' (' . $fromName . ')',
            'To: ' . $to,
            '',
            'If this succeeds but you still get no email, verify the sender in Brevo:',
            'Senders → add/verify ' . $fromAddress,
            'Then check Brevo → Transactional → Email logs.',
        ]);

        $email = (new Email())
            ->from(new Address($fromAddress, $fromName))
            ->to($to)
            ->subject('[Test only] FurniStyle SMTP check — not a signup code')
            ->text(
                "This is only a mail connection test. It is NOT your account verification code.\n\n"
                . "To get a real 6-digit code: register at the website or app, or use Resend verification.\n"
                . "The real email subject is: \"Your FurniStyle verification code\".\n"
            )
            ->html(
                '<p><strong>This is only a mail connection test.</strong> It is <em>not</em> your signup verification code.</p>'
                . '<p>To get a real 6-digit code, <strong>create an account</strong> on the website or app, '
                . 'or open <strong>Enter email verification code</strong> and tap <strong>Resend code</strong>.</p>'
                . '<p>Real verification emails use the subject: <strong>Your FurniStyle verification code</strong>.</p>'
            );

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $io->error('SMTP transport failed: ' . $e->getMessage());
            $io->note([
                'Use the SMTP key (starts with xsmtpsib-), not the API key (xkeysib-).',
                'Update MAILER_DSN in .env.local and run: php bin/console cache:clear',
            ]);

            return Command::FAILURE;
        }

        $io->success('SMTP accepted the test message (no OTP in this email). Register or resend verification to get a real code.');

        return Command::SUCCESS;
    }
}
