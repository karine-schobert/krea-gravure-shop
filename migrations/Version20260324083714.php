<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260324083714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_collection (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, position INT NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_6F2A3A19989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product ADD product_collection_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD8BA44A0F FOREIGN KEY (product_collection_id) REFERENCES product_collection (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D34A04AD8BA44A0F ON product (product_collection_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE product_collection');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD8BA44A0F');
        $this->addSql('DROP INDEX IDX_D34A04AD8BA44A0F ON product');
        $this->addSql('ALTER TABLE product DROP product_collection_id');
    }
}
