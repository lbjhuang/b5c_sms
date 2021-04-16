<?php

/**
 * User: yangsu
 * Date: 18/3/6
 * Time: 10:16
 */
class BasisModel extends Model
{
    protected static $list_cd_arr = [
        'channel' => 'N00083',
        'order_status' => 'N00055',
        'dispatch_status' => 'N00182',
        'do_dispatch_type' => 'N00205',
        'logistics_status' => 'N00127',
        'aftermarket_status' => 'N00108',
        'waitPre_status' => 'N00203',
        'order_source_status' => 'N00243',
        'sort' => '',
        // 'country_status' => 'N00173',
        'shop_status' => '',
        'warehouse_status' => 'N00068',
        'logistics_company_status' => 'N00070', // 物流公司
        // 'shipping_methods_status' => 'N00173', // 物流方式
        'sales_team_status' => 'N00128',
        'down_query_status' => '',
        'site_cd' => 'N00262',
        'sell_small_team' => 'N00323',

    ];
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
            ],
            "send_ord_time" => [
                "CD" => "send_ord_time",
                "CD_VAL" => "按派单时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            "sendout_time" => [
                "CD" => "sendout_time",
                "CD_VAL" => "按出库时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ],
            /*"s_time4" => [
                "CD" => "s_time4",
                "CD_VAL" => "按出库时间",
                "SORT_NO" => "0",
                "ETc" => ""
            ]*/
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
                "CD_VAL" => "支付交易号",
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
                "CD_VAL" => "收件人邮箱",
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
            "remark_msg" => [
                "CD" => "remark_msg",
                "CD_VAL" => "备注",
                "SORT_NO" => "0",
                "ETc" => ""
            ]

        ],
    ];

    /**
     * @param bool $format
     * @param null $model
     *
     * @return mixed
     */
    public static function listMenu($format = false, $model = null)
    {
        $arr = self::$list_cd_arr;
        foreach ($arr as $key => $value) {
            if ('shop_status' == $key) {
                $data[$key] = self::getShop();
            } elseif ('site_cd' == $key) {
                $siteCodeArr = CodeModel::getCodeArr([$value]);
                $data[$key] = array_combine(array_column($siteCodeArr, 'CD'), array_values($siteCodeArr));
            } elseif (!empty($value)) {
                $data[$key] = CodeModel::getCodeLang($value);
            } else {
                $data[$key] = self::toLang(self::$lis_value_arr[$key], 'CD_VAL');
            }
        }
        $data['country_status'] = BaseModel::getAreaCode();
        $data['shipping_methods_status'] = self::getLogisticsModel();
        if ($format) return self::formatCd($data);
        return $data;
    }

    public static function toLang($val, $key)
    {
        foreach ($val as $k => &$v) {
            $v[$key] = L($v[$key]);
        }
        return $val;
    }

    protected static function getLogisticsModel()
    {
        $Model = M();
        $field = 'ID,LOGISTICS_CODE as CD,LOGISTICS_MODE as CD_VAL';
        $where['IS_DELETE'] = 0;
        $where['IS_ENABLE'] = 1;
        return $Model->table('tb_ms_logistics_mode')->where($where)->field($field)->select();
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    protected static function formatCd($data)
    {
        return $data;
    }

    public static function getShop()
    {
        $Model = M();
        $field = 'ID as CD, STORE_NAME as CD_VAL';
        return $Model->table('tb_ms_store')->field($field)->select();
    }

    public static function getListCdArr($index = false)
    {
        return ($index!== false && isset(self::$list_cd_arr[$index])) ?  self::$list_cd_arr[$index]: self::$list_cd_arr;
    }
}