<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
        private UserPasswordHasherInterface $passwordHasher,
        private ParameterBagInterface $params,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        $session = $request->getSession();
        $session->start();
        $oauthSource = (string) $session->get('google_oauth_source', 'login');

        /** @var object $googleUser */
        $googleUser = $client->fetchUserFromToken($accessToken);
        $email = $googleUser->getEmail();

        if (!is_string($email) || $email === '') {
            throw new \RuntimeException('Google OAuth did not return an email address.');
        }

        $email = strtolower(trim($email));

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($email, $googleUser, $oauthSource, $session) {
                /** @var User|null $user */
                $user = $this->userRepository->findOneBy(['email' => $email]);
                $userAlreadyExisted = $user !== null;

                $mappedRole = $this->getMappedRoleForEmail($email);

                if ($user === null) {
                    if ($oauthSource === 'login') {
                        throw new CustomUserMessageAuthenticationException(
                            'No account found for this Google email. Please register first.'
                        );
                    }

                    if ($oauthSource === 'staff_login' && $mappedRole === null) {
                        throw new CustomUserMessageAuthenticationException(
                            'This Google account is not allowed for staff login.'
                        );
                    }

                    if ($oauthSource !== 'register' && $oauthSource !== 'staff_login') {
                        throw new CustomUserMessageAuthenticationException(
                            'No account found for this Google email. Please register first.'
                        );
                    }

                    $user = new User();
                    $user->setEmail($email);
                    $user->setUsername($this->generateUniqueUsername($email));
                    $user->setName($this->guessNameFromGoogleUser($googleUser, $email));
                    $user->setRoles([$this->resolveRoleForNewGoogleUser($oauthSource, $mappedRole)]);
                    $user->setStatus('active');
                    $user->setIsVerified(true);
                    $user->setVerificationToken(null);
                    $user->setVerificationTokenExpiresAt(null);

                    $plainPassword = bin2hex(random_bytes(16));
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);

                    $this->entityManager->persist($user);

                    if ($oauthSource === 'register') {
                        $session->set('google_oauth_register_new_user', true);
                    }
                } else {
                    if ($oauthSource === 'register') {
                        $session->getFlashBag()->add('error', 'An account with this Google email already exists. Please log in.');
                        $session->set('google_oauth_skip_login', true);
                    }

                    // Google implies the email is verified.
                    if (!$user->isVerified()) {
                        $user->setIsVerified(true);
                        $user->setVerificationToken(null);
                        $user->setVerificationTokenExpiresAt(null);
                    }

                    // If older accounts have missing fields, fill them from Google.
                    if ($user->getUsername() === null || trim((string) $user->getUsername()) === '') {
                        $user->setUsername($this->generateUniqueUsername($email));
                    }
                    if ($user->getName() === null || trim((string) $user->getName()) === '') {
                        $user->setName($this->guessNameFromGoogleUser($googleUser, $email));
                    }
                    if ($user->getPassword() === null || trim((string) $user->getPassword()) === '') {
                        $plainPassword = bin2hex(random_bytes(16));
                        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                        $user->setPassword($hashedPassword);
                    }

                    // Optionally elevate privileges based on configured email lists.
                    if ($mappedRole !== null) {
                        $rawRoles = $user->getRawRoles();
                        if (!in_array($mappedRole, $rawRoles, true)) {
                            $rawRoles[] = $mappedRole;
                            $user->setRoles(array_values(array_unique($rawRoles)));
                        }
                    }
                }

                $this->entityManager->flush();
                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        $session = $request->getSession();

        if ($session->get('google_oauth_register_new_user', false) === true) {
            $session->remove('google_oauth_register_new_user');
            $this->clearAuthenticatedSession($session);
            $session->getFlashBag()->add(
                'success',
                'Your account was created with Google. Please sign in to continue.'
            );

            return new RedirectResponse($this->router->generate('app_login'));
        }

        if ($session->get('google_oauth_skip_login', false) === true) {
            $session->remove('google_oauth_skip_login');
            $this->clearAuthenticatedSession($session);

            return new RedirectResponse($this->router->generate('app_login'));
        }

        if (!method_exists($user, 'getRoles')) {
            return new RedirectResponse($this->router->generate('app_homepage'));
        }

        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->router->generate('app_admin_dashboard'));
        }

        if (in_array('ROLE_STAFF', $roles, true)) {
            return new RedirectResponse($this->router->generate('app_staff_profile'));
        }

        return new RedirectResponse($this->router->generate('app_homepage'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $session = $request->getSession();
        $oauthSource = (string) $session->get('google_oauth_source', 'login');

        $message = $exception->getMessage();
        if ($message === '' || $message === $exception->getMessageKey()) {
            $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        }

        $session->getFlashBag()->add('error', $message);

        $redirectRoute = $oauthSource === 'register' ? 'app_register' : 'app_login';

        return new RedirectResponse($this->router->generate($redirectRoute));
    }

    private function clearAuthenticatedSession(\Symfony\Component\HttpFoundation\Session\SessionInterface $session): void
    {
        $this->tokenStorage->setToken(null);
        $session->remove('_security_main');
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        // Redirect to login page if needed.
        return new RedirectResponse($this->router->generate('app_login'));
    }

    /**
     * Customer login/register → ROLE_USER unless email is in admin/staff allow lists.
     */
    private function resolveRoleForNewGoogleUser(string $oauthSource, ?string $mappedRole): string
    {
        if ($mappedRole !== null) {
            return $mappedRole;
        }

        return $oauthSource === 'staff_login' ? 'ROLE_STAFF' : 'ROLE_USER';
    }

    /**
     * Returns ROLE_ADMIN or ROLE_STAFF if the email is listed; otherwise null.
     */
    private function getMappedRoleForEmail(string $email): ?string
    {
        $adminEmails = $this->parseEmailList((string) $this->params->get('app.google_admin_emails'));
        if (in_array($email, $adminEmails, true)) {
            return 'ROLE_ADMIN';
        }

        $staffEmails = $this->parseEmailList((string) $this->params->get('app.google_staff_emails'));
        if (in_array($email, $staffEmails, true)) {
            return 'ROLE_STAFF';
        }

        return null;
    }

    /**
     * Converts email local-part to a unique username.
     */
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
            $username = substr($base, 0, $maxBaseLength) . $suffix;
            $i++;
        }

        return $username;
    }

    private function guessNameFromGoogleUser(object $googleUser, string $email): ?string
    {
        if (method_exists($googleUser, 'getName')) {
            $name = $googleUser->getName();
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        if (method_exists($googleUser, 'getFirstName') && method_exists($googleUser, 'getLastName')) {
            $first = $googleUser->getFirstName();
            $last = $googleUser->getLastName();
            $first = is_string($first) ? trim($first) : '';
            $last = is_string($last) ? trim($last) : '';
            $full = trim($first . ' ' . $last);
            if ($full !== '') {
                return $full;
            }
        }

        $localPart = strtolower(trim((string) strstr($email, '@', true)));
        return $localPart !== '' && $localPart !== 'false' ? $localPart : null;
    }

    /**
     * @return list<string>
     */
    private function parseEmailList(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $items = array_filter(array_map('trim', explode(',', $raw)));
        return array_values(array_unique(array_map('strtolower', $items)));
    }
}

