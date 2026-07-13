<?php

namespace App\Controller\Admin;

use App\Entity\Materiel;
use App\Form\MaterielType;
use App\Repository\MaterielRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/materiel')]
#[IsGranted('ROLE_ADMIN')]
final class MaterielController extends AbstractController
{
    #[Route('/', name: 'app_admin_materiel_index', methods: ['GET'])]
    public function index(MaterielRepository $repository): Response
    {
        return $this->render('admin/materiel/index.html.twig', [
            'materiels'      => $repository->findAll(),
            'seuil_critique' => 5,
        ]);
    }

    #[Route('/new', name: 'app_admin_materiel_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $materiel = new Materiel();
        $form = $this->createForm(MaterielType::class, $materiel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($materiel);
            $em->flush();

            $this->addFlash('success', 'Le matériel a été ajouté au stock.');
            return $this->redirectToRoute('app_admin_materiel_index');
        }

        return $this->render('admin/materiel/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_materiel_edit', methods: ['GET', 'POST'])]
    public function edit(
        Materiel $materiel,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(MaterielType::class, $materiel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Le matériel a été mis à jour.');
            return $this->redirectToRoute('app_admin_materiel_index');
        }

        return $this->render('admin/materiel/edit.html.twig', [
            'form'     => $form,
            'materiel' => $materiel,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_materiel_delete', methods: ['POST'])]
    public function delete(
        Materiel $materiel,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $materiel->getId(), $request->request->get('_token'))) {
            if (!$materiel->getMaterielInterventions()->isEmpty()) {
                $this->addFlash('error', 'Impossible de supprimer : ce matériel est déjà lié à des interventions.');
                return $this->redirectToRoute('app_admin_materiel_index');
            }

            $em->remove($materiel);
            $em->flush();
            $this->addFlash('success', 'Le matériel a été supprimé.');
        }

        return $this->redirectToRoute('app_admin_materiel_index');
    }
}