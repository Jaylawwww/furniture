<?php

namespace App\Twig;

use App\Entity\CustomerOrder;
use App\Repository\CustomerOrderRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AdminExtension extends AbstractExtension
{
    public function __construct(
        private readonly CustomerOrderRepository $orderRepository,
        private readonly Security $security,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pending_order_count', $this->getPendingOrderCount(...)),
        ];
    }

    public function getPendingOrderCount(): int
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return 0;
        }

        return $this->orderRepository->countByStatus(CustomerOrder::STATUS_PENDING);
    }
}
