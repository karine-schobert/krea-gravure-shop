<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260326075225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE product_additional_categories (product_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_5BE9EBAE4584665A (product_id), INDEX IDX_5BE9EBAE12469DE2 (category_id), PRIMARY KEY (product_id, category_id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_additional_collections (product_id INT NOT NULL, product_collection_id INT NOT NULL, INDEX IDX_A143AEC64584665A (product_id), INDEX IDX_A143AEC68BA44A0F (product_collection_id), PRIMARY KEY (product_id, product_collection_id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_additional_categories ADD CONSTRAINT FK_5BE9EBAE4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_additional_categories ADD CONSTRAINT FK_5BE9EBAE12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_additional_collections ADD CONSTRAINT FK_A143AEC64584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_additional_collections ADD CONSTRAINT FK_A143AEC68BA44A0F FOREIGN KEY (product_collection_id) REFERENCES product_collection (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE product_additional_categories DROP FOREIGN KEY FK_5BE9EBAE4584665A');
        $this->addSql('ALTER TABLE product_additional_categories DROP FOREIGN KEY FK_5BE9EBAE12469DE2');
        $this->addSql('ALTER TABLE product_additional_collections DROP FOREIGN KEY FK_A143AEC64584665A');
        $this->addSql('ALTER TABLE product_additional_collections DROP FOREIGN KEY FK_A143AEC68BA44A0F');
        $this->addSql('DROP TABLE product_additional_categories');
        $this->addSql('DROP TABLE product_additional_collections');
    }
}
