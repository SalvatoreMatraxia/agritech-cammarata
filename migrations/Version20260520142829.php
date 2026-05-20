<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520142829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organic_product ADD half_life_days INT NOT NULL');
        $this->addSql('ALTER TABLE organic_product ADD wash_off_mm DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE organic_product ADD re_treatment_threshold DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE treatment ADD organic_product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE treatment ADD CONSTRAINT FK_98013C3125FE8511 FOREIGN KEY (organic_product_id) REFERENCES organic_product (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_98013C3125FE8511 ON treatment (organic_product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organic_product DROP half_life_days');
        $this->addSql('ALTER TABLE organic_product DROP wash_off_mm');
        $this->addSql('ALTER TABLE organic_product DROP re_treatment_threshold');
        $this->addSql('ALTER TABLE treatment DROP CONSTRAINT FK_98013C3125FE8511');
        $this->addSql('DROP INDEX IDX_98013C3125FE8511');
        $this->addSql('ALTER TABLE treatment DROP organic_product_id');
    }
}
