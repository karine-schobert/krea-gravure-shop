<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406135621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE shipment (id INT AUTO_INCREMENT NOT NULL, logistic_status VARCHAR(50) NOT NULL, carrier VARCHAR(50) DEFAULT NULL, tracking_number VARCHAR(100) DEFAULT NULL, tracking_url VARCHAR(255) DEFAULT NULL, shipped_at DATETIME DEFAULT NULL, estimated_delivery_at DATETIME DEFAULT NULL, delivered_at DATETIME DEFAULT NULL, last_tracking_sync_at DATETIME DEFAULT NULL, tracking_raw_payload LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, order_id INT NOT NULL, UNIQUE INDEX UNIQ_2CB20DC8D9F6D38 (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE shipment ADD CONSTRAINT FK_2CB20DC8D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE shipment DROP FOREIGN KEY FK_2CB20DC8D9F6D38');
        $this->addSql('DROP TABLE shipment');
    }
}
