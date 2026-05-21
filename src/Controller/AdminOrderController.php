<?php

namespace App\Controller;

use App\Entity\CustomerOrder;
use App\Repository\CustomerOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/orders')]
final class AdminOrderController extends AbstractController
{
    #[Route('', name: 'app_admin_orders', methods: ['GET'])]
    public function index(Request $request, CustomerOrderRepository $orderRepository): Response
    {
        $status = trim((string) $request->query->get('status', ''));
        $orders = $orderRepository->findForAdmin($status !== '' ? $status : null);

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders,
            'statusFilter' => $status,
            'pendingCount' => $orderRepository->countByStatus(CustomerOrder::STATUS_PENDING),
            'confirmedCount' => $orderRepository->countByStatus(CustomerOrder::STATUS_CONFIRMED),
            'completedCount' => $orderRepository->countByStatus(CustomerOrder::STATUS_COMPLETED),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_order_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, CustomerOrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findOneForAdmin($id);
        if ($order === null) {
            throw $this->createNotFoundException('Order not found.');
        }

        return $this->render('admin/orders/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/status', name: 'app_admin_order_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateStatus(
        int $id,
        Request $request,
        CustomerOrderRepository $orderRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $order = $orderRepository->findOneForAdmin($id);
        if ($order === null) {
            throw $this->createNotFoundException('Order not found.');
        }

        if (!$this->isCsrfTokenValid('order_status', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid security token.');
        }

        $newStatus = (string) $request->request->get('status', '');
        $allowed = [
            CustomerOrder::STATUS_CONFIRMED,
            CustomerOrder::STATUS_COMPLETED,
        ];

        if (!\in_array($newStatus, $allowed, true)) {
            $this->addFlash('error', 'Invalid status update.');

            return $this->redirectToRoute('app_admin_order_show', ['id' => $id]);
        }

        $current = $order->getStatus();
        if ($current === CustomerOrder::STATUS_CANCELLED) {
            $this->addFlash('error', 'Cancelled orders cannot be updated.');

            return $this->redirectToRoute('app_admin_order_show', ['id' => $id]);
        }

        if ($newStatus === CustomerOrder::STATUS_CONFIRMED && $current !== CustomerOrder::STATUS_PENDING) {
            $this->addFlash('error', 'Only pending orders can be marked as received.');

            return $this->redirectToRoute('app_admin_order_show', ['id' => $id]);
        }

        if ($newStatus === CustomerOrder::STATUS_COMPLETED && !\in_array($current, [CustomerOrder::STATUS_PENDING, CustomerOrder::STATUS_CONFIRMED], true)) {
            $this->addFlash('error', 'This order cannot be marked completed.');

            return $this->redirectToRoute('app_admin_order_show', ['id' => $id]);
        }

        $order->setStatus($newStatus);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Order #%d marked as %s.', $order->getId(), $newStatus));

        return $this->redirectToRoute('app_admin_order_show', ['id' => $id]);
    }
}
