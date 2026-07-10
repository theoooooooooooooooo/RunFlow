<?php

namespace App\Tests\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminAccessTest extends WebTestCase
{
    public function testClientNePeutPasAccederAuBackOfficeAdmin(): void
    {
        $webClient = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $client = new Utilisateur();
        $client->setEmail('client.access.' . uniqid() . '@example.com');
        $client->setNom('Client');
        $client->setPrenom('Access');
        $client->setTelephone('0692222222');
        $client->setPassword('hash-factice');
        $client->setRoles(['ROLE_CLIENT']);
        $em->persist($client);
        $em->flush();

        $webClient->loginUser($client);
        $webClient->request('GET', '/admin/intervention/');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testTechnicienDesactiveNePeutPasSeConnecter(): void
    {
        $webClient = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $hasher = static::getContainer()->get('security.user_password_hasher');

        $technicien = new Utilisateur();
        $technicien->setEmail('technicien.desactive.' . uniqid() . '@example.com');
        $technicien->setNom('Technicien');
        $technicien->setPrenom('Desactive');
        $technicien->setTelephone('0692333333');
        $technicien->setRoles(['ROLE_TECHNICIEN']);
        $technicien->setActif(false);
        $technicien->setPassword($hasher->hashPassword($technicien, 'MotDePasse123!'));
        $em->persist($technicien);
        $em->flush();

        $webClient->request('GET', '/login');
        $webClient->submitForm('Sign in', [
            'email' => $technicien->getEmail(),
            'password' => 'MotDePasse123!',
        ]);

        // Ajuste selon le comportement réel : reste sur /login avec message d'erreur
        $this->assertResponseRedirects('/login');
    }
}