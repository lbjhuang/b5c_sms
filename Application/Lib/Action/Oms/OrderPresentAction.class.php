<?php

/**
 * User: huanzhu
 * Date: 18/2/26
 * Time: 18:06
 */
class OrderPresentAction extends BaseAction
{

    private $order_model = '';
    private $order_extend_model = '';
    private $guds_model = '';
    private $order_log_model = '';
    private $logistics_mode = '';
    private $delivery_model = '';
    const  ORDER_STATUS = 'N000550400';     //订单状态 (发货)
    const ORD_SUBMIT = 'GENERAL SYSTEM';
    const DETAIL_MSG = '预派单成功';

    public function _initialize()
    {
        header('Access-Control-Allow-Origin: *');
        //header("Content-type: text/html; charset=utf-8");
        $this->order_model = M("op_order", "tb_");
        $this->order_extend_model = M("op_order_extend", "tb_");
        $this->guds_model = M("op_order_guds", "tb_");
        $this->order_log_model = M("ms_ord_hist", "sms_");
        $this->logistics_mode = M("ms_logistics_mode", "tb_");
        $this->delivery_model = M("op_order_delivery", "tb_");
        if (!class_exists('ButtonAction')) {
            include_once APP_PATH . 'Lib/Action/Home/ButtonAction.class.php';
        }
        B('SYSOperationLog');

    }

    private static function get_condition_Data($param)
    {
        $data = Mainfunc::chooseParam($param);
        return $data;
    }

    //列表页面
    public function OrderPresentList()
    {
        $this->display();
    }

    //批量预派单
    public function batch_present_order()
    {
        $this->display();
    }

    //列表数据
    public function getpreList()
    {
        if ($presentData = OrderPresentModel::parseParam()) {
            if (empty($presentData['orderData'])) {
                $presentData['orderData'] = [];
            }
            $this->jsonOut($presentData);
        } else {
            $this->jsonOut(array("code" => 500, "msg" => "data get error"));
        }

    }

    //预派单编辑
    public function edit_present()
    {
        $presentModel = new OrderPresentModel();
        $save_data = $presentModel->getEditData();
        if ($saveRes = M("op_order", "tb_")->where($save_data['where'])->save($save_data['save'])) {
            $this->jsonOut(array("code" => 200, "msg" => "success", "data" => $saveRes));
        } else {
            $this->jsonOut(array("code" => 500, "msg" => "editError"));
        }

    }

    //预派单
    public function preOperation()
    {
        $condition = self::get_condition_Data('condition');
        $where['PLAT_CD'] = $condition['platCd'];
        $where['ORDER_ID'] = $condition['orderId'];
        $guds_order_data = $this->guds_model->field("SKU_ID,B5C_SKU_ID")->where($where)->select();
        foreach ($guds_order_data as $guds) {
            if ($guds['SKU_ID'] != $guds['B5C_SKU_ID']) {
                if (!is_null($guds['SKU_ID']) and is_null($guds['B5C_SKU_ID'])) {
                    if (!$find_res = M("ms_guds_opt", "tb_")->where("GUDS_OPT_ID=" . "'" . $guds['SKU_ID'] . "'")->find()) {
                        $this->jsonOut(array("code" => 50001, "msg" => "sku信息不匹配"));
                    }
                }
            }
        }
        $this->validate_pre_order($where);
        $send_status['SEND_ORD_STATUS'] = 'N001821000';
        if ($preOperationRes = $this->order_model->where($where)->save($send_status)) {
            $this->jsonOut(array("code" => 2000, "msg" => L("预派单成功"), "data" => 'success'));
        } else {
            $this->jsonOut(array("code" => 50001, "msg" => L("预派单失败"), "data" => 'error'));
        }
    }

