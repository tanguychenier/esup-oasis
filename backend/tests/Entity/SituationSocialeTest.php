<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;

/**
 * OBC-1 (amendement Fatiha) — la situation sociale Apogée est désormais la source
 * autoritative ; le booléen `boursier` upstream est conservé pour compatibilité
 * et dérivé du code (`BO` => true, autre => false, null/vide => null).
 */
final class SituationSocialeTest extends TestCase
{
    public function testFreshUtilisateurHasNoSituationSociale(): void
    {
        $u = new Utilisateur();

        self::assertNull($u->getCodeSituationSociale());
        self::assertNull($u->getLibelleSituationSociale());
        self::assertNull($u->isBoursier());
    }

    public function testCodeBoMarksBoursierAndKeepsLibelle(): void
    {
        $u = new Utilisateur();
        $u->setSituationSociale('BO', "Boursier d'État");

        self::assertSame('BO', $u->getCodeSituationSociale());
        self::assertSame("Boursier d'État", $u->getLibelleSituationSociale());
        self::assertTrue($u->isBoursier());
    }

    /**
     * @dataProvider nonBursaryCodes
     */
    public function testNonBoCodeDoesNotMarkBoursier(string $code, string $libelle): void
    {
        $u = new Utilisateur();
        $u->setSituationSociale($code, $libelle);

        self::assertSame($code, $u->getCodeSituationSociale());
        self::assertSame($libelle, $u->getLibelleSituationSociale());
        self::assertFalse($u->isBoursier());
    }

    public static function nonBursaryCodes(): array
    {
        return [
            'non boursier' => ['NO', 'Non boursier'],
            'pupille de la nation' => ['PU', 'Pupille de la Nation'],
            'aide ponctuelle' => ['AP', 'Aide ponctuelle'],
        ];
    }

    public function testNullCodeKeepsBoursierUnknown(): void
    {
        $u = new Utilisateur();
        // pré-état : boursier=true via un appel BO
        $u->setSituationSociale('BO', "Boursier d'État");
        self::assertTrue($u->isBoursier());

        // code remis à null => boursier inconnu (null), pas false
        $u->setSituationSociale(null, null);

        self::assertNull($u->getCodeSituationSociale());
        self::assertNull($u->getLibelleSituationSociale());
        self::assertNull($u->isBoursier());
    }

    public function testEmptyOrWhitespaceCodeIsTreatedAsNull(): void
    {
        $u = new Utilisateur();
        $u->setSituationSociale('   ', '   ');

        self::assertNull($u->getCodeSituationSociale());
        self::assertNull($u->getLibelleSituationSociale());
        self::assertNull($u->isBoursier());
    }

    public function testValuesAreTrimmed(): void
    {
        $u = new Utilisateur();
        $u->setSituationSociale("  BO\n", "  Boursier d'État  ");

        self::assertSame('BO', $u->getCodeSituationSociale());
        self::assertSame("Boursier d'État", $u->getLibelleSituationSociale());
        self::assertTrue($u->isBoursier());
    }
}
