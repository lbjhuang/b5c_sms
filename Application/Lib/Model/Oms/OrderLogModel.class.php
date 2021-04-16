<?php
/**
 * User: yangsu
 * Date: 18/7/30
 * Time: 14:18
 */


class OrderLogModel extends Model
{
    protected $autoCheckFields = false;

    private static function bwcStatusGet($order_no, $plat_cd)
    {
        $Model = M();
        $where['ORDER_ID'] = $order_no;
        $where['PLAT_CD'] = $plat_cd;
        $bwc_status = $Model->table('tb_op_order')->where($where)->getField('BWC_ORDER_STATUS');
        return $bwc_status;
    }

    private static function bwcStatusListGet($order_nos, $plat_cds)
    {
        $Model = M();
        $where['ORDER_ID'] = ['in', $order_nos];
        $where['PLAT_CD'] = ['in', $plat_cds];
        $bwc_status = $Model->table('tb_op_order')->field('ORDER_ID, PLAT_CD, BWC_ORDER_STATUS')->where($where)->select();
        return $bwc_status;
    }

    public function addLog($order_id, $plat_cd, $msg, $operation_info = null,$user_name = null)
    {
        $bwc_status = self::bwcStatusGet($order_id, $plat_cd);
        $log['ORD_NO'] = $order_id;
        $log['ORD_HIST_SEQ'] = time();
        $log['ORD_STAT_CD'] = $bwc_status;  //订单状态
        if (empty($user_name)) {
            $user_name = $_SESSION['m_loginname'];
        }
        $log['ORD_HIST_WRTR_EML'] = $user_name;
        $log['ORD_HIST_REG_DTTM'] = date("Y-m-d H:i:s", time());
        $log['ORD_HIST_HIST_CONT'] = $msg;
        $log['updated_time'] = date("Y-m-d H:i:s", time());
        $log['plat_cd'] = $plat_cd;
        if ($operation_info) {
            if (!is_string($operation_info) && !DataModel::isJson($operation_info)) {
                $operation_info = json_encode($operation_info, JSON_UNESCAPED_UNICODE);
            }
            $log['operation_info'] = $operation_info;
        }
        $add_res = M("ms_ord_hist", "sms_")->add($log);
    }

    public function addAllLog($order_arr, $msg = '')
    {
        $logs = [];
        $date = date("Y-m-d H:i:s", time());
        $time = time();
        $m_loginname = $_SESSION['m_loginname'] ? $_SESSION['m_loginname'] : 'ERP SYSTEM';
        foreach ($order_arr as $value) {
            $log = [];
            $log['ORD_NO'] = $value['ORDER_ID'];
            $log['ORD_HIST_SEQ'] = $date;
            $log['ORD_STAT_CD'] = $value['BWC_ORDER_STATUS'];  //订单状态
            $log['ORD_HIST_WRTR_EML'] = $m_loginname;
            $log['ORD_HIST_REG_DTTM'] = $date;
            $log['ORD_HIST_HIST_CONT'] = $value['msg'] ? $value['msg'] : $msg;
            $log['updated_time'] = $time;
            $log['plat_cd'] = $value['PLAT_CD'];
            if ($value) {
                $operation_info = $value;
                if (!is_string($operation_info) && !DataModel::isJson($operation_info)) {
                    $operation_info = json_encode($operation_info, JSON_UNESCAPED_UNICODE);
                }
                $log['operation_info'] = $operation_info;
            }
            $logs[] = $log;
        }
        $add_res = M("ms_ord_hist", "sms_")->addAll($logs);
    }

}