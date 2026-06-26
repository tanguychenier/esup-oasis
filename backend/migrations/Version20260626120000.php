<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * OBC-1 (amendement Fatiha) — situation sociale Apogée.
 *
 * Le booléen `boursier` upstream est conservé (rétro-compat) mais il est
 * désormais dérivé de `code_situation_sociale === 'BO'`. Les deux nouvelles
 * colonnes portent le code et le libellé bruts remontés par Apogée
 * (ins_adm_anu.cod_soc / sit_sociale.lib_soc).
 */
final class Version20260626120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'OBC-1 add code/libelle situation sociale to utilisateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur ADD code_situation_sociale VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD libelle_situation_sociale VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur DROP code_situation_sociale');
        $this->addSql('ALTER TABLE utilisateur DROP libelle_situation_sociale');
    }
}
