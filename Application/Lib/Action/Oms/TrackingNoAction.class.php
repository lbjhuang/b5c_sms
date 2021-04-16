<?php

/**
 * User: shenmo
 * Date: 20/12/8
 * Time: 11:07
 */
class TrackingNoAction extends BasisAction
{
    public $error_message;

    public function _initialize()
    {
        if (!class_exists('ButtonAction')) {
            include_once APP_PATH . 'Lib/Action/Home/ButtonAction.class.php';
        }
        header('Access-Control-Allow-Origin:*');
        if (!in_array(ACTION_NAME, C('FILTER_ACTIONS'))
            && !in_array(strtolower(GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME), C('FILTER_GLOBAL_MODEL_ACTIONS'))
            && 'b08a8be1abd25efd858141757dbfc5c5' != $_GET['api']
        ){
            parent::_initialize();
        }
        $_SESSION['in'] = 'b08a8be1abd25efd858141757dbfc5c5';
    }

    public function lists()
    {
        if (IS_POST) {
            $OrderEsModel = new OrderEsModel();
            $data = $this->joinTermList(DataModel::getData(false));
            //var_dump($data);exit;
//            $data->page->this_page = 1;
//            $data->page->page_count = 10;
            $res = $OrderEsModel->lists($data);
            if ($res['data']) {
                $res['data'] = PatchModel::getStock($res['data']);
            } else {
                $res['data'] = [];
            }
            $this->ajaxReturn($res, 'success', 200000);
        } else {
            $this->display();
        }

    }

    //从待获取运单号退回待派单
    public function return_to_patch()
    {
        try {
            $params = $this->jsonParams()['data']['orders'];
            foreach ($params as $value) {
                $order_es_info[] = [
                    'orderId' => $value['order_number']
                ];
            }
            //查询第三方订单、平台cd
            $where['B5C_ORDER_NO'] = array('IN', array_column($params, 'order_number'));
            $Model = M();
            $search_db = $Model->table('tb_op_order')
                ->field('ORDER_ID orderId,PLAT_CD platform')
                ->where($where)
                ->select();
            if (!empty($order_es_info)) {
                $res = ApiModel::backToDispatch($order_es_info, __FUNCTION__);
                if ($res && in_array('2000', array_column($res['data']['data'], 'code'))) {
                    $return_data = $this->return_success;
                    //派单状态由待获取运单号变为待派单日志记
                    SmsMsOrdHistModel::writeMulHist($this->joinReturnPatchLog($search_db));
                } else {
                    $return_data = $this->return_error;
                }
            } else {
                $return_data = $this->return_error;
            }
            $return_data['data'] = $res;
            //$this->returnFooter($res);
        }catch (Exception $exception) {
            $return_data['data'] = $this->error_message;
            $return_data['status'] = $exception->getCode();
            $return_data['info'] = $exception->getMessage();
        }
        $this->ajaxRetrunRes($return_data);
    }

