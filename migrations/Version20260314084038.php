<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260314084038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address ADD country VARCHAR(100) NOT NULL, ADD user_id INT NOT NULL, CHANGE first_name first_name VARCHAR(150) NOT NULL, CHANGE last_name last_name VARCHAR(150) NOT NULL, CHANGE address address VARCHAR(255) NOT NULL, CHANGE postal_code postal_code VARCHAR(20) NOT NULL, CHANGE phone phone VARCHAR(30) DEFAULT NULL, CHANGE instructions instructions LONGTEXT DEFAULT NULL, CHANGE is_default is_default TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D4E6F81A76ED395 ON address (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81A76ED395');
        $this->addSql('DROP INDEX IDX_D4E6F81A76ED395 ON address');
        $this->addSql('ALTER TABLE address DROP country, DROP user_id, CHANGE first_name first_name VARCHAR(150) DEFAULT NULL, CHANGE last_name last_name VARCHAR(150) DEFAULT NULL, CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE postal_code postal_code VARCHAR(20) DEFAULT NULL, CHANGE phone phone VARCHAR(30) NOT NULL, CHANGE instructions instructions LONGTEXT NOT NULL, CHANGE is_default is_default TINYINT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
    }
}
