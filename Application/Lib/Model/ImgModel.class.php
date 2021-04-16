<?php

/**
 * User: yangsu
 * Date: 18/1/23
 * Time: 20:07
 */
class ImgModel extends Model
{
    /**
     * @param      $sku_arr
     * @param null $lang
     * @param bool $is_spu
     * @return mixed
     */
    public static function skuLangImg($sku_arr, $lang = null, $is_spu = false)
    {
        $M = M();
        $spu_arr = (!$is_spu) ? array_values(GoodsModel::sku2Spu($sku_arr)) : $sku_arr;
        $where_img['MAIN_GUDS_ID'] = array('in', $spu_arr);
        $where_img['GUDS_IMG_CD'] = 'N000080200';
        if (!$lang) {
            $lang = cookie('think_language') ? LanguageModel::langToCode(cookie('think_language')) : 'N000920100';
        }
        $where_img['LANGUAGE'] = array('eq', $lang);
        $select = $M->table('tb_ms_guds_img')->field('MAIN_GUDS_ID,GUDS_IMG_CDN_ADDR')->where($where_img)->group('GUDS_ID')->select();
        if (!$select) {
            $where_img['LANGUAGE'] = array('eq', 'N000920200');
            $select = $M->table('tb_ms_guds_img')->field('MAIN_GUDS_ID,GUDS_IMG_CDN_ADDR')->where($where_img)->group('GUDS_ID')->select();
        }
        return $select;
    }

}