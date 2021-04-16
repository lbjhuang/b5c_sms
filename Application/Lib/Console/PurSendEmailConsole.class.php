<?php

/**
 * 调拨异步发送邮件
 * User: b5m
 * Date: 2018/1/22
 * Time: 14:54
 */
class PurSendEmailConsole extends ConsoleAction
{
    public function run()
    {
        $data = RedisModel::client()->get('pur_batch_send_email');
        $data = json_decode($data, true);
        if (empty($data)) {
            return true;
        }
        foreach ($data as $value) {
            $res[] = (new PurPaymentService())->sendPaidEmail($value, A('OrderDetail')->paid_email_content());
        }
        Logs($res, __FUNCTION__, 'fm');
    }
}