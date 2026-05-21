<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\CustomerAccountHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CustomerGoogleAuthService
{
    public function __construct(
        private readonly GoogleIdTokenVerifier $tokenVerifier,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @return array{user: User, created: bool}
     */
    public function authenticate(string $idToken, string $mode): array
    {
        if (!\in_array($mode, ['login', 'register'], true)) {
            throw new \InvalidArgumentException('Mode must be "login" or "register".');
        }

        $profile = $this->tokenVerifier->verify($idToken);
        $email = $profile['email'];

        /** @var User|null $user */
        $user = $this->userRepository->findOneBy(['email' => $email]);
        $created = false;

        if ($user === null) {
            if ($mode === 'login') {
                throw new \InvalidArgumentException('No account found for this Google email. Please register first.');
            }

            $user = $this->createCustomerFromGoogle($profile);
            $created = true;
        } else {
            if ($mode === 'register') {
                throw new \InvalidArgumentException('An account with this Google email already exists. Please log in.');
            }

            if (!CustomerAccountHelper::isCustomerOnly($user)) {
                throw new \InvalidArgumentException(
                    'Admin and staff accounts cannot sign in on the customer app. Please use the web admin panel.'
                );
            }

            $this->syncExistingCustomerFromGoogle($user, $profile);
        }

        if ($user->getStatus() === 'disabled') {
            throw new \InvalidArgumentException('Your account has been disabled. Please contact support.');
        }

        if ($user->getStatus() === 'archived') {
            throw new \InvalidArgumentException('Your account has been archived. Please contact support.');
        }

        $this->entityManager->flush();

        return ['user' => $user, 'created' => $created];
    }

    /**
     * @param array{email: string, name: ?string, given_name: ?string, family_name: ?string} $profile
     */
    private function createCustomerFromGoogle(array $profile): User
    {
        $user = new User();
        $user->setEmail($profile['email']);
        $user->setUsername($this->generateUniqueUsername($profile['email']));
        $user->setName($this->resolveDisplayName($profile));
        $user->setRoles(['ROLE_USER']);
        $user->setStatus('active');
        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);
        $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));

        $this->entityManager->persist($user);

        return $user;
    }

    /**
     * @param array{email: string, name: ?string, given_name: ?string, family_name: ?string} $profile
     */
    private function syncExistingCustomerFromGoogle(User $user, array $profile): void
    {
        if (!$user->isVerified()) {
            $user->setIsVerified(true);
            $user->setVerificationToken(null);
            $user->setVerificationTokenExpiresAt(null);
        }

        if ($user->getUsername() === null || trim((string) $user->getUsername()) === '') {
            $user->setUsername($this->generateUniqueUsername($profile['email']));
        }

        if ($user->getName() === null || trim((string) $user->getName()) === '') {
            $user->setName($this->resolveDisplayName($profile));
        }

        if ($user->getPassword() === null || trim((string) $user->getPassword()) === '') {
            $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));
        }
    }

    /**
     * @param array{email: string, name: ?string, given_name: ?string, family_name: ?string} $profile
     */
    private function resolveDisplayName(array $profile): ?string
    {
        if ($profile['name'] !== null) {
            return $profile['name'];
        }

        $full = trim(($profile['given_name'] ?? '').' '.($profile['family_name'] ?? ''));
        if ($full !== '') {
            return $full;
        }

        $local = strtolower(trim((string) strstr($profile['email'], '@', true)));

        return $local !== '' && $local !== 'false' ? $local : null;
    }

    private function generateUniqueUsername(string $email): string
    {
        $localPart = strtolower(trim((string) strstr($email, '@', true)));
        if ($localPart === '' || $localPart === 'false') {
            $localPart = 'user';
        }

        $base = preg_replace('/[^a-z0-9_]/i', '', $localPart) ?: 'user';
        $base = substr($base, 0, 50);

        $username = $base;
        $i = 1;

        while ($this->userRepository->findOneBy(['username' => $username]) !== null) {
            $suffix = (string) $i;
            $maxBaseLength = max(1, 50 - strlen($suffix));
            $username = substr($base, 0, $maxBaseLength).$suffix;
            $i++;
        }

        return $username;
    }
}
