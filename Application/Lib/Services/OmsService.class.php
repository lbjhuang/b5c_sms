<?php
class OmsService extends Service
{

    public $user_name;
    public $model;
    public $area_table;
    public $order_table;
    public $omsAfterSaleService;

    private $mapping_where_arr = [
        "platform" => "tb_op_order.PLAT_CD",
        "mark_status" => "tb_op_order.is_mark",
        "order_status" => "tb_op_order.BWC_ORDER_STATUS",
        "order_source_status" => "tb_op_order.order_origin",
        // "dispatch_status" => "tb_op_order.SEND_ORD_STATUS", //
        "do_dispatch_status" => "tb_op_order.SEND_ORD_TYPE_CD", //
        "find_order_error_type" => "tb_op_order.FIND_ORDER_ERROR_TYPE",
        "surfaceWayGetCd" => "tb_op_order.surface_way_get_cd",
        "logisticsSingleStatuCd" => "tb_op_order.LOGISTICS_SINGLE_STATU_CD",
        // "logistics_status" => "",// 物流状态
        // "aftermarket" => "", // 售后状态
        // "sort" => "",
        // "remark_have" => "REMARK_STAT_CD",
        "country" => "tb_op_order.ADDRESS_USER_COUNTRY_ID",
        "shop" => "tb_op_order.STORE_ID",
        "warehouse" => "tb_op_order.WAREHOUSE",
        "logistics_company" => "logistic_cd",
        "logistics_method" => "tb_op_order.logistic_model_id",

        "is_remote_area" => "tb_op_order.is_remote_area",

        "is_remote_area_val" => "tb_op_order.is_remote_area",

        "search_time_type" => [
            'order_time'    => 'tb_op_order.ORDER_TIME',
            'pay_time'      => 'tb_op_order.ORDER_PAY_TIME',
            'send_time'     => 'tb_op_order.SHIPPING_TIME', // SEND_ORD_TIME
            'send_ord_time' => 'tb_op_order.SEND_ORD_TIME',
            's_time4'       => 'tb_ms_ord.S_TIME4',
            'sendout_time'       => 'tb_ms_ord.sendout_time',
        ],
        "search_condition" => [
            'order_id' => 'tb_op_order.B5C_ORDER_NO',
            'thr_order_id' => 'tb_op_order.ORDER_ID',
            'thr_order_no' => 'tb_op_order.ORDER_NO',
            'receiver_phone' => 'tb_op_order.ADDRESS_USER_PHONE',
            'pay_the_serial_number' => 'tb_op_order.PAY_TRANSACTION_ID',
            'receiver_tel' => 'tb_op_order.RECEIVER_TEL',
            'consignee_name' => 'tb_op_order.ADDRESS_USER_NAME',
            'tracking_number' => 'tb_ms_ord_package.TRACKING_NUMBER',
            'sku_title' => 'tb_ms_guds.GUDS_NM',
            'sku_number' => 'tb_op_order_guds.B5C_SKU_ID',
            'zip_code' => 'tb_op_order.ADDRESS_USER_POST_CODE',
            'receiver_email' => 'tb_op_order.USER_EMAIL',
            'pay_method' => 'tb_op_order.PAY_METHOD'
        ]
    ];




    public function __construct($model = "")
    {
        $this->user_name = DataModel::userNamePinyin();
        if ($model) {
            $this->model = $model;
        } else {
            $this->model = new Model();
        }
        $this->area_table  = M('op_area_configuration', 'tb_');
        $this->order_table = M('op_order', 'tb_');
        $this->omsAfterSaleService = new OmsAfterSaleService(new \Model());
    }

    /**
     * 新增/更新偏远地区
     * @param $request_data
     * @throws Exception
     */
    public function saveRemoteAreaConfig($request_data)
    {
        $result = $this->isUniqueRemoteAreaConfig($request_data);
        if (200 != $result['code']) {
            throw new \Exception(L($result['msg']));
        }
        $this->model->startTrans();
        if ($request_data['config_id']) {
            $save_data = [
                'id'                 => $request_data['config_id'],
                'country_id'         => $request_data['country_id'],
                'description'        => $request_data['description'],
                'prefix_postal_code' => $request_data['prefix_postal_code'],
                'logistics_company'  => $request_data['logistics_company'],
                'logistics_mode'     => $request_data['logistics_mode'],
                'updated_by'         => $this->user_name,
            ];
            if (false === $this->area_table->save($save_data)) {
                Logs(json_encode($save_data), __FUNCTION__ . ' fail', __CLASS__);
                $this->model->rollback();
                throw new \Exception(L('编辑偏远地区配置失败'));
            }
        } else {
            $add_data = [];
            foreach ($request_data as $value) {
                $add_data[] = [
                    'country_id'         => $value['country_id'],
                    'description'        => $value['description'],
                    'prefix_postal_code' => $value['prefix_postal_code'],
                    'logistics_company'  => $value['logistics_company'],
                    'logistics_mode'     => $value['logistics_mode'],
                    'updated_by'         => $this->user_name,
                    'created_by'         => $this->user_name,
                ];
            }
            if (!$this->area_table->addAll($add_data)) {
                Logs(json_encode($add_data), __FUNCTION__ . ' fail', __CLASS__);
                $this->model->rollback();
                throw new \Exception(L('配置偏远地区失败'));
            }
        }
        $this->model->commit();
    }

    /**
     * 判断偏远地区是否唯一
     * @param $data
     * @return array
     */
    private function isUniqueRemoteAreaConfig($data)
    {
        if (isset($data['config_id'])) {
            $result = $this->area_table->where([
                'country_id'         => $data['country_id'],
                'prefix_postal_code' => $data['prefix_postal_code'],
                'logistics_company' => $data['logistics_company'],
                'logistics_mode'    => $data['logistics_mode'],
                'id'                 => ['neq', $data['config_id']]
            ])->find();

            if ($result) {
                return ['code' => 300, 'msg' => '该偏远地区已经存在配置'];
            }
        } else {
            foreach ($data as $value) {
                $result = $this->area_table->where([
                    'country_id'         => $value['country_id'],
                    'prefix_postal_code' => $value['prefix_postal_code'],
                    'logistics_company' => $value['logistics_company'],
                    'logistics_mode'    => $value['logistics_mode'],
                ])->find();
                if ($result) {
                    return ['code' => 300, 'msg' => '邮编为' . $value['prefix_postal_code'] . '的偏远地区已经存在配置'];
                }
            }
        }
        return ['code' => 200, 'msg' => 'success'];
    }

    public function getConfigInfo($request_data)
    {
        if (empty($request_data['config_id'])) {
            throw new \Exception(L('参数错误'));
        }
        return $this->area_table->find($request_data['config_id']);
    }

