<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les tables actualite/personnel et le statut d\'envoi mail des messages';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE actualite (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, date_publication DATETIME NOT NULL, delai_publication DATETIME NOT NULL, contenu LONGTEXT NOT NULL, image VARCHAR(500) NOT NULL, faq JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1B46D917989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personnel (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, poste VARCHAR(255) NOT NULL, photo VARCHAR(500) NOT NULL, description LONGTEXT NOT NULL, liens_sociaux JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("ALTER TABLE message ADD statut_envoi_mail VARCHAR(20) DEFAULT 'pending' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE actualite');
        $this->addSql('DROP TABLE personnel');
        $this->addSql('ALTER TABLE message DROP statut_envoi_mail');
    }
}
