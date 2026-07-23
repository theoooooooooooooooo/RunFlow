<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegalController extends AbstractController
{
    /**
     * Affiche les conditions générales d'utilisation
     */
    #[Route('/cgu', name: 'app_cgu', methods: ['GET'])]
    public function cgu(): Response
    {
        return $this->render('legal/cgu.html.twig');
    }

    /**
     * Affiche la politique de confidentialité (RGPD)
     */
    #[Route('/politique-confidentialite', name: 'app_politique_confidentialite', methods: ['GET'])]
    public function politiqueConfidentialite(): Response
    {
        return $this->render('legal/politique_confidentialite.html.twig');
    }
}