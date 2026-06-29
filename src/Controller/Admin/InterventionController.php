<?php

namespace App\Controller\Admin;

use App\Entity\Intervention;
use App\Entity\Utilisateur;
use App\Enum\StatutInterventionEnum;
use App\Form\InterventionAdminType;
use App\Form\AffectationTechnicienType;
use App\Repository\InterventionRepository;
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
        EntityManagerInterface $em
    ): Response {
        $affectationForm = $this->createForm(AffectationTechnicienType::class, $intervention);
        $affectationForm->handleRequest($request);

        if ($affectationForm->isSubmitted() && $affectationForm->isValid()) {
            $intervention->setStatut(StatutInterventionEnum::PLANIFIEE);
            $em->flush();

            $this->addFlash('success', 'Technicien affecté et intervention planifiée.');
            return $this->redirectToRoute('app_admin_intervention_show', ['id' => $intervention->getId()]);
        }

        return $this->render('admin/intervention/show.html.twig', [
            'intervention'    => $intervention,
            'affectationForm' => $affectationForm,
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