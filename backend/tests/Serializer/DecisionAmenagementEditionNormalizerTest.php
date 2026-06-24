<?php

declare(strict_types=1);

namespace App\Tests\Serializer;

use App\Entity\Amenagement;
use App\Entity\TypeAmenagement;
use App\Repository\ParametreRepository;
use App\Serializer\DecisionAmenagementEditionNormalizer;
use App\Service\FileStorage\StorageProviderInterface;
use App\State\DecisionAmenagementExamens\DecisionAmenagementManager;
use PHPUnit\Framework\TestCase;

final class DecisionAmenagementEditionNormalizerTest extends TestCase
{
    private DecisionAmenagementEditionNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new DecisionAmenagementEditionNormalizer(
            $this->createStub(DecisionAmenagementManager::class),
            $this->createStub(StorageProviderInterface::class),
            $this->createStub(ParametreRepository::class),
        );
    }

    public function testGroupByCategorieReturnsThreeBucketsEvenWhenEmpty(): void
    {
        $groupes = $this->normalizer->groupByCategorie([]);

        self::assertSame(['etudes', 'aidesHumaines', 'examens'], array_keys($groupes));
        self::assertSame([], $groupes['etudes']);
        self::assertSame([], $groupes['aidesHumaines']);
        self::assertSame([], $groupes['examens']);
    }

    public function testGroupByCategorieDispatchesEachAmenagementByItsTypeFlags(): void
    {
        $etudes = $this->makeAmenagement(pedagogique: true);
        $aideHumaine = $this->makeAmenagement(aideHumaine: true);
        $examens = $this->makeAmenagement(examens: true);

        $groupes = $this->normalizer->groupByCategorie([$etudes, $aideHumaine, $examens]);

        self::assertSame([$etudes], $groupes['etudes']);
        self::assertSame([$aideHumaine], $groupes['aidesHumaines']);
        self::assertSame([$examens], $groupes['examens']);
    }

    public function testGroupByCategorieIncludesAmenagementInEveryMatchingCategory(): void
    {
        // Some amenagement types may flag both pedagogique and examens
        // (e.g. tiers-temps applicable both in class and during exams).
        $polyvalent = $this->makeAmenagement(pedagogique: true, examens: true);

        $groupes = $this->normalizer->groupByCategorie([$polyvalent]);

        self::assertSame([$polyvalent], $groupes['etudes']);
        self::assertSame([], $groupes['aidesHumaines']);
        self::assertSame([$polyvalent], $groupes['examens']);
    }

    public function testGroupByCategorieIgnoresAmenagementWithoutType(): void
    {
        $orphan = new Amenagement();

        $groupes = $this->normalizer->groupByCategorie([$orphan]);

        self::assertSame([], $groupes['etudes']);
        self::assertSame([], $groupes['aidesHumaines']);
        self::assertSame([], $groupes['examens']);
    }

    public function testGroupByCategorieDoesNotPlaceAmenagementWhenAllFlagsAreFalse(): void
    {
        $unflagged = $this->makeAmenagement();

        $groupes = $this->normalizer->groupByCategorie([$unflagged]);

        self::assertSame([], $groupes['etudes']);
        self::assertSame([], $groupes['aidesHumaines']);
        self::assertSame([], $groupes['examens']);
    }

    private function makeAmenagement(
        bool $pedagogique = false,
        bool $aideHumaine = false,
        bool $examens = false,
    ): Amenagement {
        $type = (new TypeAmenagement())
            ->setLibelle('libelle')
            ->setActif(true)
            ->setPedagogique($pedagogique)
            ->setAideHumaine($aideHumaine)
            ->setExamens($examens);

        $amenagement = new Amenagement();
        $amenagement->setType($type);

        return $amenagement;
    }
}
