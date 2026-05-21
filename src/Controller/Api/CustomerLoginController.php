<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\CustomerAccountHelper;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use App\Security\UserChecker;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customer')]
final class CustomerLoginController extends AbstractController
{
    #[Route('/login', name: 'api_customer_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        UserChecker $userChecker,
        JWTTokenManagerInterface $jwtManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['message' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $violations = $validator->validate($data, new Assert\Collection([
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'password' => [new Assert\NotBlank()],
        ]));

        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json(['message' => 'Validation failed.', 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $email = strtolower(trim((string) $data['email']));
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user instanceof User || !$passwordHasher->isPasswordValid($user, (string) $data['password'])) {
            return $this->json(['message' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!CustomerAccountHelper::isCustomerOnly($user)) {
            return $this->json([
                'message' => 'Admin and staff accounts cannot sign in on the customer app. Please use the web admin panel.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $userChecker->checkPreAuth($user);
            $userChecker->checkPostAuth($user);
        } catch (CustomUserMessageAccountStatusException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'token' => $jwtManager->create($user),
        ]);
    }
}
