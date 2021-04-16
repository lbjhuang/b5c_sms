<?php
/**
 * User: yangsu
 * Date: 18/11/22
 * Time: 16:31
 */

class LogService extends Service
{
    public $logRepository;
    public function __construct()
    {
        $this->logRepository = new LogRepository();
    }

    /**
     * 统一小写
     *
     * @var array
     */
    public $table_field_name = [
        'tb_op_order' => [
            'warehouse' => '下发仓库',
            'logistic_cd' => '下发物流公司',
            'logistic_model_id' => '下发物流方式',
            'surface_way_get_cd' => '面单获取方式',
            'address_user_name' => '收货人姓名',
            'address_user_phone' => '收货人手机号',
            'address_user_identity_card' => '收货人身份证',
            'receiver_tel' => '收货人电话',
            'user_email' => '收货人邮箱',
            'address_user_country' => '国家',
//            'address_user_country_id' => '国家',
            'address_user_country_edit' => '国家',
            'address_user_provinces' => '省份',
            'address_user_city' => '市',
            'address_user_region' => '区/县',
            'address_user_address1' => '详细地址1',
            'address_user_address2' => '详细地址2',
            'address_user_address3' => '详细地址3',
            'address_user_address4' => '详细地址4',
            'address_user_address5' => '详细地址5',
            'address_user_post_code' => '邮编',
        ],
        'tb_op_order_guds' => [
            'b5c_sku_id' => 'SKU ID',
            'order_item_id' => '第三方产品ID',
            'customs_price' => '申报价格',
        ],
        'tb_ms_ord_package' => [
            'tracking_number' => '物流追踪号',
        ],
        'tb_ms_ord' => [
            'whole_status_cd' => '派单状态',
            'weighing' => '重量',
        ],
        'tb_op_order_extend' => [
            'doorplate' => '地址门牌号',
            'buyer_user_id' => '买家id',
        ]
    ];

    /**
     * @param $table
     * @param $where
     * @param array $update_content
     * @param $code_map
     * @param $old_data
     * @return string
     */
    public function getUpdateMessage($table, $where, array $update_content, $code_map, $old_data = null)
    {
        if(is_null($old_data))
        {
            $old_arr_db = $this->logRepository->getOldInfo($table, $where);
        }
        else
        {
            $old_arr_db = $old_data;
        }

        $log_msg = '';
        if (empty($old_arr_db)) {
//            $log_msg = '此改动为新增数据';
            return $log_msg;
        }
        foreach ($old_arr_db as $key => $value) {
            $old_arr[strtolower($key)] = $value;
        }
        $all_key = array_keys($old_arr);
        if ($code_map) {
            list($all_code_key_val, $all_logistics_key_val, $all_country_key_val) = $code_map;
        } else {
            list($all_code_key_val, $all_logistics_key_val, $all_country_key_val) = $this->getCodeMap();
        }
        $all_increase_key = array_keys($this->table_field_name[$table]);
        $all_increase_key = array_map(function ($value) {
            return strtolower($value);
        }, $all_increase_key);
        foreach ($update_content as $key => $value) {
            $key = strtolower($key);
            if (in_array($key, $all_key) && $value != $old_arr[$key]) {
                $key_name = $this->table_field_name[$table][$key] ? $this->table_field_name[$table][$key] : $key;
                list($old_value, $new_value) = $this->dataToValue(
                    [
                        'code' => $all_code_key_val,
                        'logistics' => $all_logistics_key_val,
                        'country' => $all_country_key_val
                    ],
                    $old_arr, $key, $value);
                if (in_array($key, $all_increase_key)) {
//                    $log_msg .= "{$key_name} 由 {$old_value} 编辑为 {$new_value};<br/>" . PHP_EOL;
                    $log_msg .= ''.$key_name.' 由 "'.$old_value.'" 编辑为 "'.$new_value.'";<br/>' . PHP_EOL;
                } else {
                    Logs(DataModel::userNamePinyin(), "{$table} {$key_name} 由 {$old_value} 编辑为 {$new_value}", 'Log');
                }
            }
        }
        return $log_msg;
    }

    public function getCodeMap()
    {
        $all_code_key_val      = CodeModel::getAllCodeArrKeyVal();
        $all_logistics_key_val = $this->logRepository->getAllLogisticsKeyVal();
        $all_country_key_val   = $this->logRepository->getAllCountryKeyVal();
        return [
            $all_code_key_val,
            $all_logistics_key_val,
            $all_country_key_val,
        ];
    }

