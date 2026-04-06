<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404143313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review ADD status VARCHAR(20) NOT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD order_item_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6E415FB15 FOREIGN KEY (order_item_id) REFERENCES order_item (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_794381C6E415FB15 ON review (order_item_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6E415FB15');
        $this->addSql('DROP INDEX IDX_794381C6E415FB15 ON review');
        $this->addSql('ALTER TABLE review DROP status, DROP updated_at, DROP order_item_id');
    }
}
