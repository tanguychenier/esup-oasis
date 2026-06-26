<?php

declare(strict_types=1);

namespace App\Tests\Service\SiScol;

use App\Entity\Inscription;
use App\Service\SiScol\RedoublementCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * OBC-3 — couverture unitaire de la règle officielle de redoublement
 * désignée par la DSI (mail Fatiha juin 2026).
 *
 * Règle métier validée :
 *   - nbr_ins_etp > 1 ET pas de cod_sis_cur_amg ⇒ redoublant.
 *   - nbr_ins_etp <= 1 ⇒ jamais redoublant (1ère inscription à l'étape).
 *   - cod_sis_cur_amg renseigné ⇒ jamais redoublant (cursus aménagé).
 *   - compteur null ⇒ jamais redoublant (donnée absente côté Apogée).
 */
final class RedoublementCalculatorTest extends TestCase
{
    public static function inscriptionCases(): iterable
    {
        // Cas 1 — première inscription : pas redoublant.
        yield 'compteur = 1, pas de cursus aménagé' => [
            'nbrInsEtp' => 1,
            'codeSisCurAmg' => null,
            'expected' => false,
        ];

        // Cas 2 — deuxième inscription, pas de cursus aménagé : redoublant.
        yield 'compteur = 2 sans cursus aménagé' => [
            'nbrInsEtp' => 2,
            'codeSisCurAmg' => null,
            'expected' => true,
        ];

        // Cas 3 — deuxième inscription mais cursus aménagé : pas redoublant.
        yield 'compteur = 2 avec cursus aménagé' => [
            'nbrInsEtp' => 2,
            'codeSisCurAmg' => 'CA01',
            'expected' => false,
        ];

        // Cas 4 — troisième inscription avec un autre cursus aménagé.
        yield 'compteur = 3 avec cursus aménagé différent' => [
            'nbrInsEtp' => 3,
            'codeSisCurAmg' => 'CA42',
            'expected' => false,
        ];

        // Cas 5 — compteur absent côté Apogée : pas redoublant.
        yield 'compteur null' => [
            'nbrInsEtp' => null,
            'codeSisCurAmg' => null,
            'expected' => false,
        ];

        // Cas 6 — compteur > 1, cursus aménagé explicitement null.
        yield 'compteur = 4, cursus aménagé null' => [
            'nbrInsEtp' => 4,
            'codeSisCurAmg' => null,
            'expected' => true,
        ];

        // Garde-fous supplémentaires.
        yield 'compteur = 2 avec cursus aménagé vide (chaîne vide) ⇒ redoublant' => [
            'nbrInsEtp' => 2,
            'codeSisCurAmg' => '',
            'expected' => true,
        ];

        yield 'compteur = 0 ⇒ pas redoublant' => [
            'nbrInsEtp' => 0,
            'codeSisCurAmg' => null,
            'expected' => false,
        ];
    }

    #[DataProvider('inscriptionCases')]
    public function testIsRedoublant(?int $nbrInsEtp, ?string $codeSisCurAmg, bool $expected): void
    {
        $inscription = new Inscription();
        $inscription->setNbrInsEtp($nbrInsEtp);
        $inscription->setCodeSisCurAmg($codeSisCurAmg);

        self::assertSame(
            $expected,
            (new RedoublementCalculator())->isRedoublant($inscription),
        );
    }
}
