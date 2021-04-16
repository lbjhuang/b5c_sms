<?php

/**
 * User: yangsu
 * Date: 17/11/16
 * Time: 11:00
 */
class OrdersModel extends Model
{
    public static $pre = 'delivery_warehouse-';
    public static $GAPP_CD_ARR = [
        'N000831400',
        'N000834100',
        'N000834200',
        'N000834300'];

    /**
     * @param      $result
     * @param null $Model
     *
     * @return array
     */
    public static function get_all_batch_plat_warehouse($result, $Model = null)
    {
        if (!$Model) $Model = M();
        $all_order_id = array_column($result, 'ORDER_ID');
        $field_order = ['t1.ORDER_ID', 't1.B5C_SKU_ID', 't2.PLAT_CD', 'CONCAT_WS(\'&saleTeamCode=\',t1.B5C_SKU_ID,t3.SALE_TEAM_CD) as SKU_PLAT'];
        $where_order['t1.ORDER_ID'] = array('in', $all_order_id);
        $where_order['t1.B5C_SKU_ID'] = array('exp', 'is not null');
        $guds_all_arr = $Model->table('tb_op_order_guds t1,tb_op_order t2,tb_ms_store t3')->field($field_order)->where($where_order)->where('t1.ORDER_ID = t2.ORDER_ID AND t2.STORE_ID = t3.ID')->select();
        $sku_all_arr = array_column($guds_all_arr, 'SKU_PLAT');
        $guds_list_arr = [];
        Logs($guds_all_arr, '$guds_all_arr', 'ord');
        foreach ($guds_all_arr as $v) {
            $guds_list_arr[$v['ORDER_ID']][] = $v['SKU_PLAT'];
        }
        $sku_uni_arr = array_unique(array_filter($sku_all_arr));
        $warehouse_arr = RedisModel::get_key_list_api(self::addEsPrefix($sku_all_arr));
        $warehouses_orders_arr = self::warehouse_order_check($sku_uni_arr, $warehouse_arr, $guds_list_arr, $sku_all_arr);
        if (!$warehouse_arr || !$warehouses_orders_arr) {
            Logs($warehouse_arr, 'warehouse_arr', 'ord');
            Logs($warehouses_orders_arr, 'warehouses_orders_arr', 'ord');
        }
        return $warehouses_orders_arr;
    }

    /**
     * @param $e
     *
     * @return array
     */
    public static function addPrefix($e)
    {
        $func = function ($e) {
            return self::$pre . $e;
        };
        return array_map($func, $e);
    }

    /**
     * @param $e
     *
     * @return array
     */
    public static function addEsPrefix($e)
    {
        $func = function ($e) {
            return $e;
        };
        return array_map($func, $e);
    }

    /**
     * @param $sku_pre_arr
     * @param $warehouse_arr
     * @param $guds_orders_arr
     *
     * @return array
     */
    public static function warehouse_order_check($sku_pre_arr, $warehouse_arr, $guds_orders_arr, $sku_all_arr)
    {


        $sku_pre_flip_arr = array_flip($sku_pre_arr);
        $show_warehouse_arr = $du_arr = $arr = [];
        foreach ($guds_orders_arr as $key => $val) {
            foreach ($val as $v) {
//                $arr[$key][] = json_decode($warehouse_arr[$sku_pre_flip_arr[$v]], true);
                $arr[$key][] = $warehouse_arr[$sku_pre_flip_arr[$v]];
            }
            $reduce_arr[$key] = array_reduce($arr[$key], 'array_merge_recursive', []);
            $du_arr[$key] = array_count_values($reduce_arr[$key]);
        }
        $count_arr = array_map("count", $guds_orders_arr);
        $func = function ($e) {
            return max(array_values($e));
        };
        $having_warehouse_arr = array_map($func, $du_arr);
        foreach ($having_warehouse_arr as $key => $value) {
            if ($having_warehouse_arr[$key] == $count_arr[$key]) {
                foreach ($du_arr[$key] as $k => $v) {
                    if ($value == $v) {
                        $show_warehouse_arr[$key][] = $k;
                    }
                }
            }
        }
        return $show_warehouse_arr;
    }