    /*批量预派单验证*/
    public function validate_pre_order($where = array(), $type = '', $order_id = '')
    {
        $msg = [];
        $order_pre_data = $this->order_model->field("ADDRESS_USER_COUNTRY,ADDRESS_USER_COUNTRY_ID,ADDRESS_USER_PROVINCES,WAREHOUSE,logistic_cd,logistic_model_id,SURFACE_WAY_GET_CD")
            ->where($where)->find();
        if (is_null($order_pre_data['ADDRESS_USER_COUNTRY_ID']) or is_null($order_pre_data['ADDRESS_USER_COUNTRY_ID'])) {
            if ($type == 'batch') {
                $msg[] = '无收件人国家或省份信息';
            } else {
                $this->jsonOut(array("code" => 50001, "msg" => L("无收件人国家或省份信息"), "data" => 'error'));
            }
        }
        if (is_null($order_pre_data['WAREHOUSE']) or empty($order_pre_data['WAREHOUSE'])) {
            if ($type == 'batch') {
                $msg[] = '无下发仓库信息';
            } else {
                $this->jsonOut(array("code" => 50002, "msg" => L("无下发仓库信息"), "data" => 'error'));
            }
        }
        if (is_null($order_pre_data['logistic_cd']) or empty($order_pre_data['logistic_cd'])) {
            if ($type == 'batch') {
                $msg[] = '无下发物流公司信息';
            } else {
                $this->jsonOut(array("code" => 50003, "msg" => L("无下发物流公司信息"), "data" => 'error'));
            }
        }
        if (empty($order_pre_data['SURFACE_WAY_GET_CD']) or empty($order_pre_data['SURFACE_WAY_GET_CD'])) {
            if ($type == 'batch') {
                $msg[] = '无面单获取方式信息';
            } else {
                $this->jsonOut(array("code" => 50004, "msg" => L("无面单获取方式信息"), "data" => 'error'));
            }
        }
        if ($type == 'batch') return $msg;
    }


    //批量预派单(主)
    public function batch_pre_patch()
    {
        $orders_data = self::get_condition_Data('condition');
        $count = 0;
        $count_error = 0;
        foreach ($orders_data as $order) {
            $order_where['PLAT_CD'] = $order['platCd'];
            $order_where['ORDER_ID'] = $order['orderId'];
            $error_type_data = $this->order_model->field("FIND_ORDER_ERROR_TYPE")->where($order_where)->find();
            $error_type_info = OrderPresentModel::change_code($error_type_data['FIND_ORDER_ERROR_TYPE']);
            $msg = $this->validate_pre_order($order_where, 'batch', $order_where['ORDER_ID']);
            $send_status['ADVANCE_ORD_USER'] = $_SESSION['m_loginname'];
            $send_status['ADVANCE_ORD_TIME'] = date("Y-m-d H:i:s", time());
            //var_dump($msg);die;
            if (empty($msg)) {  //无问题,可预派单
                $send_status['SEND_ORD_STATUS'] = 'N001821000';
                OrderPresentModel::get_log_data($order_where['ORDER_ID'], self::DETAIL_MSG, $order_where['PLAT_CD'], 'N001820100');
                if (!$batch_present_res = $this->order_model->where($order_where)->save($send_status)) {
                    $this->jsonOut(array("code" => 50001, "msg" => L("批量预派单失败")));
                }
                $count++;
            } else {
                /*$send_status['ADVANCE_ORD_TYPE_CD'] = $error_type_data['FIND_ORDER_ERROR_TYPE'];
                $send_status['ADVANCE_ORD_MSG'] = $error_type_info;
                if (!$batch_present_res = $this->order_model->where($order_where)->save($send_status)) {
                     $this->jsonOut(array("code"=>50001,"msg"=>L("批量预派单失败")));
                 }*/
                $msg_all['orderId'] = $order['orderId'];
                $msg_all['msg'] = implode(",", $msg);
                //log
                OrderModel::findOrderErrorUpd($order_where);
                OrderPresentModel::get_log_data($order_where['ORDER_ID'], '预派单失败,' . $msg_all['msg'], $order_where['PLAT_CD'], 'N001820100');
                $msg_all['code'] = 40002002;
                $msg_all['shortSupply'] = false;
                $all_msg['message_orders'][] = $msg_all;
                $count_error++;
            }
        }
        $all_msg['orderlist_success'] = $count;
        $all_msg['orderlist_false'] = $count_error;
        $all_msg['orderlist_num'] = $count_error + $count;
        if ($all_msg) {
            $this->jsonOut(array("code" => 2000, "msg" => L("批量预派单成功"), "data" => $all_msg));
        } else {
            $this->jsonOut(array("code" => 50002, "msg" => L("批量预派单失败")));
        }
    }

    //批量一键通过
    public function one_through()
    {
        $condition = self::get_condition_Data();
        foreach ($condition as $v) {
            $where['PLAT_CD'] = $condition['platCd'];
            $where['ORDER_ID'] = $condition['orderId'];
            $SEND_ORD_TYPE['SEND_ORD_TYPE'] = 1;
            if (!$res = M("op_order", "tb_")->where($where)->save($SEND_ORD_TYPE)) {
                $this->jsonOut();
            }
        }

    }