    /**
     * 偏远地区配置列表
     * @param $request_data
     * @param bool $is_excel
     * @return array
     */
    public function remoteAreaConfigList($request_data, $is_excel = false)
    {
        $search_map  = [
            'country_id'         => 'country_id',
            'prefix_postal_code' => 'prefix_postal_code',
        ];
        $search_type = ['country_id'];
        list($where, $limit) = WhereModel::joinSearchTemp($request_data, $search_map, "", $search_type);
        list($res_db, $pages) = $this->getRemoteAreaConfigList($where, $limit, $is_excel);
        return [
            'data'  => $res_db,
            'pages' => $pages
        ];
    }

    /**
     * 获取偏远地区配置列表
     * @param $where
     * @param $limit
     * @param $is_excel
     * @return array
     */
    public function getRemoteAreaConfigList($where, $limit, $is_excel)
    {
        $field      = "tb_op_area_configuration.*, tb_ms_user_area.zh_name AS country_name, tb_ms_cmn_cd.CD_VAL AS logistics_company_name, tb_ms_logistics_mode.LOGISTICS_MODE as logistics_mode_name";
        $query      = $this->area_table
            ->field($field)
            ->join('left join tb_ms_user_area on tb_op_area_configuration.country_id = tb_ms_user_area.id')
            ->join('left join tb_ms_logistics_mode on tb_op_area_configuration.logistics_mode = tb_ms_logistics_mode.id')
            ->join('left join tb_ms_cmn_cd on tb_op_area_configuration.logistics_company = tb_ms_cmn_cd.CD')
            ->where($where);
        $query_copy = clone $query;

        $pages['total']        = $query->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        if (false === $is_excel) {
            $query_copy->limit($limit[0], $limit[1]);
        }
        $db_res = $query_copy->order('updated_at desc')->select();
        return [$db_res, $pages];
    }

    /**
     * 删除偏远地区配置
     * @param $request_data
     * @throws Exception
     */
    public function deleteRemoteAreaConfig($request_data)
    {
        $this->model->startTrans();
        if (empty($request_data['config_id'])) {
            throw new \Exception(L('参数错误'));
        }
        $where     = ['id' => $request_data['config_id']];
        $save_data = [
            'deleted_by' => $this->user_name,
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->user_name,
        ];
        if (false === $this->area_table->where($where)->save($save_data)) {
            $this->model->rollback();
            throw new \Exception('记录删除人失败');
        }
        if (!$this->area_table->where($where)->delete()) {
            $this->model->rollback();
            throw new \Exception(L('删除偏远地区配置失败'));
        }
        $this->model->commit();
    }

    //oms订单详情拆组合商品
    public function splitGroupSku($request_data)
    {
        $order_id = $request_data['order_info']['order_id'];
        $plat_cd  = $request_data['order_info']['plat_cd'];
        $date     = date('Y-m-d H:i:s');

        $order_guds_model  = M('op_order_guds', 'tb_');
        $guds_origin_model = M('op_order_guds_cb_origin', 'tb_');

        // 已经派单禁止组合拆商品
        $order_where = array(
            'ORDER_ID' => $request_data['order_info']['order_id'],
            'PLAT_CD' =>  $request_data['order_info']['plat_cd'],
        );
        $order_data = M('order','tb_op_')->where($order_where)->field('B5C_ORDER_NO,ID,PARENT_ORDER_ID,CHILD_ORDER_ID')->find();
        if (!empty($order['B5C_ORDER_NO'])) {
            throw new Exception(L('订单已派单,禁止组合拆商品'));
        }
        // 已经拆单的母单禁止组合拆商品
        if ($order_data['CHILD_ORDER_ID']){
            throw new Exception(L('已经拆单的母单禁止组合拆商品'));
        }

        $this->checkGroupSkuMap($request_data['goods_info']);
        foreach ($request_data['goods_info'] as $value) {
            $where         = ['ORDER_ID' => $order_id, 'ID' => $value['id']];
            $guds_info     = $order_guds_model->where($where)->find();
            $temp_original = [
                'order_id'    => $order_id,
                'plat_cd'     => $plat_cd,
                'sku_id'      => $guds_info['SKU_ID'],
                'guds_type'   => $guds_info['guds_type'],
                'item_id'     => $guds_info['item_id'],
                'b5c_sku_id'  => $value['sku_id'],
                'paid_price'  => $guds_info['PAID_PRICE'],
                'item_price'  => $guds_info['ITEM_PRICE'],
                'item_count'  => $guds_info['ITEM_COUNT'],
                'create_user' => DataModel::userNamePinyin(),
                'update_user' => DataModel::userNamePinyin(),
            ];
            $origin_info   = $guds_origin_model->where(['order_id' => $order_id, 'plat_cd' => $plat_cd, 'b5c_sku_id' => $value['sku_id'], 'item_count' => $guds_info['ITEM_COUNT'],])->find();
            if (empty($origin_info)) {
                $cb_origin_id = $guds_origin_model->add($temp_original);
                if (!$cb_origin_id) {
                    throw new \Exception(L('拆分组合商品原始表失败'));
                }
            }

            Logs($guds_info, __FUNCTION__, __CLASS__);
            if (!$order_guds_model->where($where)->delete()) {
                throw new \Exception(L('删除组合商品信息失败'));
            }

            $group_sku_map_arr = SkuModel::getGroupSkuMap($value['sku_id']);
            $data              = [];
            foreach ($group_sku_map_arr as $key => $v) {
                $temp_data = [];
                $temp_data = $guds_info;
                $item_id   = "_" . sprintf('%04s', $key);
                $map       = [
                    'ORDER_ID'  => $guds_info['ORDER_ID'],
                    'PLAT_CD'   => $guds_info['PLAT_CD'],
                    'SKU_ID'    => $guds_info['SKU_ID'],
                    'guds_type' => $guds_info['guds_type'],
                    'item_id'   => $item_id,
                ];
                if ($order_guds_model->where($map)->getField('ID')) {
                    //有商品记录重复，标记为uuid
                    $item_id = uuid();
                }

                unset($temp_data['ID']);
                $temp_data['CREATE_TIME']           = $date;
                $temp_data['CRATE_AT']              = $date;
                $temp_data['UPDATE_AT']             = $date;
                $temp_data['B5C_SKU_ID']            = $v['sku_id'];
                $temp_data['CREATE_USER']           = DataModel::userNamePinyin();
                $temp_data['UPDATE_USER_LAST']      = DataModel::userNamePinyin();
                $temp_data['indicator_update_time'] = $date;
                $temp_data['ITEM_COUNT']            = bcmul($temp_data['ITEM_COUNT'], $v['number']);
                $temp_data['group_sku_id']          = $cb_origin_id ? $cb_origin_id : '';
                $temp_data['item_id']               = $item_id;
                $temp_data['ITEM_PRICE']            = null;
                $temp_data['PAID_PRICE']            = null;
                $data[]                             = $temp_data;
            }
            $order = A('Home/Orders');
            $data  = $order->customsInfoInsert($data, null, null, 'B5C_SKU_ID');
            if (!$order_guds_model->addAll($data)) {
                throw new \Exception(L('添加商品信息失败'));
            }
            Logs(json_encode($guds_info), __FUNCTION__ . 'oms原组合商品信息', __CLASS__);

            // 组合拆商品子单 商品 同步母单
            if ($order_data['PARENT_ORDER_ID']){
                // 修改母单商品信息,重新增加母单商品
                $condition  = array(
                    'ORDER_ID' => $order_data['PARENT_ORDER_ID'],
                    'PLAT_CD' => $plat_cd,
                    'B5C_SKU_ID' =>$guds_info ['B5C_SKU_ID'] ,
                );
                $update_data = array(
                    'ORDER_ID' => $order_data['PARENT_ORDER_ID']."_XG_SJ"
                );
                $res_one = $order_guds_model->where($condition)->save($update_data);
                foreach ( $data as &$itme){
                    $itme['ORDER_ID'] =  $order_data['PARENT_ORDER_ID'];
                }
                $res_two = $order_guds_model->addAll($data);
                if (!$res_one || !$res_two) {
                    throw new \Exception("添加母单商品信息失败  - {$res_one}  - {$res_two} ");
                }
                $msg = '组合拆商品:' . implode(',', array_column($request_data['goods_info'], 'sku_id'));
                OrderLogModel::addLog($order_data['PARENT_ORDER_ID'], $request_data['order_info']['plat_cd'], $msg);
            }
        }
        $order_extend_model = M('order_extend', 'tb_op_');
        $map = ['order_id'=>$order_id, 'plat_cd'=>$plat_cd];
        if ($order_extend_model->where($map)->count()) {
            $extend_save = ['comb_resolution' => 1];
            $res = $order_extend_model->where($map)->save($extend_save);
        } else {
            $extend_save = [
                'order_id'       => $order_id,
                'plat_cd'        => $plat_cd,
                'comb_resolution'=> 1
            ];
            $res = $order_extend_model->add($extend_save);
        }
        if (!$res) {
            Logs([$res,$extend_save,$map], __FUNCTION__ . '标记失败', 'fm');
        }
    }

