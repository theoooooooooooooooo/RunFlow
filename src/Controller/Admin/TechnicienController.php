<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Form\TechnicienType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/technicien')]
#[IsGranted('ROLE_ADMIN')]
final class TechnicienController extends AbstractController
{
    /**
     * Liste des techniciens
     */
    #[Route('/', name: 'app_admin_technicien_index', methods: ['GET'])]
    public function index(UtilisateurRepository $repository): Response
    {
        return $this->render('admin/technicien/index.html.twig', [
            'techniciens' => $repository->findTechniciens(),
        ]);
    }

    /**
     * Créer un technicien
     */
    #[Route('/new', name: 'app_admin_technicien_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $technicien = new Utilisateur();
        $technicien->setRoles(['ROLE_TECHNICIEN']);

        $form = $this->createForm(TechnicienType::class, $technicien, ['is_creation' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plain_password')->getData();
            $technicien->setPassword($hasher->hashPassword($technicien, $plainPassword));

            $em->persist($technicien);
            $em->flush();

            $this->addFlash('success', 'Le technicien a été créé avec succès.');
            return $this->redirectToRoute('app_admin_technicien_index');
        }

        return $this->render('admin/technicien/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Modifier un technicien
     */
    #[Route('/{id}/edit', name: 'app_admin_technicien_edit', methods: ['GET', 'POST'])]
    public function edit(
        Utilisateur $technicien,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->verifierEstTechnicien($technicien);

        $form = $this->createForm(TechnicienType::class, $technicien, ['is_creation' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Le technicien a été mis à jour.');
            return $this->redirectToRoute('app_admin_technicien_index');
        }

        return $this->render('admin/technicien/edit.html.twig', [
            'form'       => $form,
            'technicien' => $technicien,
        ]);
    }

    /**
     * Activer / désactiver un technicien
     */
    #[Route('/{id}/toggle-actif', name: 'app_admin_technicien_toggle', methods: ['POST'])]
    public function toggleActif(
        Utilisateur $technicien,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->verifierEstTechnicien($technicien);

        if (!$this->isCsrfTokenValid('toggle' . $technicien->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_technicien_index');
        }

        $technicien->setActif(!$technicien->isActif());
        $em->flush();

        $message = $technicien->isActif()
            ? 'Le technicien a été réactivé.'
            : 'Le technicien a été désactivé.';

        $this->addFlash('success', $message);
        return $this->redirectToRoute('app_admin_technicien_index');
    }

    private function verifierEstTechnicien(Utilisateur $utilisateur): void
    {
        if (!in_array('ROLE_TECHNICIEN', $utilisateur->getRoles(), true)) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un technicien.');
        }
    }
}