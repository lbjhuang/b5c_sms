<?php

class PendingModel
{
    static function filterListsData($params) {
        $params->data->must_not[] = 'b5cOrderNo';
        $params->data->must_not[] = 'childOrderId';
        $params->data->query_string = 'bwcOrderStatus:(N000550600 OR N000550400 OR N000550500  OR N000550800) AND sendOrdStatus:N001821101 AND NOT platCd:(N000831300 OR N000830100)';
        $value = $params->data->logisticsSingleStatuCd;
        if ($value && $value != '[]' && in_array('N002080200', $value)) {
            $params->data->logisticsSingleStatuCd[] = 'N002080600';
        }
        return $params;
    }

    /**
     * @param $params
     * @return bool
     * @throws Exception
     * 待开单退回
     */
    static function returnToPatch($params) {
        M()->startTrans();
        $order_log_m = new OrderLogModel();
        foreach ($params as $v) {
            $send_order_status = M('order','tb_op_')
                ->lock(true)
                ->where(['ORDER_ID'=>$v['thr_order_id'], 'PLAT_CD'=>$v['plat_cd']])
                ->getField('SEND_ORD_STATUS');
            if($send_order_status != 'N001821101') {
                M()->rollback();
                Throw new Exception($v['thr_order_id'].'订单状态异常');
            }
            $save = [
                'SEND_ORD_STATUS'               => 'N001820100',
                'LOGISTICS_SINGLE_STATU_CD'     => 'N002080100',
                'LOGISTICS_SINGLE_ERROR_MSG'    => null,
                'LOGISTICS_SINGLE_UP_TIME'      => date('Y-m-d H:i:s'),
            ];
            $res = M('order','tb_op_')
                ->where(['ORDER_ID'=>$v['thr_order_id'], 'PLAT_CD'=>$v['plat_cd']])
                ->save($save);
            if(!$res) {
                M()->rollback();
                Throw new Exception($v['thr_order_id'].'退回失败');
            }
            $where_pack['ORD_ID']   = $v['thr_order_id'];
            $where_pack['plat_cd']  = $v['plat_cd'];
            $del_res                = M('ord_package','tb_ms_')->where($where_pack)->delete();
            if($del_res === false) {
                M()->rollback();
                Throw new Exception($v['thr_order_id'].'退回失败，删除运单失败');
            }
            $order_log_m->addLog($v['thr_order_id'], $v['plat_cd'], '待开单退回到待派单', $save);
        }
        M()->commit();
        return true;
    }
}