    /**
     * 判断是否可拆组合商品
     * @param $data
     * @return bool
     * @throws Exception
     */
    public function checkGroupSkuMap($data)
    {
        foreach ($data as $v) {
            if (!$v['is_group_sku']) {
                throw new Exception(L('sku为：' . $v['sku_id'] . '的商品为非组合商品,无须拆商品'));
            }
            $group_sku_map_arr = SkuModel::getGroupSkuMap($v['sku_id']);
            if (empty($group_sku_map_arr)) {
                throw new \Exception(L('sku为：' . $v['sku_id'] . '的组合商品没有绑定子sku关系,不能拆'));
            }
        }
        return true;
    }

    /**
     * 批量主动刷新订单
     * @param $b5c_order_nos
     * @param $tag 日志区分
     */
    public function saveAllOrderByB5cOrderNo($b5c_order_nos, $tag = __FUNCTION__)
    {
        $orders = $this->order_table->field('ORDER_ID, PLAT_CD')->where(['B5C_ORDER_NO' => ['in', $b5c_order_nos]])->select();
        foreach ($orders as $value) {
            $order_info[] = [
                'opOrderId' => $value['ORDER_ID'],
                'platCd'    => $value['PLAT_CD'],
            ];
        }
        ApiModel::updateOrderFromEs($order_info, $tag);
    }

    public function updateOrderFromEs($data, $order_key = 'ORDER_ID', $plat_key = 'PLAT_CD', $switch = 'switch', $tag = __FUNCTION__)
    {
       
        foreach (DataModel::toYield($data) as $value) {
            $order_info[] = [
                'opOrderId' => $value[$order_key],
                'platCd' => $value[$plat_key],
            ];
        }
        if ('no_switch' == $switch) {
            return ApiModel::publicProcess($order_info,1);
        }
       
        return ApiModel::updateOrderFromEs($order_info, $tag);
    }

    public function getReplyOrder($data, $order_key = 'ORDER_ID', $plat_key = 'PLAT_CD')
    {
        return $this->checkOrder($data, $order_key, $plat_key);
    }

    public function checkOrder($data, $order_key = 'ORDER_ID', $plat_key = 'PLAT_CD',$tag = __FUNCTION__)
    {
        //OTTO回邮单仓库配置code校验 plat_cd N000837445
        $date = date('Y-m-d');
        $name = DataModel::userNamePinyin();
        $order_exists = $order_info = [];
        //OTTO售后单回邮单待处理 查询最新记录
        $order_keys = array_column($data, $order_key);
        $plat_keys = array_column($data, $plat_key);
        $order_tmp = M('op_order_return', 'tb_')->field('order_id, platform_cd, order_no')
            ->where(['status_code' => OmsAfterSaleService::STATUS_RETURN_WAIT_INVALID, 'reply_status_code' => OmsAfterSaleService::RETURN_ORDER_WAITING, 'order_id' => ['in', $order_keys], 'platform_cd' => ['in', $plat_keys]])
            ->group('order_id,platform_cd')->select();
        foreach ($order_tmp as $item) {
            $order_exists[$item['order_id'] . $item['platform_cd']] = $item;
        }
        foreach (DataModel::toYield($data) as $value) {
            if (isset($order_exists[$value[$order_key] . $value[$plat_key]])) {
                $order_info['error'][] = ['order_no' => $order_exists[$value[$order_key] . $value[$plat_key]]['order_no'], 'order_id' => $value[$order_key], 'plat_cd' => $value[$plat_key]];
                continue;
            }
            $orderApplyDetails = $this->omsAfterSaleService->getApplyDetail($value[$order_key], $value[$plat_key]);
            $over_return_num = min(array_column($orderApplyDetails, 'over_return_num'));
            if ($over_return_num < 1) {
                $order_info['error'][] = ['order_no' => $orderApplyDetails[0]['order_no'], 'order_id' => $value[$order_key], 'plat_cd' => $value[$plat_key]];
            } else {
                $order_info['success'][] = ['order_no' => $orderApplyDetails[0]['order_no'], 'order_id' => $value[$order_key], 'plat_cd' => $value[$plat_key]];
                $addAll[] = [
                    'after_sale_no' =>  date(Ymd) . TbWmsNmIncrementModel::generateNo('after-sale'),//退货售后单
                    'order_no' => $orderApplyDetails[0]['order_no'],
                    'order_id' => $value[$order_key],
                    'platform_cd' => $value[$plat_key],
                    'logistics_no' => '',
                    'status_code' => OmsAfterSaleService::STATUS_RETURN_WAIT_INVALID,
                    'reply_status_code' => OmsAfterSaleService::RETURN_ORDER_WAITING,
                    'logistics_way_code' => '',
                    'logistics_fee_currency_code' => 'N000590300',
                    'logistics_fee' => 0,
                    'service_fee_currency_code' => 'N000590300',
                    'service_fee' => 0,
                    'return_reason' => '回邮单',
                    'return_time' => $date,
                    'is_relevance_order' => '0',
                    'created_by' => $name,
                ];
            }
        }
        if (!empty($addAll) && !$return_id = M('op_order_return', 'tb_')->addAll($addAll)) {
            throw new \Exception(L('退货单申请失败'));
        }
        $order_info['add'] = $return_id;
        return $order_info;
    }

