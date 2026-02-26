<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création des tables AJBVA';
    }

    public function up(Schema $schema): void
    {
        // Table membre
        $this->addSql('CREATE TABLE membre (
            id SERIAL NOT NULL PRIMARY KEY,
            identifiant VARCHAR(30) NOT NULL,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            genre VARCHAR(10) NOT NULL,
            date_naissance DATE NOT NULL,
            date_adhesion DATE NOT NULL,
            telephone VARCHAR(20) NOT NULL,
            email VARCHAR(180) DEFAULT NULL,
            photo VARCHAR(255) DEFAULT NULL,
            grande_famille VARCHAR(100) DEFAULT NULL,
            village_origine VARCHAR(100) DEFAULT NULL,
            statut VARCHAR(20) NOT NULL DEFAULT \'Actif\',
            solde_cotisations NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            CONSTRAINT UNIQ_membre_identifiant UNIQUE (identifiant)
        )');

        // Table user
        $this->addSql('CREATE TABLE "user" (
            id SERIAL NOT NULL PRIMARY KEY,
            membre_id INTEGER DEFAULT NULL,
            email VARCHAR(180) NOT NULL,
            roles TEXT NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT UNIQ_user_email UNIQUE (email),
            CONSTRAINT FK_user_membre FOREIGN KEY (membre_id) REFERENCES membre(id) ON DELETE SET NULL
        )');

        // Table cotisation
        $this->addSql('CREATE TABLE cotisation (
            id SERIAL NOT NULL PRIMARY KEY,
            membre_id INTEGER NOT NULL,
            cas_social_id INTEGER DEFAULT NULL,
            type VARCHAR(30) NOT NULL DEFAULT \'mensuelle\',
            montant NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\',
            date_paiement DATE NOT NULL,
            mois_concerne VARCHAR(7) DEFAULT NULL,
            note VARCHAR(255) DEFAULT NULL,
            recu_numero VARCHAR(30) DEFAULT NULL,
            statut VARCHAR(20) NOT NULL DEFAULT \'payee\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT FK_cotisation_membre FOREIGN KEY (membre_id) REFERENCES membre(id) ON DELETE CASCADE,
            CONSTRAINT UNIQ_recu_numero UNIQUE (recu_numero)
        )');

        // Table cas_social
        $this->addSql('CREATE TABLE cas_social (
            id SERIAL NOT NULL PRIMARY KEY,
            membre_id INTEGER NOT NULL,
            declared_by_id INTEGER DEFAULT NULL,
            validated_by_id INTEGER DEFAULT NULL,
            type VARCHAR(30) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            date_evenement DATE NOT NULL,
            montant_assistance NUMERIC(10, 2) DEFAULT NULL,
            statut_assistance VARCHAR(20) NOT NULL DEFAULT \'En attente\',
            date_paiement_assistance DATE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT FK_cas_social_membre FOREIGN KEY (membre_id) REFERENCES membre(id) ON DELETE CASCADE,
            CONSTRAINT FK_cas_social_declared FOREIGN KEY (declared_by_id) REFERENCES "user"(id) ON DELETE SET NULL,
            CONSTRAINT FK_cas_social_validated FOREIGN KEY (validated_by_id) REFERENCES "user"(id) ON DELETE SET NULL
        )');

        // Table projet
        $this->addSql('CREATE TABLE projet (
            id SERIAL NOT NULL PRIMARY KEY,
            nom VARCHAR(200) NOT NULL,
            description TEXT DEFAULT NULL,
            budget_total NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\',
            montant_collecte NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\',
            benefices NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\',
            statut VARCHAR(20) NOT NULL DEFAULT \'En cours\',
            date_debut DATE DEFAULT NULL,
            date_fin DATE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
        )');

        // Table patrimoine
        $this->addSql('CREATE TABLE patrimoine (
            id SERIAL NOT NULL PRIMARY KEY,
            projet_id INTEGER DEFAULT NULL,
            nom VARCHAR(200) NOT NULL,
            description TEXT DEFAULT NULL,
            valeur_achat NUMERIC(10, 2) NOT NULL DEFAULT \'0.00\',
            date_acquisition DATE NOT NULL,
            etat VARCHAR(20) NOT NULL DEFAULT \'Bon état\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            CONSTRAINT FK_patrimoine_projet FOREIGN KEY (projet_id) REFERENCES projet(id) ON DELETE SET NULL
        )');

        // Ajouter la FK de cotisation vers cas_social
        $this->addSql('CREATE INDEX IDX_cotisation_cas_social ON cotisation (cas_social_id)');
        $this->addSql('CREATE INDEX IDX_cotisation_membre ON cotisation (membre_id)');
        $this->addSql('CREATE INDEX IDX_cas_social_membre ON cas_social (membre_id)');
        $this->addSql('CREATE INDEX IDX_user_membre ON "user" (membre_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS patrimoine');
        $this->addSql('DROP TABLE IF EXISTS projet');
        $this->addSql('DROP TABLE IF EXISTS cotisation');
        $this->addSql('DROP TABLE IF EXISTS cas_social');
        $this->addSql('DROP TABLE IF EXISTS "user"');
        $this->addSql('DROP TABLE IF EXISTS membre');
    }
}
