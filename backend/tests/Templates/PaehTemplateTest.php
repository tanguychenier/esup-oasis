<?php

declare(strict_types=1);

namespace App\Tests\Templates;

use App\Entity\Amenagement;
use App\Entity\TypeAmenagement;
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

    /**
     * @param list<Amenagement> $etudes
     * @param list<Amenagement> $aidesHumaines
     * @param list<Amenagement> $examens
     */
    private function renderWith(
        array $etudes,
        array $aidesHumaines,
        array $examens,
    ): string {
        $beneficiaire = (object)[
            'utilisateur' => (object)['nom' => 'DOE', 'prenom' => 'Jane', 'email' => 'jane@example.org'],
            'gestionnaire' => (object)['prenom' => 'Alice', 'nom' => 'Dupont', 'email' => 'alice@example.org'],
        ];

        $data = [
            'amenagements' => [...$etudes, ...$aidesHumaines, ...$examens],
            'amenagementsParCategorie' => [
                'etudes' => $etudes,
                'aidesHumaines' => $aidesHumaines,
                'examens' => $examens,
            ],
            'annee' => 2026,
            'president' => ['qualite' => 'Le President', 'nom' => 'P. NOMME'],
            'responsable_phase' => [
                'qualite' => 'Le responsable',
                'nom' => 'R. NOMME',
                'signature' => ['contents' => null, 'mimeType' => null],
            ],
        ];
        // Element [0] is read by the header to extract beneficiary info — emulate that shape.
        $data[0] = (object)[
            'amenagements' => [(object)['beneficiaires' => [$beneficiaire]]],
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
