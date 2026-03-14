<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260314125139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` ADD shipping_full_name VARCHAR(255) DEFAULT NULL, ADD shipping_address_line VARCHAR(255) DEFAULT NULL, ADD shipping_postal_code VARCHAR(50) DEFAULT NULL, ADD shipping_city VARCHAR(120) DEFAULT NULL, ADD shipping_country VARCHAR(120) DEFAULT NULL, ADD shipping_phone VARCHAR(50) DEFAULT NULL, ADD shipping_instructions LONGTEXT DEFAULT NULL, ADD address_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398F5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_F5299398F5B7AF75 ON `order` (address_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398F5B7AF75');
        $this->addSql('DROP INDEX IDX_F5299398F5B7AF75 ON `order`');
        $this->addSql('ALTER TABLE `order` DROP shipping_full_name, DROP shipping_address_line, DROP shipping_postal_code, DROP shipping_city, DROP shipping_country, DROP shipping_phone, DROP shipping_instructions, DROP address_id');
    }
}