    public function getOrderParam($tag = __FUNCTION__)
    {
        //OTTO回邮单快递公司配置 N00382
        $otto_express = M()->table('tb_ms_cmn_cd')
            ->where(['CD' => ['like', '%N00382%'], 'USE_YN' => 'Y'])->field('CD,CD_VAL')->find();
        //OTTO回邮单仓库配置code校验 plat_cd N000837445
        $otto_warehouse = M()->table('tb_ms_cmn_cd')->field('CD, ETC')
            ->where(['CD' => ['like', '%N00379%'], 'USE_YN' => 'Y'])->find();
        //OTTO回邮单处理方式配置code校验
        $otto_service = M()->table('tb_ms_cmn_cd')->field('ETC ReturnServiceNo,ETC2 ItemNumber,ETC3 Quantity')
            ->where(['CD' => ['like', '%N00380%'], 'ETC4' => $otto_warehouse['CD'], 'USE_YN' => 'Y'])->select();
        //OTTO售后单回邮单待处理 第三方api并发限制访问频次调整为10条/2分钟 查询去重取最新记录
        $data = M('op_order_return', 'tb_')->field('order_id, platform_cd, order_no')
            ->where(['status_code' => OmsAfterSaleService::STATUS_RETURN_WAIT_INVALID, 'reply_status_code' => OmsAfterSaleService::RETURN_ORDER_WAITING])
            ->limit(10)->group('order_id,platform_cd')->order('id desc')->select();
        $date = date('Y-m-d');
        foreach (DataModel::toYield($data) as $value) {
            $orderApplyDetails = $this->omsAfterSaleService->getApplyDetail($value['order_id'], $value['platform_cd']);
            $order = $orderApplyDetails[0];
            $goods_info = [];
            foreach ($orderApplyDetails as $key => $item) {
                $goods_info[$key] = [
                   'upc_id' => $item['product_info']['upc_id'],
                   'sku_id' => $item['product_info']['sku_id'],
                   'warehouse_code' => $item['warehouse'],
                   'yet_return_num' => $item['over_return_num'],
                ];
                $goods_info[$key]['handle_type'] = json_encode($otto_service);
            }
            $order_data = [
                'return_info' => [
                    'base_info' => [
                        'logistics_no' => '',
                        'logistics_way_code' => '',
                        'logistics_fee_currency_code' => 'N000590300',
                        'logistics_fee' => 0,
                        'service_fee_currency_code' => 'N000590300',
                        'service_fee' => 0,
                        'return_reason' => '回邮单',
                        'return_time' => $date,
                        'is_relevance_order' => '0',
                    ],
                    'goods_info' => $goods_info,
                ],
                'customer_info' => [
                    'address_1' => $order['address'] . ' ' . $order['doorplate'],
                    'address_2' => $order['address2'],
                    'city_name' => $order['city'],
                    'country_name' => $order['country'],
                    'duty_paragraph' => '',
                    'email' => $order['email'],
                    'fax' => '',
                    'postal_code' => $order['postal_code'],
                    'province_id' => $order['province'],
                    'receiver_name' => $order['receiver_name'],
                    'receiver_phone' => $order['receiver_tel'],
                    'two_char' => $order['country_code'],
                ],
                'order_info' => [
                    'order_no' => $order['order_no'],
                    'order_id' => $value['order_id'],
                    'platform_cd' => $value['platform_cd']
                ],
                'warehouse_info' => $otto_warehouse['ETC'],
                'express_name' => $otto_express['CD_VAL'],
                'type' => 1,
            ];
            $order_info[] = $order_data;
        }
        return $order_info;
    }

    public function reOrderApply($data, $tag = __FUNCTION__)
    {
//        import('ORG.Util.String');
//        $uuid = String::uuid();
//        $res['data'] =  GuzzleModel::reOrderApply($data,$uuid);
        $res['data'] =  GuzzleModel::reOrderApplyNew($data);
        return $res;
    }

    public function getOrderInfos($data, $type = 0)
    {
        $order_id_name = 'thr_order_id';
        if (1 == $type) $order_id_name = 'order_id';
        $where_str = ' ( 1 != 1 ';
        foreach ($data as $value) {
            $where_str .= sprintf(" OR (op_order.ORDER_ID = '%s' AND op_order.PLAT_CD = '%s')", $value[$order_id_name], $value['plat_cd']);
        }
        $where_str .= ' ) ';
        $field  = 'op_order.ORDER_ID,op_order.PLAT_CD,op_order.ORDER_NO as order_no,op_order.USER_EMAIL as email,ord.WHOLE_STATUS_CD as whole_status_cd,
            op_order.SEND_ORD_STATUS as send_ord_status,op_order.ADDRESS_USER_NAME as receiver_name,op_order.ADDRESS_USER_PHONE as receiver_phone,
            op_order.ADDRESS_USER_COUNTRY as country,op_order.ADDRESS_USER_COUNTRY_CODE as country_code,op_order.ADDRESS_USER_CITY as city,
            op_order.ADDRESS_USER_PROVINCES as province,op_order.ADDRESS_USER_ADDRESS1 as address,op_order.ADDRESS_USER_ADDRESS2 as address2,
            op_order.ADDRESS_USER_POST_CODE as postal_code,op_order.RECEIVER_TEL as receiver_tel,guds.guds_type,
            guds.B5C_SKU_ID as sku_id,SUM(guds.item_count) AS order_goods_num,e.doorplate';
        $db_res = $this->model->table('tb_op_order op_order')
            ->field($field)
            ->join('left join tb_op_order_guds guds on op_order.ORDER_ID = guds.ORDER_ID AND op_order.PLAT_CD = guds.PLAT_CD')
            ->join('left join tb_ms_ord ord on op_order.ORDER_ID = ord.THIRD_ORDER_ID  AND op_order.PLAT_CD = ord.PLAT_FORM')
            ->join('left join tb_op_order_extend e on op_order.ORDER_ID = e.order_id and op_order.PLAT_CD = e.plat_cd')
            ->where($where_str, null, true)
            ->order('guds.guds_type asc')
            ->group('op_order.ORDER_ID,op_order.PLAT_CD,guds.B5C_SKU_ID')
            ->select();
        return $db_res;
    }

