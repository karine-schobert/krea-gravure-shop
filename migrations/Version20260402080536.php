<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260402080536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop_request_attachment CHANGE position position INT DEFAULT 0 NOT NULL, CHANGE is_visible is_visible TINYINT DEFAULT 1 NOT NULL, CHANGE is_checked is_checked TINYINT DEFAULT 0 NOT NULL, CHANGE admin_notes admin_notes LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop_request_attachment CHANGE position position INT NOT NULL, CHANGE is_visible is_visible TINYINT NOT NULL, CHANGE is_checked is_checked TINYINT NOT NULL, CHANGE admin_notes admin_notes LONGTEXT NOT NULL');
    }
}
