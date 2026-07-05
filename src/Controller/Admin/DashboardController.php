<?php

namespace App\Controller\Admin;

use App\Repository\InterventionRepository;
use App\Repository\MaterielRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(
        InterventionRepository $interventionRepo,
        UtilisateurRepository $utilisateurRepo,
        MaterielRepository $materielRepo,
    ): Response {
        $statuts = $interventionRepo->countByStatut();

        return $this->render('admin/dashboard/index.html.twig', [
            'statuts'              => $statuts,
            'nb_en_attente'        => $statuts['en_attente'] ?? 0,
            'nb_techniciens'       => $utilisateurRepo->countTechniciens(),
            'nb_clients'           => $utilisateurRepo->countClients(),
            'nb_stock_critique'    => count($materielRepo->findStockCritique()),
            'dernieres'            => $interventionRepo->findDernieres(5),
            'en_attente'           => $interventionRepo->findEnAttente(),
            'stock_critique'       => $materielRepo->findStockCritique(),
            'comptes_anonymises'   => $utilisateurRepo->findRecemmentAnonymises(), // ← ajouté
        ]);
    }
}
