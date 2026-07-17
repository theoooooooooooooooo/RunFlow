<?php

namespace App\Controller;

use App\Entity\Adresse;
use App\Entity\Intervention;
use App\Entity\Utilisateur;
use App\Enum\StatutInterventionEnum;
use App\Form\InterventionClientType;
use App\Repository\AdresseRepository;
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
    public function new(Request $request, EntityManagerInterface $em, AdresseRepository $adresseRepo): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $intervention = new Intervention();
        $form = $this->createForm(InterventionClientType::class, $intervention, ['client' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $adresseExistante = $form->get('adresse_existante')->getData();

            if ($adresseExistante) {
                // Réutilisation d'une adresse déjà associée à ce client
                $adresse = $adresseExistante;

            } else {
                // Nouvelle adresse : vérification des champs obligatoires
                $rue          = trim((string) $form->get('adresse_rue')->getData());
                $ville        = trim((string) $form->get('adresse_ville')->getData());
                $codePostal   = trim((string) $form->get('adresse_code_postal')->getData());
                $complement   = $form->get('adresse_complement')->getData();

                if ($rue === '' || $ville === '' || $codePostal === '') {
                    $this->addFlash('error', 'Veuillez sélectionner une adresse existante ou renseigner une nouvelle adresse complète.');
                    return $this->render('intervention/new.html.twig', [
                        'form'         => $form,
                        'intervention' => $intervention,
                    ]);
                }

                // Sécurité anti-doublon : réutilise une adresse identique si elle existe déjà pour ce client
                $adresse = null;
                foreach ($adresseRepo->findByClient($user) as $existante) {
                    if (
                        mb_strtolower($existante->getRue()) === mb_strtolower($rue)
                        && mb_strtolower($existante->getVille()) === mb_strtolower($ville)
                        && (string) $existante->getCodePostal() === $codePostal
                    ) {
                        $adresse = $existante;
                        break;
                    }
                }

                if (!$adresse) {
                    $adresse = new Adresse();
                    $adresse->setRue($rue);
                    $adresse->setVille($ville);
                    $adresse->setCodePostal((int) $codePostal);
                    $adresse->setComplementAdresse($complement);
                    $em->persist($adresse);
                }
            }

            $intervention->setAdresse($adresse);
            $intervention->setClient($user);
            $intervention->setDateDemande(new \DateTimeImmutable());
            $intervention->setStatut(StatutInterventionEnum::EN_ATTENTE);

            $em->persist($intervention);
            $em->flush();

            $this->addFlash('success', 'Votre demande d\'intervention a bien été envoyée.');
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