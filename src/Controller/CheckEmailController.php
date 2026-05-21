<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

final class CheckEmailController extends AbstractController
{
    #[Route('/check-email', name: 'app_check_email', methods: ['GET'])]
    #[Route('/verify-email-code', name: 'app_verify_email_code', methods: ['GET'])]
    public function checkEmail(Request $request): Response
    {
        return $this->render('registration/check_email.html.twig', [
            'email' => strtolower(trim((string) $request->query->get('email', ''))),
        ]);
    }

    #[Route('/resend-verification', name: 'app_resend_verification', methods: ['POST'])]
    public function resend(
        Request $request,
        UserRepository $userRepository,
        EmailVerificationService $emailVerificationService,
        RouterInterface $router,
    ): Response {
        $email = strtolower(trim((string) $request->request->get('email', '')));
        if ($email === '') {
            $this->addFlash('error', 'Email is required.');
            return $this->redirectToRoute('app_check_email');
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if ($user === null || $user->isVerified()) {
            $this->addFlash('success', 'If an unverified account exists, a new verification link has been sent.');
            return $this->redirectToRoute('app_check_email', ['email' => $email]);
        }

        try {
            $emailVerificationService->issueAndSendVerification($user, $router);
            $this->addFlash('success', 'A new verification link has been sent. Check your inbox and spam folder.');
        } catch (\Throwable $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_check_email', ['email' => $email]);
    }
}