    //获取op_order信息
    function getOpOrders($data, $type = 0) {
        $order_id_name = 'thr_order_id';
        if (1 == $type) $order_id_name = 'order_id';
        $where_str = ' ( 1 != 1 ';
        foreach ($data as $value) {
            $where_str .= sprintf(" OR (tb_op_order.ORDER_ID = '%s' AND tb_op_order.PLAT_CD = '%s')", $value[$order_id_name], $value['plat_cd']);
        }
        $where_str .= ' ) ';
        $Model = M();
        $order_data_db = $Model->table('tb_op_order')->where($where_str, null, true)->select();
        return $order_data_db;
    }

    //批量主动刷新订单
    public function saveOrderByB5cOrderNo($data, $type = 0, $tag = __FUNCTION__)
    {
        import('ORG.Util.String');
        $op_orders = $this->getOpOrders($data, $type);
        $uuid = String::uuid();
        Logs('saveOrderStatusApiAction:' . $tag, $uuid);
        //B5C订单ID
        $b5cOrderNos = array_column($op_orders, 'B5C_ORDER_NO');
        (new OmsService())->saveAllOrderByB5cOrderNo($b5cOrderNos, __FUNCTION__);
        Logs('saveOrderStatusApiEnd:' . $tag, $uuid);
    }

    //任务数据
    public function monitor()
    {
        $Db = new SlaveModel();
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
        return $data;
    }

