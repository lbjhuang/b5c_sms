<?php
/**
 * User: zouxuejun
 * Date: 20/3/25
 * Time: 16:52
 */

/**
 *  gshopper_data_stage
 * Class DataService
 *  gshopper_data_stage 基础服务类
 */
class DataService extends Service
{
    //触发类型
    public $trigger_type = [
        '0' => ['name' => '获取单号', 'column' => 'LOGISTICS_SINGLE_STATU_CD'],
        '1' => ['name' => '标记发货', 'column' => 'third_deliver_status'],
    ];

    //触发类型对应statusCODE
    public $trigger_status_cd = [
        //获取单号
        '0' => [
            'N002080400' => ['cd_val' => '获取成功已有运单号', 'describe' => '获取成功，可进行后续操作'],
            'N002080500' => ['cd_val' => '获取失败', 'describe' => '通知实施人员排查'],
            'N002080600' => ['cd_val' => '获取中请稍后', 'describe' => '请稍后'],
        ],
        //标记发货
        '1' => [
            '1' => ['cd_val' => '已标发货', 'describe' => '标记成功，可以进行派单操作'],
            '2' => ['cd_val' => '等待平台反馈', 'describe' => '需要继续等待'],
            '3' => ['cd_val' => '等待平台反馈（两分钟未收到反馈，可以和从新标记）', 'describe' => '可以重新标记'],
            '4' => ['cd_val' => '标记发货失败', 'describe' => '通知实施人员排查'],
        ],
    ];

