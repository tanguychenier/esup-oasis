<?php

declare(strict_types=1);

namespace App\Tests\Service\SiScol;

use PHPUnit\Framework\TestCase;

/**
 * OBC-3 — contrat de colonnes de la requête Apogée
 * `config/apogee/apogee_get_inscriptions.sql`.
 *
 * L'`ApogeeProvider` consomme la ligne renvoyée par cette requête sous la
 * forme `$row->NOM_DE_COLONNE`. Si l'une des colonnes attendues disparaît
 * du SQL, le mapping côté PHP retombe silencieusement sur `null` et le
 * front perd ses badges OBC-3 (niveau, redoublant, cursus aménagé) sans
 * qu'aucun test PHPUnit ne casse — d'où ce test de contrat statique qui
 * relit le fichier .sql et exige la présence des colonnes critiques.
 *
 * Note — le test est volontairement insensible à la casse pour ne pas
 * dépendre de la convention `lower_case` historique (le driver Oracle
 * normalise les noms de colonnes en MAJUSCULES côté `oci_fetch_object`).
 */
final class ApogeeInscriptionsSqlContractTest extends TestCase
{
    private const REQUIRED_COLUMNS = [
        'cod_anu',
        'cod_etp',
        'cod_vrs_vet',
        'lib_web_vet',
        'cod_cmp',
        'lib_cmp',
        // OBC-3 — flag de redoublement calculé directement par la requête.
        'redoublant',
        // OBC-3 — cursus aménagé SISE conservé pour l'affichage frontend.
        'cod_sis_cur_amg',
        'lib_cur_amg',
    ];

    public function testRequiredColumnsArePresent(): void
    {
        $sql = strtolower($this->loadSql());

        foreach (self::REQUIRED_COLUMNS as $column) {
            self::assertMatchesRegularExpression(
                '/\b' . preg_quote($column, '/') . '\b/',
                $sql,
                sprintf('Colonne attendue "%s" absente du SQL Apogée.', $column),
            );
        }
    }

    public function testRedoublantIsExposedAsAColumnAlias(): void
    {
        // Garantit que la règle métier vit bien dans le SQL (et pas dans
        // un wrapper PHP) : on attend un alias explicite `as redoublant`.
        $sql = strtolower($this->loadSql());

        self::assertMatchesRegularExpression(
            '/\bas\s+redoublant\b/',
            $sql,
            'La règle de redoublement doit être exposée comme un alias SQL `as redoublant`.',
        );
    }

    private function loadSql(): string
    {
        $path = dirname(__DIR__, 3) . '/config/apogee/apogee_get_inscriptions.sql';

        self::assertFileExists($path, 'Fichier SQL Apogée introuvable : ' . $path);

        return (string) file_get_contents($path);
    }
}
