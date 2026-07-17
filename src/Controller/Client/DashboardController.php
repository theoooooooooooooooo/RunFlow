<?php

namespace App\Controller\Client;

use App\Entity\Utilisateur;
use App\Repository\InterventionRepository;
use App\Enum\StatutInterventionEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLIENT')]
final class DashboardController extends AbstractController
{
    #[Route('/client', name: 'app_client_dashboard')]
    public function index(InterventionRepository $interventionRepo): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $interventions = $interventionRepo->findByClient($user);

        // Compteurs par statut pour ce client
        $compteurs = [
            'en_attente' => 0,
            'en_cours'   => 0,
            'terminee'   => 0,
        ];
        foreach ($interventions as $i) {
            $s = $i->getStatut()->value;
            if (isset($compteurs[$s])) {
                $compteurs[$s]++;
            }
        }

        return $this->render('client/dashboard/index.html.twig', [
            'interventions' => array_slice($interventions, 0, 5), // 5 dernières
            'compteurs'     => $compteurs,
            'total'         => count($interventions),
        ]);
    }
}
