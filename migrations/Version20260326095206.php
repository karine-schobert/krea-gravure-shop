<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260326095206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_cart_product ON cart_item');
        $this->addSql('ALTER TABLE cart_item ADD customization LONGTEXT DEFAULT NULL, ADD offer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE252753C674EE FOREIGN KEY (offer_id) REFERENCES product_offer (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_F0FE252753C674EE ON cart_item (offer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE252753C674EE');
        $this->addSql('DROP INDEX IDX_F0FE252753C674EE ON cart_item');
        $this->addSql('ALTER TABLE cart_item DROP customization, DROP offer_id');
        $this->addSql('CREATE UNIQUE INDEX uniq_cart_product ON cart_item (cart_id, product_id)');
    }
}