    /**
     * @param $table
     * @param $where
     * @param array $update_content
     * @return string
     */
    public function getOneUpdateMessage($table, $where,$property,$update_content)
    {
        $logRepository = new LogRepository();
        $old_arr_db = $logRepository->getOldInfo($table, $where);
        $log_msg = '';
        $key_name = $this->table_field_name[$table][$property] ? $this->table_field_name[$table][$property] : $property;
        if ($old_arr_db[$property] != $update_content[$property]){
            if (empty($old_arr_db[$property])){
                // $log_msg .= "{$key_name} 由 空 编辑为 {$update_content[$property]};<br/>" . PHP_EOL;
                $log_msg .= ''.$key_name.' 由  "  "  编辑为 "'.$update_content[$property].'";<br/>' . PHP_EOL;
            }else{
                //$log_msg .= "{$key_name} 由 {$old_arr_db[$property]} 编辑为 {$update_content[$property]};<br/>" . PHP_EOL;
                $log_msg .= ''.$key_name.' 由 "'.$old_arr_db[$property].'" 编辑为 "'.$update_content[$property].'";<br/>' . PHP_EOL;
            }

        }
        return $log_msg;
    }

    /**
     * @param $all_key_val
     * @param $old_arr
     * @param $key
     * @param $value
     * @return array
     */
    private function dataToValue($all_key_val, $old_arr, $key, $value)
    {
        switch ($key) {
            case  'logistic_model_id':
                $old_value = $all_key_val['logistics'][$old_arr[$key]] ? $all_key_val['logistics'][$old_arr[$key]] : $old_arr[$key];
                $new_value = $all_key_val['logistics'][$value] ? $all_key_val['logistics'][$value] : $value;
                break;
            case  'address_user_country_id':
                $old_value = $all_key_val['country'][$old_arr[$key]] ? $all_key_val['country'][$old_arr[$key]] : $old_arr[$key];
                $new_value = $all_key_val['country'][$value] ? $all_key_val['country'][$value] : $value;
                break;
            case  'address_user_country_edit':
                $old_value = $old_arr['address_user_country_edit'];
                if (empty($old_arr['address_user_country_edit']) && $all_key_val['country'][$old_arr['address_user_country_id']]) {
                    $old_value = $all_key_val['country'][$old_arr['address_user_country_id']];
                }
                $new_value = $value;
                break;
            default:
                $old_value = $all_key_val['code'][$old_arr[$key]] ? $all_key_val['code'][$old_arr[$key]] : $old_arr[$key];
                $new_value = $all_key_val['code'][$value] ? $all_key_val['code'][$value] : $value;
        }
        return array($old_value, $new_value);
    }

    public function getDataCodeMap($data, $key_map = [])
    {
        $code_keys = $logistics_keys =  $countries_keys =  [];
        if($key_map)
        {
            $code_keys = isset($key_map['code']) ? $key_map['code'] : [];
            $logistics_keys = isset($key_map['logistics']) ? $key_map['logistics'] : [];
            $countries_keys = isset($key_map['country']) ? $key_map['country'] : [];
        }
        $keys = [
            'code' => $code_keys,
            'logistics' => $logistics_keys,
            'country' => $countries_keys,
        ];
        $values_data = [];
        foreach ($keys as $k =>  $values) {
            $values_data[$k] = [];
            if($values)
            {
                 foreach ($values as $key_name) {
                    $values_data[$k] = array_unique(array_merge(array_column($data,$key_name), $values_data[$k]));
                }
            }
        }
        $code_values      = isset($values_data['code']) ? $values_data['code'] : [];
        $logistics_values = isset($values_data['logistics']) ? $values_data['logistics'] : [];
        $country_values   = isset($values_data['country']) ? $values_data['country'] : [];

        $code_key_values  = $logistics_key_values = $countries_keys_values =  [];
        # cmn_code 逻辑部分查询
        if($code_values)
        {
            $Model = M();
            $temp_res = $Model->table('tb_ms_cmn_cd')
                ->field('CD,CD_VAL,ETC')
                ->order('SORT_NO asc')
                ->where(['CD' => ['in', $code_values]])
                ->select();
            $code_key_values = array_column($temp_res, 'CD_VAL', 'CD');
        }
        if($logistics_values)
        {
            $res_db =  M()->table('tb_ms_logistics_mode')
                 ->field('ID,LOGISTICS_MODE')
                 ->where(['ID' => ['in', $logistics_values]])
                 ->select();
            $logistics_key_values =  array_column($res_db, 'LOGISTICS_MODE', 'ID');
        }
        if($country_values) {
            $res_db2 = M()->table('tb_ms_user_area')
                ->where(['id' => ['in', $country_values]])
                ->field('id,zh_name')
                ->select();
            $countries_keys_values  = array_column($res_db2, 'zh_name', 'id');
        }
        return [
            $code_key_values,
            $logistics_key_values,
            $countries_keys_values
        ];
    }

}