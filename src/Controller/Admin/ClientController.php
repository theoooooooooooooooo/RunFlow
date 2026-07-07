<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/client')]
#[IsGranted('ROLE_ADMIN')]
final class ClientController extends AbstractController
{
    /**
     * Liste des clients
     */
    #[Route('/', name: 'app_admin_client_index', methods: ['GET'])]
    public function index(UtilisateurRepository $repository): Response
    {
        return $this->render('admin/client/index.html.twig', [
            'clients' => $repository->findClients(),
        ]);
    }

    /**
     * Détail d'un client (historique de ses interventions)
     */
    #[Route('/{id}', name: 'app_admin_client_show', methods: ['GET'])]
    public function show(Utilisateur $client): Response
    {
        if (!in_array('ROLE_CLIENT', $client->getRoles(), true)) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un client.');
        }

        return $this->render('admin/client/show.html.twig', [
            'client' => $client,
        ]);
    }
}