    //点击拆单
    public function spilt_order()
    {
        $condition = self::get_condition_Data('condition');
        $condition['PLAT_CD'] = $condition['platCd'];
        $condition['ORDER_ID'] = $condition['orderId'];

        if (!$this->isMayOperationOrders($condition,true,false )){
            $this->jsonOut(array("code" => 50001, "msg" => L("代销售订单禁止拆单操作"), "data" => 'error'));
        }

        $pModel = new OrderPresentModel();
        if (!is_null($condition['ORDER_ID']) && !is_null($condition['PLAT_CD'])) $split_data = $pModel->get_split_order_data($condition);
        if (!is_null($split_data)) {
            $this->jsonOut(array("code" => 200, "msg" => "success", "data" => $split_data));
        } else {
            $this->jsonOut(array("code" => 50001, "msg" => L("无拆单数据"), "data" => 'error'));
        }
    }

    //拆单选择仓库
    public function split_choose_warehouse()
    {
        $condition = self::get_condition_Data('condition');
        $model = new OrderPresentModel();
        $inventory = $model->get_inventory($condition['sku_id'], $condition['saleTeamCode'], $condition['recom_warehouse_cd']);
        if (!is_null($inventory)) {
            $this->jsonOut(array("code" => 2000, "msg" => L("可售库存"), "data" => $inventory));
        } else {
            $this->jsonOut(array("code" => 50001, "msg" => L("无可售库存")));
        }
    }

    //提交拆单
    public function submit_split_order()
    {
        $order_model = $this->order_model;
        $guds_model = $this->guds_model;;
        $model = new OrderPresentModel();
        $condition = self::get_condition_Data('condition');
        $res = $model->validate_split_data($condition);   //校验数据
        ////判断是否退款
        $order_info[] = [
            'order_id' => $condition[0]['order_id'],
            'plat_cd'  => $condition[0]['plat_cd'],
        ];
        $res_refund = (new OmsAfterSaleService())->checkOrderRefund($order_info);
        if (true !== $res_refund) {
            $ret = array("code" => 50012, "msg" => $res_refund['msg'], "data" => []);
            $this->jsonOut($ret);
        }

        if ($res['code']) {
            $ret = array("code" => 50001, "msg" => $res['msg'], "data" => $inventory);
        } else {
            $order_id = $condition[0]['order_id'];
            $plat_cd = $condition[0]['plat_cd'];
            $otto_sites = $this->getOttoSites();
            $order_model->startTrans();
            $data = $model->deal_submit_order($condition, $order_id, $plat_cd);
            if (!$order_res = $order_model->addAll($data['order'])) {
                $msg = '拆单失败,订单号已被占用,订单拆分存入失败';
                OrderPresentModel::get_log_data($order_id, $msg, $plat_cd, 'N001820100');
                $ret = array("code" => 50003, "msg" => L($msg), "data" => $order_res);
                $order_model->rollback();
            } elseif (!$guds_res = $guds_model->addAll($this->gudsClear($data))) {
                $msg = '拆单失败,sku存入失败';
                OrderPresentModel::get_log_data($order_id, $msg, $plat_cd, 'N001820100');
                $ret = array("code" => 50002, "msg" => L($msg), "data" => $order_res);
                $order_model->rollback();
            }elseif (!$order_extend_res = $this->orderExtendAdd($data)) {
                $msg = '拆单失败,添加订单扩展失败';
                OrderPresentModel::get_log_data($order_id, $msg, $plat_cd, 'N001820100');
                $ret = array("code" => 50002, "msg" => L($msg), "data" => $order_res);
                $order_model->rollback();
            }elseif (in_array($plat_cd, $otto_sites) && !$delivery_res = $model->updateDelivery($order_model, $condition)) {
                $msg = '拆单失败,delivery表子单号更新失败';
                OrderPresentModel::get_log_data($order_id, $msg, $plat_cd, '');
                $ret = array("code" => 50004, "msg" => L($msg), "data" => $order_res);
                $order_model->rollback();
            } else {

                $child_id = $this->order_model->field("CHILD_ORDER_ID")->where("ORDER_ID='{$order_id}'")->find();
                $msg = "拆单成功:子订单号为" . $child_id['CHILD_ORDER_ID'];
                OrderPresentModel::get_log_data($order_id, $msg, $plat_cd, 'N001820100');
                // 接口请求
                $model = new PatchExtendModel($condition);
                $model->dismantling($condition);
                if ($model->getResponse()->code == 2000) {
                    //售后补发单拆单
                    $after_sale_res = (new OmsAfterSaleService())->splitReissue($condition);
                    if (!$after_sale_res) {
                        $msg = "售后-补发单拆单失败";
                        $ret = array("code" => 50006, "msg" => L($msg), "data" => $after_sale_res);
                        $order_model->rollback();
                    } else {
                        $order_model->commit();
                        $ret = array("code" => 2000, "msg" => L("提交成功"), "data" => $guds_res);
                    }
                } else {
                    $order_model->rollback();
                    $ret = array("code" => $model->getResponse()->code, "msg" => L($model->getResponse()->msg), "data" => $model->getResponse()->data);
                }
            }
        }
        //释放锁
        RedisLock::unlock();
        $this->jsonOut($ret);
    }

