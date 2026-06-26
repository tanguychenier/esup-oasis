<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * OBC-3 — redoublement Apogée (champs Fatiha / DSI 2026-06).
 *
 * Ajoute trois colonnes sur `inscription` pour qualifier le redoublement
 * à partir des données officielles Apogée (mail DSI Fatiha, 2026-06) :
 *   - `nbr_ins_etp`        : compteur d'inscriptions à l'étape,
 *   - `code_sis_cur_amg`   : code SISE national du cursus aménagé,
 *   - `lib_cur_amg`        : libellé Apogée du cursus aménagé.
 *
 * Un compteur strictement supérieur à 1 et un cursus aménagé absent
 * caractérisent un redoublant ; la présence d'un cursus aménagé écarte
 * le redoublement (parcours pluri-annuel négocié).
 */
final class Version20260626130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'OBC-3 add nbr_ins_etp, code_sis_cur_amg and lib_cur_amg columns to inscription';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription ADD nbr_ins_etp INT DEFAULT NULL');
        $this->addSql('ALTER TABLE inscription ADD code_sis_cur_amg VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE inscription ADD lib_cur_amg VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription DROP lib_cur_amg');
        $this->addSql('ALTER TABLE inscription DROP code_sis_cur_amg');
        $this->addSql('ALTER TABLE inscription DROP nbr_ins_etp');
    }
}
