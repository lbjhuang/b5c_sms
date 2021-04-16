<?php
/**
 * User: yangsu
 * Date: 18/5/9
 * Time: 17:16
 */

class PatchInfoModel extends \Model
{
    /**
     * @param null $store_arr
     *
     * @return array
     */
    public static function patchRecommend($store_arr = null)
    {
        $Model = M();
        if (empty($store_arr)) {
            $where['ID'] = array('EXP', 'IS NOT NULL');
            $where_info['store_id'] = array('EXP', 'IS NOT NULL');
        } else {
            $where['ID'] = array('IN', $store_arr);
            $where_info['store_id'] = array('IN', $store_arr);
        }
        $field = 'ID,WAREHOUSES AS NOT_WAREHOUSE,LGT_MODES AS NOT_LGT_MODES';
        $store_db_arr = $Model->table('tb_ms_store')
            ->field($field)
            ->where($where)
            ->select();
        $tb_ms_logistics_mode_info_arr = $Model->table('tb_ms_logistics_mode_info')
            ->field('store_id,logistics_mode_id')
            ->where($where_info)
            ->select();;
        $all_warehouse_db_arr = CodeModel::getCodeArr(['N00068']);
        $all_lgt_db_arr = $Model->table('tb_ms_logistics_mode')
            ->field('ID,LOGISTICS_CODE,CD_VAL AS LOGISTICS_CODE_VAL,LOGISTICS_MODE,WARE_HOUSE,SERVICE_CODE')
            ->join('LEFT JOIN tb_ms_cmn_cd ON tb_ms_cmn_cd.CD = tb_ms_logistics_mode.LOGISTICS_CODE')
            ->where('IS_ENABLE = 1 AND IS_DELETE = 0')
            ->select();
        $lgt_ware_arr = array_column($all_lgt_db_arr, 'WARE_HOUSE', 'ID');
        $cd_val_arr['SERVICE_CODE'] = array_column($all_lgt_db_arr, 'SERVICE_CODE', 'ID');
        $cd_val_arr['LOGISTICS_MODE'] = array_column($all_lgt_db_arr, 'LOGISTICS_MODE', 'ID');
        $cd_val_arr['LOGISTICS_CODE'] = array_column($all_lgt_db_arr, 'LOGISTICS_CODE', 'ID');
        $cd_val_arr['LOGISTICS_CODE_VAL'] = array_column($all_lgt_db_arr, 'LOGISTICS_CODE_VAL', 'ID');
        $cd_val_arr['warehouse'] = array_column($all_warehouse_db_arr, 'CD_VAL', 'CD');
        foreach ($store_db_arr as $value) {
            list($lgt_modes_arr, $warehouse_arr) = self::joinLgtInfo($value, $all_lgt_db_arr, $all_warehouse_db_arr, $tb_ms_logistics_mode_info_arr);
            foreach ($lgt_modes_arr as $lgt_val) {
                $tmp_lgt_ware = explode(',', $lgt_ware_arr[$lgt_val]);
                $array_intersect = array_intersect($warehouse_arr, $tmp_lgt_ware);
                if ($array_intersect) $tmp_warehouse_arr[] = $array_intersect;
                unset($array_intersect);
                unset($tmp_lgt_ware);
            }
            $intersect_warehouse_arr = [];
            array_map(function ($tmp_value) use (&$intersect_warehouse_arr) {
                $intersect_warehouse_arr = array_merge($intersect_warehouse_arr, $tmp_value);
            }, $tmp_warehouse_arr);
            unset($tmp_warehouse_arr);
            $intersect_warehouse_arr = array_unique($intersect_warehouse_arr);
            $res_store_arr[$value['ID']] = self::joinWarehouse($intersect_warehouse_arr, $lgt_modes_arr, $cd_val_arr);
            unset($intersect_warehouse_arr);
            unset($lgt_modes_arr);
            unset($res_data_arr);
        }
        return $res_store_arr;
    }

