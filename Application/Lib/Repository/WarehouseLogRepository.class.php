<?php


class WarehouseLogRepository extends Repository
{
    public function getOldInfo($table, $where)
    {
        return (new Model())->table($table)
            ->where($where)
            ->find();
    }

    public function addAll($log_data)
    {
        $res = M("warehouse_log",'tb_wms_')->addAll($log_data);
        return $res;
    }

    public function getCodeVal($where)
    {
        $res = M("cmn_cd",'tb_ms_')->field("CD,CD_VAL")->where($where)->select();
        return $res;
    }

    public function getInfo($where, $order)
    {
        return M('warehouse_log','tb_wms_')->where($where)->order($order)->select();
    }
}