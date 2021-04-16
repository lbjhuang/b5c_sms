<?php

/**
 * User: yangsu
 * Date: 18/3/5
 * Time: 10:27
 */
class PatchAction extends BasisAction
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
            
            $data = PatchModel::filterListsData($data);
            $data->data->not_after_sale_status = 1;
            
           
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

    private function joinInData($data)
    {
        foreach ($data as &$val) {
            $val['orderId'] = $val['third_order_number'];
            $val['platCd'] = $val['plat_cd'];
            $val['orderNo'] = $val['third_party_order_number'];
            $val['storeId'] = $val['STORE_ID'];
            $val['payCurrency'] = $val['trading_currency'];
        }
        return $data;
    }

    /**
     *  Instance
     */
    public function electronicOrder()
    {
        try {
            $data = DataModel::getData(true)['data']['orders'];
            
            if (!empty($data)) {
                
                $order_info = [];
                array_map(function($v) use (&$order_info) {
                    $order_info[] = [
                        'order_id' => $v['thr_order_id'],
                        'plat_cd'  => $v['plat_cd'],
                    ];

                    if (!$this->isMayOperationOrders( array( 'ORDER_ID'=>$v['thr_order_id'] ,'PLAT_CD'=>$v['plat_cd']),true,false)){
                        throw new Exception(L('订单ID：'. $v['thr_order_id']. '  代销售订单禁止获取单号'));
                    }
                    
                }, $data);
                $res = (new OmsAfterSaleService())->checkOrderRefund($order_info);
                if (true !== $res) {
                    $orderNoRes = array_column($res['data'], 'order_no');
                    $orderNoStrRes = implode(",", $orderNoRes);
                    throw new Exception(L('订单号：'. $orderNoStrRes. '有发起退款售后，禁止发货，请注意！'));
                }
                //批量新增邮件提醒任务
                (new DataService())->addRemindTaskAll($order_info);
            }
            // $data_arr_info = $this->getOrderInfos($data);
            // $this->checkElectronicOrderInfo($data_arr_info);

            $res = (new PatchModel())->orderStatusUpd($data);

            //批量主动刷新订单
            (new OmsService())->updateOrderFromEs($data,'thr_order_id','plat_cd');

            if ($res) {
                $return_data = $this->return_success;
            } else {
                $return_data = $this->return_error;
            }
            $return_data['data'] = $res;
        } catch (Exception $exception) {
            $return_data['data'] = $this->error_message;
            $return_data['status'] = $exception->getCode();
            $return_data['info'] = $exception->getMessage();
        }
        $this->ajaxRetrunRes($return_data);
    }

    /**
     *  Instance
     */
    public function checkOrderAddressValidMult()
    {
        try {
            $data = DataModel::getData(true)['data']['orders'];
            if (!empty($data)) {
                $order_info = [];
                array_map(function($v) use (&$order_info) {
                    $order_info[] = [
                        'order_id' => $v['thr_order_id'],
                        'plat_cd'  => $v['plat_cd'],
                    ];
                }, $data);
                $res = (new OmsAfterSaleService())->checkOrderAddressValid($order_info);
            }
            //批量主动刷新订单
            (new OmsService())->updateOrderFromEs($data,'thr_order_id','plat_cd');
            if (true === $res['res']) {
                $return_data = $this->return_success;
            } else {
                $return_data = $this->return_error;
            }
            $return_data['data'] = $res;
        } catch (Exception $exception) {
            $return_data['data'] = $this->error_message;
            $return_data['status'] = $exception->getCode();
            $return_data['info'] = $exception->getMessage();
        }
        $this->ajaxRetrunRes($return_data);
    }

    /**
     *  Instance
     */
    public function checkOrderAddressValid()
    {
        try {
            $data = DataModel::getData(true)['data'];
            if (!empty($data)) {
                $order_info = [
                    'order_id' => $data['order_id'],
                    'plat_cd'  => $data['plat_cd'],
                ];
                $res = (new OmsAfterSaleService())->orderAddressValid($order_info, true);
            }
            //批量主动刷新订单
            (new OmsService())->updateOrderFromEs([$data],'thr_order_id','plat_cd');
            if (true === $res['res']) {
                $return_data = $this->return_success;
            } else {
                $return_data = $this->return_error;
            }
            $return_data['data'] = $res;
        } catch (Exception $exception) {
            $return_data['data'] = $this->error_message;
            $return_data['status'] = $exception->getCode();
            $return_data['info'] = $exception->getMessage();
        }
        $this->ajaxRetrunRes($return_data);
    }

    private function getOrderInfos($data)
    {
        $where_str = '( 1 !=1 ';
        foreach ($data as $val) {
            $temp_str = sprintf(" OR ( tb_op_order.ORDER_ID = '%s' AND tb_op_order.PLAT_CD = '%s' )",
                $val['thr_order_id'], $val['plat_cd']);
            $where_str .= $temp_str;
        }
        $where_str .= ' )';
        $Model = M();
        $info_arr = $Model->table('tb_op_order,tb_op_order_guds')
            ->field('tb_op_order.ORDER_ID,tb_op_order.PLAT_CD,tb_op_order.ADDRESS_USER_ADDRESS1,tb_op_order.ADDRESS_USER_PHONE,tb_op_order_guds.CUSTOMS_PRICE,tb_op_order_guds.B5C_SKU_ID')
            ->where($where_str)
            ->where('tb_op_order.ORDER_ID = tb_op_order_guds.ORDER_ID AND tb_op_order.PLAT_CD = tb_op_order_guds.PLAT_CD', null, true)
            ->select();
        if (!$info_arr) {
            throw new Exception(L('详情信息缺失'), 400400);
        }
        foreach ($info_arr as $key => $val) {
            $data_dimensions[$val['ORDER_ID'] . $val['PLAT_CD']]['ADDRESS_USER_ADDRESS1'] = $val['ADDRESS_USER_ADDRESS1'];
            $data_dimensions[$val['ORDER_ID'] . $val['PLAT_CD']]['ADDRESS_USER_PHONE'] = $val['ADDRESS_USER_PHONE'];
            $temp_gud['CUSTOMS_PRICE'] = $val['CUSTOMS_PRICE'];
            $temp_gud['B5C_SKU_ID'] = $val['B5C_SKU_ID'];
            $data_dimensions[$val['ORDER_ID'] . $val['PLAT_CD']]['guds'][] = $temp_gud;
            unset($temp_gud);
        }
        return $data_dimensions;
    }

    private function checkElectronicOrderInfo($data)
    {
        foreach ($data as $key => $val) {
            $rule[$key . '.ADDRESS_USER_ADDRESS1'] = 'required';
            $rule[$key . '.ADDRESS_USER_PHONE'] = 'required';
            foreach ($val['guds'] as $k => $v) {
                $rule[$key . '.guds.' . $k . '.CUSTOMS_PRICE'] = 'required';
                $rule[$key . '.guds.' . $k . '.B5C_SKU_ID'] = 'required';
            }
        }
        ValidatorModel::validate($rule, $data);
        unset($rules);
        $message = ValidatorModel::getMessage();
        if ($message && strlen($message) > 0) {
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            throw new Exception(L('require data have error'), 40001);
        }
    }

    public function listMenu()
    {
        $return_data = $this->return_data;
        $action = I('action');
        $type = 'patch';
        if ($action) {
            $type = $action;
        }
        $return_data['data'] = PatchModel::listMenu(false, null, $type);
        // $return_data['data'] = PatchModel::filterMenu($return_data['data']);
        if (!empty($return_data['data'])) {
            $return_data['status'] = 200000;
            $return_data['msg'] = 'success';
        }
        $this->ajaxReturn($return_data);
    }
    /**
     * 物流编辑前获取订单的面单方式
     */
    public function checkLogisticsStatus(){
        $post_data = DataModel::getData(true);
     
        $ids = $post_data['id'];
        if(empty($ids)){
            $return_data = $this->return_error;
            $return_data['info'] = 'id为必须参数';
            $return_data['data'] = [];
            $this->ajaxReturn($return_data);
        }
        
        $orders = M('op_order', 'tb_')->alias('orders')->where(['orders.ID' => ['in', $ids]])->join('left join tb_ms_cmn_cd cd on orders.logistic_cd=cd.CD')->field('orders.*,cd.CD_VAL')->select();
       
        if(!count($orders)){
            $return_data = $this->return_error;
            $return_data['info'] = 'id不合法';
            $return_data['data'] = [];
            $this->ajaxReturn($return_data);
        }
        $data = [];
        foreach($orders as $order){
         
            if($order['SURFACE_WAY_GET_CD'] == 'N002010100' && in_array($order['LOGISTICS_SINGLE_STATU_CD'],['N002080200', 'N002080300', 'N002080600', 'N002080500', 'N002080400'])){
                #类型为一键获取单号且已经获取过单号的订单
                $tmp = [
                    'order_no' => $order['ORDER_NO'],
                    'logistic_name' => $order['CD_VAL']
                ];
                $data[] = $tmp;
            }
        }

        $return_data['status'] = 200000;
        $return_data['msg'] = 'success';
        $return_data['data'] = $data;
        $this->ajaxReturn($return_data);
    }
    /**
     * 物流编辑前保存前获取订单的面单方式
     */
    public function checkLogisticsBySave()
    {
        $post_data = DataModel::getData(true);

        $ids = array_column($post_data['orders'], 'id');
        
        $warehouse_ids = array_column($post_data['orders'], 'delivery_warehouse_code');
        $surface_way_get_cd_ids = array_column($post_data['orders'], 'electronic_single_code');
        $logistics_company_code_ids = array_column($post_data['orders'], 'logistics_company_code'); 
        $merge_cd_arr = array_merge($warehouse_ids, $surface_way_get_cd_ids, $logistics_company_code_ids);
       
        $post_data_order_by_id = array_column($post_data['orders'], Null,'id');
       
        if (empty($ids)) {
            $return_data = $this->return_error;
            $return_data['info'] = 'id为必须参数';
            $return_data['data'] = [];

            $this->ajaxReturn($return_data);
        }
        list($data, $error_msg,$exception_detail) = $this->checkNull($post_data['orders']);
        if ($error_msg) {
            $return_data = $this->return_error;
            if (!empty($exception_detail)){
                $return_data['info'] = $exception_detail;
            }else{
                $return_data['info'] = '校验失败，可能原因：请求参数缺失，订单状态已更新，或订单锁获取失败';
            }
            $return_data['data'] = $error_msg;
            $this->deleteDataLockKey($post_data['orders']);
            $this->ajaxReturn($return_data);
        }
        $shipping = M('ms_logistics_mode', 'tb_')->getField('id,LOGISTICS_MODE');
        $orders = M('op_order', 'tb_')
                    ->alias('orders')
                    ->where(['orders.ID' => ['in', $ids]])
                    ->join('left join tb_ms_cmn_cd cd on orders.WAREHOUSE=cd.CD')
                    ->join('left join tb_ms_cmn_cd cd2 on orders.SURFACE_WAY_GET_CD=cd2.CD')
                    ->join('left join tb_ms_cmn_cd cd3 on orders.logistic_cd=cd3.CD')
                    ->field('orders.*,cd.CD_VAL,cd2.CD_VAL as CD_VAL2,cd3.CD_VAL as CD_VAL3')
                    ->select();
        $cd_arr = M('ms_cmn_cd', 'tb_')->where(['CD'=> ['in', $merge_cd_arr]])->getField('CD,CD_VAL');
        if (!count($orders) || !$orders) {
            $return_data = $this->return_error;
            $return_data['info'] = 'id不合法';
            $return_data['data'] = [];
            $this->deleteDataLockKey($post_data['orders']);
            $this->ajaxReturn($return_data);
        }
        $section_ids = [];
        $all_ids = [];
        $data = [];
      
        foreach ($orders as $order) {
           
            if ($order['SURFACE_WAY_GET_CD'] == 'N002010100' && in_array($order['LOGISTICS_SINGLE_STATU_CD'], ['N002080200', 'N002080300', 'N002080600', 'N002080500', 'N002080400']) ) {
               
                #类型为一键获取单号且已经获取过单号的订单
                $tmpChange = [];
                if($post_data_order_by_id[$order['ID']]['delivery_warehouse_code'] != $order['WAREHOUSE']){
                    //
                    $tmpChange['delivery_warehouse'] = [
                        'old'=> $order['CD_VAL'],
                        'new' => $cd_arr[$post_data_order_by_id[$order['ID']]['delivery_warehouse_code']],
                    ];
                }
                if ($post_data_order_by_id[$order['ID']]['electronic_single_code'] != $order['SURFACE_WAY_GET_CD']) {
                    //
                   
                    $tmpChange['electronic_single'] = [
                        'old' => $order['CD_VAL2'],
                        'new' => $cd_arr[$post_data_order_by_id[$order['ID']]['electronic_single_code']],
                    ];
                }
                if ($post_data_order_by_id[$order['ID']]['logistics_company_code'] != $order['logistic_cd']) {
                    //
                    $tmpChange['logistics_company'] = [
                        'old' => $order['CD_VAL3'],
                        'new' => $cd_arr[$post_data_order_by_id[$order['ID']]['logistics_company_code']],
                    ];
                }
                if ($post_data_order_by_id[$order['ID']]['shipping_methods_code'] != $order['logistic_model_id']) {
                    //
                    $tmpChange['shipping_methods'] = [
                        'old' => $shipping[$order['logistic_model_id']],
                        'new' => $shipping[$post_data_order_by_id[$order['ID']]['shipping_methods_code']],
                    ];
                }
                $TRACKING_NUMBER = M('ms_ord_package', 'tb_')
                                    ->where(['ORD_ID'=>$order['ORDER_ID'], 'plat_cd'=>$order['PLAT_CD']])
                                    ->getField('TRACKING_NUMBER');
                                    
                if ($post_data_order_by_id[$order['ID']]['waybill_number'] != $TRACKING_NUMBER) {
                    //
                    $tmpChange['waybill_number'] = [
                        'old' => $TRACKING_NUMBER ? $TRACKING_NUMBER:'',
                        'new' => $post_data_order_by_id[$order['ID']]['waybill_number'],
                    ];
                }
                if(count($tmpChange) >0 ){
                    $tmp = [
                        'order_no' => $order['ORDER_NO'],
                        'change'=> $tmpChange
                    ];
                    $data[] = $tmp;
                }
               
            }else{
                $section_ids[] = $order['ID'];
            }
            $all_ids[] = $order['ID'];
        }
        $re['data'] = $data;
        $re['section_ids'] = $section_ids;
        $re['all_ids'] = $all_ids;
        $return_data['status'] = 200000;
        $return_data['msg'] = 'success';
        $return_data['data'] = $re;
        # 删除锁 key
        $this->deleteDataLockKey($post_data['orders']);
        $this->ajaxReturn($return_data);
    }
    /**
     * 物流编辑
     */
    public function logisticsUpdate()
    {
        $post_data = DataModel::getData(true);
        $ids = array_column($post_data['orders'], 'id');
        if (empty($ids)) {
            //得到各个订单的id（有些页面前端没有id可获取）
            $b5c_order_nos = array_column($post_data['orders'], 'b5c_order_no');
            if (!empty($b5c_order_nos)) {
                $orders = M('op_order', 'tb_')->where(['B5C_ORDER_NO' => ['in', $b5c_order_nos]])->getField('B5C_ORDER_NO,ID');
                foreach ($post_data['orders'] as &$item) {
                    $item['id'] = $orders[$item['b5c_order_no']];
                }
            }
        }
        list($data, $error_msg,$exception_detail) = $this->checkNull($post_data['orders']);
        if ($error_msg) {
            $return_data = $this->return_error;
            if (!empty($exception_detail)){
                $return_data['info'] = $exception_detail;
            }else{
                $return_data['info'] = '校验失败，可能原因：请求参数缺失，订单状态已更新，或订单锁获取失败';
            }
            $return_data['data'] = $error_msg;
        } else {
            try{
                $Model = M();
                $Model->startTrans();
                $res = PatchModel::logisticsUpdate($data, $Model);
//                $Model->commit();
                $return_data         = $this->return_success;
                $return_data['data'] = $res;
                //批量主动刷新订单
                (new OmsService())->updateOrderFromEs($data,'thr_order_id','plat_cd');
            } catch (Exception $exception) {
                $Model->rollback();
                $res = $this->catchException($exception);
                $return_data = $this->return_error;
                $return_data['data'] = [$res['msg']];
            }
        }
        # 释放锁
        RedisLock::unlock();
        # 删除锁 key
        $this->deleteDataLockKey($post_data['orders']);
        if (200000 != $return_data['status']) {
            if (empty($return_data['data'])) {
                $return_data['data'] = '批量编辑失败';
            }
            Logs($return_data, __FUNCTION__. '-批量编辑失败', 'fm');
            if (count($post_data['orders']) > 30) {
                @SentinelModel::addAbnormal('批量编辑30个以上执行结果：', $return_data['status'], [$return_data],'oms_notice');
            }
        }
        $this->ajaxRetrunRes($return_data);
    }

    public function checkNull($data)
    {

        $exception_detail = "";
        $check_data = [];
        $ids = array_column($data, 'id');
        if (!empty($ids)) {
            $check_data = M('op_order', 'tb_')->where(['ID' => ['in', $ids]])->getField('ID,SEND_ORD_STATUS,WAREHOUSE');
        }
        $redis_keys = $redis_values = [];
        $client = RedisModel::client();
        foreach ($data as $item) {
            $key  = $item['thr_order_id'] . '_' . $item['plat_cd'];
            $redis_keys[] = $key;
        }
        $replites = $client->pipeline(function($pipe ) use ($redis_keys) {
            foreach ($redis_keys as $key) {
                $pipe->get($key);
            }
        });
        $redis_values = array_combine($redis_keys,$replites);
        foreach ($data as $key => $val) {
            if (empty($val['id'])) {
                $temp_key = $key + 1;
                $error_msg[] = "第.$temp_key.行缺少自增id";
                if (empty($exception_detail)) $exception_detail = "第.$temp_key.行缺少自增id";
            }
            $index  = $item['thr_order_id'] . '_' . $item['plat_cd'];
            if (empty($val['thr_order_id']) || empty($val['plat_cd']) || empty($val['delivery_warehouse_code']) || empty($val['logistics_company_code']) || empty($val['shipping_methods_code']) || empty($val['electronic_single_code'])) {
                $msg = '';
                if (empty($val['thr_order_id'])) $msg = '订单id';
                if (empty($val['plat_cd'])) $msg = '平台code';
                if (empty($val['delivery_warehouse_code'])) $msg = '下发仓库';
                if (empty($val['logistics_company_code'])) $msg = '物流公司';
                if (empty($val['shipping_methods_code'])) $msg = '物流方式';
                if (empty($val['electronic_single_code'])) $msg = '面单获取方式';
                $show_key = $key + 1;
                $error_msg[] = "第.$show_key.行缺失.$msg";
                if (empty($exception_detail)) $exception_detail = "第.$show_key.行缺失.$msg";
                
            }
            else if ($redis_values[$index]) {
                $error_msg[] = $val['thr_order_id'] . " 订单锁获取失败";
                if (empty($exception_detail)) $exception_detail = "订单锁获取失败";
            }
            else {
                if (empty($check_data[$val['id']])) {
                    $error_msg[] = $val['thr_order_id'] . " 订单信息获取失败";
                    if (empty($exception_detail)) $exception_detail = "订单信息获取失败";
                } elseif ($check_data[$val['id']]['WAREHOUSE'] != $val['delivery_warehouse_code'] && !in_array($check_data[$val['id']]['SEND_ORD_STATUS'], ['N001820100', 'N001821000', 'N001820300'])) {
                    $error_msg[] = $val['thr_order_id'] . " 派单状态已更新，请刷新重试";
                    if (empty($exception_detail)) $exception_detail = "派单状态已更新";
                }

                if (!$this->isMayOperationOrders( array( 'ID' => $val['id'] ) ,true,false)){
                    $error_msg[] = $val['thr_order_id'] . " 代销售订单禁止操作";
                    if (empty($exception_detail)) $exception_detail = "代销售订单禁止操作";
                }

            }



        }
        # 批量设置锁
        $replites2 = $client->pipeline(function($pipe ) use ($redis_keys) {
            $expire = 5;
            foreach ($redis_keys as $key) {
                $pipe->set($key, 1, 'EX',$expire);
            }
        });
        return [$data, $error_msg,$exception_detail];
    }

    protected function deleteDataLockKey($data)
    {
        $redis_keys = $redis_values = [];
        $client = RedisModel::client();
        foreach ($data as $item) {
            $key  = $item['thr_order_id'] . '_' . $item['plat_cd'];
            $redis_keys[] = $key;
        }
        if($redis_keys) {
            $delete_pipes = $client->pipeline(function($pipe ) use ($redis_keys) {
                foreach ($redis_keys as $key) {
                    $pipe->del($key);
                }
            });
        }
        return true;
    }

    public function checkHasAfterSales($data_arr)
    {

        return [$data_arr, $has_after_sales_order_arr];
    }

    // 批量更新延迟发货时间
    public function batchChangeDelayDeliveryTime()
    {
        $request_data = DataModel::getData(true, 'orders');
        try {
            $return_data = (new OmsService())->batchChangeDelayDate($request_data);
        } catch (Exception $exception) {
            $return_data['data'] = $this->error_message;
            $return_data['status'] = $exception->getCode();
            $return_data['info'] = $exception->getMessage();
        }
        $this->ajaxReturn($return_data);
    }

    public function logisticsGet()
    {
        ini_set('memory_limit', '512M');
        import('ORG.Util.String');
        LogsModel::$project_name = 'logisticsGet';
        LogsModel::$time_grain = true;
        LogsModel::$act_microtime = microtime(true);
        $data_arr = DataModel::getData(true);
        $uuid = LogsModel::$uuid = String::uuid();
        Logs('action', $uuid);
        Logs('apiAction', $uuid);
        // 过滤含售后状态(除取消退款)的订单，不允许编辑，因为编辑后会触发自动标记发货，会出现退款给客户同时发货给用户的情况，造成损失
        $map['order_id'] = 'thr_order_id';
        $map['plat_cd'] = 'plat_cd';
        $count_datas_pre = count($data_arr['datas']);
        list($datas, $has_after_sales_order_arr) = $this->filterRefundData($data_arr['datas'], $map);
        $count_datas_after_err = count($has_after_sales_order_arr);
        $count_datas = count($datas);
        $data_arr['datas'] = $datas;
        $return_data = $this->return_success;
        if ($count_datas_after_err === $count_datas_pre) { // 全部订单都不符合
            $return_data['status'] = 200000;
            $return_data['info'] = '售后状态中的订单不允许编辑，请注意！';
            $return_data['data'] = '';
            $this->ajaxRetrunRes($return_data);
            die();
        }

        if ($count_datas_after_err) { // 部分订单不符合
            $return_msg = "售后状态中的订单不允许编辑，已自动过滤掉{$count_datas_after_err}条订单，请注意！";
        }
        Logs(G('begin', 'start3', 6).'---'.G('begin', 'start3', 'm'), 'start time and memory 3', 'fm');
        if (30 <= $count_datas) {
            $chunk_data = array_chunk($data_arr['datas'],20);
            $res['data'] =  GuzzleModel::recommend($chunk_data,$uuid);
        }else{
            list($data, $data_key_arr) = PatchModel::filterRecommend($data_arr['datas'], $uuid, true);
            $res = ApiModel::recommend($data,110);
        }
        Logs(G('begin', 'end3', 6).'---'.G('begin', 'end3', 'm'), 'end  time and memory 3', 'fm');
        Logs('apiEnd', $uuid);
        if ($res) {
            if (is_string($res['data'])) {
                $res['data'] = json_decode($res['data'], true)['data'];
            }
            foreach ($res['data'] as $key => $val) {
                if ($val['code'] != 2000) {
                    $err['orderId'] = $val['orderId'];
                    $err['platCd'] = $val['platCd'];
                    $err_order[] = $err;
                    unset($res['data'][$key]);
                }
            }
//            if (30 > $count_datas) {
//                $res['data'] = PatchModel::removeRecommendName($res['data']);
//            }
            $return_data = $this->return_success;
        }else{
            foreach ($data_arr['datas'] as $key => $val) {
                    $err['orderId'] = $val['thr_order_id'];
                    $err['platCd'] = $val['plat_cd'];
                    $err_order[] = $err;
            }
        }
        if ($err_order) {
            Logs(G('begin', 'start4', 6).'---'.G('begin', 'start4', 'm'), 'start time and memory 4', 'fm');
            Logs('errOrderAct', $uuid);
            $info_res = PatchModel::patchRecommend($err_order);
            if (empty($info_res) && !is_array($info_res)) {
                $return_data = $this->return_error;
            } else {
                foreach ($info_res['data'] as $v) {
                    if ($data_key_arr && is_array($data_key_arr)) {
                        foreach ($v['data']['warehouse'] as $temp_k => $temp_v) {
                            if ($temp_v['cd'] != $data_key_arr[$v['orderId'] . $v['platCd']]) {
                                unset($v['data']['warehouse'][$temp_k]);
                            }
                        }
                        $v['data']['warehouse'] = array_values($v['data']['warehouse']);
                    }
                    $v['is_api'] = false;
                    $res['data'][] = $v;
                }
                $res['data'] = array_values($res['data']);
                $return_data = $this->return_success;
            }
            Logs(G('begin', 'end4', 6).'---'.G('begin', 'end4', 'm'), 'end  time and memory 4', 'fm');
            Logs('errOrderEnd', $uuid);
        }

        if ($count_datas_after_err) {
            foreach ($has_after_sales_order_arr as $key => $value) {
                $after_v = [];
                $after_v['code'] = '40051000';
                $after_v['msg'] = '订单属于售后单（非取消退款），不允许编辑';
                $after_v['data']['orderId'] = $value['order_id'];
                $after_v['data']['platCd'] = $value['plat_cd'];
                $res['data'][] = $after_v;
            }
        }
        if ($res['status'] === 2000) {
            $res['status'] = 200000;
        }
        $return_data['status'] = $return_data['status'];
        $return_data['info'] = $return_msg ? $return_msg : $return_data['info'];
        $return_data['data'] = $res['data'];
        Logs('end', $uuid);
        $this->ajaxRetrunRes($return_data);
    }

    /**
     *
     */
    public function patchs()
    {
        $post_data = DataModel::getData(true);
        $_SESSION['in'] = 'b08a8be1abd25efd858141757dbfc5c5';
        $Orders = A('Home/Orders');
        foreach ($post_data as $value) {
            $order_data['order_id'] = $value['thr_order_id'];
            $order_data['warehouse'] = $value['warehouse'];
            $order_data['plat_cd'] = $value['plat_cd'];
            $order_data['order_plat_key'] = $value['thr_order_id'] . '_' . $value['plat_cd'];
            $order_data_arr[] = $order_data;

            $order_info[] = [
                'order_id' => $value['thr_order_id'],
                'plat_cd'  => $value['plat_cd'],
            ];
            $order_es_info[] = [
                'opOrderId' => $value['thr_order_id'],
                'platCd'    => $value['plat_cd'],
            ];
        }
        //判断是否退款
        $res_refund = (new OmsAfterSaleService())->checkOrderRefund($order_info);
        if (true !== $res_refund) {
            $this->returnFooter($res_refund);
        }

        $res = $Orders->patch_order($order_data_arr, true, true);
        Logs(['order_data_arr' => $order_data_arr, 'res' => $res], __FUNCTION__, __CLASS__);

        if (count($order_info) > 0) {
            //批量主动刷新订单
            ApiModel::updateOrderFromEs($order_es_info, __FUNCTION__);
        }

        //触发ES更新母订单
        OrderModel::triggerESUpdateParentOrder(['ID'=>['in',array_column($post_data, 'id')]]);

        $this->returnFooter($res);

    }

    /**
     * 【批量派单+获取单号】
     */
    public function patchsAndElectronicOrder()
    {
        $post_data = DataModel::getData(true)['data']['orders'];
        $_SESSION['in'] = 'b08a8be1abd25efd858141757dbfc5c5';
        $Orders = A('Home/Orders');
        foreach ($post_data as $key => $value) {
            $order_data['order_id'] = $value['thr_order_id'];
            $order_data['warehouse'] = $value['warehouse'];
            $order_data['plat_cd'] = $value['plat_cd'];
            $order_data['order_plat_key'] = $value['thr_order_id'] . '_' . $value['plat_cd'];
            $order_data_arr[] = $order_data;

            $order_info[] = [
                'order_id' => $value['thr_order_id'],
                'plat_cd'  => $value['plat_cd'],
            ];
            $order_es_info[] = [
                'opOrderId' => $value['thr_order_id'],
                'platCd'    => $value['plat_cd'],
            ];
            unset($post_data[$key]['warehouse']);
        }
        //判断是否退款
        $res_refund = (new OmsAfterSaleService())->checkOrderRefund($order_info);
        if (true !== $res_refund) {
            $this->returnFooter($res_refund);
        }

        //派单
        $res = $Orders->patch_order($order_data_arr, true, true);
        Logs(['order_data_arr' => $order_data_arr, 'res' => $res], __FUNCTION__, __CLASS__);
        //派单成功后才获取单号
        if (!empty($res['body']['success_orders'])) {
            //获取单号
            $res['electronicOrder'] = (new PatchModel())->orderStatusUpd($res['body']['success_orders']);
        }
        
        if (count($order_info) > 0) {
            //批量主动刷新订单
            ApiModel::updateOrderFromEs($order_es_info, __FUNCTION__);
        }

        //触发ES更新母订单
        OrderModel::triggerESUpdateParentOrder(['ID'=>['in',array_column($post_data, 'id')]]);

        $this->returnFooter($res);

    }

    public function showLogs()
    {
        $this->display();
    }

    public function ship_result()
    {
        $this->display();
    }

    /**
     *
     */
    public function importExcel()
    {
        $Order = A('Home/Orders');
        $res = $Order->otherimport();
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

    public function sort_recommend()
    {
        $data['file_name'] = 'sort_recommend.xlsx';
        $this->down($data);
    }

    /**
     * @param null $data
     */
    public function down($data = null)
    {
        if (is_null($data)) $data = json_decode(htmlspecialchars_decode(I('post_data')), true);
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Oms/Public/Excel/' . $data['file_name'];
        Http::download($filename, $filename);

    }

    /**
     * @param null $params
     */
    public function faceOrderGet($params = null)
    {
        if (empty($params)) {
            $post_data = DataModel::getData(true);
        } else {
            $post_data['logistic_model_id'] = $params;
        }
        $res = PatchModel::faceOrderGet($post_data['logistic_model_id']);
        if (!empty($params)) return $res;
        $this->returnFooter($res);
    }

    /**
     * @param $data
     */
    private function joinTermList($data)
    {
        $data->data->must_not[] = 'b5cOrderNo';
        $data->data->must_not[] = 'childOrderId';
        #增加处理中的状态
        $data->data->query_string = 'bwcOrderStatus:(N000550600 OR N000550400 OR N000550500  OR N000550800 OR N000551004) AND (store.sendOrdType:0 OR (store.sendOrdType:1 AND NOT sendOrdStatus:N001820100 ) ) AND sendOrdStatus:(N001820100 OR N001821000 OR N001820300 ) AND NOT platCd:(N000831300 OR N000830100)';
        return $data;
    }

    public function filterRefundData($get_data, $map)
    {
        // 根据订单id和平台cd,去找对应的退款售后状态（除退款取消状态外），如果有则表示该订单无需进行标记发货
        if (!$map) { // 字段映射
            $map['order_id'] = 'ORDER_ID';
            $map['plat_cd'] = 'PLAT_CD';
        }


        
        $order_info = []; $error_order = [];
        foreach ($get_data as $key => $value) {
            $order_info[$key]['order_id'] = $value[$map['order_id']];
            $order_info[$key]['plat_cd'] = $value[$map['plat_cd']];
        }
        $res_refund = (new OmsAfterSaleService())->checkOrderAfterSales($order_info);
        if (true !== $res_refund) { // 有售后状态需要处理的订单，筛选出来
            $error_order = $res_refund; $get_data_new = [];
            $orderkey_arr = array_column($res_refund, 'orderkey');
            foreach ($get_data as $key => $value) {
                if (!in_array($value[$map['plat_cd']] . $value[$map['order_id']], $orderkey_arr)) {
                    $get_data_new[] = $value;
                }
            }
            $get_data = $get_data_new;
        }
        return [$get_data, $error_order];
    }

    /**
     *
     */
    public function signSendOut()
    {
        set_time_limit(300);//api调用时间较久
        import('ORG.Util.String');
        $uuid = String::uuid();
        LogsModel::$project_name = 'signSendOut';
        LogsModel::$time_grain = true;
        LogsModel::$act_microtime = microtime(true);
        Logs('action', $uuid);
        $request_data = DataModel::getData(true, 'data');
        try {
            $this->checkRequestData($request_data, $this->joinSignOutCheckRule($request_data));
            Logs('checkData', $uuid);
           
            list($get_data, $error_data) = $this->filterSignSendData($request_data);
          
            Logs('joinData', $uuid);
            if ($get_data) {
               
                list($get_data, $error_refund_data) = $this->filterRefundData($get_data); // 退款类型订单（除取消退款、无效、待使用外）不参与标记发货
               
                $req_data['data']['orders'] = $get_data;
                Logs('getApiAction', $uuid);
                foreach ($get_data as $value) {
                    OrderLogModel::addLog($value['orderId'], $value['platCd'], '开始标记发货', $value);
                }
                $this->updateThirdDeliverStatusFromDoing($get_data);
                $delivery_count = count($req_data);
                Logs(G('begin', 'end1', 6), LogsModel::$act_microtime . 'flag-delivery-flag-start-count:'.$delivery_count.'-time');
                $response_res = ApiModel::thrSendOut(json_encode($req_data));
                Logs(G('begin', 'end2', 6), LogsModel::$act_microtime . 'flag-delivery-flag-end-count:'.$delivery_count.'-time');
                foreach ($response_res as $value) {
                    if (!empty($value['orderId'])) {
                        OrderLogModel::addLog($value['orderId'], $value['platCd'], '标记发货成功', $value);
                    }
                }
                Logs('getApiEnd', $uuid);
                //批量主动刷新订单
                (new OmsService())->updateOrderFromEs($request_data,'order_id','plat_cd');
            } else {
                $response_res = [];
            }
            //批量新增邮件提醒任务
            (new DataService())->addRemindTaskAll($request_data, 1); //标记发货邮件
            $return_data = $this->joinSignOutReturn($response_res, $error_data, $request_data, $error_refund_data);
           
            Logs('addOrderLog', $uuid);
        } catch (Exception $exception) {
            $return_data['data'] = $this->error_message;
            $return_data['status'] = $exception->getCode();
            $return_data['info'] = $exception->getMessage();
        }
        Logs('end', $uuid);
        
        
        $this->ajaxReturn($return_data);
    }

    private function updateThirdDeliverStatusFromDoing($data)
    {
        $sql = 'UPDATE tb_op_order SET THIRD_DELIVER_STATUS = 2 WHERE 1 != 1 ';
        foreach ($data as $datum) {
            $sql .= " OR ( ORDER_ID = '{$datum['orderId']}' AND PLAT_CD = '{$datum['platCd']}' AND THIRD_DELIVER_STATUS != '1') ";
        }
        $Model = new Model();
        return $Model->execute($sql);
    }

    private function joinSignOutReturn($response_res, $error_data, $request_data, $error_refund_data)
    {
        $success = 200;
        $error_num = $success_num = 0;
        $excel_log_arr = [];
        if (!class_exists('OrdersAction')) {
            include_once APP_PATH . 'Lib/Action/Home/OrdersAction.class.php';
        }
        $Orders = new OrdersAction();
        $bwc_res_keyval = $Orders->searchStatus(array_column($request_data, 'order_id'));
        if ($response_res && $response_res['code'] == 2000) {
            foreach ($response_res['data']['orders'] as $value) {
                if ($value['stat']) {
                    $excel_log_arr = $this->joinLogData($value, $bwc_res_keyval, $excel_log_arr, '标记发货成功');
                    $success_num += 1;
                } else {
                    $excel_log_arr = $this->joinLogData($value, $bwc_res_keyval, $excel_log_arr, '标记发货失败: ' . $value['orderMsg']);
                    $error_num += 1;
                    $message_orders[] = $value;
                }
            }
        }
        if ($error_data) {
            $gp_plat_cds = CodeModel::getGpPlatCds();
            foreach ($error_data as $value) {
                $value_temp['orderId'] = $value['order_id'];
                $value_temp['platCd'] = $value['plat_cd'];
                if(!empty($value['is_gp'])){
                    $value_temp['orderMsg'] = '当前Gshopper平台不支持标记发货';
                }else if (!empty($value['is_opt'])){
                    $value_temp['orderMsg'] = '代销售订单不支持标记发货';
                }else{
                    $value_temp['orderMsg'] = (new OmsService())->get_error_msg_classify($value, $gp_plat_cds);
                }
               
                // $value_temp['orderMsg'] = '该店铺不支持标记发货，或物流信息缺失或发货未对接第三方平台,或已进行标记发货，请检测';
                $message_orders[] = $value_temp;
            }
            $error_num += count($error_data);
        }
        if ($error_refund_data) {
            foreach ($error_refund_data as $value) {
                $value_temp['orderId'] = $value['order_id'];
                $value_temp['platCd'] = $value['plat_cd'];
                $value_temp['orderMsg'] = '售后状态中的订单（除取消退款以外），不允许标记发货';
                $message_orders[] = $value_temp;
            }
            $error_num += count($error_refund_data);
        }
        SmsMsOrdHistModel::writeMulHist($excel_log_arr);
        $res['code'] = $success;
        $res['info'] = 'success';
        $res['body']['orderlist_false'] = $error_num;    // 错误数
        $res['body']['orderlist_success'] = $success_num;
        $res['body']['orderlist_num'] = count($request_data);   // 总数
        $res['body']['message_orders'] = $message_orders;
        return $res;
    }

    private function filterSignSendData($data)
    {
        $gp_plat_cds = CodeModel::getGpPlatCds();
        $gp_plat_cds = "'". implode("', '", $gp_plat_cds). "'";
        $where_str_new = $where_str = ' ( 1 != 1 ';
        foreach ($data as $value) {
            $where_str .= sprintf(" OR (t1.ORDER_ID = '%s' AND t1.PLAT_CD = '%s')", $value['order_id'], $value['plat_cd']);
            $where_str_new .= sprintf(" OR (tb_op_order.ORDER_ID = '%s' AND tb_op_order.PLAT_CD = '%s')", $value['order_id'], $value['plat_cd']);
        }
        $where_str .= ' ) ';
        $where_str_new  .= ' ) ';
        $Model = M();
        #根据code查找GP店铺code
        $shopCodeArr = array_column(CodeModel::getSiteCodeArr('N002620800'), 'CD');
        //10068 GP标记发货的优化补充改动
        $order_data_db = $Model->table('tb_op_order AS t1,tb_ms_store AS t2,tb_ms_ord_package AS t3')
            ->field('t1.ORDER_ID,t1.PLAT_CD,t1.ORDER_ID AS orderId,t1.PLAT_CD AS platCd,CONCAT(t1.ORDER_ID,t1.PLAT_CD) AS order_key')
//            ->where('t1.STORE_ID = t2.ID AND (t1.PLAT_CD = t3.plat_cd AND t1.ORDER_ID = t3.ORD_ID ) AND  length(t2.APPKES) > 5 AND t2.DELIVERY_STATUS = 1 AND (t1.WAREHOUSE IS NOT NULL AND t1.WAREHOUSE != \'\') AND (t1.logistic_cd IS NOT NULL AND t1.logistic_cd != \'\') AND (t1.logistic_model_id IS NOT NULL AND t1.logistic_model_id != \'\') AND ((t3.TRACKING_NUMBER IS NOT NULL AND t3.TRACKING_NUMBER != \'\' ) OR t2.STORE_NAME IN ("Qoo10-JP","Qoo10-SG")) AND t1.THIRD_DELIVER_STATUS != 1')
            ->where("t1.STORE_ID = t2.ID AND (t1.PLAT_CD = t3.plat_cd AND t1.ORDER_ID = t3.ORD_ID ) AND  length(t2.APPKES) > 5 AND t2.DELIVERY_STATUS = 1 AND (t1.WAREHOUSE IS NOT NULL AND t1.WAREHOUSE != '') AND (t1.logistic_cd IS NOT NULL AND t1.logistic_cd != '') AND (t1.logistic_model_id IS NOT NULL AND t1.logistic_model_id != '') AND ((t3.TRACKING_NUMBER IS NOT NULL AND t3.TRACKING_NUMBER != '' ) OR t2.STORE_NAME IN ('Qoo10-JP','Qoo10-SG')) AND (t1.THIRD_DELIVER_STATUS != 1 OR (t1.THIRD_DELIVER_STATUS = 1 AND t1.PLAT_CD IN ({$gp_plat_cds})))")
            ->where($where_str, null, true)
            ->select();
        $key_arr = array_column($order_data_db, 'order_key');
        $order_data_db_all  = array_column($order_data_db, null,'order_key');
        $shopnc_order_data = PatchModel::verifysShopncOrderMult($where_str_new);
        foreach ($data as $value) {
            $is_shopnc_order = in_array($value['order_id'].'_'.$value['plat_cd'],$shopnc_order_data) ? true : false;
            if (in_array($value['plat_cd'], $shopCodeArr) && !$is_shopnc_order) {
                $value['is_gp'] = 1;
                $error_order[] = $value;
                unset($order_data_db_all[ $value['order_id'].$value['plat_cd']]);
                continue;
            }

            if (!$this->isMayOperationOrders( array( 'ORDER_ID'=>$value['order_id'] , 'PLAT_CD'=>$value['plat_cd']) , true,false)){
                $value['is_opt'] = 1;
                $error_order[] = $value;
                unset($order_data_db_all[ $value['order_id'].$value['plat_cd']]);
                continue;
            }

            if (!in_array($value['order_id'] . $value['plat_cd'], $key_arr)) {
                $error_order[] = $value;
                
            }
        }
        $order_data_db_all = array_values($order_data_db_all);
        return [$order_data_db_all, $error_order];
    }
    
    private function checkRequestData($data, $rule)
    {
        ValidatorModel::validate($rule, $data);
        unset($rules);
        $message = ValidatorModel::getMessage();
        if ($message && strlen($message) > 0) {
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            throw new Exception(L('require data have error'), 40001);
        }
    }

    /**
     * @param $data
     * @param $rule
     *
     * @return mixed
     */
    private function joinSignOutCheckRule($data)
    {
        foreach ($data as $key => $val) {
            $rule[$key . '.plat_cd'] = 'required';
            $rule[$key . '.order_id'] = 'required';
        }
        return $rule;
    }

    /**
     * @param $value
     * @param $excel_log
     * @param $bwc_res_keyval
     * @param $excel_log_arr
     *
     * @return array
     */
    private function joinLogData($value, $bwc_res_keyval, $excel_log_arr, $ORD_HIST_HIST_CONT)
    {
        $excel_log ['ORD_NO'] = $value['orderId'];
        $excel_log ['plat_cd'] = $value['platCd'];
        $excel_log ['ORD_HIST_SEQ'] = time();
        $excel_log ['ORD_STAT_CD'] = $bwc_res_keyval[$value['orderId'] . $value['platCd']];
        $excel_log ['ORD_HIST_WRTR_EML'] = $_SESSION['m_loginname'];
        $excel_log ['ORD_HIST_REG_DTTM'] = date('Y-m-d H:i:s', time());
        $excel_log ['ORD_HIST_HIST_CONT'] = $ORD_HIST_HIST_CONT;
        $excel_log_arr[] = $excel_log;
        return $excel_log_arr;
    }


    public function updOrderStatusToPatch()
    {
        $user = session('m_loginname');
        if ('upd' == $_GET['upd']) {
            $time = '2018-06-23 00:00:00';
            $Model = M();
            $Model->startTrans();
            $where['ORDER_TIME'] = array('lt', $time);
            $where['SEND_ORD_STATUS'] = array('neq', 'N001820200');
            $where['B5C_ORDER_NO'] = array('exp', 'IS NULL');
            $save['SEND_ORD_STATUS'] = 'N001820200';
            $order_arr = $Model->table('tb_op_order')
                ->field('ORDER_ID,PLAT_CD')
                ->where($where)
                ->select();
            if (empty($order_arr)) {
                die('无可操作数据');
            }
            foreach ($order_arr as $value) {
                OrderPresentModel::get_log_data($value['ORDER_ID'], $user . L(':更新订单状态为已派单操作'), $value['PLAT_CD']);
            }
            $res = $Model->table('tb_op_order')
                ->where($where)
                ->save($save);
            if ($res) {
                $Model->commit();
            } else {
                $Model->rollback();
            }
            var_dump($res);
        } else {
            $this->display();
        }
    }

    public function deletePatchOrderIds()
    {
        try {
            $this->model = M();
            $this->model->startTrans();
            $request_data = DataModel::getData(true)['data'];

            $order_ids = M('op_order', 'tb_')->where(['ID'=>['in',array_column($request_data, 'id')]])->getField('ORDER_ID', true);

            list($all_guds_id, $all_packages_id, $extend_ids) = $this->copyDelOrderData($request_data);
            $res = $this->deleteData($request_data, 'id', 'tb_op_order', 'FILE_NAME IS NOT NULL AND PARENT_ORDER_ID IS NULL AND B5C_ORDER_NO IS NULL AND SEND_ORD_STATUS IN (\'N001820100\',\'N001821000\',\'N001820300\')', $this->model, 'id');
            $del_gud_res = $this->deleteData($all_guds_id, null, 'tb_op_order_guds', '', $this->model, 'ID');
            if ($extend_ids){
                $del_extend_res = $this->deleteData($extend_ids, null, 'tb_op_order_extend', '', $this->model, 'id');
            }else{
                $del_extend_res['code'] = 200;
            }
            if ($all_packages_id) {
                $del_pack_res = $this->deleteData($all_packages_id, null, 'tb_ms_ord_package', '', $this->model, 'ID');
            } else {
                $del_pack_res['code'] = 200;
            }

            if ($res['code'] == 200 && $del_gud_res['code'] == 200 && $del_extend_res['code'] == 200) {
                $this->model->commit();

                (new OmsAfterSaleService())->cancelReissueByOrder($order_ids);

            } elseif ($del_gud_res['code'] != 200) {
                $res['info'] = '删除订单商品失败';
                $this->model->rollback();
            } elseif ($del_pack_res['code'] != 200) {
                $res['info'] = '删除物流信息失败';
                $this->model->rollback();
            } elseif ($del_extend_res['code'] != 200) {
                $res['info'] = '删除订单扩展表数据失败';
                $this->model->rollback();
            } else {
                $res['info'] = '请检测订单是否 Excel 导入，是否拆单';
                $this->model->rollback();
            }
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = DataModel::$error_return;
            $res['info'] = $exception->getMessage();
        }
        RedisLock::unlock();
        $this->ajaxReturn($res);
    }

    private function copyDelOrderData($data)
    {
        $where_del['tb_op_order.ID'] = array('IN', array_column($data, 'id'));
        $order_res = $this->model->table('tb_op_order')
            ->field('*')
            ->where($where_del)
            ->select();

        if (empty($order_res)) {
            throw new Exception(L('无可删除订单'));
        }
        foreach ($order_res as $v) {

            if (!$this->isMayOperationOrders(array('ORDER_ID'=>$v['ORDER_ID'],'PLAT_CD'=>$v['PLAT_CD']),true)){
                throw new Exception(L('代销售订单禁止删除操作'));
            }

            if (!RedisLock::lock($v['ORDER_ID'] . '_' . $v['PLAT_CD'], 30)) {
                throw new Exception(L('订单锁获取失败'));
            }
        }
        $guds_res = $this->model->table('tb_op_order,tb_op_order_guds')
            ->field('*')
            ->where($where_del)
            ->where('tb_op_order.ORDER_ID = tb_op_order_guds.ORDER_ID AND tb_op_order.PLAT_CD = tb_op_order_guds.PLAT_CD')
            ->select();
        $packages_res = $this->model->table('tb_op_order,tb_ms_ord_package')
            ->field('tb_ms_ord_package.id')
            ->where($where_del)
            ->where('tb_op_order.ORDER_ID = tb_ms_ord_package.ORD_ID AND tb_op_order.PLAT_CD = tb_ms_ord_package.PLAT_CD')
            ->select();
        $extend_res = $this->model->table('tb_op_order,tb_op_order_extend')
            ->field('tb_op_order_extend.id')
            ->where($where_del)
            ->where('tb_op_order.ORDER_ID = tb_op_order_extend.order_id AND tb_op_order.PLAT_CD = tb_op_order_extend.plat_cd')
            ->select();
        $all_guds_id     = array_column($guds_res, 'ID');
        $all_packages_id = array_column($packages_res, 'id');
        $extend_ids      = array_column($extend_res, 'id');
        list($order_res, $guds_res) = $this->removeCopyKey($order_res, $guds_res);
        $user = session('m_loginname');
        foreach ($order_res as $value) {
            OrderPresentModel::get_log_data($value['ORDER_ID'], $user . L(':删除订单'), $value['PLAT_CD']);
        }
        $order_copy = $this->model->table('tb_op_order_del')->addAll($order_res);
        $guds_copy = $this->model->table('tb_op_order_guds_del')->addAll($guds_res);
        if (!$order_copy || !$guds_copy) {
            throw new Exception(L('删除前备份复制数据异常'));
        }
        return [$all_guds_id, $all_packages_id, $extend_ids];
    }

    private function unsetId($data)
    {
        foreach ($data as &$value) {
            unset($value['ID']);
        }
        return $data;
    }

    public function makeOrder()
    {
        $request_data = DataModel::getData(true)['data'];


    }

    public function unMakeOrder()
    {

    }

    /**
     * @param $order_res
     * @param $guds_res
     *
     * @return array
     */
    private function removeCopyKey($order_res, $guds_res)
    {
        $all_order_key = array_keys($order_res[0]);
        $temp_order_key = ['ORDER_ID', 'PLAT_CD', 'PLAT_NAME', 'SHOP_ID', 'ORDER_UPDATE_TIME', 'ORDER_TIME', 'ORDER_PAY_TIME', 'ORDER_CREATE_TIME', 'USER_ID', 'USER_NAME', 'USER_EMAIL', 'ADDRESS_USER_NAME', 'ADDRESS_USER_PHONE', 'ADDRESS_USER_COUNTRY', 'ADDRESS_USER_COUNTRY_ID', 'ADDRESS_USER_COUNTRY_CODE', 'ADDRESS_USER_CITY', 'ADDRESS_USER_PROVINCES', 'ADDRESS_USER_REGION', 'ADDRESS_USER_ADDRESS1', 'ADDRESS_USER_ADDRESS2', 'ADDRESS_USER_ADDRESS3', 'ADDRESS_USER_ADDRESS4', 'ADDRESS_USER_ADDRESS5', 'ADDRESS_USER_POST_CODE', 'PAY_CURRENCY', 'PAY_ITEM_PRICE', 'PAY_TOTAL_PRICE', 'PAY_SHIPING_PRICE', 'PAY_VOUCHER_AMOUNT', 'PAY_PRICE', 'PAY_METHOD', 'PAY_TRANSACTION_ID', 'SHIPPING_TYPE', 'SHIPPING_DELIVERY_COMPANY', 'SHIPPING_DELIVERY_COMPANY_CD', 'SHIPPING_TRACKING_CODE', 'SHIPPING_MSG', 'CRAWLER_DATE', 'UPDATE_TIME', 'ORDER_STATUS', 'ORDER_NUMBER', 'SITE', 'ADDRESS_USER_ADDRESS_MSG', 'BWC_ORDER_STATUS', 'PAY_SETTLE_PRICE', 'BWC_USER_ID', 'PACK_NO', 'PACKING_NO', 'RELATED_ORDER', 'SELLER_DELIVERY_NO', 'B5C_ORDER_NO', 'B5C_ACCOUNT_ID', 'SHORT_SUPPLY', 'B5C_ORDER_DES_COUNT', 'SHIPPING_TIME', 'REFUND_STAT_CD', 'RECEIVER_TEL', 'BUYER_TEL', 'BUYER_MOBILE', 'REMARK_STAT_CD', 'ORDER_STATUS1', 'FAIL_TIMES', 'REMARK_MSG', 'CREATE_USER', 'FILE_NAME', 'TARIFF', 'THIRD_DELIVER_STATUS', 'STORE_ID', 'SHIPPING_NUMBER', 'ORDER_SEQUENCE', 'ORDER_NO', 'SHOP_STORE_MAPPING', 'b5c_logistics_cd', 'UPDATE_USER_LAST', 'SEND_ORD_STATUS', 'WAREHOUSE', 'SEND_ORD_MSG', 'SEND_ORD_TIME', 'SELLER_ID', 'MPS', 'SOURCE', 'FIND_ORDER_JSON', 'FIND_ORDER_ERROR_TYPE', 'FIND_ORDER_INVOKE_TIMES', 'FIND_ORDER_FAIL_MSG', 'FIND_ORDER_TIME', 'PARENT_ORDER_ID', 'CHILD_ORDER_ID', 'COUPONS_ID', 'CANAL_BATCH_VAL', 'receiver_cust_id', 'logistic_cd', 'logistic_model_id', 'SURFACE_WAY_GET_CD', 'PAY_TOTAL_PRICE_DOLLAR', 'SEND_FREIGHT', 'SEND_ORD_TYPE', 'ADVANCE_ORD_TYPE_CD', 'ADVANCE_ORD_MSG', 'ADVANCE_ORD_TIME', 'ADVANCE_ORD_USER', 'SEND_ORD_TYPE_CD', 'SEND_ORD_USER', 'LOGISTICS_SINGLE_STATU_CD', 'LOGISTICS_SINGLE_UP_TIME', 'LOGISTICS_SINGLE_ERROR_MSG', 'SEND_ORD_ERROR_STATUS_CD', 'voucher_code', 'ADDRESS_USER_IDENTITY_CARD', 'SEARCH_BACK', 'has_default_warehouse', 'default_warehouse_logistics'];
        $un_key = array_diff($all_order_key, $temp_order_key);
        $order_res = $this->unsetCopyKey($order_res, $un_key);
        $temp_guds_key = ['ORDER_ITEM_ID', 'B5C_ITEM_ID', 'ITEM_NAME', 'SKU_ID', 'SKU_SIZE', 'SKU_COLOR', 'SKU_MESSAGE', 'ITEM_COUNT', 'ORDER_ID', 'CREATE_TIME', 'SHOP_ID', 'SHIPPING_TYPE', 'ITEM_PRICE', 'PAID_PRICE', 'CURRENCY', 'SHIPPING_AMOUNT', 'VOCHER_AMOUNT', 'ITEM_STATUS', 'PROMISED_SHIPPING_TIME', 'SHIPPING_PROVIDER_TYPE', 'CREATE_AT', 'UPDATE_AT', 'B5C_SKU_ID', 'SLLER_ITEM_CODE', 'OPTION_CODE', 'B5C_SKU_ID1', 'E2G_SKU_ID', 'PARAMS', 'PRODUCT_ORDER_ID', 'PRODUCT_DETAIL_URL', 'UPDATE_USER_LAST', 'GAPP_SKU_ID', 'b5c_sku_id_back', 'PLAT_CD', 'CUSTOMS_PRICE', 'declare_the_price', 'cost_price', 'third_party_agreement_price', 'third_party_sales_price', 'guds_type'];
        $all_order_guds_key = array_keys($guds_res[0]);
        $un_guds_key = array_diff($all_order_guds_key, $temp_guds_key);
        $guds_res = $this->unsetCopyKey($guds_res, $un_guds_key);
        return array($order_res, $guds_res);
    }

    /**
     * @param $data_res
     * @param $un_key
     *
     * @return array
     */
    private function unsetCopyKey($data_res, $un_key)
    {
        foreach ($data_res as $key => $value) {
            foreach ($value as $k => $v) {
                if (in_array($k, $un_key)) {
                    unset($data_res[$key][$k]);
                }
            }
        }
        return $data_res;
    }

    public function cancelOrders()
    {
        try {
            $this->model = M();
            $this->model->startTrans();
            $request_data = DataModel::getData(true)['data'];
            $ids = array_column($request_data, 'id');
            $this->checkOrderNull($ids);
            $ids = array_unique($ids);
            $where['tb_op_order.ID'] = array('IN', $ids);
            $conditions ['_string'] = " tb_op_order.CHILD_ORDER_ID IS NULL AND tb_op_order.B5C_ORDER_NO IS NULL AND tb_op_order.SEND_ORD_STATUS IN ('N001820100', 'N001821000', 'N001820300', 'N001821101')";
            $this->checkOrderHasPatch($where, $conditions, $ids);
            $save_res = $this->updateCancelData($save = [], $where, $conditions);
            if (empty($save_res)) {
                throw new Exception(L('更改订单状态为[取消]失败,请检测订单当前是否派单'));
            }
            $user = session('m_loginname');
            $this->addLog($where, $user);
            $res = DataModel::$success_return;
            $res['info'] = '取消 ' . $save_res . ' 单订单';
            // 接口请求
            $model = new PatchExtendModel();
            $model->cancellationOrder($this->requestData);
            foreach ($model->getResponseData() as $key => $value) {
                if ($value->code != 2000) {
                    $error [] = $value->data->parentOrderId;
                }
            }
            if ($error) {
                $tmpIds = null;
                $input = array_column($this->requestData, 'ORDER_ID', 'id');
                $input = array_flip($input);
                foreach ($error as $key => $value) {
                    $tmpIds [] = $input [$value];
                }
                $where ['ID'] = ['IN', $tmpIds];
                $this->updateCancelData($save = [], $where, $conditions, 'N001820100');
            }
            $this->model->commit();

            //取消售后补发单
            $order_ids = M('op_order', 'tb_')->where(['ID'=>['in',$ids]])->getField('ORDER_ID', true);
            (new OmsAfterSaleService())->cancelReissueByOrder($order_ids);

            //触发ES更新母订单
            OrderModel::triggerESUpdateParentOrder(['ID'=>['in',$ids]]);

        } catch (Exception $exception) {
            $this->model->rollback();
            $res = DataModel::$error_return;
            $res['info'] = $exception->getMessage();
        }
        RedisLock::unlock();
        $this->ajaxReturn($res);
    }

    public function checkOrderNull($data)
    {
        if (empty($data)) {
            throw new Exception(L('请求数据缺失'));
        }
    }

    public $requestData;

    /**
     * @param $where
     * @param $where_str
     * @param $id_arr
     *
     * @throws Exception
     */
    private function checkOrderHasPatch($where, $where_str, $id_arr)
    {
        $select_res = $this->model->table('tb_op_order')
            ->field('tb_op_order.id, tb_op_order.PLAT_CD,tb_op_order.ORDER_ID,tb_op_order_extend.btc_order_type_cd')
            ->join('LEFT JOIN tb_op_order_extend ON tb_op_order.ORDER_ID = tb_op_order_extend.order_id 
	            AND tb_op_order.PLAT_CD = tb_op_order_extend.plat_cd')
            ->where($where)
            ->where($where_str, null, true)
            ->select();
        $this->requestData = $select_res;
        if (count($select_res) != count($id_arr)) {
            throw new Exception(L('请检测订单当前是否包含已派单'));
        }
        foreach ($select_res as $v) {
            if (!RedisLock::lock($v['ORDER_ID'] . '_' . $v['PLAT_CD'], 30)) {
                throw new Exception(L('订单锁获取失败'));
            }

            if ($v['btc_order_type_cd'] == 'N003720003'){
                throw new Exception(L('代销售订单删除操作'));
            }
        }
    }

    /**
     * @param $where
     * @param $user
     */
    private function addLog($where, $user)
    {
        $order_res = $this->model->table('tb_op_order')
            ->field('ORDER_ID,PLAT_CD')
            ->where($where)
            ->select();
        foreach ($order_res as $value) {
            OrderPresentModel::get_log_data($value['ORDER_ID'], $user . L(':取消订单'), $value['PLAT_CD']);
        }
    }

    /**
     * @param $save
     * @param $where
     * @param $where_str
     * @param $state
     *
     * @return mixed
     */
    private function updateCancelData($save, $where, $where_str, $state = 'N001821100')
    {
        $save['SEND_ORD_STATUS'] = $state;
        $save_res = $this->model->table('tb_op_order')
            ->where($where)
            ->where($where_str, null, true)
            ->save($save);
        $this->updateGshopperOrderStatus($where, $where_str);
        return $save_res;
    }

    public function getWarehouseStock($require_data = null)
    {
        try {
            if (empty($require_data)) {
                $require_data = DataModel::getData(true);
            }
            if (empty($require_data)) {
                throw new Exception('无数据');
            }
            $res = DataModel::$success_return;
            $res_data['data'] = PatchModel::getStock($require_data['data']);
            $res['data'] = $res_data['data'];
        } catch (Exception $exception) {
            $res = DataModel::$error_return;
            $res['msg'] = $exception->getMessage();
        }
        $this->ajaxReturn($res);
    }

    /**
     * @param $where
     * @param $where_str
     * @param $save_order_status
     */
    private function updateGshopperOrderStatus($where)
    {
        $where_str = ' CHILD_ORDER_ID IS NULL AND B5C_ORDER_NO IS NULL';
        $gp_plat_cds = CodeModel::getGpPlatCds();
        $where['PLAT_CD'] = array('IN', $gp_plat_cds);
        $store_ids = PatchModel::getShopncStoreIds();
        if ($store_ids){
            $where['STORE_ID'] = array('not in',$store_ids);
        }
        $search_db = $this->model->table('tb_op_order')
            ->field('ORDER_ID,PLAT_CD')
            ->where($where)
            ->where($where_str, null, true)
            ->select();
        foreach ($search_db as $value) {
            OrderLogModel::addLog($value['ORDER_ID'], $value['PLAT_CD'], '更改 Gshopper 订单状态');
        }
        if ($search_db) {
            $save_order_status['BWC_ORDER_STATUS'] = 'N000550900';
            $save_order_status_res = $this->model->table('tb_op_order')
                ->where($where)
                ->where($where_str, null, true)
                ->save($save_order_status);
            Logs($save_order_status_res, 'save_order_status_res', 'updateGshopperOrderStatus');
        }
    }
    //网络异常的谷仓订单自动获取单号
    public function autoGetGcFaceOrder()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $rClineVal = RedisModel::lock('id' . $request_data['id'], 10);
            if ($request_data) {
                $this->validateAutoGetFaceOrderData($request_data);
            } else {
                throw new Exception('请求为空');
            }
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res = DataModel::$success_return;
            $res['code'] = 200;
            PatchModel::addGcFaceOrderToSet($request_data);
            RedisModel::unlock('id' . $request_data['id']);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }
    private function validateAutoGetFaceOrderData($data) {
        foreach ($data as $key => $value) {
            $rules["{$key}.id"] = 'required|numeric';
            $rules["{$key}.order_id"] = 'required';
            $rules["{$key}.plat_cd"] = 'required';

            $custom_attributes["{$key}.id"]          = 'id';
            $custom_attributes["{$key}.order_id"]  = '订单id';
            $custom_attributes["{$key}.plat_cd"]  = '平台code';
        }
        $this->validate($rules, $data, $custom_attributes);
    }

    //关闭谷仓自动获取单号
    public function cancelGcAutoGetFaceOrder()
    {
        try {
            if (!checkPermissions('oms/patch', __FUNCTION__)) {
                throw new Exception(L('权限不足'));
            }
            $request_data = DataModel::getDataNoBlankToArr();
            $rClineVal = RedisModel::lock('id' . $request_data['id'], 10);
            if ($request_data) {
                $this->validateCancelGcData($request_data);
            }
            if (!$rClineVal) {
                throw new Exception(L('获取流水锁失败'));
            }
            $res = DataModel::$success_return;
            $res['code'] = 200;
            if (empty($request_data)) {
                //不选默认取消全部
                PatchModel::removeAllGcFaceOrderSet();
            } else {
                PatchModel::removeGcFaceOrderSet($request_data);
            }
            RedisModel::unlock('id' . $request_data['id']);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }
    private function validateCancelGcData($data) {
        foreach ($data as $key => $value) {
            $rules["{$key}.id"] = 'required|numeric';
            $rules["{$key}.order_id"] = 'required';
            $rules["{$key}.plat_cd"] = 'required';

            $custom_attributes["{$key}.id"]          = 'id';
            $custom_attributes["{$key}.order_id"]  = '订单id';
            $custom_attributes["{$key}.plat_cd"]  = '平台code';
        }
        $this->validate($rules, $data, $custom_attributes);
    }
    /**
     * 物流编辑-待派单
     */
    public function waitLogisticsUpdate()
    {
       $this->logisticsUpdate();
    }
    /**
     *  待派单-获取单号
     */
    public function waitElectronicOrder()
    {
        $this->electronicOrder();
    }

}