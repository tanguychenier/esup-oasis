<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 *  @author Manuel Rossard <manuel.rossard@u-bordeaux.fr>
 *
 */

namespace App\ApiResource;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Inscription\InscriptionProvider;
use DateTimeInterface;
use ReflectionProperty;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(operations: [
    new Get(
        uriTemplate: self::ITEM_URI,
        uriVariables: ['id'],
        openapi: false,
        security: "is_granted('" . self::VOIR_INSCRIPTION . "', object)",
        provider: InscriptionProvider::class,
        stateOptions: new Options(entityClass: \App\Entity\Inscription::class),
    ),
])]
final class Inscription
{
    public const string COLLECTION_URI = '/inscriptions';
    public const string ITEM_URI = self::COLLECTION_URI . '/{id}';

    public const string VOIR_INSCRIPTION = 'VOIR_INSCRIPTION';

    #[ApiProperty(identifier: true)]
    public int $id {
        get {
            $prop = new ReflectionProperty(self::class, 'id');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->id = $this->entity->getId();
            }
            return $this->id;
        }
    }

    #[Groups([
        Utilisateur::GROUP_OUT,
        Demande::GROUP_OUT,
        Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT,
        Amenagement::GROUP_OUT,
    ])]
    public Formation $formation {
        get {
            $prop = new ReflectionProperty(self::class, 'formation');
            if (!$prop->isInitialized($this) && $this->entity !== null && $this->entity->getFormation()) {
                $this->formation = new Formation($this->entity->getFormation());
            }
            return $this->formation;
        }
    }

    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public DateTimeInterface $debut {
        get {
            $prop = new ReflectionProperty(self::class, 'debut');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->debut = $this->entity->getDebut();
            }
            return $this->debut;
        }
    }
    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public DateTimeInterface $fin {
        get {
            $prop = new ReflectionProperty(self::class, 'fin');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->fin = $this->entity->getFin();
            }
            return $this->fin;
        }
    }

    /**
     * OBC-3 — code étape Apogée conservé pour exposer le cursus
     * d'inscription du bénéficiaire et permettre au front de regrouper
     * les inscriptions du même parcours.
     */
    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public ?string $codeEtape {
        get {
            $prop = new ReflectionProperty(self::class, 'codeEtape');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->codeEtape = $this->entity->getCodeEtape();
            }
            return $this->codeEtape ?? null;
        }
    }

    /**
     * OBC-3 — niveau d'études (L1/L2/L3/M1/M2/D1/D2/D3) dérivé à la volée
     * du préfixe du code étape via NiveauExtractor. null pour les codes
     * hors barème (PASS, LAS, codes locaux). Jamais persisté.
     *
     * Positionné en post-traitement de Formation::niveau : ce dernier
     * porte le niveau brut Apogée (NIVEAU) du diplôme, tandis que cette
     * propriété expose un niveau LMD normalisé par inscription.
     */
    #[Groups([Utilisateur::GROUP_OUT, Demande::GROUP_OUT, Utilisateur::AMENAGEMENTS_UTILISATEURS_OUT])]
    public ?string $niveau {
        get {
            $prop = new ReflectionProperty(self::class, 'niveau');
            if (!$prop->isInitialized($this) && $this->entity !== null) {
                $this->niveau = (new \App\Service\SiScol\NiveauExtractor())->extract($this->entity->getCodeEtape());
            }
            return $this->niveau ?? null;
        }
    }

    public function __construct(
        private readonly ?\App\Entity\Inscription $entity = null,
    ) {}
}
