<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * OBC-3 — redoublement Apogée (règle calculée côté SQL, DSI 2026-06).
 *
 * Ajoute trois colonnes sur `inscription` pour qualifier le redoublement
 * à partir des données officielles Apogée (mail DSI Fatiha, 2026-06) :
 *   - `redoublant`        : booléen calculé directement par la requête
 *                            Apogée (cf. config/apogee/apogee_get_inscriptions.sql),
 *   - `code_sis_cur_amg`  : code SISE national du cursus aménagé,
 *   - `lib_cur_amg`       : libellé Apogée du cursus aménagé.
 *
 * La règle métier (`nbr_ins_etp > 1` ET pas de cursus aménagé) vit dans
 * le SQL pour que chaque université puisse l'adapter à sa propre version
 * d'Apogée sans modifier le code applicatif.
 */
final class Version20260626130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'OBC-3 add redoublant, code_sis_cur_amg and lib_cur_amg columns to inscription';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription ADD redoublant BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE inscription ADD code_sis_cur_amg VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE inscription ADD lib_cur_amg VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inscription DROP lib_cur_amg');
        $this->addSql('ALTER TABLE inscription DROP code_sis_cur_amg');
        $this->addSql('ALTER TABLE inscription DROP redoublant');
    }
}
