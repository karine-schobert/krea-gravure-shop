<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260321164306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_season (product_id INT NOT NULL, season_id INT NOT NULL, INDEX IDX_92981A0D4584665A (product_id), INDEX IDX_92981A0D4EC001D1 (season_id), PRIMARY KEY (product_id, season_id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE season (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_F0E45BA9989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_season ADD CONSTRAINT FK_92981A0D4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_season ADD CONSTRAINT FK_92981A0D4EC001D1 FOREIGN KEY (season_id) REFERENCES season (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_season DROP FOREIGN KEY FK_92981A0D4584665A');
        $this->addSql('ALTER TABLE product_season DROP FOREIGN KEY FK_92981A0D4EC001D1');
        $this->addSql('DROP TABLE product_season');
        $this->addSql('DROP TABLE season');
    }
}
