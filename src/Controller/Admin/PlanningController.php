<?php

namespace App\Controller\Admin;

use App\Repository\InterventionRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/planning')]
#[IsGranted('ROLE_ADMIN')]
final class PlanningController extends AbstractController
{
    /**
     * Page planning FullCalendar
     */
    #[Route('/', name: 'app_admin_planning', methods: ['GET'])]
    public function index(UtilisateurRepository $utilisateurRepo): Response
    {
        return $this->render('admin/planning/index.html.twig', [
            'techniciens' => $utilisateurRepo->findTechniciens(),
        ]);
    }

    /**
     * API : créneaux occupés (pour FullCalendar)
     * ?technicien_id=X pour filtrer par technicien
     */
    #[Route('/api/creneaux', name: 'app_admin_planning_creneaux', methods: ['GET'])]
    public function creneaux(
        Request $request,
        InterventionRepository $interventionRepo,
        UtilisateurRepository $utilisateurRepo
    ): JsonResponse {
        $technicienId = $request->query->get('technicien_id');

        if ($technicienId) {
            $technicien = $utilisateurRepo->find($technicienId);
            $interventions = $technicien
                ? $interventionRepo->findByTechnicien($technicien)
                : [];
        } else {
            $interventions = $interventionRepo->findPlanifiees();
        }

        $events = [];
        foreach ($interventions as $intervention) {
            if (!$intervention->getDatePlanifiee()) {
                continue;
            }

            $events[] = [
                'id'    => $intervention->getId(),
                'title' => sprintf(
                    '%s — %s',
                    $intervention->getClient()->getNomComplet(),
                    $intervention->getAdresse()->getVille()
                ),
                'start' => $intervention->getDatePlanifiee()->format('Y-m-d\TH:i:s'),
                'end'   => $intervention->getDateFinPlanifiee()->format('Y-m-d\TH:i:s'), // ← utilise la méthode de l'entité
                'color' => $this->getCouleurTechnicien($intervention->getTechnicien()?->getId()),
                'extendedProps' => [
                    'technicien' => $intervention->getTechnicien()?->getNomComplet(),
                    'adresse'    => (string) $intervention->getAdresse(),
                    'statut'     => $intervention->getStatut()->label(),
                    'duree'      => $intervention->getDureeEstimee() ?? 120,
                ],
            ];
        }

        return new JsonResponse($events);
    }

    /**
     * Couleur par technicien (pour différencier sur le calendrier)
     */
    private function getCouleurTechnicien(?int $id): string
    {
        $couleurs = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444',
            '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16',
        ];

        return $couleurs[$id % count($couleurs)];
    }
}