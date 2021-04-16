<?php

/**
 * User: yuanshixiao
 * Date: 2017/5/17
 * Time: 13:11
 */
class TbMsOrdModel extends Model
{
    protected $trueTableName = 'tb_ms_ord';
    public function sendOut() {

    }
    
    /**
     * 查询订单
     * @param type $where 查询条件
     * @return type
     */
    public static function getOutOrders($where) {
        return M()->table('tb_ms_ord')->field(['ORD_ID'])->where($where)->select();
    }
}