    public function addOne($sql,$type,$excel_name,$query_count,$export_template_id = 0){
        $data = array(
            'file_name' => $excel_name,
            'query' => $sql,
            'query_count' => $query_count,
            'type' => $type,
            'status' => 0,
            'export_template_id' => $export_template_id,
            'created_by' => DataModel::userNamePinyin(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_by' => DataModel::userNamePinyin(),
            'updated_at' => date('Y-m-d H:i:s'),
        );
        $dataRepository = new DataRepository();
        $ret = $dataRepository->addOne($data);
        return $ret;
    }

    public function getList($params,$pages)
    {
        $dataRepository = new DataRepository();
        $where = $this->feeWhere($params);
        $where = '1=1';
        $ret = $dataRepository->getList($where,$pages);
        return $ret;

    }

    /**
     * 列表查询 where 组装
     * @param $params
     */
    public function feeWhere($params)
    {
        $where = array();
        //  Payment Key
        if (isset($params['payment_no']) && !empty($params['payment_no'])) {
            $where['payment_no'] = $params['payment_no'];
        }
        // 调拨单号
        if (isset($params['allo_no']) && !empty($params['allo_no'])) {
            $where['allo_no'] = $params['allo_no'];
        }
        // 关联付款单号
        if (isset($params['payment_audit_no']) && !empty($params['payment_audit_no'])) {
            $where['payment_audit_no'] = $params['payment_audit_no'];
        }
        // 销售团队
        if (isset($params['allo_in_team']) && !empty($params['allo_in_team'])) {
            $data = explode(',', $params['allo_in_team']);
            $where['allo_in_team'] = array('in', $data);
        }
        // 我方公司
        if (isset($params['our_company_cd']) && !empty($params['our_company_cd'])) {
            $data = explode(',', $params['our_company_cd']);
            $where['our_company_cd'] = array('in', $data);
        }
        // 供应商
        if (isset($params['supplier_id']) && !empty($params['supplier_id'])) {
            $data = explode(',', $params['supplier_id']);
            $where['supplier_id'] = array('in', $data);
        }
        // 费用细分
        if (isset($params['cost_sub_cd']) && !empty($params['cost_sub_cd'])) {
            $data = explode(',', $params['cost_sub_cd']);
            $where['cost_sub_cd'] = array('in', $data);
        }
        // 费用负责人
        if (isset($params['ETC2']) && !empty($params['ETC2'])) {
            $where['ETC2'] = ['like', '%' . $params['ETC2'] . '%'];
        }
        // 状态
        if (isset($params['fee_status'])) {
            if ($params['fee_status'] != "" ){
                $where['tb_wms_payment.status'] = $params['fee_status'];
            }
        }
        return $where;
    }

    //新增邮件提醒任务
    public function addRemindTask($bill_inc_id,$trigger_type,$trigger_name)
    {
        $data = array(
            'bill_inc_id' => $bill_inc_id,
            'trigger_type' => $trigger_type,
            'trigger_name' => $trigger_name,
            'trigger_user_id' => DataModel::userId(),
            'created_by' => DataModel::userNamePinyin(),
            'created_at' => date('Y-m-d H:i:s'),
        );
        $dataRepository = new DataRepository();
        $ret = $dataRepository->addRemindTask($data);
        return $ret;
    }
    private function getOrderData($data)
    {
        $where_str = ' ( 1 != 1 ';
        foreach ($data as $value) {
            $where_str .= sprintf(" OR (ORDER_ID = '%s' AND PLAT_CD = '%s')", $value['order_id'], $value['plat_cd']);
        }
        $where_str .= ' ) ';
        $Model = M();
        //10068 GP标记发货的优化补充改动
        $order_data= $Model->table('tb_op_order')->field('ID,ORDER_ID,PLAT_CD')->where($where_str, null, true)->select();
        return $order_data;
    }

    //批量新增邮件提醒任务
    public function addRemindTaskAll($request_data, $type = 0)
    {
        $order_data = $this->getOrderData($request_data);
        foreach ($order_data as $item) {
            $tem = array(
                'bill_inc_id'  => $item['ID'],
                'trigger_type' => $type,
                'trigger_name' => $this->trigger_type[$type]['name'],
                'trigger_user_id' => DataModel::userId(),
                'created_by'   => DataModel::userNamePinyin(),
                'created_at'   => date('Y-m-d H:i:s'),
            );
            $data[] = $tem;
        }
        $dataRepository = new DataRepository();
        $ret = $dataRepository->addRemindTaskAll($data);
        return $ret;
    }

    //删除邮件提醒任务
    public function delRemindTask($bill_inc_ids, $trigger_type)
    {
        //删除当前操作的成功的订单
        $where['bill_inc_id'] = ['in', $bill_inc_ids];
        $where['trigger_type'] = $trigger_type;
        $data = array(
            'deleted_by' => 'ERP SYSTEM',
            'deleted_at' => date('Y-m-d H:i:s'),
        );
        $dataRepository = new DataRepository();
        $ret = $dataRepository->editRemindTask($where, $data);
        return $ret;
    }

    public function getRemindTaskList($trigger_type)
    {
        $dataRepository = new DataRepository();
        $where['trigger_type'] = $trigger_type;
        $ret = $dataRepository->getRemindTaskList($where);
        return $ret;
    }

    //获取触发操作邮件数据
    public function sendRemindTaskEmail($trigger_type)
    {
        $list = $this->getRemindTaskEmailData($trigger_type);
        $ret = $this->sendEmail($list, $trigger_type);
        return $ret;
    }

    //获取触发操作邮件数据
    public function sendEmail($list, $trigger_type)
    {
        if (empty($list)) return [];
        //发送邮件
        $remind_name = $this->trigger_type[$trigger_type]['name'];
        $title_after = '_' . $remind_name . '操作提醒' . date('Y-m-d H:i');
        $email = new SMSEmail();
        $ids = $data = $save = [];
        foreach ($list as $key => $val) {
            $to = $key . '@gshopper.com';
            $title = $key . $title_after;
            $content = '<a href="' . ERP_URL . '/index.php?m=index&a=index&source=email&actionType=dispatch" style="text-decoration: underline;cursor: pointer;color: #0088CC;margin: 20px 0;display: block;">详情请到erp待派单页面</a><table style="word-break:break-all;" border="1px" cellspacing="0" cellpadding="0"><thead><th style="min-width:140px;">动作</th><th style="min-width:140px;">动作状态</th><th style="max-width:560px;">订单号</th><th style="min-width:140px;">说明</th><th style="min-width:140px;">操作人</th></thead>';
            $bill_inc_ids = [];
            foreach ($val as $item) {
                //标记发货 成功
                if ($trigger_type == 1 && $item['third_deliver_status'] == 1) {
                    $bill_inc_ids = $item['bill_inc_ids'];
                }
                //获取单号 成功
                if ($trigger_type == 0 && $item['LOGISTICS_SINGLE_STATU_CD'] == 'N002080400') {
                    $bill_inc_ids = $item['bill_inc_ids'];
                }
                $order_ids = implode(',', $item['order_ids']);
                $content .= "<tr><td style='text-align:center;'>{$item['trigger_name']}</td><td style='text-align:center;max-width:140px;'>{$item['cd_val']}</td><td style='max-width:560px;'>{$order_ids}</td><td style='text-align:center;'>{$item['describe']}</td><td style='text-align:center;'>{$item['created_by']}</td></tr>";
            }
            $content .= '</table>';
            $t['to'] = $to;
            $t['title'] = $title;
            $t['content'] = $content;
            $t['res'] = $res = $email->sendEmail($to, $title, $content);
            if ($res) {
                $ids = array_merge($ids, $bill_inc_ids);
            }
            $data[] = $t;
        }
        //反馈成功的订单不在邮件通知
        if (!empty($ids)) {
            $res = $this->delRemindTask($ids, $trigger_type);
        }
        Logs(['trigger_type' => $trigger_type, 'data' => json_encode($list), 'ret' => json_encode($data), 'ids' => json_encode($ids), 'res' => $res], __FUNCTION__.'-触发操作邮件发送并删除反馈成功的触发记录', __CLASS__);
        return $data;
    }

    //获取触发操作邮件数据
    public function getRemindTaskEmailData($trigger_type)
    {
        $order_data = $this->getRemindTaskData($trigger_type);
        $list = $this->formatRemindTaskList($order_data, $trigger_type);
        return $list;
    }

    public function getRemindTaskData($trigger_type, $limit = 1000)
    {
        //读取从库
        $Model = new SlaveModel();
        $where['trigger_type'] = $trigger_type;
        $where['deleted_by'] = ['EXP','IS NULL']; //没有删除的操作记录
        $where['created_at'] = ['gt',date('Y-m-d 00:00:00')]; //只发送20200819 0:0:0之后的
        $data_db = C('DATA_DB.DB_NAME'); //获取邮件通知表名
        if ($trigger_type == 1) {
            //标记发货
            $list= $Model->table('tb_op_order')
                ->field('tb_op_order.ORDER_ID,tb_op_order.third_deliver_status,tb_op_order.LOGISTICS_SINGLE_STATU_CD,
            b5c_remind_task.created_by,b5c_remind_task.bill_inc_id,trigger_name')
                ->join('left join ' . $data_db . '.b5c_remind_task on tb_op_order.ID = b5c_remind_task.bill_inc_id')
                ->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID')
                ->where($where)
                //只获取待派单的订单数据
                ->where(' (tb_ms_store.SEND_ORD_TYPE = 0 OR (tb_ms_store.SEND_ORD_TYPE = 1 AND tb_op_order.send_ord_status != \'N001820100\' )) AND (  (tb_op_order.B5C_ORDER_NO IS NULL)  ) AND ( tb_op_order.BWC_ORDER_STATUS IN (\'N000550600\',\'N000550400\',\'N000550500\',\'N000550800\') ) AND ( tb_op_order.SEND_ORD_STATUS IN (\'N001820100\',\'N001821000\',\'N001820300\') ) AND  (tb_op_order.CHILD_ORDER_ID IS NULL)  AND ( tb_op_order.PLAT_CD NOT IN (\'N000831300\',\'N000830100\') )  ', null, true)
                ->limit($limit)->select();
        } else {
            //获取单号
            $list= $Model->table('tb_op_order')
                ->field('tb_op_order.ORDER_ID,tb_op_order.third_deliver_status,tb_op_order.LOGISTICS_SINGLE_STATU_CD,
            tb_ms_ord_package.TRACKING_NUMBER,b5c_remind_task.created_by,b5c_remind_task.bill_inc_id,trigger_name')
                ->join('left join tb_ms_ord_package on (tb_op_order.ORDER_ID = tb_ms_ord_package.ORD_ID AND tb_op_order.PLAT_CD = tb_ms_ord_package.plat_cd)')
                ->join('left join ' . $data_db . '.b5c_remind_task on tb_op_order.ID = b5c_remind_task.bill_inc_id')
                ->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID')
                ->where($where)
                //只获取待派单的订单数据
                ->where(' (tb_ms_store.SEND_ORD_TYPE = 0 OR (tb_ms_store.SEND_ORD_TYPE = 1 AND tb_op_order.send_ord_status != \'N001820100\' )) AND (  (tb_op_order.B5C_ORDER_NO IS NULL)  ) AND ( tb_op_order.BWC_ORDER_STATUS IN (\'N000550600\',\'N000550400\',\'N000550500\',\'N000550800\') ) AND ( tb_op_order.SEND_ORD_STATUS IN (\'N001820100\',\'N001821000\',\'N001820300\') ) AND  (tb_op_order.CHILD_ORDER_ID IS NULL)  AND ( tb_op_order.PLAT_CD NOT IN (\'N000831300\',\'N000830100\') )  ', null, true)
                ->limit($limit)->select();
        }
        return $list;
    }

    public function formatRemindTaskList($list, $trigger_type)
    {
        if (empty($list)) return [];
        $format_code = $this->trigger_status_cd[$trigger_type];
        $column = $this->trigger_type[$trigger_type]['column'];
        $data = [];
        foreach ($list as $key => $val) {
            //获取单号 拉单成功但没有运单号直接跳过
            if ($trigger_type == 0 && $val['LOGISTICS_SINGLE_STATU_CD'] == 'N002080400' && $val['TRACKING_NUMBER'] == "") {
                continue;
            }
            //不符合映射配置的专门做映射
            $k = isset($format_code[$val[$column]]) ? $val[$column] : 'error';
            //归类每个用户处理的订单按照不同的订单状态区分
            if (!isset($data[$val['created_by']][$k])) {
                $tem['trigger_name'] = $val['trigger_name'];
                $tem['created_by'] = $val['created_by'];
                $tem[$column] = $k;
                $tem['cd_val'] = isset($format_code[$val[$column]]) ? $format_code[$val[$column]]['cd_val'] : 'erp内部异常';
                $tem['describe'] = isset($format_code[$val[$column]]) ? $format_code[$val[$column]]['describe'] : '通知实施人员排查';
                $data[$val['created_by']][$k] = $tem;
            }
            if (!in_array($val['ORDER_ID'], $data[$val['created_by']][$k]['order_ids'])) {
                $data[$val['created_by']][$k]['order_ids'][] = $val['ORDER_ID'];
                $data[$val['created_by']][$k]['bill_inc_ids'][] = $val['bill_inc_id'];
            }
        }
        return $data;
    }
}

