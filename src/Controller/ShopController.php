<?php

namespace App\Controller;

use App\Entity\CustomerOrder;
use App\Entity\Payment;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CustomerOrderRepository;
use App\Repository\PaymentRepository;
use App\Repository\ProductRepository;
use App\Security\CustomerAccountHelper;
use App\Service\CustomerOrderService;
use App\Service\ShopCartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/shop')]
final class ShopController extends AbstractController
{
    #[Route('/product/{id}', name: 'app_shop_product', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function product(int $id, ProductRepository $productRepository, ShopCartService $cartService): Response
    {
        $product = $productRepository->find($id);
        if (!$product instanceof Product) {
            throw $this->createNotFoundException('Product not found.');
        }

        $inCart = $cartService->getRawItems()[$id] ?? 0;
        $stock = $product->getStock();
        $maxQuantity = $stock === null ? 99 : max(0, $stock - $inCart);

        return $this->render('shop/product.html.twig', [
            'product' => $product,
            'inCart' => $inCart,
            'maxQuantity' => $maxQuantity,
        ]);
    }

    #[Route('/cart/add', name: 'app_shop_cart_add', methods: ['POST'])]
    public function addToCart(Request $request, ShopCartService $cartService): Response
    {
        if (!$this->isCsrfTokenValid('cart_add', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request. Please try again.');

            return $this->redirectToRoute('app_homepage');
        }

        $productId = (int) $request->request->get('productId');
        $quantity = max(1, (int) $request->request->get('quantity', 1));

        try {
            $cartService->add($productId, $quantity);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            $redirect = $request->request->get('redirect');
            if (\is_string($redirect) && str_starts_with($redirect, '/')) {
                return $this->redirect($redirect);
            }

            return $this->redirectToRoute('app_shop_cart');
        }

        $this->addFlash('success', 'Added to cart.');

        $redirect = $request->request->get('redirect');
        if (\is_string($redirect) && str_starts_with($redirect, '/')) {
            return $this->redirect($redirect);
        }

        return $this->redirectToRoute('app_shop_cart');
    }

    #[Route('/cart', name: 'app_shop_cart', methods: ['GET'])]
    public function cart(ShopCartService $cartService): Response
    {
        try {
            $cartService->assertCartWithinStock();
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->render('shop/cart.html.twig', [
            'lines' => $cartService->getLineItems(),
            'subtotal' => $cartService->getSubtotal(),
        ]);
    }

    #[Route('/cart/update/{productId}', name: 'app_shop_cart_update', requirements: ['productId' => '\d+'], methods: ['POST'])]
    public function updateCart(int $productId, Request $request, ShopCartService $cartService): Response
    {
        if (!$this->isCsrfTokenValid('cart_update', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('app_shop_cart');
        }

        try {
            $cartService->setQuantity($productId, (int) $request->request->get('quantity', 1));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_shop_cart');
    }

    #[Route('/cart/remove/{productId}', name: 'app_shop_cart_remove', requirements: ['productId' => '\d+'], methods: ['POST'])]
    public function removeFromCart(int $productId, Request $request, ShopCartService $cartService): Response
    {
        if (!$this->isCsrfTokenValid('cart_remove', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('app_shop_cart');
        }

        $cartService->remove($productId);

        return $this->redirectToRoute('app_shop_cart');
    }

    #[Route('/checkout', name: 'app_shop_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        ShopCartService $cartService,
        CustomerOrderService $orderService,
    ): Response {
        $lines = $cartService->getLineItems();
        if ($lines === []) {
            $this->addFlash('error', 'Your cart is empty.');

            return $this->redirectToRoute('app_homepage', ['_fragment' => 'products']);
        }

        if (!$this->getUser() instanceof User) {
            $this->addFlash('info', 'Please log in to place your order.');

            return $this->redirectToRoute('app_login', [
                'redirect' => $this->generateUrl('app_shop_checkout'),
            ]);
        }

        $user = $this->requireCustomer();

        try {
            $cartService->assertCartWithinStock();
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_shop_cart');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('checkout', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid request.');

                return $this->redirectToRoute('app_shop_checkout');
            }

            $notes = trim((string) $request->request->get('notes', ''));

            try {
                $order = $orderService->createOrder(
                    $user,
                    $cartService->toOrderLineItems(),
                    $notes !== '' ? $notes : null,
                );
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('app_shop_checkout');
            }

            $cartService->clear();
            $this->addFlash('success', 'Order placed! It is now pending in the admin panel.');

            return $this->redirectToRoute('app_shop_order_show', ['id' => $order->getId()]);
        }

        return $this->render('shop/checkout.html.twig', [
            'lines' => $lines,
            'subtotal' => $cartService->getSubtotal(),
        ]);
    }

    #[Route('/orders', name: 'app_shop_orders', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function orders(CustomerOrderRepository $orderRepository): Response
    {
        $user = $this->requireCustomer();

        return $this->render('shop/orders/index.html.twig', [
            'orders' => $orderRepository->findForUser($user),
        ]);
    }

    #[Route('/orders/{id}', name: 'app_shop_order_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function orderShow(int $id, CustomerOrderRepository $orderRepository, PaymentRepository $paymentRepository): Response
    {
        $user = $this->requireCustomer();
        $order = $orderRepository->findOneForUser($id, $user);
        if ($order === null) {
            throw $this->createNotFoundException('Order not found.');
        }

        return $this->render('shop/orders/show.html.twig', [
            'order' => $order,
            'payment' => $paymentRepository->findPaidForOrder($order),
        ]);
    }

    #[Route('/orders/{id}/cancel', name: 'app_shop_order_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancelOrder(
        int $id,
        Request $request,
        CustomerOrderRepository $orderRepository,
        CustomerOrderService $orderService,
    ): Response {
        if (!$this->isCsrfTokenValid('order_cancel', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('app_shop_orders');
        }

        $user = $this->requireCustomer();
        $order = $orderRepository->findOneForUser($id, $user);
        if ($order === null) {
            throw $this->createNotFoundException('Order not found.');
        }

        try {
            $orderService->cancelOrder($order);
            $this->addFlash('success', 'Order cancelled. Stock has been restored.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_shop_order_show', ['id' => $id]);
    }

    #[Route('/orders/{id}/pay', name: 'app_shop_order_pay', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function payOrder(
        int $id,
        Request $request,
        CustomerOrderRepository $orderRepository,
        PaymentRepository $paymentRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('order_pay', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid request.');

            return $this->redirectToRoute('app_shop_orders');
        }

        $user = $this->requireCustomer();
        $order = $orderRepository->findOneForUser($id, $user);
        if ($order === null) {
            throw $this->createNotFoundException('Order not found.');
        }

        $method = (string) $request->request->get('method', Payment::METHOD_GCASH);
        $allowed = [
            Payment::METHOD_CARD,
            Payment::METHOD_GCASH,
            Payment::METHOD_BANK_TRANSFER,
            Payment::METHOD_CASH,
        ];
        if (!\in_array($method, $allowed, true)) {
            $method = Payment::METHOD_GCASH;
        }

        if ($order->getStatus() !== CustomerOrder::STATUS_PENDING) {
            $this->addFlash('error', 'Only pending orders can be paid.');

            return $this->redirectToRoute('app_shop_order_show', ['id' => $id]);
        }

        if ($paymentRepository->findPaidForOrder($order) !== null) {
            $this->addFlash('error', 'This order has already been paid.');

            return $this->redirectToRoute('app_shop_order_show', ['id' => $id]);
        }

        $payment = new Payment();
        $payment->setUser($user);
        $payment->setCustomerOrder($order);
        $payment->setAmount($order->getTotalAmount());
        $payment->setMethod($method);
        $payment->setStatus(Payment::STATUS_PAID);
        $payment->setPaidAt(new \DateTimeImmutable());
        $order->setStatus(CustomerOrder::STATUS_CONFIRMED);

        $entityManager->persist($payment);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Payment successful. Reference: %s', $payment->getReference()));

        return $this->redirectToRoute('app_shop_order_show', ['id' => $id]);
    }

    #[Route('/payments', name: 'app_shop_payments', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function payments(PaymentRepository $paymentRepository): Response
    {
        $user = $this->requireCustomer();

        return $this->render('shop/payments.html.twig', [
            'payments' => $paymentRepository->findForUser($user),
        ]);
    }

    private function requireCustomer(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!CustomerAccountHelper::isCustomerOnly($user)) {
            throw $this->createAccessDeniedException('Shop orders are for customer accounts only.');
        }

        return $user;
    }
}
