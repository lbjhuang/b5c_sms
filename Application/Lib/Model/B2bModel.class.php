<?php

/**
 * User: yansu
 * Date: 17/5/27
 * Time: 11:24
 */
class B2bModel extends Model
{
    public static $number_th = ['1st', '2nd', '3rd'];
    public static $bill_state = [
        'N000590100' => 'USD',
        'N000590200' => 'KRW',
        'N000590300' => 'CNY',
        'N000590400' => 'JPY',
        'N000590500' => 'EUR',
        'N000590600' => 'HKD',
        'N000590700' => 'SGD',
        'N000590800' => 'AUD',
        'N000590900' => 'GBP',
        'N000591000' => 'CAD',
        'N000591100' => 'MYR',
        'N000591200' => 'DEM',
        'N000591300' => 'MXN',
        'N000591400' => 'THB',
        'N000591500' => 'PHP',
        'N000591600' => 'IDR'
    ];

    public static $search_type = [
        'SKU_ID' => 'SKUID',
        'bar_code' => 'BarCode',
        'goods_title' => '商品名称',
    ];

    public static $code_mapping = [
        'currency_bz' => '기준환율종류코드',
        'deviation_cause' => '出入库差异',
        'node_type' => '付款节点',
        'node_date' => '付款天数',
        'node_prop' => '付款比例',
        'shipping' => '交货方式',
        'invioce' => '发票类型',
        'tax_point' => '采购税率',
    ];

    public static $need_gather = ['PO_ID', 'ORDER_ID', 'client_id', 'sales_team_id', 'invoice_type', 'tax_point', 'payment_account_type', 'deducting_tax_currency', 'side_taxed_currency', 'deducting_tax', 'side_taxed', 'receiving_code', 'overdue_statue'];

//    now state
    public static $code = [
        /*'now_state' =>
            [
                '无',
                '待发货',
                '部分发货',
                '发货完成',
                '部分到港',
                '全部到港',
                '部分入库',
                '已入库',
                '部分收款',
                '全部收款',
                '已收到退税'
            ],*/
        'overdue' => [
            '未逾期',
            '已逾期',
        ],
        'order_fh' => [
            '全部',
            '待发货',
            '部分发货',
            '已发货',
        ],
        'unconfirmed_state' => [
            '无',
            '有',
        ],
        'order_sk' => [
            0 => '全部',
            'N002540100' => '待提交',
            'N002540200' => '待核销',
            'N002540300' => '已核销',
        ],
        'order_ts' => [
            '全部',
            '待退税',
            '部分退税',
            '已退税'
        ],
        'currency_bz' => [
            'CNY',
            'KRW',
            'USD',
            'HKD',
            'JPY',
            'SGD',
            'EUR',
            'GBP',
            'AUD'
        ],
        'now_state' =>
            [
                '全部',
                '待发货',
                '发货完成',
                '未收款',
                '已收款',
                '已收到退税'
            ],
        'warehouse_state' =>
            [
                '全部',
                '待确认',
                '部分确认',
                '已确认'
            ],
        'warehousing_state' =>
            [
                '全部',
                '未确认',
                '已确认',
            ],
        'period' => [
            '1次性付款',
            '二期',
            '三期'
        ],
        'gathering' => [
            '全部',
            '待收款',
            '已收款',
        ],
        'main_gathering_state' => [
            '待收款',
            '已收款',
            '部分收款'
        ],
        'ship_state' =>
            [
                '全部',
                '待发货',
                '部分发货',
                '已发货'
            ],
        'is_no' =>
            [
                '是', '否'
            ],
        'transaction_type' =>
            [
                '贷款', '退税', '分期'
            ],
        'or_invoice_arr' => ['已开票', '未开票'],
        'deviation_cause' => ['发错货', '少发货', '多发货', '标签/外观/质量不符客户拒收', '保质期不符客户拒收'],
        'node_type' => ['合同后', '发货后', '到港后', '入库后', '收到发票后'],
        'node_date' => [1, 3, 5, 7, 10, 15, 30, 45, 60, 75, 90, 100, 0],   // 付款天数
//        'node_is_workday' => ["天", "工作日"],
        'node_is_workday' => ["天"],
        'node_prop' => [5, 10, 15, 20, 25, 30, 35, 40, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100],
        'shipping' => ["CIF", "FOB", "EXW", "DDP", "一般 贸易", "KR Domestic", "其他/Others"],
        'invioce' => ["不开票", "增值税普通发票", "增值税专用发票"],
        'tax_point' => [0, 6, 8, 10, 13, 17],
//        'tax_rebate_ratio' => [0, 8, 10, 13, 17],
        'order_overdue_statue' => ['未逾期', '部分逾期', '已逾期'],
        'overdue_statue' => ['当期未逾期', '当期逾期', '实际未逾期', '实际逾期']
    ];

    public static $checkdata = [
        'b2bgathering' => [
            'PO_ID' => ['tb' => 'receipt', 'type' => 'LIKE', 'require' => '',],
            'client_id' => ['tb' => 'receipt', 'type' => 'LIKE', 'require' => ''],  // 客户名称
            'sales_team_id' => ['tb' => 'receipt', 'type' => '', 'require' => ''],  // 销售团队ID
            'transaction_type' => ['tb' => 'receipt', 'type' => '', 'require' => ''],  // 收款类型
        ],
        'b2bgathering_def' => [
            'pre' => 'tb_b2b_',
            'type' => 'EQ'
        ],
        'b2bwarehousing' => [
            'warehouse' => ['tb' => 'ship_list', 'type' => 'LIKE', 'require' => ''],
            'PO_ID' => ['tb' => 'info', 'type' => 'LIKE', 'require' => '',],
            'CLIENT_NAME' => ['tb' => 'info', 'type' => 'LIKE', 'require' => ''],  // 客户名称
            'SALES_TEAM' => ['tb' => 'info', 'type' => '', 'require' => ''],  // 销售团队ID
            'AUTHOR' => ['tb' => 'warehouse_list', 'type' => '', 'require' => ''],  // 销售团队ID
            'DOSHIP_ID' => ['tb' => 'warehouse_list', 'type' => '', 'require' => ''],
            'BILL_LADING_CODE' => ['tb' => 'ship_list', 'type' => '', 'require' => ''],
        ],
        'b2bwarehousing_def' => [
            'pre' => 'tb_b2b_',
            'type' => 'EQ'
        ],
        'b2blist' => [
            'warehouse_state' => ['tb' => 'warehouse_list', 'type' => 'IN', 'require' => ''],
            'order_overdue_statue' => ['tb' => 'order', 'type' => 'IN', 'require' => ''],
            'order_fh' => ['tb' => 'ship_list', 'type' => 'IN', 'require' => ''],
            'order_sk' => ['tb' => 'receivable', 'type' => 'IN', 'require' => ''],
            'order_ts' => ['tb' => 'receipt', 'type' => 'IN', 'require' => ''],
            'return_status_cd' => ['tb' => 'order', 'type' => 'IN', 'require' => ''],
            'PO_ID' => ['tb' => 'order', 'type' => 'LIKE', 'require' => '',],
            'CLIENT_NAME' => ['tb' => 'info', 'type' => 'LIKE', 'require' => ''],
            'PO_USER' => ['tb' => 'info', 'type' => 'LIKE', 'require' => ''],
            'SALES_TEAM' => ['tb' => 'info', 'type' => '', 'require' => ''],
            'BILLING_CYCLE_STATE' => ['tb' => 'info', 'type' => '', 'require' => ''],
            'goods_title' => ['tb' => '', 'type' => '', 'require' => '', 'pre' => 'g'],
            'SKU_ID' => ['tb' => '', 'type' => '', 'require' => '', 'pre' => 'g'],
            'po_time_action' => ['tb' => 'order', 'type' => 'BETWEEN', 'require' => ''],
            'po_time_end' => ['tb' => 'order', 'type' => 'BETWEEN', 'require' => '']
        ],
        'b2blist_def' => [
            'pre' => 'tb_b2b_',
            'type' => 'EQ'
        ],
        'do_ship_list' => [
            'shipping_status' => ['tb' => 'doship', 'type' => '', 'require' => ''],
            'CLIENT_NAME' => ['tb' => 'doship', 'type' => 'LIKE', 'require' => ''],
            'PO_ID' => ['tb' => 'doship', 'type' => 'LIKE', 'require' => ''],
            'sales_team_code' => ['tb' => 'doship', 'type' => '', 'require' => ''],
        ],
        'do_ship_list_def' => [
            'pre' => 'tb_b2b_',
            'type' => 'EQ'
        ],
        'b2b_warehouse_list' => [
            'shipping_status' => ['tb' => 'warehouse_list', 'type' => '', 'require' => ''],
            'CLIENT_NAME' => ['tb' => 'warehouse_list', 'type' => 'LIKE', 'require' => ''],
            'PO_ID' => ['tb' => 'warehouse_list', 'type' => 'LIKE', 'require' => ''],
            'delivery_warehouse_code' => ['tb' => 'warehouse_list', 'type' => '', 'require' => ''],
            'sales_team_code' => ['tb' => 'warehouse_list', 'type' => '', 'require' => ''],

        ],
        'b2b_warehouse_list_def' => [
            'pre' => 'tb_b2b_',
            'type' => 'EQ'
        ],
        '_def' => [
            'pre' => 'tb_b2b_',
            'type' => 'EQ'
        ],

    ];

