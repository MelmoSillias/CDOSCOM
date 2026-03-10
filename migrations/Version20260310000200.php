<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table visitor_activity pour le tracking de navigation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE visitor_activity (id INT AUTO_INCREMENT NOT NULL, event_type VARCHAR(50) NOT NULL, path VARCHAR(255) NOT NULL, route_name VARCHAR(100) DEFAULT NULL, session_id VARCHAR(100) DEFAULT NULL, ip_hash VARCHAR(128) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, referrer VARCHAR(255) DEFAULT NULL, is_admin TINYINT(1) NOT NULL, duration_ms INT DEFAULT NULL, status_code INT DEFAULT NULL, method VARCHAR(10) DEFAULT NULL, metadata JSON NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C58D7C265D7CC71A (created_at), INDEX IDX_C58D7C2671F7E88B (event_type), INDEX IDX_C58D7C26889E367D (path), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE visitor_activity');
    }
}
