<?php

namespace App\DataFixtures;

use App\Entity\Adresse;
use App\Entity\Commentaire;
use App\Entity\Intervention;
use App\Entity\Materiel;
use App\Entity\MaterielIntervention;
use App\Entity\Utilisateur;
use App\Enum\StatutInterventionEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private Generator $faker;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $villesReunion = ['Saint-Denis', 'Saint-Pierre', 'Saint-Paul', 'Le Tampon', 'Saint-André', 'Saint-Louis', 'Le Port', 'Sainte-Marie'];

        // ─────────────────────────────────────────────
        // 1. ADMINISTRATEUR
        // ─────────────────────────────────────────────
        $admin = new Utilisateur();
        $admin->setNom('Payet');
        $admin->setPrenom('Gérome');
        $admin->setEmail('admin@runflow.re');
        $admin->setTelephone('0692000001');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));
        $manager->persist($admin);

        // ─────────────────────────────────────────────
        // 2. TECHNICIENS (6)
        // ─────────────────────────────────────────────
        $techniciens = [];
        for ($i = 1; $i <= 6; $i++) {
            $technicien = new Utilisateur();
            $technicien->setNom($this->faker->lastName());
            $technicien->setPrenom($this->faker->firstName());
            $technicien->setEmail('technicien' . $i . '@runflow.re');
            $technicien->setTelephone('069' . $this->faker->numerify('#######'));
            $technicien->setRoles(['ROLE_TECHNICIEN']);
            $technicien->setActif($i !== 6); // le dernier est désactivé pour tester ce cas
            $technicien->setPassword($this->passwordHasher->hashPassword($technicien, 'Technicien123!'));
            $manager->persist($technicien);
            $techniciens[] = $technicien;
        }

        // ─────────────────────────────────────────────
        // 3. CLIENTS (20)
        // ─────────────────────────────────────────────
        $clients = [];
        for ($i = 1; $i <= 20; $i++) {
            $client = new Utilisateur();
            $client->setNom($this->faker->lastName());
            $client->setPrenom($this->faker->firstName());
            $client->setEmail('client' . $i . '@example.com');
            $client->setTelephone('069' . $this->faker->numerify('#######'));
            $client->setRoles(['ROLE_CLIENT']);
            $client->setPassword($this->passwordHasher->hashPassword($client, 'Client123!'));
            $manager->persist($client);
            $clients[] = $client;
        }

        // ─────────────────────────────────────────────
        // 4. MATÉRIEL (10 références)
        // ─────────────────────────────────────────────
        $materielsData = [
            ['Joint torique 20mm', 150],
            ['Robinet mitigeur', 25],
            ['Tuyau PVC 32mm (1m)', 80],
            ['Siphon lavabo', 40],
            ['Flexible douche', 30],
            ['Raccord laiton', 200],
            ['Chauffe-eau 200L', 5],
            ['Colle PVC', 60],
            ['Clapet anti-retour', 45],
            ['Vanne d\'arrêt', 3], // stock critique volontaire
        ];

        $materiels = [];
        foreach ($materielsData as [$nom, $quantite]) {
            $materiel = new Materiel();
            $materiel->setNom($nom);
            $materiel->setDescription($this->faker->sentence(8));
            $materiel->setQuantiteStock($quantite);
            $manager->persist($materiel);
            $materiels[] = $materiel;
        }

        $manager->flush(); // flush intermédiaire pour obtenir les ID avant les relations

        // ─────────────────────────────────────────────
        // 5. INTERVENTIONS (40, réparties sur tous les statuts)
        // ─────────────────────────────────────────────
        $statutsRepartition = [
            'en_attente' => 8,
            'acceptee'   => 5,
            'refusee'    => 3,
            'planifiee'  => 8,
            'en_cours'   => 4,
            'terminee'   => 10,
            'annulee'    => 2,
        ];

        $descriptions = [
            'Fuite sous évier de cuisine',
            'Robinet qui goutte en continu',
            'Canalisation bouchée dans la salle de bain',
            'Chauffe-eau ne chauffe plus',
            'Remplacement joint de baignoire',
            'Installation nouveau lavabo',
            'Pression d\'eau trop faible',
            'Réparation chasse d\'eau',
            'Fuite compteur d\'eau',
            'Odeurs suspectes évacuation',
        ];

        foreach ($statutsRepartition as $statutValue => $nombre) {
            $statut = StatutInterventionEnum::from($statutValue);
            for ($i = 0; $i < $nombre; $i++) {
                $adresse = new Adresse();
                $adresse->setRue($this->faker->streetAddress());
                $adresse->setVille($this->faker->randomElement($villesReunion));
                $adresse->setCodePostal((int) $this->faker->numberBetween(97400, 97490));
                $adresse->setComplementAdresse($this->faker->optional(0.3)->secondaryAddress());
                $manager->persist($adresse);

                $intervention = new Intervention();
                $intervention->setClient($this->faker->randomElement($clients));
                $intervention->setAdresse($adresse);
                $intervention->setDescription($this->faker->randomElement($descriptions));
                $intervention->setDateDemande(\DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-2 months', '-1 week')
                ));
                $intervention->setDateSouhaitee(\DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-1 week', '+2 weeks')
                ));
                $intervention->setStatut($statut);

                // Statuts nécessitant un technicien et une planification
                if (in_array($statut, [
                    StatutInterventionEnum::PLANIFIEE,
                    StatutInterventionEnum::EN_COURS,
                    StatutInterventionEnum::TERMINEE,
                ], true)) {
                    $technicien = $this->faker->randomElement(array_filter($techniciens, fn($t) => $t->isActif()));
                    $intervention->setTechnicien($technicien);
                    $intervention->setDureeEstimee($this->faker->randomElement([60, 90, 120, 180]));
                    $intervention->setDatePlanifiee(\DateTimeImmutable::createFromMutable(
                        $this->faker->dateTimeBetween('-3 weeks', '+1 week')
                    ));

                    // Association de matériel (1 à 3 pièces aléatoires)
                    $nbMateriels = $this->faker->numberBetween(1, 3);
                    $materielsChoisis = $this->faker->randomElements($materiels, $nbMateriels);
                    foreach ($materielsChoisis as $materiel) {
                        $mi = new MaterielIntervention();
                        $mi->setIntervention($intervention);
                        $mi->setMateriel($materiel);
                        $mi->setQuantite($this->faker->numberBetween(1, 3));
                        $manager->persist($mi);
                    }
                }

                // Commentaire uniquement pour les interventions terminées
                if ($statut === StatutInterventionEnum::TERMINEE) {
                    $commentaire = new Commentaire();
                    $commentaire->setContenu($this->faker->paragraph(2));
                    $commentaire->setDate(\DateTimeImmutable::createFromMutable(
                        $this->faker->dateTimeBetween('-2 weeks', 'now')
                    ));
                    $commentaire->setAuteur($intervention->getTechnicien());
                    $commentaire->setIntervention($intervention);
                    $manager->persist($commentaire);
                    $intervention->setCommentaire($commentaire);
                }

                $manager->persist($intervention);
            }
        }

        $manager->flush();
    }
}

