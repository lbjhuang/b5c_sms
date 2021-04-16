<?php

/**
 * 提供接口给GP&第三方调用
 * Created by PhpStorm.
 * User: mark.zhong
 * Date: 2020/2/11
 * Time: 17:10
 */
class ApiAction extends BaseApiAction
{

    public function _initialize()
    {
        parent::_initialize();
    }

    public function applyRefund ()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data) {
                $this->validateRefundApplySubmitData($request_data);
            } else {
                throw new Exception('请求为空');
            }

            $rClineVal    = RedisModel::lock('order_no' . $request_data['order_no'], 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $extra_info = isset($request_data['extra_info']) ? $request_data['extra_info'] : [];
            $request_user= isset($extra_info['request_user']) ? $extra_info['request_user'] : '';
            if($request_user)
            {
                session('m_loginname', $request_user);
            }

            $res = DataModel::$success_return;
            $res['code'] = 200;
            $this->model->startTrans();
            $service = new OmsAfterSaleService();
            $after_sale_no = $service->refundApplySubmit($request_data, $service::SOURCE_TYPE_GP);
            $res['data']['after_sale_no'] = $after_sale_no;
            $this->model->commit();
            RedisModel::unlock('order_no' . $request_data['order_no']);
            if($request_user)
            {
                session('m_loginname', null);
            }
            Logs($request_data, __FUNCTION__, 'fm');
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = $this->catchException($exception);
            @SentinelModel::addAbnormal('gp退款', '失败', [$request_data, $res], 'gp_refund_notice');
        }
        $this->ajaxReturn($res);
    }

    private function validateRefundApplySubmitData($data)
    {
        $rules                    = [];
        $rules['order_id']        = 'required';
        $rules['order_no']        = 'required';
        $rules['platform_cd']     = 'required|size:10';
//        $rules['attachment']      = 'required';
        $rules['audit_status_cd'] = 'required|size:10';

        $custom_attributes = [
            'order_id'        => '订单id',
            'order_no'        => '订单号',
            'platform_cd'     => '平台code',
//            'attachment'      => '附件',
            'audit_status_cd' => '审核状态',
        ];
        foreach ($data['refund_info'] as $key => $value) {
            $rules["refund_info.{$key}.type"]               = 'required';
            $rules["refund_info.{$key}.order_pay_date"]     = 'required';
            $rules["refund_info.{$key}.current_date"]       = 'required';
            #$rules["refund_info.{$key}.refund_channel_cd"]  = 'required|size:10';
//            $rules["refund_info.{$key}.refund_user_name"]   = 'required';
//            $rules["refund_info.{$key}.refund_account"]     = 'required';
            $rules["refund_info.{$key}.refund_amount"]      = 'required|numeric|min:0';
            $rules["refund_info.{$key}.amount_currency_cd"] = 'required';
//            $rules["refund_info.{$key}.sales_team_cd"]      = 'required|size:10';
            $rules["refund_info.{$key}.refund_reason_cd"]   = 'required';
            $rules["refund_info.{$key}.created_by"]         = 'required';

            $custom_attributes["refund_info.{$key}.type"]               = '售后类型';
            $custom_attributes["refund_info.{$key}.order_pay_date"]     = '订单支付日期';
            $custom_attributes["refund_info.{$key}.current_date"]       = '当前日期';
            $custom_attributes["refund_info.{$key}.refund_channel_cd"]  = '赔付渠道';
//            $custom_attributes["refund_info.{$key}.refund_user_name"]   = '赔付对象';
//            $custom_attributes["refund_info.{$key}.refund_account"]     = '账号';
            $custom_attributes["refund_info.{$key}.refund_amount"]      = '支付金额';
            $custom_attributes["refund_info.{$key}.amount_currency_cd"] = '支付金额币种';
//            $custom_attributes["refund_info.{$key}.sales_team_cd"]      = '实际业务所属部门';
            $custom_attributes["refund_info.{$key}.refund_reason_cd"]   = '退款原因';
            $custom_attributes["refund_info.{$key}.created_by"]         = '下单用户名';
        }
        $this->validate($rules, $data, $custom_attributes);
    }

    public function haiguan179(){
        $data = $_POST;
        Logs("请求-".date("Y-m-d H:i:s")."----".json_encode($data),null,'haiguan179');
        $response = ApiModel::haiguan179Callback($data);
        Logs("响应-A".date("Y-m-d H:i:s")."----".$response,null,'haiguan179');
        header('Content-Type:application/json; charset=utf-8');
        exit($response);
    }

    public function getMsectime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;

    }
    public function haiku(){
        $data = file_get_contents("php://input", 'r');
        Logs("请求-".date("Y-m-d H:i:s")."----".$data,null,'haiku');
        $response = ApiModel::haiKuCallback($data);
        Logs("响应-".date("Y-m-d H:i:s")."----".$response,null,'haiku');
//        $response = "<response><success>true</success><errorcode></errorcode><errormsg></errormsg></response>";
        header("Content-type: text/xml");
        exit($response);
    }

    public function applySubmit()
    {
        $model = M();
        $old_user = session('m_loginname');
        try
        {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data) {
                $this->validateApplySubmitData($request_data);
            } else {
                throw new Exception('请求为空');
            }
            $rClineVal    = RedisModel::lock('order_no' . $request_data['order_no'], 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $extra_info = isset($request_data['extra_info']) ? $request_data['extra_info'] : [];
            $request_user= isset($extra_info['request_user']) ? $extra_info['request_user'] : '';

            if($request_user)
            {
                session('m_loginname', $request_user);
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $service = new OmsAfterSaleService($model);
            $model->startTrans();
            $result = $service->applySubmit($request_data);
            $res['data'] = $result;
            ## todo 修改 提交返回问题  便于操作下一步内容
            # $model->commit();
            ### TODO  根据参数 生成对应的出入库加库存
            $model->commit();
            #  自动入库
            if(isset($extra_info['is_auto_warehouse']) && $extra_info['is_auto_warehouse'] == 1)
            {
                $service->returnAutoWarehouse($result['return_id']);
            }
            ## session 清除
            if($request_user)
            {
                session('m_loginname', $old_user);
            }
            RedisModel::unlock('order_no' . $request_data['order_no']);
        }
        catch (Exception $exception)
        {
            if($old_user) {
                session('m_loginname', $old_user);
            }
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function validateApplySubmitData($data)
    {
        $goods_return_attributes      = $goods_reissue_attributes = $rules = [];
        $rules['order_info.order_no'] = 'required';
        $rules['order_info.order_id'] = 'required';
        if ($data['return_info']) {
            $rules["return_info.base_info.logistics_no"]                = '';
            $rules["return_info.base_info.logistics_way_code"]          = 'required|string';
            $rules["return_info.base_info.logistics_fee_currency_code"] = 'required|string|size:10';
            $rules["return_info.base_info.logistics_fee"]               = 'required|numeric|min:0';
            $rules["return_info.base_info.service_fee_currency_code"]   = 'required|string|size:10';
            $rules["return_info.base_info.service_fee"]                 = 'required|numeric|min:0';
            $rules["return_info.base_info.return_reason"]               = 'required';
            $rules["return_info.goods_info"]                            = 'required|array';

            foreach ($data['return_info']['goods_info'] as $key => $value) {
                // $rules["return_info.goods_info.{$key}.upc_id"]                            = 'required';
                $rules["return_info.goods_info.{$key}.sku_id"]         = 'required';
                $rules["return_info.goods_info.{$key}.yet_return_num"] = 'required|integer|min:1';
                $rules["return_info.goods_info.{$key}.warehouse_code"] = 'required|string|size:10';
                //                $rules["return_info.goods_info.{$key}.order_goods_num"] = 'required|integer|min:1';

                // $goods_return_attributes["return_info.goods_info.{$key}.upc_id"]          = '商品条形码';
                $goods_return_attributes["return_info.goods_info.{$key}.sku_id"]         = '商品sku';
                $goods_return_attributes["return_info.goods_info.{$key}.yet_return_num"] = '退货件数';
                $goods_return_attributes["return_info.goods_info.{$key}.warehouse_code"] = '退货仓库';
            }
        }
        if ($data['reissue_info']) {
//            $rules["reissue_info.base_info.child_order_no"] = 'sometimes|required';
            $rules["reissue_info.base_info.receiver_name"]  = 'required';
            $rules["reissue_info.base_info.receiver_phone"] = 'required';
            $rules["reissue_info.base_info.country_id"]     = 'required';
            $rules["reissue_info.base_info.province_id"]    = 'required';
//            $rules["reissue_info.base_info.city_id"]        = 'required';
            $rules["reissue_info.base_info.address"]        = 'required';
            $rules["reissue_info.base_info.postal_code"]    = 'required';
            $rules["reissue_info.base_info.reissue_reason"] = 'required';
            // $rules["reissue_info.base_info.email"]          = 'required';
            $rules["reissue_info.goods_info"] = 'required|array';

            foreach ($data['reissue_info']['goods_info'] as $key => $value) {
                // $rules["reissue_info.goods_info.{$key}.upc_id"]                             = 'required';
                $rules["reissue_info.goods_info.{$key}.sku_id"]          = 'required';
                $rules["reissue_info.goods_info.{$key}.yet_reissue_num"] = 'required|integer|min:1';
                //                $rules["reissue_info.goods_info.{$key}.order_goods_num"]  = 'required|integer|min:1';

                // $goods_reissue_attributes["reissue_info.goods_info.{$key}.upc_id"]          = '商品条形码';
                $goods_reissue_attributes["reissue_info.goods_info.{$key}.sku_id"]          = '商品sku';
                $goods_reissue_attributes["reissue_info.goods_info.{$key}.yet_reissue_num"] = '补发件数';
            }
        }
        $custom_attributes = [
            'order_info.order_no'     => '订单号',
            'order_info.order_id'     => '订单id',
            'return_info.goods_info'  => '退货商品信息',
            'reissue_info.goods_info' => '补发商品信息',

            'return_info.base_info.logistics_no'                => '物流单号',
            'return_info.base_info.logistics_way_code'          => '物流方式',
            'return_info.base_info.logistics_fee_currency_code' => '物流费用币种',
            'return_info.base_info.logistics_fee'               => '物流费用',
            'return_info.base_info.service_fee_currency_code'   => '服务费币种',
            'return_info.base_info.service_fee'                 => '服务费',
            'return_info.base_info.return_reason'               => '售后原因',

            'reissue_info.base_info.receiver_name'  => '收件人姓名',
            'reissue_info.base_info.receiver_phone' => '收件人手机号',
            'reissue_info.base_info.country_id'     => '国家',
            'reissue_info.base_info.province_id'    => '省份',
            'reissue_info.base_info.city_id'        => '城市',
            'reissue_info.base_info.address'        => '详细地址',
            'reissue_info.base_info.postal_code'    => '邮编',
            'reissue_info.base_info.reissue_reason' => '售后原因',
            'reissue_info.base_info.email'          => '邮箱',
        ];
        $custom_attributes = array_merge($goods_return_attributes, $custom_attributes);
        $custom_attributes = array_merge($goods_reissue_attributes, $custom_attributes);
        $this->validate($rules, $data, $custom_attributes);
    }

    //退货入库提交
    public function returnWarehouseSubmit()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            Logs($request_data, __FUNCTION__, 'api');
            $this->validateReturnWarehouseData($request_data);
            $res         = DataModel::$success_return;
            $res['code'] = 200;
            $this->model->startTrans();
            $service = new OmsAfterSaleService();
            $service->returnWarehouseSubmit($request_data);
            $is_end = $service->tagEnd($request_data['return_id']);
            $service->updateWarehouseStatus($request_data['return_id'], $request_data['return_goods_id'], $is_end);
            $this->model->commit();
        } catch (Exception $exception) {
            $this->model->rollback();
            $res = $this->catchException($exception);
            @SentinelModel::addAbnormal('第三方退货入库', '失败', [$request_data, $res], 'gp_refund_notice');
        }
        $this->ajaxReturn($res);
    }

    public function validateReturnWarehouseData($data)
    {
        if ($data['warehouse_num'] <= 0 && $data['warehouse_num_broken'] <= 0) {
            throw new \Exception(L('正品数和残次品数必须有一个大于0'));
        }
        $rules             = [
            'return_id'            => 'required|integer',
            'return_goods_id'      => 'required|integer',
            'sku_id'               => 'required',
            'warehouse_num'        => 'required|integer|min:0',
            'warehouse_num_broken' => 'sometimes|integer|min:0',
        ];
        $custom_attributes = [
            'return_id'            => '退货单id',
            'return_goods_id'      => '退货单商品信息id',
            'sku_id'               => '商品sku',
            'warehouse_num'        => '正品数量',
            'warehouse_num_broken' => '残次品数量',
        ];
        $this->validate($rules, $data, $custom_attributes);
    }
}