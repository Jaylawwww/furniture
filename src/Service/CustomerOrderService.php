<?php

namespace App\Service;

use App\Entity\CustomerOrder;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CustomerOrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
    ) {
    }

    /**
     * @param list<array{productId: int, quantity: int}> $lineItems
     */
    public function createOrder(User $user, array $lineItems, ?string $notes = null): CustomerOrder
    {
        if ($lineItems === []) {
            throw new \InvalidArgumentException('Order must contain at least one item.');
        }

        $order = new CustomerOrder();
        $order->setUser($user);
        $order->setNotes($notes);
        $order->setStatus(CustomerOrder::STATUS_PENDING);

        foreach ($lineItems as $line) {
            $productId = (int) ($line['productId'] ?? 0);
            $quantity = (int) ($line['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                throw new \InvalidArgumentException('Each item requires a positive productId and quantity.');
            }

            $product = $this->productRepository->find($productId);
            if (!$product instanceof Product) {
                throw new \InvalidArgumentException(sprintf('Product %d not found.', $productId));
            }

            $stock = $product->getStock();
            if ($stock !== null && $stock < $quantity) {
                throw new \InvalidArgumentException(sprintf('Insufficient stock for "%s". Available: %d.', $product->getName(), $stock));
            }

            $item = new OrderItem();
            $item->setProduct($product);
            $item->setQuantity($quantity);
            $item->setUnitPrice(number_format((float) $product->getPrice(), 2, '.', ''));
            $order->addItem($item);

            if ($stock !== null) {
                $product->setStock($stock - $quantity);
            }
        }

        $order->recalculateTotal();
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    public function cancelOrder(CustomerOrder $order): void
    {
        if ($order->getStatus() !== CustomerOrder::STATUS_PENDING) {
            throw new \InvalidArgumentException('Only pending orders can be cancelled.');
        }

        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            if ($product === null) {
                continue;
            }
            $stock = $product->getStock();
            if ($stock !== null) {
                $product->setStock($stock + $item->getQuantity());
            }
        }

        $order->setStatus(CustomerOrder::STATUS_CANCELLED);
        $this->entityManager->flush();
    }
}
