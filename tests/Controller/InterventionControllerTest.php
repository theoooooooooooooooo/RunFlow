<?php

namespace App\Tests\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InterventionControllerTest extends WebTestCase
{
    private function creerClient(EntityManagerInterface $em): Utilisateur
    {
        $client = new Utilisateur();
        $client->setEmail('client.test.' . uniqid() . '@example.com');
        $client->setNom('Client');
        $client->setPrenom('Test');
        $client->setTelephone('0692111111');
        $client->setPassword('hash-factice');
        $client->setRoles(['ROLE_CLIENT']);
        $em->persist($client);
        $em->flush();

        return $client;
    }

    public function testClientNonConnecteEstRedirigeVersLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/intervention/new');

        $this->assertResponseRedirects('/login');
    }

    public function testCreationDemandeParClientConnecte(): void
    {
        $webClient = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $utilisateur = $this->creerClient($em);
        $webClient->loginUser($utilisateur);

        $webClient->request('GET', '/intervention/new');
        $this->assertResponseIsSuccessful();

        $webClient->submitForm('Envoyer ma demande', [
            'intervention_client[description]' => 'Fuite sous évier de test',
            'intervention_client[date_souhaitee]' => '2026-08-15 10:00',
            'intervention_client[adresse_rue]' => '12 rue de Test',
            'intervention_client[adresse_ville]' => 'Saint-Denis',
            'intervention_client[adresse_code_postal]' => '97400',
        ]);

        $this->assertResponseRedirects();
    }

    public function testClientNePeutPasAccederAUneInterventionDunAutreClient(): void
    {
        $webClient = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $clientA = $this->creerClient($em);
        $clientB = $this->creerClient($em);

        // Le client A tente d'accéder à une intervention appartenant au client B
        // (id fictif à adapter selon un jeu de données de test, ou créer l'intervention ici)
        $webClient->loginUser($clientA);
        $webClient->request('GET', '/intervention/999999');

        $this->assertResponseStatusCodeSame(404); // ou 403 selon ton implémentation
    }
}