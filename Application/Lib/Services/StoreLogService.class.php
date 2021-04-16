<?php
/**
 * User: xuejun.zou
 * Date: 19/12/12
 * Time: 9:50
 * 店铺管理日志类
 */

class StoreLogService extends Service
{
    public $storeLogRepository;

    public function __construct()
    {
        $this->storeLogRepository = new StoreLogRepository();
    }

    private $table_field_name = [
        'tb_ms_store' => [
            'STORE_NAME' => '店铺名称',
            'MERCHANT_ID' => '店铺别名',
            'USER_ID' => '负责人联系方式',
//            'store_by' => '店铺负责人',
            'OPERATION_TYPE' => '运营类型',
            'STORE_STATUS' => '店铺状态',
            'STORE_BACKSTAGE_URL' => '店铺后台地址',
            'COUNTRY_ID' => '国家',
            'STORE_INDEX_URL' => '店铺链接',
            'PLAT_NAME' => '站点',
            'PRODUCT_DETAIL_URL_MARK' => '商品主链接',
            'SALE_TEAM_CD' => '销售团队',
//            'SEND_ORD_TYPE' => '是否支持预派单',
            'DELIVERY_STATUS' => '发货系统',
            'IS_VAT' => '是否交VAT',
            'company_cd' => '注册公司',

            'plat_explain' => '平台说明',
            'up_shop_time' => '开店日期',
            'up_shop_num' => '开店账号',
            'up_shop_pass' => '开店密码',
            'proposer_email' => '申请邮箱',
            'proposer_phone' => '申请手机号码',
            'proposer_by' => '申请人',
            'is_fee' => '是否需押金或收取费用',
            'remark' => '备注',
            'credit_card_explain' => '信用卡绑定情况',
            'recently_affirm_time' => '最近确认时间',
            'handover_by' => '交接人',
            'shop_manager_id' => '店铺负责人',
            'ORDER_SWITCH' => '拉单开关',
            'LAST_TIME_POINT' => 'Last Time',
            'BEAN_CD' => 'Bean Code',
            'PROXY' => 'PROXY',
            'CRAWLER_STATUSES' => 'CRAWLER_STATUSES',
            'STORE_PWD' => '店铺密码',
            'fin_account_bank_id' => '账号渠道',
            'income_company_cd' => '收入记录公司',
            'product_id' => '店铺的赠品ID',
            'is_auto_devliery' => '自动标记发货',
            'sell_small_team_cd' => '销售小团队',
            'scan_time' => '扫描时间',
            'scan_switch' => '扫描时间开关',
            "default_timezone_cd" => '运营后台时间默认时区',
            "store_type_cd" => '店铺类型',
            "reality_opt_store_id" => '实际运营店铺ID',
            "supplier_id" => '合作方公司名称',
//            'APPKES' => "第三方授权信息",
//            "QUEUE_INFO" => "QUEUE_INFO",

        ],
        'tb_ms_custom_warehouse_config' => [
            'rule_name' => '规则名称',
            'status' => '状态',
            'warehouse_code' => '仓库code',
            'logistics_company_code' => '物流公司code',
            'logistics_mode_id' => '物流模式/方式id',
            'face_order_code' => '面单获取方式',
            'sku' => 'SKU信息',
            'prefix' => '前缀',
            'suffix' => '后缀',
            'shipping_method' => '配送方式',
            'country' => '国家ID',
            'country_name' => '国家名称',
            'remark' => '备注',
        ],
        'tb_ms_logistics_mode_info' => [
            'store_id' => '店铺id',
            'logistics_mode_id' => '物流方式id',
            'order_amount_range' => '订单金额区间（美元）',
            'recipient_country' => '收件人国家',
            'cast_time' => '投妥时间',
            'cast_switch' => '投妥时间开关',
        ],
    ];

    private $table_field_name_status_val = [
        'tb_ms_store' => [
            'OPERATION_TYPE' => [
                0 => "B2C",
                1 => "B2B2C",
                2 => "B5C",
            ],
            'STORE_STATUS' => [
                0 => "运营中",
                1 => "未运营"
            ],
            'SEND_ORD_TYPE' => [
                0 => '否',
                1 => '是'
            ],
            'DELIVERY_STATUS' => [
                0 => '未对接',
                1 => '已对接'
            ],
            'IS_VAT' => [
                0 => '否',
                1 => '是',
            ],
            'ORDER_SWITCH' => [
                0 => '关',
                1 => '开'
            ],
            'is_auto_devliery' => [
                0 => '否',
                1 => '是'
            ],
            'scan_switch' => [
                0 => '关',
                1 => '开'
            ],
        ],
        'tb_ms_custom_warehouse_config' => [
            'status' => [
                0 => "停用",
                1 => "启用",
                2 => "删除",
            ],
            'rule_type' => [
                1 => "自定义",
                2 => "默认",
            ],
        ],
        'tb_ms_logistics_mode_info' => [
            'cast_switch' => [
                0 => '关',
                1 => '开'
            ],
        ],
    ];
    private $table_field_name_by = [
        'tb_ms_store' => [
            'shop_manager_id',
            'handover_by',
        ],
        'tb_ms_custom_warehouse_config' => [
            'updated_by',
            'deleted_by',
        ],
    ];

