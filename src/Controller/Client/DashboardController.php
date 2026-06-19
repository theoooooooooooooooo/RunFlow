<?php

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/client/dashboard', name: 'app_client_dashboard')]
    public function index(): Response
    {
        return $this->render('client/dashboard/index.html.twig', [
            'controller_name' => 'Client/DashboardController',
        ]);
    }
}
