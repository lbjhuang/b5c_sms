<?php
// 
// 
// 
// 
// 
// 
// 
// 
// 
import("@.ToolClass.B2b.B2bData");

/**
 * BaseCommon 类
 *
 * @category
 * @package
 * @subpackage
 * @author    huaxin
 */
class BaseCommon
{

    /**
     *  Check some for po
     *
     */
    static public function checkPo($poData, $poObj)
    {
        $poData = is_array($poData) ? $poData : array();
        $poData['is_error'] = 0;
        // 退税金额（币种和商品含税成本保持一致，不可选择）
        $poData['cur_tuishui'] = $poData['backend_currency'];
        // 销售端应缴税
        $poData['cur_saletax'] = $poObj->cur_saletax;
        // 其他收入：币种和PO金额保持一致，不可选择
        $poData['cur_other'] = $poData['po_currency'];
        // 销售端应缴税
        $poData['sale_tax'] = isset($poObj->sale_tax) ? $poObj->sale_tax : null;
        $poData['sale_tax'] = ZFun::keepNumber($poData['sale_tax']);

        return $poData;
    }

    /**
     *  Check fahuo
     *
     */
    static public function chk_fahuo_type($fahuofangshi)
    {
        $fahuo_type = null;
        //DOMESTIC
        $checkArr = array('N001530500', 'N001530700', 'N001530900');
        if ($fahuofangshi) {
            $fahuo_type = 0;
            if (in_array($fahuofangshi, $checkArr)) {
                $fahuo_type = 1;
            }
        }
        return $fahuo_type;
    }

    /**
     *  Change price by rate
     *  PS:
     *   cur_from is dict cur
     *   cur_to is cur
     */
    static public function price_by_rate($price, $cur_from, $cur_to, $day)
    {
        $rate = 1;
        if ($cur_from == $cur_to) {
            return $price * $rate;
        }
        if (!isset($day)) {
            $day = date('Y-m-d');
        }
        $rate = B2bModel::update_currency($cur_from, $day, $cur_to);
        return $price * $rate;
    }

    static public function price_cur_rate($price, $cur_from, $cur_to, $day)
    {
        trace($price, '$price');
        trace($cur_from, '$cur_from');
        trace($day, '$day');
        $rate = 1;
        if ($cur_from == $cur_to) {
            return $price * $rate;
        }
        if (!isset($day)) {
            $day = date('Y-m-d');
        }
        //check cur
        if (strlen($cur_from) > 6) {
            $items = D("ZZmscmncd")->getValueFromPrev('N00059');
            $cur_from = isset($items[$cur_from]) ? $items[$cur_from] : '';
        }
        if (strlen($cur_to) > 6) {
            $items = D("ZZmscmncd")->getValueFromPrev('N00059');
            $cur_to = isset($items[$cur_to]) ? $items[$cur_to] : '';
        }
        if (empty($cur_from) or empty($cur_to)) {
            return $price * $rate;
        }
        if ($cur_from == $cur_to) {
            return $price * $rate;
        }
        Logs($day, '$day');
        $rate = self::api_currency($cur_from, $day, $cur_to);
        Logs($rate, '$rate');
        //check 1 or not
        if ($rate == 1) {
        }
        return $price * $rate;
    }

    /**
     *
     *
     */
    public static function api_currency($currency, $date, $dst_currency = 'USD')
    {
        // $insight = INSIGHT;
        if (empty($date) || '1970-01-01' == $date || empty($currency)) {
            return 0;
        }
        $date = date('Y-m-d', strtotime($date));
        $url = BI_API_REVEN.'/external/exchangeRate?date=' . $date . '&dst_currency=' . $dst_currency . '&src_currency=' . $currency;

        $curl_request = curl_request($url);
        $currency = @json_decode($curl_request, 1);
        Logs($url, 'url', 'api_currency');
        Logs($curl_request, 'curl_request', 'api_currency');
        if (empty($currency['data'][0]['rate'])) {
            return 0;
        } else {
            return $currency['data'][0]['rate'];
        }
    }

