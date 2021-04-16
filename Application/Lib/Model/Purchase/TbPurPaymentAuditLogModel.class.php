<?php

/**
 * Created by fuming.
 * User: Administrator
 * Date: 2019/8/26
 * Time: 10:17
 */
class TbPurPaymentAuditLogModel extends Model
{
    protected $trueTableName = 'tb_pur_payment_audit_log';

    public static function recordLog($payment_audit_id, $operation_info, $date, $status_name, $remark)
    {
        if (empty($payment_audit_id)) return;
        $payment_audit_id = (array)$payment_audit_id;
        $data = array_map(function($id) use ($operation_info, $date, $status_name, $remark) {
            return $temp[] = [
                'payment_audit_id' => $id,
                'operation_info' => $operation_info,
                'status_name'    => $status_name,
                'remark'         => $remark,
                'created_by' => session('m_loginname'),
                'created_at' => empty($date) ? date('Y-m-d H:i:s') : $date,
            ];
        }, $payment_audit_id);
        (new self())->addAll($data);
    }

    public static function deleteLog($payment_audit_id)
    {
        if (empty($payment_audit_id)) return;
        (new self())->where(['payment_audit_id' => $payment_audit_id])->delete();
    }
}