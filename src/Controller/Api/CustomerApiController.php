<?php

namespace App\Controller\Api;

use App\Entity\Booking;
use App\Entity\CustomerOrder;
use App\Entity\Payment;
use App\Entity\User;
use App\Repository\BookingRepository;
use App\Repository\CategoryRepository;
use App\Repository\CustomerOrderRepository;
use App\Repository\PaymentRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Service\CustomerApiPresenter;
use App\Service\CustomerOrderService;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ContactMailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Security\CustomerAccountVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customer')]
final class CustomerApiController extends AbstractController
{
    public function __construct(
        private readonly CustomerApiPresenter $presenter,
    ) {
    }

    #[Route('/products', name: 'api_customer_products', methods: ['GET'])]
    public function products(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $categoryId = $request->query->getInt('category');
        $search = trim((string) $request->query->get('q', ''));

        $products = $productRepository->findForCustomerCatalog(
            $categoryId > 0 ? $categoryId : null,
            $search !== '' ? $search : null,
        );

        $items = array_map(
            fn ($product) => $this->presenter->presentProduct($product, $request),
            $products,
        );

        return $this->json(['items' => $items, 'total' => \count($items)]);
    }

    #[Route('/products/{id}', name: 'api_customer_product_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function product(int $id, Request $request, ProductRepository $productRepository): JsonResponse
    {
        $product = $productRepository->find($id);
        if ($product === null) {
            return $this->json(['message' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->presenter->presentProduct($product, $request));
    }

    #[Route('/categories', name: 'api_customer_categories', methods: ['GET'])]
    public function categories(CategoryRepository $categoryRepository): JsonResponse
    {
        $items = array_map(
            fn ($category) => $this->presenter->presentCategory($category),
            $categoryRepository->findBy([], ['Name' => 'ASC']),
        );

        return $this->json(['items' => $items, 'total' => \count($items)]);
    }

    #[Route('/register', name: 'api_customer_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        EmailVerificationService $emailVerificationService,
        RouterInterface $router,
        ValidatorInterface $validator,
        ParameterBagInterface $params,
    ): JsonResponse {
        $payload = $this->decodeJson($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $violations = $validator->validate($payload, new Assert\Collection([
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'username' => [new Assert\NotBlank(), new Assert\Length(min: 3, max: 50)],
            'password' => [new Assert\NotBlank(), new Assert\Length(min: 8)],
            'name' => new Assert\Optional([new Assert\Length(max: 255)]),
        ]));

        if (\count($violations) > 0) {
            return $this->validationError($violations);
        }

        $email = strtolower(trim((string) $payload['email']));
        $username = trim((string) $payload['username']);

        if ($userRepository->findOneBy(['email' => $email])) {
            return $this->json(['message' => 'An account with this email already exists.'], Response::HTTP_CONFLICT);
        }

        if ($userRepository->findOneBy(['username' => $username])) {
            return $this->json(['message' => 'An account with this username already exists.'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setName(isset($payload['name']) ? trim((string) $payload['name']) : null);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, (string) $payload['password']));
        $user->setStatus('active');

        $autoVerify = filter_var($params->get('app.customer_api_auto_verify') ?? true, FILTER_VALIDATE_BOOL);
        if ($autoVerify) {
            $user->setIsVerified(true);
            $user->setVerificationToken(null);
            $user->setVerificationTokenExpiresAt(null);
        } else {
            $user->setIsVerified(false);
            $user->setVerificationToken($emailVerificationService->generateVerificationToken());
            $user->setVerificationTokenExpiresAt((new \DateTimeImmutable())->modify('+24 hours'));
        }

        $entityManager->persist($user);
        $entityManager->flush();

        if (!$autoVerify) {
            try {
                $verificationUrl = $emailVerificationService->buildVerificationUrl(
                    (string) $user->getVerificationToken(),
                    $router,
                );
                $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
            } catch (\Throwable) {
                return $this->json([
                    'message' => 'Account created but we could not send the verification email. Use resend verification from the app.',
                    'requiresVerification' => true,
                    'user' => $this->presenter->presentUser($user),
                ], Response::HTTP_CREATED);
            }
        }

        return $this->json([
            'message' => $autoVerify
                ? 'Account created. You can log in now.'
                : 'Account created. Check your email and tap the verification link before logging in.',
            'requiresVerification' => !$autoVerify,
            'user' => $this->presenter->presentUser($user),
        ], Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'api_customer_me', methods: ['GET'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->presenter->presentUser($user));
    }

    #[Route('/me', name: 'api_customer_me_update', methods: ['PATCH'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function updateProfile(
        Request $request,
        #[CurrentUser] User $user,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        $payload = $this->decodeJson($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $violations = $validator->validate($payload, new Assert\Collection([
            'username' => new Assert\Optional([new Assert\NotBlank(), new Assert\Length(min: 3, max: 50)]),
            'name' => new Assert\Optional([new Assert\Length(max: 255)]),
        ]));

        if (\count($violations) > 0) {
            return $this->validationError($violations);
        }

        if (isset($payload['username'])) {
            $username = trim((string) $payload['username']);
            $existing = $userRepository->findOneBy(['username' => $username]);
            if ($existing && $existing->getId() !== $user->getId()) {
                return $this->json(['message' => 'This username is already taken.'], Response::HTTP_CONFLICT);
            }
            $user->setUsername($username);
        }

        if (\array_key_exists('name', $payload)) {
            $user->setName($payload['name'] !== null ? trim((string) $payload['name']) : null);
        }

        $entityManager->flush();

        return $this->json($this->presenter->presentUser($user));
    }

