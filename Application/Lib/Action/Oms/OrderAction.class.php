<?php



/**
 * User: yangsu
 * Date: 18/2/24
 * Time: 11:03
 */
import('ORG.Util.Date');// 导入日期类


class OrderAction extends BasisAction
{


    /**
     * @return bool|void
     */
    public function _initialize()
    {
        if (!class_exists('ButtonAction')) {
            include_once APP_PATH . 'Lib/Action/Home/ButtonAction.class.php';
        }
        header('Access-Control-Allow-Origin: *');
        if (!in_array(ACTION_NAME, C('FILTER_ACTIONS'))
            && !in_array(strtolower(GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME), C('FILTER_GLOBAL_MODEL_ACTIONS'))
        ) {
            if ('b08a8be1abd25efd858141757dbfc5c5' == $_GET['api'] && 'orderdetail' == strtolower(ACTION_NAME)) {
                $_SESSION['userId'] = 0;
            } else {
                parent::_initialize();
            }
        }
    }

    /**
     *
     */
    public function listMenu()
    {
        $return_data = $this->return_data;
        $return_data['data'] = OrderModel::listMenu();
        if (!empty($return_data['data'])) {
            $return_data['status'] = 200000;
            $return_data['msg'] = 'success';
        }
        $this->ajaxReturn($return_data);
    }

    // 获取符合条件的订单美元总价
    public function getDollarAmount()
    {
        if (IS_POST) {
            $OrderEsModel = new OrderEsModel();

            $data = DataModel::getData(false);
            $data->data->query_string = 'NOT platCd:(N000831300 OR N000830100) ';
            $data->data->must_not = 'parentOrderId';
            $res = $OrderEsModel->getEsAmount($data);
            $this->ajaxReturn($res, 'success', 200000);
        } else {
            $this->return_data['info'] = 'type is error';
            $this->return_data['status'] = 400100;
        }
        $this->ajaxRetrunRes($this->return_data);
    }

    /**
     *
     */
    public function lists()
    {
        if (IS_POST) {
            $OrderEsModel = new OrderEsModel();
            $data = DataModel::getData(false);
            $data->data->query_string = 'NOT platCd:(N000831300 OR N000830100) ';
            // 5pyo5aSPIDgtMyAxMDo1MDo0Nw0K5oiR5Lus5LiN6KaB6aqM6K+B5pS25Lu25Lq65ZCN5a2X
            // $data->data->query_string .= ' AND addressUserName:?* ';
            $data->data->must_not = 'parentOrderId';
            $res = $OrderEsModel->lists($data);
            $res = $this->modOrderList($res);
            $this->ajaxReturn($res, 'success', 200000);
        } else {
            $this->return_data['info'] = 'type is error';
            $this->return_data['status'] = 400100;
        }
        $this->ajaxRetrunRes($this->return_data);
    }

    public function modOrderList($res)
    {
        if (empty($res)) return $res;
        foreach ($res['data'] as $key => $value) {
            $res['data'][$key]['is_show_all_sku'] = false;
        }
        return $res;
    }

    /**
     *
     */
    public function orderDetail()
    {
        if (IS_POST) {
            $post_data = DataModel::getData(false);
            if ($post_data->thr_order_id && $post_data->plat_code) {
                $temp['opOrderId'] = $post_data->thr_order_id;
                $temp['platCd'] = $post_data->plat_code;
                $updateEsData = ApiModel::publicProcess([$temp]);
                Logs($updateEsData, 'es update msg');
                $OrderEsModel = new OrderEsModel();
                $res = $OrderEsModel->orderDetail($post_data);
                $res = $this->joinOrderIsPatch($res);
                $res['data'][0]['is_patch_order'] = $OrderEsModel->isPatchOrder($res);
                $res = $this->getCostPrice($res);
                foreach ($res[0]['guds'] as &$v) {
                    $v['customsPrice'] = $v['customsPrice'] === '0.00' ? $v['costPrice'] * 1.1 : $v['customsPrice'];
                }
                // 11244-代发业务结算价计价方式调整
                $payItemPrice = 0;
                $guds_model = M('op_order_guds', 'tb_');
                foreach ($res['data'][0]['guds'] as &$v) {
                    if ($v['isOnSaleSnapshot'] === null) {
                        $v['is_on_sale_snapshot_name'] = '---';
                    } else if ($v['isOnSaleSnapshot'] == '0') {
                        $v['is_on_sale_snapshot_name'] = '否';
                    } else {
                        $v['is_on_sale_snapshot_name'] = '是';
                    }
                    if (empty($v['isEditedSnapshot'])) {
                        $product = $guds_model->field(['edited_error_msg'])->find($v['id']);
                        $v['is_edited_snapshot_name'] = '未编辑';
                        $v['edited_error_msg'] = $product['edited_error_msg'];

                    } else {
                        $v['is_edited_snapshot_name'] = '已编辑';
                        $v['edited_error_msg'] = '';
                    }
                    if (empty($v['isIndicatorSnapshot'])) {
                        $v['is_indicator_snapshot_name'] = '否';
                    } else {
                        $v['is_indicator_snapshot_name'] = '是';
                    }
                    if (empty($v['indicatorUpdateTime'])) {
                        $v['indicatorUpdateTime'] = '';
                    }
                    if (empty($v['groupSkuId'])) {
                        $v['groupSkuId'] = '';
                    } else {
                        $v['groupSkuId'] = $v['groupSkuIdNumber'];
                    }
                    if ($v['isOnSaleRecieverSnapshot'] == 0) {
                        $v['is_on_sale_reciever_snapshot_name'] = '否';
                    } else if ($v['isOnSaleRecieverSnapshot'] == 1) {
                        $v['is_on_sale_reciever_snapshot_name'] = '是';
                    } else if ($v['isOnSaleRecieverSnapshot'] == 2) {
                        $v['is_on_sale_reciever_snapshot_name'] = '不考虑';
                    }
                    if ($v['gudsType'] == 1) {
                        $v['guds_type_name'] = '赠品';
                    } else {
                        $v['guds_type_name'] = '---';
                    }
                    $cooperative_final_price  =  (isset($v['costPrice']) && !empty($v['costPrice'])) ? $v['costPrice']* 1.2 : 0;
                    $v['cooperative_final_price']  = $cooperative_final_price;
                    //  11092 订单类型=代销售订单
                    if ($res['data'][0]['btc_order_type_cd'] == 'N003720003'){
                        $v['costPrice'] = $cooperative_final_price;
                    }
                    //  11092 订单类型=代发货订单
                    if ($res['data'][0]['btc_order_type_cd'] == 'N003720002'){
                        $v['itemPrice'] = "";
                    }
                    // 商品总价】为USD & 每个SKU的【合作结算单价（订单币种）】* 数量 之和
                    $payItemPrice += $cooperative_final_price * $v['itemCount'];
                }

                if ($res['data'][0]['btc_order_type_cd'] == 'N003720002'){
                    $res['data'][0]['total_price_of_goods'] = $payItemPrice;
                    $res['data'][0]['pay_settle_price'] = '-';
                    $res['data'][0]['pay_settle_price_dollar'] = '-';
                    $res['data'][0]['pay_the_total_price'] = '-';
                    $res['data'][0]['pay_the_total_price_usd'] = '-';
                    $res['data'][0]['cooperative_info']['cooperative_price'] = $payItemPrice;
                    $res['data'][0]['cooperative_info']['cooperative_price_currency_val'] = 'USD';
                }

                $res['data'][0] = PatchModel::getDetailStock($res['data'][0]);
                $this->return_data['data'] = $res;
                $this->return_data['info'] = 'success';
                $this->return_data['status'] = 200000;
            }
            $this->ajaxRetrunRes($this->return_data);
        } else {
            $this->display();
        }
    }