    private $table_field_name_code = [
        'tb_ms_store' => [
            'company_cd',
            'income_company_cd',
            'sell_small_team_cd',
            'store_type_cd'
        ],
        'tb_ms_custom_warehouse_config' => [
            'warehouse_code',
            'logistics_company_code',
            'face_order_code',
        ],
    ];

    private $table_field_name_company = [
        'tb_ms_store' => [
            'COUNTRY_ID',
        ],
        'tb_ms_logistics_mode_info' => [
            'recipient_country',
        ],
    ];

    private $table_field_name_time = [
        'tb_ms_store' => [
            'recently_affirm_time',
            'up_shop_time',
        ],
    ];
    private $table_field_name_fin_account = [
        'tb_ms_store' => [
            'fin_account_bank_id',
        ],
    ];

    /**
     *  高级配置
     * @var array
     */
    public  $table_field_name_advanced_config  = [
        'tb_ms_store' => [
            'BEAN_CD' => 'Bean Code',
            'orderInvokeCount' => 'Order Invoke Count',
            'LAST_TIME_POINT' => 'Last Time',
            'itemInvokeCount' => 'Item Invoke Count',
            'cron' => 'Cron',
            'queuePlaceholder' => 'Queue Place holder',
            'CRAWLER_STATUSES' => 'Crawler Status',
            'PROXY ' => 'Proxy',
            'sellerId' => 'Seller ID',
            'accessToken' => 'Access Token',
            'appSecret' => 'App Secret',
            'clientId' => 'Client ID',
            'is_auto_devliery' => 'is auto delivery'
        ],
    ];
    /**
     *  高级配置=>自动获取token
     * @var array
     */
    public  $table_field_name_advanced_config_auto_token  = [
        'tb_ms_store' => [
            'token_update_frequency'=> 'Token Update Frequency',
            'token_update_time' => 'Token Update Time',
        ],
    ];

    /**高级配置 修改自动拉取token配置  (不影响原先高级配置日志逻辑 故独立出来)
     * @param $table
     * @param $where
     * @param array $update_content
     * @param $module  模块  4.高级配置
     * @return string
     */
    public function getUpdateMessageByAutoToken($table, $where, array $update_content, $module, $id){

        $old_arr_db = $this->storeLogRepository->getOldInfo($table, $where);
        $log_data = array();
        $log_msg = '';
        if (empty($old_arr_db)) {
            return $log_msg;
        }
        foreach ($old_arr_db as $key => $value) {
            $old_arr[$key] = $value;
        }
        $all_key = array_keys($old_arr);
        $log_data = array();

        //  高级配置 日志单独处理
        if ($module  == 4) {
            $log_config_data = $this->advanced_config_auto_token($table, $old_arr, $update_content, $id);
        }
        if (!empty($log_config_data)) $log_data = array_merge($log_config_data, $log_data);
        return $log_data;
    }
    /**
     *  自定义走仓配置
     * @var array
     */
    public  $table_field_name_custom_config  = [
        'tb_ms_custom_warehouse_config' => [
            'store_id' => '店铺ID',
            'sort' => '排序（优先级）',
            'rule_name' => '规则名称',
            'status' => '状态',
            'rule_type' => '规则类型',
            'warehouse_code' => '仓库code',
            'logistics_company_code' => '物流公司code',
            'logistics_mode_id' => '物流模式/方式id',
            'face_order_code' => '面单获取方式',
            'sku' => 'SKU信息',
            'prefix' => '前缀',
            'suffix' => '后缀',
            'shipping_method' => '配送方式',
            'country' => '国家ID',
            'country_name' => '国家名称',
            'remark' => '备注',
            'scan_time' => '扫描时间',
            'scan_switch' => '扫描时间开关',
        ],
    ];