    /**
     *  Calculate the forecast
     *
     *  Need :
     *      ( [/] not must [*] must )
     *      fahuofangshi
     *      day                 /
     *      backend_currency
     *      backend_estimat
     *      cur_tuishui
     *      drawback_estimate
     *      poAmountBz
     *      poAmount
     *      cur_saletax
     *      sale_tax
     *      tax_p_code          *
     *      tax_p               /
     *      logistics_currency
     *      logistics_estimat
     *
     */
    static public function calcu_f($info, $to_cur = 'USD')
    {
        $ret = array();
        //退税
        $ret['drawback'] = '';
        //含税收入
        $ret['tax_income'] = '';
        //销售端应缴税
        $ret['sale_tax'] = '';
        //不含税收入（KPI）
        $ret['tax_free_income'] = '';
        //毛利
        $ret['gross_profit'] = '';
        //毛利率
        $ret['gross_interest_rate'] = '';
        //物流费用
        $ret['logistics_cost'] = '';
        //可再销售残次品成本
        $ret['imperfections_cost'] = '';
        //含税成本
        $ret['tax_cost'] = '';
        //不含税成本
        $ret['non_tax_cost'] = '';
        //采购端应缴税
        $ret['purchase_tax'] = '';
        //税率
        $ret['tax_p'] = '';
        //COGS
        $ret['cogs'] = '';

        // fix
        $ret['imperfections_cost'] = floatval($ret['imperfections_cost']);

        $show_cur = $to_cur;
        $ret['cur'] = $show_cur;

        // fa huo
        $fahuofangshi = isset($info['fahuofangshi']) ? $info['fahuofangshi'] : null;
        $fh_type = self::chk_fahuo_type($fahuofangshi);
        $ret['fahuofangshi'] = $fahuofangshi;
        $ret['fh_type'] = $fh_type;
        // date
        $ret['day'] = isset($info['day']) ? $info['day'] : null;
        if (empty($ret['day'])) {
            $ret['day'] = date('Y-m-d');
        }
        $ret['day'] = date('Y-m-d', strtotime($ret['day']));
        Logs($ret['day'], '$ret[\'day\']');
        if ($fh_type === null) {
            return $ret;
        }

        // 退税金额（币种和商品含税成本保持一致，不可选择）
        $info['cur_tuishui'] = $info['backend_currency'];
        // 销售端应缴税
        $info['cur_saletax'] = $info['cur_saletax'];
        // 其他收入：币种和PO金额保持一致，不可选择
        $info['cur_other'] = $info['poAmountBz'];

        // basic info
        // 含税成本
        $backend_currency = isset($info['backend_currency']) ? $info['backend_currency'] : null;
        $backend_estimat = isset($info['backend_estimat']) ? $info['backend_estimat'] : null;
        $backend_estimat = ZFun::keepNumber($backend_estimat);
        // tuishui
        $cur_tuishui = isset($info['cur_tuishui']) ? $info['cur_tuishui'] : null;
        $drawback_estimate = isset($info['drawback_estimate']) ? $info['drawback_estimate'] : null;
        $drawback_estimate = ZFun::keepNumber($drawback_estimate);
        // 含税收入 - PO金额
        $poAmountBz = isset($info['poAmountBz']) ? $info['poAmountBz'] : null;
        $poAmount = isset($info['poAmount']) ? $info['poAmount'] : null;
        $poAmount = ZFun::keepNumber($poAmount);
        // 销售端应缴税
        $cur_saletax = isset($info['cur_saletax']) ? $info['cur_saletax'] : null;
        $sale_tax = isset($info['sale_tax']) ? $info['sale_tax'] : null;
        $sale_tax = ZFun::keepNumber($sale_tax);
        // tax
        $tax_p_code = isset($info['tax_p_code']) ? $info['tax_p_code'] : null;
        $tax_p = isset($info['tax_p']) ? $info['tax_p'] : null;
        $tax_p = D("ZZmscmncd")->getNameFromCode($tax_p_code);
        $tax_p = ZFun::keepNumber($tax_p);
        $tax_p = $tax_p / 100;
        //物流费用
        $logistics_currency = isset($info['logistics_currency']) ? $info['logistics_currency'] : null;
        $logistics_estimat = isset($info['logistics_estimat']) ? $info['logistics_estimat'] : null;
        $logistics_estimat = ZFun::keepNumber($logistics_estimat);
        $ret['drawback'] = self::price_cur_rate($drawback_estimate, $cur_tuishui, $show_cur, $ret['day']);
        $ret['tax_income'] = self::price_cur_rate($poAmount, $poAmountBz, $show_cur, $ret['day']);
        $ret['sale_tax'] = self::price_cur_rate($sale_tax, $cur_saletax, $show_cur, $ret['day']);
        $ret['tax_cost'] = self::price_cur_rate($backend_estimat, $backend_currency, $show_cur, $ret['day']);
        $ret['logistics_cost'] = self::price_cur_rate($logistics_estimat, $logistics_currency, $show_cur, $ret['day']);
        $ret['tax_p_code'] = $tax_p_code;
        $ret['tax_p'] = $tax_p;
        $ret['non_tax_cost'] = $ret['tax_cost'] - $ret['drawback'];

        //采购端应缴税=含税成本-含税成本/（1+税率）【这个值在页面上不显示】
        $ret['purchase_tax'] = $ret['tax_cost'] - ($ret['tax_cost'] / (1 + $tax_p));

        // Domestic
        if ($fh_type === 1) {
            //不含税收入（KPI）=PO金额-销售端应缴税
            $ret['tax_free_income'] = $ret['tax_income'] - $ret['sale_tax'];
            //毛利=PO金额-销售端应缴税+采购端应缴税  -->>  毛利=PO金额-销售端应缴税-不含税成本+采购端应缴税
            $ret['gross_profit'] = $ret['tax_income'] - $ret['sale_tax'] - $ret['non_tax_cost'] + $ret['purchase_tax'];
            //毛利率=毛利/不含税收入=（PO金额-销售端应缴税+含税成本）/（PO金额-销售端应缴税）
            $ret['gross_interest_rate'] = $ret['gross_profit'] / $ret['tax_free_income'];
            //cogs
            $ret['cogs'] = $ret['tax_cost'];
        } // Not Domestic
        elseif ($fh_type === 0) {
            //采购端应缴税=0【这个值在页面上不显示】
            $ret['purchase_tax'] = 0;
            //销售端应缴税=0

            //不含税成本=含税成本-退税
            $ret['non_tax_cost'] = $ret['tax_cost'] - $ret['drawback'];
            //不含税收入（KPI）=PO金额-销售端应缴税=PO金额
            $ret['tax_free_income'] = $ret['tax_income'] - $ret['sale_tax'];
            //毛利=不含税收入-不含税成本=（PO金额-销售端应缴税）-（含税成本-退税）
            $ret['gross_profit'] = $ret['tax_free_income'] - $ret['non_tax_cost'];
            //毛利率=毛利/不含税收入
            $ret['gross_interest_rate'] = $ret['gross_profit'] / $ret['tax_free_income'];
            //cogs =A-C, with VAT Return
            $ret['cogs'] = $ret['tax_cost'] - ($ret['tax_cost'] - ($ret['tax_cost'] / (1 + $tax_p)));
        }

        // var_dump($ret);
        // var_dump($info);
        return $ret;
    }