    public function sendGpSms()
    {
        try {
            $data = DataModel::getDataNoBlankToArr();
            $Model = new Model();
            $where['tb_op_order.order_id'] = $data['order_id'];
            $where['tb_op_order.plat_cd'] = $data['plat_cd'];
            $res_db = $Model->table('tb_op_order,tb_op_order_extend')
                ->field('tb_op_order.PAY_CURRENCY,tb_op_order.PAY_PRICE,tb_op_order.PAY_TOTAL_PRICE_DOLLAR,tb_op_order.PAY_TOTAL_PRICE_DOLLAR,tb_op_order.ADDRESS_USER_COUNTRY_CODE,tb_op_order_extend.*')
                ->where($where)
                ->where("tb_op_order.ORDER_ID = tb_op_order_extend.order_id AND tb_op_order.PLAT_CD = tb_op_order_extend.plat_cd", null, true)
                ->find();
            if (empty($res_db)) {
                throw new Exception('订单不存在');
            }
            $sms_content = $data['sms_content'];
            if ('86' == $res_db['tel_code']) {
                $smstype = 1;
                if (empty($sms_content)) {
                    $sms_content = "您的订单：{$data['order_id']}，支付总金额：{$res_db['PAY_CURRENCY']}{$res_db['PAY_PRICE']} 即将发货。请注意查收，感谢在 Gshopper 购物。";
                }
            } else {
                $smstype = 2;
                if (empty($sms_content)) {
                    $sms_content = "Your order: {$res_db['PAY_CURRENCY']}, total payment amount: {$res_db['PAY_CURRENCY']}{$res_db['PAY_PRICE']} is about to be dispatched. Thank you for shopping on Gshopper platform.";
                }
            }

            $data = [
                'key' => GP_SMS_KEY,
                'mobile' => $res_db['tel_code'] . $res_db['credit_card_phone_no'],
                'content' => $sms_content,
                'smstype' => $smstype,
                'auth_id' => 0,
                'order_id' => $data['order_id'],
                'priority' => null,
                'cip' => $res_db['create_user_ip'],
            ];
            $res = DataModel::$success_return;
            $res['data'] = ApiModel::sendCardSms($data);
            if (empty($res['data'])) {
                throw new Exception(L('请求短信接口无返回'));
            }
            if (0 !== $res['data']['code']) {
                $res['code'] = $res['data']['code'];
            }
        } catch (Exception $exception) {
            $res = DataModel::$error_return;
            $res['msg'] = $exception->getMessage();
        }
        $this->ajaxRetrunOriginal($res);
    }

    public function updateEsOrderInfo(array $order_arr = [])
    {
        try {
            if (empty($order_arr)) {
                $order_arr = DataModel::getDataNoBlankToArr();
            }
            $this->verificationUpdateEsRequestData($order_arr);
            if (empty($order_arr)) {
                throw new Exception(L('无正确订单信息'));
            }
            $res = ApiModel::publicProcess($order_arr);
        } catch (Exception $exception) {
            $res = $this->catchException();
        }
        $this->ajaxRetrunRes($res);
    }

    private function verificationUpdateEsRequestData(array $data)
    {
        foreach ($data as $key => $datum) {
            $rules = [
                "{$key}.opOrderId" => 'required|numeric',
                "{$key}.platCd" => 'required|string|size:10'
            ];
        }
        $attributes = [
            'opOrderId' => '订单号',
            'platCd' => '平台'
        ];
        $this->validate($rules, $data, $attributes);
    }

    public function decodeId()
    {
        $data = DataModel::getData()['data']['query'];
        $ord = M('op_order', 'tb_')->field(['ADDRESS_USER_IDENTITY_CARD', 'PLAT_CD', 'BWC_ORDER_STATUS'])
            ->where(['ORDER_ID' => $data['third_order_id'], 'PLAT_CD' => $data['plat_cd']])
            ->find();
        $cardId = $ord['ADDRESS_USER_IDENTITY_CARD'];
        //添加日志
        $sr ['ORD_NO'] = $data['third_order_id'];
        $sr ['ORD_HIST_SEQ'] = time() + rand(0, 10);
        $sr ['ORD_STAT_CD'] = $ord ['BWC_ORDER_STATUS'];
        $sr ['ORD_HIST_WRTR_EML'] = $_SESSION['m_loginname'];
        $sr ['ORD_HIST_REG_DTTM'] = date('Y-m-d H:i:s', $sr ['ORD_HIST_SEQ']);
        $sr ['updated_time'] = date('Y-m-d H:i:s', $sr ['ORD_HIST_SEQ']);
        $sr ['ORD_HIST_HIST_CONT'] = L('身份证号解密');
        $sr ['plat_cd'] = $ord ['PLAT_CD'];
        M()->table('sms_ms_ord_hist')->add($sr);
        if ($cardId) {
            $cardId = (new OrderEsModel())->decodeCardId($cardId, $data['plat_cd'], false);
            $this->ajaxReturn(['code' => 2000, 'msg' => 'success', 'data' => $cardId]);
        } else {
            $this->ajaxReturn(['code' => 3000, 'msg' => L('身份证为空'), 'data' => L('身份证为空')]);
        }
    }

    public function getCostPrice($res)
    {
        import("@.Action.Home.OrdersAction");
        $Orders = new OrdersAction();
        $res['data'][0]['guds'] = $Orders->getCustoms($res['data'][0]['guds'], null, $res['data'][0]['order_id'], 'skuId', 'costPrice');
        return $res;
    }

    public function joinOrderIsPatch($res)
    {
        $res['data'][0]['is_patch'] = 0;
        $order_id = $res['data'][0]['third_party_order_id'];
        $plat_cd = $res['data'][0]['plat_cd'];
        if ($this->searchPatchOrderStatus($order_id, $plat_cd)) {
            $res['data'][0]['is_patch'] = 1;
        }
        return $res;
    }

    /**
     *
     */
    public function orderList()
    {
        $this->display();
    }


