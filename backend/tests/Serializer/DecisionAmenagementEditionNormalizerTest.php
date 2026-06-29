<?php

declare(strict_types=1);

namespace App\Tests\Serializer;

use App\ApiResource\DecisionAmenagementExamens as DecisionAmenagementExamensResource;
use App\Entity\Amenagement;
use App\Entity\DecisionAmenagementExamens;
use App\Entity\TypeAmenagement;
use App\Entity\Utilisateur;
use App\Repository\ParametreRepository;
use App\Serializer\DecisionAmenagementEditionNormalizer;
use App\Service\FileStorage\StorageProviderInterface;
use App\State\DecisionAmenagementExamens\DecisionAmenagementManager;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

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

    public function testNormalizeForwardsObservationsToTwigPayload(): void
    {
        $beneficiaire = new Utilisateur();
        $beneficiaire->setUid('benef-uid');

        $entity = new DecisionAmenagementExamens();
        $entity->setBeneficiaire($beneficiaire);
        $entity->setDebut(new DateTimeImmutable('2026-09-01'));
        $entity->setFin(new DateTimeImmutable('2027-08-31'));
        $entity->setObservations("Salle isolée\nTemps majoré uniquement le matin");

        $manager = $this->createStub(DecisionAmenagementManager::class);
        $manager->method('parUidEtAnnee')->willReturn($entity);

        $parametreRepository = $this->createStub(ParametreRepository::class);
        // Most cle queries are nullable; SIGNATURE_DECISIONS is dereferenced
        // unconditionally so we always return at least an empty Parametre.
        $parametreRepository->method('findOneBy')->willReturnCallback(
            fn(array $criteria) => $criteria['cle'] === \App\Entity\Parametre::SIGNATURE_DECISIONS
                ? new \App\Entity\Parametre()
                : null,
        );

        $normalizer = new DecisionAmenagementEditionNormalizer(
            $manager,
            $this->createStub(StorageProviderInterface::class),
            $parametreRepository,
        );
        $normalizer->setClock(new MockClock(new DateTimeImmutable('2026-06-25 12:00:00')));

        $resource = new DecisionAmenagementExamensResource($entity);

        $data = $normalizer->normalize($resource, 'pdf');

        self::assertArrayHasKey('observations', $data);
        self::assertSame("Salle isolée\nTemps majoré uniquement le matin", $data['observations']);
    }

    public function testNormalizeForwardsNullObservationsWhenNotSet(): void
    {
        $beneficiaire = new Utilisateur();
        $beneficiaire->setUid('benef-uid');

        $entity = new DecisionAmenagementExamens();
        $entity->setBeneficiaire($beneficiaire);
        $entity->setDebut(new DateTimeImmutable('2026-09-01'));
        $entity->setFin(new DateTimeImmutable('2027-08-31'));
        // No observations set -> remains null

        $manager = $this->createStub(DecisionAmenagementManager::class);
        $manager->method('parUidEtAnnee')->willReturn($entity);

        $parametreRepository = $this->createStub(ParametreRepository::class);
        // Most cle queries are nullable; SIGNATURE_DECISIONS is dereferenced
        // unconditionally so we always return at least an empty Parametre.
        $parametreRepository->method('findOneBy')->willReturnCallback(
            fn(array $criteria) => $criteria['cle'] === \App\Entity\Parametre::SIGNATURE_DECISIONS
                ? new \App\Entity\Parametre()
                : null,
        );

        $normalizer = new DecisionAmenagementEditionNormalizer(
            $manager,
            $this->createStub(StorageProviderInterface::class),
            $parametreRepository,
        );
        $normalizer->setClock(new MockClock(new DateTimeImmutable('2026-06-25 12:00:00')));

        $resource = new DecisionAmenagementExamensResource($entity);

        $data = $normalizer->normalize($resource, 'pdf');

        self::assertArrayHasKey('observations', $data);
        self::assertNull($data['observations']);
    }

    public function testNormalizeForwardsDateAvisMedecinToTwigPayload(): void
    {
        $beneficiaire = new Utilisateur();
        $beneficiaire->setUid('benef-uid');

        $dateAvisMedecin = new DateTimeImmutable('2026-06-15');

        $entity = new DecisionAmenagementExamens();
        $entity->setBeneficiaire($beneficiaire);
        $entity->setDebut(new DateTimeImmutable('2026-09-01'));
        $entity->setFin(new DateTimeImmutable('2027-08-31'));
        $entity->setDateAvisMedecin($dateAvisMedecin);

        $manager = $this->createStub(DecisionAmenagementManager::class);
        $manager->method('parUidEtAnnee')->willReturn($entity);

        $parametreRepository = $this->createStub(ParametreRepository::class);
        $parametreRepository->method('findOneBy')->willReturnCallback(
            fn(array $criteria) => $criteria['cle'] === \App\Entity\Parametre::SIGNATURE_DECISIONS
                ? new \App\Entity\Parametre()
                : null,
        );

        $normalizer = new DecisionAmenagementEditionNormalizer(
            $manager,
            $this->createStub(StorageProviderInterface::class),
            $parametreRepository,
        );
        $normalizer->setClock(new MockClock(new DateTimeImmutable('2026-06-25 12:00:00')));

        $resource = new DecisionAmenagementExamensResource($entity);

        $data = $normalizer->normalize($resource, 'pdf');

        self::assertArrayHasKey('dateAvisMedecin', $data);
        self::assertInstanceOf(\DateTimeInterface::class, $data['dateAvisMedecin']);
        self::assertSame('2026-06-15', $data['dateAvisMedecin']->format('Y-m-d'));
    }

    public function testNormalizeForwardsNullDateAvisMedecinWhenNotSet(): void
    {
        $beneficiaire = new Utilisateur();
        $beneficiaire->setUid('benef-uid');

        $entity = new DecisionAmenagementExamens();
        $entity->setBeneficiaire($beneficiaire);
        $entity->setDebut(new DateTimeImmutable('2026-09-01'));
        $entity->setFin(new DateTimeImmutable('2027-08-31'));
        // No dateAvisMedecin set -> remains null

        $manager = $this->createStub(DecisionAmenagementManager::class);
        $manager->method('parUidEtAnnee')->willReturn($entity);

        $parametreRepository = $this->createStub(ParametreRepository::class);
        $parametreRepository->method('findOneBy')->willReturnCallback(
            fn(array $criteria) => $criteria['cle'] === \App\Entity\Parametre::SIGNATURE_DECISIONS
                ? new \App\Entity\Parametre()
                : null,
        );

        $normalizer = new DecisionAmenagementEditionNormalizer(
            $manager,
            $this->createStub(StorageProviderInterface::class),
            $parametreRepository,
        );
        $normalizer->setClock(new MockClock(new DateTimeImmutable('2026-06-25 12:00:00')));

        $resource = new DecisionAmenagementExamensResource($entity);

        $data = $normalizer->normalize($resource, 'pdf');

        self::assertArrayHasKey('dateAvisMedecin', $data);
        self::assertNull($data['dateAvisMedecin']);
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