    /**
     *  Format forecast num
     *
     */
    static public function fmt_the_forecast($forecast)
    {
        $forecast = is_array($forecast) ? $forecast : array();
        $fmtNum = array(
            'drawback',
            'tax_income',
            'sale_tax',
            'tax_free_income',
            'gross_profit',
            'gross_interest_rate',
            'logistics_cost',
            'imperfections_cost',
            'tax_cost',
            'non_tax_cost',
            'purchase_tax',
            'tax_p',
            'cogs',
        );
        //percent
        $forecast['fmt_gross_interest_rate'] = '';
        $forecast['gross_interest_rate'] = isset($forecast['gross_interest_rate']) ? $forecast['gross_interest_rate'] : '';
        if (isset($forecast['gross_interest_rate'])) {
            $forecast['fmt_gross_interest_rate'] = Mainfunc::pricePretty(ZFun::keepNumber(100 * $forecast['gross_interest_rate'])) . '%';
        }
        foreach ($forecast as $key => $val) {
            if (in_array($key, $fmtNum)) {
                $val = Mainfunc::pricePretty(ZFun::keepNumber($val));
            }
            $forecast[$key] = $val;
        }
        return $forecast;
    }

    /**
     *  Format goods list
     *
     */
    static public function fmt_goods_list($list)
    {
        $list = is_array($list) ? $list : array();
        foreach ($list as $k => $v) {
            $jdesc = isset($v['jdesc']) ? $v['jdesc'] : null;
            $arr = unserialize($jdesc);
            $arr['price'] = isset($arr['price']) ? $arr['price'] : null;
            if ($arr['price'] !== null) {
                $list[$k]['price_goods'] = $arr['price'];
            }
            // translate
            $see_purchasing = D("ZZmscmncd")->getNameFromCode($v['purchasing_team']);
            $see_introduce = D("ZZmscmncd")->getNameFromCode($v['introduce_team']);
            $list[$k]['see_purchasing'] = $see_purchasing;
            $list[$k]['see_introduce'] = $see_introduce;
        }
        return $list;
    }

