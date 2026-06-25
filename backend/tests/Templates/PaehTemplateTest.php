<?php

declare(strict_types=1);

namespace App\Tests\Templates;

use App\Entity\Amenagement;
use App\Entity\Beneficiaire;
use App\Entity\TypeAmenagement;
use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class PaehTemplateTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = new Environment(new FilesystemLoader(dirname(__DIR__, 2) . '/templates'));
    }

    public function testTemplateRendersTheThreeCategorySectionsWhenAllAreNonEmpty(): void
    {
        $html = $this->renderWith(
            etudes: [$this->amenagement('Tiers-temps en cours', pedagogique: true)],
            aidesHumaines: [$this->amenagement('Preneur de notes', aideHumaine: true)],
            examens: [$this->amenagement('Tiers-temps aux examens', examens: true)],
        );

        self::assertStringContainsString("Aménagements d'études", $html);
        self::assertStringContainsString('Aides humaines', $html);
        self::assertStringContainsString("Aménagements d'examens", $html);

        self::assertStringContainsString('Tiers-temps en cours', $html);
        self::assertStringContainsString('Preneur de notes', $html);
        self::assertStringContainsString('Tiers-temps aux examens', $html);
    }

    public function testTemplateOnlyShowsCategoriesThatHaveAmenagements(): void
    {
        $html = $this->renderWith(
            etudes: [],
            aidesHumaines: [],
            examens: [$this->amenagement('Tiers-temps aux examens', examens: true)],
        );

        self::assertStringNotContainsString("Aménagements d'études", $html);
        self::assertStringNotContainsString('Aides humaines', $html);
        self::assertStringContainsString("Aménagements d'examens", $html);
    }

    public function testTemplatePluralizesAmenagementWordingFromTotalCount(): void
    {
        $multipleHtml = $this->renderWith(
            etudes: [$this->amenagement('A', pedagogique: true), $this->amenagement('B', pedagogique: true)],
            aidesHumaines: [],
            examens: [],
        );
        $singleHtml = $this->renderWith(
            etudes: [],
            aidesHumaines: [],
            examens: [$this->amenagement('A', examens: true)],
        );

        self::assertStringContainsString('des aménagements suivants', $multipleHtml);
        self::assertStringContainsString("de l'aménagement suivant", $singleHtml);
    }

    public function testTemplateKeepsTheUpstreamDecisionEtablissementTitle(): void
    {
        $html = $this->renderWith(etudes: [], aidesHumaines: [], examens: []);

        // The title is a static literal in the template, so Twig outputs it verbatim.
        self::assertStringContainsString("<title>Décision d'établissement</title>", $html);
    }

    public function testTemplateRendersObservationsBlockWhenProvided(): void
    {
        $html = $this->renderWith(
            etudes: [$this->amenagement('Tiers-temps en cours', pedagogique: true)],
            aidesHumaines: [],
            examens: [],
            observations: "Suivi médical à prévoir.\nContact prochain semestre.",
        );

        self::assertStringContainsString('Observations particulières', $html);
        self::assertStringContainsString('Suivi médical à prévoir.', $html);
        self::assertStringContainsString('Contact prochain semestre.', $html);
        self::assertStringContainsString('<section class="observations">', $html);
    }

    public function testTemplateHidesObservationsBlockWhenNullOrEmpty(): void
    {
        $htmlNull = $this->renderWith(
            etudes: [$this->amenagement('Tiers-temps en cours', pedagogique: true)],
            aidesHumaines: [],
            examens: [],
            observations: null,
        );
        $htmlBlank = $this->renderWith(
            etudes: [$this->amenagement('Tiers-temps en cours', pedagogique: true)],
            aidesHumaines: [],
            examens: [],
            observations: "   \n  ",
        );

        self::assertStringNotContainsString('Observations particulières', $htmlNull);
        self::assertStringNotContainsString('<section class="observations">', $htmlNull);
        self::assertStringNotContainsString('Observations particulières', $htmlBlank);
        self::assertStringNotContainsString('<section class="observations">', $htmlBlank);
    }

    public function testDestinataireRendersBirthDateAndStudentNumberWhenPresent(): void
    {
        $html = $this->renderWith(
            etudes: [],
            aidesHumaines: [],
            examens: [$this->amenagement('Tiers-temps aux examens', examens: true)],
            dateNaissance: new \DateTimeImmutable('2002-04-15'),
            numeroEtudiant: '21800123',
        );

        self::assertStringContainsString('Né(e) le 15/04/2002', $html);
        self::assertStringContainsString('N° étudiant : 21800123', $html);
    }

    public function testDestinataireOmitsBirthDateAndStudentNumberWhenAbsent(): void
    {
        $html = $this->renderWith(
            etudes: [],
            aidesHumaines: [],
            examens: [$this->amenagement('Tiers-temps aux examens', examens: true)],
            dateNaissance: null,
            numeroEtudiant: null,
        );

        self::assertStringNotContainsString('Né(e) le', $html);
        self::assertStringNotContainsString('N° étudiant', $html);
    }

    /**
     * @param list<Amenagement> $etudes
     * @param list<Amenagement> $aidesHumaines
     * @param list<Amenagement> $examens
     */
    private function renderWith(
        array $etudes,
        array $aidesHumaines,
        array $examens,
        ?string $observations = null,
        ?\DateTimeInterface $dateNaissance = null,
        int|string|null $numeroEtudiant = null,
    ): string {
        $etudiant = (new Utilisateur())
            ->setNom('DOE')
            ->setPrenom('Jane')
            ->setEmail('jane@example.org');
        if ($dateNaissance !== null) {
            $etudiant->setDateNaissance($dateNaissance);
        }
        if ($numeroEtudiant !== null) {
            $etudiant->setNumeroEtudiant((int) $numeroEtudiant);
        }

        $gestionnaire = (new Utilisateur())
            ->setNom('Dupont')
            ->setPrenom('Alice')
            ->setEmail('alice@example.org');

        $beneficiaire = (new Beneficiaire())
            ->setUtilisateur($etudiant)
            ->setGestionnaire($gestionnaire);

        // Header reads premierAmenagement.beneficiaires|first — attach the beneficiary
        // to a header-only amenagement that is NOT exposed in categories.
        $headerAmenagement = $this->amenagement('__header__');
        $headerAmenagement->addBeneficiaire($beneficiaire);

        $data = [
            'amenagements' => [$headerAmenagement, ...$etudes, ...$aidesHumaines, ...$examens],
            'amenagementsParCategorie' => [
                'etudes' => $etudes,
                'aidesHumaines' => $aidesHumaines,
                'examens' => $examens,
            ],
            'observations' => $observations,
            'annee' => 2026,
            'president' => ['qualite' => 'Le President', 'nom' => 'P. NOMME'],
            'responsable_phase' => [
                'qualite' => 'Le responsable',
                'nom' => 'R. NOMME',
                'signature' => ['contents' => null, 'mimeType' => null],
            ],
        ];

        // Stub Symfony's `app` Twig global only with the env value the template reads.
        $appStub = new class {
            public string $environment = 'test';
        };

        return $this->twig->render('Decisions/index.html.twig', [
            'data' => $data,
            'backUrl' => 'http://localhost',
            'app' => $appStub,
        ]);
    }

    private function amenagement(
        string $libelle,
        bool $pedagogique = false,
        bool $aideHumaine = false,
        bool $examens = false,
    ): Amenagement {
        $type = (new TypeAmenagement())
            ->setLibelle($libelle)
            ->setActif(true)
            ->setPedagogique($pedagogique)
            ->setAideHumaine($aideHumaine)
            ->setExamens($examens);

        $amenagement = new Amenagement();
        $amenagement->setType($type);

        return $amenagement;
    }
}
