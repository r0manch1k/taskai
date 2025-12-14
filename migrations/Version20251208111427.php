<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208111427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE company_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE bot_user (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql(<<<'SQL'
            CREATE TABLE company (
              id INT NOT NULL,
              bot_user_id INT NOT NULL,
              domain VARCHAR(255) NOT NULL,
              token VARCHAR(255) DEFAULT NULL,
              space_id INT DEFAULT NULL,
              PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_4FBF094F5898BEB0 ON company (bot_user_id)');
        $this->addSql(<<<'SQL'
            ALTER TABLE
              company
            ADD
              CONSTRAINT FK_4FBF094F5898BEB0 FOREIGN KEY (bot_user_id) REFERENCES bot_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE company_id_seq CASCADE');
        $this->addSql('ALTER TABLE company DROP CONSTRAINT FK_4FBF094F5898BEB0');
        $this->addSql('DROP TABLE bot_user');
        $this->addSql('DROP TABLE company');
    }
}