    /**
     * @param null $nm_val
     * @param        $cache
     * @param int $cache_time
     * @param string $nm
     * @param bool $USE_YN
     * @param null $code
     * @param null $params
     *
     * @return array
     */
    public static function get_code($nm_val = null, $cache, $cache_time = 3600, $nm = 'CD_NM', $USE_YN = false, $code = null, $params = null)
    {
        if (empty($cache_time)) {
            $cache_time = 3600;
        }
        $Cd = M('cmn_cd', 'tb_ms_');
        if (empty($code)) {
            if (static::$code_mapping[$nm_val]) {
                $nm_val = static::$code_mapping[$nm_val];
            }
            if (static::to_cd(static::$code[$nm_val])) {
                return static::to_cd(static::$code[$nm_val]);
            }
        } else {
            $nm_val = array('like', $code);
        }
        $where[$nm] = $nm_val;
        if ($USE_YN) $where['USE_YN'] = 'Y';
        $cd_hash_key = md5(json_encode($where));
        // $cd = RedisModel::get_key('erp_cd_data_' . $cd_hash_key);
        if (empty($cd)) {
            if (1 == $cache) {
                $cd = $Cd->where($where)->order('SORT_NO asc')->cache(true, 2)->getField('CD,CD_VAL,ETc');
                goto to_return_cd;
            }
            if (true == $params) {
                $cd = $Cd->where($where)->order('SORT_NO asc')->cache(true, 2)->getField('CD,CD_VAL,SORT_NO,ETc,ETC2');
                goto to_return_cd;
            }
            $cd = $Cd->where($where)->order('SORT_NO asc')->cache(true, 2)->getField('CD,CD_VAL,SORT_NO,ETc');
            if ($cd) {
                goto to_return_cd;
            }
        } else {
            $cd = json_decode($cd, true);
            return $cd;
        }
        to_return_cd:
        // RedisModel::set_key('erp_cd_data_' . $cd_hash_key, json_encode($cd, JSON_UNESCAPED_UNICODE), null, $cache_time);
        return $cd;

    }

    /**
     * @param        $nm_val
     * @param        $cache
     * @param int $cache_time
     * @param string $nm
     *
     * @return mixed
     */
    public static function get_code_lang($nm_val, $cache, $cache_time = 60, $nm = 'CD_NM')
    {
        $cd_arr = B2bModel::get_code($nm_val, $cache, $cache_time = 60, $nm = 'CD_NM');
        foreach ($cd_arr as &$v) {
            $v['CD_VAL'] = L($v['CD_VAL']);
        }
        return $cd_arr;
    }

    /**
     * @param $nm_val
     *
     * @return array|mixed
     */
    public static function get_code_y($nm_val)
    {
        return static::get_code($nm_val, 0, 0, 'CD_NM', true);
    }

    /**
     * @param        $cd
     * @param bool $USE_YN
     * @param null $params
     * @param string $nm
     *
     * @return array
     */
    public static function get_code_cd($cd, $USE_YN = false, $params = null, $nm = 'CD')
    {

        $cd_arr = static::get_code(null, 0, 0, $nm, $USE_YN, $cd . '%', $params);
        return $cd_arr;
    }

//  币种
    public static function get_currency()
    {
        $Currency = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '기준환율종류코드';
        return $Currency->where($where)->getField('CD,CD_VAL,ETc');
    }

//  退税比例
    public static function get_tax_rebate_ratio()
    {
        $Cd = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '退税比例';
        return $Cd->where($where)->getField('CD,CD_VAL,ETc');
    }

//  销售团队
    public static function get_sales_team()
    {
        $Cd = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '销售团队';
        return $Cd->where($where)->getField('CD,CD_VAL,ETc');
    }

//    付款节点
    public static function get_payment_node()
    {
        $Cd = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '付款节点';
        return $Cd->where($where)->getField('CD,CD_VAL,ETc');
    }

//    付款周期
    public static function get_payment_cycle()
    {
        $Cd = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '付款周期';
        return $Cd->where($where)->getField('CD,CD_VAL,ETc');
    }

//    发票and税点
    public static function get_invoice_point()
    {
        $Cd = M('cmn_cd', 'tb_ms_');
        $where_invoice['CD_NM'] = '发票类型';
        $res['invoice'] = $Cd->where($where_invoice)->getField('CD,CD_VAL,ETc');
        $where_point['CD_NM'] = '发票税点';
        $res['point'] = $Cd->where($where_point)->getField('CD,CD_VAL,ETc');
        return $res;
    }


//  发货方式
    public static function get_shipping_method()
    {
        $Cd = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '发货方式';
        return $Cd->where($where)->getField('CD,CD_VAL,ETc');
    }

    /**
     * 获取商品信息
     *
     */
    public static function get_goods_info($sku)
    {
        $Guds_opt = M('guds_opt', 'tb_ms_');
        $guds = $Guds_opt
            ->field('tb_ms_guds_opt.GUDS_OPT_ORG_PRC,tb_ms_guds.STD_XCHR_KIND_CD,tb_ms_guds_opt.GUDS_ID')
            ->join('left join tb_ms_guds on tb_ms_guds.GUDS_ID = tb_ms_guds_opt.GUDS_ID')
            ->where('tb_ms_guds_opt.GUDS_OPT_ID = ' . $sku)
            ->select();
        return $guds[0];
    }

    /**
     * @return array
     */
    private static function join_guds($goods)
    {
        $date = date('Y-m-d H:i:s');
        foreach ($goods as $v) {
            $gud['GSKU'] = $v['SHIPPING_SKU'];
            $info = self::get_goods_info($gud['GSKU']);
            $gud['taxes'] = 0;
            $gud['should_num'] = $v['DELIVERED_NUM'];
            $gud['send_num'] = $v['DELIVERED_NUM'];
            $gud['price'] = $info['GUDS_OPT_ORG_PRC'];// 单价
            $gud['currency_id'] = $info['STD_XCHR_KIND_CD'];// 币种
            $gud['currency_time'] = $date;
            $guds[] = $gud;
        }
        return $guds;
    }

    /**
     * @param $msord_arr
     * @param $goods_arr
     *
     * @return array
     */
    private static function join_sendOut_data($msord_arr, $goods_arr)
    {
        foreach ($msord_arr as $key => $value) {
            $res['bill']['channel'] = $value['PLAT_FORM'];
            $res['bill']['warehouse_id'] = $value['WAREHOUSE'];
            $res['bill']['link_bill_id'] = $value['ORD_ID'];
            $res['bill']['third_order_id'] = $value['THIRD_ORDER_ID'];
            $res['bill']['bill_type'] = 'N000950100';
            $res['bill']['relation_type'] = 'N002350400';
            $res['guds'] = $goods_arr[$value['ORD_ID']];
            $res_arr[] = $res;
        }
        return $res_arr;
    }

    /**
     ** update godo CNY to USD
     *
     * @param      $res
     * @param null $v
     * @param null $currency_set
     *
     * @return mixed
     */
    public static function currencyAdd($res, $v = null, $currency_set = null)
    {
        foreach ($res as &$v) {
            if ($v['unit_price']) {
                $v['rmb_unit_price'] = $v['unit_price'];
                $v['num'] = 0;
            } else {
                unset($v);
            }
        }
        return $res;
    }

