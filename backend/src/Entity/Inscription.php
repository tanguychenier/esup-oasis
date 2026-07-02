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

namespace App\Entity;

use App\Repository\InscriptionRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
#[Index(name: 'IDX_INSCRIPTION_FIN', columns: ['fin'])]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $etudiant = null;

    #[ORM\ManyToOne(inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Formation $formation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $fin = null;

    /**
     * Code étape Apogée (cod_etp), conservé pour exposer le cursus
     * d'inscription et en dériver le niveau d'études (L1/L2/L3/M1/M2/D1-D3).
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $codeEtape = null;

    /**
     * OBC-3 — compteur natif Apogée (nbr_ins_etp) du nombre
     * d'inscriptions administratives à l'étape. Base officielle du
     * calcul du redoublement (cf. RedoublementCalculator).
     */
    #[ORM\Column(nullable: true)]
    private ?int $nombreInscriptionsEtape = null;

    /**
     * OBC-3 — code du cursus aménagé SISE (cod_sis_cur_amg). Sa présence
     * neutralise le calcul du redoublement (étalement / contrat
     * pédagogique pluri-annuel) et alimente l'affichage "cursus aménagé".
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $codeCursusAmenage = null;

    /**
     * OBC-3 — libellé du cursus aménagé (lib_cur_amg), affiché tel quel
     * sur la fiche bénéficiaire.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $libelleCursusAmenage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEtudiant(): ?Utilisateur
    {
        return $this->etudiant;
    }

    public function setEtudiant(?Utilisateur $etudiant): self
    {
        $this->etudiant = $etudiant;

        return $this;
    }

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): self
    {
        $this->formation = $formation;

        return $this;
    }

    public function getDebut(): ?DateTimeInterface
    {
        return $this->debut;
    }

    public function setDebut(DateTimeInterface $debut): self
    {
        $this->debut = DateTime::createFromInterface($debut);

        return $this;
    }

    public function getFin(): ?DateTimeInterface
    {
        return $this->fin;
    }

    public function setFin(DateTimeInterface $fin): self
    {
        $this->fin = DateTime::createFromInterface($fin);

        return $this;
    }

    public function getCodeEtape(): ?string
    {
        return $this->codeEtape;
    }

    public function setCodeEtape(?string $codeEtape): self
    {
        $this->codeEtape = $codeEtape;

        return $this;
    }

    public function getNombreInscriptionsEtape(): ?int
    {
        return $this->nombreInscriptionsEtape;
    }

    public function setNombreInscriptionsEtape(?int $nombreInscriptionsEtape): self
    {
        $this->nombreInscriptionsEtape = $nombreInscriptionsEtape;

        return $this;
    }

    public function getCodeCursusAmenage(): ?string
    {
        return $this->codeCursusAmenage;
    }

    public function setCodeCursusAmenage(?string $codeCursusAmenage): self
    {
        $this->codeCursusAmenage = $codeCursusAmenage;

        return $this;
    }

    public function getLibelleCursusAmenage(): ?string
    {
        return $this->libelleCursusAmenage;
    }

    public function setLibelleCursusAmenage(?string $libelleCursusAmenage): self
    {
        $this->libelleCursusAmenage = $libelleCursusAmenage;

        return $this;
    }
}