    /**
     *  Format order info list
     *
     */
    static public function fmt_orderinfo_list($list)
    {
        $list = is_array($list) ? $list : array();
        $where = array();
        $area_init = B2bModel::get_area(true, $where);

        foreach ($list as $k => $v) {
            // calcu
            $profit_pre = self::one_order_calcu_f($v);
            // target
            $TARGET_PORT = isset($v['TARGET_PORT']) ? $v['TARGET_PORT'] : null;
            $addrs = @json_decode($TARGET_PORT, true);
            $addrs = (array)$addrs;
            $addrs['targetCity'] = isset($addrs['targetCity']) ? $addrs['targetCity'] : '';
            $addrs['country'] = isset($addrs['country']) ? $addrs['country'] : '';
            $addrs['stareet'] = isset($addrs['stareet']) ? $addrs['stareet'] : '';
            $addrs['city'] = isset($addrs['city']) ? $addrs['city'] : '';
            $addrs['province'] = isset($addrs['province']) ? $addrs['province'] : '';
            // name
            $addrNames = array();
            $addrNames['country'] = isset($area_init[$addrs['country']]) ? $area_init[$addrs['country']] : '';
            $addrNames['stareet'] = isset($area_init[$addrs['stareet']]) ? $area_init[$addrs['stareet']] : '';
            $addrNames['city'] = isset($area_init[$addrs['city']]) ? $area_init[$addrs['city']] : '';
            // set
            $v['addrs'] = $addrs;
            $v['addrNames'] = $addrNames;
            $v['profit_pre'] = $profit_pre;
            $v['cur_tuishui'] = $v['backend_currency'];
            $v['cur_saletax'] = $v['cur_saletax'];
            $v['cur_other'] = $v['po_currency'];
            // state
            $v['fmt_submit'] = B2bData::getSubmitState($v['submit_state']);
            $list[$k] = $v;
        }
        return $list;
    }

