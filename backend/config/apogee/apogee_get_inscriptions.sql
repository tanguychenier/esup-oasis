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
           else trim(annuelle.num_tel)
           end                                                     as num_tel,
       iaa.cod_soc,
       soc.lib_soc,
       rgi.lib_rgi,
       lib_dip,
       niveau,
       dsi.lib_dsi,
       trim(annuelle.lib_ad1)                                      as adr_lib_ad1,
       trim(annuelle.lib_ad2)                                      as adr_lib_ad2,
       trim(annuelle.lib_ad3)                                      as adr_lib_ad3,
       trim(annuelle.cod_bdi)                                      as adr_cod_bdi,
       trim(nvl(com.lib_com, annuelle.lib_ade))                    as adr_lib_vil,
       trim(annuelle.cod_pay)                                      as adr_cod_pay,
       trim(pay.lib_pay)                                           as adr_lib_pay
from ins_adm_etp iae
         join diplome dip on dip.cod_dip = iae.cod_dip
         left outer join sec_dis_sis sds on sds.cod_sds = dip.cod_sds
         left outer join discipline_sis dsi on dsi.cod_dsi = sds.cod_dsi
         left outer join apogee.extern_niveau_etape niv on niv.cod_etp = iae.cod_etp
         join individu i on i.cod_ind = iae.cod_ind
         join ins_adm_anu iaa on iaa.cod_ind = i.cod_ind and iaa.cod_anu = iae.cod_anu and iaa.eta_iaa = 'E'
         join regime_ins rgi on rgi.cod_rgi = iaa.cod_rgi
         left outer join sit_sociale soc on soc.cod_soc = iaa.cod_soc
         join composante cmp on cmp.cod_cmp = iae.cod_cmp
         join version_etape vet on vet.cod_etp = iae.cod_etp and vet.cod_vrs_vet = iae.cod_vrs_vet
         left outer join adresse annuelle on annuelle.cod_ind_ina = i.cod_ind and annuelle.cod_anu_ina = iae.cod_anu
         left outer join commune com on com.cod_bdi = annuelle.cod_bdi and com.cod_com = annuelle.cod_com
         left outer join pays pay on pay.cod_pay = annuelle.cod_pay
where i.cod_etu = :codEtu
  and iae.cod_anu between :debut and :fin
  and iae.tem_iae_prm = 'O'
  and iae.eta_iae = 'E'
  and iae.eta_pmt_iae = 'P'
order by iae.cod_anu
