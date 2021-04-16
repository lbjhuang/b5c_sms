<?php
/**
 * User: yangsu
 * Date: 2019/11/08
 * Time: 13:07
 */

class ToolAction extends BaseAction
{

    public function _initialize()
    {
        if ('b08a8be1abd25efd858141757dbfc5c5' != $_GET['api']) {
            parent::_initialize();
        }
    }

    /**
     *
     */
    public function updateTodayOrder()
    {
        $limit_number = I('limit_number');
        if (IS_POST) {
            if (empty($limit_number) || $limit_number < 1) {
                $limit_number = 100;
            }
            if ($limit_number > 4000) {
                $limit_number = 4000;
            }
            $Model = new Model();
            $where['UPDATE_TIME'] = ['GT', date('Y-m-d')];
            $orders = $Model->table('tb_op_order')
                ->where($where)
                ->limit($limit_number)
                ->order('UPDATE_TIME DESC')
                ->select();
            $res = (new OmsService())->updateOrderFromEs($orders, 'ORDER_ID', 'PLAT_CD', 'no_switch');
            $this->success($res['res']);
            die();
        }
        $this->display('update_today_order');
    }

    /**
     *
     */
    public function erpSwitchEs()
    {
        $switch_list = [
            'erp_switch_update_order_from_es' => [
                'name' => '异步更新 OMS ES',
                'value' => (boolean)RedisModel::get_key('erp_switch_update_order_from_es'),
            ],
            'erp_switch_make_up_ebay_every_four_hour' => [
                'name' => '每四小时补拉 ebay 任务',
                'value' => (boolean)RedisModel::get_key('erp_switch_make_up_ebay_every_four_hour'),
            ],
            'erp_switch_update_https_request' => [
                'name' => '强制跳转 https 登录',
                'value' => (boolean)RedisModel::get_key('erp_switch_update_https_request'),
            ],
        ];
        $this->assign('switch_list', $switch_list);
        $this->assignJson('switch_list_json', $switch_list);
        $this->display('erp_switch_es');
    }

    /**
     *
     */
    public function updateSwitchEs()
    {
        $key = I('erp_switch');
        $value = I('value');
        $res = RedisModel::set_key('erp_switch_' . $key, (int)$value);
        if ($res) {
            $this->ajaxSuccess($res);
        } else {
            $this->ajaxError($res);
        }
    }

