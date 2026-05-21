<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomepageController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(ProductRepository $productRepository): Response
    {
        // Fetch all products for public display (limit to 8 most recent)
        $products = $productRepository->findBy([], ['id' => 'DESC'], 8);

        return $this->render('homepage/index.html.twig', [
            'products' => $products,
        ]);
    }
}