    /**
     *  Format each state of order list
     *
     */
    static public function state_order_list($list)
    {
        $list = is_array($list) ? $list : array();
        $order_id_arr = array_column($list, 'ORDER_ID');
        $all_status_arr = B2bData::gain_receipt_status_arr($order_id_arr);
        foreach ($list as $k => $v) {
            $all_status = $all_status_arr[$v['ORDER_ID']];
            // state-发货
            $v['is_shipped'] = $all_status['is_shipped'];
            // state-收款
            $v['is_receipts'] = $all_status['is_receipts'];
            // state-退税
            $v['is_tax'] = $all_status['is_tax'];
            $list[$k] = $v;
        }
        /*foreach ($list as $k => $v) {
            $order_id = $v['ORDER_ID'];
            $all_status = B2bData::gain_receipt_status($order_id);
            // state-发货
            $v['is_shipped'] = $all_status['is_shipped'];
            // state-收款
            $v['is_receipts'] = $all_status['is_receipts'];
            // state-退税
            $v['is_tax'] = $all_status['is_tax'];
            $list[$k] = $v;
        }*/
        // die();
        return $list;
    }

    /**
     *  Calcu one order info
     *
     */
    static public function one_order_calcu_f($order)
    {
        $info = array();
        $info['fahuofangshi'] = $order['DELIVERY_METHOD'];
        $info['day'] = $order['po_time'];
        $info['backend_currency'] = $order['backend_currency'];
        $info['backend_estimat'] = $order['backend_estimat'];
        $info['cur_tuishui'] = $order['cur_tuishui'];
        $info['drawback_estimate'] = $order['drawback_estimate'];
        $info['poAmountBz'] = $order['po_currency'];
        $info['poAmount'] = $order['po_amount'];
        $info['cur_saletax'] = $order['cur_saletax'];
        $info['sale_tax'] = $order['sale_tax'];
        $info['logistics_currency'] = $order['logistics_currency'];
        $info['logistics_estimat'] = $order['logistics_estimat'];
        $info['tax_p_code'] = $order['TAX_POINT'];
        $forecast = self::calcu_f($info);
        return $forecast;
    }

    /**
     *  Calcu one order profit
     *
     */
    static public function fmt_order_profit($data)
    {
        $data = is_array($data) ? $data : array();
        $data['profit'] = isset($data['profit']) ? $data['profit'] : array();
        $data['profit']['create_time'] = isset($data['profit']['create_time']) ? $data['profit']['create_time'] : null;

        $order = isset($data['info'][0]) ? $data['info'][0] : null;

        if ($data['profit']['create_time']) {

            $info = array();
            $info['fahuofangshi'] = $order['DELIVERY_METHOD'];
            $info['day'] = $order['po_time'];
            //cur
            $info['backend_currency'] = $order['backend_currency'];
            //sum - 商品成本
            $info['backend_estimat'] = $order['backend_estimat'];
            //cur
            $info['poAmountBz'] = $order['po_currency'];
            //sum - 销售收入
            $info['poAmount'] = $data['profit']['I'];
            //cur
            $info['logistics_currency'] = $order['logistics_currency'];
            //物流
            $info['logistics_estimat'] = $order['logistics_estimat'];
            //tax code
            $info['tax_p_code'] = $order['TAX_POINT'];
            //cur
            $info['cur_tuishui'] = $order['cur_tuishui'];
            //need calculate - 退税
            $info['drawback_estimate'] = self::change_tuishui($info);
            //cur
            $info['cur_saletax'] = $order['cur_saletax'];
            //need calculate - 销售端应缴税
            $info['sale_tax'] = self::change_saletax($info);

            $forecast = self::calcu_f($info);

        }
        return $data;
    }

    /**
     *  Calcu tuishui
     *
     */
    static public function change_tuishui($info)
    {
        $ret = 0;
        $fahuofangshi = isset($info['fahuofangshi']) ? $info['fahuofangshi'] : null;
        $fh_type = self::chk_fahuo_type($fahuofangshi);
        $tax_p_code = isset($info['tax_p_code']) ? $info['tax_p_code'] : null;
        $tax_p = self::calcu_tax_by_code($tax_p_code);
        $cb = isset($info['backend_estimat']) ? $info['backend_estimat'] : null;
        $cb = ZFun::keepNumber($cb);
        if ($fh_type === 0) {
            $ret = $cb - ($cb / (1 + $tax_p));
        }
        $ret = ZFun::keepNumber($ret);
        return $ret;
    }

