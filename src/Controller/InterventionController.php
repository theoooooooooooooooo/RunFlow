<?php

namespace App\Controller;

use App\Entity\Intervention;
use App\Entity\Utilisateur;
use App\Enum\StatutInterventionEnum;
use App\Form\InterventionType;
use App\Repository\InterventionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/intervention')]
final class InterventionController extends AbstractController
{
    /**
     * Liste de toutes les interventions (Administrateur)
     */
    #[Route('/', name: 'app_intervention_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(InterventionRepository $repository): Response
    {
        return $this->render('intervention/index.html.twig', [
            'interventions' => $repository->findAll(),
        ]);
    }

    /**
     * Interventions du client connecté
     */
    #[Route('/mes-demandes', name: 'app_intervention_client', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function clientInterventions(InterventionRepository $repository): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        return $this->render('intervention/index.html.twig', [
            'interventions' => $repository->findBy(
                ['client' => $user],
                ['date_demande' => 'DESC']
            ),
        ]);
    }

    /**
     * Interventions du technicien connecté
     */
    #[Route('/mes-interventions', name: 'app_intervention_technicien', methods: ['GET'])]
    #[IsGranted('ROLE_TECHNICIEN')]
    public function technicienInterventions(InterventionRepository $repository): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        return $this->render('intervention/index.html.twig', [
            'interventions' => $repository->findBy(
                ['technicien' => $user],
                ['date_planifiee' => 'ASC']
            ),
        ]);
    }

    /**
     * Création d'une intervention (Client)
     */
    #[Route('/new', name: 'app_intervention_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLIENT')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $intervention = new Intervention();

        $form = $this->createForm(InterventionType::class, $intervention);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $intervention->setClient($user);

            $intervention->setDateDemande(new \DateTimeImmutable());

            $intervention->setStatut(StatutInterventionEnum::EN_ATTENTE);

            $entityManager->persist($intervention);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande d\'intervention a bien été créée.');

            return $this->redirectToRoute('app_intervention_client');
        }

        return $this->render('intervention/new.html.twig', [
            'form' => $form,
            'intervention' => $intervention,
        ]);
    }

    /**
     * Détail d'une intervention
     */
    #[Route('/{id}', name: 'app_intervention_show', methods: ['GET'])]
    public function show(Intervention $intervention): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN')) {

            if (
                $this->isGranted('ROLE_CLIENT')
                && $intervention->getClient() !== $user
            ) {
                throw $this->createAccessDeniedException();
            }

            if (
                $this->isGranted('ROLE_TECHNICIEN')
                && $intervention->getTechnicien() !== $user
            ) {
                throw $this->createAccessDeniedException();
            }
        }

        return $this->render('intervention/show.html.twig', [
            'intervention' => $intervention,
        ]);
    }

    /**
     * Modification d'une intervention (Administrateur)
     */
    #[Route('/{id}/edit', name: 'app_intervention_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        Intervention $intervention,
        EntityManagerInterface $entityManager
    ): Response {

        $form = $this->createForm(InterventionType::class, $intervention);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->flush();

            $this->addFlash('success', 'Intervention mise à jour.');

            return $this->redirectToRoute('app_intervention_index');
        }

        return $this->render('intervention/edit.html.twig', [
            'form' => $form,
            'intervention' => $intervention,
        ]);
    }
}