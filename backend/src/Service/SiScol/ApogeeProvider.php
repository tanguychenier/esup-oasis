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

namespace App\Service\SiScol;

use App\Entity\Formation;
use App\Entity\Utilisateur;
use DateTime;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ApogeeProvider extends AbstractSiScolDataProvider
{
    public function __construct(
        private readonly string $username,
        #[SensitiveParameter]
        private readonly string $password,
        private readonly string $db,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(file:resolve:APOGEE_REQUETE_INSCRIPTIONS)%')]
        private readonly string $requeteInscriptions,
        #[Autowire('%env(file:resolve:APOGEE_REQUETE_FORMATION)%')]
        private readonly string $requeteFormation,
    ) {}

    /**
     * @inheritDoc
     */
    public function getInscriptions(Utilisateur $etudiant, DateTimeInterface $debut, ?DateTimeInterface $fin): array
    {
        try {
            $db = $this->connect();
        } catch (RuntimeException) {
            $this->logger->warning('Récupération des inscriptions impossible, apogée indisponible');
            throw new BackendUnavailableException();
        }

        $sql = $this->requeteInscriptions;

        $stmt = oci_parse($db, $sql);
        $codEtu = $etudiant->getNumeroEtudiant();
        oci_bind_by_name($stmt, 'codEtu', $codEtu);
        $anneeDebut = $this->getAnneeApogee($debut);
        oci_bind_by_name($stmt, 'debut', $anneeDebut);
        $anneeFin = match ($fin) {
            null => $anneeDebut + 100, //on prend tout ce qu'on trouve depuis l'année de début...
            default => $this->getAnneeApogee($fin),
        };
        oci_bind_by_name($stmt, 'fin', $anneeFin);

        if (!oci_execute($stmt)) {
            $this->logger->warning('Récupération des inscriptions impossible, apogée indisponible');
            return [];
        }
        $formations = [];
        while ($row = oci_fetch_object($stmt)) {
            $numTel = $row->NUM_TEL;
            $dateNai = $row->DATE_NAI_IND;
            $codSexEtu = $row->COD_SEX_ETU;
            $codSoc = isset($row->COD_SOC) ? trim((string) $row->COD_SOC) : null;
            $codSoc = ($codSoc === null || $codSoc === '') ? null : $codSoc;
            $formations[] = [
                'codeFormation' => $row->COD_ETP . '#' . $row->COD_VRS_VET,
                'libFormation' => $row->LIB_WEB_VET,
                'codeComposante' => $row->COD_CMP,
                'libComposante' => $row->LIB_CMP,
                'debut' => new DateTime($row->COD_ANU . '-09-01'),
                'fin' => new DateTime(($row->COD_ANU + 1) . '-08-31'),
                // OBC-1 — situation sociale Apogée (NO/BO/PU...) ; boursier dérivé de COD_SOC = 'BO'.
                'codeSituationSociale' => $codSoc,
                'libelleSituationSociale' => isset($row->LIB_SOC) ? trim($row->LIB_SOC) : null,
                'boursier' => $codSoc === 'BO',
                // OBC-1 — régime d'inscription Apogée (rgi.lib_rgi), stocké dans `statut`
                // pour des raisons historiques. Côté Utilisateur ce champ est porté par
                // `statutEtudiant` (cf. PHPDoc sur l'entité) et exposé tel quel dans
                // ScolariteSection sous le libellé "Régime d'inscription".
                'statut' => $row->LIB_RGI,
                'niveau' => $row->NIVEAU,
                'discipline' => $row->LIB_DSI,
                'diplome' => $row->LIB_DIP,
                // OBC-1 — adresse postale Apogée : on prend l'adresse annuelle (de l'année
                // d'inscription) et on retombe sur l'adresse fixe par champ quand l'annuelle
                // est partielle (cf. critère JIRA Robin 29/06 : "adresse annuelle prioritaire ;
                // adresse fixe sert de repli"). Pour la ville d'acheminement étranger,
                // commune.lib_com (France) ou adresse.lib_ade (étranger).
                'adresseLigne1' => isset($row->ADR_LIB_AD1) ? trim($row->ADR_LIB_AD1) : null,
                'adresseLigne2' => isset($row->ADR_LIB_AD2) ? trim($row->ADR_LIB_AD2) : null,
                'adresseComplement' => isset($row->ADR_LIB_AD3) ? trim($row->ADR_LIB_AD3) : null,
                'adresseCodePostal' => isset($row->ADR_COD_BDI) ? trim($row->ADR_COD_BDI) : null,
                'adresseVille' => isset($row->ADR_LIB_VIL) ? trim($row->ADR_LIB_VIL) : null,
                'adresseCodePays' => isset($row->ADR_COD_PAY) ? trim($row->ADR_COD_PAY) : null,
                'adressePays' => isset($row->ADR_LIB_PAY) ? trim($row->ADR_LIB_PAY) : null,
            ];
        }

        if (isset($numTel) && null === $etudiant->getTelPerso()) {
            $etudiant->setTelPerso($numTel);
        }
        if (isset($dateNai)) {
            $etudiant->setDateNaissance(new DateTime($dateNai));
            /** @noinspection PhpUndefinedVariableInspection */
            $etudiant->setGenre($codSexEtu);
        }
        return $formations;
    }

    /**
     * @return resource
     */
    protected function connect()
    {
        $res = @oci_pconnect($this->username, $this->password, $this->db, 'AL32UTF8');

        if (!$res) {
            $this->logger->error('La base de données apogée est indisponible');
            throw new RuntimeException('base de données apogée indisponible');
        }

        return $res;
    }

    /**
     * @param DateTimeInterface $debut
     * @return int
     */
    protected function getAnneeApogee(DateTimeInterface $debut): int
    {
        return match ((int) $debut->format('m') >= 9) {
            true => (int) $debut->format('Y'),
            false => (int) $debut->format('Y') - 1,
        };
    }

    public function getFormation(Formation $incomplete): array
    {
        try {
            $db = $this->connect();
        } catch (RuntimeException) {
            $this->logger->warning('Récupération des infos formation impossible, apogée indisponible');
            throw new BackendUnavailableException();
        }

        $sql = $this->requeteFormation;

        $stmt = oci_parse($db, $sql);
        [$codEtp, $codVrsVet] = explode(separator: '#', string: $incomplete->getCodeExterne());
        oci_bind_by_name($stmt, 'codEtp', $codEtp);
        oci_bind_by_name($stmt, 'codVrsVet', $codVrsVet);

        if (!oci_execute($stmt)) {
            $this->logger->warning('Récupération des infos formation impossible, apogée indisponible');
            return [];
        }

        $data = [];
        if ($row = oci_fetch_object($stmt)) {
            $data = [
                'diplome' => $row->LIB_DIP,
                'niveau' => $row->NIVEAU,
                'discipline' => $row->LIB_DSI,
            ];
        }
        return $data;
    }
}
