<?php

namespace App\Controller\Admin;

use App\Entity\Intervention;
use App\Entity\Utilisateur;
use App\Enum\StatutInterventionEnum;
use App\Form\AffectationTechnicienType;
use App\Form\InterventionAdminType;
use App\Repository\InterventionRepository;
use App\Repository\MaterielRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/intervention')]
#[IsGranted('ROLE_ADMIN')]
final class InterventionController extends AbstractController
{
    /**
     * Liste toutes les interventions avec filtre par statut
     */
    #[Route('/', name: 'app_admin_intervention_index', methods: ['GET'])]
    public function index(
        Request $request,
        InterventionRepository $repository
    ): Response {
        $statut = $request->query->get('statut');

        $interventions = $statut
            ? $repository->findByStatutString($statut)
            : $repository->findAll();

        return $this->render('admin/intervention/index.html.twig', [
            'interventions' => $interventions,
            'statut_actif'  => $statut,
            'statuts'       => StatutInterventionEnum::cases(),
        ]);
    }

    /**
     * Détail d'une intervention + formulaire d'affectation
     */
    #[Route('/{id}', name: 'app_admin_intervention_show', methods: ['GET', 'POST'])]
    public function show(
        Intervention $intervention,
        Request $request,
        EntityManagerInterface $em,
        MaterielRepository $materielRepo
    ): Response {
        // Capture l'état du matériel AVANT modification (pour restituer le stock)
        $ancienMateriel = [];
        foreach ($intervention->getMaterielInterventions() as $mi) {
            $materielId = $mi->getMateriel()->getId();
            if (!isset($ancienMateriel[$materielId])) {
                $ancienMateriel[$materielId] = ['materiel' => $mi->getMateriel(), 'quantite' => 0];
            }
            $ancienMateriel[$materielId]['quantite'] += $mi->getQuantite();
        }

        $dejaPlanifiee = $intervention->getStatut() === StatutInterventionEnum::PLANIFIEE;

        $affectationForm = $this->createForm(AffectationTechnicienType::class, $intervention);
        $affectationForm->handleRequest($request);

        if ($affectationForm->isSubmitted() && $affectationForm->isValid()) {

            // Restitue l'ancien stock avant de recalculer
            foreach ($ancienMateriel as $data) {
                $data['materiel']->setQuantiteStock($data['materiel']->getQuantiteStock() + $data['quantite']);
            }

            // Vérifie le nouveau stock disponible
            foreach ($intervention->getMaterielInterventions() as $mi) {
                if ($mi->getQuantite() > $mi->getMateriel()->getQuantiteStock()) {
                    $this->addFlash('error', sprintf(
                        'Stock insuffisant pour "%s" (demandé : %d, disponible : %d).',
                        $mi->getMateriel()->getNom(),
                        $mi->getQuantite(),
                        $mi->getMateriel()->getQuantiteStock()
                    ));
                    return $this->redirectToRoute('app_admin_intervention_show', ['id' => $intervention->getId()]);
                }
            }

            // Déduit le nouveau stock
            foreach ($intervention->getMaterielInterventions() as $mi) {
                $mi->getMateriel()->setQuantiteStock($mi->getMateriel()->getQuantiteStock() - $mi->getQuantite());
            }

            $intervention->setStatut(StatutInterventionEnum::PLANIFIEE);
            $em->flush();

            $this->addFlash('success', $dejaPlanifiee
                ? 'Affectation mise à jour avec succès.'
                : 'Technicien affecté et intervention planifiée.'
            );
            return $this->redirectToRoute('app_admin_intervention_show', ['id' => $intervention->getId()]);
        }

        return $this->render('admin/intervention/show.html.twig', [
            'intervention'          => $intervention,
            'affectationForm'       => $affectationForm,
            'materiels_disponibles' => $materielRepo->findAll(),
        ]);
    }

    /**
     * Accepter une demande
     */
    #[Route('/{id}/accepter', name: 'app_admin_intervention_accepter', methods: ['POST'])]
    public function accepter(
        Intervention $intervention,
        EntityManagerInterface $em
    ): Response {
        if ($intervention->getStatut() !== StatutInterventionEnum::EN_ATTENTE) {
            $this->addFlash('error', 'Cette intervention ne peut pas être acceptée.');
            return $this->redirectToRoute('app_admin_intervention_show', ['id' => $intervention->getId()]);
        }

        $intervention->setStatut(StatutInterventionEnum::ACCEPTEE);
        $em->flush();

        $this->addFlash('success', 'La demande a été acceptée.');
        return $this->redirectToRoute('app_admin_intervention_show', ['id' => $intervention->getId()]);
    }

    /**
     * Refuser une demande
     */
    #[Route('/{id}/refuser', name: 'app_admin_intervention_refuser', methods: ['POST'])]
    public function refuser(
        Intervention $intervention,
        EntityManagerInterface $em
    ): Response {
        if ($intervention->getStatut() !== StatutInterventionEnum::EN_ATTENTE) {
            $this->addFlash('error', 'Cette intervention ne peut pas être refusée.');
            return $this->redirectToRoute('app_admin_intervention_show', ['id' => $intervention->getId()]);
        }

        $intervention->setStatut(StatutInterventionEnum::REFUSEE);
        $em->flush();

        $this->addFlash('success', 'La demande a été refusée.');
        return $this->redirectToRoute('app_admin_intervention_index');
    }

    /**
     * Modifier une intervention (statut, description)
     */
    #[Route('/{id}/edit', name: 'app_admin_intervention_edit', methods: ['GET', 'POST'])]
    public function edit(
        Intervention $intervention,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(InterventionAdminType::class, $intervention);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Intervention mise à jour.');
            return $this->redirectToRoute('app_admin_intervention_show', ['id' => $intervention->getId()]);
        }

        return $this->render('admin/intervention/edit.html.twig', [
            'form'         => $form,
            'intervention' => $intervention,
        ]);
    }
}