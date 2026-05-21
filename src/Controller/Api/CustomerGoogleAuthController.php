<?php

namespace App\Controller\Api;

use App\Security\CustomerAccountHelper;
use App\Service\CustomerApiPresenter;
use App\Service\CustomerGoogleAuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use App\Security\UserChecker;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customer')]
final class CustomerGoogleAuthController extends AbstractController
{
    #[Route('/auth/google', name: 'api_customer_auth_google', methods: ['POST'])]
    public function googleAuth(
        Request $request,
        CustomerGoogleAuthService $googleAuthService,
        CustomerApiPresenter $presenter,
        JWTTokenManagerInterface $jwtManager,
        UserChecker $userChecker,
        ValidatorInterface $validator,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return $this->json(['message' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $violations = $validator->validate($payload, new Assert\Collection([
            'idToken' => [new Assert\NotBlank()],
            'mode' => [new Assert\NotBlank(), new Assert\Choice(choices: ['login', 'register'])],
        ]));

        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json(['message' => 'Validation failed.', 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $googleAuthService->authenticate((string) $payload['idToken'], (string) $payload['mode']);
        } catch (\InvalidArgumentException $e) {
            $status = match (true) {
                str_contains($e->getMessage(), 'No account found') => Response::HTTP_NOT_FOUND,
                str_contains($e->getMessage(), 'already exists') => Response::HTTP_CONFLICT,
                str_contains($e->getMessage(), 'Admin and staff') => Response::HTTP_FORBIDDEN,
                default => Response::HTTP_BAD_REQUEST,
            };

            return $this->json(['message' => $e->getMessage()], $status);
        }

        $user = $result['user'];

        try {
            $userChecker->checkPreAuth($user);
            $userChecker->checkPostAuth($user);
        } catch (CustomUserMessageAccountStatusException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }

        if (!CustomerAccountHelper::isCustomerOnly($user)) {
            return $this->json([
                'message' => 'Admin and staff accounts cannot sign in on the customer app.',
            ], Response::HTTP_FORBIDDEN);
        }

        $message = $result['created']
            ? 'Account created with Google. You are now signed in.'
            : 'Signed in with Google.';

        return $this->json([
            'message' => $message,
            'token' => $jwtManager->create($user),
            'user' => $presenter->presentUser($user),
        ], $result['created'] ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