    #[Route('/change-password', name: 'api_customer_change_password', methods: ['POST'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function changePassword(
        Request $request,
        #[CurrentUser] User $user,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        $payload = $this->decodeJson($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $violations = $validator->validate($payload, new Assert\Collection([
            'currentPassword' => [new Assert\NotBlank()],
            'newPassword' => [new Assert\NotBlank(), new Assert\Length(min: 8)],
            'confirmPassword' => [new Assert\NotBlank()],
        ]));

        if (\count($violations) > 0) {
            return $this->validationError($violations);
        }

        if (!$passwordHasher->isPasswordValid($user, (string) $payload['currentPassword'])) {
            return $this->json(['message' => 'Current password is incorrect.'], Response::HTTP_BAD_REQUEST);
        }

        if ($payload['newPassword'] !== $payload['confirmPassword']) {
            return $this->json(['message' => 'New password and confirmation do not match.'], Response::HTTP_BAD_REQUEST);
        }

        if ($passwordHasher->isPasswordValid($user, (string) $payload['newPassword'])) {
            return $this->json(['message' => 'New password must be different from your current password.'], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($passwordHasher->hashPassword($user, (string) $payload['newPassword']));
        $entityManager->flush();

        return $this->json(['message' => 'Password changed successfully.']);
    }

    #[Route('/contact', name: 'api_customer_contact', methods: ['POST'])]
    public function contact(
        Request $request,
        ContactMailService $contactMailService,
        ValidatorInterface $validator,
    ): JsonResponse {
        $payload = $this->decodeJson($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $violations = $validator->validate($payload, new Assert\Collection([
            'category' => [new Assert\NotBlank(), new Assert\Choice(choices: ['support', 'visit', 'business'])],
            'name' => [new Assert\NotBlank(), new Assert\Length(max: 120)],
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'subject' => [new Assert\NotBlank(), new Assert\Length(max: 150)],
            'message' => [new Assert\NotBlank(), new Assert\Length(min: 10)],
        ]));

        if (\count($violations) > 0) {
            return $this->validationError($violations);
        }

        try {
            $contactMailService->send([
                'category' => (string) $payload['category'],
                'name' => (string) $payload['name'],
                'email' => (string) $payload['email'],
                'subject' => (string) $payload['subject'],
                'message' => (string) $payload['message'],
            ]);
        } catch (\Throwable) {
            return $this->json(['message' => 'Unable to send your message right now. Please try again later.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json(['message' => 'Thanks! Your message has been sent.']);
    }

    #[Route('/orders', name: 'api_customer_orders', methods: ['GET'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function orders(#[CurrentUser] User $user, CustomerOrderRepository $orderRepository, Request $request): JsonResponse
    {
        $items = array_map(
            fn (CustomerOrder $order) => $this->presenter->presentOrder($order, $request),
            $orderRepository->findForUser($user),
        );

        return $this->json(['items' => $items, 'total' => \count($items)]);
    }

    #[Route('/orders', name: 'api_customer_orders_create', methods: ['POST'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function createOrder(
        Request $request,
        #[CurrentUser] User $user,
        CustomerOrderService $orderService,
        ValidatorInterface $validator,
    ): JsonResponse {
        $payload = $this->decodeJson($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $violations = $validator->validate($payload, new Assert\Collection([
            'items' => [
                new Assert\NotBlank(),
                new Assert\Type('array'),
                new Assert\Count(min: 1),
            ],
            'notes' => new Assert\Optional([new Assert\Length(max: 1000)]),
        ]));

        if (\count($violations) > 0) {
            return $this->validationError($violations);
        }

        $lineItems = [];
        foreach ($payload['items'] as $index => $rawItem) {
            if (!\is_array($rawItem)) {
                return $this->json(['message' => 'Validation failed.', 'errors' => ["items[$index]" => 'Each item must be an object.']], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $itemViolations = $validator->validate($rawItem, new Assert\Collection([
                'productId' => [new Assert\NotBlank(), new Assert\Type('integer'), new Assert\Positive()],
                'quantity' => [new Assert\NotBlank(), new Assert\Type('integer'), new Assert\Positive()],
            ]));
            if (\count($itemViolations) > 0) {
                return $this->validationError($itemViolations);
            }
            $lineItems[] = [
                'productId' => (int) $rawItem['productId'],
                'quantity' => (int) $rawItem['quantity'],
            ];
        }

        try {
            $order = $orderService->createOrder(
                $user,
                $lineItems,
                isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'message' => 'Order placed successfully.',
            'order' => $this->presenter->presentOrder($order, $request),
        ], Response::HTTP_CREATED);
    }

    #[Route('/orders/{id}', name: 'api_customer_order_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function orderShow(int $id, #[CurrentUser] User $user, CustomerOrderRepository $orderRepository, Request $request): JsonResponse
    {
        $order = $orderRepository->findOneForUser($id, $user);
        if ($order === null) {
            return $this->json(['message' => 'Order not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->presenter->presentOrder($order, $request));
    }

    #[Route('/orders/{id}', name: 'api_customer_order_cancel', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function cancelOrder(
        int $id,
        Request $request,
        #[CurrentUser] User $user,
        CustomerOrderRepository $orderRepository,
        CustomerOrderService $orderService,
    ): JsonResponse {
        $order = $orderRepository->findOneForUser($id, $user);
        if ($order === null) {
            return $this->json(['message' => 'Order not found.'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->decodeJson($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $action = $payload['action'] ?? 'cancel';
        if ($action !== 'cancel') {
            return $this->json(['message' => 'Unsupported action. Use {"action":"cancel"}.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $orderService->cancelOrder($order);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'message' => 'Order cancelled.',
            'order' => $this->presenter->presentOrder($order, $request),
        ]);
    }

    #[Route('/bookings', name: 'api_customer_bookings', methods: ['GET'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function bookings(#[CurrentUser] User $user, BookingRepository $bookingRepository): JsonResponse
    {
        $items = array_map(
            fn (Booking $booking) => $this->presenter->presentBooking($booking),
            $bookingRepository->findForUser($user),
        );

        return $this->json(['items' => $items, 'total' => \count($items)]);
    }

    #[Route('/bookings', name: 'api_customer_bookings_create', methods: ['POST'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function createBooking(
        Request $request,
        #[CurrentUser] User $user,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        $payload = $this->decodeJson($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $violations = $validator->validate($payload, new Assert\Collection([
            'scheduledAt' => [new Assert\NotBlank(), new Assert\DateTime(\DateTimeInterface::ATOM)],
            'notes' => new Assert\Optional([new Assert\Length(max: 1000)]),
            'contactPhone' => new Assert\Optional([new Assert\Length(max: 30)]),
        ]));

        if (\count($violations) > 0) {
            return $this->validationError($violations);
        }

        try {
            $scheduledAt = new \DateTimeImmutable((string) $payload['scheduledAt']);
        } catch (\Exception) {
            return $this->json(['message' => 'scheduledAt must be a valid ISO-8601 datetime.'], Response::HTTP_BAD_REQUEST);
        }

        if ($scheduledAt <= new \DateTimeImmutable()) {
            return $this->json(['message' => 'Booking must be scheduled in the future.'], Response::HTTP_BAD_REQUEST);
        }

        $booking = new Booking();
        $booking->setUser($user);
        $booking->setScheduledAt($scheduledAt);
        $booking->setNotes(isset($payload['notes']) ? trim((string) $payload['notes']) : null);
        $booking->setContactPhone(isset($payload['contactPhone']) ? trim((string) $payload['contactPhone']) : null);
        $booking->setStatus(Booking::STATUS_PENDING);

        $entityManager->persist($booking);
        $entityManager->flush();

        return $this->json([
            'message' => 'Booking created successfully.',
            'booking' => $this->presenter->presentBooking($booking),
        ], Response::HTTP_CREATED);
    }

    #[Route('/bookings/{id}', name: 'api_customer_booking_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function bookingShow(int $id, #[CurrentUser] User $user, BookingRepository $bookingRepository): JsonResponse
    {
        $booking = $bookingRepository->findOneForUser($id, $user);
        if ($booking === null) {
            return $this->json(['message' => 'Booking not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->presenter->presentBooking($booking));
    }

    #[Route('/bookings/{id}', name: 'api_customer_booking_update', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function updateBooking(
        int $id,
        Request $request,
        #[CurrentUser] User $user,
        BookingRepository $bookingRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        $booking = $bookingRepository->findOneForUser($id, $user);
        if ($booking === null) {
            return $this->json(['message' => 'Booking not found.'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->decodeJson($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $violations = $validator->validate($payload, new Assert\Collection([
            'action' => new Assert\Optional([new Assert\Choice(choices: ['cancel'])]),
            'scheduledAt' => new Assert\Optional([new Assert\DateTime(\DateTimeInterface::ATOM)]),
            'notes' => new Assert\Optional([new Assert\Length(max: 1000)]),
            'contactPhone' => new Assert\Optional([new Assert\Length(max: 30)]),
        ]));

        if (\count($violations) > 0) {
            return $this->validationError($violations);
        }

        if (($payload['action'] ?? null) === 'cancel') {
            if ($booking->getStatus() === Booking::STATUS_COMPLETED) {
                return $this->json(['message' => 'Completed bookings cannot be cancelled.'], Response::HTTP_BAD_REQUEST);
            }
            $booking->setStatus(Booking::STATUS_CANCELLED);
        } else {
            if ($booking->getStatus() !== Booking::STATUS_PENDING) {
                return $this->json(['message' => 'Only pending bookings can be updated.'], Response::HTTP_BAD_REQUEST);
            }

            if (isset($payload['scheduledAt'])) {
                try {
                    $scheduledAt = new \DateTimeImmutable((string) $payload['scheduledAt']);
                } catch (\Exception) {
                    return $this->json(['message' => 'scheduledAt must be a valid ISO-8601 datetime.'], Response::HTTP_BAD_REQUEST);
                }
                if ($scheduledAt <= new \DateTimeImmutable()) {
                    return $this->json(['message' => 'Booking must be scheduled in the future.'], Response::HTTP_BAD_REQUEST);
                }
                $booking->setScheduledAt($scheduledAt);
            }

            if (\array_key_exists('notes', $payload)) {
                $booking->setNotes($payload['notes'] !== null ? trim((string) $payload['notes']) : null);
            }

            if (\array_key_exists('contactPhone', $payload)) {
                $booking->setContactPhone($payload['contactPhone'] !== null ? trim((string) $payload['contactPhone']) : null);
            }
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Booking updated.',
            'booking' => $this->presenter->presentBooking($booking),
        ]);
    }

    #[Route('/payments', name: 'api_customer_payments', methods: ['GET'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function payments(#[CurrentUser] User $user, PaymentRepository $paymentRepository): JsonResponse
    {
        $items = array_map(
            fn (Payment $payment) => $this->presenter->presentPayment($payment),
            $paymentRepository->findForUser($user),
        );

        return $this->json(['items' => $items, 'total' => \count($items)]);
    }

    #[Route('/payments', name: 'api_customer_payments_create', methods: ['POST'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function createPayment(
        Request $request,
        #[CurrentUser] User $user,
        CustomerOrderRepository $orderRepository,
        PaymentRepository $paymentRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
    ): JsonResponse {
        $payload = $this->decodeJson($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $violations = $validator->validate($payload, new Assert\Collection([
            'orderId' => [new Assert\NotBlank(), new Assert\Type('integer'), new Assert\Positive()],
            'method' => [new Assert\NotBlank(), new Assert\Choice(choices: [
                Payment::METHOD_CARD,
                Payment::METHOD_GCASH,
                Payment::METHOD_BANK_TRANSFER,
                Payment::METHOD_CASH,
            ])],
        ]));

        if (\count($violations) > 0) {
            return $this->validationError($violations);
        }

        $order = $orderRepository->findOneForUser((int) $payload['orderId'], $user);
        if ($order === null) {
            return $this->json(['message' => 'Order not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($order->getStatus() !== CustomerOrder::STATUS_PENDING) {
            return $this->json(['message' => 'Only pending orders can be paid.'], Response::HTTP_BAD_REQUEST);
        }

        if ($paymentRepository->findPaidForOrder($order) !== null) {
            return $this->json(['message' => 'This order has already been paid.'], Response::HTTP_CONFLICT);
        }

        $payment = new Payment();
        $payment->setUser($user);
        $payment->setCustomerOrder($order);
        $payment->setAmount($order->getTotalAmount());
        $payment->setMethod((string) $payload['method']);
        $payment->setStatus(Payment::STATUS_PAID);
        $payment->setPaidAt(new \DateTimeImmutable());

        $order->setStatus(CustomerOrder::STATUS_CONFIRMED);

        $entityManager->persist($payment);
        $entityManager->flush();

        return $this->json([
            'message' => 'Payment successful.',
            'payment' => $this->presenter->presentPayment($payment),
        ], Response::HTTP_CREATED);
    }

    #[Route('/payments/{id}', name: 'api_customer_payment_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(CustomerAccountVoter::CUSTOMER_ACCOUNT)]
    public function paymentShow(int $id, #[CurrentUser] User $user, PaymentRepository $paymentRepository): JsonResponse
    {
        $payment = $paymentRepository->findOneForUser($id, $user);
        if ($payment === null) {
            return $this->json(['message' => 'Payment not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->presenter->presentPayment($payment));
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
