<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325083807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_offer (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, sale_type VARCHAR(50) NOT NULL, quantity INT NOT NULL, price_cents INT NOT NULL, is_customizable TINYINT DEFAULT 0 NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, position INT DEFAULT NULL, starts_at DATETIME DEFAULT NULL, ends_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, product_id INT NOT NULL, INDEX IDX_888AFC624584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_offer ADD CONSTRAINT FK_888AFC624584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_offer DROP FOREIGN KEY FK_888AFC624584665A');
        $this->addSql('DROP TABLE product_offer');
    }
}
