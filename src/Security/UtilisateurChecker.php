<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

// Contrôles supplémentaires appliqués à chaque tentative de connexion, en plus du mot de passe
class UtilisateurChecker implements UserCheckerInterface
{
    /**
     * Avant validation du mot de passe : bloque la connexion si le compte a été désactivé (ex: technicien désactivé)
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
     * Après authentification réussie : aucune vérification supplémentaire nécessaire
     */
    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // Rien à vérifier après authentification
    }
}