    /**
     * @param $intersect_warehouse_arr
     * @param $lgt_modes_arr
     * @param $cd_val
     *
     * @return mixed
     */
    private static function joinWarehouse($intersect_warehouse_arr, $lgt_modes_arr, $cd_val_arr)
    {
        foreach ($intersect_warehouse_arr as $warehouse_v) {
            foreach ($lgt_modes_arr as $lgt_value) {
                $tmp['logisticsMode'] = $cd_val_arr['LOGISTICS_MODE'][$lgt_value];
                $tmp['fright'] = 0;
                $tmp['isShow'] = 0;
                $tmp['id'] = $lgt_value;
                $logisticsMethod[$cd_val_arr['LOGISTICS_CODE'][$lgt_value]]['logisticsMethod'][] = $tmp;
                $logisticsMethod[$cd_val_arr['LOGISTICS_CODE'][$lgt_value]]['logisticsCode'] = $cd_val_arr['LOGISTICS_CODE'][$lgt_value];
                $logisticsMethod[$cd_val_arr['LOGISTICS_CODE'][$lgt_value]]['logisticsName'] = $cd_val_arr['LOGISTICS_CODE_VAL'][$lgt_value];
                $logisticsMethod[$cd_val_arr['LOGISTICS_CODE'][$lgt_value]]['serviceCode'] = $cd_val_arr['SERVICE_CODE'][$lgt_value];
                $logisticsMethod[$cd_val_arr['LOGISTICS_CODE'][$lgt_value]]['isShow'] = 0;
                unset($tmp);
            }
            $warehouse_temp['lgtModel'] = array_values($logisticsMethod);
            $warehouse_temp['cd'] = $warehouse_v;
            $warehouse_temp['name'] = $cd_val_arr['warehouse'][$warehouse_v];
            $warehouse_temp['isShow'] = 0;
            $res_data_arr['warehouse'][] = $warehouse_temp;
            unset($lgtModel);
            unset($warehouse_temp);
            unset($logisticsMethod);
        }
        return $res_data_arr;
    }

    /**
     * @param $value
     * @param $all_lgt_db_arr
     * @param $all_warehouse_db_arr
     *
     * @return array
     */
    private static function joinLgtInfo($value, $all_lgt_db_arr, $all_warehouse_db_arr, $tb_ms_logistics_mode_info_arr)
    {
        $lgt_modes_arr = self::joinLgtModes($value, $all_lgt_db_arr, $tb_ms_logistics_mode_info_arr);

        $NOT_WAREHOUSE = $value['NOT_WAREHOUSE'];
        $warehouse_column = array_column($all_warehouse_db_arr, 'CD');
        if (empty($NOT_WAREHOUSE) || $NOT_WAREHOUSE == null) {
            $warehouse_arr = $warehouse_column;
        } else {
            $not_warehouse_exp = explode(',', $NOT_WAREHOUSE);
            if (!is_array($not_warehouse_exp)) {
                $not_warehouse_exp[] = $not_warehouse_exp;
            }
            $warehouse_arr = array_diff($warehouse_column, $not_warehouse_exp);
        }
        return array($lgt_modes_arr, $warehouse_arr);
    }

    /**
     * @param $value
     * @param $all_lgt_db_arr
     *
     * @return array
     */
    private static function joinLgtModes($value, $all_lgt_db_arr, $tb_ms_logistics_mode_info_arr)
    {
        $NOT_LGT_MODES = $value['NOT_LGT_MODES'];
        $lgt_column = array_column($all_lgt_db_arr, 'ID');
        if (empty($NOT_LGT_MODES) || $NOT_LGT_MODES == null) {
            $lgt_modes_arr = $lgt_column;
        } else {
            $not_lgt_modes_exp = explode(',', $NOT_LGT_MODES);
            if (!is_array($not_lgt_modes_exp)) {
                $not_lgt_modes_exp[] = $not_lgt_modes_exp;
            }
            $lgt_modes_arr = array_diff($lgt_column, $not_lgt_modes_exp);
        }
        $info_arr = array_column($tb_ms_logistics_mode_info_arr, 'logistics_mode_id');
        if (!empty($info_arr)) {
            $lgt_modes_arr = array_merge($lgt_modes_arr, $info_arr);
        }
        $lgt_modes_arr = array_unique($lgt_modes_arr);
        return $lgt_modes_arr;
    }
}