<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Contrôles de statut de compte appliqués par le système de sécurité Symfony à chaque tentative de
 * connexion, en complément de la vérification du mot de passe.
 */
class UtilisateurChecker implements UserCheckerInterface
{
    /**
     * Bloque la connexion si le compte a été désactivé par un administrateur (voir
     * {@see \App\Controller\Admin\TechnicienController::toggleActif()}), avant même que le mot de
     * passe soit vérifié.
     *
     * @throws CustomUserMessageAccountStatusException si Utilisateur::isActif() est false ;
     *         l'authentification échoue et aucune session n'est ouverte.
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Utilisateur) {
            return;
        }

        if (!$user->isActif()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte a été désactivé. Veuillez contacter l\'administrateur.'
            );
        }
    }

    /**
     * Aucun contrôle supplémentaire à effectuer une fois l'authentification réussie.
     */
    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // Rien à vérifier après authentification
    }
}