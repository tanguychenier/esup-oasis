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
       iaa.cod_soc,
       soc.lib_soc,
       rgi.lib_rgi,
       lib_dip,
       niveau,
       dsi.lib_dsi,
       -- adresse choisie en bloc (annuelle si elle existe, sinon fixe) : un coalesce
       -- colonne par colonne mélangerait les deux adresses quand l'annuelle est partielle
       case when annuelle.cod_ind_ina is not null then annuelle.lib_ad1 else fixe.lib_ad1 end as adr_lib_ad1,
       case when annuelle.cod_ind_ina is not null then annuelle.lib_ad2 else fixe.lib_ad2 end as adr_lib_ad2,
       case when annuelle.cod_ind_ina is not null then annuelle.lib_ad3 else fixe.lib_ad3 end as adr_lib_ad3,
       case when annuelle.cod_ind_ina is not null then annuelle.cod_bdi else fixe.cod_bdi end as adr_cod_bdi,
       case when annuelle.cod_ind_ina is not null then annuelle.lib_vil else fixe.lib_vil end as adr_lib_vil,
       case when annuelle.cod_ind_ina is not null then annuelle.cod_pay else fixe.cod_pay end as adr_cod_pay,
       iae.nbr_ins_etp,
       amg.cod_sis_cur_amg,
       amg.lib_cur_amg
from ins_adm_etp iae
         join diplome dip on dip.cod_dip = iae.cod_dip
         left outer join sec_dis_sis sds on sds.cod_sds = dip.cod_sds
         left outer join discipline_sis dsi on dsi.cod_dsi = sds.cod_dsi
         left outer join apogee.extern_niveau_etape niv on niv.cod_etp = iae.cod_etp
         join individu i on i.cod_ind = iae.cod_ind
         join ins_adm_anu iaa on iaa.cod_ind = i.cod_ind and iaa.cod_anu = iae.cod_anu and iaa.eta_iaa = 'E'
         join regime_ins rgi on rgi.cod_rgi = iaa.cod_rgi
         left outer join sit_sociale soc ON (soc.cod_soc = iaa.cod_soc)
         join composante cmp on cmp.cod_cmp = iae.cod_cmp
         join version_etape vet on vet.cod_etp = iae.cod_etp and vet.cod_vrs_vet = iae.cod_vrs_vet
         left outer join cursus_amg amg on (amg.cod_cur_amg = iae.cod_cur_amg)
         left outer join adresse fixe on fixe.cod_ind = i.cod_ind
         left outer join adresse annuelle on annuelle.cod_ind_ina = i.cod_ind and annuelle.cod_anu_ina = iae.cod_anu
where i.cod_etu = :codEtu
  and iae.cod_anu between :debut and :fin
  and iae.tem_iae_prm = 'O'
  and iae.eta_iae = 'E'
order by iae.cod_anu