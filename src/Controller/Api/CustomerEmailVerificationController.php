<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customer')]
final class CustomerEmailVerificationController extends AbstractController
{
    #[Route('/resend-verification', name: 'api_customer_resend_verification', methods: ['POST'])]
    public function resendVerification(
        Request $request,
        UserRepository $userRepository,
        EmailVerificationService $emailVerificationService,
        RouterInterface $router,
        ValidatorInterface $validator,
    ): JsonResponse {
        $payload = $this->decodeJson($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $violations = $validator->validate($payload, new Assert\Collection([
            'email' => [new Assert\NotBlank(), new Assert\Email()],
        ]));

        if (\count($violations) > 0) {
            return $this->validationError($violations);
        }

        $email = strtolower(trim((string) $payload['email']));
        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user === null) {
            return $this->json(['message' => 'If an unverified account exists, a new verification link has been sent.']);
        }

        if ($user->isVerified()) {
            return $this->json(['message' => 'This email is already verified. You can sign in.']);
        }

        try {
            $emailVerificationService->issueAndSendVerification($user, $router);
        } catch (\Throwable) {
            return $this->json(['message' => 'Unable to send verification email right now. Please try again later.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json(['message' => 'A new verification link has been sent to your email.']);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function decodeJson(Request $request): array|JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['message' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        return $data;
    }

    private function validationError(\Symfony\Component\Validator\ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $this->json(['message' => 'Validation failed.', 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
