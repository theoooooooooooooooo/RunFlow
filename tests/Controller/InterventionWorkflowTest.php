<?php

namespace App\Tests\Controller;

use App\Entity\Adresse;
use App\Entity\Intervention;
use App\Entity\Utilisateur;
use App\Enum\StatutInterventionEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InterventionWorkflowTest extends WebTestCase
{
    private function creerUtilisateur(EntityManagerInterface $em, string $role, bool $actif = true): Utilisateur
    {
        $user = new Utilisateur();
        $user->setEmail($role . '.' . uniqid() . '@example.com');
        $user->setNom('Nom' . $role);
        $user->setPrenom('Prenom' . $role);
        $user->setTelephone('0692000000');
        $user->setPassword('hash-factice');
        $user->setRoles([$role]);
        if (method_exists($user, 'setActif')) {
            $user->setActif($actif);
        }
        $em->persist($user);

        return $user;
    }

    private function creerIntervention(EntityManagerInterface $em, Utilisateur $client): Intervention
    {
        $adresse = new Adresse();
        $adresse->setRue('12 rue du Test');
        $adresse->setVille('Saint-Denis');
        $adresse->setCodePostal(97400);
        $em->persist($adresse);

        $intervention = new Intervention();
        $intervention->setClient($client);
        $intervention->setAdresse($adresse);
        $intervention->setDescription('Fuite à réparer - test workflow');
        $intervention->setDateDemande(new \DateTimeImmutable());
        $intervention->setDateSouhaitee(new \DateTimeImmutable('+3 days'));
        $intervention->setStatut(StatutInterventionEnum::EN_ATTENTE);
        $em->persist($intervention);
        $em->flush();

        return $intervention;
    }

    public function testWorkflowCompletDeLaDemandeALaCloture(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $admin = $this->creerUtilisateur($em, 'ROLE_ADMIN');
        $technicien = $this->creerUtilisateur($em, 'ROLE_TECHNICIEN');
        $clientDemandeur = $this->creerUtilisateur($em, 'ROLE_CLIENT');
        $em->flush();

        $intervention = $this->creerIntervention($em, $clientDemandeur);
        $id = $intervention->getId();

        // ── 1. Acceptation par l'admin ──
        $client->loginUser($admin);
        $client->request('GET', "/admin/intervention/{$id}");
        $this->assertResponseIsSuccessful();

        $client->submitForm('Accepter la demande');
        $this->assertResponseRedirects();

        $em->clear();
        $intervention = $em->getRepository(Intervention::class)->find($id);
        $this->assertSame(StatutInterventionEnum::ACCEPTEE, $intervention->getStatut());

        // ── 2. Planification (technicien + créneau) ──
        $client->request('GET', "/admin/intervention/{$id}");
        $this->assertResponseIsSuccessful();

        $client->submitForm('Planifier l\'intervention', [
            'affectation_technicien[technicien]' => (string) $technicien->getId(),
            'affectation_technicien[duree_estimee]' => '120',
            'affectation_technicien[date_planifiee]' => '2026-08-20T09:00',
        ]);
        $this->assertResponseRedirects();

        $em->clear();
        $intervention = $em->getRepository(Intervention::class)->find($id);
        $this->assertSame(StatutInterventionEnum::PLANIFIEE, $intervention->getStatut());
        $this->assertSame($technicien->getId(), $intervention->getTechnicien()->getId());
        $this->assertNotNull($intervention->getDatePlanifiee());

        // ── 3. Démarrage par le technicien assigné ──
        $client->loginUser($technicien);
        $client->request('GET', "/intervention/{$id}");
        $this->assertResponseIsSuccessful();

        $client->submitForm('Démarrer l\'intervention');
        $this->assertResponseRedirects();

        $em->clear();
        $intervention = $em->getRepository(Intervention::class)->find($id);
        $this->assertSame(StatutInterventionEnum::EN_COURS, $intervention->getStatut());

        // ── 4. Clôture avec commentaire obligatoire ──
        $client->request('GET', "/technicien/intervention/{$id}/valider");
        $this->assertResponseIsSuccessful();

        $client->submitForm('Valider et terminer l\'intervention', [
            'commentaire[contenu]' => 'Intervention réalisée avec succès, joint remplacé.',
        ]);
        $this->assertResponseRedirects();

        $em->clear();
        $intervention = $em->getRepository(Intervention::class)->find($id);
        $this->assertSame(StatutInterventionEnum::TERMINEE, $intervention->getStatut());
        $this->assertNotNull($intervention->getCommentaire());
        $this->assertStringContainsString('joint remplacé', $intervention->getCommentaire()->getContenu());
    }

    public function testUnTechnicienNonAssigneNePeutPasDemarrerLIntervention(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $technicienAssigne = $this->creerUtilisateur($em, 'ROLE_TECHNICIEN');
        $autreTechnicien = $this->creerUtilisateur($em, 'ROLE_TECHNICIEN');
        $clientDemandeur = $this->creerUtilisateur($em, 'ROLE_CLIENT');
        $em->flush();

        $intervention = $this->creerIntervention($em, $clientDemandeur);
        $intervention->setTechnicien($technicienAssigne);
        $intervention->setStatut(StatutInterventionEnum::PLANIFIEE);
        $intervention->setDatePlanifiee(new \DateTimeImmutable('+2 days'));
        $em->flush();

        $id = $intervention->getId();

        // Le technicien assigné charge la page pour récupérer un token CSRF valide
        $client->loginUser($technicienAssigne);
        $crawler = $client->request('GET', "/intervention/{$id}");
        $token = $crawler->filter('form[action*="demarrer"] input[name="_token"]')->attr('value');

        // Un autre technicien (non assigné) tente d'appeler l'action directement avec ce token
        $client->loginUser($autreTechnicien);
        $client->request('POST', "/technicien/intervention/{$id}/demarrer", ['_token' => $token]);

        $this->assertResponseStatusCodeSame(403);

        $em->clear();
        $intervention = $em->getRepository(Intervention::class)->find($id);
        $this->assertSame(StatutInterventionEnum::PLANIFIEE, $intervention->getStatut());
    }

    public function testRefusDUneDemandeEnAttente(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $admin = $this->creerUtilisateur($em, 'ROLE_ADMIN');
        $clientDemandeur = $this->creerUtilisateur($em, 'ROLE_CLIENT');
        $em->flush();

        $intervention = $this->creerIntervention($em, $clientDemandeur);
        $id = $intervention->getId();

        $client->loginUser($admin);
        $client->request('GET', "/admin/intervention/{$id}");
        $this->assertResponseIsSuccessful();

        $client->submitForm('Refuser la demande');
        $this->assertResponseRedirects();

        $em->clear();
        $intervention = $em->getRepository(Intervention::class)->find($id);
        $this->assertSame(StatutInterventionEnum::REFUSEE, $intervention->getStatut());
    }
}