    /**
     * @param $order_arr
     * @param $enabled_plat_cd
     * @param $order_arr_rep
     * @param $order_arr_rep
     * @param $err_arrs
     *
     * @return array
     */
    public static function build_order_data_join($order_arr, $enabled_plat_cd, $err_arrs)
    {
        $order_Val_arr = array_column($order_arr, 'warehouse');
        if (!$order_Val_arr) {
            Logs($order_Val_arr, '$order_Val_arr');
            return [[], [], [], $err_arrs];
        }
        $order_id_arr = array_column($order_arr, 'order_id');
        $order_keyVal_arr = array_column($order_arr, 'warehouse', 'order_id');
        $Model = M();
//         AND t5.B5C_SWITCH = '1'
        $field = 't1.COUPONS_ID,t1.STORE_ID,t1.ID,t1.ORDER_ID,t1.ORDER_NO,t1.PLAT_CD,t1.PLAT_NAME,t1.SITE,t1.SHOP_ID,t1.ORDER_UPDATE_TIME,t1.ORDER_TIME,t1.ORDER_PAY_TIME,t1.ORDER_CREATE_TIME,t1.USER_ID,t1.USER_NAME,t1.USER_EMAIL,t1.ADDRESS_USER_NAME,t1.ADDRESS_USER_PHONE,t1.ADDRESS_USER_COUNTRY,t1.ADDRESS_USER_COUNTRY_CODE,t1.ADDRESS_USER_COUNTRY_ID,t1.ADDRESS_USER_CITY,t1.ADDRESS_USER_PROVINCES,t1.ADDRESS_USER_REGION,t1.ADDRESS_USER_ADDRESS1,t1.ADDRESS_USER_ADDRESS2,t1.ADDRESS_USER_ADDRESS3,t1.ADDRESS_USER_ADDRESS4,t1.ADDRESS_USER_ADDRESS5,t1.ADDRESS_USER_POST_CODE,t1.PAY_CURRENCY,t1.PAY_ITEM_PRICE,t1.PAY_TOTAL_PRICE,t1.PAY_SHIPING_PRICE,t1.PAY_VOUCHER_AMOUNT,t1.PAY_VOUCHER_AMOUNT,t1.PAY_PRICE,t1.PAY_METHOD,t1.PAY_TRANSACTION_ID,t1.SHIPPING_TYPE,t1.SHIPPING_DELIVERY_COMPANY,t1.SHIPPING_TRACKING_CODE,t1.SHIPPING_MSG,t1.CRAWLER_DATE,t1.UPDATE_TIME,t1.ORDER_STATUS,t1.ORDER_NUMBER,t1.ADDRESS_USER_ADDRESS_MSG,t1.BWC_ORDER_STATUS,t1.BWC_USER_ID,t1.B5C_ACCOUNT_ID,t1.SHORT_SUPPLY,t1.B5C_ORDER_DES_COUNT,t1.RECEIVER_TEL,t1.BUYER_TEL,t1.BUYER_MOBILE,t1.TARIFF,t2.ITEM_COUNT,t2.B5C_SKU_ID,t2.ITEM_PRICE,t2.GAPP_SKU_ID,t5.USER_ID as STORE_USER_ID';
        if ($enabled_plat_cd) {
            $order_where = self::orderMsWhereJoin($order_arr);
        } else {
            $order_where = self::orderOlderWhereJoin($order_id_arr);
        }
        $order_db_arr = $Model->table('(tb_op_order t1,tb_op_order_guds t2,' . PMS_DATABASE . '.product_sku t4,tb_ms_store t5)')
            ->field($field)
            ->where('t1.STORE_ID = t5.id  AND t1.ORDER_ID = t2.ORDER_ID  AND t2.B5C_SKU_ID = t4.sku_id AND (t1.B5C_ORDER_NO IS NULL OR LENGTH(t1.B5C_ORDER_NO) = 0)AND t2.B5C_SKU_ID IS NOT NULL  AND (' . $order_where . ')')
            ->join('left join tb_wms_center_stock AS t3 on t2.B5C_SKU_ID = t3.SKU_ID')
            ->order('t1.B5C_ORDER_DES_COUNT ASC')
            ->select();
        $error_order_data = null;
        if (!$order_db_arr || count($order_db_arr) != count($order_arr)) {
            $all_order_data = $Model->table('tb_op_order t1')
                ->field('t1.STORE_ID,t1.B5C_ORDER_NO,t1.ORDER_PAY_TIME,t2.B5C_SKU_ID, t3.SKU_ID,t4.sku_id as GUDS_OPT_ID,t5.id,t1.ORDER_ID')
                ->join('left join tb_op_order_guds AS t2 on t1.ORDER_ID = t2.ORDER_ID')
                ->join('left join tb_wms_center_stock AS t3 on t2.B5C_SKU_ID = t3.SKU_ID')
                ->join('left join ' . PMS_DATABASE . '.product_sku AS t4 on t2.B5C_SKU_ID = t4.sku_id')
                ->join('left join tb_ms_store AS t5 on t1.STORE_ID = t5.id')
                ->where($order_where)
                ->select();
            $order_db_arr_key = array_column($order_db_arr, 'ORDER_ID');
            foreach ($all_order_data as $k => $v) {
                if (!in_array($v['ORDER_ID'], $order_db_arr_key)) {
                    if ($v['B5C_ORDER_NO']) {
                        $err_msg['orderId'] = $v['ORDER_ID'];
                        $err_msg['msg'] = '订单已生成';
                    } elseif (!$v['GUDS_OPT_ID']) {
                        $err_msg['orderId'] = $v['ORDER_ID'];
                        $err_msg['msg'] = '订单商品信息缺失';
                    } elseif (!$v['B5C_SKU_ID']) {
                        $err_msg['orderId'] = $v['ORDER_ID'];
                        $err_msg['msg'] = '订单 SKU 缺失';
                    } else {
                        $err_msg['orderId'] = $v['ORDER_ID'];
                        $err_msg['msg'] = '订单相关数据缺失';
                    }

                    $err_arrs[] = $err_msg;
                }
            }
            if (!$order_db_arr) {
                return [false, $error_order_data, [], $err_arrs];
            }
        }
        if ($enabled_plat_cd) {
            $order_db_arr = self::filterPatchOrders($order_db_arr, $order_arr);
        }
        foreach ($order_db_arr as $key => $value) {
            $order_new_arr[$value['ORDER_ID']][] = $value;
        }
        $order_db_key_arr = array_column($order_db_arr, 'ORDER_ID');
        $data_tmp = [];
        $order_un_key_arr = array_unique($order_db_key_arr);
        $user_id = DataModel::userId();
        foreach ($order_un_key_arr as $key => $value) {
            $order_tpm = self::joinOrderPatchData($order_new_arr[$value], $user_id);
            $data_tmp[] = $order_tpm;
        }
        return [$data_tmp, $error_order_data, [], $err_arrs];
    }

