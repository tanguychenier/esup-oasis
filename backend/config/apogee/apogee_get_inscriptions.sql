select iae.cod_anu,
       vet.cod_etp,
       vet.cod_vrs_vet,
       vet.lib_web_vet,
       cmp.cod_cmp,
       cmp.lib_cmp,
       to_char(i.date_nai_ind, 'YYYY-MM-DD') as date_nai_ind,
       i.cod_sex_etu,
       case
           when annuelle.num_tel_port is not null then trim(annuelle.num_tel_port)
           when fixe.num_tel_port is not null then trim(fixe.num_tel_port)
           when annuelle.num_tel is not null then trim(annuelle.num_tel)
           else trim(fixe.num_tel)
           end                               as num_tel,
       iaa.tem_brs_iaa,
       rgi.lib_rgi,
       lib_dip,
       niveau,
       dsi.lib_dsi,
       -- OBC-3 — redoublant : compteur Apogée natif `nbr_ins_etp` (nombre total
       -- d'inscriptions à l'étape, cumulatif sur la carrière de l'étudiant). Le
       -- critère JIRA Robin 29/06/2026 mentionne "deux années consécutives" ;
       -- `nbr_ins_etp > 1` est la règle stricte Apogée native, qui ne distingue
       -- pas consécutif/non-consécutif. À reposer à Robin pour validation
       -- explicite (un étudiant L1 en 2022 puis L1 en 2025 serait flaggé ici,
       -- alors que la lecture stricte de "consécutif" l'exclurait).
       -- Le cursus aménagé SISE (cod_sis_cur_amg) neutralise le badge (cf.
       -- mail Robin 23/06/2026 : un cursus aménagé n'est pas un redoublement).
       case
           when iae.nbr_ins_etp > 1 and amg.cod_sis_cur_amg is null then 1
           else 0
           end                               as redoublant,
       amg.cod_sis_cur_amg,
       amg.lib_cur_amg
from ins_adm_etp iae
         join diplome dip on dip.cod_dip = iae.cod_dip
         left outer join sec_dis_sis sds on sds.cod_sds = dip.cod_sds
         left outer join discipline_sis dsi on dsi.cod_dsi = sds.cod_dsi
         left outer join apogee.extern_niveau_etape niv on niv.cod_etp = iae.cod_etp
         left outer join cursus_amg amg on amg.cod_cur_amg = iae.cod_cur_amg
         join individu i on i.cod_ind = iae.cod_ind
         join ins_adm_anu iaa on iaa.cod_ind = i.cod_ind and iaa.cod_anu = iae.cod_anu and iaa.eta_iaa = 'E'
         join regime_ins rgi on rgi.cod_rgi = iaa.cod_rgi
         join composante cmp on cmp.cod_cmp = iae.cod_cmp
         join version_etape vet on vet.cod_etp = iae.cod_etp and vet.cod_vrs_vet = iae.cod_vrs_vet
         left outer join adresse fixe on fixe.cod_ind = i.cod_ind
         left outer join adresse annuelle on annuelle.cod_ind_ina = i.cod_ind and annuelle.cod_anu_ina = iae.cod_anu
where i.cod_etu = :codEtu
  and iae.cod_anu between :debut and :fin
  and iae.tem_iae_prm = 'O'
  and iae.eta_iae = 'E'
order by iae.cod_anu