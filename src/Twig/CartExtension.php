<?php

namespace App\Twig;

use App\Service\ShopCartService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CartExtension extends AbstractExtension
{
    public function __construct(
        private readonly ShopCartService $cartService,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_item_count', $this->cartService->getItemCount(...)),
        ];
    }
}
