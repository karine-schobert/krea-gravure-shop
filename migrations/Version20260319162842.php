<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260319162842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE homepage_product (homepage_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_CA5B608B571EDDA (homepage_id), INDEX IDX_CA5B608B4584665A (product_id), PRIMARY KEY (homepage_id, product_id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE homepage_product ADD CONSTRAINT FK_CA5B608B571EDDA FOREIGN KEY (homepage_id) REFERENCES homepage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE homepage_product ADD CONSTRAINT FK_CA5B608B4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE homepage ADD hero_description LONGTEXT NOT NULL, ADD hero_primary_cta_label VARCHAR(100) NOT NULL, ADD hero_secondary_cta_label VARCHAR(100) NOT NULL, ADD hero_secondary_cta_link VARCHAR(255) NOT NULL, ADD hero_image VARCHAR(255) NOT NULL, ADD about_text1 LONGTEXT NOT NULL, ADD about_text2 LONGTEXT NOT NULL, ADD about_image VARCHAR(255) NOT NULL, ADD shop_title VARCHAR(255) NOT NULL, ADD shop_subtitle LONGTEXT NOT NULL, ADD shop_description LONGTEXT NOT NULL, DROP about_text, CHANGE about_title about_title VARCHAR(255) NOT NULL, CHANGE hero_cta hero_eyebrow VARCHAR(100) NOT NULL, CHANGE hero_subtitle hero_primary_cta_link VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE homepage_product DROP FOREIGN KEY FK_CA5B608B571EDDA');
        $this->addSql('ALTER TABLE homepage_product DROP FOREIGN KEY FK_CA5B608B4584665A');
        $this->addSql('DROP TABLE homepage_product');
        $this->addSql('ALTER TABLE homepage ADD hero_subtitle VARCHAR(255) NOT NULL, ADD hero_cta VARCHAR(100) NOT NULL, ADD about_text LONGTEXT DEFAULT NULL, DROP hero_eyebrow, DROP hero_description, DROP hero_primary_cta_label, DROP hero_primary_cta_link, DROP hero_secondary_cta_label, DROP hero_secondary_cta_link, DROP hero_image, DROP about_text1, DROP about_text2, DROP about_image, DROP shop_title, DROP shop_subtitle, DROP shop_description, CHANGE about_title about_title VARCHAR(255) DEFAULT NULL');
    }
}