    public function getSalesReportData($request_data, $num_length = 5000)
    {
        $model = M('op_order', 'tb_');
        list($where, $where_temp) = $this->buildWhere($request_data);

        $model = $model
            ->field([
                'tb_op_order.ORDER_PAY_TIME',//付款日期
                'tb_op_order.ORDER_TIME',//下单日期
                'tb_op_order.PLAT_CD',
                'tb_ms_store.STORE_NAME',
                'tb_op_order.ORDER_NO',
                'GROUP_CONCAT(tb_op_order_guds.SKU_ID) as plat_sku_id_str',//平台产品编码
                'GROUP_CONCAT(tb_op_order_guds.B5C_SKU_ID) as b5c_sku_id_str',
                'SUM(tb_op_order_guds.ITEM_COUNT) as total_goods_num',//数量
                'SUM(tb_op_order_guds.cost_usd_price) as total_cost_usd_price',//总成本usd
                'tb_op_order.PAY_TOTAL_PRICE_DOLLAR',//结算金额USD
                'tb_op_order.pre_freight_currency',//头程运费币种
                'tb_op_order.pre_amount_freight',//头程运费试算
                'tb_op_order.freight_currency',//尾程运费币种
                'tb_op_order.amount_freight',//尾程运费试算
                'tb_op_order.carry_tariff_currency',//尾程物流派送关税币种
                'tb_op_order.carry_tariff',//尾程物流派送关税/关税
                'tb_op_order.insurance_currency',//保险币种
                'tb_op_order.insurance_fee',//保险费
                'tb_op_order.vat_fee_currency',//VAT币种
                'tb_op_order.vat_fee',//VAT费用
                'tb_op_order.league_fee_currency',//流量活动费用币种
                'tb_op_order.league_fee',//流量活动费用/广告推广费
                'tb_op_order.PAY_CURRENCY',//订单交易币种
                'tb_op_order_extend.platform_discount_price',//平台优惠/平台补贴
                'tb_op_order_guds.group_sku_id', // 组合SKU
                'tb_op_order.BWC_ORDER_STATUS as bwc_order_status', // 订单状态
                'tb_op_order.WAREHOUSE as warehouse_cd' // 下发仓库
            ])
            ->join('left join tb_ms_ord on tb_ms_ord.THIRD_ORDER_ID = tb_op_order.ORDER_ID AND tb_ms_ord.PLAT_FORM = tb_op_order.PLAT_CD ')
            ->join('left join tb_op_order_guds on tb_op_order_guds.ORDER_ID = tb_op_order.ORDER_ID AND tb_op_order_guds.PLAT_CD = tb_op_order.PLAT_CD')
            ->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID')
            ->join('left join tb_ms_ord_package on tb_op_order.ORDER_ID = tb_ms_ord_package.ORD_ID AND tb_op_order.PLAT_CD = tb_ms_ord_package.plat_cd')
            ->join('left join tb_ms_user_area on tb_ms_user_area.id  = tb_op_order.ADDRESS_USER_COUNTRY_ID')
            ->join('left join tb_op_order_extend on tb_op_order_extend.order_id = tb_op_order.ORDER_ID AND tb_op_order_extend.plat_cd = tb_op_order.PLAT_CD')
            ->where($where);
        if (!empty($where_temp)) {
            $model->where($where_temp, null, true);
        }
        $model->where('tb_op_order.PARENT_ORDER_ID IS NULL ',null, true);
        $model->where("tb_op_order.PLAT_CD NOT IN ( 'N000830100','N000831300' ) ",null, true);
        $model->group('tb_op_order.ID');
        $model ->limit(0,$num_length);
        $res_db = $model ->select();
        $res_db = CodeModel::autoCodeTwoVal($res_db, ['PLAT_CD','bwc_order_status','warehouse_cd']);
        $sku_ids = [];
        foreach ($res_db as &$item) {
            $com_sku = explode(',', $item['plat_sku_id_str']);
            $com_sku = array_unique($com_sku); // 去重
            if (count($com_sku) == 1) {
                // 组合商品
                if ($item['group_sku_id'] > 0) {
                    $sku_data = SkuModel::getGroupSkuMap($com_sku);
                    if (count($sku_data) == count(explode(',', $item['b5c_sku_id_str']))) {
                        $item['b5c_sku_id_str'] = $com_sku[0];
                        $item['total_goods_num'] = $item['total_goods_num'] / array_sum(array_column($sku_data, 'number'));
                    }
                }
                $item['plat_sku_id_str'] = $com_sku[0];
            }

            $sku_ids = array_merge($sku_ids, explode(',', $item['b5c_sku_id_str']));
            //没有尾程运费币种  默认 尾程运费试算 为零
            if (empty($item['freight_currency'])){
                $item['freight_currency'] = 'N000590300';
            }
        }
        $sku_ids = array_unique(array_filter($sku_ids));
        $pms_model = new PmsBaseModel();
        $product = $pms_model->table('product_sku sku')
            ->field('sku.sku_id, pd.spu_name, pd.language')
            ->join('left join product on product.spu_id = sku.spu_id')
            ->join('left join product_detail pd on pd.spu_id = product.spu_id')
            ->where(['sku.sku_id' => ['in', $sku_ids], 'pd.language'=>['in',['N000920100','N000920200']]])
            ->select();
        $product_map = [];
        foreach ($product as $v) {
            $product_map[$v['sku_id']][$v['language']] = $v['spu_name'];
        }

        $price_map = [
            'pre_amount_freight' => 'pre_freight_currency',
            'amount_freight'     => 'freight_currency',
            'carry_tariff'       => 'carry_tariff_currency',
            'insurance_fee'      => 'insurance_currency',
            'vat_fee'            => 'vat_fee_currency',
            'league_fee'         => 'league_fee_currency',
            'PAY_CURRENCY'       => 'platform_discount_price',
        ];
        foreach ($res_db as &$item) {
            $en_spu_name = $cn_spu_name = '';
            $sku_arr = explode(',', $item['b5c_sku_id_str']);
            foreach ($sku_arr as $v) {
                $en_spu_name .= $product_map[$v]['N000920200'] . ',';
                $cn_spu_name .= $product_map[$v]['N000920100'] . ',';
            }
            $en_spu_name = trim($en_spu_name, ',');
            $cn_spu_name = trim($cn_spu_name, ',');
            $item['en_spu_name'] = $en_spu_name;
            $item['cn_spu_name'] = $cn_spu_name;
            $item['cost_price'] = bcdiv($item['total_cost_usd_price'], $item['total_goods_num'], 2);
//            $item['plat_sku_id_str'] = "'". $item['plat_sku_id_str'];
            if (!empty($item['ORDER_PAY_TIME'])){
                $pay_date = date('Ymd', strtotime($item['ORDER_PAY_TIME']));
            }else{
                $item['ORDER_PAY_TIME'] =  $item['ORDER_TIME'];
                $pay_date = date('Ymd', strtotime($item['ORDER_TIME']));
            }
            foreach ($price_map as $price => $curr) {
                $currency = cdVal($item[$curr]) ? : $item[$curr];
                $currency = strtolower($currency);
                if ($currency == 'usd') {
                    $rate = 1;
                } else {
                    if (isset($currency_map[$currency][$pay_date])) {
                        $rate = $currency_map[$currency][$pay_date];
                    } else {
                        if (!$currency) {
                            continue;
                        }
                        $rate = exchangeRateConversion($currency, 'usd', $pay_date);
                        $currency_map[$currency][$pay_date] = $rate;
                    }
                }
                $item[$price] = bcmul($item[$price], $rate, 2);

            }
        }
        return $res_db;
    }
    #销售报表插入task
    public function addSalesReportTask($request_data)
    {
        $model = M('op_order', 'tb_');
        list($where, $where_temp) = $this->buildWhere($request_data);

        $model = $model
            ->field([
                'tb_op_order.ORDER_PAY_TIME', //付款日期
                'tb_op_order.ORDER_TIME', //下单日期
                'tb_op_order.PLAT_CD',
                'tb_ms_store.STORE_NAME',
                'tb_op_order.ORDER_NO',
                'GROUP_CONCAT(tb_op_order_guds.SKU_ID) as plat_sku_id_str', //平台产品编码
                'GROUP_CONCAT(tb_op_order_guds.B5C_SKU_ID) as b5c_sku_id_str',
                'SUM(tb_op_order_guds.ITEM_COUNT) as total_goods_num', //数量
                'SUM(tb_op_order_guds.cost_usd_price) as total_cost_usd_price', //总成本usd
                'tb_op_order.PAY_TOTAL_PRICE_DOLLAR', //结算金额USD
                'tb_op_order.pre_freight_currency', //头程运费币种
                'tb_op_order.pre_amount_freight', //头程运费试算
                'tb_op_order.freight_currency', //尾程运费币种
                'tb_op_order.amount_freight', //尾程运费试算
                'tb_op_order.carry_tariff_currency', //尾程物流派送关税币种
                'tb_op_order.carry_tariff', //尾程物流派送关税/关税
                'tb_op_order.insurance_currency', //保险币种
                'tb_op_order.insurance_fee', //保险费
                'tb_op_order.vat_fee_currency', //VAT币种
                'tb_op_order.vat_fee', //VAT费用
                'tb_op_order.league_fee_currency', //流量活动费用币种
                'tb_op_order.league_fee', //流量活动费用/广告推广费
                'tb_op_order.PAY_CURRENCY', //订单交易币种
                'tb_op_order_extend.platform_discount_price', //平台优惠/平台补贴
                'tb_op_order_guds.group_sku_id', // 组合SKU
                'tb_op_order_guds.ITEM_NAME',  // 平台名称
                'tb_op_order.BWC_ORDER_STATUS as bwc_order_status', // 订单状态
                'tb_op_order.WAREHOUSE as warehouse_cd', // 下发仓库
                'tb_ms_user_area.two_char'
            ])
            ->join('left join tb_ms_ord on tb_ms_ord.THIRD_ORDER_ID = tb_op_order.ORDER_ID AND tb_ms_ord.PLAT_FORM = tb_op_order.PLAT_CD ')
            ->join('left join tb_op_order_guds on tb_op_order_guds.ORDER_ID = tb_op_order.ORDER_ID AND tb_op_order_guds.PLAT_CD = tb_op_order.PLAT_CD')
            ->join('left join tb_ms_store on tb_op_order.STORE_ID = tb_ms_store.ID')
            ->join('left join tb_ms_ord_package on tb_op_order.ORDER_ID = tb_ms_ord_package.ORD_ID AND tb_op_order.PLAT_CD = tb_ms_ord_package.plat_cd')
            ->join('left join tb_ms_user_area on tb_ms_user_area.id  = tb_op_order.ADDRESS_USER_COUNTRY_ID')
            ->join('left join tb_op_order_extend on tb_op_order_extend.order_id = tb_op_order.ORDER_ID AND tb_op_order_extend.plat_cd = tb_op_order.PLAT_CD')
            ->where($where);
        if (!empty($where_temp)) {
            $model->where($where_temp, null, true);
        }
        $model->where('tb_op_order.PARENT_ORDER_ID IS NULL ', null, true);
        $model->where("tb_op_order.PLAT_CD NOT IN ( 'N000830100','N000831300' ) ", null, true);
        $model->group('tb_op_order.ID');
        $model->limit(0, 1);
        $res_db = $model->select();
        $sql = $model->_sql();
        #去除limit  条数限制在data项目中加
        $sql = substr($sql,0,strripos($sql,'limit'));
        $data = array(
            'file_name' => '销售报表'.date('_YmdHis').'.xlsx',
            'query' => $sql,
            // 'query_count' => $query_count,
            'type' => 3,
            'status' => 0,
            'created_by' => DataModel::userNamePinyin(),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_by' => DataModel::userNamePinyin(),
            'updated_at' => date('Y-m-d H:i:s'),
        );
        $dataRepository = new DataRepository();
        return $ret = $dataRepository->addOne($data);

       

    }
    public function buildWhere($where_arr)
    {
        $twoDimension = ['search_time_type', 'search_condition', 'search_value'];
        $mapping_arr_key = array_keys($this->mapping_where_arr);
        $status_arr = '';
        $where_insert = '';
        foreach ($where_arr as $key => $value) {
            if (!empty($key) && !empty($value)) {
                if (in_array($key, $mapping_arr_key) && !in_array($key, $twoDimension)) {
                    $where[$this->mapping_where_arr[$key]] = array('IN', (array)$value);
                }
                if ('sales_team' == $key) {
                    $where_insert_temp = '';
                    if (!empty($where_insert)) $where_insert .= 'AND';
                    foreach ($value as $temp_value){
                        if (!empty($where_insert_temp)) $where_insert_temp .= 'OR';
                        $where_insert_temp .= "  tb_ms_store.SALE_TEAM_CD LIKE '%{$temp_value}%' ";
                    }
                    $where_insert .= " ( {$where_insert_temp} ) ";
                }
                if ('dispatch_status' == $key) {
                    $status_arr = implode("','", $value);
                }
                if ('search_condition' == $key && !empty($where_arr['search_value'])) {
                    if (strpos($where_arr['search_value'], ',')) {
                        $where[$this->mapping_where_arr[$key][$value]] = array('IN', OrderEsModel::str2arrSearch($where_arr['search_value']));
                    } elseif ($value == 'zip_code') {
                        $where[$this->mapping_where_arr[$key][$value]] = trim($where_arr['search_value']);
                    } else {
                        $where[$this->mapping_where_arr[$key][$value]] = array('LIKE', '%' . trim($where_arr['search_value']) . '%');
                        if ($value == 'sku_title') {
                            $where[$this->mapping_where_arr['search_condition']['sku_number']] = array('IN', SkuModel::titleToSku($where_arr['search_value']));
                            unset($where['tb_ms_guds.GUDS_NM']);
                        }
                    }
                }
                if ('search_time_type' == $key && ($where_arr['search_time_left'] || $where_arr['search_time_right'])) {
                    $where[$this->mapping_where_arr[$key][$value]] = array(
                        array('egt', trim($where_arr['search_time_left'])),
                        array('elt', trim($where_arr['search_time_right'])),
                    );
                }
                if ('sku_is_null' == $key && $value == 1) {
                    if (!empty($where_insert)) $where_insert .= 'AND';
                    $where_insert .= " (  tb_op_order_guds.B5C_SKU_ID = '' OR tb_op_order_guds.B5C_SKU_ID IS NULL ) ";
                }

                if ('addressee_msg_null' == $key && $value == 1) {
                    if (!empty($where_insert)) $where_insert .= 'AND';
                    $where_insert .= " (  tb_op_order.ADDRESS_USER_COUNTRY_ID = '' OR tb_op_order.ADDRESS_USER_COUNTRY_ID IS NULL  OR  tb_op_order.ADDRESS_USER_ADDRESS1 = '' OR  tb_op_order.ADDRESS_USER_ADDRESS1 IS NULL) ";
                }

                if ('recommend_type' == $key) {
                    if (!empty($where_insert)) $where_insert .= 'AND';
                    $temp_string = '';
                    foreach ($value as $recommend_type_value) {
                        if ('recommend_system' == $recommend_type_value) {
                            $temp_string .= "OR (tb_op_order.FIND_ORDER_JSON != '' AND tb_op_order.has_default_warehouse = 0) ";
                        }
                        if ('recommend_user' == $recommend_type_value) {
                            $temp_string .= "OR tb_op_order.has_default_warehouse = 1 ";
                        }
                        if ('operation_assignment' == $recommend_type_value) {
                            $temp_string .= "OR tb_op_order.has_default_warehouse = 2 ";
                        }
                    }
                    $where_insert .= trim($temp_string, 'OR');
                }
                if ('patch_err' == $key) {
                    if (!empty($where_insert)) $where_insert .= 'AND';
                    $temp_string = '';
                    foreach ($value as $patch_err_value) {
                        if ('un_inventory' == $patch_err_value) {
                            $temp_string .= "OR tb_op_order.SEND_ORD_TYPE_CD = 'N002030700' ";
                        }
                        if ('un_warehouse_logistics' == $patch_err_value) {
                            $temp_string .= "OR (  tb_op_order.warehouse = ''  OR  tb_op_order.logistic_model_id = '' OR  tb_op_order.logistic_model_id IS NULL) ";
                        }
                        if ('un_single_number' == $patch_err_value) {
                            $temp_string .= "OR (tb_op_order.SURFACE_WAY_GET_CD = 'N002010100' AND tb_ms_ord_package.TRACKING_NUMBER IS NULL) ";

                        }
                    }
                    $where_insert .= trim($temp_string, 'OR');
                }
            }
        }
        if (!empty($status_arr)) {
            $status_arr = trim($status_arr, ',');
            $where_insert .= " ((tb_op_order.SEND_ORD_STATUS IN ('$status_arr') AND ( tb_ms_ord.WHOLE_STATUS_CD IS NULL  OR tb_ms_ord.WHOLE_STATUS_CD = '') ) OR (tb_ms_ord.WHOLE_STATUS_CD IN ('$status_arr') AND ( tb_op_order.ORDER_ID IS NOT NULL OR tb_op_order.ORDER_ID = '' ))) ";
        }
        if (!empty($where_arr['remark_have']) && $where_arr['remark_have'] == 1) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $where_insert .= " (  tb_op_order.REMARK_MSG != ''  OR  tb_op_order.SHIPPING_MSG != '') ";
        }
        if (!empty($where_arr['hasDefaultWarehouse']) && $where_arr['hasDefaultWarehouse'] == 1) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $where_insert .= " (  tb_op_order.has_default_warehouse = 1) ";
        }
        if (!empty($where_arr['thirdDeliverStatus']) && $where_arr['thirdDeliverStatus'] == 1) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $where_insert .= " (  tb_op_order.THIRD_DELIVER_STATUS = 1) ";
        }
        if (!empty($where_arr['after_sale_type'])) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $where_insert .= " (tb_op_order.after_sale_type = {$where_arr['after_sale_type']}) ";
        }
        if (isset($where_arr['is_apply_after_sale']) && "" !== $where_arr['is_apply_after_sale']) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            if ($where_arr['is_apply_after_sale']) {
                $where_insert .= " (tb_op_order.is_apply_after_sale = {$where_arr['is_apply_after_sale']}) ";
            } else {
                $where_insert .= " (tb_op_order.is_apply_after_sale IS NULL) ";
            }
        }
        if (isset($where_arr['third_deliver_status']) && "" !== $where_arr['third_deliver_status']) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $where_insert .= " (tb_op_order.THIRD_DELIVER_STATUS = {$where_arr['third_deliver_status']}) ";
        }
        if (!empty($where_arr['after_sale_status'])) {
            if (!empty($where_insert)) $where_insert .= 'AND';
            $after_sale_status_data = (new OmsAfterSaleService())->getStatusMap($where_arr['after_sale_status']);

            $after_sale_status = "('".implode("','",$after_sale_status_data). "')";
            $where_insert .= " ((tb_op_order_extend.reissue_status_cd IN {$after_sale_status}) OR
            (tb_op_order_extend.return_status_cd IN {$after_sale_status}) OR 
            (tb_op_order_extend.refund_status_cd IN {$after_sale_status})) ";
        }
        if (!empty($where_arr['is_count_kpi'])){
            $search_field = ['is_on_sale_snapshot', 'is_edited_snapshot', 'is_indicator_snapshot'];
            $tem = array();
            foreach ($where_arr['is_count_kpi'] as $v) {
                $tem [] = 'tb_op_order.'.$search_field[$v] .' = 0' ;
            }
            if (!empty($tem)){
                if (!empty($where_insert)) $where_insert .= 'AND';
                $where_insert .= " ((  ".implode('  OR  ',$tem)."  )) ";
            }
        }
        //var_dump($where_insert);die;
        return [$where, $where_insert];
    }

    // 批量设置订单发货延期时间
    public function batchChangeDelayDate($data)
    {

        $op_order_extend_model = M('order_extend', 'tb_op_');
        foreach ($data as $key => $value) {
            $result['code'] = '2000';
            $result['msg'] = "success";
            $result['platCd'] = $value['plat_cd'];
            $result['orderId'] = $value['order_id']; 
            if (!$value['order_id'] || !$value['plat_cd'] || !$value['delay_delivery_time']) {
                $result['msg'] = "订单【{$value['plat_cd']}--{$value['order_id']}】参数缺失，延迟发货时间为{$value['delay_delivery_time']}";
                $result['code'] = '4100';
                $res['info'][] = $result['msg'];
            } // 检查订单是否已存在延迟发货时间，如果有值，则无需重复设置
            $where_map = ['order_id' => $value['order_id'], 'plat_cd' => $value['plat_cd']];
            $delay_delivery_time = $op_order_extend_model->where($where_map)->getField('delay_delivery_time');

            if ($delay_delivery_time) {
                $result['msg'] = "订单【{$value['order_id']}】延迟发货时间已存在，无需重复设置";
                $result['code'] = '4000';
                $res['info'][] = $result['msg'];
            }
            if ($result['code'] === '2000') {
                // 更新时间
                $update_res = $op_order_extend_model->where($where_map)->save(['delay_delivery_time' => $value['delay_delivery_time']]);
                if (false === $update_res) {
                    $result['msg'] = "设置失败，订单【{$value['plat_cd']}--{$value['order_id']}】，延迟发货时间为{$value['delay_delivery_time']}";
                    $result['code'] = '4200';
                    $res['info'][] = $result['msg'];
                }
            }
            
            $res['data'][] = $result;
            unset($result);
            unset($where_map);
            unset($delay_delivery_time);
        }
        $res['info'] = $res['info'] ? $res['info'] : 'success';
        $res['status'] = 200000;
        return $res;

    }

    public function get_error_msg_classify($value, $gp_plat_cds)
    {
        $msg = '物流信息缺失，请先补充保存运单号信息';
        $sql = "SELECT
            t1.ORDER_ID,
            t1.PLAT_CD,
            t1.ORDER_ID AS orderId,
            t1.PLAT_CD AS platCd,
            t2.APPKES,
            t2.DELIVERY_STATUS,
            t2.STORE_NAME,
            t1.WAREHOUSE,
            t1.logistic_cd,
            t1.logistic_model_id,
            t3.TRACKING_NUMBER,
            t1.THIRD_DELIVER_STATUS,

            CONCAT( t1.ORDER_ID, t1.PLAT_CD ) AS order_key 
        FROM
            tb_op_order AS t1,
            tb_ms_store AS t2,
            tb_ms_ord_package AS t3 
        WHERE
            (
                t1.STORE_ID = t2.ID 
                AND ( t1.PLAT_CD = t3.plat_cd AND t1.ORDER_ID = t3.ORD_ID ) 
                AND ( 1 != 1 OR ( t1.ORDER_ID = '{$value['order_id']}' AND t1.PLAT_CD = '{$value['plat_cd']}' ) ) 
                #AND length( t2.APPKES ) > 5 #店埔第三方授权信息
                #AND t2.DELIVERY_STATUS = 1 #发货是否对接第三方平台;0-未对接;1-对接;
                #AND ( t1.WAREHOUSE IS NOT NULL AND t1.WAREHOUSE != '' )  #该订单的仓库
                #AND ( t1.logistic_cd IS NOT NULL AND t1.logistic_cd != '' )  #物流公司code
                #AND ( t1.logistic_model_id IS NOT NULL AND t1.logistic_model_id != '' ) #物流方式ID
                #AND ( ( t3.TRACKING_NUMBER IS NOT NULL AND t3.TRACKING_NUMBER != '' ) OR t2.STORE_NAME IN ( 'Qoo10-JP', 'Qoo10-SG' ) )  #物流运单号为空
                #AND ( t1.THIRD_DELIVER_STATUS != 1 OR ( t1.THIRD_DELIVER_STATUS = 1 AND t1.PLAT_CD IN ({$gp_plat_cds}  ) ) ) #订单号第三方平台发货状态(1:成功, 0: 否,2待/进行中,3失败) 
            )";
        $error_order_data = M()->query($sql);
        if (!empty($error_order_data)) {
            $msg = $this->error_msg_classify($error_order_data, $gp_plat_cds);
        }
        return $msg;
    }

    public function error_msg_classify($error_order_data, $gp_plat_cds)
    {
        $msg = '';
        $error_order_data = $error_order_data[0];
        if (strlen($error_order_data['APPKES']) <= 5) {
            $msg .= '|请补充完整店埔第三方授权信息';
        }
        if ($error_order_data['DELIVERY_STATUS'] != 1) {
            $msg .= '|店埔发货未对接第三方平台';
        }
        if (!$error_order_data['WAREHOUSE']) {
            $msg .= '|该订单的仓库信息为空';
        }
        if (!$error_order_data['logistic_cd']) {
            $msg .= '|该订单物流公司code为空';
        }
        if (!$error_order_data['logistic_model_id']) {
            $msg .= '|该订单物流方式ID为空';
        }
        if (!$error_order_data['TRACKING_NUMBER'] && $error_order_data['STORE_NAME'] != 'Qoo10-JP' && $error_order_data['STORE_NAME'] != 'Qoo10-SG') {
            $msg .= '|该订单物流运单号为空';
        }
        if ($error_order_data['THIRD_DELIVER_STATUS'] == 1 && !in_array($error_order_data['PLAT_CD'], $gp_plat_cds)) {
            $msg .= '|已进行标记发货';
        }
        return $msg;
    }
}