    public static function filterPatchOrders($order_db_arr, $order_arr)
    {
//        Logs($order_db_arr, 'order_db_arr');
        $order_plat_key_arr = array_column($order_arr, 'order_plat_key');
        foreach ($order_db_arr as &$value) {
            if (!in_array($value['ORDER_ID'] . '_' . $value['PLAT_CD'], $order_plat_key_arr)) {
                unset($value);
            }
        }
        return $order_db_arr;
    }

    private static function joinOrderPatchData($orders, $user_id = null)
    {
        $order_tpm = new stdClass();
        $order = $orders[0];
        $order_tpm->platform = $order['PLAT_CD'];
        $order_tpm->thirdOrderId = $order['ORDER_ID'];
        $order_tpm->operatorId = $user_id;
        return $order_tpm;
    }


    /**
     * @param $orders
     * @param $order_keyVal_arr
     * @param $curreny_code_arr
     *
     * @return stdClass
     */
    private static function join_order_list_data($orders, $order_keyVal_arr, $curreny_code_arr)
    {
        $order_tpm = new stdClass();
        $order = $orders[0];
        $order_tpm->platform = $order['PLAT_CD'];
        $order_tpm->payAmount = $order['PAY_TOTAL_PRICE'];
        $order_tpm->reqCont = $order['SHIPPING_MSG'];
        $goods_tpm = new stdClass();
        foreach ($orders as $value) {
            $order_tpm->goods[] = self::join_goods($value, $order_keyVal_arr[$order['ORDER_ID']], $order['PLAT_CD']);
        }
        $order_tpm->addrDstr = $order['ADDRESS_USER_REGION'];
        $order_tpm->addrCty = $order['ADDRESS_USER_CITY'];
        $joinAddress = self::joinAddress($order);
        $order_tpm->addrDtl = $joinAddress;
        $order_tpm->payDttm = $order['ORDER_PAY_TIME'];
        $order_tpm->orderTime = $order['ORDER_TIME'];
        $order_tpm->discountMn = $order['PAY_VOUCHER_AMOUNT'];
        $order_tpm->ordType = 'N000620400'; // 自营
        $order_tpm->recrNm = $order['ADDRESS_USER_NAME'];
        $order_tpm->dlvAmt = $order['PAY_SHIPING_PRICE'];
        $order_tpm->thirdOrdId = $order['ORDER_ID'];
        $order_tpm->tariff = ($order['TARIFF']) ? $order['TARIFF'] : 0; // TARIFF
        $order_tpm->recrTel = $order['ADDRESS_USER_PHONE'];
        $order_tpm->b5cUserId = $order['STORE_USER_ID'];
        if (!empty($order['PAY_CURRENCY'])) {
            ($order['PAY_CURRENCY'] == 'RMB') ? $order['PAY_CURRENCY'] = 'CNY' : '';
            $order_tpm->currencyCd = $curreny_code_arr[$order['PAY_CURRENCY']];
        }
        $order_tpm->addrPrvn = $order['ADDRESS_USER_PROVINCES'];
        $order_tpm->ordStatCd = $order['BWC_ORDER_STATUS'];
//      add order province msg
        $order_tpm->province = $order['ADDRESS_USER_PROVINCES'];
        $order_tpm->city = $order['ADDRESS_USER_CITY'];
        $order_tpm->area = $order['ADDRESS_USER_REGION'];
        $order_tpm->countryCode = $order['ADDRESS_USER_COUNTRY_CODE'];
        $order_tpm->countryId = $order['ADDRESS_USER_COUNTRY_ID'];

        $order_tpm->addressDetail = $joinAddress;
        $order_tpm->coupons = $order['COUPONS_ID'];

        $order_tpm->thirdParentOrderId = $order['PARENT_ORDER_ID'];
        $order_tpm->logisticModelId = $order['logistic_model_id'];
        // $order_tpm->postageModelId = self::postageModelIdGet($order['ORDER_ID'],$order['PLAT_CD']);
        $order_tpm->postageModelId = null;
        return $order_tpm;
    }


