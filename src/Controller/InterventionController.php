<?php

namespace App\Controller;

use App\Entity\Adresse;
use App\Entity\Intervention;
use App\Entity\Utilisateur;
use App\Enum\StatutInterventionEnum;
use App\Form\InterventionClientType;
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
     * Interventions du client connecté
     */
    #[Route('/mes-demandes', name: 'app_intervention_client', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function clientInterventions(InterventionRepository $repository): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        return $this->render('intervention/client_index.html.twig', [
            'interventions' => $repository->findByClient($user),
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

        return $this->render('intervention/technicien_index.html.twig', [
            'interventions' => $repository->findByTechnicien($user),
        ]);
    }

    /**
     * Création d'une intervention (Client)
     */
    #[Route('/new', name: 'app_intervention_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLIENT')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $intervention = new Intervention();
        $form = $this->createForm(InterventionClientType::class, $intervention);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adresse = new Adresse();
            $adresse->setRue($form->get('adresse_rue')->getData());
            $adresse->setVille($form->get('adresse_ville')->getData());
            $adresse->setCodePostal($form->get('adresse_code_postal')->getData());
            $adresse->setComplementAdresse($form->get('adresse_complement')->getData());
            $em->persist($adresse);

            $intervention->setAdresse($adresse);
            $intervention->setClient($user);
            $intervention->setDateDemande(new \DateTimeImmutable());
            $intervention->setStatut(StatutInterventionEnum::EN_ATTENTE);

            $em->persist($intervention);
            $em->flush();

            $this->addFlash('success', 'Votre demande a bien été envoyée.');
            return $this->redirectToRoute('app_client_dashboard');
        }

        return $this->render('intervention/new.html.twig', [
            'form'         => $form,
            'intervention' => $intervention,
        ]);
    }

    /**
     * Détail d'une intervention (Client + Technicien)
     */
    #[Route('/{id}', name: 'app_intervention_show', methods: ['GET'])]
    public function show(Intervention $intervention): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN')) {
            if ($this->isGranted('ROLE_CLIENT') && $intervention->getClient() !== $user) {
                throw $this->createAccessDeniedException();
            }
            if ($this->isGranted('ROLE_TECHNICIEN') && $intervention->getTechnicien() !== $user) {
                throw $this->createAccessDeniedException();
            }
        }

        return $this->render('intervention/show.html.twig', [
            'intervention' => $intervention,
        ]);
    }
}