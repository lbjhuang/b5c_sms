<?php
/**
 * User: xuejun.zou
 * Date: 19/12/12
 * Time: 9:50
 */

class StoreLogRepository extends Repository
{
    public function getOldInfo($table, $where)
    {
        return (new Model())->table($table)
            ->where($where)
            ->find();
    }

    public function addAll($log_data)
    {
        $res = M("store_log",'tb_ms_')->addAll($log_data);
        return $res;
    }

    public function getCodeVal($where){
        $res = M("cmn_cd",'tb_ms_')->field("CD,CD_VAL")->where($where)->select();
        return $res;
    }
}