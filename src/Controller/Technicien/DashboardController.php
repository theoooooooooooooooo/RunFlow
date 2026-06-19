<?php

namespace App\Controller\Technicien;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/technicien/dashboard', name: 'app_technicien_dashboard')]
    public function index(): Response
    {
        return $this->render('technicien/dashboard/index.html.twig', [
            'controller_name' => 'Technicien/DashboardController',
        ]);
    }
}