    /**
     * @param $table
     * @param $where
     * @param array $update_content
     * @param $module  模块  1.基础配置、2.仓库配置、3.物流配置、4.高级配置、5.财务配置、6.自定义走仓配置
     * @return string
     */
    public function getUpdateMessage($table, $where, array $update_content, $module, $id)
    {
        $old_arr_db = $this->storeLogRepository->getOldInfo($table, $where);
        $log_msg = '';
        if (empty($old_arr_db)) {
            return $log_msg;
        }
        foreach ($old_arr_db as $key => $value) {
            $old_arr[$key] = $value;
        }
        $all_key = array_keys($old_arr);
        $log_data = array();

        //  高级配置 日志单独处理
        if ($module  == 4){
            $log_config_data = $this->advanced_config($table,$old_arr,$update_content,$id);
        }
        foreach ($update_content as $key => $value) {
            if (isset($this->table_field_name_time[$table]) && in_array($key, $this->table_field_name_time[$table])) {
                $value = date("Y-m-d H:i:s",strtotime($value));
            }
            if (in_array($key, $all_key) && $value != $old_arr[$key]) {
                $key_name = $this->table_field_name[$table][$key] ? $this->table_field_name[$table][$key] : "";
                $front_value = $old_arr[$key];
                $later_value = $value;
                // 状态转义
                if (isset($this->table_field_name_status_val[$table][$key]) && !empty($this->table_field_name_status_val[$table][$key])) {
                    $front_value = $this->table_field_name_status_val[$table][$key][$old_arr[$key]];
                    $later_value = $this->table_field_name_status_val[$table][$key][$value];
                }
                //  人物昵称转义
                if (isset($this->table_field_name_by[$table]) && in_array($key, $this->table_field_name_by[$table])) {
                    $userData = $this->getUserData();
                    $front_value = $userData[$old_arr[$key]];
                    $later_value = $userData[$value];
                }
                // CODE 转义
                if (isset($this->table_field_name_code[$table])  && in_array($key, $this->table_field_name_code[$table])) {
                    $front_data = CodeModel::autoCodeOneVal(['code' => $old_arr[$key]], ['code']);
                    $front_value = $front_data['code_val'];
                    $later_data = CodeModel::autoCodeOneVal(['code' => $value], ['code']);
                    $later_value = $later_data['code_val'];
                }
                // 国家地区 昵称
                if (isset($this->table_field_name_company[$table])  && in_array($key, $this->table_field_name_company[$table])) {
                    $front_value = TbMsUserArea::find($old_arr[$key], array('zh_name'))['zh_name'];
                    $later_value = TbMsUserArea::find($value, array('zh_name'))['zh_name'];
                }

                //  销售团队数据单独处理
                if ($key == "SALE_TEAM_CD") {
                    list($front_value, $later_value) = $this->disposeTame($old_arr[$key], $value);
                }
                // 合作方公司名称 单独处理
                if ($key == "supplier_id"){
                    list($front_value, $later_value) = $this->disposeSupplier($old_arr[$key], $value);
                }


                // 处理时间
                if (isset($this->table_field_name_time[$table]) && in_array($key, $this->table_field_name_time[$table])) {
                    $front_value = !empty($old_arr[$key]) ? date('Y-m-d',strtotime($old_arr[$key])) : "";
                    $later_value = !empty($value) ? date('Y-m-d',strtotime($value)) : "";
                }
                // 密码
                if ($key == 'STORE_PWD'){
                    $front_value = "*****";
                    $later_value = "*****";
                }
                // 账号渠道
                if (isset($this->table_field_name_fin_account[$table]) && in_array($key, $this->table_field_name_fin_account[$table])) {
                    $finAccountData = $this->getFinAccount();
                    $front_value = $finAccountData[$old_arr[$key]];
                    $later_value = $finAccountData[$value];
                }

                //物流模式/方式id单独处理
                if ($module == 6 && $key == "logistics_mode_id") {
                    $front_value= M('ms_logistics_mode','tb_')->where(['ID' => $front_value])->getField('LOGISTICS_MODE');
                    $later_value= M('ms_logistics_mode','tb_')->where(['ID' => $later_value])->getField('LOGISTICS_MODE');
                }

                if (!empty($key_name)) {
                    if ($module == 6) {
                        //使用店铺id
                        $id = $old_arr['store_id'];
                    }
                    $temp = array(
                        'store_id' => $id,
                        'module' => $module,
                        'field_name' => $key_name,
                        'front_value' => !empty($front_value) ? $front_value : "",
                        'later_value' => !empty($later_value) ? $later_value : "",
                        'update_by' => userName(),
                        'update_at' => date("Y-m-d H:i:s"),
                    );
                    $log_data[] = $temp;
                }
            }
        }
        if (!empty($log_config_data)) $log_data = array_merge($log_config_data,$log_data);
        return $log_data;
    }

