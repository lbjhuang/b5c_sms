<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/3/15
 * Time: 10:41
 */

class SmsMsOrdHistModel extends Model
{
    /**
     * 数据写入订单历史表
     * @param $data
     * @return bool
     */
    public static function writeHist($data)
    {
        $model = new Model();
        $model->table('sms_ms_ord_hist');
        if ($model->add($data))
            return true;
        else
            return false;
    }

    public static function writeMulHist($data)
    {
        $model = new Model();
        $model->table('sms_ms_ord_hist');
        if ($model->addAll($data))
            return true;
        else
            return false;
    }
}