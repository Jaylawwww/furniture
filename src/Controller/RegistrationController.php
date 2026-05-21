<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        EmailVerificationService $emailVerificationService,
        RouterInterface $router,
    ): Response {
        // If already logged in, send users away from public registration
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('app_homepage');
        }

        // Ensure session is started for CSRF token validation
        $session = $request->getSession();
        $session->start();

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if email already exists
            $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'An account with this email already exists.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            // Check if username already exists
            $existingUsername = $userRepository->findOneBy(['username' => $user->getUsername()]);
            if ($existingUsername) {
                $this->addFlash('error', 'An account with this username already exists.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }
            
            // Public registration: customers/clients only (ROLE_USER)
            $user->setRoles(['ROLE_USER']);

            // Encode (hash) the password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Require email verification
            $user->setIsVerified(false);
            $user->setVerificationToken($emailVerificationService->generateVerificationToken());
            $user->setVerificationTokenExpiresAt((new \DateTimeImmutable())->modify('+24 hours'));

            // Save to the database
            $entityManager->persist($user);
            $entityManager->flush();

            try {
                $verificationUrl = $emailVerificationService->buildVerificationUrl(
                    (string) $user->getVerificationToken(),
                    $router,
                );
                $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
            } catch (\Throwable $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('app_check_email', [
                    'email' => $user->getEmail(),
                ]);
            }

            $this->addFlash('success', 'We sent a verification link to your email. Open it to activate your account.');

            return $this->redirectToRoute('app_check_email', [
                'email' => $user->getEmail(),
            ]);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