    public static function currencyUpd($res, $currency_cd = null)
    {
        foreach ($res as &$v) {
            $rate = self::update_currency($currency_cd, $v['currency_time'], $dst_currency = 'USD');
            if ($v['unit_price'] && $rate) {
                $v['rmb_unit_price'] = $v['unit_price'] * $rate;
                $v['num'] = 0;
            } else {
                unset($v);
            }
        }
        return $res;
    }

    /**
     * @param $data
     * @param $data_temp
     *
     * @return mixed
     */
    private static function userCcJoin($data, $data_temp)
    {
        $finance_str = self::get_code_cd($data['company_our'], false, true, 'CD_VAL');
        $sale_leader_str = self::get_code_cd($data['sales_team_id'], false, true);
        $data_temp['cc'] = explode(',', array_values($finance_str)[0]['ETC2']);
        if ('10.8' != substr($_SERVER['SERVER_ADDR'], 0, 4)) {
            $data_temp['cc'] = ['yangsu@gshopper.com'];
        }
        Logs($finance_str, '$finance_str', 'mail_b2b');
        Logs($sale_leader_str, '$sale_leader_str', 'mail_b2b');
        $data_temp['user'] = $sale_leader_str[$data['sales_team_id']]['ETc'];
        return $data_temp;
    }

    /**
     * 根据合同编号查询是否有相应的合同
     *
     * @param $conNo
     *
     * @return bool|mixed
     */
    public function search_contracct_by_con_no($conNo)
    {
        $model = D('TbCrmContract');
        $ret = $model->where('CON_NO ="' . $conNo . '"')->find();
        if ($ret) return $ret;
        return false;
    }

    /**
     * @param null $p
     * @param null $assoc
     * @param null $obj2arr
     *
     * @return mixed
     */
    public static function get_data($p = null, $assoc = null, $obj2arr = null)
    {
        $data = file_get_contents("php://input", 'r');
        if ($obj2arr) return json_decode($data, $assoc);
        if ($assoc) return json_decode($data, $assoc)[$p];
        if ($p) return json_decode($data)->$p;
        return json_decode($data);
    }