    /**
     *
     */
    public function orderDetailUpdSku()
    {
        $post_data = DataModel::getDataNoBlankToArr();

        $res_data = $this->return_success;
        $res_data['data'] = array();

        if (IS_POST && !empty($post_data)) {
            $where['ORDER_ID'] = $post_data['thr_order_id'];
            $where['PLAT_CD'] = $post_data['plat_cd'];
            //锁
            $redisKey = $where['ORDER_ID'] . '_' . $where['PLAT_CD'];
            if (!RedisLock::lock($redisKey)) {
                $res_data = $this->return_error;
                $res_data['info'] = L('订单锁获取失败');
            } elseif (!$this->isMayOperationOrders($where,true,true)) {
                $res_data = $this->return_error;
                $res_data['info'] = L('代销售订单禁止操作');
            }
            else {
                //订单平台是否属于GSHOPPER
                $is_gp = false;
                if ($this->isGpOrderPlatCd($where['PLAT_CD'])) {
                    $is_gp = true;
                }
                $ids = array_column($post_data['sku_arr'], 'id');
                $where['ID'] = ['in', $ids];
                $Model = M();
                //获取原始数据sku信息
                $order_guds = $Model->table('tb_op_order_guds')->where($where)->select();
                $order_guds_data  = array();
                foreach ($order_guds as $itme){
                    $order_guds_data[$itme['ID']] = $itme;
                }

                $order_where = array(
                    'ORDER_ID'=>$post_data['thr_order_id'],
                    'PLAT_CD'=>$post_data['plat_cd'],
                );

                $order = M('order','tb_op_')->where($order_where)->field('B5C_ORDER_NO,ID,PARENT_ORDER_ID,CHILD_ORDER_ID,PLAT_CD')->find();

                // 已经拆单的母单禁止编辑SKU
                if ($order['CHILD_ORDER_ID']){
                    $res_data = $this->return_error;
                    $res_data['info'] = L('已经拆单的母单禁止编辑SKU');
                    $this->ajaxRetrunRes($res_data);
                }

                $is_update_sku = false;
                if (!empty($order['B5C_ORDER_NO']))  {
                    $res_data = $this->return_error;
                    $res_data['info'] = L('订单已派单');
                } else {
                    $Model->startTrans();
                    $is_err = false;
                    try{
                        foreach ($post_data['sku_arr'] as $v) {
                            //属于GSHOPPER的订单不予许修改sku
                            if ($is_gp && $v['sku'] !== $order_guds_data[$v['id']]['B5C_SKU_ID']) {
                                $is_err = true;
                                break;
                            }
                            $where['tb_op_order_guds.ID'] = $v['id'];
                            $save['B5C_SKU_ID'] = $v['sku'];
                            $save['CUSTOMS_PRICE'] = $v['CUSTOMS_PRICE'];
                            $save['UPDATE_AT'] = DateModel::now();
                            $save['UPDATE_USER_LAST'] = session('m_loginname');
                            $save['ORDER_ITEM_ID'] = $v['ORDER_ITEM_ID'];
//                          $msg = "修改 SKU 为 {$save['B5C_SKU_ID']}，金额为 {$save['CUSTOMS_PRICE']}";
                            $LogService = new \LogService();
                            $msg = $LogService->getUpdateMessage('tb_op_order_guds', $where, $save);
                            if (!empty($msg)) {
                                OrderLogModel::addLog($where['ORDER_ID'], $where['PLAT_CD'], $msg);
                            }
                            $res[] = $Model->table('tb_op_order_guds')->where($where)->save($save);

                            //更新delivery表数据
                            $save_delivery['b5c_sku_id'] = $v['sku'];
                            $delivery_where['plat_cd'] = $post_data['plat_cd'];
                            $delivery_where['sku_id'] = $v['thr_sku'];
                            $delivery_where['order_id'] = $post_data['thr_order_id'];
                            
                            $res[] = $Model->table('tb_op_order_delivery')->where($delivery_where)->save($save_delivery);

                            if ($order_guds_data[$v['id']]['B5C_SKU_ID'] != $v['sku']){
                                $is_update_sku = true;
                            }
                        }
                        $Model->commit();
                    }catch (\Exception $e){
                        $Model->rollback();
                        $res_data = $this->return_error;
                        $res_data['info'] = $e->getMessage();
                    }

                    // 同步母单商品信息
                    if ($is_update_sku && $order['PARENT_ORDER_ID']) {
                        //  修改母单商品的信息 【不做删除处理】
                        $condition = array(
                            'ORDER_ID' => $order['PARENT_ORDER_ID'],
                            'PLAT_CD' => $post_data['plat_cd'],

                        );
                        $update_data = array(
                            'ORDER_ID' => $order['PARENT_ORDER_ID'] . "----" . rand(1, 99999)
                        );
                        $Model->table('tb_op_order_guds')->where($condition)->save($update_data);
                        $order_sub = M('order', 'tb_op_')->where($condition)->field('CHILD_ORDER_ID')->find();
                        if ($order_sub) {
                            $condition = array(
                                'ORDER_ID' => array('in',explode(',',$order_sub['CHILD_ORDER_ID'])),
                                'PLAT_CD' => $post_data['plat_cd'],
                            );
                            $order_sub_data =  M('order_guds', 'tb_op_')
                                ->field('*,SUM(ITEM_COUNT) as SUM_ITEM_COUNT')
                                ->where($condition)
                                ->group('B5C_SKU_ID')
                                ->select();
                            foreach ($order_sub_data as &$itme){
                                $itme['ITEM_COUNT'] = $itme['SUM_ITEM_COUNT'];
                                $itme['ORDER_ID'] = $order['PARENT_ORDER_ID'];
                                $itme['item_id'] = uuid();
                                unset($itme['SUM_ITEM_COUNT']);
                                unset($itme['ID']);
                            }
                            // 从新添加母单商品信息
                            $Model->table('tb_op_order_guds')->addAll($order_sub_data);
                            if (!empty($msg)) {
                                OrderLogModel::addLog($order['PARENT_ORDER_ID'], $post_data['plat_cd'], $msg);
                            }
                        }
                    }

                    if ($is_err) {
                        $res_data = $this->return_error;
                        $res_data['info'] = L('订单属于 GSHOPPER，不允许修改SKU');
                    }
                }
            }
            RedisLock::unlock();
        }
        $this->ajaxRetrunRes($res_data);
    }

    /**+
     * @param $plat_cd
     *
     * @return bool
     */
    private function isGpOrderPlatCd($plat_cd)
    {
        if (in_array($plat_cd, CodeModel::getGpPlatCds())) {
            return true;
        }
        return false;
    }

