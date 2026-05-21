<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create a default admin account if it does not exist',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = 'admin@gmail.com';
        $username = 'admin';

        $existing = $this->userRepository->findOneBy(['email' => $email]);
        if ($existing) {
            $io->success(sprintf('Admin user already exists with email "%s".', $email));
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setName('Default Admin');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setStatus('active');
        $user->setIsVerified(true);

        $plainPassword = 'admin123';
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Admin user created. Email: %s  Password: %s', $email, $plainPassword));

        return Command::SUCCESS;
    }
}

