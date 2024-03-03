<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240303122340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE brand (id INT AUTO_INCREMENT NOT NULL, brand VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1C52F9581C52F958 (brand), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE car (id INT AUTO_INCREMENT NOT NULL, identify_id INT DEFAULT NULL, model VARCHAR(255) NOT NULL, matriculation VARCHAR(9) NOT NULL, places SMALLINT NOT NULL, UNIQUE INDEX UNIQ_773DE69D69BFAAB5 (matriculation), INDEX IDX_773DE69DE51E104A (identify_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, zipcode VARCHAR(5) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student (id INT AUTO_INCREMENT NOT NULL, register_id INT DEFAULT NULL, live_id INT DEFAULT NULL, possess_id INT DEFAULT NULL, firstname VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, phone VARCHAR(10) NOT NULL, email VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_B723AF33444F97DD (phone), UNIQUE INDEX UNIQ_B723AF33E7927C74 (email), UNIQUE INDEX UNIQ_B723AF334976CB7E (register_id), INDEX IDX_B723AF331DEBA901 (live_id), UNIQUE INDEX UNIQ_B723AF336F87E8F8 (possess_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE student_trip (student_id INT NOT NULL, trip_id INT NOT NULL, INDEX IDX_49758EE3CB944F1A (student_id), INDEX IDX_49758EE3A5BC2E0E (trip_id), PRIMARY KEY(student_id, trip_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trip (id INT AUTO_INCREMENT NOT NULL, drive_id INT DEFAULT NULL, start_id INT DEFAULT NULL, arrive_id INT DEFAULT NULL, kmdistance DOUBLE PRECISION NOT NULL, traveldate DATETIME NOT NULL, placesoffered SMALLINT NOT NULL, INDEX IDX_7656F53B86E5E0C4 (drive_id), INDEX IDX_7656F53B623DF99B (start_id), INDEX IDX_7656F53BF4028648 (arrive_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, login VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649AA08CB10 (login), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE car ADD CONSTRAINT FK_773DE69DE51E104A FOREIGN KEY (identify_id) REFERENCES brand (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF334976CB7E FOREIGN KEY (register_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF331DEBA901 FOREIGN KEY (live_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF336F87E8F8 FOREIGN KEY (possess_id) REFERENCES car (id)');
        $this->addSql('ALTER TABLE student_trip ADD CONSTRAINT FK_49758EE3CB944F1A FOREIGN KEY (student_id) REFERENCES student (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE student_trip ADD CONSTRAINT FK_49758EE3A5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53B86E5E0C4 FOREIGN KEY (drive_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53B623DF99B FOREIGN KEY (start_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53BF4028648 FOREIGN KEY (arrive_id) REFERENCES city (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE car DROP FOREIGN KEY FK_773DE69DE51E104A');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF334976CB7E');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF331DEBA901');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF336F87E8F8');
        $this->addSql('ALTER TABLE student_trip DROP FOREIGN KEY FK_49758EE3CB944F1A');
        $this->addSql('ALTER TABLE student_trip DROP FOREIGN KEY FK_49758EE3A5BC2E0E');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B86E5E0C4');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B623DF99B');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53BF4028648');
        $this->addSql('DROP TABLE brand');
        $this->addSql('DROP TABLE car');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE student');
        $this->addSql('DROP TABLE student_trip');
        $this->addSql('DROP TABLE trip');
        $this->addSql('DROP TABLE user');
    }
}
