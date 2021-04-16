<?php

class WarehouseLogService extends Service
{
    public $warehouseLogRepository;

    public function __construct()
    {
        $this->warehouseLogRepository = new WarehouseLogRepository();
    }

    private $table_field_name = [
        'tb_wms_warehouse' => [
            'address_en' => '英文地址',
            'address' => '中文地址',
            'is_bonded' => '是否保税仓',
            'default_addr' => '默认地址',
            'contacts' => '总负责人花名',
            'place' => '区域',
            'system_docking' => '自动出库',
            'auto_dispatch' => '获取单号自动派单',
            'auto_dispatch_delay' => '获取单号自动派单延迟（分钟）',
            'job_content' => '作业内容',
            'sender' => '寄件人',
            'sender_phone_number' => '寄件电话',
            'sender_system' => '自动出库',
            'sender_zip_code' => '邮编',
            'auto_group' => '组合自动打包',
            'phone' => '电话',
            'remarks' => '备注',
            'in_contacts' => '入库负责人',
            'operator_cds' => '运营方',
            'out_contacts' => '出库负责人',
            'contract_no' => '合同编号',
            'contract_start' => '合同有效期开始时间',
            'contract_end' => '合同有效期结束时间',
            'cost_currency' => '仓库每日成本币种',
            'cost_per_day' => '仓库每日成本美元',
            'type_cd' => '仓库类型',
        ],
        'tb_ms_cmn_cd' => [
            'USE_YN' => '开启状态',
            'CD_VAL' => '仓库名称'
        ],
    ];

    private $table_field_name_status_val = [
        'tb_ms_cmn_cd' => [
            'USE_YN' => [
                'Y' => "开启",
                'N' => "关闭"
            ]
        ],
        'tb_wms_warehouse' => [
            'is_bonded' => [
                0 => '否',
                1 => '是'
            ],
            'default_addr' => [
                1 => '中文地址',
                2 => '英文地址'
            ],
            'auto_dispatch' => [
                0 => '否',
                1 => '是'  
            ],
            'auto_group' => [
                0 => '不支持',
                1 => '支持' 
            ],
        ],
    ];

    private $table_field_name_code = [
        'tb_wms_warehouse' => [
            'system_docking',
            'sender_system',
            'cost_currency',
            'type_cd',
        ],
    ];

    private $table_field_nick_name = [
        'tb_wms_warehouse' => [
            'in_contacts',
            'out_contacts',

        ],
    ];

    private $table_field_name_contact = [
        'tb_wms_warehouse' => [
            'job_content',
            'operator_cds',
        ],
    ];


    /**
     * 驼峰命名转下划线命名
     * 思路:
     * 小写和大写紧挨一起的地方,加上分隔符,然后全部转小写
     */
    public function uncamelize($camelCaps,$separator='_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

    /**
     * @param $table
     * @param $where
     * @param array $update_content
     * @return string
     */
    public function getUpdateMessage($table, $where, array $update_content, $CD)
    {
        $old_arr_db = $this->warehouseLogRepository->getOldInfo($table, $where);
        $log_msg = '';
        if (empty($old_arr_db)) {
            return $log_msg;
        }
        foreach ($old_arr_db as $key => $value) {
            $old_arr[$key] = $value;
        }
        $all_key = array_keys($old_arr);
        $log_data = array();
        foreach ($update_content as $key => $value) {
            if ($key !== 'USE_YN' && $key !== 'CD_VAL') {
                $key = $this->uncamelize($key);
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
                // 数组转字符串
                if (isset($this->table_field_nick_name[$table])  && in_array($key, $this->table_field_name[$table])) {
                    $front_value = $old_arr[$key];
                    $later_value = $value;
                }
                // CODE 转义
                if (isset($this->table_field_name_code[$table])  && in_array($key, $this->table_field_name_code[$table])) {
                    $front_data = CodeModel::autoCodeOneVal(['code' => $old_arr[$key]], ['code']);
                    $front_value = $front_data['code_val'];
                    $later_data = CodeModel::autoCodeOneVal(['code' => $value], ['code']);
                    $later_value = $later_data['code_val'];
                }
                // 多个code，逗号隔开
                if (isset($this->table_field_name_contact[$table])  && in_array($key, $this->table_field_name_contact[$table])) {
                    $label = ',';
                    if ($key == 'job_content') {
                        $label = ':';
                    }
                    list($front_value, $later_value) = $this->arrayToStr($old_arr[$key], $value, $label);
                    if ($front_value == '' && $later_value == '') {
                        continue;
                    }
                }

                if (!empty($key_name)) {
                    $temp = array(
                        'warehouse_cd' => $CD,
                        'field_name' => $key_name,
                        'front_value' => !empty($front_value) ? $front_value : "空",
                        'later_value' => !empty($later_value) ? $later_value : "空",
                        'update_by' => userName(),
                        'update_at' => date("Y-m-d H:i:s"),
                    );
                    if ($front_value === '0' || $front_value === 0) {
                        $temp['front_value'] = '0';
                    }
                    if ($later_value === '0' || $later_value === 0) {
                        $temp['later_value'] = '0';
                    }
                    $log_data[] = $temp;
                }
            }
        }
        if (!empty($log_config_data)) $log_data = array_merge($log_config_data,$log_data);
        return $log_data;
    }

    // 查看日志
    public function getInfo($param = [])
    {
        $res = $this->warehouseLogRepository->getInfo($param, 'update_at desc');
        $warehouse_name = cdVal($param['warehouse_cd']);
        $data = [];
        foreach ($res as $key => $value) {
            $data[$key]['warehouse_name'] = $warehouse_name;
            $data[$key]['update_by'] = $value['update_by'];
            $data[$key]['update_at'] = $value['update_at']; 
            $data[$key]['info'] = $value['field_name'] . '由' . $value['front_value'] . '编辑为' . $value['later_value'];
            if ($value['front_value'] === $value['later_value']) { // 前后相同，表明是新增
                $data[$key]['info'] = $value['field_name'] . $value['front_value'];
            }
        }
        return $data;
    }

    /**
     *  添加日志
     */
    public function addLog($log_data)
    {
        return $this->warehouseLogRepository->addAll($log_data);
    }


    /**
     *  处理数组转字符串，多个code，以逗号隔开的
     */
    private function arrayToStr($front_value, $later_value, $label = ',')
    {
        $front_data = explode($label,$front_value);
        $later_data = explode($label,$later_value);
        if (!array_diff($front_data, $later_data) && !array_diff($later_data, $front_data)) {
            return ['', ''];
        }

        $cd_data = array_unique(array_merge($front_data,$later_data));
        $where['CD'] = array('in',$cd_data);
        $list = $this->warehouseLogRepository->getCodeVal($where);
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
        return array(implode($label,$front_value),implode($label,$later_value));
    }

}