    /**
     *
     */
    public function orderDetailUpdInfo()
    {
        $post_data = DataModel::getData(true);
        $res_data = $this->return_error;
        if (IS_POST && !empty($post_data)) {
            $Model = M('op_order', 'tb_');
            $where['ORDER_ID'] = $post_data['thr_order_id'];
            $where['PLAT_CD'] = $post_data['plat_cd'];

            if (!$this->isMayOperationOrders( $where,true,true)){
                $res_data['info'] = L('代销售订单禁止操作');
                $this->ajaxRetrunRes($res_data);
            }
            $save = OrderModel::joinInfoUpd($post_data['info_arr']);
            if (OrderModel::isB2c($where['PLAT_CD'])) {
                if (strpos($save['ADDRESS_USER_IDENTITY_CARD'], '*') === false) {
                    $save['ADDRESS_USER_IDENTITY_CARD'] = ZUtils::encodeDecodeId('enc', trim($save['ADDRESS_USER_IDENTITY_CARD']));
                } else {
                    unset($save['ADDRESS_USER_IDENTITY_CARD']);
                }
            }
            $ori_country = $Model->where($where)->getField('ADDRESS_USER_COUNTRY');
            $country = TbMsUserAreaModel::checkCountry(strtolower(trim($save['ADDRESS_USER_COUNTRY'])));
            if ($country || $ori_country == $save['ADDRESS_USER_COUNTRY']) {
                if ($country) {
                    $country = json_decode($country, true);
                    $save['ADDRESS_USER_COUNTRY_ID'] = $country['id'];
                    $save['ADDRESS_USER_COUNTRY_EDIT'] = $save['ADDRESS_USER_COUNTRY'];
                }
                unset($save['ADDRESS_USER_COUNTRY']);
                $save['UPDATE_USER_LAST'] = session('m_loginname');
                $save['UPDATE_TIME'] = date("Y-m-d H:i:s");
                $LogService = new \LogService();
                $update_msg = $LogService->getUpdateMessage('tb_op_order', $where, $save);
                $res = $Model->where($where)->save($save);

                // 地址门牌号
                $extend_where['order_id'] = $post_data['thr_order_id'];
                $extend_where['plat_cd'] = $post_data['plat_cd'];
                $extend_save['doorplate'] = isset($post_data['info_arr']['doorplate']) ? $post_data['info_arr']['doorplate'] : " ";
                $extend_save['buyer_user_id'] = isset($post_data['info_arr']['buyerUserId']) ? trim($post_data['info_arr']['buyerUserId']) : " ";
                //编辑订单详情清空地址校验
                $extend_save['address_valid_res'] = "";
                $extend_save['address_valid_status'] = 0;
                $extend_update_msg = $LogService->getOneUpdateMessage('tb_op_order_extend', $extend_where, "doorplate", $extend_save);
                $extend_update_msg .= $LogService->getOneUpdateMessage('tb_op_order_extend', $extend_where, "buyer_user_id", $extend_save);
                $findData = M('order_extend', 'tb_op_')->where($extend_where)->find();
                if ($findData) {
                    $extend_save['updated_by'] = $_SESSION['m_loginname'];
                    $extend_save['updated_at'] = date('Y-m-d H:i:s');
                    $ret = M('order_extend', 'tb_op_')
                        ->where($extend_where)
                        ->save($extend_save);
                } else {
                    $extend_save['order_id'] = $post_data['thr_order_id'];
                    $extend_save['plat_cd'] = $post_data['plat_cd'];
                    $extend_save['created_by'] = $_SESSION['m_loginname'];
                    $extend_save['created_at'] = date('Y-m-d H:i:s');
                    $ret = M('order_extend', 'tb_op_')
                        ->where($extend_where)
                        ->add($extend_save);
                }

                if ($ret === false) {
                    $res_data['info'] = $Model->getError() ?: L('保存失败');
                }
                if (!empty($extend_update_msg)) {
                    $update_msg .= $extend_update_msg;
                }

                if ($res === false) {
                    $res_data['info'] = $Model->getError() ?: L('保存失败');
                } else {
                    $res_data = $this->return_success;
                    $res_data['info'] = L('保存成功');
                    if (!empty($update_msg)) {
                        OrderPresentModel::get_log_data($post_data['thr_order_id'], $update_msg, $post_data['plat_cd'], 'N001820100');
                    }
                    OrderPresentModel::get_log_data($post_data['thr_order_id'], '收货人编辑成功', $post_data['plat_cd'], 'N001820100');
                    $setSystemSort = SystemSortModel::setSystemSort($post_data['thr_order_id'], $post_data['plat_cd'], $save);
                    if ($setSystemSort) {
                        OrderLogModel::addLog($post_data['thr_order_id'], $post_data['plat_cd'], L('限制收货信息替换锁定'));
                    }
                }
            } else {
                $res_data['info'] = L('国家信息不正确，保存失败');
            }

        }
        $this->ajaxRetrunRes($res_data);

    }

    /**
     * 编辑发票地址
     */
    public function orderUpdInvoiceAddress()
    {
        //throw new Exception('订单不存在');
        $post_data = DataModel::getData(true);
        $res_data = $this->return_error;
        if (IS_POST && !empty($post_data)) {
            $Model = M('op_order', 'tb_');
            $where['ORDER_ID'] = $post_data['thr_order_id'];
            $where['PLAT_CD'] = $post_data['plat_cd'];
            $save = OrderModel::joinInfoUpd($post_data['info_arr']);
            $order_id = $Model->where($where)->getField('ID');
            if ($order_id) {
                $field = ['city', 'civility', 'company', 'country', 'name', 'phone', 'apartment_number', 'mobile', 'state', 'street', 'street_1', 'street_2', 'street_3', 'street_4', 'province', 'area', 'zip_code'];
                $LogService = new \LogService();
                // 地址门牌号
                $billing_address_where['order_id'] = $post_data['thr_order_id'];
                $billing_address_where['plat_cd'] = $post_data['plat_cd'];
                $billing_address_save = [];
                $billing_address_update_msg = '';
                foreach ($field as $item) {
                    $billing_address_save[$item] = isset($post_data['info_arr'][$item]) ? $post_data['info_arr'][$item] : " ";
                    //日志记录订单发票地址
                    $billing_address_update_msg .= $LogService->getOneUpdateMessage('tb_op_billing_address', $billing_address_where, $item, $billing_address_save);
                }
                $findData = M('billing_address', 'tb_op_')->where($billing_address_where)->find();
                if ($findData) {
                    $billing_address_save['updated_by'] = $_SESSION['m_loginname'];
                    $billing_address_save['updated_at'] = date('Y-m-d H:i:s');
                    $ret = M('billing_address', 'tb_op_')
                        ->where($billing_address_where)
                        ->save($billing_address_save);
                } else {
                    $billing_address_save['order_id'] = $post_data['thr_order_id'];
                    $billing_address_save['plat_cd'] = $post_data['plat_cd'];
                    $billing_address_save['created_by'] = $_SESSION['m_loginname'];
                    $billing_address_save['created_at'] = date('Y-m-d H:i:s');
                    $ret = M('billing_address', 'tb_op_')
                        ->where($billing_address_where)
                        ->add($billing_address_save);
                }
                if (!empty($billing_address_update_msg)) {
                    $update_msg = $billing_address_update_msg;
                }
                if ($ret === false) {
                    $res_data['info'] = $Model->getError() ?: L('保存失败');
                } else {
                    $res_data = $this->return_success;
                    $res_data['info'] = L('保存成功');
                    if (!empty($update_msg)) {
                        OrderPresentModel::get_log_data($post_data['thr_order_id'], $update_msg, $post_data['plat_cd'], 'N001820100');
                    }
                    OrderPresentModel::get_log_data($post_data['thr_order_id'], '收货人发票地址编辑成功', $post_data['plat_cd'], 'N001820100');
                    $setSystemSort = SystemSortModel::setSystemSort($post_data['thr_order_id'], $post_data['plat_cd'], $save);
                    if ($setSystemSort) {
                        OrderLogModel::addLog($post_data['thr_order_id'], $post_data['plat_cd'], L('限制发票信息替换锁定'));
                    }
                }
            } else {
                $res_data['info'] = L('订单不存在，保存失败');
            }
        }
        $this->ajaxRetrunRes($res_data);

    }

