<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260601095008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE adresse (id INT AUTO_INCREMENT NOT NULL, rue VARCHAR(255) NOT NULL, ville VARCHAR(255) NOT NULL, code_postal INT NOT NULL, complement_adresse VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date DATETIME NOT NULL, intervention_id INT DEFAULT NULL, auteur_id INT NOT NULL, UNIQUE INDEX UNIQ_67F068BC8EAE3863 (intervention_id), INDEX IDX_67F068BC60BB6FE6 (auteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE intervention (id INT AUTO_INCREMENT NOT NULL, date_demande DATETIME NOT NULL, date_souhaitee DATETIME NOT NULL, date_planifiee DATETIME NOT NULL, description LONGTEXT NOT NULL, statut VARCHAR(255) NOT NULL, client_id INT NOT NULL, technicien_id INT DEFAULT NULL, adresse_id INT NOT NULL, INDEX IDX_D11814AB19EB6921 (client_id), INDEX IDX_D11814AB13457256 (technicien_id), INDEX IDX_D11814AB4DE7DC5C (adresse_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE intervention_materiel (intervention_id INT NOT NULL, materiel_id INT NOT NULL, INDEX IDX_2541CB328EAE3863 (intervention_id), INDEX IDX_2541CB3216880AAF (materiel_id), PRIMARY KEY (intervention_id, materiel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE materiel (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, quantite_stock INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE materiel_intervention (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, intervention_id INT NOT NULL, materiel_id INT NOT NULL, INDEX IDX_551CE5C88EAE3863 (intervention_id), INDEX IDX_551CE5C816880AAF (materiel_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC8EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB19EB6921 FOREIGN KEY (client_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB13457256 FOREIGN KEY (technicien_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB4DE7DC5C FOREIGN KEY (adresse_id) REFERENCES adresse (id)');
        $this->addSql('ALTER TABLE intervention_materiel ADD CONSTRAINT FK_2541CB328EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intervention_materiel ADD CONSTRAINT FK_2541CB3216880AAF FOREIGN KEY (materiel_id) REFERENCES materiel (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE materiel_intervention ADD CONSTRAINT FK_551CE5C88EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id)');
        $this->addSql('ALTER TABLE materiel_intervention ADD CONSTRAINT FK_551CE5C816880AAF FOREIGN KEY (materiel_id) REFERENCES materiel (id)');
        $this->addSql('ALTER TABLE utilisateur ADD nom VARCHAR(255) NOT NULL, ADD prenom VARCHAR(255) NOT NULL, ADD telephone INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC8EAE3863');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC60BB6FE6');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB19EB6921');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB13457256');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB4DE7DC5C');
        $this->addSql('ALTER TABLE intervention_materiel DROP FOREIGN KEY FK_2541CB328EAE3863');
        $this->addSql('ALTER TABLE intervention_materiel DROP FOREIGN KEY FK_2541CB3216880AAF');
        $this->addSql('ALTER TABLE materiel_intervention DROP FOREIGN KEY FK_551CE5C88EAE3863');
        $this->addSql('ALTER TABLE materiel_intervention DROP FOREIGN KEY FK_551CE5C816880AAF');
        $this->addSql('DROP TABLE adresse');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE intervention');
        $this->addSql('DROP TABLE intervention_materiel');
        $this->addSql('DROP TABLE materiel');
        $this->addSql('DROP TABLE materiel_intervention');
        $this->addSql('ALTER TABLE utilisateur DROP nom, DROP prenom, DROP telephone');
    }
}
