<?php

/**
 * User: yangsu
 * Date: 18/3/5
 * Time: 14:45
 */
include_once "BasisModel.class.php";

/**
 * Class PatchModel
 */
class PatchModel extends BasisModel
{
    protected $autoCheckFields = false;

    //店铺编号：199 派单的库存占用，可占用的范围需要在现有的基础上增加一层过滤：库存归属公司必须=这个店铺的注册公司。
    //store 199 乐天1店的注册公司
    public static $store = [
        199
    ];
    #需要改变订单状态的id
    public static $CHANGE_BWC_ORDER_STATUS = [];
   
    /**
     * @var array
     */
    protected static $list_cd_arr = [
        'channel' => 'N00083',
        /*'dispatch_status' => 'N00182',
        'logistics_status' => 'N00127',
        'aftermarket_status' => 'N00108',*/
        'sort' => '',
        // 'country_status' => 'N00173',
        'shop_status' => '',
        'warehouse_status' => 'N00068',
        'logistics_company_status' => 'N00070', // 物流公司
        // 'shipping_methods_status' => 'N00173', // 物流方式
        'sales_team_status' => 'N00128',
        'waitpre_status' => 'N00205',
        'down_query_status' => '',
        'do_dispatch_status' => 'N00203',
        'surfaceWayGetStatus' => 'N00201',
        'logisticsSingleStatus' => 'N00208',
        'do_dispatch_type' => 'N00205',
        'recommend_type' => '',
        'patch_err' => '',
        'mark_status' => '',
        'order_status' => '',
        'site_cd' => 'N00262',

        'address_valid_conf' => 'N00343',

        'sell_small_team' => 'N00323',

    ];

    const GC_AUTO_GET_NO_KEY = 'auto-get-gc-face-order';//谷仓自动获取单号集合key
    const GC_AUTO_SEND_COUNT = 'gc-auto-send-count';//谷仓自动获取单号次数