    public function setIsCsv()
    {
        cookie('is_csv', true);
        var_dump(cookie('is_csv'));
    }


    public function checkExportOrder()
    {
        $post_data = DataModel::getData();
        $post_data['sort'] = 'ORDER_TIME desc';
        $Excel = new ExcelModel();
        $response = array(
            'code' => 200,
            'is_hint' => false,
        );
        list($total, $query) = $Excel->checkExportOrder($post_data, $post_data['type']);
        if ($total > 5000) {
            $dataService = new DataService();
            $excel_name = DataModel::userNamePinyin() . "-订单列表-" . time() . '.csv';
            $dataService->addOne($query, 0, $excel_name, $total);
            $response['is_hint'] = true;
        }
        $this->ajaxReturn($response);
    }


    /**
     *
     */
    public function exportOrder()
    {
        ini_set('max_execution_time', '120');
        session_write_close();
        Logs(I('post_data'), 'I(\'post_data\')');
        $post_data = json_decode(htmlspecialchars_decode(I('post_data')), true);
        $post_data['sort'] = 'ORDER_TIME desc';
        $Excel = new ExcelModel();
        list($xlsName, $xlsCell, $xlsData) = $Excel->exportOrder($post_data, $post_data['type']);
        if (3000 < count($xlsData)) {
            unset($xlsName, $xlsCell, $xlsData);
            $this->exportOrderCsv($post_data);
            die();
        }
        $Orders = A('Home/Orders');
        $width = ['type' => 'auto_size'];
        $Orders->exportExcel_self($xlsName, $xlsCell, $xlsData, $width);
    }


    private function exportOrderCsv($post_data)
    {
        LogsModel::$project_name = 'exportOrderCsv';
        LogsModel::$time_grain = true;
        Logs('act');
        $Excel = new ExcelModel();
        list($xlsName, $string) = $this->getCsvTitle($post_data, $Excel);
        $string .= $this->getCsvCount($post_data, $Excel);
        Logs('end');
        ExcelModel::exportCsv($xlsName, $string);
    }

    /**
     *
     */
    public function ordersExport()
    {
        if (IS_POST) {
            $OrderEsModel = new OrderEsModel();
            $post_data = DataModel::getData(false);
            $post_data->page->page_count = 3000;
            $post_data->page->this_page = 0;
            $res = $OrderEsModel->lists($post_data, true);
            $res_arr = $OrderEsModel->mapOrderExport($res);
            $Excel = new OrdersAction();
            $expTitle = 'excel';
            list($expCellName, $expTableData) = $this->joinExpExcel($res_arr);
            $Excel->exportExcel($expTitle, $expCellName, $expTableData, $type = 0);
        }


    }

