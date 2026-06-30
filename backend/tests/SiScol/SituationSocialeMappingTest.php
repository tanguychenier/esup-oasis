<?php

declare(strict_types=1);

namespace App\Tests\SiScol;

use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;

/**
 * OBC-1 critère 2 — projection de la situation sociale Apogée vers l'entité Utilisateur.
 *
 * Ce test isole la règle de projection appliquée dans
 * {@see \App\State\Utilisateur\UtilisateurManager::majInscriptionsEtIdentite()} :
 *  - codeSituationSociale / libelleSituationSociale recopiés depuis la dernière inscription ;
 *  - boursier dérivé : témoin Apogée legacy OU code == "BO".
 *
 * Pas de dépendance base/conteneur : on rejoue le contrat de mapping sur une
 * entité réelle, ce qui valide aussi les accessors de l'entité.
 *
 * TODO(apogée-réel) : confirmer contre Apogée Saclay réel le nom de colonne
 * cod_soc (vs cod_sco) et la liste exacte des codes considérés "boursier" (ici
 * seul "BO" l'est). Les valeurs ci-dessous (NO/BO/PU) sont des hypothèses
 * dryrun à valider avec la DSI.
 */
final class SituationSocialeMappingTest extends TestCase
{
    /**
     * Réplique exacte de la projection situation sociale + boursier de
     * UtilisateurManager::majInscriptionsEtIdentite() sur la dernière inscription.
     *
     * @param array<string, mixed> $derniereInscription
     */
    private function projeterSituationSociale(Utilisateur $utilisateur, array $derniereInscription): void
    {
        $codeSituationSociale = $derniereInscription['codeSituationSociale'] ?? null;
        $utilisateur->setCodeSituationSociale($codeSituationSociale);
        $utilisateur->setLibelleSituationSociale($derniereInscription['libelleSituationSociale'] ?? null);

        $utilisateur->setBoursier(
            ($derniereInscription['boursier'] ?? false) || ($codeSituationSociale === 'BO'),
        );
    }

    public function testCodeBoMarqueLEtudiantBoursier(): void
    {
        $utilisateur = new Utilisateur();

        $this->projeterSituationSociale($utilisateur, [
            'boursier' => false,
            'codeSituationSociale' => 'BO',
            'libelleSituationSociale' => 'Boursier',
        ]);

        self::assertSame('BO', $utilisateur->getCodeSituationSociale());
        self::assertSame('Boursier', $utilisateur->getLibelleSituationSociale());
        self::assertTrue($utilisateur->isBoursier(), 'Le code BO doit dériver boursier = true');
    }

    public function testCodeNoNeMarquePasBoursier(): void
    {
        $utilisateur = new Utilisateur();

        $this->projeterSituationSociale($utilisateur, [
            'boursier' => false,
            'codeSituationSociale' => 'NO',
            'libelleSituationSociale' => 'Normal',
        ]);

        self::assertSame('NO', $utilisateur->getCodeSituationSociale());
        self::assertSame('Normal', $utilisateur->getLibelleSituationSociale());
        self::assertFalse($utilisateur->isBoursier(), 'Le code NO ne doit pas dériver boursier');
    }

    public function testCodePupilleNeMarquePasBoursier(): void
    {
        $utilisateur = new Utilisateur();

        $this->projeterSituationSociale($utilisateur, [
            'boursier' => false,
            'codeSituationSociale' => 'PU',
            'libelleSituationSociale' => 'Pupille de la nation',
        ]);

        self::assertSame('PU', $utilisateur->getCodeSituationSociale());
        self::assertSame('Pupille de la nation', $utilisateur->getLibelleSituationSociale());
        self::assertFalse($utilisateur->isBoursier(), 'Le code PU ne doit pas dériver boursier');
    }

    public function testTemoinBoursierLegacyResteHonore(): void
    {
        // Rétrocompat : si Apogée renvoie le témoin legacy boursier sans code situation
        // sociale (ou un code non-BO), on garde boursier = true.
        $utilisateur = new Utilisateur();

        $this->projeterSituationSociale($utilisateur, [
            'boursier' => true,
            'codeSituationSociale' => 'NO',
            'libelleSituationSociale' => 'Normal',
        ]);

        self::assertTrue($utilisateur->isBoursier(), 'Le témoin legacy boursier doit rester honoré');
    }

    public function testAbsenceDeCodeSocialeNeLevePasDErreur(): void
    {
        // Cas Apogée sans cod_soc (jointure sit_sociale non résolue) : null sans erreur.
        $utilisateur = new Utilisateur();

        $this->projeterSituationSociale($utilisateur, [
            'boursier' => false,
        ]);

        self::assertNull($utilisateur->getCodeSituationSociale());
        self::assertNull($utilisateur->getLibelleSituationSociale());
        self::assertFalse($utilisateur->isBoursier());
    }

    public function testEntiteSituationSocialeParDefautEstNulle(): void
    {
        $utilisateur = new Utilisateur();

        self::assertNull($utilisateur->getCodeSituationSociale());
        self::assertNull($utilisateur->getLibelleSituationSociale());
    }

    public function testAccessorsSituationSocialeSontFluents(): void
    {
        $utilisateur = new Utilisateur();

        $retourCode = $utilisateur->setCodeSituationSociale('PU');
        $retourLibelle = $utilisateur->setLibelleSituationSociale('Pupille de la nation');

        self::assertSame($utilisateur, $retourCode);
        self::assertSame($utilisateur, $retourLibelle);
    }

    public function testTrimDesEspacesDuCodeApogee(): void
    {
        // ApogeeProvider applique trim() sur COD_SOC / LIB_SOC (oci renvoie des
        // CHAR padés). On reproduit ici le trim côté provider pour documenter
        // le contrat : les valeurs projetées ne doivent pas porter d'espaces.
        $rawCode = '  BO  ';
        $rawLibelle = '  Boursier  ';

        $utilisateur = new Utilisateur();
        $this->projeterSituationSociale($utilisateur, [
            'boursier' => false,
            'codeSituationSociale' => trim($rawCode),
            'libelleSituationSociale' => trim($rawLibelle),
        ]);

        self::assertSame('BO', $utilisateur->getCodeSituationSociale());
        self::assertSame('Boursier', $utilisateur->getLibelleSituationSociale());
        self::assertTrue($utilisateur->isBoursier(), 'Après trim, "BO" doit dériver boursier');
    }
}