    /**
     * @var array
     */
    protected static $lis_value_arr = [
        'sort' => [
            "order_time" => [
                "CD" => "order_time",
                "CD_VAL" => "按下单时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "pay_time" => [
                "CD" => "pay_time",
                "CD_VAL" => "按付款时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "send_time" => [
                "CD" => "send_time",
                "CD_VAL" => "按平台发货时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ]
        ],
        'pending_sort' => [
            "order_time" => [
                "CD" => "order_time",
                "CD_VAL" => "按下单时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "pay_time" => [
                "CD" => "pay_time",
                "CD_VAL" => "按付款时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "send_time" => [
                "CD" => "send_time",
                "CD_VAL" => "按平台发货时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "send_ord_time" => [
                "CD" => "send_ord_time",
                "CD_VAL" => "按派单时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ]
        ],
        'patch_sort' => [
            "order_time" => [
                "CD" => "order_time",
                "CD_VAL" => "按下单时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "pay_time" => [
                "CD" => "pay_time",
                "CD_VAL" => "按付款时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "send_time" => [
                "CD" => "send_time",
                "CD_VAL" => "按平台发货时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ]
        ],
        'down_query_status' => [
            "order_id" => [
                "CD" => "order_id",
                "CD_VAL" => "订单号",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "thr_order_id" => [
                "CD" => "thr_order_id",
                "CD_VAL" => "第三方订单ID",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "thr_order_no" => [
                "CD" => "thr_order_no",
                "CD_VAL" => "第三方订单号",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "receiver_phone" => [
                "CD" => "receiver_phone",
                "CD_VAL" => "收货人手机号",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "pay_the_serial_number" => [
                "CD" => "pay_the_serial_number",
                "CD_VAL" => "支付流水号",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "pay_method" => [
                "CD" => "pay_method",
                "CD_VAL" => "支付类型",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "receiver_tel" => [
                "CD" => "receiver_tel",
                "CD_VAL" => "收货人电话",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "consignee_name" => [
                "CD" => "consignee_name",
                "CD_VAL" => "收货人姓名",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "receiver_email" => [
                "CD" => "receiver_email",
                "CD_VAL" => "收货人邮箱",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "tracking_number" => [
                "CD" => "tracking_number",
                "CD_VAL" => "运单号",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "sku_title" => [
                "CD" => "sku_title",
                "CD_VAL" => "SKU 标题",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "sku_number" => [
                "CD" => "sku_number",
                "CD_VAL" => "SKU 编号",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "zip_code" => [
                "CD" => "zip_code",
                "CD_VAL" => "邮编",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
        ],
        'do_patch_type' => [
            "normal" => [
                "CD" => "normal",
                "CD_VAL" => "正常",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "incomplete_information" => [
                "CD" => "incomplete_information",
                "CD_VAL" => "信息不全",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "no_delivery_warehouse" => [
                "CD" => "no_delivery_warehouse",
                "CD_VAL" => "无下发仓库",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "no_hair_logistics" => [
                "CD" => "no_hair_logistics",
                "CD_VAL" => "无下发物流",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "no_shipping_template" => [
                "CD" => "no_shipping_template",
                "CD_VAL" => "无运费模板",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "sheet_purchase_failed" => [
                "CD" => "sheet_purchase_failed",
                "CD_VAL" => "面单获取失败",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "inventory_shortage" => [
                "CD" => "inventory_shortage",
                "CD_VAL" => "库存不足",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
        ],
        'recommend_type' => [
            "recommend_system" => [
                "CD" => "recommend_system",
                "CD_VAL" => "系统推荐",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "recommend_user" => [
                "CD" => "recommend_user",
                "CD_VAL" => "用户推荐",
                "SORT_NO" => "1",
                "ETc" => ""
            ],
            "operation_assignment" => [
                "CD" => "operation_assignment",
                "CD_VAL" => "运营指派",
                "SORT_NO" => "2",
                "ETc" => ""
            ],
        ],
        'patch_err' => [
            "un_inventory" => [
                "CD" => "un_inventory",
                "CD_VAL" => "库存不足",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "un_warehouse_logistics" => [
                "CD" => "un_warehouse_logistics",
                "CD_VAL" => "无仓库物流",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "un_single_number" => [
                "CD" => "un_single_number",
                "CD_VAL" => "未获取运单号",
                "SORT_NO" => "0",
                "ETc" => ""
            ]
        ],
        'mark_status' => [
            "0" => [
                "CD" => "0",
                "CD_VAL" => "未标记订单",
                "SORT_NO" => "1",
                "ETc" => ""
            ],
            "1" => [
                "CD" => "1",
                "CD_VAL" => "已标记订单",
                "SORT_NO" => "0",
                "ETc" => ""
            ]
        ],
        'order_status' => [
            "N000550400" => [
                "CD" => "N000550400",
                "CD_VAL" => "待发货",
                "SORT_NO" => "0",
                "ETc" => "0"
            ],
            "N000550500" => [
                "CD" => "N000550500",
                "CD_VAL" => "待收货",
                "SORT_NO" => "0",
                "ETc" => "0"
            ],
            "N000550600" => [
                "CD" => "N000550600",
                "CD_VAL" => "已收货",
                "SORT_NO" => "0",
                "ETc" => "0"
            ],
            "N000550800" => [
                "CD" => "N000550800",
                "CD_VAL" => "交易成功",
                "SORT_NO" => "0",
                "ETc" => "0"
            ],
        ]
    ];

    /**
     * @param $value
     * @param $Model
     *
     * @return array
     */
    private static function savePackage($data, $Model, $LogService, $op_order_map)
    {
        $date = dateTime();
        ### 待优化部分
        $key_map = [
            "code" => [
                "logistic_cd",
                "BWC_ORDER_STATUS",
            ],
            "logistics" => [],
            "country" => [],
        ];
        $code_map = $LogService->getDataCodeMap($op_order_map, $key_map);
        $data_ord_package_counts = self::savePackageDataCounts($data);
        $data_ord_package_records = self::savePackageDataRecord($data);
        $logistic_cd_keys = [];
        foreach ($data as $item) {
            $logistic_cd = $op_order_map[$item['id']]['logistic_cd'];
            $logistic_cd_keys[] = 'CMN_CD_' .  $logistic_cd;
        };
        $logistic_cd_keys = array_unique($logistic_cd_keys);
        $client = RedisModel::client();
        $logistic_cd_json = $client->pipeline(function($pipe ) use ($logistic_cd_keys) {
            foreach ($logistic_cd_keys as $key) {
                $pipe->get($key);
            }
        });
        $logistic_cd_json = array_combine($logistic_cd_keys,$logistic_cd_json);

        $expe_codes = [];
        foreach ($logistic_cd_json as $key =>  $item) {
            $expe_code = null;
            if($item)
            {
                $expe_code = json_decode($item,true)['cdVal'];
            }
            $expe_codes[$key]= $expe_code;
        }
        foreach ($data as $key=>$value) {
            $where_package = [];
            $where_package['ORD_ID']  = $value['thr_order_id'];
            $where_package['plat_cd'] = $value['plat_cd'];
            $index = $value['thr_order_id'] . '_'. $value['plat_cd'];
            $search_res = isset($data_ord_package_counts[$index]) ?  $data_ord_package_counts[$index] : 0;
            $tmpStatus = $op_order_map[$value['id']]['BWC_ORDER_STATUS'];
            //判断是否有运单号+订单状态为待发货 有  则改为 处理中  只处理GP订单
            if (in_array($value['thr_order_id'], self::$CHANGE_BWC_ORDER_STATUS)) {
                //第一次上传运单号  订单状态修改为处理中
                $tmpStatus = 'N000551004';
            }
            $logistic_cd = $op_order_map[$value['id']]['logistic_cd'];
            # $expe_code = json_decode(RedisModel::get_key('CMN_CD_' . $logistic_cd), true)['cdVal'];
            $expe_code = isset($expe_codes[$logistic_cd]) ? $expe_codes[$logistic_cd] : null;
            if ($search_res) {
                $temp_package['ORD_ID']          = $value['thr_order_id'];
                $temp_package['plat_cd']         = $value['plat_cd'];
                $temp_package['TRACKING_NUMBER'] = trim($value['waybill_number']);
                $temp_package['SYS_CHG_DTTM']    = $date;
                $temp_package['EXPE_COMPANY']    = $logistic_cd;
                $temp_package['EXPE_CODE']       = $expe_code;
                $save_package[] = $temp_package;
            } else {
                
                $add_package_temp['ORD_ID']       = $value['thr_order_id'];
                $add_package_temp['plat_cd']      = $value['plat_cd'];
                $add_package_temp['REFERENCE_NO'] = $add_package_temp['TRACKING_NUMBER'] = trim($value['waybill_number']);
                $add_package_temp['SYS_REG_DTTM'] = $date;
                $add_package_temp['SYS_CHG_DTTM'] = $date;
                $add_package_temp['updated_at']   = $date;
                $add_package_temp['EXPE_COMPANY'] = $logistic_cd;
                $add_package_temp['EXPE_CODE']    = $expe_code;
                $add_package[] = $add_package_temp;
            }
            $other = [
                'TRACKING_NUMBER' => trim($value['waybill_number']),
                'SYS_CHG_DTTM'    => $date,
                'EXPE_COMPANY'    => $logistic_cd,
                'EXPE_CODE'       =>  $expe_code,
            ];
            $old_data = isset($data_ord_package_records[$index]) ? $data_ord_package_records[$index]: [];
            $update_msg = $LogService->getUpdateMessage('tb_ms_ord_package', $where_package, $other, $code_map, $old_data);
            if (!empty($update_msg)) {
                $log_data[] = [
                    'ORD_NO'             => $value['thr_order_id'],
                    'ORD_HIST_SEQ'       => time(),
                    'ORD_STAT_CD'        => $tmpStatus,
                    'ORD_HIST_WRTR_EML'  => $_SESSION['m_loginname'],
                    'ORD_HIST_REG_DTTM'  => $date,
                    'ORD_HIST_HIST_CONT' => $update_msg,
                    'updated_time'       => $date,
                    'plat_cd'            => $value['plat_cd'],
                ];
            }
            $log_data_common[] = [
                'ORD_NO'             => $value['thr_order_id'],
                'ORD_HIST_SEQ'       => time(),
                'ORD_STAT_CD'        => $tmpStatus,
                'ORD_HIST_WRTR_EML'  => $_SESSION['m_loginname'],
                'ORD_HIST_REG_DTTM'  => $date,
                'ORD_HIST_HIST_CONT' => '物流信息编辑',
                'updated_time'       => $date,
                'plat_cd'            => $value['plat_cd'],
            ];
        }
        unset($code_map);
        $model = M('ord_package', 'tb_ms_');
        if (!empty($save_package)) {
            $sql = TempImportExcelModel::saveAllExtend($save_package, $model, $pk = 'ORD_ID', ['ORD_ID', 'plat_cd']);
            $res_update = $model->execute($sql);
            if (false === $res_update){
                @SentinelModel::addAbnormal('批量编辑提交', '更新运单号失败', [$res_update,$sql,$save_package],'oms_notice');
                throw new \Exception(L('更新运单号失败'));
            }
        }
        if (!empty($add_package)) {
            $res_add = $Model->table('tb_ms_ord_package')->addAll($add_package);
            if (!$res_add) {
                @SentinelModel::addAbnormal('批量编辑提交', '添加运单信息失败', [$res_add,$add_package],'oms_notice');
                throw new \Exception(L('添加运单信息失败'));
            }
        }
        if (!empty($log_data)) {
            M("ms_ord_hist", "sms_")->addAll($log_data);
        }
        M("ms_ord_hist", "sms_")->addAll($log_data_common);
        unset($log_data);
        unset($log_data_common);
        unset($add_package);
        unset($save_package);
        return true;
    }

    /**
     * @param $value
     * @param $Model
     * @param $where_order
     * @param $res_arr
     *
     * @return mixed
     */
    private static function saveOrder($data, $LogService, $op_order_map)
    {
        $date = dateTime();
        $key_map = [
            "code" => [
                "delivery_warehouse_code",
                "logistics_company_code",
                "electronic_single_code",
            ],
            "logistics" => [
                "shipping_methods_code",
            ],
            "country" => [],
        ];
//        $code_map = $LogService->getDataCodeMap($data, $key_map);
        $auto_get_no = self::getSavePackageDataExtend($data);
        $auto_get_no = array_keys($auto_get_no);
//        $data_ord_package_records = self::savePackageDataRecord($data);
        $code_map = $LogService->getCodeMap();
        $data_order_records = self::saveOrderDataRecord($data);
        foreach ($data as &$value) {
            $temp_order = [];
            $tmpStatus = $op_order_map[$value['id']]['BWC_ORDER_STATUS'];
            $temp_order['BWC_ORDER_STATUS'] = $tmpStatus;
            //判断是否有运单号+订单状态为待发货 有  则改为 处理中  只处理GP订单
            if (in_array($value['thr_order_id'], self::$CHANGE_BWC_ORDER_STATUS)) {
                //第一次上传运单号  订单状态修改为处理中
                $temp_order['BWC_ORDER_STATUS'] = 'N000551004';
                $tmpStatus = 'N000551004'; 
            }
            //修改下发仓库，取消自动获取单号标识
            if (in_array($value['thr_order_id'].'_'.$value['plat_cd'], $auto_get_no)) {
                $order_extend[] = [
                    'order_id' => $value['thr_order_id'],
                    'plat_cd' => $value['plat_cd'],
                    'is_auto_get_no' => 0,
                ];
            }
            //修改订单物流公司或物流方式清空地址校验 is_change_logistics 物流公司、物流方式是否改动 
            if ($value['is_change_logistics'] && (!empty($value['logistics_company_code']) || !empty($value['shipping_methods_code']))) {
                $order_extend[] = [
                    'order_id' => $value['thr_order_id'],
                    'plat_cd' => $value['plat_cd'],
                    'address_valid_res' => "",
                    'address_valid_status' => 0,
                ];
            }
            $where_order['ORDER_ID']  = $value['thr_order_id'];
            $where_order['PLAT_CD']   = $value['plat_cd'];

            $temp_order['ORDER_ID']          = $value['thr_order_id'];
            $temp_order['PLAT_CD']           = $value['plat_cd'];
            $temp_order['warehouse']         = $value['delivery_warehouse_code'];
            $temp_order['logistic_cd']       = $value['logistics_company_code'];
            $temp_order['logistic_model_id'] = $value['shipping_methods_code'];
            switch ($value['electronic_single_code']) {
                case '无需面单':
                    $value['electronic_single_code'] == 'N002010200';
                    break;
                case '一键获取':
                    $value['electronic_single_code'] == 'N002010100';
                    break;
            }
            $temp_order['SURFACE_WAY_GET_CD'] = $value['electronic_single_code'];
            $other = [
                'warehouse' => $value['delivery_warehouse_code'],
                'logistic_cd'    => $value['logistics_company_code'],
                'logistic_model_id'    => $value['shipping_methods_code'],
                'SURFACE_WAY_GET_CD'       =>  $value['electronic_single_code'],
            ];
            if ($value['electronic_single_code'] == 'N002010100' || $value['electronic_single_code'] == '一键获取') {
                $temp_order['LOGISTICS_SINGLE_STATU_CD'] = 'N002080100';
                $other['LOGISTICS_SINGLE_STATU_CD']        = 'N002080100';
            } else {
                $temp_order['LOGISTICS_SINGLE_STATU_CD'] = 'N002080400';
                $other['LOGISTICS_SINGLE_STATU_CD']        = 'N002080400';
            }
            $temp_order['SEND_FREIGHT']               = $value['patch_freight'];
            $temp_order['LOGISTICS_SINGLE_ERROR_MSG'] = null;
            $temp_order['updated_at']                 = $date;
            $save_order[] = $temp_order;
            $other['SEND_FREIGHT']               = $value['patch_freight'];
            $other['LOGISTICS_SINGLE_ERROR_MSG'] = null;
            $other['updated_at']                 = $date;
            $index = $value['thr_order_id'] . '_'. $value['plat_cd'];
            $old_data = isset($data_order_records[$index]) ? $data_order_records[$index]: [];
            $update_msg = $LogService->getUpdateMessage('tb_op_order', $where_order, $other, $code_map, $old_data);
            if (!empty($update_msg)) {
                $log_data[] = [
                    'ORD_NO'             => $value['thr_order_id'],
                    'ORD_HIST_SEQ'       => time(),
                    'ORD_STAT_CD'        => $tmpStatus,
                    'ORD_HIST_WRTR_EML'  => $_SESSION['m_loginname'],
                    'ORD_HIST_REG_DTTM'  => $date,
                    'ORD_HIST_HIST_CONT' => $update_msg,
                    'updated_time'       => $date,
                    'plat_cd'            => $value['plat_cd'],
                ];
            }
        }
        unset($code_map);
        if (!empty($save_order)) {
            $model = M('order', 'tb_op_');
            $sql = TempImportExcelModel::saveAllExtend($save_order, $model, $pk = 'ORDER_ID', ['ORDER_ID', 'PLAT_CD']);
            $res   = $model->execute($sql);
            if (!$res) {
                @SentinelModel::addAbnormal('批量编辑提交', '更新订单信息失败', [$res,$sql,$save_order],'oms_notice');
                throw new \Exception(L('更新订单信息失败'));
            }
            self::cancelAutoFlag($order_extend, '批量编辑');
        }
        if (!empty($log_data)) {
            $res = M("ms_ord_hist", "sms_")->addAll($log_data);
            if (!$res) {
                @SentinelModel::addAbnormal('批量编辑提交', '日志记录失败', [$res,$log_data],'oms_notice');
            }
            unset($log_data);
        }
        unset($save_order);
        return true;
    }

    /**
     * @todo data code mapping
     *
     * @param $data
     *
     * @return mixed
     */
    public function assemblyElectronicData($data)
    {
        foreach ($data['data']['orders'] as $value) {
            $res['third_order_id'] = $value['thr_order_id'];
            $res['child_order_id'] = $value['CHILD_ORDER_ID'];
            $res['plat_cd'] = $value['plat_cd'];
            $res['b5c_logistics_cd'] = $value['b5c_logistics_cd'];
            $res['shipper_id'] = 1;
            $res['service_code'] = $value['service_code'];
            $res['location'] = $value['b5c_logistics_cd'];
            $return_data['data']['orders'][] = $res;
        }
        return $return_data;
    }

    /**
     * @param bool $format
     * @param null $model
     * @param null $type
     *
     * @return mixed
     */
    public static function listMenu($format = false, $model = null, $type = null)
    {
        // $redis = RedisModel::client();
        $arr = self::$list_cd_arr;
        foreach ($arr as $key => $value) {
            if ('shop_status' == $key) {
                $data[$key] = self::getShop();
            } elseif ('site_cd' == $key) {
                $siteCodeArr = CodeModel::getCodeArr([$value]);
                $data[$key] = array_combine(array_column($siteCodeArr, 'CD'), array_values($siteCodeArr));
            } elseif ('patch' == $type && 'sort' == $key) {
                $data[$key] = self::toLang(self::$lis_value_arr['patch_sort'], 'CD_VAL');
            }  elseif ('pending' == $type && 'sort' == $key) {
                $data[$key] = self::toLang(self::$lis_value_arr['pending_sort'], 'CD_VAL');
            } elseif (!empty($value)) {
                $data[$key] = CodeModel::getCodeLang($value);
            } else {
                $data[$key] = self::toLang(self::$lis_value_arr[$key], 'CD_VAL');
            }
        }
        $data['country_status'] = BaseModel::getAreaCode();
        $data['shipping_methods_status'] = self::getLogisticsModel();
        $data['reply_status'] = CommonDataModel::replyStatus();
        if ($format) return self::formatCd($data);
        return $data;
    }

    public static function filterMenu($data)
    {
        if ($data['logisticsSingleStatus']['N002080200']) {
            unset($data['logisticsSingleStatus']['N002080200']);
        }
        return $data;
    }

    /**
     * 派单列表-获取个订单可售库存
     * @param $data
     * @return mixed
     */
    public static function getStock($data)
    {
        $res_arr = [];
//        $gp_codes = CodeModel::getGpPlatCds();
        $data = self::updateStoreSaleTeam($data);
        foreach ($data as $value) {
            foreach ($value['opOrderGuds'] as $k => $v) {
                if ($v['b5cSkuId'] && $value['sales_team_cd'] && $value['warehouse'] && 10 == strlen($v['b5cSkuId'])) {
                    $res['orderId'] = $v['orderId'];
                    $res['platCd'] = $v['platCd'];
                    $res['skuId'] = $v['b5cSkuId'];
                    $res['saleTeamCode'] = $value['sales_team_cd'];
                    $res['deliveryWarehouse'] = $value['warehouse'];
                    $res['storeId'] = $value['STORE_ID'];
                    $res['sellSmallTeamCd'] = $value['sell_small_team_cd'];
                    #9610-店铺专属库存需求-修改成不区分是否gp订单
//                        if (in_array($res['platCd'], $gp_codes)) {
//                            $res['type'] = 1;
//                        } else {
//                            $res['type'] = 0;
//                        }
                    $res_arr[] = $res;
                }
            }
        }

        $batchStockPostArr = self::getSaleStock($res_arr);//获取库存信息

        foreach ($data as &$value) {
            $is_sup_spilt = $no_adequate = 0;
            foreach ($value['opOrderGuds'] as $k => $v) {
                $temp['sku'] = $v['b5cSkuId'];
                $temp['need_num'] = $v['itemCount'];
                $temp['available_inventory'] = $batchStockPostArr[$v['orderId'].$v['platCd'].$v['b5cSkuId']] ? : 0;
                if ($temp['need_num'] > $temp['available_inventory']) {
                    $no_adequate += 1;
                }
                $value['stock'][] = $temp;
                $is_sup_spilt += $v['itemCount'];
            }
            $value['stock_adequate'] = ($no_adequate) ? 0 : 1;
//            if (!$batchStockPost) {
//                $value['stock_adequate'] = -1;
//            }
            $value['is_spilt'] = ($value['parentOrderId']) ? 1 : 0;
            $value['is_sup_spilt'] = 0;
            if ($is_sup_spilt > 1) $value['is_sup_spilt'] = 1;
//            if (!empty($value['logistics_type_name']) && empty($value['surfaceWayGetNm'])) {
//                $value = OrderPresentModel::toDoFaceListGet($value);
//            }
        }
        $data = OrderPresentModel::batchToDoFaceListGet($data);//批量更新面单获取方式
        return $data;
    }

    /**
     *#9471需求改成直接从数据库获取库存
     * @param $res_arr
     * @return array
     */
    public static  function getSaleStock($res_arr)
    {
        $batchStockPostArr = $db_stock_team = $db_stock_warehouse = [];
        if (!empty($res_arr)) {
            $sku_ids         = array_unique(array_column($res_arr, 'skuId'));
            $warehouse_ids   = array_unique(array_column($res_arr, 'deliveryWarehouse'));
            //店铺编号：199 派单的库存占用，可占用的范围需要在现有的基础上增加一层过滤：库存归属公司必须=这个店铺的注册公司。
            //store 199 乐天1店的注册公司
//            $store = M("ms_store", "tb_")->where(['ID' => ['in', self::$store]])->getField('ID,company_cd');
//            $sale_team_codes = [];
//            foreach ($res_arr as $item) {
//                $sale_team_codes = array_merge($sale_team_codes, explode(',', $item['saleTeamCode']));
//            }
//            array_push($sale_team_codes, 'N001281500');
//            $sale_team_codes = array_unique($sale_team_codes);
            $where = [
                't1.SKU_ID'         => ['in', $sku_ids],
                't11.warehouse_id'  => ['in', $warehouse_ids],
            ];
//            $sale_team_str = " '" . join("','", array_values($sale_team_codes) ) . "' ";
//            $where['_string'] = " (t1.sale_team_code in ({$sale_team_str}) OR (CASE WHEN t4.cat_level1 = 6 THEN datediff(NOW(), t1.create_time) >= 90 WHEN t4.cat_level1 <> 6 THEN datediff(NOW(), t1.create_time) >= 150 END))";
            $result = StockModel::getAvailableForSaleStock($where);
            foreach ($res_arr as $key => $item) {
                //排查同一订单里相同sku
                if (!isset($flag[$item['orderId'].$item['platCd'].$item['skuId']])) {
                    $flag[$item['orderId'].$item['platCd'].$item['skuId']] = true;
                } else {
                    unset($res_arr[$key]);
                }
            }
            foreach ($res_arr as $item) {
                foreach ($result as $value) {
                    if ($item['skuId'] != $value['skuId'] || $item['deliveryWarehouse'] != $value['deliveryWarehouse']) {
                        continue;
                    }
                    //店铺编号：199 派单的库存占用，可占用的范围需要在现有的基础上增加一层过滤：库存归属公司必须=这个店铺的注册公司。
                    //9925 关联交易需求-B2C订单触发部分,取消限制
//                    if (isset($store[$item['storeId']])) {
//                        if ($value['CON_COMPANY_CD'] != $store[$item['storeId']]) {
//                            continue;
//                        }
//                    }
//                    var_dump($value);
                    // 9949-库存归属转为小团队-配置&订单调整
                    $is_continue = true;   // 是否跳出循环  默认是 【数据过滤】
                    if ($value['saleTeamCode'] == 'N001281500' || $value['unsalable'] > 180) {
//                        var_dump('销售团队=公共团队）|| （是否滞销=是/否');
                        $is_continue = false;   // （销售团队=公共团队）|| （是否滞销=是/否）
                    }else{
                        // 是否多销售团队情况
                        if (count(explode(',',$item['saleTeamCode'])) > 1 ){
//                            var_dump('是否多销售团队情况');
                            if (strpos($item['saleTeamCode'], $value['saleTeamCode']) !== false) {

                                $is_continue = false;  // 销售团队=店铺配置的多个销售团队）

                            }
                        }else{
                            // 店铺是否配置销售小团队
                            if (!empty($item['sellSmallTeamCd'])){
                                if ($item['saleTeamCode'] == $value['saleTeamCode']){
                                    if (empty($value['sellSmallTeamCd'])){
                                        $is_continue = false;  //（销售团队=店铺销售团队）&（销售小团队=空）&
                                    }else{
                                        if ($item['sellSmallTeamCd'] == $value['sellSmallTeamCd']){
                                            $is_continue = false;   // （销售团队=店铺销售团队）&（销售小团队=店铺销售小团队）
                                            
                                        }
                                    }
                                }
                            }else{
                                if ($item['saleTeamCode'] == $value['saleTeamCode']){

                                    $is_continue = false;  // （销售团队=店铺销售团队）

                                }
                            }
                        }
                    }
                    if ($is_continue){
                        continue;
                    }
                    $batchStockPostArr[$item['orderId'].$item['platCd'].$item['skuId']] += $value['availableForSale'];
                }
            }
        }
        return $batchStockPostArr;
    }

    private static function updateStoreSaleTeam($data)
    {
        $store_ids = array_unique(array_column($data, 'STORE_ID'));
        $Model = new Model();
        if (empty($store_ids)) {
            $res_db = $Model->table('tb_ms_store')
                ->field(['ID', 'SALE_TEAM_CD','sell_small_team_cd'])
                ->select();
        }else{
            $where['ID'] = ['IN', $store_ids];
            $res_db = $Model->table('tb_ms_store')
                ->field(['ID', 'SALE_TEAM_CD','sell_small_team_cd'])
                ->where($where)
                ->select();
        }
        $sale_team_map = array_column($res_db, 'SALE_TEAM_CD', 'ID');
        $sell_small_team_map = array_column($res_db, 'sell_small_team_cd', 'ID');
        $data = array_map(function ($datum) use ($sale_team_map,$sell_small_team_map) {
            if ($sale_team_map[$datum['STORE_ID']]) {
                $datum['sales_team_cd'] = $sale_team_map[$datum['STORE_ID']];
            }
            if ($sell_small_team_map[$datum['STORE_ID']]) {
                $datum['sell_small_team_cd'] = $sell_small_team_map[$datum['STORE_ID']];
            }
            return $datum;
        }, $data);
        return $data;
    }

    /**
     * 单个订单派单信息库存
     *
     * @param $data
     *
     * @return mixed
     */
    public static function getDetailStock($data)
    {
        $res_arr = [];
//        $gp_codes = CodeModel::getGpPlatCds();
        foreach ($data['patch_data'] as $k => $v) {
            if ($v['sku_id'] && $data['sales_team_cd'] && $data['warehouse'] && 10 == strlen($v['sku_id'])) {
                $stock_param = [
                    'orderId'           => $data['third_party_order_id'],
                    'platCd'            => $data['platCd'],
                    'skuId'             => $v['sku_id'],
                    'saleTeamCode'      => $data['sales_team_cd'],
                    'deliveryWarehouse' => $data['warehouse'],
                    'storeId'           => $data['STORE_ID']
                ];
                $res_arr[] = $stock_param;
            }
        }
        //#9576 处理详情库存展示获取方式,改成直接读取库获取
        $batchStockPostArr = self::getSaleStock($res_arr);//获取库存信息
        foreach ($data['patch_data'] as $k => $v) {
            $temp['sku'] = $v['sku_id'];
            $temp['need_num'] = $v['the_number_issued'];
            $temp['available_inventory'] = $batchStockPostArr[$data['third_party_order_id'].$data['platCd'].$v['sku_id']] ? : 0;
            $data['patch_data'][$k]['stock_adequate'] = $temp['need_num'] > $temp['available_inventory'] ? 0 : 1;
        }
        return $data;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function filterFaceAlone($data)
    {
        foreach ($data as $value) {
            $value['order_plat_key'] = $where_arr[] = $value['order_id'] . $value['plat_cd'];
        }
        $Model = M();
        $where['order_plat_key'] = array('IN', $where_arr);
        if ($where) {
            $res = $Model->table('tb_op_face_alone')->field('order_plat_key')->where($where)->select();
            foreach ($data as &$value) {
                if ($res && in_array($value['order_plat_key'], $res)) {
                    unset($value);
                }
            }
        }
        return $data;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function saveFaceAlone($data)
    {

        foreach ($data as $v) {
            $save['orderId'] = $v['orderId'];
            $save['plat_cd'] = $v['plat_cd'];
            $save['logistics_company'] = $v['b5cLogisticsCd'];
            $save['templateType'] = $v['templateType'];
            $save['trackingNo'] = $v['trackingNo'];
            $save_arr[] = $save;
        }
        $M = M();
        $res = $M->table('tb_op_face_alone')->save($save_arr);
        return $res;
    }

    /**
     * @param $data
     * @param $uuid
     * @param $type
     *
     * @return mixed
     */
    public static function filterRecommend($data, $uuid, $type = false)
    {
        $data_arr['processCode'] = 'PROCESS_BEFORE_SEND_ORDER';
        $data_arr['processId'] = $uuid;
        foreach ($data as $value) {
            $temp['orderId'] = $value['thr_order_id'];
            $temp['platCd'] = $value['plat_cd'];
            if ($type) $temp['fromBatchEdit'] = true;
            if ($value['warehouse_cd']) {
                $data_key_arr[$value['thr_order_id'] . $value['plat_cd']] = $value['warehouse_cd'];
            }
            $data_arr['data'][] = $temp;
        }
        return [$data_arr, $data_key_arr];
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function logisticsUpdate($data, $Model,$store_ids = array())
    {

        $total_count = count($data);
        list($data, $error_arr_info) = OrderModel::inRecommend($data, $Model);
        Logs('memory:'.memory_get_usage()/1024/1024, __FUNCTION__, 'other');
        $LogService   = new LogService();
        $ids          = array_column($data, 'id');
        if (empty($ids)){
            throw new \Exception(L('校验失败，请求参数id缺失'));
        }
        $op_order_map = $Model->table('tb_op_order')->where(['ID' =>['in',$ids]])->getField('ID,logistic_cd,BWC_ORDER_STATUS');
        #根据code查找GP店铺code
        $shopCodeArr = array_column(CodeModel::getSiteCodeArr('N002620800'), 'CD');
        foreach($data as $value){
            if (!empty($value['waybill_number']) && in_array($value['plat_cd'], $shopCodeArr) && !$value['is_shopnc_order']) {
                //第一次上传运单号 订单状态修改为处理中
                array_push(self::$CHANGE_BWC_ORDER_STATUS, $value['thr_order_id']);
            }
        }
        self::savePackage($data, $Model, $LogService, $op_order_map);
        self::saveOrder($data, $LogService, $op_order_map);
        $Model->commit();
        $date = dateTime();
        # 待优化部分数据
        $systemSortModel = new SystemSortModel();
        $res= $systemSortModel->batchUpdateSystemSort($data);
        if($res)
        {
            foreach ($data as $value) {
               //  $setSystemSort = SystemSortModel::setSystemSort($value['thr_order_id'], $value['plat_cd']);
                $tmp_bwc_order_status = $op_order_map[$value['id']]['BWC_ORDER_STATUS'];
                //判断是否有运单号+订单状态为待发货 有  则改为 处理中  只处理GP订单
                if(in_array($value['thr_order_id'], self::$CHANGE_BWC_ORDER_STATUS)){
                    //第一次上传运单号  订单状态修改为处理中
                    $tmp_bwc_order_status = 'N000551004';
                }
                $log_data[] = [
                    'ORD_NO'             => $value['thr_order_id'],
                    'ORD_HIST_SEQ'       => time(),
                    'ORD_STAT_CD'        => $tmp_bwc_order_status,
                    'ORD_HIST_WRTR_EML'  => $_SESSION['m_loginname'],
                    'ORD_HIST_REG_DTTM'  => $date,
                    'ORD_HIST_HIST_CONT' => L('更新限制替换锁定成功'),
                    'updated_time'       => $date,
                    'plat_cd'            => $value['plat_cd'],
                ];

            }
            if (!empty($log_data)) {
                M("ms_ord_hist", "sms_")->addAll($log_data);
                unset($log_data);
            }
        }
        $res_arr['order'] = $total_count-count($error_arr_info) ;
        $res_arr['error'] = count($error_arr_info);
        $res_arr['error_arr_info'] = $error_arr_info;

        Logs('memory2:'.memory_get_usage()/1024/1024, __FUNCTION__, 'other');
        return $res_arr;
    }



    /**
     * @param $data
     * @param bool $self_logistic
     *
     * @return array
     */
    public function orderStatusUpd($data, $self_logistic = false)
    {
        Logs($data, '$data');
        foreach ($data as $value) {
            //带获取只有非自有物流才可获取&&获取失败可以获取
            $order_info = M('order', 'tb_op_')
                ->field('LOGISTICS_SINGLE_STATU_CD,warehouse,SURFACE_WAY_GET_CD,logistic_cd,logistic_model_id')
                ->where(['ORDER_ID' => $value['thr_order_id'], 'PLAT_CD' => $value['plat_cd']])
                ->find();
            if (empty($order_info) || 'N002010100' != $order_info['SURFACE_WAY_GET_CD']) {
                continue;
            }
            if (($order_info['LOGISTICS_SINGLE_STATU_CD'] == 'N002080100' && $this->isSelfLogistic($order_info) == $self_logistic) || $order_info['LOGISTICS_SINGLE_STATU_CD'] == 'N002080500') {
                $res[] = $this->orderStatusUpdDo($value);
            }
        }
        return $res;
    }

    public function isSelfLogistic($param)
    {
        //TODO 检查是否自有物流
        $res = M('logistics_own_config', 'tb_ms_')
            ->where([
                'warehouse_code' => $param['warehouse'],
                'logistics_company_code' => $param['logistic_cd'],
                'logistics_mode_id' => $param['logistic_model_id'],
                'is_own_logistics_warehouse' => 1
            ])
            ->getField('id');
        return $res ? true : false;
    }

    public function orderStatusUpdDo($data)
    {
        $Model = M();
        $where_pack['ORD_ID'] = $where['ORDER_ID'] = $data['thr_order_id'];
        $where_pack['plat_cd'] = $where['PLAT_CD'] = $data['plat_cd'];
        $save['LOGISTICS_SINGLE_STATU_CD'] = 'N002080200';
        $save['LOGISTICS_SINGLE_ERROR_MSG'] = null;
        $save['LOGISTICS_SINGLE_UP_TIME'] = date();
        $del_res[] = $Model->table('tb_ms_ord_package')->where($where_pack)->delete();
        $res = $Model->table('tb_op_order')->where($where)->save($save);
        OrderLogModel::addLog($data['thr_order_id'], $data['plat_cd'], '面单开始获取', $save);
        return $res;
    }

    /**
     * @param $mode_id
     *
     * @return null
     */
    public function faceOrderGet($mode_id)
    {
        $Model = M();
        $where['ID'] = $mode_id;
        $surface_str = $Model->table('tb_ms_logistics_mode')
            ->where($where)
            ->cache(true, 3)
            ->getField('SURFACE_WAY_GET_CD');
        if ($surface_str) {
            $surface_arr = (strstr($surface_str, ',')) ? explode(',', $surface_str) : $surface_str;
            $where_arr['CD'] = array('IN', $surface_arr);
            $res = $Model->table('tb_ms_cmn_cd')
                ->field('CD,CD_VAL')
                ->where($where_arr)
                ->cache(true, 3)
                ->select();
        }
        return ($res) ? $res : null;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function recommendJoin($data)
    {
        foreach ($data as $value) {
            if ($value['findOrderJson'] &&
                (empty($value['warehouse']) && empty($value['logistics_company']) && empty($value['logistics_type']) && empty($value['freight']))) {
                $find_order_arr = json_decode($value['FindOrderJson'])['data']['warehouse'];
                if ($find_order_arr) {
                    $warehouse_key = array_flip(array_column($find_order_arr, 'isShow'));
                    if ($find_order_arr[$warehouse_key[1]]) {
                        $lgt_model_key = array_flip(array_column($find_order_arr[$warehouse_key[1]]['lgtModel'], 'isShow'));
                        if ($find_order_arr[$warehouse_key[1]][$lgt_model_key[1]]) {
                            $logistics_method_key = array_flip(array_column($find_order_arr[$warehouse_key[1]][$lgt_model_key[1]]['logisticsMethod'], 'isShow'));
                            if ($find_order_arr[$warehouse_key[1]][$lgt_model_key[1]][$logistics_method_key[1]]) {
                                $save['warehouse'] = $find_order_arr[$warehouse_key[1]]['cd'];
                                $save['logistics_company'] = $find_order_arr[$warehouse_key[1]]['lgtModel'][$lgt_model_key[1]]['logisticsCode'];
                                $save['logistics_type'] = $find_order_arr[$warehouse_key[1]]['lgtModel'][$lgt_model_key[1]]['logisticsCode'];
                                $save['freight'] = $find_order_arr[$warehouse_key[1]]['lgtModel'][$lgt_model_key[1]]['logisticsCode'];
                            }

                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param $data
     */
    public static function patchRecommend($data)
    {
        $order_where = OrdersModel::initWhere();
        foreach ($data as $k => $v) {
            $order_where .= " OR (ORDER_ID = '" . $v['orderId'] . "' AND PLAT_CD = '" . $v['platCd'] . "')  ";
        }
        $Model = M();
        $field = 'ID,STORE_ID,ORDER_ID,PLAT_CD,FIND_ORDER_JSON';
        $order_db_arr = $Model->table('tb_op_order')
            ->field($field)
            ->where($order_where)
            ->select();
        if ($order_db_arr) {
            $all_store = array_column($order_db_arr, 'STORE_ID');
            Logs('joinPatchInfoAct', LogsModel::$uuid);
            $store_patch_arr = PatchInfoModel::patchRecommend(array_unique($all_store));
            Logs('joinPatchInfoEnd', LogsModel::$uuid);
            $isset_store = [];
            foreach ($order_db_arr as $val) {
                $res['code'] = 2000;
                $res['msg'] = 'success';
                $res['orderId'] = $val['ORDER_ID'];
                $res['platCd'] = $val['PLAT_CD'];
                if (isset($isset_store[$val['STORE_ID']])) {
                    //同一店铺推荐物流一样，去除重复，前端判断取值
                    $res['data'] = $val['STORE_ID'];
                } else {
                    $res['data'] = $store_patch_arr[$val['STORE_ID']];
                }
                $res['storeId'] = $val['STORE_ID'];
                // $res['data'] = json_decode($val['FIND_ORDER_JSON'], true);
                $res_data['data'][] = $res;
                $isset_store[$val['STORE_ID']] = true;
            }
            $res_data['msg'] = 'success';
            $res_data['code'] = 2000;
        } else {
            $res_data['msg'] = 'error';
            $res_data['code'] = 4000;
        }
        return $res_data;
    }

    public static function filterListsData($data)
    {
        $value = $data->data->logisticsSingleStatuCd;
        if ($value && $value != '[]' && in_array('N002080200', $value)) {
            $data->data->logisticsSingleStatuCd[] = 'N002080600';
        }
        return $data;
    }

    /**
     * 批量获取面单方式
     * @param $mode_ids 物流id集合
     * @return mixed
     */
    public static function batchFaceOrderGet($mode_ids)
    {
        $mode_ids = array_filter($mode_ids);
        if (empty($mode_ids)) {
            return [];
        }
        $Model = M();
        $where['ID'] = ['in',$mode_ids];
        $surface_map = $Model->table('tb_ms_logistics_mode')
            ->where($where)
            ->cache(true, 3)
            ->getField('ID,SURFACE_WAY_GET_CD');
        foreach ($surface_map as $key => $item) {
            $res[$key] = array_filter(explode(',', $item));
        }
        return $res;
    }

    public static function removeRecommendName($data)
    {
        foreach ($data as $order_key => $order_value) {
            if (2000 == $order_value['code']) {
                foreach ($order_value['data']['warehouse'] as $warehouse_key => $warehouse_value) {
                    unset($data[$order_key]['data']['warehouse'][$warehouse_key]['name']);
                    foreach ($warehouse_value['lgtModel'] as $lgtModel_key => $lgtModel_value) {
                        unset($data[$order_key]['data']['warehouse'][$warehouse_key]['lgtModel'][$lgtModel_key]['logisticsName']);
                        foreach ($lgtModel_value['logisticsMethod'] as $logisticsMethod_key => $logisticsMethod_value) {
                            unset($data[$order_key]['data']['warehouse'][$warehouse_key]['lgtModel'][$lgtModel_key]['logisticsMethod'][$logisticsMethod_key]['logisticsMode']);
                        }
                    }
                }
            }
        }
        return $data;
    }

    //谷仓获取单号失败后，主动触发获取单号
    public static function triggerGcFaceOrderGet()
    {
        $redis_client = RedisModel::client();
        $set_ids      = $redis_client->sunion(self::GC_AUTO_GET_NO_KEY);//集合中待自动获取的订单自增id
        if (empty($set_ids)) {
            return;
        }
        $order_model        = M('order', 'tb_op_');
        $order_extend_model = M('order_extend', 'tb_op_');
        $where = [
            'WAREHOUSE'                  => ['IN', ['N000686800', 'N000687900', 'N000688400', 'N000689100', 'N000689200', 'N000689458', 'N000689460', 'N000689465', 'N000689471']],
            'LOGISTICS_SINGLE_STATU_CD'  => 'N002080500',
            'LOGISTICS_SINGLE_ERROR_MSG' => ['LIKE', "%网络异常%"],
            'BWC_ORDER_STATUS'           => ['IN', ['N000550600', 'N000550400', 'N000550500', 'N000550800']],
            'SEND_ORD_STATUS'            => ['IN', ['N001820100', 'N001821000', 'N001820300']],
            'PLAT_CD'                    => ['NOT IN', ['N000831300', 'N000830100']],
            'ID'                         => ['IN', $set_ids]
        ];
        $where['_string'] = 'B5C_ORDER_NO IS NULL AND CHILD_ORDER_ID IS NULL';
        $list = $order_model->field('ID,ORDER_ID,BWC_ORDER_STATUS,PLAT_CD')->where($where)->lock(true)->select();
        if (empty($list)) {
            return;
        }
        $db_ids   = array_column($list, 'ID');
        $diff_ids = array_diff($set_ids, $db_ids);
        foreach ($diff_ids as $id) {
//            $cancel_auto_ids[] = $id;
            $redis_client->srem(self::GC_AUTO_GET_NO_KEY, $id);//移除已经成功了的订单自增id
        }
        $intersect_ids = array_intersect($set_ids, $db_ids);
        foreach ($intersect_ids as $id) {
            $redis_client->hincrby(self::GC_AUTO_SEND_COUNT,$id,1);//记录发送次数
        }
        $count_list = $redis_client->HGETALL(self::GC_AUTO_SEND_COUNT);
        Logs($list, __FUNCTION__.'------触发自动获取单号订单', 'fm');
        $order_model->startTrans();
        $date = dateTime();
        foreach (DataModel::toYield($list) as $key => $item) {
            if ($count_list[$item['ID']] > 5) {
                //自动获取超过5次自动停止
                $redis_client->srem(self::GC_AUTO_GET_NO_KEY, $item['ID']);
                $redis_client->hdel(self::GC_AUTO_SEND_COUNT, $item['ID']);
                $cancel_auto_data[] = [
                    'order_id'       => $item['ORDER_ID'],
                    'plat_cd'        => $item['PLAT_CD'],
                    'is_auto_get_no' => 0,
                ];
                continue;
            }
            $save['LOGISTICS_SINGLE_STATU_CD'] = 'N002080200';
            $save['LOGISTICS_SINGLE_ERROR_MSG'] = null;
            $save['LOGISTICS_SINGLE_UP_TIME'] = date();
            $res = $order_model->where(['ID' => $item['ID']])->save($save);
            if (!$res) {
                $order_model->rollback();
                @SentinelModel::addAbnormal('自动获取单号', '触发自动获取单号失败', [$item, $save, $res],'oms_patch_notice');
                continue;
            }
            $map = ['order_id'=>$item['ORDER_ID'], 'plat_cd'=>$item['PLAT_CD']];
            if ($order_extend_model->where($map)->count()) {
                $extend_save = ['is_auto_get_no' => 1];
                $res = $order_extend_model->where($map)->save($extend_save);
            } else {
                $extend_save = [
                    'order_id'       => $item['ORDER_ID'],
                    'plat_cd'        => $item['PLAT_CD'],
                    'is_auto_get_no' => 1
                ];
                $res = $order_extend_model->add($extend_save);
            }
            if (false === $res) {
                $order_model->rollback();
                @SentinelModel::addAbnormal('自动获取单号', '标记自动获取单号失败', [$map, $extend_save, $res],'oms_patch_notice');
                continue;
            }
            $log_data[] = [
                'ORD_NO'             => $item['ORDER_ID'],
                'ORD_HIST_SEQ'       => time(),
                'ORD_STAT_CD'        => $item['BWC_ORDER_STATUS'],
                'ORD_HIST_WRTR_EML'  => ' ERP SYSTEM',
                'ORD_HIST_REG_DTTM'  => $date,
                'ORD_HIST_HIST_CONT' => '触发自动获取单号：状态由“获取面单失败” 变成 “面单获取中”',
                'updated_time'       => $date,
                'plat_cd'            => $item['PLAT_CD'],
            ];
            unset($list[$key]);
        }
        $order_model->commit();
        if (!empty($log_data)) {
            M("ms_ord_hist", "sms_")->addAll($log_data);
        }
        if (!empty($cancel_auto_data)) {
            $res = $order_extend_model->execute(TempImportExcelModel::saveAllExtend($order_extend_model, $cancel_auto_data, $pk = 'order_id', ['order_id', 'plat_cd']));
            if (false === $res) {
                @SentinelModel::addAbnormal('自动获取单号', '取消自动获取标识失败', [$cancel_auto_data, $res],'oms_patch_notice');
            }
        }
        unset($log_data);
    }

    //自动获取订单自增id写进集合
    public static function addGcFaceOrderToSet($data)
    {
        $ids = array_column($data, 'id');
        self::checkGcOrder($ids);
        RedisModel::client()->sadd(self::GC_AUTO_GET_NO_KEY,$ids);
        Logs($data, __FUNCTION__.'------record auto data', 'fm');

        $orders = M('order', 'tb_op_')->field('ID,ORDER_ID,BWC_ORDER_STATUS,PLAT_CD')->where(['ID'=>['in', $ids]])->select();
        $date = dateTime();
        foreach ($orders as $item) {
            $log_data[] = [
                'ORD_NO'             => $item['ORDER_ID'],
                'ORD_HIST_SEQ'       => time(),
                'ORD_STAT_CD'        => $item['BWC_ORDER_STATUS'],
                'ORD_HIST_WRTR_EML'  => $_SESSION['m_loginname'],
                'ORD_HIST_REG_DTTM'  => $date,
                'ORD_HIST_HIST_CONT' => $_SESSION['m_loginname'] . '   操作了 “自动获取单号(谷仓)”按钮',
                'updated_time'       => $date,
                'plat_cd'            => $item['PLAT_CD'],
            ];
        }
        if (!empty($log_data)) {
            M("ms_ord_hist", "sms_")->addAll($log_data);
            unset($log_data);
        }
    }

    //自动获取条件判断
    private static function checkGcOrder($ids)
    {
        $field = 'ORDER_NO,WAREHOUSE,LOGISTICS_SINGLE_STATU_CD,LOGISTICS_SINGLE_ERROR_MSG,BWC_ORDER_STATUS,SEND_ORD_STATUS,PLAT_CD,B5C_ORDER_NO,CHILD_ORDER_ID';
        $order_info = M('order', 'tb_op_')->field($field)->where(['ID'=>['IN',$ids]])->select();
        if (empty($order_info)) {
            throw new \Exception(L('订单为空'));
        }
        foreach (DataModel::toYield($order_info) as $key => $order) {
            if (!in_array($order['WAREHOUSE'], [
                'N000686800',
                'N000687900',
                'N000688400',
                'N000689100',
                'N000689200',
                'N000689458',
                'N000689460',
                'N000689465',
                'N000689471'])) {
                throw new \Exception(L('订单号：'.$order['ORDER_NO'].'，下发仓库不是谷仓'));
            }
            if ($order['LOGISTICS_SINGLE_STATU_CD'] != 'N002080500') {
                throw new \Exception(L('订单号：'.$order['ORDER_NO'].'，面单获取状态不是“获取面单失败”'));
            }
            if (!in_array($order['BWC_ORDER_STATUS'], ['N000550600','N000550400','N000550500','N000550800'])) {
                throw new \Exception(L('订单号：'.$order['ORDER_NO'].'，订单状态不满足自动获取条件'));
            }
            if (!in_array($order['SEND_ORD_STATUS'], ['N001820100','N001821000','N001820300'])) {
                throw new \Exception(L('订单号：'.$order['ORDER_NO'].'，订单派单状态不满足自动获取条件'));
            }
            if (in_array($order['PLAT_CD'], ['N000831300','N000830100'])) {
                throw new \Exception(L('订单号：'.$order['ORDER_NO'].'，YT和B5C平台不能自动获取'));
            }
            if ($order['B5C_ORDER_NO']) {
                throw new \Exception(L('订单号：'.$order['ORDER_NO'].'，已经派单'));
            }
            if ($order['CHILD_ORDER_ID']) {
                throw new \Exception(L('订单号：'.$order['ORDER_NO'].'，已经拆单'));
            }
            if (false === strpos($order['LOGISTICS_SINGLE_ERROR_MSG'], '网络异常')) {
                throw new \Exception(L('订单号：'.$order['ORDER_NO'].'，面单失败原因不是网络异常'));
            }
            unset($order_info[$key]);
        }
    }

    //取消自动获取
    public static function removeGcFaceOrderSet($data)
    {
        $redis_client = RedisModel::client();
        $ids = array_column($data, 'id');
//        self::checkGcOrder($ids);
        $orders = M('order', 'tb_op_')->field('ID,ORDER_ID,BWC_ORDER_STATUS,PLAT_CD')->where(['ID'=>['in', $ids]])->select();
        $date = dateTime();
        foreach ($orders as $item) {
            $redis_client->hdel(self::GC_AUTO_SEND_COUNT, [$item['ID']]);
            $redis_client->srem(self::GC_AUTO_GET_NO_KEY,$item['ID']);
            $log_data[] = [
                'ORD_NO'             => $item['ORDER_ID'],
                'ORD_HIST_SEQ'       => time(),
                'ORD_STAT_CD'        => $item['BWC_ORDER_STATUS'],
                'ORD_HIST_WRTR_EML'  => $_SESSION['m_loginname'],
                'ORD_HIST_REG_DTTM'  => $date,
                'ORD_HIST_HIST_CONT' => $_SESSION['m_loginname'] . '  操作了 “停止自动获取”按钮',
                'updated_time'       => $date,
                'plat_cd'            => $item['PLAT_CD'],
            ];
            $cancel_auto_data[] = [
                'order_id'       => $item['ORDER_ID'],
                'plat_cd'        => $item['PLAT_CD'],
                'is_auto_get_no' => 0,
            ];
        }
        if (!empty($log_data)) {
            M("ms_ord_hist", "sms_")->addAll($log_data);
            unset($log_data);
        }
        self::cancelAutoFlag($cancel_auto_data, '停止自动获取');
        Logs($data, __FUNCTION__.'------remove auto data', 'fm');
    }

    //取消全部订单自动获取
    public static function removeAllGcFaceOrderSet()
    {
        $redis_client = RedisModel::client();
        $ids = $redis_client->sunion(self::GC_AUTO_GET_NO_KEY);
        $orders = M('order', 'tb_op_')->field('ID,ORDER_ID,BWC_ORDER_STATUS,PLAT_CD')->where(['ID'=>['in', $ids]])->select();
        $date = dateTime();
        if (!empty($orders)) {
            $redis_client->del(self::GC_AUTO_SEND_COUNT);
            $res = $redis_client->del(self::GC_AUTO_GET_NO_KEY);
            Logs([$res,$ids], __FUNCTION__. '------set deleted ids and result', 'fm');
        }
        foreach ($orders as $item) {
            $log_data[] = [
                'ORD_NO'             => $item['ORDER_ID'],
                'ORD_HIST_SEQ'       => time(),
                'ORD_STAT_CD'        => $item['BWC_ORDER_STATUS'],
                'ORD_HIST_WRTR_EML'  => $_SESSION['m_loginname'],
                'ORD_HIST_REG_DTTM'  => $date,
                'ORD_HIST_HIST_CONT' => $_SESSION['m_loginname'] . '  操作了 “停止自动获取”按钮',
                'updated_time'       => $date,
                'plat_cd'            => $item['PLAT_CD'],
            ];
            $cancel_auto_data[] = [
                'order_id'       => $item['ORDER_ID'],
                'plat_cd'        => $item['PLAT_CD'],
                'is_auto_get_no' => 0,
            ];
        }
        if (!empty($log_data)) {
            M("ms_ord_hist", "sms_")->addAll($log_data);
            unset($log_data);
        }
        self::cancelAutoFlag($cancel_auto_data, '停止自动获取');
    }

    public static function cancelAutoFlag($data, $msg)
    {
        if (empty($data)) {
            return;
        }
        $order_extend_model = M('order_extend', 'tb_op_');
        $res = $order_extend_model->execute(TempImportExcelModel::saveAllExtend($data, $order_extend_model, $pk = 'order_id', ['order_id', 'plat_cd']));
        if (false === $res) {
            @SentinelModel::addAbnormal($msg, '取消自动获取单号标识', [$data, $res],'oms_patch_notice');
        }
    }

    /**
     * 批量批量编辑 packeage
     * @param $data
     * @author Redbo He
     * @date 2020/12/3 15:36
     */
    public static function savePackageDataCounts(array $data)
    {
        $result = [];
        if($data)
        {
            $case_sql = 'CASE ';
            foreach ($data as $item) {
                $case_sql .= sprintf("WHEN %s THEN %s \n", "`ORD_ID` = '{$item['thr_order_id']}' &&  `plat_cd` ='{$item['plat_cd']}'", 1);
            }
            $case_sql .= 'ELSE 0 
        END';
            $ord_ids = array_unique(array_column($data,'thr_order_id'));
            $ord_ids_str = join( ', ',array_map(function( $v ){  return "'".$v."'";},$ord_ids) );
            $query_sql = "SELECT ORD_ID,plat_cd,  SUM({$case_sql}) as tp_count FROM `tb_ms_ord_package` where `ORD_ID` in ($ord_ids_str) GROUP BY ORD_ID,plat_cd";
            $model = M();
            $query_result = $model->query($query_sql);
            if($query_result)
            {
                foreach ($query_result as $item)
                {
                    $index = $item['ORD_ID'] . '_'. $item['plat_cd'];
                    $result[$index] = $item;
                }
            }
        }
        return $result;
    }

    public static function savePackageDataRecord(array $data)
    {
        $result = [];
        if($data)
        {
            $case_sql = 'CASE ';
            foreach ($data as $item) {
                $case_sql .= sprintf("WHEN %s THEN %s \n", "`ORD_ID` = '{$item['thr_order_id']}' &&  `plat_cd` ='{$item['plat_cd']}'", 1);
            }
            $case_sql .= 'ELSE 0 
        END';
            $ord_ids = array_unique(array_column($data,'thr_order_id'));
            $ord_ids_str = join( ', ',array_map(function( $v ){  return "'".$v."'";},$ord_ids) );
            $query_sql = "SELECT ({$case_sql}) as in_use,tb_ms_ord_package.* FROM `tb_ms_ord_package` where `ORD_ID` in ($ord_ids_str) GROUP BY ORD_ID,plat_cd";
            $model = M();
            $query_result = $model->query($query_sql);
            $result = [];
            if($query_result)
            {
                foreach ($query_result as $item)
                {
                    if(!$item['in_use']) continue;
                    $index = $item['ORD_ID'] . '_'. $item['plat_cd'];
                    unset($item['in_use']);
                    $result[$index] = $item;
                }
            }
        }
        return $result;
    }

    protected static function getSavePackageDataExtend(array $data)
    {
        $result = [];
        if($data)
        {
            $case_sql = 'CASE ';
            foreach ($data as $item) {
                $case_sql .= sprintf("WHEN %s THEN %s \n", "`order_id` = '{$item['thr_order_id']}' &&  `plat_cd` ='{$item['plat_cd']}'", 1);
            }
            $case_sql .= 'ELSE 0 
        END';
            # 8004932878248579
            $ord_ids = array_unique(array_column($data,'thr_order_id'));
            $ord_ids_str = join( ', ',array_map(function( $v ){  return "'".$v."'";},$ord_ids) );
            $query_sql = "SELECT ({$case_sql}) as in_use,tb_op_order_extend.order_id,plat_cd,is_auto_get_no FROM `tb_op_order_extend` where `order_id` in ($ord_ids_str) AND is_auto_get_no = 1 GROUP BY order_id,plat_cd";
            $model = M();
            $query_result = $model->query($query_sql);
            if($query_result)
            {
                foreach ($query_result as $item)
                {
                    if(!$item['in_use']) continue;
                    $index = $item['order_id'] . '_'. $item['plat_cd'];
                    unset($item['in_use']);
                    $result[$index] = $item;
                }
            }
        }
        return $result;
    }

    public static function saveOrderDataRecord(array $data)
    {
        $result = [];
        if($data)
        {
            $where_sql = '1 != 1';
            foreach ($data as $value) {
                $where_sql .= sprintf(" OR ( ORDER_ID = '%s' AND PLAT_CD = '%s' ) ", $value['thr_order_id'], $value['plat_cd']);
            }
            $db_data = M('order','tb_op_')
                ->field('ORDER_ID,PLAT_CD,WAREHOUSE,logistic_cd,logistic_model_id')
                ->where($where_sql)
                ->select();
            if ($db_data){
                foreach ($db_data as $itme){
                    $result[$itme['ORDER_ID'].'_'.$itme['PLAT_CD']] = $itme;
                }
            }
        }
        return $result;
    }


    /**
     * 验证是否是shopnc平台订单(单条)
     * @return bool
     */
    public static function verifysShopncOrderOne($condition){
        $is_shopnc_order = false;
        $condition['tb_ms_store.BEAN_CD'] =  'ShopNC';
        $info = M("store","tb_ms_")->field("ID,BEAN_CD")
            ->join('LEFT JOIN tb_ms_store ON tb_op_order.STORE_ID = tb_ms_store.ID')
            ->where($condition)
            ->find();
        if ($info) $is_shopnc_order = true;
        return $is_shopnc_order;
    }
    /**
     * 验证是否是shopnc平台订单(数组)
     * @return bool
     */
    public static function verifysShopncOrderMult($condition){
        if (is_array($condition)){
            $condition['tb_ms_store.BEAN_CD'] =  'ShopNC';
        }else {
            $condition .=  " AND  tb_ms_store.BEAN_CD = 'ShopNC'";
        }
        $shopnc_order_data = M("order","tb_op_")->field("ID")
            ->field("CONCAT(tb_op_order.ORDER_ID,'_',tb_op_order.PLAT_CD) as order_plat ")
            ->join('LEFT JOIN tb_ms_store ON tb_op_order.STORE_ID = tb_ms_store.ID')
            ->where($condition)
            ->select();
        if ($shopnc_order_data) $shopnc_order_data = array_column($shopnc_order_data,'order_plat');
        return $shopnc_order_data;
    }
    /**
     *  获取所有 shopnc平台下的店铺
     */
    public static function getShopncStoreIds(){
        $list = M("store","tb_ms_")->field("ID,BEAN_CD")->where(array('BEAN_CD'=>'ShopNC'))->select();
        $store_ids = array();
        if (!$list) $store_ids = array_column($list,'ID');
        return $store_ids;
    }
}