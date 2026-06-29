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
            $formations[] = [
                'codeFormation' => $row->COD_ETP . '#' . $row->COD_VRS_VET,
                'libFormation' => $row->LIB_WEB_VET,
                'codeComposante' => $row->COD_CMP,
                'libComposante' => $row->LIB_CMP,
                'debut' => new DateTime($row->COD_ANU . '-09-01'),
                'fin' => new DateTime(($row->COD_ANU + 1) . '-08-31'),
                'boursier' => $row->TEM_BRS_IAA == 'O',
                'statut' => $row->LIB_RGI, //changement de dernière minute... on colle le régime dans le champ "statut"
                'niveau' => $row->NIVEAU,
                'discipline' => $row->LIB_DSI,
                'diplome' => $row->LIB_DIP,
                // OBC-3 — code étape (cod_etp) pour exposer le cursus
                // d'inscription et en dériver le niveau LMD côté API.
                'codeEtape' => isset($row->COD_ETP) ? trim($row->COD_ETP) : null,
                // OBC-3 — flag de redoublement calculé directement dans la
                // requête SQL (REDOUBLANT = 1 quand nbr_ins_etp > 1 et
                // cod_sis_cur_amg est NULL). La règle métier vit dans le
                // .sql afin que chaque université puisse l'adapter sans
                // toucher au code applicatif.
                'redoublant' => isset($row->REDOUBLANT) ? ((int) $row->REDOUBLANT === 1) : null,
                // OBC-3 — cursus aménagé SISE (cod_sis_cur_amg / lib_cur_amg)
                // joint depuis la table cursus_amg : conservé pour
                // l'affichage du badge dédié côté frontend.
                'codeCursusAmenageSise' => isset($row->COD_SIS_CUR_AMG) ? trim($row->COD_SIS_CUR_AMG) : null,
                'libelleCursusAmenage' => isset($row->LIB_CUR_AMG) ? trim($row->LIB_CUR_AMG) : null,
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
