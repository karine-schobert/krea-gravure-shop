<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325140417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_offer DROP FOREIGN KEY `FK_888AFC624584665A`');
        $this->addSql('ALTER TABLE product_offer ADD CONSTRAINT FK_888AFC624584665A FOREIGN KEY (product_id) REFERENCES product (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_offer DROP FOREIGN KEY FK_888AFC624584665A');
        $this->addSql('ALTER TABLE product_offer ADD CONSTRAINT `FK_888AFC624584665A` FOREIGN KEY (product_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