    /**
     * @param $order
     *
     * @return mixed
     */
    public static function joinAddress($order)
    {
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($order['ADDRESS_USER_ADDRESS' . $i])) {
                $res = $order['ADDRESS_USER_ADDRESS' . $i];
                break;
            }
        }
        return $res;
    }

    /**
     * @param $goods_tpms
     * @param $deliveryWarehouse
     * @param $PLAT_CD
     *
     * @return stdClass
     */
    private
    static function join_goods($goods_tpms, $deliveryWarehouse, $PLAT_CD)
    {
        $goods_tpm = new stdClass();
        $goods_tpm->gudsQty = (int)$goods_tpms['ITEM_COUNT'];
        $goods_tpm->skuId = $goods_tpms['B5C_SKU_ID'];
        $goods_tpm->rmbPrice = $goods_tpms['ITEM_PRICE'];
        $goods_tpm->deliveryWarehouse = $deliveryWarehouse;
        if (in_array($PLAT_CD, self::$GAPP_CD_ARR)) {
            $goods_tpm->thrdSkuId = $goods_tpms['GAPP_SKU_ID'];
        }
        return $goods_tpm;
    }

    /**
     * @param $order_arr
     *
     * @return string
     */
    private static function orderMsWhereJoin($order_arr)
    {
        $order_where = self::initWhere();
        foreach ($order_arr as $k => $v) {
            $order_where .= " OR (t1.ORDER_ID = '" . $v['order_id'] . "' AND t1.PLAT_CD = '" . $v['plat_cd'] . "')  ";
        }
        return $order_where;
    }

    private static function orderOlderWhereJoin($order_id_arr)
    {
        $where_str = null;
        foreach ($order_id_arr as $k => $v) {
            $where_str .= "'$v',";
        }
        $where_str = trim($where_str, ',');
        $order_where = "t1.ORDER_ID IN ($where_str)";
        return $order_where;
    }

    /**
     * @param $order_arr
     * @param $order_db_arr
     * @param $order_id_arr
     * @param $err_msg
     *
     * @return array|null
     */
    public static function noMsOrderCheck($order_arr, $order_db_arr, $order_id_arr)
    {
        $err_no_order_arrs = null;
        if (count($order_arr) != count($order_db_arr)) {
            $err_no_order_arr = array_diff($order_id_arr, array_column($order_db_arr, 'ORDER_ID'));
            if ($err_no_order_arr) {
                foreach ($err_no_order_arr as $value) {
                    $err_msg['orderId'] = $value;
                    $err_msg['msg'] = '订单缺失';
                }
            }
            $err_no_order_arrs[] = $err_msg;
        }
        return $err_no_order_arrs;
    }


    /**
     * @param $data
     *
     * @return mixed
     */
    public function fetch_data_push($data)
    {
        $host_url = HOST_URL;
        if ($host_url == 'HOST_URL') $host_url = 'http://b5caiapi.stage.com';
        $url = $host_url . '/third_order/save.json';
        $Stock = A('Home/Stock');
        $res_json = $Stock->Curl_post($url, json_encode($data));
        $res = json_decode($res_json, true);
        if ($res['code'] != 2000) {
            Logs($data, 'fetch_data_push', 'patch_data');
            Logs($url, 'url_fetch', 'patch_data');
        }
        return $res;
    }

    public function erpOrderDispatch($data)
    {
        $host_url = HOST_URL;
        if ($host_url == 'HOST_URL') $host_url = 'http://b5caiapi.stage.com';
        $url = $host_url . 'erp_order/dispatch.json';
        $Stock = A('Home/Stock');
        $res_json = $Stock->Curl_post($url, json_encode($data));
        $res = json_decode($res_json, true);
        if ($res['code'] != 2000) {
            Logs($data, 'fetch_data_push', 'patch_data');
            Logs($url, 'url_fetch', 'patch_data');
        }
        return $res;
    }


    /**
     * Test Order Like 10181%
     * Test Order Id 101813598
     *
     * @return array
     */
    public static function test()
    {


    }

    /**
     * @param $ord_arr
     * @param $model
     *
     * @return array
     */
    public static function go_all_batch($ord_arr, $model)
    {
        $Model = M();
        $field = ['t1.ORD_ID', 'PLAT_FORM', 't1.DELIVERY_WAREHOUSE', 'THIRD_ORDER_ID', 't2.delivery_warehouse AS WAREHOUSE'];
        $where['t1.ORD_ID'] = array('in', $ord_arr);
//        $where['t1.ORD_ID'] = array('exp', '= t2.ORD_ID ');
        $where['t2.delivery_warehouse'] = array('exp', 'IS NOT NULL');
        $msord_arr = $Model->table('tb_ms_ord AS t1, tb_wms_batch_order AS t2')->field($field)->where('t1.ORD_ID = t2.ORD_ID ')->where($where)->group('t2.ORD_ID')->select();
        $goods_arr = self::join_all_batch_goods($ord_arr);
        if (empty($msord_arr) || empty($goods_arr)) {
            $return_arr = array('info' => '订单商品信息缺失', 'code' => 40001, "status" => "n");
            Logs($msord_arr, '$msord_arr');
        } else {
            $warehouse_status = B2bModel::out_online_all_warehouse($msord_arr, $goods_arr, $model);
            if ($warehouse_status['code'] != 2000) {
                $msg = $warehouse_status['code'] . $warehouse_status['msg'] . json_encode($warehouse_status['info']);
                $return_arr = array('info' => $msg, 'code' => $warehouse_status['code'], "status" => "n");
            } else {
                $return_arr = array('info' => '创建发货成功', 'code' => 200, "status" => "y");
            }
        }
        return $return_arr;
    }

    /**
     * 商品
     *
     * @param $ord_arr
     *
     * @return mixed
     */
    private function join_all_batch_goods($ord_arr)
    {
        $Guds = M('ms_ord_guds_opt', 'tb_');
        $gudField = 'ORD_ID,GUDS_OPT_ID,ORD_GUDS_QTY,ORD_GUDS_QTY,RMB_PRICE';
        $gudWhere['ORD_ID'] = array('in', $ord_arr);
        $gud_list = $Guds->field($gudField)->where($gudWhere)->select();
        $date = date('Y-m-d H:i:s');
        foreach ($gud_list as $key => $value) {
            unset($gud);
            $gud['GSKU'] = $value['GUDS_OPT_ID'];  // GUDS_OPT_ID
            $gud['taxes'] = 0;
            $gud['should_num'] = $value['ORD_GUDS_QTY']; // ORD_GUDS_QTY
            $gud['send_num'] = $value['ORD_GUDS_QTY'];
            $gud['price'] = $value['RMB_PRICE'];// 单价 RMB_PRICE
            $gud['currency_id'] = 'N000590300';// 币种
            $gud['currency_time'] = $date;
            $guds_arr[$value['ORD_ID']][] = $gud;
        }
        return $guds_arr;
    }

    public static function orderStatus_upd($ordlist, $CLOSE_TYPE)
    {

    }

    public static function join_close_order($ordlist)
    {

    }

    /**
     * close third party order
     *
     * @param $thrOrd_str
     *
     * @return mixed
     */

    public static function close_order_thr($thrOrd_str)
    {
        $model = M();
        $save['BWC_ORDER_STATUS'] = 'N000550900';
        $status_close_thrOrder = $model->table('tb_op_order')->where('ORDER_ID in (' . $thrOrd_str . ')')->save($save);
        return $status_close_thrOrder;
    }

    /**
     * clean order occupy
     */
    public static function cancel_order($order_arr)
    {
        $data = self::join_cancel_data($order_arr);
        if (empty($data)) {
            $res['code'] = 40101;
            return $res;
        }
        $Stock = A('HOME/Stock');
        $host_url = HOST_URL;
        if ($host_url == 'HOST_URL') $host_url = 'http://b5caiapi.stage.com';
        $url = $host_url . '/batch/release_occupy.json';
        $res_json = $Stock->Curl_post($url, json_encode($data));
        $res = json_decode($res_json, true);
        if ($res['code'] != 2000) {
            Logs($data, 'data_cancel');
            Logs(json_encode($data), 'json_encode(data)');
            Logs($res_json, 'res_json');
            Logs($url, 'url_cancel');
        }
        return $res;
    }

    /**
     *
     */
    public static function join_cancel_data($get_data)
    {
        $req_data = new stdClass();
        $ordInfo = new stdClass();
        $req_data->data = new stdClass();
        foreach ($get_data as $v) {
            unset($ordInfo);
            $ordInfo->gudsId = $v['gudsId'];
            $ordInfo->skuId = $v['skuId'];
            $ordInfo->orderId = $v['orderId'];
            $req_data->data->release[] = $ordInfo;
        }
        return $req_data;
    }

    /**
     * one dimensional array to match
     *
     * @param $arr
     * @param $key
     *
     * @return mixed
     */
    public static function one2match_arr($arr, $key)
    {
        foreach ($arr as $v) {
            $match_arr[$v[$key]][] = $v;
        }
        return $match_arr;
    }

    /**
     * @param $status_arr
     *
     * @return mixed
     */
    public static function get_statusOrd($status_arr)
    {
        $M = M();
        $status_where['t1.ORD_STAT_CD'] = array('in', $status_arr);
        $res = $M->table('tb_ms_ord AS t1,	tb_wms_batch_order AS t2')->field('t1.ORD_ID')->where('t1.ORD_ID = t2.ORD_ID AND t2.use_type = 1')->where($status_where)->select();
        return $res;
    }

    public static function initWhere()
    {
        return '1 != 1';
    }


}