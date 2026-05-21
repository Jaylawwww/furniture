<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutController extends AbstractController
{
    #[Route('/about', name: 'app_about')]
    public function index(): Response
    {
        return $this->render('about/index.html.twig');
    }

    #[Route('/about/team', name: 'app_about_team')]
    public function team(): Response
    {
        return $this->render('about/team.html.twig');
    }

    #[Route('/about/team/design', name: 'app_about_team_design')]
    public function teamDesign(): Response
    {
        return $this->render('about/team_design.html.twig');
    }

    #[Route('/about/team/operations', name: 'app_about_team_operations')]
    public function teamOperations(): Response
    {
        return $this->render('about/team_operations.html.twig');
    }

    #[Route('/about/team/quality', name: 'app_about_team_quality')]
    public function teamQuality(): Response
    {
        return $this->render('about/team_quality.html.twig');
    }
}

