<?php

declare(strict_types=1);

namespace App\Tests\ApiResource;

use App\ApiResource\Inscription as InscriptionResource;
use App\Entity\Inscription as InscriptionEntity;
use PHPUnit\Framework\TestCase;

/**
 * OBC-3 — vérifie le pont entre l'entité (champs Apogée bruts) et
 * l'ApiResource (champs dérivés exposés au front).
 *
 * Le flag `redoublant` est désormais calculé directement par la requête
 * SQL Apogée (cf. `config/apogee/apogee_get_inscriptions.sql`) et persisté
 * sur l'entité. L'ApiResource se contente d'exposer le booléen côté API.
 */
final class InscriptionTest extends TestCase
{
    public function testRedoublantTrueIsExposedFromEntity(): void
    {
        $entity = new InscriptionEntity();
        $entity->setRedoublant(true);

        $resource = new InscriptionResource($entity);

        self::assertTrue($resource->redoublant);
        self::assertNull($resource->cursusAmenage);
    }

    public function testRedoublantFalseWhenEntityFlagFalse(): void
    {
        $entity = new InscriptionEntity();
        $entity->setRedoublant(false);

        $resource = new InscriptionResource($entity);

        self::assertFalse($resource->redoublant);
    }

    public function testRedoublantFalseWhenEntityFlagNull(): void
    {
        // Donnée non remontée par le connecteur SI : l'ApiResource doit
        // exposer `false` plutôt que faire fuiter le null.
        $entity = new InscriptionEntity();
        $entity->setRedoublant(null);

        $resource = new InscriptionResource($entity);

        self::assertFalse($resource->redoublant);
    }

    public function testCursusAmenageExposedWhenCodePresent(): void
    {
        $entity = new InscriptionEntity();
        $entity->setRedoublant(false);
        $entity->setCodeSisCurAmg('CA42');
        $entity->setLibCurAmg('Cursus aménagé sport haut niveau');

        $resource = new InscriptionResource($entity);

        self::assertFalse($resource->redoublant);
        self::assertSame(
            ['code' => 'CA42', 'libelle' => 'Cursus aménagé sport haut niveau'],
            $resource->cursusAmenage,
        );
    }

    public function testRedoublantFalseOnEmptyResource(): void
    {
        // Sécurité : un ApiResource construit sans entité (e.g. POST sortant
        // d'un autre State Processor) ne doit jamais déclencher d'appel à
        // un getter d'entité null.
        $resource = new InscriptionResource();

        self::assertFalse($resource->redoublant);
    }
}