    /**
     *  修改 订单 收货地址
     */
    public function updateOrderAddres()
    {
        $post_data = DataModel::getData(true);
        $tran_result = true;
        // 增加日志
        $LogService = new \LogService();

        $trans = M();
        $trans->startTrans();   // 开启事务
        try {
            foreach ($post_data as $k => $v) {
                if (!isset($v['thr_order_id']) || empty($v['thr_order_id'])) {
                    throw new Exception("参数有误");
                }
                if (!isset($v['plat_cd']) || empty($v['plat_cd'])) {
                    throw new Exception("更新失败");
                }
                $where['ORDER_ID'] = $v['thr_order_id'];
                $where['PLAT_CD'] = $v['plat_cd'];

                if (!$this->isMayOperationOrders( $where)){
                    $this->return_failed['info'] = "代销售订单或者shopnc平台订单禁止操作";
                    throw new Exception("代销售订单或者shopnc平台订单禁止操作");
                }

                $save['ADDRESS_USER_ADDRESS1'] = isset($v['address']) ? trim($v['address']) : "";
                // $save['ADDRESS_USER_ADDRESS2'] = isset($v['address2']) ? trim($v['address2']) : "";
                $update_msg = $LogService->getUpdateMessage('tb_op_order', $where, $save);
                $result = M('order', 'tb_op_')
                    ->where($where)
                    ->save($save);

                if ($result === false) {
                    throw new Exception("更新失败");
                }
                $extend_where['order_id'] = $v['thr_order_id'];
                $extend_where['plat_cd'] = $v['plat_cd'];
                $extend_save['doorplate'] = isset($v['doorplate']) ? trim($v['doorplate']) : "";
                $extend_update_msg = $LogService->getOneUpdateMessage('tb_op_order_extend', $extend_where, "doorplate", $extend_save);
                $findData = M('order_extend', 'tb_op_')->where($extend_where)->find();
                if ($findData) {
                    $saveData = array(
                        'doorplate' => isset($v['doorplate']) ? trim($v['doorplate']) : "",
                        'created_by' => $_SESSION['m_loginname'],
                        'created_at' => date('Y-m-d H:i:s'),
                    );
                    $result = M('order_extend', 'tb_op_')
                        ->where($extend_where)
                        ->save($saveData);
                } else {
                    $addData = array(
                        'order_id' => $v['thr_order_id'],
                        'plat_cd' => $v['plat_cd'],
                        'created_by' => $_SESSION['m_loginname'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'doorplate' => isset($v['doorplate']) ? trim($v['doorplate']) : "",
                    );
                    $result = M('order_extend', 'tb_op_')
                        ->where($extend_where)
                        ->add($addData);
                }

                if ($result === false) {
                    throw new Exception("更新失败");
                }
                if (!empty($extend_update_msg)) {
                    $update_msg .= $extend_update_msg;
                }
                if (!empty($update_msg)) {
                    OrderPresentModel::get_log_data($v['thr_order_id'], $update_msg, $v['plat_cd'], 'N001820100');
                }
            }
        } catch (Exception $ex) {
            $tran_result = false;
        }
        if ($tran_result === false) {
            $trans->rollback();
            $this->ajaxRetrunRes($this->return_failed);
        } else {
            $trans->commit();
            //(new OmsService())->updateOrderFromEs($post_data, 'thr_order_id', 'plat_cd');
            $this->ajaxRetrunRes($this->return_success);
        }
    }

    /**
     *
     */
    public function orderRemarks()
    {
        $post_data = DataModel::getData(true);
        $Model = M();
        $where['ORDER_ID'] = $post_data['thr_order_id'];
        $where['PLAT_CD'] = $post_data['plat_cd'];
        $save_type = [
            'operate' => 'REMARK_MSG'
        ];
        $save[$save_type[$post_data['remarks_type']]] = trim($post_data['remarks_msg']);
        $save['REMARK_STAT_CD'] = 1;
        $this->return_success['data'] = $Model->table('tb_op_order')->where($where)->save($save);
        (new OmsService())->updateOrderFromEs([$post_data], 'thr_order_id', 'plat_cd');
        $this->ajaxRetrunRes($this->return_success);
    }

    /**
     * @param $res_arr
     * @param $join_data
     * @param $expTableData
     *
     * @return array
     */
    private function joinExpExcel($res_arr)
    {
        $expCellName = array(
            array('ID', '收款单号')

        );
        foreach ($res_arr as $key => $val) {
            $join_data['SKU_ID'] = $val['SKU_ID'];
            $expTableData[] = $join_data;
        }
        return array($expCellName, $expTableData);
    }

    /**
     * @return mixed
     */
    public function orderLog()
    {
        //tb_op_order.ORDER_ID
        $thrOrderId = I('get.thr_order_id');
        $platCd = I('get.plat_cd');
        $logWhere['ORD_NO'] = $thrOrderId;
        $logWhere['plat_cd'] = $platCd;
        $opOrder = M('op_order', 'tb_')->where(['ORDER_ID' => $thrOrderId, 'PLAT_CD' => $platCd])->find();
        //修改OMS订单查询业务兼容（根据订单创建时间判断小于等于 2020.4.1 日订单两表都进行查询，大于采用新表）
        $logField = 'ORD_HIST_REG_DTTM, ORD_STAT_CD, ORD_HIST_WRTR_EML, ORD_HIST_HIST_CONT,tb_ms_cmn_cd.CD_VAL AS ORD_STAT_CD_NAME';
        if ($opOrder['ORDER_TIME'] > '2020-04-03 00:00:00') {
            //在新表里查询
            $type = 2;
            $this->return_success['data'] = $this->getOrderLog($logField, $logWhere, $type);
            $this->ajaxRetrunRes($this->return_success);
            exit;
        }
        //在旧表里查询
        $type = 1;
        $opOrderLogOld = $this->getOrderLog($logField, $logWhere, $type);
        //在新表里查询
        $type = 2;
        $opOrderLogNew = $this->getOrderLog($logField, $logWhere, $type);
        $opOrderLog = array_merge((array)$opOrderLogNew, (array)$opOrderLogOld);
        $this->return_success['data'] = $opOrderLog;
        $this->ajaxRetrunRes($this->return_success);
    }

    /**
     * @return mixed
     */
    public function getOrderLog($logField, $logWhere, $type)
    {
        //在旧表里查询
        $ModelLog = M('ms_ord_hist_0323', 'sms_');
        $table = 'sms_ms_ord_hist_0323';
        if ($type == 2) {
            //在新表里查询
            $ModelLog = M('ms_ord_hist', 'sms_');
            $table = 'sms_ms_ord_hist';
        }
        $data = $ModelLog->field($logField)
            ->where($logWhere)
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = ' . $table . '.ORD_STAT_CD ')
            ->order('id desc')
            ->limit(500)
            ->select();
        return $data;
    }

    /**
     *  Close order
     *  set order statue
     *
     * @return null
     */
    public function closeOrder()
    {
        $res = OrderModel::closeord(DataModel::getData(true)['datas']);
        $this->returnFooter($res);
    }

    /**
     *
     */
    public function links()
    {
        $this->error_message['info'] = L('无商品链接数据');
        $res = OrderModel::links(DataModel::getData(true));
        $this->returnFooter($res);
    }

    /**
     *
     */
    public function faceAlonePath()
    {
        $post_data = DataModel::getData(true);
        $arrays = OrderModel::faceAloneJoin($post_data);
        $this->returnFooter($arrays);
    }

    /**
     *
     */
    public function printShow()
    {
        $this->display();
    }

    /**
     *
     */
    public function logisicsEdit()
    {
        import('ORG.Util.String');
        LogsModel::$project_name = 'logisicsEdit';
        LogsModel::$time_grain = true;
        LogsModel::$act_microtime = microtime(true);
        LogsModel::$uuid = String::uuid();
        Logs('action');
        ini_set('date.timezone', 'Asia/Shanghai');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        $this->assign('order_nums', json_encode(OrderModel::logisicsEditData($filePath)['body']), JSON_UNESCAPED_UNICODE);
        Logs('end');
        $this->display('showlogs');
        // $this->returnFooter($res);
    }

    /**
     *
     */
    public function logisicsSortEdit()
    {
        ini_set('date.timezone', 'Asia/Shanghai');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        $this->assign('order_nums', json_encode(OrderModel::logisicsSortEdit($filePath)['body']), JSON_UNESCAPED_UNICODE);
        $this->display('showlogs');
        // $this->returnFooter($res);
    }

    /**
     *
     */
    public function storesGet()
    {
        $this->return_error['info'] = L('店铺无数据');
        $this->returnFooter(OrderModel::storesGet(DataModel::getData(true)));
    }

    /**
     *
     */
    public function outsend()
    {
        $this->display();
    }

    /**
     *
     */
    public function invoice()
    {
        $data = DataModel::getData(true)['data'];
        /*$data['b5c_order_no'][] = 'gspt524565480121';
        $data['b5c_order_no'][] = 'gspt524570471047';*/
        $res = OrderModel::invoice($data);
        Logs(json_encode($res, JSON_UNESCAPED_UNICODE));
        $this->returnFooter($res);
    }

    /**
     * @param $order_id
     * @param $plat_cd
     *
     * @return mixed
     */
    public function searchPatchOrderStatus($order_id, $plat_cd)
    {
        $where_arr['tb_op_order.ORDER_ID'] = $order_id;
        $where_arr['tb_op_order.PLAT_CD'] = $plat_cd;
        if ($where_arr['tb_op_order.PLAT_CD'] && $where_arr['tb_op_order.ORDER_ID']) {
            $model = M('op_order', 'tb_');
            $model
                ->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID');
            $model->where($where_arr);
            $temp_res = $model->where(' (tb_ms_store.SEND_ORD_TYPE = 0 OR (tb_ms_store.SEND_ORD_TYPE = 1 AND tb_op_order.send_ord_status != \'N001820100\' )) AND (tb_op_order.PARENT_ORDER_ID IS NULL) AND (  (tb_op_order.B5C_ORDER_NO IS NULL)  ) AND ( tb_op_order.BWC_ORDER_STATUS IN (\'N000550600\',\'N000550400\',\'N000550500\',\'N000550800\') ) AND ( tb_op_order.SEND_ORD_STATUS IN (\'N001820100\',\'N001821000\',\'N001820300\') ) AND ( tb_op_order.PLAT_CD NOT IN (\'N000831300\',\'N000830100\') )  ', null, true)->select();
        }
        return $temp_res;
    }

    public function updateOrderPayAllUsd()
    {
        $Model = M();
        $Model->startTrans();
        $where_str = "((PAY_CURRENCY != 'USD' AND PAY_TOTAL_PRICE_DOLLAR = PAY_TOTAL_PRICE ) OR (PAY_CURRENCY = 'USD' AND PAY_TOTAL_PRICE_DOLLAR != PAY_TOTAL_PRICE )) AND ORDER_PAY_TIME IS NOT NULL  ";
        $res = $Model->table('tb_op_order')
            ->field('ORDER_ID,PLAT_CD,ID,PAY_TOTAL_PRICE,PAY_TOTAL_PRICE_DOLLAR,ORDER_PAY_TIME,PAY_CURRENCY')
            ->where($where_str, null, true)
            ->select();
        var_dump($res);
        if (!class_exists('BaseCommon')) {
            import("@.ToolClass.B2b.BaseCommon");
        }
        foreach ($res as $value) {
            $api_currency = BaseCommon::api_currency(strtoupper($value['PAY_CURRENCY']), $value['ORDER_PAY_TIME']);
            if ($api_currency) {
                $save['PAY_TOTAL_PRICE_DOLLAR'] = $value['PAY_TOTAL_PRICE'] * $api_currency;
                $where_id['ID'] = $value['ID'];
                $res_all[] = $Model->table('tb_op_order')
                    ->where($where_id)
                    ->save($save);
            } else {
                $save['PAY_TOTAL_PRICE_DOLLAR'] = NULL;
                $where_id['ID'] = $value['ID'];
                $res_all[] = $Model->table('tb_op_order')
                    ->where($where_id)
                    ->save($save);
                Logs($api_currency, 'api_currency' . $value['ORDER_ID']);
            }
        }
        if (count($res_all) > count($res)) {
            $Model->rollback();
        } else {
            $Model->commit();
        }
        var_dump($res_all);
    }

    /**
     * @param $xlsData
     * @param $xls_cell_keys
     * @param $xlsDataNew
     * @param $xlsDataNews
     *
     * @return mixed
     */
    private function yieldCellData($key, $value, $xls_cell_keys)
    {
        foreach ($xls_cell_keys as $temp_value) {
            yield  $value[$temp_value];
        }
    }

    /**
     * @param $xlsData
     * @param $xls_cell_keys
     * @param $xlsDataNews
     *
     * @return array
     */
    private function yeildXlsData($xlsData, $xls_cell_keys)
    {
        foreach ($xlsData as $key => $value) {
            yield $this->yieldCellData($key, $value, $xls_cell_keys);
        }

    }

    /**
     * @param $post_data
     * @param $Excel
     * @param $xlsDataNew
     * @param $xlsDataNews
     *
     * @return array
     */
    private function getCsvTitle($post_data, $Excel, $xlsDataNew, $xlsDataNews)
    {
        $offset = 0;
        $length = 1;
        list($xlsName, $xlsCell, $xlsData) = $Excel->exportOrder(
            $post_data,
            $post_data['type'],
            null,
            $offset,
            $length
        );
        unset($xlsData);
        $xls_cell_values = array_column($xlsCell, 1);
        $string = ExcelModel::getExportCsvString([$xls_cell_values]);
        return [$xlsName, $string];
    }

    private function getCsvCount($post_data, $Excel)
    {
        $offset = 0;
        $sum = $length = 200000;
        $string = '';
        $to_special_arr = [];
        // key from 0 [F,AB,AC,AH]
        $to_str_arr = [5, 6, 7, 8, 9, 10, 27, 28, 33, 53];
        // key from 0 [AI-AP]
        // $to_special_arr = [34,35,36,37,38,39,40,41,42];
        do {
            list($xlsName, $xlsCell, $xlsData) = $Excel->exportOrder(
                $post_data,
                $post_data['type'],
                null,
                $offset,
                $length
            );
            $sum = count($xlsData);
            $xls_cell_keys = array_column($xlsCell, 0);
            unset($xlsName, $xlsCell, $post_data);
            foreach (DataModel::toYield($xlsData) as $key => $value) {
                foreach (DataModel::toYield($xls_cell_keys) as $temp_key => $temp_value) {
                    $xlsDataNew[] = $value[$temp_value];
                }
                $xlsDataNews[] = $xlsDataNew;
                unset($xlsDataNew);
            }
            unset($xlsData);
            $string .= ExcelModel::getExportCsvString($xlsDataNews, $to_str_arr, $to_special_arr);
            $offset += $length;
        } while ($sum == $length);
        return $string;
    }

    public function getSite()
    {
        $plat_cds = DataModel::getDataNoBlankToArr('plat_cd');
        if (!is_array($plat_cds)) {
            $this->ajaxError();
        }
        $this->ajaxSuccess(CodeModel::getSiteCodeArr($plat_cds));
    }

    //oms订单详情拆组合商品
    public function splitGroupSkuSubmit()
    {
        $model = new Model();
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $rClineVal = RedisModel::lock('order_id' . $request_data['order_info']['order_id'], 10);

            if ($request_data) {
                $this->validateSplitGroupSkuData($request_data);
            } else {
                throw new Exception(L('请求为空'));
            }
            if (!$rClineVal) {
                throw new Exception(L('获取流水锁失败'));
            }
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $model->startTrans();
            (new OmsService($model))->splitGroupSku($request_data);
            $msg = '组合拆商品:' . implode(',', array_column($request_data['goods_info'], 'sku_id'));
            OrderLogModel::addLog($request_data['order_info']['order_id'], $request_data['order_info']['plat_cd'], $msg);
            $model->commit();
            RedisModel::unlock('order_id' . $request_data['order_info']['order_id']);
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateSplitGroupSkuData($data)
    {
        $goods_attributes = $rules = [];
        $rules['order_info.order_id'] = 'required';
        $rules['order_info.plat_cd'] = 'required|size:10';

        foreach ($data['goods_info'] as $key => $value) {
            $rules["goods_info.{$key}.sku_id"] = 'required';
            $rules["goods_info.{$key}.is_group_sku"] = 'required';
            $rules["goods_info.{$key}.id"] = 'required';

            $goods_attributes["goods_info.{$key}.sku_id"] = '平台sku';
            $goods_attributes["goods_info.{$key}.is_group_sku"] = '是否是组合商品';
            $goods_attributes["goods_info.{$key}.id"] = '商品id';
        }
        $custom_attributes = [
            'order_info.order_id' => '订单id',
            'order_info.plat_cd' => '平台',

        ];
        $custom_attributes = array_merge($goods_attributes, $custom_attributes);
        $this->validate($rules, $data, $custom_attributes);
    }

    //获取组合商品绑定关系
    public function groupSkuMap()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if (empty($request_data['sku_ids']) || !is_array($request_data['sku_ids'])) {
                throw new Exception(L('请求参数错误'));
            }
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $group_sku_map_arr = SkuModel::getGroupSkuMap($request_data['sku_ids']);
            $data = [];

            $service = new OmsAfterSaleService();
            array_map(function ($group) use (&$data, $service) {
                $condition['product_sku.sku_id'] = $group['sku_id'];
                $product_info = SkuModel::getProductInfo($condition)[0];
                $group['upc_id'] = $product_info['upc_id'];
                $group['thumbnail'] = $product_info['thumbnail'];
                $group['spu_name'] = $product_info['spu_name'];
                $group['product_attr'] = $product_info['product_attr'];
                $data[$group['cb_sku_id']][] = $group;
            }, $group_sku_map_arr);

            $res['data'] = $data;
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function checkGroupSkuMap()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res = DataModel::$success_return;
            $res['code'] = 200;
            (new OmsService())->checkGroupSkuMap($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    /**
     *  拆单与取消拆单提醒验证
     */
    public function examine()
    {
        $res_data['is_hint'] = true;
        $request_data = DataModel::getDataNoBlankToArr();
        if (!isset($request_data['third_party_order_id']) || empty($request_data['third_party_order_id'])) {
            $this->ajaxReturn('', '参数有误', 0);
        }
        $where = [
            'tb_op_order.ORDER_ID' => $request_data['third_party_order_id']
        ];
        $orderModel = M('order', 'tb_op_');
        $data = $orderModel->field('	tb_op_order.ORDER_NO,tb_op_order.ORDER_ID,tb_ms_logistics_mode.LOGISTICS_CODE,tb_ms_ord_package.TRACKING_NUMBER')
            ->join('LEFT JOIN tb_ms_logistics_mode ON tb_op_order.logistic_model_id = tb_ms_logistics_mode.ID')
            ->join('LEFT JOIN tb_ms_ord_package ON tb_op_order.ORDER_ID = tb_ms_ord_package.ORD_ID')
            ->where($where)
            ->find();


        if (empty($data)) {
            $this->ajaxReturn('', '数据有误', 0);
        }
        if (!isset($data['LOGISTICS_CODE']) || empty($data['LOGISTICS_CODE'])) {
            $this->ajaxReturn('', 'LOGISTICS_CODE 不存在', 0);
        }
        // 自提除外
        if ($data['LOGISTICS_CODE'] == 'N000709900') {
            $res_data['is_hint'] = false;
            $this->ajaxReturn($res_data);
        }
        // 运单号为空
        if (empty($data['TRACKING_NUMBER'])) {
            $res_data['is_hint'] = false;
            $this->ajaxReturn($res_data);
        }
        $this->ajaxReturn($res_data);
    }

    // 申请售后
    public function applyAfterSales()
    {
        $this->display();
    }

    /**销售报表**/
    public function exportSalesReport()
    {
        session_write_close();
        $post_data = json_decode($_POST['post_data'], true);
        $res = DataModel::$error_return;
        #改造为异步任务
        $ret = (new OmsService())->addSalesReportTask($post_data['data']);
        if($ret){
            $res = DataModel::$success_return;
        }
        $this->ajaxReturn($res);
        // $list = (new OmsService())->getSalesReportData($post_data['data']);

        // $xlsCell = [
        //     ['ORDER_PAY_TIME', '日期'],
        //     ['PLAT_CD_val', '交易平台'],
        //     ['STORE_NAME', '店铺名称'],
        //     ['ORDER_NO', '订单号'],
        //     ['warehouse_cd_val', '下发仓库'],
        //     ['bwc_order_status_val', '订单状态'],
        //     ['en_spu_name', '商品名称'],
        //     ['total_goods_num', '数量'],
        //     ['plat_sku_id_str', '平台产品编码'],
        //     ['cn_spu_name', '产品中文名称'],
        //     ['cost_price', '成本USD'],
        //     ['PAY_TOTAL_PRICE_DOLLAR', '结算金额USD'],
        //     ['pre_amount_freight', '头程费USD'],
        //     ['amount_freight', '尾程费USD'],
        //     ['carry_tariff', '关税USD'],
        //     ['insurance_fee', '保险费USD'],
        //     ['vat_fee', 'VAT税USD'],
        //     ['league_fee', '广告推广费USD'],
        //     ['plat_fee', '平台月租费USD'],
        //     ['collection_fee', '收款手续费USD'],
        //     ['other_fee', '其他费用USD'],
        //     ['platform_discount_price', '平台补贴USD'],
        // ];

        // $xlsName = "销售报表";
        // $width = ['size' => '25'];
        // $number_fields = [
        //     'cost_price', 'PAY_TOTAL_PRICE_DOLLAR', 'pre_amount_freight',
        //     'amount_freight', 'carry_tariff', 'insurance_fee', 'vat_fee',
        //     'league_fee', 'plat_fee', 'collection_fee', 'other_fee', 'platform_discount_price'
        // ];
        // $this->exportExcel($xlsName, $xlsCell, $list, $width, $number_fields);
    }


    /**
     * 导出订单发票任务 将需要导出的订单放到导出数据任务表中
     * @author Redbo He
     * @date 2020/9/18 10:51
     */
    public function exportOrderInvoiceTask()
    {
        $post_data = DataModel::getData();
        ## 数据校验
        $model = D("B5cInvoiceTask");
        $ids   = isset($post_data['ids']) ? $post_data['ids'] : [];
        $types = isset($post_data['types']) ? $post_data['types'] : [];
        // 手动做数据校验
        if (empty($ids)) {
            return $this->ajaxError([], "导出订单ID不能为空");
        }
        if (empty($types)) {
            return $this->ajaxError([], "请选择需要导出的数据格式");
        }
        // 合理性校验数据
        $orderListService = new OrderListService();
        $res = $orderListService->saveOrderInvoiceTask($ids, $types);
        
    
        if ($res && !$res['status'])
        {
            return $this->ajaxError([],$res['msg']);
        }
        else
        {
            return $this->ajaxSuccess($res['data'],$res['msg']);
        }

    }

    /**
     * 批量更新待派单信息
     * @author Redbo He
     * @date 2020/11/26 13:49
     */
    public function batchUpdateOrderOperateRemark()
    {
        $post_data = DataModel::getData();
        if(empty($post_data) || !is_array($post_data)) {
            return $this->ajaxError([],'请求参数不能为空');
        }
        # 批量数据格式校验
        $require_fields = ['id', 'remark_msg'];
        $error = '';
        foreach ($post_data as $item) {

            if (!$this->isMayOperationOrders( array( 'ID'=>$item['id']) )){
                $error = "代销售订单或者shopnc平台订单禁止操作";
            }

            foreach ($require_fields as $require_field) {
                if(!isset($item[$require_field])) {
                    $error = "请求参数确实必要参数 ". $require_field. "，请检查";
                    break;
                }
            }
            if($error) {
                break;
            }
        }
        if($error)
        {
            return $this->ajaxError([], $error);
        }
        # 从新组装批量更新数据
        $update_data = []; # tb_op_order
        $date = new Date();
        foreach ($post_data as $item) {
            $update_data[] = [
                'id'             => $item['id'],
                'REMARK_STAT_CD' => 1,
                'REMARK_MSG'     => $item['remark_msg'],
                'UPDATE_TIME'    => $date->format(),
            ];
        }
        # 生成更新sql 并执行数据
        $sql = BatchUpdate::getBatchUpdateSql('tb_op_order', $update_data,'id');
        $model = M();
        $res = $model->execute($sql);
        if($res)
        {
            $this->ajaxSuccess([],'success');
        }
        else
        {
            return $this->ajaxError([],'程序运行失败,请稍后再试');
        }
    }

    public function export_template_list(){
        $this->display();
    }
}