    //取消拆单
    public function cancel_split()
    {
        $condition = self::get_condition_Data('condition');


        $where_no_del['PLAT_CD'] = $where['PLAT_CD'] = $condition['plat_cd'];
        $where['ORDER_ID'] = $condition['order_id'];
        $parent_order_id = $this->order_model->field("PARENT_ORDER_ID, is_auto_split")->where($where)->find();  //获取该子单母单id
        if ($parent_order_id['is_auto_split']) {
            $this->jsonOut(['code' => 50002, 'msg' => L('自动拆单订单不能取消拆单'), 'data' => []]);
        }
        // 取消拆单接口处理
        $model = new OrderPresentModel();
        $response = $model->cancellationDis($condition['plat_cd'], $parent_order_id['PARENT_ORDER_ID']);
        $r = null;
        if ($response ['code'] == 2000) {
            foreach ($response ['data'] as $key => $value) {
                if ($value ['code'] != 2000) {
                    $r [] = [
                        'msg' => $value ['msg'],
                        'parentOrderId' => $value ['data']['parentOrderId']
                    ];
                }
            }
        } else {
            $this->order_model->rollback();
            $this->jsonOut(['code' => $response ['code'], 'msg' => L($response ['msg']), 'data' => $response ['data']]);
        }
        if (is_null($r)) {
            $otto_sites = $this->getOttoSites();
            $this->order_model->startTrans();
            $parent_where['PLAT_CD'] = $condition['plat_cd'];
            $where_no_del['PARENT_ORDER_ID'] = $parent_where['ORDER_ID'] = $parent_order_id['PARENT_ORDER_ID'];
            $parent_data['CHILD_ORDER_ID'] = null;
            $child_orders_string = $this->order_model->field("CHILD_ORDER_ID")->where($parent_where)->find();  //获取该母单下所有的子单
            $child_orders = explode(",", $child_orders_string['CHILD_ORDER_ID']);
            $no_del_order = $this->order_model->field("CHILD_ORDER_ID")
                ->where($where_no_del)
                ->where('B5C_ORDER_NO IS NULL AND SEND_ORD_STATUS != \'N001820200\'')
                ->select();  //获取该母单下所有的子单
            if (count($child_orders) != count($no_del_order)) {
                $this->order_model->rollback();
                $this->jsonOut(array("code" => 50001, "msg" => L("取消失败,子单状态已派单"), "data" => $no_del_order));
            }
            $del_where['PLAT_CD'] = $condition['plat_cd'];
            foreach ($child_orders as $child_order_id) {   //删订单
                $del_where['ORDER_ID'] = $child_order_id;
                if (!$del_res_order = $this->order_model->where($del_where)->delete()) {
                    $this->order_model->rollback();
                    $this->jsonOut(array("code" => 50001, "msg" => L("取消失败,未成功取消子订单"), "data" => $del_res_order));
                }
                if (!$del_res_guds = $this->guds_model->where($del_where)->delete()) {
                    $this->order_model->rollback();
                    $this->jsonOut(array("code" => 50001, "msg" => L("取消失败,未成功删除关联商品"), "data" => $del_res_order));
                }
            }
            $res1 = $this->order_model->where($parent_where)->save($parent_data);//处理母单

            //otto平台则查找delivery表是否有订单数据，有则需要取消绑定子单号的绑定操作
            if(in_array($condition['plat_cd'], $otto_sites)){
                if (!$delivery_res =  $model->cancelDeliverySplit($condition)) {
                    $this->order_model->rollback();
                    $this->jsonOut(array("code" => 50003, "msg" => L("取消失败,未成功清空关联delivery表子单号"), "data" => $del_res_order));
                }
            }

            //售后补发单取消拆单
            $after_sale_res = (new OmsAfterSaleService())->cancelSplitReissue($parent_order_id['PARENT_ORDER_ID'], $condition['plat_cd']);
            if (!$after_sale_res) {
                $this->order_model->rollback();
                $this->jsonOut(['code' => 50006, 'msg' => L('售后-补发单取消拆单失败'), 'data' => $after_sale_res]);
            }
            $this->order_model->commit();
            OrderLogModel::addLog($parent_order_id['PARENT_ORDER_ID'], $condition['plat_cd'], '取消拆单');
            $this->jsonOut(array("code" => 2000, "msg" => L("取消成功"), "data" => $del_res));
        } else {
            $this->order_model->rollback();
            $this->jsonOut(['code' => 50001, 'msg' => L('取消失败'), 'data' => $r]);
        }
    }

