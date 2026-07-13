<?php

namespace App\Controller\Technicien;

use App\Entity\Commentaire;
use App\Entity\Intervention;
use App\Entity\Utilisateur;
use App\Enum\StatutInterventionEnum;
use App\Form\CommentaireType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/technicien/intervention')]
#[IsGranted('ROLE_TECHNICIEN')]
final class InterventionController extends AbstractController
{
    /**
     * Vérifie que l'intervention appartient bien au technicien connecté
     */
    private function verifierProprietaire(Intervention $intervention): void
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        if ($intervention->getTechnicien() !== $user) {
            throw $this->createAccessDeniedException('Cette intervention ne vous est pas assignée.');
        }
    }

    /**
     * Démarrer l'intervention (PLANIFIEE → EN_COURS)
     */
    #[Route('/{id}/demarrer', name: 'app_technicien_intervention_demarrer', methods: ['POST'])]
    public function demarrer(
        Intervention $intervention,
        EntityManagerInterface $em
    ): Response {
        $this->verifierProprietaire($intervention);

        if ($intervention->getStatut() !== StatutInterventionEnum::PLANIFIEE) {
            $this->addFlash('error', 'Cette intervention ne peut pas être démarrée.');
            return $this->redirectToRoute('app_intervention_show', ['id' => $intervention->getId()]);
        }

        $intervention->setStatut(StatutInterventionEnum::EN_COURS);
        $em->flush();

        $this->addFlash('success', 'Intervention démarrée.');
        return $this->redirectToRoute('app_intervention_show', ['id' => $intervention->getId()]);
    }

    /**
     * Ajouter ou modifier le commentaire + valider l'intervention (EN_COURS → TERMINEE)
     */
    #[Route('/{id}/valider', name: 'app_technicien_intervention_valider', methods: ['GET', 'POST'])]
    public function valider(
        Intervention $intervention,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->verifierProprietaire($intervention);

        if ($intervention->getStatut() !== StatutInterventionEnum::EN_COURS) {
            $this->addFlash('error', 'Cette intervention ne peut pas être validée pour le moment.');
            return $this->redirectToRoute('app_intervention_show', ['id' => $intervention->getId()]);
        }

        /** @var Utilisateur $user */
        $user = $this->getUser();

        $commentaire = $intervention->getCommentaire() ?? new Commentaire();

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire->setDate(new \DateTimeImmutable());
            $commentaire->setAuteur($user);
            $commentaire->setIntervention($intervention);

            $intervention->setCommentaire($commentaire);
            $intervention->setStatut(StatutInterventionEnum::TERMINEE);

            $em->persist($commentaire);
            $em->flush();

            $this->addFlash('success', 'Intervention validée et terminée avec succès.');
            return $this->redirectToRoute('app_intervention_technicien');
        }

        return $this->render('technicien/intervention/valider.html.twig', [
            'intervention' => $intervention,
            'form'         => $form,
        ]);
    }
}