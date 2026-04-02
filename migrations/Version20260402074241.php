<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260402074241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE workshop_request (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(30) NOT NULL, customer_type VARCHAR(20) NOT NULL, full_name VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(50) DEFAULT NULL, preferred_contact_method VARCHAR(20) DEFAULT NULL, company_name VARCHAR(180) DEFAULT NULL, contact_person VARCHAR(180) DEFAULT NULL, requires_invoice TINYINT DEFAULT 0 NOT NULL, request_type VARCHAR(50) NOT NULL, need_type VARCHAR(50) DEFAULT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, event_type VARCHAR(100) DEFAULT NULL, event_name VARCHAR(180) DEFAULT NULL, event_date DATETIME DEFAULT NULL, desired_date DATETIME DEFAULT NULL, deadline_notes LONGTEXT DEFAULT NULL, desired_quantity_range VARCHAR(100) DEFAULT NULL, budget_notes LONGTEXT DEFAULT NULL, delivery_method VARCHAR(20) DEFAULT NULL, requires_quote TINYINT DEFAULT 0 NOT NULL, project_stage VARCHAR(50) DEFAULT NULL, status VARCHAR(30) DEFAULT \'new\' NOT NULL, priority VARCHAR(20) DEFAULT \'normal\' NOT NULL, admin_notes LONGTEXT DEFAULT NULL, customer_notes LONGTEXT DEFAULT NULL, is_read TINYINT DEFAULT 0 NOT NULL, is_flagged TINYINT DEFAULT 0 NOT NULL, consent_rgpd TINYINT DEFAULT 0 NOT NULL, consent_rgpd_at DATETIME DEFAULT NULL, source VARCHAR(50) DEFAULT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, submitted_at DATETIME DEFAULT NULL, processed_at DATETIME DEFAULT NULL, answered_at DATETIME DEFAULT NULL, archived_at DATETIME DEFAULT NULL, first_admin_reply_at DATETIME DEFAULT NULL, last_admin_reply_at DATETIME DEFAULT NULL, last_customer_reply_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_79D89D88AEA34913 (reference), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workshop_request_attachment (id INT AUTO_INCREMENT NOT NULL, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(255) NOT NULL, path VARCHAR(500) NOT NULL, mime_type VARCHAR(100) DEFAULT NULL, size INT DEFAULT NULL, attachment_type VARCHAR(50) NOT NULL, position INT NOT NULL, is_visible TINYINT NOT NULL, is_checked TINYINT NOT NULL, admin_notes LONGTEXT NOT NULL, file_hash VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, workshop_request_id INT NOT NULL, INDEX IDX_FFA397DAC58021C0 (workshop_request_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE workshop_request_item (id INT AUTO_INCREMENT NOT NULL, custom_label VARCHAR(180) DEFAULT NULL, quantity INT DEFAULT NULL, personalization_text LONGTEXT DEFAULT NULL, material_notes VARCHAR(255) DEFAULT NULL, format_notes VARCHAR(255) DEFAULT NULL, color_notes VARCHAR(255) DEFAULT NULL, dimensions_notes VARCHAR(255) DEFAULT NULL, line_message LONGTEXT DEFAULT NULL, position INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, workshop_request_id INT NOT NULL, category_id INT DEFAULT NULL, product_id INT DEFAULT NULL, INDEX IDX_F1E53118C58021C0 (workshop_request_id), INDEX IDX_F1E5311812469DE2 (category_id), INDEX IDX_F1E531184584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE workshop_request_attachment ADD CONSTRAINT FK_FFA397DAC58021C0 FOREIGN KEY (workshop_request_id) REFERENCES workshop_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_request_item ADD CONSTRAINT FK_F1E53118C58021C0 FOREIGN KEY (workshop_request_id) REFERENCES workshop_request (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE workshop_request_item ADD CONSTRAINT FK_F1E5311812469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE workshop_request_item ADD CONSTRAINT FK_F1E531184584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE workshop_request_attachment DROP FOREIGN KEY FK_FFA397DAC58021C0');
        $this->addSql('ALTER TABLE workshop_request_item DROP FOREIGN KEY FK_F1E53118C58021C0');
        $this->addSql('ALTER TABLE workshop_request_item DROP FOREIGN KEY FK_F1E5311812469DE2');
        $this->addSql('ALTER TABLE workshop_request_item DROP FOREIGN KEY FK_F1E531184584665A');
        $this->addSql('DROP TABLE workshop_request');
        $this->addSql('DROP TABLE workshop_request_attachment');
        $this->addSql('DROP TABLE workshop_request_item');
    }
}