    /**
     *
     */
    public function monitor()
    {
        $Db = new Model();
        $data['ms_ord_db'] = $Db->query("SELECT * FROM `tb_ms_ord` WHERE `reset_num` > '0' ORDER BY `updated_time` DESC LIMIT 1");
        $data['package_number'] = $Db->query("SELECT
                                   count(tb_op_order.order_id ) AS total
                                FROM
                                    (
                                        tb_op_order,
                                        tb_ms_logistics_mode
                                    )                           
                                WHERE
                                    tb_op_order.logistic_model_id = tb_ms_logistics_mode.ID
                                AND tb_op_order.plat_cd <> ''
                                AND tb_op_order.order_id <> ''
                                AND tb_ms_logistics_mode.SERVICE_CODE <> ''
                                AND tb_op_order.LOGISTICS_SINGLE_STATU_CD = 'N002080200'");
        $data['todo_package_number'] = $Db->query("SELECT
                                                count(a.order_id) AS total
                                            FROM
                                                tb_op_order a,
                                                tb_ms_logistics_mode b
                                            WHERE
                                                a.logistic_model_id = b.ID
                                            AND a.LOGISTICS_SINGLE_STATU_CD = 'N002080600'
                                            ");
        $data['package_temp'] = $Db->query("SELECT
                                        count(tb_op_order.order_id ) AS total
                                    FROM
                                        tb_op_order,
                                        tb_ms_logistics_mode
                                    WHERE
                                        tb_op_order.logistic_model_id = tb_ms_logistics_mode.ID
                                    AND tb_op_order.plat_cd <> ''
                                    AND tb_op_order.order_id <> ''
                                    AND tb_ms_logistics_mode.SERVICE_CODE <> ''
                                    AND tb_op_order.LOGISTICS_SINGLE_STATU_CD = 'N002080300'
                                    AND TIMESTAMPDIFF(
                                        SECOND,
                                        LOGISTICS_SINGLE_UP_TIME,
                                        now()
                                    ) > 10
                                    ");
        $data['b2b_error_send'] = $Db->query("SELECT
                                count(t1.PO_ID) AS total
                            FROM
                                (
                                    SELECT
                                        tb_b2b_doship.PO_ID,
                                        tb_b2b_doship.todo_sent_num,
                                        tb_b2b_doship.update_time,
                                        SUM(
                                            tb_b2b_goods.TOBE_DELIVERED_NUM
                                        ) AS sum
                                    FROM
                                        tb_b2b_doship,
                                        tb_b2b_goods
                                    WHERE
                                        tb_b2b_doship.ORDER_ID = tb_b2b_goods.ORDER_ID
                                    GROUP BY
                                        tb_b2b_goods.ORDER_ID
                                ) t1
                            WHERE
                                t1.todo_sent_num != sum");
        $this->assign('data', $data);
        $this->display();
    }

    /**
     *
     */
    public function showEbayMissOrder()
    {
        $Model = new Model();
        $sql = "
        SELECT
            t1.STORE_ID,
            t1.STORE_NAME AS 'STORE_NAME',
            MAX(t1.ORDER_NO) - MIN(t1.ORDER_NO) + 1 - COUNT(t1.ORDER_NO) AS 'may_miss_order_num',
            MAX(t1.ORDER_NO) AS 'max_order_no',
            MIN(t1.ORDER_NO) AS 'min_order_no',
            GROUP_CONCAT(t1.ORDER_NO) AS 'group_orders'
        FROM
            (
                SELECT
                    ORDER_NO,
                    ORDER_ID,
                    tb_op_order.PLAT_CD,
                    tb_ms_cmn_cd.CD_VAL,
                    STORE_ID,
                    tb_ms_store.STORE_NAME,
                    ORDER_CREATE_TIME,
                    ORDER_TIME,
                    ORDER_PAY_TIME
                FROM
                    tb_op_order,
                    tb_ms_cmn_cd,
                    tb_ms_store
                WHERE
                    ORDER_TIME > '2020-01-01 00:00:00'
                AND tb_op_order.PLAT_CD IN (
                    'N000831200',
                    'N000832800',
                    'N000835000',
                    'N000834900',
                    'N000834400',
                    'N000834700'
                )
                AND tb_ms_cmn_cd.CD = tb_op_order.PLAT_CD
                AND tb_op_order.STORE_ID = tb_ms_store.ID -- AND tb_op_order.STORE_ID = 252
                AND tb_op_order.ORDER_ID NOT LIKE '0000%'
                AND tb_op_order.ORDER_ID NOT LIKE 'BF%'
                AND tb_op_order.ORDER_NO NOT LIKE '%-%'
                GROUP BY
                    tb_op_order.ORDER_NO
                ORDER BY
                    tb_op_order.STORE_ID,
                    tb_op_order.ORDER_NO DESC
            ) AS t1
        GROUP BY
            t1.STORE_ID
        ";
        $res = $Model->query($sql);
        header('Content-Type: text/html; charset=utf-8');
        if (empty($res)) {
            echo '期间无漏单';
        } else {
            echo '<pre>';
            foreach ($res as &$re) {
                if ($re['may_miss_order_num'] > 0) {
                    $re['may_miss_orders'] = $this->getMissOrders($re);
                    unset($re['max_order_no'], $re['min_order_no'], $re['group_orders']);
                } else {
                    unset($re);
                }
            }
            print_r($res);
        }
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function getMissOrders($data)
    {
        for ($i = $data['min_order_no']; $i <= $data['max_order_no']; $i++) {
            $temp_should_orders[] = $i;
        }
        $actual_orders = explode(',', $data['group_orders']);
        return array_diff($temp_should_orders, $actual_orders);
    }

    public function biButtons()
    {
        $this->display('bi_buttons');
    }

    public function updateBIReport()
    {
        try {
            $redis_key = RedisModel::lock('button_update_bi_report', 600);
            if (!$redis_key) {
                throw new Exception('十分钟内不可重复点击');
            }
            $checkBIReportClickNum = $this->checkBIReportClick();
            if ($checkBIReportClickNum[0]['count'] >= 2) {
                throw new Exception('当前已有任务在执行，请稍后重试');
            }
            ApiModel::mergeRequest();
            $this->success('更新 PO 和发货时间中');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function updateSplitResultQuery()
    {
        try {
            $redis_key = RedisModel::lock('button_update_bi_report', 300);
            if (!$redis_key) {
                throw new Exception('五分钟内不可重复点击');
            }
            ApiModel::mergeSplitResultQuery();
            $this->success('更新“分成结果查询”导出数据中');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    public function mergeSplitResultQuery()
    {
        ini_set('max_execution_time', 180);
        Logs('act', __FUNCTION__, __CLASS__);
        $search_revenue_split = $this->searchRevenueSplit();
        if (0 === $search_revenue_split) {
            throw new Exception('按 fun_table_b2c_order_revenue_split 更新失败');
        }
        Logs('search_revenue_split0206', __FUNCTION__, __CLASS__);
        $search_revenue_split0206 = $this->searchRevenueSplit0206();
        if (0 === $search_revenue_split0206) {
            throw new Exception('按 fun_table_b2c_order_revenue_split0206 更新失败');
        }
        Logs('end', __FUNCTION__, __CLASS__);
        return false;
    }

    public function checkBIReportClick()
    {
        $Model = new Model(null, null, 'bi_db_config');
        $po_sql = "SELECT count(1)  FROM pg_stat_activity t where state ='active' and (t.query like '%dw_rep.fun_rep_revenue_kpi_net_profit_v1_init%'  or t.query like '%dw_rep.fun_alert_table_rename%')";
        return $Model->query($po_sql);
    }

    private function searchRevenueSplit()
    {
        $Model = new Model(null, null, 'bi_db_config');
        $po_sql = "select dw_rep.fun_table_b2c_order_revenue_split();";
        return $Model->query($po_sql);
    }

    private function searchRevenueSplit0206()
    {
        $Model = new Model(null, null, 'bi_db_config');
        $po_sql = "select dw_rep.fun_table_b2c_order_revenue_split0206();";
        return $Model->query($po_sql);
    }


    public function childBiSelectPO()
    {
        $Model = new Model(null, null, 'bi_db_config');
        $now_date = date('Y-m-d');
        $po_sql = "select * from dw_rep.fun_rep_revenue_kpi_net_profit_v1_init('2017-01-01','{$now_date}')";
        return $Model->query($po_sql);
    }

    public function childBiSelectSend()
    {
        $Model = new Model(null, null, 'bi_db_config');
        $now_date = date('Y-m-d');
        $sendout_sql = "select * from dw_rep.fun_rep_revenue_kpi_net_profit_v1_init0206('2017-01-01','{$now_date}')";
        return $Model->query($sendout_sql);
    }

    public function childBiSelectRename()
    {
        $Model = new Model(null, null, 'bi_db_config');
        $sendout_sql = "select * from dw_rep.fun_alert_table_rename('rep_revenue_kpi_net_profit_v1', 'rep_revenue_kpi_net_profit_v1_middle')";
        return $Model->query($sendout_sql);
    }

    public function mergeRequest()
    {
        ini_set('max_execution_time', 1200);
        Logs('act', __FUNCTION__, __CLASS__);
        if (0 === ApiModel::selectBiPO()) {
            throw new Exception('按 PO 更新失败');
        }
        Logs('selectBiSend', __FUNCTION__, __CLASS__);
        if (0 === ApiModel::selectBiSend()) {
            throw new Exception('按发货更新失败');
        }
        sleep(300);
        if ($this->childBiSelectRename()) {
            Logs('childBiSelectRename-end ', __FUNCTION__, __CLASS__);
            return true;
        }
        Logs('end', __FUNCTION__, __CLASS__);
        return false;
    }

}
