<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class ShopCartService
{
    private const SESSION_KEY = 'shop_cart';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductRepository $productRepository,
    ) {
    }

    /**
     * @return array<int, int> productId => quantity
     */
    public function getRawItems(): array
    {
        $session = $this->getSession();
        if ($session === null) {
            return [];
        }

        $items = $session->get(self::SESSION_KEY, []);
        if (!\is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $productId => $quantity) {
            $id = (int) $productId;
            $qty = (int) $quantity;
            if ($id > 0 && $qty > 0) {
                $normalized[$id] = $qty;
            }
        }

        return $normalized;
    }

    public function getItemCount(): int
    {
        return array_sum($this->getRawItems());
    }

    public function add(int $productId, int $quantity = 1): void
    {
        if ($productId <= 0 || $quantity <= 0) {
            return;
        }

        $items = $this->getRawItems();
        $items[$productId] = ($items[$productId] ?? 0) + $quantity;
        $this->save($items);
    }

    public function setQuantity(int $productId, int $quantity): void
    {
        $items = $this->getRawItems();
        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $quantity;
        }
        $this->save($items);
    }

    public function remove(int $productId): void
    {
        $items = $this->getRawItems();
        unset($items[$productId]);
        $this->save($items);
    }

    public function clear(): void
    {
        $session = $this->getSession();
        $session?->remove(self::SESSION_KEY);
    }

    /**
     * @return list<array{product: Product, quantity: int, lineTotal: float}>
     */
    public function getLineItems(): array
    {
        $lines = [];
        foreach ($this->getRawItems() as $productId => $quantity) {
            $product = $this->productRepository->find($productId);
            if (!$product instanceof Product) {
                continue;
            }

            $unit = (float) $product->getPrice();
            $lines[] = [
                'product' => $product,
                'quantity' => $quantity,
                'lineTotal' => $unit * $quantity,
            ];
        }

        return $lines;
    }

    public function getSubtotal(): float
    {
        $total = 0.0;
        foreach ($this->getLineItems() as $line) {
            $total += $line['lineTotal'];
        }

        return $total;
    }

    /**
     * @return list<array{productId: int, quantity: int}>
     */
    public function toOrderLineItems(): array
    {
        $result = [];
        foreach ($this->getRawItems() as $productId => $quantity) {
            $result[] = [
                'productId' => $productId,
                'quantity' => $quantity,
            ];
        }

        return $result;
    }

    /**
     * @param array<int, int> $items
     */
    private function save(array $items): void
    {
        $session = $this->getSession();
        $session?->set(self::SESSION_KEY, $items);
    }

    private function getSession(): ?SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null || !$request->hasSession()) {
            return null;
        }

        return $request->getSession();
    }
}
