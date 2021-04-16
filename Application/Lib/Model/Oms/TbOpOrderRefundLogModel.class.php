<?php

/**
 * Created by fuming.
 * User: Administrator
 * Date: 2019/12/31
 * Time: 10:17
 */
class TbOpOrderRefundLogModel extends Model
{
    protected $trueTableName = 'tb_op_order_refund_log';

    public static function recordLog($payment_id, $operation_info, $status_name, $remark)
    {
        if (empty($payment_id)) return;
        $payment_id = (array)$payment_id;
        $data = array_map(function($id) use ($operation_info, $status_name, $remark) {
            return $temp[] = [
                'refund_id'     => $id,
                'operation_info' => $operation_info,
                'status_name'    => $status_name,
                'remark'         => $remark,
                'created_by'     => session('m_loginname'),
                'created_at'     => date('Y-m-d H:i:s'),
            ];
        }, $payment_id);
        (new self())->addAll($data);
    }
}