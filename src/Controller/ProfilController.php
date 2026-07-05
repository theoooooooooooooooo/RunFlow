<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\ChangePasswordType;
use App\Form\ProfilType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
final class ProfilController extends AbstractController
{
    /**
     * Consulter et modifier son profil
     */
    #[Route('/', name: 'app_profil', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfilType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour.');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/index.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    /**
     * Changer le mot de passe
     */
    #[Route('/mot-de-passe', name: 'app_profil_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('new_password')->getData();
            $user->setPassword($hasher->hashPassword($user, $newPassword));

            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('profil/password.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Consulter ses données personnelles (RGPD)
     */
    #[Route('/mes-donnees', name: 'app_profil_donnees', methods: ['GET'])]
    public function mesDonnees(): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        return $this->render('profil/donnees.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Supprimer son compte avec anonymisation (RGPD - droit à l'effacement)
     * Réservé aux clients
     */
    #[Route('/supprimer', name: 'app_profil_supprimer', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT')]
    public function supprimer(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('supprimer_compte', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_profil');
        }

        $user->setNom('Utilisateur');
        $user->setPrenom('Anonymisé');
        $user->setEmail('anonyme_' . $user->getId() . '_' . uniqid() . '@runflow.local');
        $user->setTelephone('0000000000');
        $user->setPassword(bin2hex(random_bytes(32)));
        $user->setRoles(['ROLE_ANONYMISE']);
        $user->setDateAnonymisation(new \DateTimeImmutable()); // ← ajouté

        $em->flush();

        $this->container->get('security.token_storage')->setToken(null);
        $request->getSession()->invalidate();

        $this->addFlash('success', 'Votre compte a été supprimé et vos données anonymisées.');
        return $this->redirectToRoute('app_login');
    }
}