    /**
     *  添加日志
     */
    public function addLog($log_data)
    {
        $repositoy = new StoreLogRepository();
        $ret = $repositoy->addAll($log_data);
        return $ret;
    }

    private function getUserData()
    {
        $data = array();
        $list = DataModel::getNormalUser('M_ID,M_NAME');
        if (!empty($list) && is_array($list)) {
            foreach ($list as $value) {
                $data[$value['M_ID']] = $value['M_NAME'];
            }
        }
        return $data;
    }

    private function getFinAccount(){
        $where = [
            'state' => '1'
        ];
        $list= M('account_bank','tb_fin_')->field('id,account_bank')->where($where)->select();
        $ret = array();
        foreach ($list as $value){
            $ret[$value['id']] = $value['account_bank'];
        }
        return $ret;
    }

    /**
     *  处理销售团队
     */
    private function disposeTame($front_value, $later_value)
    {
        $front_data = explode(',',$front_value);
        $later_data = explode(',',$later_value);
        $cd_data = array_unique(array_merge($front_data,$later_data));
        $where['CD'] = array('in',$cd_data);
        $list = $this->storeLogRepository->getCodeVal($where);
        if (empty($list)){
            return array($front_value,$later_value);
        }
        $front_value = array();
        $later_value = array();
        foreach ($list as $value){
            if (in_array($value['CD'],$front_data)){
                array_push($front_value,$value['CD_VAL']);
            }
            if (in_array($value['CD'],$later_data)){
                array_push($later_value,$value['CD_VAL']);
            }
        }
        return array(implode(',',$front_value),implode(',',$later_value));
    }

    /**
     * @param $front_value
     * @param $later_value
     */
    public function disposeSupplier($front_value, $later_value){
        $front_value_data = M('sp_supplier','tb_crm_')->field('SP_NAME')->where(array('ID'=>$front_value))->find();
        $later_value_data = M('sp_supplier','tb_crm_')->field('SP_NAME')->where(array('ID'=>$later_value))->find();
        return array($front_value_data['SP_NAME'],$later_value_data['SP_NAME']);
    }

    /**
     *  高级配置
     */
    public function advanced_config($table,$old_arr,$update_content,$id)
    {
        $old_data = array_merge(json_decode($old_arr['APPKES'],true),json_decode($old_arr['QUEUE_INFO'],true));
        $update_content = array_merge(json_decode($update_content['APPKES'],true),json_decode($update_content['QUEUE_INFO'],true));
        $log_data = array();
        foreach ($update_content as $key => $value) {
            if (isset($this->table_field_name_advanced_config[$table][$key])) {
                if ($value != $old_data[$key]){
                    $key_name = $this->table_field_name_advanced_config[$table][$key] ? $this->table_field_name_advanced_config[$table][$key] : "";
                    $front_value = $old_data[$key];
                    $later_value = $value;
                    if (!empty($key_name)) {
                        $temp = array(
                            'store_id' => $id,
                            'module' => 4,
                            'field_name' => $key_name,
                            'front_value' => !empty($front_value) ? $front_value : "",
                            'later_value' => !empty($later_value) ? $later_value : "",
                            'update_by' => userName(),
                            'update_at' => date("Y-m-d H:i:s"),
                        );
                        $log_data[] = $temp;
                    }
                }
            }
        }
        return $log_data;
    }
    /**
     *  高级配置中的自动获取token选项
     */
    public function advanced_config_auto_token($table, $old_arr, $update_content, $id)
    {
      
        foreach ($update_content as $key => $value) {
            if (isset($this->table_field_name_advanced_config_auto_token[$table][$key])) {
                if ($value != $old_arr[$key]) {
                    $key_name = $this->table_field_name_advanced_config_auto_token[$table][$key] ? $this->table_field_name_advanced_config_auto_token[$table][$key] : "";
                    $front_value = $old_arr[$key];
                    $later_value = $value;
                    if (!empty($key_name)) {
                        $temp = array(
                            'store_id' => $id,
                            'module' => 4,
                            'field_name' => $key_name,
                            'front_value' => !empty($front_value) ? $front_value : "",
                            'later_value' => !empty($later_value) ? $later_value : "",
                            'update_by' => userName(),
                            'update_at' => date("Y-m-d H:i:s"),
                        );
                        $log_data[] = $temp;
                    }
                }
            }
        }
        return $log_data;
    }
}