    /**
     * @param $res
     *
     * @return string
     */
    public static function set_json($res)
    {
        return json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    /**
     * cd code
     *
     * @param $e
     *
     * @return array
     */
    private function to_cd($e)
    {
        foreach ($e as $k => $v) {
            $arr['CD'] = $k;
            $arr['CD_VAL'] = $v;
            $arr['ETc'] = $v;
            $arrs[] = $arr;
        }
        return $arrs;
    }

    /**
     * @param $k
     * @param $check
     * @param $e
     * @param $checks
     *
     * @return mixed
     */
    public static function getpio($k, $check, $e, $checks)
    {
        return empty($checks[$k][$e]) ? static::$checkdata[$check . '_def'][$e] : $checks[$k][$e];
    }

    /**
     * @param $v
     * @param $type
     *
     * @return string
     */
    public static function check_v($v, $type)
    {
        switch ($type) {
            case 'LIKE':
                $check_v = '%' . $v . '%';
                break;
            case 'IN':
            default:
                $check_v = $v;
                break;

        }
        return $check_v;
    }

    /**
     * @param $data
     * @param $check
     *
     * @return null
     */
    public static function joinwhere($data, $check, $where = null)
    {
        $checks = static::$checkdata[$check];

        $start_date = $data['lately_time_action'];
        $end_date = $data['lately_time_end'];
        if ($start_date) {
            $start_date .= ' 00:00:00';
        }
        if ($end_date) {
            $end_date .= ' 23:59:59';
        }
        if (!empty($start_date) && !empty($end_date)) {
            $where['tb_b2b_doship.update_time'] = array('between', array($start_date, $end_date));
        } elseif (empty($start_date) && !empty($end_date)) {
            $where['tb_b2b_doship.update_time'] = array('ELT', $end_date);
        } elseif (empty($end_date) && !empty($start_date)) {
            $where['tb_b2b_doship.update_time'] = array('EGT', $start_date);
        }
        unset($start_date, $end_date);

        $start_date = $data['po_time_action'];
        $end_date = $data['po_time_end'];
        if ($start_date) {
            $start_date .= ' 00:00:00';
        }
        if ($end_date) {
            $end_date .= ' 23:59:59';
        }
        if (!empty($start_date) && !empty($end_date)) {
            $where['po_time'] = array('between', array($start_date, $end_date));
        } elseif (empty($start_date) && !empty($end_date)) {
            $where['po_time'] = array('ELT', $end_date);
        } elseif (empty($end_date) && !empty($start_date)) {
            $where['po_time'] = array('EGT', $start_date);
        }
        $where = WhereModel::getBetweenDate(
            $data['S'],
            $data['U'],
            $where,
            "tb_b2b_ship_list.SUBMIT_TIME"
        );
        foreach ($data as $k => $v) {
            if (in_array($k, array_keys($checks)) && !empty($v)) {
                if (in_array($k, array('po_time_action', 'po_time_end'))) {
                    continue(1);
                }
                $pre = static::getpio($k, $check, 'pre', $checks);
                $type = static::getpio($k, $check, 'type', $checks);
                $where[$pre . $checks[$k]['tb'] . '.' . $k] = array($type, static::check_v($v, $type));
            }
        }
        if($where['tb_b2b_doship.shipping_status'] && false !== strstr($where['tb_b2b_doship.shipping_status'],',')){
            $shipping_status_string = $where['tb_b2b_doship.shipping_status'][1];
            $where['tb_b2b_doship.shipping_status'] = ['IN',WhereModel::stringToInArray($shipping_status_string)];
        }
        return $where;
    }

    public static function get_area($isCache = false, $where = false)
    {
        $model = M('_crm_site', 'tb_');
        if ($where) return $model->where($where)->cache($isCache)->getField('ID,NAME');
        return $model->cache($isCache, 3)->getField('ID,NAME');
    }

    /**
     * 处理批次出库
     *
     * @param $goods
     * @param $order_id
     */
    public static function out_batch_warehouse($goods, $order_id)
    {
        $sku_arr = array_column($goods, 'SHIPPING_SKU');
        $Model = M();
        $where['ORDER_ID'] = $order_id;
        $sales_team = $Model->table('tb_b2b_info')->where($where)->getField('SALES_TEAM');
        $user_id = session('user_id');
        $host_url = HOST_URL;
        if ($host_url == 'HOST_URL') $host_url = 'http://b5caiapi.stage.com';
        $url = $host_url . '/batch/export.json';
        trace($url, '$url');
        foreach ($goods as $v) {
            $good['num'] = $v['DELIVERED_NUM'];
            $good['skuId'] = $v['SHIPPING_SKU'];
            $good['gudsId'] = substr($v['SHIPPING_SKU'], 0, -2);
            $good['saleTeamCode'] = $sales_team;
            $good['filePath'] = null;
            $good['type'] = 1;
            $good['operatorId'] = $user_id;
            $goods_arr[] = $good;
        }
        $data['data']['export'] = $goods_arr;
        trace($data, '$data');
        $Stock = new StockAction();
        $res_json = $Stock->Curl_post($url, json_encode($data));
        $res = json_decode($res_json, true);
        if ($res['code'] != 2000) trace($data, '$data');
        return $res;
    }

    /**
     * @param $e
     */
    public static function join_batch_data($e)
    {

    }


    /**
     * 处理出库
     *
     * @param      $goods
     * @param int $type
     * @param null $order_id
     *
     * @return mixed
     */
    public static function out_warehouse($goods, $type = 1, $order_id = null)
    {
        /*$Info = M('info', 'tb_b2b_');
        $SALE_TEAM = $Info->where('ORDER_ID = ' . $order_id)->getField('SALES_TEAM');

        $goods_warehouse = array_column($goods, 'DELIVERY_WAREHOUSE');
        $goods_warehouse = array_unique($goods_warehouse);
        foreach ($goods_warehouse as $gw) {
            foreach ($goods as $g) {
                if ($g['DELIVERY_WAREHOUSE'] == $gw) $goods_sku_arr[$gw][] = $g;
            }
        }
        foreach ($goods_sku_arr as $key => $goods_sku) {
            $data = [
                'bill' => [
                    'bill_type' => 'N000950100',
                    'channel' => 'N000830100',
                    'warehouse_id' => $key,
                    'SALE_TEAM' => $SALE_TEAM

                ],
                'guds' => self::join_guds($goods_sku),
                'type' => $type
            ];
            //新接口数据
            $data = array_map(function ($v) use ($data) {
                return [
                    'operatorId' => $_SESSION['userId'],
                    'linkBillId' => $data['bill']['link_bill_id'],
                    'orderId' => $data['bill']['third_order_id'],
                    'relationType' => $data['bill']['relation_type'],
                    'billType' => $data['bill']['bill_type'],
                    'skuId' => $v['GSKU'],
                    'deliveryWarehouse' => $data['bill']['warehouse_id'],
                    'num' => $v['send_num'],
                    'saleTeamCode' => $data['bill']['SALE_TEAM'],
                ];
            }, $data['guds']);
            $url = '(new WmsModel())->b2bOutStorage($data)';
            $res = (new WmsModel())->b2bOutStorage($data);
            Logs([$data, $url, $res], __CLASS__, __FUNCTION__);
        }
        return $res;*/
    }

    /**
     * 处理线上订单出库
     * API DOC   http://erp.gshopper.stage.com/index.php?m=bill&a=out_and_in_storage
     *
     * @param        $goods
     * @param string $channel
     * @param        $warehouse_id
     * @param int $type
     * @param null $ordId
     * @param null $third_order_id
     *
     * @return mixed
     */
//    public static function out_online_warehouse($goods, $channel = 'N000830100', $warehouse_id, $type = 1, $ordId = null, $third_order_id = null, $sale_team, $ships_map = [])
//    {
//        $data = [
//            'bill' => [
//                'bill_type' => 'N000950100', //	收发类别
//                'channel' => $channel,
//                'warehouse_id' => $warehouse_id,
//                'is_use_send_net' => $ships_map[$warehouse_id],
//                'link_bill_id' => $third_order_id,
//                'third_order_id' => $ordId,
//                'SALE_TEAM' => $sale_team,
//                'relation_type' => 'N002350400',
//                'send_net_info' => self::getSendNetInfo($ordId)
//            ],
//            'guds' => $goods,
//            'type' => $type,
//            'operatorId' => $_SESSION['userId']
//        ];
//        //新接口数据
//        $data = array_map(function ($v) use ($data) {
//            return [
//                'operatorId' => $_SESSION['userId'],
//                'linkBillId' => $data['bill']['link_bill_id'],
//                'orderId' => $data['bill']['third_order_id'],
//                'relationType' => $data['bill']['relation_type'],
//                'billType' => $data['bill']['bill_type'],
//                'skuId' => $v['GSKU'],
//                'deliveryWarehouse' => $data['bill']['warehouse_id'],
//                'isUseSendNet' => $data['bill']['is_use_send_net'],
//                'num' => $v['send_num'],
//                'saleTeamCode' => $data['bill']['SALE_TEAM'],
//                'sendNetInfo' => $data['bill']['send_net_info']
//            ];
//        }, $data['guds']);
//        Logs($data, '$data', 'b2b');
//        G('beginUrl');
//        $today_date = date('Ymd');
//        Logs(G('beginUrl', $third_order_id . '-actionURLget', 6), $today_date . '-' . $third_order_id . '-actionURLget-' . microtime(true));
//        $url = '(new WmsModel())->b2bOutStorage($data)';
//        $res = (new WmsModel())->b2bOutStorage($data);
//        Logs(G('beginUrl', $third_order_id . '-endURLget', 6), $today_date . '-' . $third_order_id . '-endURLget-' . microtime(true));
//        if (!$res) {
//            @SentinelModel::addAbnormal('B2B 发货', '发货异常', [$data, $res],'b2b_notice');
//        }
//        if ($res['code'] == 10000111 || $res['code'] == 10000000) {
//            $res['code'] = 2000;
//        } else {
//            Logs([$data, $url, $res], __CLASS__, __FUNCTION__);
//        }
//        Logs($res, 'res' . $ordId, 'b2b');
//        return $res;
//    }

    /**
     * 重构该方法的原因：
     * 如果B2B发货商品SKU大于1，在调用java出库接口是要分多次调用。
     * 如果其中一次调用失败了，已经生成的出库记录无法撤回，所以改成一次出库只调用一次出库接口
     * @param $goods
     * @param null $ordId
     * @param null $third_order_id
     * @param $sale_team
     * @param array $ships_map
     * @return mixed
     */
    public static function out_online_warehouse($goods, $ordId = null, $third_order_id = null, $sale_team, $ships_map = [])
    {
        $data = [];
        foreach ($goods as $warehouse_id => $value) {
            foreach ($value as $v) {
                $data[] = [
                    'operatorId'        => $_SESSION['userId'],
                    'linkBillId'        => $third_order_id,
                    'orderId'           => $ordId,
                    'relationType'      => 'N002350400',
                    'billType'          => 'N000950100',
                    'skuId'             => $v['GSKU'],
                    'deliveryWarehouse' => $warehouse_id,
                    'isUseSendNet'      => $ships_map[$warehouse_id],
                    'num'               => $v['send_num'],
                    'saleTeamCode'      => $sale_team,
                    'sendNetInfo'       => self::getSendNetInfo($ordId)
                ];
            }
        }
        Logs($data, '$data', 'b2b');
        G('beginUrl');
        $today_date = date('Ymd');
        Logs(G('beginUrl', $third_order_id . '-actionURLget', 6), $today_date . '-' . $third_order_id . '-actionURLget-' . microtime(true));
        $url = '(new WmsModel())->b2bOutStorage($data)';
        $res = (new WmsModel())->b2bOutStorage($data);
        Logs(G('beginUrl', $third_order_id . '-endURLget', 6), $today_date . '-' . $third_order_id . '-endURLget-' . microtime(true));
        if ($res['code'] == 10000111 || $res['code'] == 10000000) {
            $res['code'] = 2000;
        }
        if ($res['code'] != 2000) {
            @SentinelModel::addAbnormal('B2B 发货', $ordId.' 发货异常-'.$res['msg'], [$data, $res],'b2b_notice');
        }
        Logs($res, 'res' . $ordId, 'b2b');
        return $res;
    }

    /**
     * @param $po_id
     * @return array
     */
    private static function getSendNetInfo($po_id)
    {
        $Model = new Model();
        $where['PO_ID'] = $po_id;
        $db_res = $Model->table('tb_b2b_info')
            ->where($where)
            ->find();
        $area_init = self::get_area(true, $where);
        $address_arr = json_decode($db_res['TARGET_PORT'], true);
        $send_net_info = [
            "addressUserCity" => $area_init[$address_arr['city']],
            "addressUserName" => $db_res['CLIENT_NAME'],
            "addressUserPhone" => null,
            "addressUserProvinces" => $area_init[$address_arr['stareet']],
            "addressUserRegion" => $area_init[$address_arr['targetCity']],
            "addressUserAddress1" => $db_res['CLIENT_NAME']
        ];
        return $send_net_info;
    }

    /**
     * 处理线上订单出库-批量
     * @param $msord_arr
     * @param $goods_arr
     *
     * @return mixed
     */
    public static function out_online_all_warehouse($msord_arr, $goods_arr, $model = null)
    {
        $data = [
            'data' => self::join_sendOut_data($msord_arr, $goods_arr),
            'type' => 0,
            'bill_type' => 'N000950100' //	收发类别
        ];
        $url = U('bill/mul_out_and_in_storage', '', true, false, true);
        if ($_SERVER['SERVER_NAME'] == 'sms2.b5c.com') $url = 'http://erp.gshopper.stage.com/index.php?m=bill&a=mul_out_and_in_storage';
        $res = json_decode(curl_request($url, $data, 'PHPSESSID=' . $_COOKIE['PHPSESSID'] . '; think_language=zh-CN'), true);
        if ($res['code'] != 10000111) {
            Logs($data, 'out_data');
            Logs($url, 'out_url');
            Logs($res, 'out_res');
        } else {
            Logs($data, 'out_data');
            $res['code'] = 2000;
        }
        return $res;
    }

    /**
     *  单次访问模型
     *
     * @param $msord_arr
     * @param $goods_arr
     * @param $model
     *
     * @return mixed
     */
    public static function out_online_all_warehouse_get_model($msord_arr, $goods_arr, $model)
    {
        $data_arr = [
            'data' => self::join_sendOut_data($msord_arr, $goods_arr),
            'type' => 0,
            'bill_type' => 'N000950100' //	收发类别
        ];
        $data = self::arr2one($data_arr);
        $res = $model->outAndInStorage($data);
        if ($res['code'] != 10000111) {
//            Logs($data_arr, 'out_data_arr_model');
            Logs($data, 'out_data_model');
            Logs($res, 'out_res_model');
        } else {
            Logs($data, 'out_data_model');
            $res['code'] = 2000;
        }
        return $res;
    }

    /**
     * @param $data_arr
     *
     * @return array
     */
    public static function arr2one($data_arr)
    {
        $data_one = $data_arr['data'][0];
        $data = [
            'bill' => [
                'bill_type' => 'N000950100', //	收发类别
                'channel' => $data_one['bill']['channel'],
                'warehouse_id' => $data_one['bill']['warehouse_id'],
                'link_bill_id' => $data_one['bill']['link_bill_id'],
                'third_order_id' => $data_one['bill']['third_order_id'],
            ],
            'guds' => $data_one['guds'],
            'type' => $data_arr['type']
        ];
        return $data;
    }

    /**
     * @param $order_id
     * @param $code
     * @param $msg
     * @param null $associated_document_number
     * @return mixed
     */
    public static function addLog($order_id, $code, $msg, $associated_document_number = null)
    {
        $Log = M('log', 'tb_b2b_');
        $data['ORDER_ID'] = $order_id;
        $data['STATE'] = $code;
        $data['COUNT'] = $msg;
        $data['associated_document_number'] = $associated_document_number;
        $data['USER_ID'] = session('m_loginname');
        $data['create_time'] = date('Y-m-d H:i:s');
        return $Log->add($data);
    }

    /**
     * @param        $currency is code
     * @param        $date
     * @param string $dst_currency
     *
     * @return int
     */
    public static function update_currency($currency, $date, $dst_currency = 'USD')
    {
        if (empty($date) || '1970-01-01' == $date || empty($currency)) {
            return 0;
        }
        $date = date('Y-m-d', strtotime($date));
        $url = BI_API_REVEN . '/external/exchangeRate?date=' . $date . '&dst_currency=' . $dst_currency . '&src_currency=' . ExchangeRateModel::getAllKeyValue()[$currency];
        $url_md5 = md5($url);
        $url_md5 = 'url_' . $url_md5;
        $result = S($url_md5);
        if ($result === false) {
            $tmp_start = time();
            $currency = @json_decode(curl_request($url), 1);
            Log::write('--url_api--' . '--start--' . $tmp_start . '--end--' . time() . '--' . $url,'INFO');
            if ($currency)
                S($url_md5, $currency, 3600 * 24);
        } else {
            $currency = $result;
        }
        if (empty($currency['data'][0]['rate'])) {
            Logs($url, 'url', 'b2b');
            return 0;
        } else {
            return $currency['data'][0]['rate'];
        }
    }

    public static function currency_po_to_erp($currency_code)
    {
        $code_arr = static::get_code('기준환율종류코드');
        $code_arr_sort = array_column($code_arr, 'CD', 'ETc');
        trace($code_arr_sort, '$code_arr_sort');
        return $code_arr_sort[$currency_code];
    }

    /**
     * barcode to sku
     *
     * @param $bar
     *
     * @return null|string
     */
    public static function bar_to_sku($bar)
    {
        $sku = null;
        if (strlen($bar) > 10) {
            $qr_code = $bar;
            $Guds_opt = M('guds_opt', 'tb_ms_');
            if (!empty($qr_code)) {
                $where_qr['GUDS_OPT_UPC_ID'] = $bar;
                $res = $Guds_opt->where($where_qr)->field('GUDS_OPT_ID')->find();
                $sku = empty($res) ? '' : $res['GUDS_OPT_ID'];
            }
        }
        return $sku;
    }

    public static function get_user($u = null)
    {
        $Admin = M('admin', 'bbm_');
        $where = null;
        if ($u) {
            $where_name['M_NAME'] = $u;
            $where['_complex'] = $where_name;
            $where['_logic'] = 'OR';
            $where['huaming'] = $u;
        }
        $user_arr = $Admin->field('M_NAME')->where($where)->select();
        return $user_arr;
    }

    /**
     * @param      $e
     * @param bool $is_json
     *
     * @return null|string
     */
    public static function toNode($e, $is_json = true)
    {
        $d = ($is_json) ? json_decode($e, true) : $e;
        if (!$d['nodeProp']) return null;
        $init_data['number_th'] = static::$number_th;
        $init_data['node_type'] = B2bModel::get_code('node_type');
        $init_data['node_date'] = B2bModel::get_code('node_date');
        $init_data['node_is_workday'] = B2bModel::get_code('node_is_workday');
        $run_e = $init_data['number_th'][$d['nodei']] . ':' . $init_data['node_type'][$d['nodeType']]['CD_VAL'] . $init_data['node_date'][$d['nodeDate']]['CD_VAL'] . $init_data['node_is_workday'][$d['nodeWorkday']]['CD_VAL'] . '-' . $d['nodeProp'] . '%';
        return $run_e;
    }

    public static function TO_CD_VAL($e)
    {
        $Cd = M('cmn_cd', 'tb_ms_');
        return $Cd->where('CD = \'' . $e . '\'')->getField('CD_VAL');
    }

    /**
     * 拆分收款
     *
     * @param $e
     */
    public static function chirld_gather_add($e, $amount)
    {
        $h8 = 3600 * 8;
        $Models = M();
        foreach ($e as $k => $v) {
            if (in_array($k, static::$need_gather)) $receiptData[$k] = $v;
        }
//        $receiptData['completion_date_end']
        $receiptData['expect_receipt_date'] = gmdate('Y-m-d H:i:s', strtotime($e['completion_date_end']) + $h8);
        $receiptData['transaction_type'] = (1 == $e['transaction_type']) ? 1 : 2;
        $receiptData['P_ID'] = $e['ID'];
        $receiptData['overdue_statue'] = 0;
        $receiptData['main_id'] = ($e['main_id']) ? $e['main_id'] : $e['ID'];
        $receiptData['expect_receipt_amount'] = (2 == $e['transaction_type']) ? $e['collect_this'] : $e['expect_receipt_amount'];
        $receiptData['actual_payment_amount'] = $receiptData['collect_this'] = $amount;
        $payment_node = json_decode($e['PAYMENT_NODE'], true);
        $receiptData['estimated_amount'] = (2 == $e['transaction_type']) ? ($e['estimated_amount'] * $payment_node[0]['nodeProp'] / 100) : $e['estimated_amount'];
        Logs($e, 'e', 'b2b');
        Logs($amount, 'amount', 'b2b');
        Logs($receiptData, 'receiptData', 'b2b');
        $receipt_id = $Models->table('tb_b2b_receipt')->data($receiptData)->add();
        return $receipt_id;
    }

    public static function chirldGatherAmountUpd($e, $amount)
    {
        $Models = M();
        $save['collect_this'] = $save['actual_payment_amount'] = $amount;
        $receipt_id = $Models->table('tb_b2b_receipt')->where("P_ID = " . $e['ID'])->save($save);
        return $receipt_id;
    }


    /**
     * @param      $ares
     * @param null $type
     * @param bool $isOne
     *
     * @return mixed|string
     */
    public static function join_ares($ares, $type = null, $isOne = false)
    {
        if ($ares != null) {
            $ares = json_decode($ares, true);
            $run_key = function ($e) {
                if ((int)$e && !empty($e)) return $e;
            };
            $id_array = array_map($run_key, array_values($ares));
            $where['ID'] = array('in', array_unique(array_filter($id_array)));
            $area_init = B2bModel::get_area(true, $where);
            $area = '';
            if ($isOne) return $area = $ares['targetCity'];
            if ($ares['country'] && $type >= 0) $area = $area_init[$ares['country']];
            if ($ares['stareet'] && $type >= 1) $area .= '-' . $area_init[$ares['stareet']];
            if ($ares['city'] && $type >= 2) $area .= '-' . $area_init[$ares['city']];
            if ($ares['targetCity'] && $type >= 3) $area .= '-' . $ares['targetCity'];
            return $area;
        }
        return $ares;
    }

    /**
     * @param $arr
     * @param $kingKey
     *
     * @return mixed
     */
    public static function toKingArr($arr, $kingKey)
    {
        if (empty($arr)) return false;
        foreach ($kingKey as $v) {
            $arr[$v] = number_format($arr[$v], 2);
        }
        return $arr;
    }

    /**
     * join proportion data to BI require
     *
     * @param $data
     *
     * @return array
     */
    public static function joinProportionReq($data)
    {
        foreach ($data['skuData'] as $v) {
            unset($tmp_param);
            unset($res);
            $tmp_param['erp_purchase_code'] = $v['purchasing_team'];
            $tmp_param['erp_intro_code'] = $v['introduce_team'];
            $tmp_param['erp_sales_code'] = $data['saleTeam'];
            $tmp_param['date'] = $data['po_date'];
            $get_data = json_decode(self::getRevenueSplitService($tmp_param), JSON_UNESCAPED_UNICODE);
            if (count($get_data)) {
                $res['state'] = '200';
                $res['data'] = self::joinPropTeam($get_data);
            } else {
                Logs($get_data, '$get_data', 'revenueSplitService');
                Logs($get_data, '$get_data');
                $res['state'] = '400';
                $res['data'] = $get_data;
            }
            $res_arr[] = $res;
        }
        return $res_arr;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function joinPropTeam($data)
    {
        $tmp_data = $data[0]['split_rule'];
        $res['team_1'] = self::pos_team($tmp_data['team_1']);
        $res['team_2'] = self::pos_team($tmp_data['team_2']);
        $res['team_3'] = self::pos_team($tmp_data['team_3']);
        Logs($res, '$res');
        return $res;
    }

    /**
     * @param $data
     *
     * @return bool|int
     */
    private static function pos_team($data)
    {
        $pointer = mb_strrpos($data, '(');
        $data1 = substr($data, 0, $pointer);
        $data2 = substr($data, $pointer + 1, -1);
        if ($data1 && $data2) {
            $res = $data1 . ',' . $data2;
        } else {
            $res = '-';
        }
        return $res;
    }


    /**
     * @param $data
     *
     * @return mixed
     */
    public static function getRevenueSplitService($data)
    {
        $get_val = null;
        if ($get_val) {
            return $get_val;
        } else {
            $url = BIADMIN . '/biadmin-backend/revenueSplitService/query';
            $res = HttpTool::curlReq($url, $data);
            Logs($url, '$url', 'revenueSplitService');
            Logs($data, '$data', 'revenueSplitService');
        }
        return $res;
    }

    /**
     * @param $main_id
     * @param $model
     *
     * @return mixed
     */
    public static function gathering_list($main_id, $model)
    {
        /*$where['main_id'] = $main_id;
        $where['receipt_operation_status'] = 1;*/
        return $model->table('tb_b2b_receipt')
            ->field('tb_b2b_receipt.*,tb_b2b_info.po_time')
            ->join('LEFT JOIN tb_b2b_info ON tb_b2b_info.ORDER_ID = tb_b2b_receipt.ORDER_ID')
            ->where("(tb_b2b_receipt.main_id = $main_id   OR tb_b2b_receipt.ID = $main_id ) AND tb_b2b_receipt.receipt_operation_status = 1 ")
            ->select();
    }

    /**
     * @param      $id
     * @param null $model
     *
     * @return mixed
     */
    public static function gatheringConfirm($id, $model = null)
    {
        $model = ($model) ? $model : M();
        $where['id'] = $id;
        $save['unconfirmed_state'] = 1;
        return $model->table('tb_b2b_receipt')->where($where)->save($save);
    }

    /**
     * @param $main_id
     * @param $model
     */
    public static function receivablesIdGet($main_id, $model)
    {
        $where['main_id'] = $main_id;
        $where['receipt_operation_status'] = 0;
        return $model->table('tb_b2b_receipt')->where($where)->order('ID desc')->limit(1)->getField('ID');
    }

    /**
     * @param $gathering_list
     *
     * @return bool
     */
    public static function confirmAll($gathering_list)
    {
        $unconfirmed_state_arr = array_column($gathering_list, 'unconfirmed_state');
        $unconfirmed_unique = array_unique($unconfirmed_state_arr);
        Logs($unconfirmed_state_arr, '$unconfirmed_state_arr', 'b2b');
        Logs($unconfirmed_unique, '$unconfirmed_unique', 'b2b');
        if (count($unconfirmed_unique) > 1) {
            return 0;
        } elseif (0 == $unconfirmed_unique[0]) {
            return 0;
        }
        return 1;
    }

    /**
     * Get batch order stock number
     *
     * @param $doship_goods
     * @param $sale_team_cd
     *
     * @return mixed
     */
    public static function batchStockGet($doship_goods, $sale_team_cd)
    {
        /*$sku_arr = array_column($doship_goods, 'sku_show');
        $purchasing_team_arr = array_column($doship_goods, 'purchasing_team', 'sku_show');
        $sku_arr = array_unique($sku_arr);*/
        foreach ($doship_goods as $k => $v) {
            $res['skuId'] = $v['SKU_ID'];
            $res['saleTeamCode'] = $sale_team_cd;
            // $res['purchaseTeamCode'] = $v['purchasing_team'];
            $res_arr[$res['skuId'] . $res['saleTeamCode']] = $res;
        }
        $batch_stock['batchStock'] = array_values($res_arr);
        $batchStockPost = ApiModel::batchStockPost(json_encode($batch_stock, JSON_UNESCAPED_UNICODE));
        if ($batchStockPost) {
            $batch_res = json_decode($batchStockPost, true)['data']['batchStock'];
            $LocationService = new LocationService();
            $LocationService->warehouse_key = 'deliveryWarehouse';
            $LocationService->sku_key = 'skuId';
            $LocationService->obtainEnumerate($batch_res);
            foreach ($batch_res as $v) {
                $batch_res_sku[$v['skuId']][] = $v;
            }
        } else {
            @SentinelModel::addAbnormal('B2B 待发货', '获取批次库存异常', null, 'b2b_notice');
        }
        return $batch_res_sku;
    }

    /**
     * @param $doship_goods
     *
     * @return mixed
     */
    public static function joinBatchSku($doship_goods)
    {
        return $doship_goods;
    }

    /**
     * @param      $sku
     * @param      $ware_house
     * @param      $sales_team_code
     * @param      $purchase_team_code
     * @param null $model
     *
     * @return mixed
     */
    public static function appointBatchGet($sku, $ware_house, $sales_team_code, $purchase_team_code, $model = null)
    {
        $M = $model ? $model : M();
        $field = 't1.id,t1.bill_id,t1.SKU_ID,t2.warehouse_id,t4.CD_VAL as warehouse_val,t1.purchase_order_no,t1.batch_code,t2.bill_date,t1.deadline_date_for_use,t1.available_for_sale_num,t3.unit_price,t3.currency_id,t3.currency_time';
        // AND t1.purchase_team_code = '%s'
        $where_str = "t1.bill_id = t2.id AND t1.stream_id = t3.id AND t4.CD = t2.warehouse_id AND t1.available_for_sale_num > 0 AND t4.CD like '%N00068%' AND t1.SKU_ID = '$sku' AND t2.warehouse_id  = '$ware_house' AND t1.sale_team_code IN ( '$sales_team_code','N001281500') ";
        $res = $M->table('tb_wms_batch as t1,tb_wms_bill as t2,tb_wms_stream as t3,tb_ms_cmn_cd as t4')
            ->field($field)
            ->where($where_str)->select();
        return self::currencyAdd($res);
    }

    public static function batchOccupyJoin($data, $order_batch_id)
    {
        Logs($data, '$data', 'b2b');
        $user_id = session('userId');
        $type = 0;
        foreach ($data as $k => $v) {
            $occupy['skuId'] = $v['skuId'];
            $occupy['gudsId'] = substr($occupy['skuId'], 0, -2);
            $occupy['num'] = $v['DELIVERED_NUM'];
            $occupy['operatorId'] = $user_id;
            $occupy['deliveryWarehouse'] = $v['deliveryWarehouse'];
            $occupy['orderId'] = $order_batch_id;
            $occupy['saleTeamCode'] = $v['saleTeamCode'];
            if ($v['batch_id']) $occupy['batchId'] = $v['batch_id'];
            $res['data']['occupy'][] = $occupy;
            $res['data']['type'] = $type;
            unset($occupy);
        }
        return json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    public static function order_batch_id_join($order_id, $doship_id)
    {
        $str = $order_id . '_' . $doship_id . '_' . date('mdHis');
        (strlen_utf8($str) > 32) ? $str = md5($str) : '';
        return $str;
    }

    public static function obj2arr($data)
    {
        return json_decode(json_encode($data), true);
    }

    public static function checkOrder($goods, $order_id)
    {
        $res['state'] = 200;
        return $res;
        /*$M = new Model();
        $res = $M->table('tb_wms_batch_order')->field('CONCAT(delivery_warehouse,SKU_ID,batch_id) as DSB')->where('ORD_ID = \'' . $order_id . '\'')->select();
        $res_arr = array_column($res, 'DSB');
        foreach ($goods as $v) {
            $key = $v['deliveryWarehouse'] . $v['skuId'] . $v['batchId'];
            Logs($key, '$key');
            Logs($v, '$v');
            if (in_array($res_arr, $key)) {
                $res['state'] = 500;
                return $res;
                continue;
            }
        }
        $res['state'] = 200;
        return $res;*/
    }

    /**
     * @param $goods
     * @param $goods_json
     *
     * @return array
     */
    public static function combinationGoods($goods, $goods_json)
    {
        $goods_new = $goods;
        $temp_goods_new = [];
        $Model = M();
        $where_goods['ID'] = array('IN', array_column($goods, 'GOODS_ID'));
        $batch_arr = $Model->table('tb_b2b_goods')
            ->field('ID,SKU_ID,batch_id')
            ->where($where_goods)->select();
        if ($batch_arr) $batch_arr_keyval = array_column($batch_arr, 'batch_id', 'ID');
        foreach ($goods as $k => $v) {
            if ($batch_arr_keyval && $batch_arr_keyval[$v['GOODS_ID']]) {
                $goods_new[$k]['batch_id'] = $batch_arr_keyval[$v['GOODS_ID']];
            }
            if (empty($v['DELIVERED_NUM']) || $v['DELIVERED_NUM'] < 0) {
                unset($goods_new[$k]);
                break;
            } elseif (!empty($goods_json[$v['skuId']]) && is_array($goods_json[$v['skuId']])) {
                foreach ($goods_json[$v['skuId']] as $goods_key => $goods_val) {
                    if ($v['GOODS_ID'] == $goods_key) {
                        $temp_goods = self::goodsJsonToGoods($goods_val, $v);
                        if ($temp_goods) {
                            $temp_goods_new[] = $temp_goods;
                            unset($goods_new[$k]);
                        }
                    }
                }

            }
        }
        foreach ($temp_goods_new as $tem_v) {
            $goods_new = array_merge($tem_v, $goods_new);
        }
        Logs($goods_json, '$goods_json', 'b2b_data');
        Logs($goods, '$goods', 'b2b_data');
        Logs($goods_new, '$goods_new', 'b2b_data');

        return $goods_new;
    }

    /**
     * @param $goods_data
     * @param $order_batch_id
     * @param $M
     *
     * @return mixed
     */
    public static function power_upd($goods_data, $order_batch_id, $M)
    {
        // $res = self::orderData($order_batch_id, $M);
        $res = ApiModel::b2bOrderData($order_batch_id);
        Logs($res, 'res_power');
        if (empty($res)) {
            return [false, $goods_data];
        }
        // $res_unit_price = array_column($res, 'rmb_unit_price', 'SKU_ID');
        foreach ($res as $v) {
            $res_unit_price[$v['SKU_ID'] . $v['delivery_warehouse']] = $v['rmb_unit_price'];
        }
        $goods_arr = $M->table('tb_b2b_ship_list AS t1,tb_b2b_ship_goods AS t2')
            ->field('t1.ID as ship_list_id,t2.ID,t2.SHIPPING_SKU,t2.DELIVERED_NUM,t1.warehouse')
            ->where('t1.order_batch_id = \'' . $order_batch_id . '\' AND t1.ID = t2.SHIP_ID')
            ->select();
        foreach ($goods_arr as $v) {
            $res_sku[$v['ship_list_id'] . $v['SHIPPING_SKU']] = $unit_price = $v['DELIVERED_NUM'] * self::unking($res_unit_price[$v['SHIPPING_SKU'] . $v['warehouse']]);
        }
        Logs($res_sku, '$res_sku');
        if ($res) {
            foreach ($goods_data as &$v) {
                $v['power'] = $res_sku[$v['SHIP_ID'] . $v['SHIPPING_SKU']];
                $save_power['power'] = $v['power'];
                $where_poser['SHIP_ID'] = $v['SHIP_ID'];
                $where_poser['sku_show'] = $v['sku_show'];
                $temp_res = $M->table('tb_b2b_ship_goods')
                    ->where($where_poser)
                    ->save($save_power);
                if (!$temp_res) {
                    return [false, $goods_data];
                }
                $temp_res_arr[] = $temp_res;
            }
        }
        Logs($goods_data, '$goods_data');
        Logs($temp_res_arr, 'temp_res_arr');
        if (count($temp_res_arr) == count($goods_data)) {
            return [true, $goods_data];
        }
        return [false, $goods_data];
    }

    public static function unking($e)
    {
        return str_replace(',', '', $e);
    }


    /**
     * @param $data
     * @param $goods_v
     *
     * @return array
     */
    private static function goodsJsonToGoods($data, $goods_v)
    {
        foreach ($data[$goods_v['deliveryWarehouse']]['batch_s'] as $k => $v) {
            $res['skuId'] = $goods_v['skuId'];
            $res['saleTeamCode'] = $goods_v['saleTeamCode'];
            $res['deliveryWarehouse'] = $goods_v['deliveryWarehouse'];
            $res['availableForSale'] = $goods_v['availableForSale'];
            $res['allAvailableForSale'] = $goods_v['allAvailableForSale'];
            $res['DELIVERED_NUM'] = $v['number'];
            $res['batch_id'] = $v['batch_id'];
            $res_arr[] = $res;
        }
        return $res_arr;
    }

    /**
     * @param $ordId
     *
     * @return array
     * @throws Exception
     */
//    public static function go_batch($order_batch_id, $goods_arr, $order_id, $sale_team = null, $ships_map = [])
//    {
//        $goods = self::outGoodsJoin($goods_arr);
//        $type = 1;
//        foreach ($goods as $k => $v) {
//            $warehouse_status = self::out_online_warehouse(
//                $v,
//                $channel = 'N000830100',
//                $k,
//                $type,
//                $order_id,
//                $order_batch_id,
//                $sale_team,
//                $ships_map);
//            if ($warehouse_status['code'] != 2000) {
//                $return_res = array('info' => $warehouse_status['info'], 'msg' => $warehouse_status['msg'], 'code' => $warehouse_status['code'], "status" => "n");
//                Logs($v, 'out_online_warehouse_data');
//            } else {
//                $return_res = array('info' => '创建发货成功', 'code' => 200, "status" => "y");
//            }
//            $return_arr[] = $return_res;
//        }
//        return $return_res;
//    }

    /**
     * 重构该方法的原因：
     * 如果B2B发货商品SKU大于1，在调用java出库接口是要分多次调用。
     * 如果其中一次调用失败了，已经生成的出库记录无法撤回，所以改成一次出库只调用一次出库接口
     * @param $order_batch_id
     * @param $goods_arr
     * @param $order_id
     * @param null $sale_team
     * @param array $ships_map
     * @return array
     */
    public static function go_batch($order_batch_id, $goods_arr, $order_id, $sale_team = null, $ships_map = [])
    {
        $goods = self::outGoodsJoin($goods_arr);
        $warehouse_status = self::out_online_warehouse($goods,$order_id,$order_batch_id,$sale_team,$ships_map);
        if ($warehouse_status['code'] != 2000) {
            $return_res = array('info' => $warehouse_status['info'], 'msg' => $warehouse_status['msg'], 'code' => $warehouse_status['code'], "status" => "n");
        } else {
            $return_res = array('info' => '创建发货成功', 'code' => 200, "status" => "y");
        }
        return $return_res;
    }

    public static function outGoodsJoin($goods_arr)
    {
        foreach ($goods_arr as $v) {
            if (empty($v['DELIVERED_NUM']) || $v['DELIVERED_NUM'] <= 0) {
                continue;
            }
            $gud['GSKU'] = $v['skuId'];  // GUDS_OPT_ID
            $gud['taxes'] = 0;
            $gud['send_num'] = $gud['should_num'] = $v['DELIVERED_NUM']; // ORD_GUDS_QTY
            $gud['rmb_unit_price'] = 0; // 未占用无数据
            $gud['currency_id'] = 'N000590300';
            $gud['currency_time'] = date('Y-m-d H:i:s');
            $guds[$v['deliveryWarehouse']][] = $gud;
        }
        return $guds;
    }

    /**
     * @param     $ordId
     * @param int $user_type
     *
     * @return mixed
     */
    public static function orderData($ordId, $M = null)
    {
        if (empty($M)) {
            $M = M();
        }
        $field = 't3.GSKU AS SKU_ID,t3.send_num AS occupy_num,t1.warehouse_id AS delivery_warehouse,t3.unit_price,t3.unit_price AS rmb_unit_price,t3.unit_price_usd,t3.currency_id,t3.currency_time,t1.link_bill_id AS ORD_ID';
        $res_unit_price = $M->table('tb_wms_bill as t1,tb_wms_stream as t3')
            ->field($field)
            ->where('t1.id = t3.bill_id AND t1.link_bill_id = \'' . $ordId . '\'')
            ->select();
        return $res_unit_price;
    }


    private static function batchGoodsJoin($data_arr, $goods_arr)
    {
        $goods_arr_val = array_column($goods_arr, 'skuId', 'DELIVERED_NUM');
        foreach ($data_arr as $v) {
            $gud['GSKU'] = $v['SKU_ID'];  // GUDS_OPT_ID
            $gud['taxes'] = 0;
            $gud['send_num'] = $gud['should_num'] = $goods_arr_val[$v['SKU_ID']]; // ORD_GUDS_QTY
            if (empty($goods_arr_val[$v['SKU_ID']]) || $goods_arr_val[$v['SKU_ID']] <= 0) {
                continue;
            }
            if (empty($v['rmb_unit_price'])) {
                $gud['price'] = $v['unit_price'];// 单价 RMB_PRICE
                if (strlen($v['currency_id']) == 3) {
                    $state_arr = array_flip(ExchangeRateModel::getAllKeyValue());
                    if ($state_arr[$gud['currency_id']]) {
                        $gud['currency_id'] = $state_arr[$gud['currency_id']];
                    }
                } else {
                    $gud['currency_id'] = $v['currency_id'];// 币种
                }
            } else {
                $gud['price'] = $v['rmb_unit_price'];// 单价 RMB_PRICE
                $gud['currency_id'] = 'N000590300';// 币种
            }

            $gud['currency_time'] = $v['currency_time'];
            if (!$gud['currency_time']) $gud['currency_time'] = date('Y-m-d H:i:s');
            $guds[$v['delivery_warehouse']][] = $gud;
        }
        return $guds;
    }

    /**
     * @param $goods_arr
     * @param $sku_key
     *
     * @return array
     */
    public static function goodsImgAdd($goods_arr, $sku_key)
    {
        return $goods_arr;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function gatheringReturn($data)
    {
        $model = M();
        $where_arr['ID'] = $data['id'];
        $save_data['return_user'] = session('m_loginname');
        $save_data['return_msg'] = $data['value'];
        $save_data['return_time'] = date('Y-m-d H:i:s');
        $save_data['return_state'] = 1;
        return $model->table('tb_b2b_receipt')->where($where_arr)->save($save_data);
    }

    /**
     * @param $data
     * @param $message
     */
    public static function receivableRemindMailSend($data, $message)
    {
        $data_temp['title'] = 'East B2B - ' . $data['PO_USER'] . ' 收款信息待确认';
        $data_temp['message'] = sprintf($message, $data['PO_ID'] . '-' . $data['ID'], count($data['all_node']), self::toNode($data['receiving_code'], true), $data['collect_this'], $data['actual_payment_amount'], $data['CLIENT_NAME'], $data['remarks'], session('m_loginname'), date('Y-m-d H:i:s'));
        $data_temp = self::userCcJoin($data, $data_temp);
        Logs(json_encode($data_temp, JSON_UNESCAPED_UNICODE), 'Remind_b2b_receivable', 'mail_b2b');
        Logs(json_encode($data, JSON_UNESCAPED_UNICODE), 'Remind_data', 'mail_b2b');
        $res = MailModel::receivableRemindMailSend($data_temp);
        Logs($res, 'res', 'mail_b2b');
    }

    /**
     * @param $return_data
     * @param $message
     */
    public static function receivableReturnMailSend($return_data, $message)
    {
        $data = $return_data['gathering'];
        $data_temp['title'] = 'East B2B - ' . $data['PO_USER'] . ' 收款信息被退回';
        $data_temp['message'] = sprintf($message, $data['PO_ID'] . '-' . $data['ID'], count($data['all_node']), self::toNode($data['receiving_code'], true), $data['collect_this'], $data['actual_payment_amount'], $data['CLIENT_NAME'], $data['remarks'], session('m_loginname'), date('Y-m-d H:i:s'), $return_data['value']);
        $sale_leader_str = self::get_code_cd($data['sales_team_id'], false, true);
        $data_temp['cc'] = [$data['operator_id'] . '@gshopper.com', $data['PO_USER'] . '@gshopper.com'];
        $data_temp['cc'] = array_unique($data_temp['cc']);
        $data_temp['user'] = $sale_leader_str[$data['sales_team_id']]['ETc'];
        Logs($data, 'Return_data', 'mail_b2b');
        Logs($return_data, 'Return_return_data', 'mail_b2b');
        Logs($data_temp, 'Return_b2b_receivable', 'mail_b2b');
        MailModel::receivableRemindMailSend($data_temp);
    }

    /**
     * @param $order_id
     * @param $main_id
     * @param $Model
     *
     * @return bool
     */
    public static function mainOrderStateUpd($order_id, $main_id, $Model)
    {
        $where['main_id'] = $main_id;
        $where['order_id'] = $order_id;
        $field = 'unconfirmed_state,receipt_operation_status';
        $receipt_all = $Model->table('tb_b2b_receipt')
            ->field($field)
            ->where($where)
            ->select();
        $un_state_arr = 0;
        foreach ($receipt_all as $k => $v) {
            if (1 == $v['unconfirmed_state'] && 1 == $v['receipt_operation_status']) {
                $un_state_arr++;
            }
        }
        if ($un_state_arr > 0) {
            $main_receipt_operation_status = 2;
        }
        if ($un_state_arr > 0 && $un_state_arr == count($receipt_all)) {
            $main_receipt_operation_status = 1;
        }
        if ($main_receipt_operation_status) {
            $save['main_receipt_operation_status'] = $main_receipt_operation_status;
            $where_main['ID'] = $main_id;
            $res = $Model->table('tb_b2b_receipt')
                ->where($where_main)
                ->save($save);
            Logs($res, 'mainOrderStateUpd', 'b2b');
            return true;
        }
        return false;
    }

    /**
     * @param $order_id
     * @param $Model
     *
     * @return bool
     */
    public static function orderStateUpd($order_id, $Model)
    {
        $where['order_id'] = $order_id;
        $where['P_ID'] = array('EXP', 'IS NULL');
        $where['transaction_type'] = array('NEQ', 1);
        $field = 'main_receipt_operation_status';
        $receipt_all = $Model->table('tb_b2b_receipt')
            ->field($field)
            ->where($where)
            ->select();
        $array_sum = array_sum(array_values($receipt_all));
        if ($array_sum > 0) {
            $receipt_state = 1;
        }
        if ($array_sum > 0 && count($receipt_all) == array_sum(array_values($receipt_all))) {
            $receipt_state = 2;
        }
        if ($receipt_state > 0) {
            $save['receipt_state'] = $receipt_state;
            $where_order['ID'] = $order_id;
            $res = $Model->table('tb_b2b_order')->where($where_order)->save($save);
            Logs($res, 'OrderStateUpd', 'b2b');
            return true;
        }
        return false;
    }

    public function orderOverdueStatueUpd($order_id, $Model)
    {

    }

    /**
     * @param $order_id
     * @param $data
     * @param $model
     *
     * @return array
     */
    public static function receiptOrderEnd($order_id, $data, $model)
    {
        $save['cost_delivery'] = $data['cost_delivery'];
        $save['logistics_costs'] = $data['logistics_costs'];
        $save['recoverable_amount'] = $data['recoverable_amount'];
        $profit_res = $model->table('tb_b2b_profit')->where('ORDER_ID = ' . $order_id)->save($save);
        $save_sale['sale_tax'] = $data['sale_tax'];
        $info_res = $model->table('tb_b2b_info')->where('ORDER_ID = ' . $order_id)->save($save_sale);
        return [$profit_res, $info_res];
    }

    /**
     * @param      $po_id
     * @param null $Model
     *
     * @return mixed
     */
    public static function po2OrderId($po_id, $Model = null)
    {
        $where['PO_ID'] = $po_id;
        return $Model->table('tb_b2b_order')->where($where)->getField('id');
    }
}
