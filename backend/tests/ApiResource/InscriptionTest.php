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
 * Le focus est la dérivation du flag `redoublant` : on s'assure que la
 * règle officielle DSI traverse bien le ResourceProvider — une entité
 * `Inscription` avec `nbrInsEtp = 2` et sans `code_sis_cur_amg` doit
 * ressortir `redoublant = true` sur l'ApiResource exposée à l'API.
 */
final class InscriptionTest extends TestCase
{
    public function testRedoublantTrueWhenCompteurGreaterThanOneAndNoCursusAmenage(): void
    {
        $entity = new InscriptionEntity();
        $entity->setNbrInsEtp(2);
        $entity->setCodeSisCurAmg(null);

        $resource = new InscriptionResource($entity);

        self::assertTrue($resource->redoublant);
        self::assertSame(2, $resource->nbrInsEtp);
        self::assertNull($resource->cursusAmenage);
    }

    public function testRedoublantFalseWhenCursusAmenageRenseigne(): void
    {
        $entity = new InscriptionEntity();
        $entity->setNbrInsEtp(3);
        $entity->setCodeSisCurAmg('CA42');
        $entity->setLibCurAmg('Cursus aménagé sport haut niveau');

        $resource = new InscriptionResource($entity);

        self::assertFalse($resource->redoublant);
        self::assertSame(3, $resource->nbrInsEtp);
        self::assertSame(
            ['code' => 'CA42', 'libelle' => 'Cursus aménagé sport haut niveau'],
            $resource->cursusAmenage,
        );
    }

    public function testRedoublantFalseWhenCompteurEqualsOne(): void
    {
        $entity = new InscriptionEntity();
        $entity->setNbrInsEtp(1);
        $entity->setCodeSisCurAmg(null);

        $resource = new InscriptionResource($entity);

        self::assertFalse($resource->redoublant);
        self::assertSame(1, $resource->nbrInsEtp);
        self::assertNull($resource->cursusAmenage);
    }

    public function testRedoublantFalseWhenCompteurNull(): void
    {
        $entity = new InscriptionEntity();
        $entity->setNbrInsEtp(null);
        $entity->setCodeSisCurAmg(null);

        $resource = new InscriptionResource($entity);

        self::assertFalse($resource->redoublant);
        self::assertNull($resource->nbrInsEtp);
        self::assertNull($resource->cursusAmenage);
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
