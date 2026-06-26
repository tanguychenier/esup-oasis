<?php

/*
 * Copyright (c) 2024-2026. Esup - Université de Bordeaux.
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 *  For full copyright and license information please view the LICENSE file distributed with the source code.
 */

namespace App\Service\SiScol;

use App\Entity\Inscription;

/**
 * OBC-3 — règle officielle de redoublement validée par la DSI (mail
 * Fatiha, juin 2026).
 *
 * Une première version naïve « même `cod_etp` sur deux années
 * universitaires consécutives = redoublant » avait été rejetée par le
 * métier le 28/05/2026 : elle confondait redoublement et contrat
 * pédagogique pluri-annuel négocié via un cursus aménagé. La DSI a
 * désigné les vrais champs Apogée à utiliser :
 *
 *   - `nbr_ins_etp` : compteur d'inscriptions à l'étape. Strictement
 *     supérieur à 1 ⇒ l'étudiant est inscrit à l'étape pour au moins
 *     la deuxième fois.
 *   - `cod_sis_cur_amg` (et libellé associé) : code SISE national du
 *     cursus aménagé. Renseigné ⇒ l'étudiant est dans un parcours
 *     pluri-annuel négocié, ce n'est pas un redoublement.
 *
 * La règle est donc : `nbrInsEtp > 1` ET pas de cursus aménagé.
 *
 * Service isolé, sans dépendance, pour pouvoir être testé unitairement
 * et réutilisé depuis le ResourceProvider (ApiResource) sans passer par
 * le conteneur de services.
 */
class RedoublementCalculator
{
    public function isRedoublant(Inscription $inscription): bool
    {
        $compteur = $inscription->getNbrInsEtp();
        if ($compteur === null || $compteur <= 1) {
            return false;
        }

        $cursusSise = $inscription->getCodeSisCurAmg();
        if ($cursusSise !== null && trim($cursusSise) !== '') {
            return false;
        }

        return true;
    }
}