    /**
     *  Calcu saletax
     *
     */
    static public function change_saletax($info)
    {
        $ret = 0;
        $fahuofangshi = isset($info['fahuofangshi']) ? $info['fahuofangshi'] : null;
        $fh_type = self::chk_fahuo_type($fahuofangshi);
        $tax_p_code = isset($info['tax_p_code']) ? $info['tax_p_code'] : null;
        $tax_p = self::calcu_tax_by_code($tax_p_code);
        $po_jin = isset($info['poAmount']) ? $info['poAmount'] : null;
        $po_jin = ZFun::keepNumber($po_jin);
        if ($fh_type === 1) {
            $ret = $po_jin - ($po_jin / (1 + $tax_p));
        }
        $ret = ZFun::keepNumber($ret);
        return $ret;
    }

    /**
     *  Calcu tax by code
     *
     */
    static public function calcu_tax_by_code($tax_p_code)
    {
        $tax_p = D("ZZmscmncd")->getNameFromCode($tax_p_code);
        $tax_p = ZFun::keepNumber($tax_p);
        $tax_p = $tax_p / 100;
        return $tax_p;
    }

    /**
     *  Format gathering data
     *
     */
    static public function fmt_gathering($gathering)
    {
        $gathering = is_array($gathering) ? $gathering : array();
        $o_id = isset($gathering['ORDER_ID']) ? $gathering['ORDER_ID'] : null;
        // receiving type [ 0 default , 1 tuishui ]
        $fmt_receiving_type = 0;
        $receiving_code = isset($gathering['receiving_code']) ? $gathering['receiving_code'] : null;
        $j_data = @json_decode($receiving_code);
        if (!$j_data) {
            $fmt_receiving_type = 1;
        }
        $gathering['fmt_receiving_type'] = $fmt_receiving_type;
        // if type that it is tuishui , change cur by backend_currency in po
        if ($fmt_receiving_type == 1) {
            // find po
            $order = B2bData::fetchOneOrder($o_id);
            $cur = isset($order['backend_currency']) ? $order['backend_currency'] : '';
            if ($cur) {
                // set replace
                $gathering['po_currency'] = $cur;
            }
        }
        return $gathering;
    }

    /**
     *  Check order time changed or not
     *
     */
    static public function order_changed_by_old($order_id, $old_data)
    {
        $ret = array();
        $ret['is_err'] = $is_err = 0;
        $ret['msg'] = $msg = '';
        $ret['check_po_changed'] = $check_po_changed = 0;
        $check_old = isset($old_data['update_time']) ? $old_data['update_time'] : null;
        $order_data = B2bData::oneOrderBasic($order_id);
        $check = isset($order_data['update_time']) ? $order_data['update_time'] : null;
        if (!$is_err) {
            if (empty($order_data)) {
                $is_err = 1;
                $msg = '该订单不存在';
            }
        }
        if (!$is_err) {
            if ($order_data['submit_state'] != 2) {
                $is_err = 1;
                $msg = '该订单是草稿状态';
            }
        }
        if (!$is_err) {
            if ($check > $check_old) {
                $is_err = 1;
                $msg = '该订单已经被修改，请重试';
                $check_po_changed = 1;
            }
        }
        $ret['is_err'] = $is_err;
        $ret['msg'] = $msg;
        $ret['check_po_changed'] = $check_po_changed;
        return $ret;
    }

    /**
     *  Format order list
     *
     */
    static public function fmtOrderList($list)
    {
        $list = is_array($list) ? $list : array();
        foreach ($list as $k => $v) {
            $v['fmt_submit'] = B2bData::getSubmitState($v['submit_state']);
            $list[$k] = $v;
        }
        return $list;
    }


}



