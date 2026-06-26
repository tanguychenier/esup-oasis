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
     * Compteur Apogée NBR_INS_ETP : nombre d'inscriptions cumulées de
     * l'étudiant à l'étape (cod_etp). Source officielle (mail DSI Fatiha
     * 2026-06) pour qualifier le redoublement.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $nbrInsEtp = null;

    /**
     * Code SISE national du cursus aménagé (cod_sis_cur_amg). Renseigné
     * pour les étudiants engagés dans un cursus aménagé Apogée — auquel
     * cas un compteur d'inscriptions > 1 ne correspond pas à un
     * redoublement mais à un parcours pluri-annuel négocié.
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $codeSisCurAmg = null;

    /**
     * Libellé Apogée du cursus aménagé (lib_cur_amg) joint à
     * `cod_sis_cur_amg`, exposé pour l'affichage côté API.
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $libCurAmg = null;

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

    public function getNbrInsEtp(): ?int
    {
        return $this->nbrInsEtp;
    }

    public function setNbrInsEtp(?int $nbrInsEtp): self
    {
        $this->nbrInsEtp = $nbrInsEtp;

        return $this;
    }

    public function getCodeSisCurAmg(): ?string
    {
        return $this->codeSisCurAmg;
    }

    public function setCodeSisCurAmg(?string $codeSisCurAmg): self
    {
        $this->codeSisCurAmg = $codeSisCurAmg;

        return $this;
    }

    public function getLibCurAmg(): ?string
    {
        return $this->libCurAmg;
    }

    public function setLibCurAmg(?string $libCurAmg): self
    {
        $this->libCurAmg = $libCurAmg;

        return $this;
    }
}