    //获取物流公司下的物流方式
    public function get_log_company()
    {
        $company_code = self::get_condition_Data('company_code');
        $where['LOGISTICS_CODE'] = array("in", $company_code);
        $where['IS_ENABLE'] = 1;
        $where['IS_DELETE'] = 0;
        $logistics_mode = $this->logistics_mode->field("ID,LOGISTICS_MODE,LOGISTICS_CODE")->where($where)->select();
        foreach ($logistics_mode as $k => $logistics) {
            $logistics_mode_data[$k]['ID'] = $logistics['ID'];
            $logistics_mode_data[$k]['CD'] = $logistics['ID'];
            // $logistics_mode_data[$k]['CD'] = $logistics['LOGISTICS_CODE'];
            $logistics_mode_data[$k]['CD_VAL'] = $logistics['LOGISTICS_MODE'];
        }
        if (!$logistics_mode_data) {
            $this->jsonOut(array("code" => 500, "msg" => L("无物流方式数据"), "data" => $logistics_mode_data));
        }
        $this->jsonOut(array("code" => 200, "msg" => "success", "data" => $logistics_mode_data));
    }

    /**
     * @param $data
     * @return mixed
     */
    private function gudsClear($data)
    {
        foreach ($data['guds'] as $key=>$value) {
            if (0 == $value['ITEM_COUNT'] || is_null($value['ITEM_COUNT'])) {
                unset($data['guds'][$key]);
            }
        }
        return $data['guds'];
    }
    /*
     * 添加扩展表数据
     */
    private function orderExtendAdd($data){
        // 处理订单扩展表
        if (isset($data['order_extend']) && !empty($data['order_extend'])){
            // 查看扩展数据否存在
            foreach ($data['order_extend'] as  $value  ){
                $where = [
                  'order_id' => $value['order_id'],
                  'plat_cd' => $value['plat_cd'],
                ];
                $res = $this->order_extend_model->where($where)->find();
                if (empty($res)){
                    $add_data = array(
                        'order_id' => $value['order_id'],
                        'plat_cd' => $value['plat_cd'],
                        'doorplate' => $value['doorplate'],
                        'other_discounted_price' => $value['other_discounted_price'],
                        'platform_discount_price' => $value['platform_discount_price'],
                        'seller_discount_price' => $value['seller_discount_price'],
                        'btc_order_type_cd' => $value['btc_order_type_cd'],
                        'created_by' => userName(),
                        'created_at' => date("Y-m-d H:i:s"),
                    );
                    //次要收货人姓名|次要收货人地址|通行符 不为空则追加到子单中
                    !empty($value['sub_addr_recipient_name']) && $add_data['sub_addr_recipient_name'] = $value['sub_addr_recipient_name'];
                    !empty($value['sub_addr']) && $add_data['sub_addr'] = $value['sub_addr'];
                    !empty($value['kr_customs_code']) && $add_data['kr_customs_code'] = $value['kr_customs_code'];
                    $ret = $this->order_extend_model->add($add_data);
                }else{
                    $save_data = array(
                        'doorplate' => $value['doorplate'],
                        'other_discounted_price' => $value['other_discounted_price'],
                        'platform_discount_price' => $value['platform_discount_price'],
                        'seller_discount_price' => $value['seller_discount_price'],
                        'updated_by' => userName(),
                        'updated_at' => date("Y-m-d H:i:s"),
                    );
                    //次要收货人姓名|次要收货人地址|通行符 不为空则追加到子单中
                    !empty($value['sub_addr_recipient_name']) && $save_data['sub_addr_recipient_name'] = $value['sub_addr_recipient_name'];
                    !empty($value['sub_addr']) && $save_data['sub_addr'] = $value['sub_addr'];
                    !empty($value['kr_customs_code']) && $save_data['kr_customs_code'] = $value['kr_customs_code'];
                    $ret = $this->order_extend_model->where($where)->save($save_data);
                }
                if ($ret === false){
                    return false;
                }

            }
        }
        return true;
    }


    //获取otto的站点cd
    public function getOttoSites(){
       $sites =  M('ms_cmn_cd','tb_')->field('CD')->where(['ETC3'=>'N002625728'])->select();
       return  array_column($sites,'CD');
    }

}