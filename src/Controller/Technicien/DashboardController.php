<?php

namespace App\Controller\Technicien;

use App\Entity\Utilisateur;
use App\Repository\InterventionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_TECHNICIEN')]
final class DashboardController extends AbstractController
{
    #[Route('/technicien/dashboard', name: 'app_technicien_dashboard')]
    public function index(InterventionRepository $interventionRepo): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $interventions  = $interventionRepo->findByTechnicien($user);
        $historique     = $interventionRepo->findHistoriqueTechnicien($user);

        // Prochaine intervention planifiée
        $prochaine = null;
        foreach ($interventions as $i) {
            if ($i->getDatePlanifiee() !== null) {
                $prochaine = $i;
                break;
            }
        }

        return $this->render('technicien/dashboard/index.html.twig', [
            'interventions' => $interventions,
            'historique'    => $historique,
            'prochaine'     => $prochaine,
            'nb_actives'    => count($interventions),
            'nb_terminees'  => count($historique),
        ]);
    }
}
