<?php
/**
 * User: yangsu
 * Date: 18/11/23
 * Time: 15:08
 */

class LogRepository extends Repository
{
    public function getOldInfo($table, $where)
    {
        return (new Model())->table($table)
            ->where($where)
            ->find();
    }

    public function getAllLogisticsKeyVal()
    {
        $res_db = (new Model())->table('tb_ms_logistics_mode')
            ->field('ID,LOGISTICS_MODE')
            ->select();
        $res = array_column($res_db, 'LOGISTICS_MODE', 'ID');
        return $res;
    }

    public function getAllCountryKeyVal()
    {
        $res_db = (new Model())->table('tb_ms_user_area')
            ->field('id,zh_name')
            ->select();
        $res = array_column($res_db, 'zh_name', 'id');
        return $res;
    }
}