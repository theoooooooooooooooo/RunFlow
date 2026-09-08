<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Authenticator du formulaire de connexion : construit le passport à partir des identifiants soumis
 * et détermine la redirection à effectuer une fois l'utilisateur authentifié.
 */
class UtilisateurAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * Construit le passport (email, mot de passe, token CSRF, remember-me) à partir du formulaire soumis ;
     * la vérification effective du mot de passe et du token CSRF est déléguée au système de sécurité
     * Symfony après le retour de cette méthode.
     *
     * Effet de bord : enregistre l'email saisi en session (pour le pré-remplir en cas d'échec de connexion).
     */
    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    /**
     * Redirige l'utilisateur vers le tableau de bord de son rôle le plus privilégié : un utilisateur
     * cumulant plusieurs rôles est envoyé sur le tableau de bord admin en priorité, puis technicien,
     * et sinon sur le tableau de bord client par défaut.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_admin_dashboard')
            );
        }

        if (in_array('ROLE_TECHNICIEN', $user->getRoles())) {
            return new RedirectResponse(
                $this->urlGenerator->generate('app_technicien_dashboard')
            );
        }

        return new RedirectResponse(
            $this->urlGenerator->generate('app_client_dashboard')
        );
    }

    /**
     * URL vers laquelle Symfony redirige en cas d'échec d'authentification.
     */
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
