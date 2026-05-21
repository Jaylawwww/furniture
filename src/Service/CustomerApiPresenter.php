<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\Request;

final class CustomerApiPresenter
{
    public function presentProduct(Product $product, Request $request): array
    {
        $category = $product->getCategory();

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'stock' => $product->getStock(),
            'image' => $product->getImage(),
            'imageUrl' => $this->imageUrl($product->getImage(), $request),
            'category' => $category ? $this->presentCategory($category) : null,
        ];
    }

    public function presentCategory(Category $category): array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
        ];
    }

    public function presentUser(\App\Entity\User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
            'status' => $user->getStatus(),
            'createdAt' => $user->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    public function presentOrder(\App\Entity\CustomerOrder $order, Request $request): array
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            $items[] = [
                'id' => $item->getId(),
                'productId' => $product?->getId(),
                'productName' => $product?->getName(),
                'quantity' => $item->getQuantity(),
                'unitPrice' => $item->getUnitPrice(),
                'lineTotal' => $item->getLineTotal(),
                'imageUrl' => $product ? $this->imageUrl($product->getImage(), $request) : null,
            ];
        }

        return [
            'id' => $order->getId(),
            'status' => $order->getStatus(),
            'totalAmount' => $order->getTotalAmount(),
            'notes' => $order->getNotes(),
            'items' => $items,
            'createdAt' => $order->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $order->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    public function presentBooking(\App\Entity\Booking $booking): array
    {
        return [
            'id' => $booking->getId(),
            'scheduledAt' => $booking->getScheduledAt()->format(\DateTimeInterface::ATOM),
            'status' => $booking->getStatus(),
            'notes' => $booking->getNotes(),
            'contactPhone' => $booking->getContactPhone(),
            'createdAt' => $booking->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $booking->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    public function presentPayment(\App\Entity\Payment $payment): array
    {
        return [
            'id' => $payment->getId(),
            'orderId' => $payment->getCustomerOrder()?->getId(),
            'amount' => $payment->getAmount(),
            'method' => $payment->getMethod(),
            'status' => $payment->getStatus(),
            'reference' => $payment->getReference(),
            'paidAt' => $payment->getPaidAt()?->format(\DateTimeInterface::ATOM),
            'createdAt' => $payment->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function imageUrl(?string $filename, Request $request): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        return $request->getSchemeAndHttpHost().'/img/'.$filename;
    }
}
