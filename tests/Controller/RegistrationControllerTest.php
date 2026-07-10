<?php

namespace App\Tests\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testInscriptionAvecDonneesValides(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();

        $email = 'test.phpunit.' . uniqid() . '@example.com';

        $client->submitForm('Register', [
            'registration_form[nom]' => 'Test',
            'registration_form[prenom]' => 'Utilisateur',
            'registration_form[email]' => $email,
            'registration_form[telephone]' => '0692000000',
            'registration_form[plainPassword]' => 'MotDePasse123!',
            'registration_form[agreeTerms]' => true,
        ]);

        $this->assertResponseRedirects();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $utilisateur = $em->getRepository(Utilisateur::class)
            ->findOneBy(['email' => $email]);

        $this->assertNotNull($utilisateur);
        $this->assertContains('ROLE_CLIENT', $utilisateur->getRoles());
    }

   public function testInscriptionAvecEmailDejaUtilise(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $emailExistant = 'existant.' . uniqid() . '@example.com';

        $existant = new Utilisateur();
        $existant->setEmail($emailExistant);
        $existant->setNom('Existant');
        $existant->setPrenom('Compte');
        $existant->setTelephone('0692000001');
        $existant->setPassword('hash-factice');
        $existant->setRoles(['ROLE_CLIENT']);
        $em->persist($existant);
        $em->flush();

        $client->request('GET', '/register');
        $client->submitForm('Register', [
            'registration_form[nom]' => 'Doublon',
            'registration_form[prenom]' => 'Test',
            'registration_form[email]' => $emailExistant,
            'registration_form[telephone]' => '0692000002',
            'registration_form[plainPassword]' => 'MotDePasse123!',
            'registration_form[agreeTerms]' => true,
        ]);

        // Le formulaire ne doit pas rediriger (pas de succès) et doit signaler l'erreur
        $this->assertResponseIsUnprocessable(); // ou assertResponseIsSuccessful() selon ton code HTTP réel
        $this->assertSelectorExists('li');
    }
}