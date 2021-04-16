<?php
/**
 * User: yangsu
 * Date: 19/12/09
 * Time: 15:08
 */

class CrontabRunRepository extends Repository
{
    public function getFailOrderThirdDeliverStatus($ten_minutes_ago, $mothtime)
    {
        $Model = new Model();
        $sql = "SELECT
                    *
                FROM
                    (
                        SELECT
                            tb_op_order.ORDER_ID,
                            tb_op_order.PLAT_CD,
                            tb_op_order.SEND_ORD_STATUS,
                            tb_op_order.THIRD_DELIVER_STATUS,
                            MAX(
                                sms_ms_ord_hist.ORD_HIST_REG_DTTM
                            ) AS MAX_ORD_HIST_REG_DTTM
                        FROM
                            tb_op_order,
                            sms_ms_ord_hist
                        WHERE
                            tb_op_order.ORDER_TIME > '{$mothtime}'
                        AND tb_op_order.THIRD_DELIVER_STATUS = 2
                        AND tb_op_order.SEND_ORD_STATUS IN ('N001820100', 'N001821000')
                        AND tb_op_order.BWC_ORDER_STATUS NOT IN ('N000550900', 'N000551000')
                        AND tb_op_order.CHILD_ORDER_ID IS NULL
                        AND tb_op_order.ORDER_ID = sms_ms_ord_hist.ORD_NO
                        AND tb_op_order.PLAT_CD = sms_ms_ord_hist.plat_cd
                        AND sms_ms_ord_hist.ORD_HIST_HIST_CONT = '开始标记发货'
                        GROUP BY
                            ORDER_ID
                    ) AS t1
                WHERE
                    t1.MAX_ORD_HIST_REG_DTTM < '{$ten_minutes_ago}'";
        $failed_orders = $Model->query($sql);
        return $failed_orders;
    }


    public function updateFailOrderThirdDeliverStatus($ten_minutes_ago, $mothtime)
    {
        $Model = new Model();
        $sql = "UPDATE tb_op_order,
                     (
                        SELECT
                            tb_op_order.ORDER_ID,
                            tb_op_order.PLAT_CD,
                            tb_op_order.SEND_ORD_STATUS,
                            tb_op_order.THIRD_DELIVER_STATUS,
                            MAX(
                                sms_ms_ord_hist.ORD_HIST_REG_DTTM
                            ) AS MAX_ORD_HIST_REG_DTTM
                        FROM
                            tb_op_order,
                            sms_ms_ord_hist
                        WHERE
                            tb_op_order.ORDER_TIME > '{$mothtime}'
                        AND tb_op_order.THIRD_DELIVER_STATUS = 2
                        AND tb_op_order.SEND_ORD_STATUS IN ('N001820100', 'N001821000')
                        AND tb_op_order.BWC_ORDER_STATUS NOT IN ('N000550900', 'N000551000')
                        AND tb_op_order.CHILD_ORDER_ID IS NULL
                        AND tb_op_order.ORDER_ID = sms_ms_ord_hist.ORD_NO
                        AND tb_op_order.PLAT_CD = sms_ms_ord_hist.plat_cd
                        AND sms_ms_ord_hist.ORD_HIST_HIST_CONT = '开始标记发货'
                        GROUP BY
                            ORDER_ID
                    ) AS t1
                    SET tb_op_order.THIRD_DELIVER_STATUS = 3
                    WHERE
                        t1.MAX_ORD_HIST_REG_DTTM < '{$ten_minutes_ago}'
                    AND tb_op_order.THIRD_DELIVER_STATUS = 2
                    AND t1.ORDER_ID = tb_op_order.ORDER_ID
                    AND t1.PLAT_CD = tb_op_order.PLAT_CD";
        $update_failed_orders = $Model->query($sql);
        return $update_failed_orders;
    }
}