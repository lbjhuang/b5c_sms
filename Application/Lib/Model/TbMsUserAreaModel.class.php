<?php

/**
 * User: yangsu
 * Date: 17/12/18
 * Time: 19:31
 */
class TbMsUserAreaModel extends Model
{
    /**
     * @param $res_arr
     *
     * @return mixed
     */
    public static function getAllId($res_arr)
    {
        $M = M();
        $where['zh_name'] = array('in', $res_arr);
        $res = $M->table('tb_ms_user_area')->field('en_name,three_char,zh_name,id')->where($where)->select();
        return $res;
    }

    public static function getCountryId($res_arr)
    {
        $M = M();
        $where['zh_name'] = array('in', $res_arr);
        $where['area_type'] = 1;
        $res = $M->table('tb_ms_user_area')->field('en_name,three_char,zh_name,id')->where($where)->select();
        return $res;
    }

    public static function checkCountry($country)
    {
        return RedisModel::get_key('PROCESS_TB_MS_USER_AREA' . $country);
    }

}