    /**
     * @param $status_arr
     * @param $where
     * @param $bwc_res_keyval
     *
     * @return mixed
     */
    public function searchStatus($status_arr)
    {
        $Model = M();
        $where['ORDER_ID'] = array('IN', $status_arr);
        $bwc_status_arr = $Model->table('tb_op_order')->field('ORDER_ID,PLAT_CD,BWC_ORDER_STATUS')->where($where)->select();
        foreach ($bwc_status_arr as $value) {
            $bwc_res_keyval[$value['ORDER_ID'] . $value['PLAT_CD']] = $value['BWC_ORDER_STATUS'];
        }
        return $bwc_res_keyval;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function joinReturnPatchLog($data)
    {
        $bwc_res_keyval = $this->searchStatus(array_column($data, 'orderId'));
        foreach ($data as $value) {
            $excel_log ['ORD_NO'] = $value['orderId'];
            $excel_log ['plat_cd'] = $value['platform'];
            $excel_log ['ORD_HIST_SEQ'] = time();
            $excel_log ['ORD_STAT_CD'] = $bwc_res_keyval[$value['orderId'] . $value['platform']];
            $excel_log ['ORD_HIST_WRTR_EML'] = $_SESSION['m_loginname'] ? $_SESSION['m_loginname'] : 'GENERAL SYSTEM';
            $excel_log ['ORD_HIST_REG_DTTM'] = date('Y-m-d H:i:s', time());
            $excel_log ['ORD_HIST_HIST_CONT'] = '派单状态由待获取运单号变为待派单';
            $excel_log_arr[] = $excel_log;
        }
        return $excel_log_arr;
    }

    /**
     * 无需标记发货
     */
    public function noSignSendOut()
    {
        try {
            $params = $this->jsonParams()['data']['orders'];
            foreach ($params as $value) {
                $order_es_info[] = [
                    'thirdOrderId' => $value['thr_order_id'],
                    'platform' => $value['plat_cd']
                ];
            }
            Logs(G('begin', 'end1', 6), LogsModel::$act_microtime . 'no-sign-send-out:'.$delivery_count.'-time');
            //无需标记发货
            $res = ApiModel::notThirdDeliverGoods($order_es_info);
            
            Logs(G('begin', 'end2', 6), LogsModel::$act_microtime . 'no-sign-send-out:'.$delivery_count.'-time');
            
            //批量主动刷新订单
            (new OmsService())->updateOrderFromEs($params,'order_id','plat_cd');
            $return_data['data'] = $res;
        } catch (Exception $exception) {
            $return_data['data'] = $this->error_message;
            $return_data['code'] = $exception->getCode();
            $return_data['msg'] = $exception->getMessage();
        }
        
        $this->ajaxReturn($return_data);
    }

    /**
     * ERP相应调整---作废出库
     */
    public function winitVoidWarehouse()
    {
        try {
            $params = $this->jsonParams()['data']['orders'];
            if ($params) {
                $this->validateElectronicReplyOrderData($params);
            } else {
                throw new Exception('请求为空');
            }
            //并发请求校验
            $rClineVal    = RedisModel::lock('id' . json_decode(array_column($params, 'id')), 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            foreach ($params as $value) {
                $order_info[] = [
                    'orderId' => $value['thr_order_id'],
                    'platCd' => $value['plat_cd']
                ];
            }
            //判断订单是否是万邑通 and 物流下单成功
            $data = (new OmsAfterSaleService())->checkOrderVoidWarehouse($params, 'thr_order_id');

            $delivery_count = count($order_info);
            Logs(G('begin', 'end1', 6), LogsModel::$act_microtime . 'no-sign-send-out:'.$delivery_count.'-time');
            //作废出库开始
            $order_log_m = new OrderLogModel();
            $order_log_m->addAllLog($data, '请求作废出库API开始');

            //作废出库
            $res = ApiModel::winitVoidWarehouse($order_info);
            if ($res && $res['data']) {
                foreach ($data as $item) {
                    $order_tmp[$item['ORDER_ID'] . $item['PLAT_CD']] = $item;
                }
                foreach ($res['data'] as $value) {
                    if (!isset($order_tmp[$value['orderId'] . $value['platCd']])) {
                        continue;
                    }
                    $tmp = $order_tmp[$value['orderId'] . $value['platCd']];
                    if (!empty($value['code']) && '2000' == $value['code']) {
                        $success_data[] = $tmp;
                    } else {
                        $error_data[] = $tmp;
                    }
                }
                if (isset($success_data) && $success_data) {
                    $order_log_m->addAllLog($success_data, '作废接口返回成功');
                }
                if (isset($error_data) && $error_data) {
                    $order_log_m->addAllLog($error_data, '作废接口返回未成功');
                }
            }

            //作废出库结束
            $order_log_m->addAllLog($data, '请求作废出库API结束');

            Logs(G('begin', 'end2', 6), LogsModel::$act_microtime . 'no-sign-send-out:'.$delivery_count.'-time');

            //批量主动刷新订单
            (new OmsService())->updateOrderFromEs($params,'order_id','plat_cd');
            $return_data['data'] = $res;
        } catch (Exception $exception) {
            $return_data['data'] = $this->error_message;
            $return_data['code'] = $exception->getCode() ? $exception->getCode() : 4000;
            $return_data['msg'] = $exception->getMessage();
        }

        RedisModel::unlock('id' . json_decode(array_column($params, 'id')));
        $this->ajaxReturn($return_data);
    }

    /**
     * 获取单号&回邮单号操作
     */
    public function electronicAndReplyOrder()
    {
        try {
            $params = $this->jsonParams()['data']['orders'];
            if ($params) {
                $this->validateElectronicReplyOrderData($params);
            } else {
                throw new Exception('请求为空');
            }
            //并发请求校验
            $rClineVal    = RedisModel::lock('id' . json_decode(array_column($params, 'id')), 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            if (array_unique(array_column($params, 'plat_cd')) != ['N000837445']) {
                throw new Exception('非OTTO平台');
            }
            if (!empty($params)) {
                $order_info = [];
                array_map(function($v) use (&$order_info) {
                    $order_info[] = [
                        'order_id' => $v['thr_order_id'],
                        'plat_cd'  => $v['plat_cd'],
                    ];
                }, $params);
                $res = (new OmsAfterSaleService())->checkOrderRefund($order_info);
                if (true !== $res) {
                    $orderNoRes = array_column($res['data'], 'order_no');
                    $orderNoStrRes = implode(",", $orderNoRes);
                    throw new Exception(L('订单号：'. $orderNoStrRes. '有发起退款售后，禁止发货，请注意！'));
                }
                //批量新增邮件提醒任务
                (new DataService())->addRemindTaskAll($order_info);
            }

            //OTTO回邮单快递公司配置 N00382
            $otto_express = M()->table('tb_ms_cmn_cd')
                ->where(['CD' => ['like', '%N00382%'], 'USE_YN' => 'Y'])->field('CD,ETC')->select();
            if (!$otto_express) {
                throw new Exception('对应的快递公司没有配置，请前往code管理配置');
            }
            if (count($otto_express) > 1) {
                throw new Exception('对应的快递公司有异常 存在多个，请前往code管理配置');
            }
            //OTTO回邮单仓库配置code校验
            $otto_warehouse = M()->table('tb_ms_cmn_cd')
                ->where(['CD' => ['like', '%N00379%'], 'USE_YN' => 'Y'])->field('CD,ETC')->select();
            if (!$otto_warehouse) {
                throw new Exception('对应的仓库没有配置，请前往code管理配置');
            }
            if (count($otto_warehouse) > 1 || strpos($otto_warehouse[0]['ETC'], '易达') === false) {
                throw new Exception('对应的仓库配置有异常，请前往code管理配置');
            }
            if ($otto_express[0]['ETC'] != $otto_warehouse[0]['ETC']) {
                throw new Exception('对应的仓库配置和快递公司对应关系有异常常，请前往code管理配置');
            }
            //OTTO回邮单处理方式配置code校验
            $otto_service = M()->table('tb_ms_cmn_cd')->field('ETC ReturnServiceNo,ETC2 ItemNumber,ETC3 Quantity')
                ->where(['CD' => ['like', '%N00380%'], 'ETC4' => $otto_warehouse[0]['CD'], 'USE_YN' => 'Y'])->select();
            if (!$otto_service) {
                throw new Exception('对应的处理方式没有配置，请前往code管理配置');
            }
            $return_service = CommonDataModel::return_service();
            if (empty($return_service)) {
                throw new Exception('OTTO回邮单处理方式无数据，请联系api同事');
            }
            $return_service_tmp = [];
            foreach ($return_service as $key => $item) {
                $return_service_tmp[$item['ReturnServiceNo'] . $item['ItemNumber']] = $item;
            }
            //对比code配置的处理方式正确性
            foreach ($otto_service as $key => $item) {
                if (!isset($return_service_tmp[$item['ReturnServiceNo'] . $item['ItemNumber']])) {
                    throw new Exception('对应的处理方式有异常，请前往code管理配置');
                }
            }

            $delivery_count = count($params);
            Logs(G('begin', 'end1', 6), LogsModel::$act_microtime . 'electronic-order:'.$delivery_count.'-time');

            //获取单号
            $res['electronicOrder'] = (new PatchModel())->orderStatusUpd($params);
            Logs(G('begin', 'end2', 6), LogsModel::$act_microtime . 'electronic-order:'.$delivery_count.'-time');

            //获取回邮单号
            Logs(G('begin', 'end3', 6), LogsModel::$act_microtime . 'reply-order:'.$delivery_count.'-time');
            $res = (new OmsService())->getReplyOrder($params,'thr_order_id','plat_cd');
            Logs(G('begin', 'end4', 6), LogsModel::$act_microtime . 'reply-order:'.$delivery_count.'-time');

            //批量主动刷新订单
            (new OmsService())->updateOrderFromEs($params,'thr_order_id','plat_cd');
            $return_data         = DataModel::$success_return;
            $return_data['data'] = $res;
        } catch (Exception $exception) {
            $return_data        = DataModel::$error_return;
            $return_data['msg'] = $exception->getMessage();
        }
        RedisModel::unlock('id' . json_decode(array_column($params, 'id')));
        $this->ajaxReturn($return_data);
    }

    private function validateElectronicReplyOrderData($data) {
        foreach ($data as $key => $value) {
            $rules["{$key}.id"]           = 'required|numeric';
            $rules["{$key}.thr_order_id"] = 'required';
            $rules["{$key}.plat_cd"]      = 'required';

            $custom_attributes["{$key}.id"]           = 'id';
            $custom_attributes["{$key}.thr_order_id"] = '订单id';
            $custom_attributes["{$key}.plat_cd"]      = '平台code';
        }
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *
     */
    public function exportOrder()
    {
        set_time_limit(120);
        Logs(I('post_data'), 'I(\'post_data\')');
        $post_data = json_decode(htmlspecialchars_decode(I('post_data')), true);
        $Excel = new ExcelModel();
        $post_data['sort'] = 'ORDER_TIME desc';
        list($xlsName, $xlsCell, $xlsData) = $Excel->exportOrder($post_data, $post_data['type']);
        $Orders = A('Home/Orders');
        $width = ['type' => 'auto_size'];
        $Orders->exportExcel_self($xlsName, $xlsCell, $xlsData, $width);
    }

    /**
     * @param $data
     */
    private function joinTermList($data)
    {
//        $data->data->must_not[] = 'b5cOrderNo';
//        $data->data->must_not[] = 'childOrderId';
        #增加处理中的状态
        $data->data->sendOrdStatus = 'N001820200';
        $data->data->wholeStatusCd = 'N001821102';
        return $data;
    }

}