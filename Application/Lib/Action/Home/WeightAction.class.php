<?php

/**
 * User: yangsu
 * Date: 17/4/27
 * Time: 14:56
 */
class WeightAction extends BaseAction
{
    public function _initialize()
    {

    }

    private $bill_state = [
        'N000590100' => 'USD',
        'N000590200' => 'KRW',
        'N000590300' => 'CNY',
        'N000590400' => 'JPY',
    ];

    public function index()
    {
        $this->UpdateAll();
    }

    /**
     * 更新全部
     */
    public function UpdateAll()
    {
        ini_set('max_execution_time', 1800);
        $Model = M();
        $power_log_bill = $Model->table('tb_wms_power_log')->where('actiontype = \'end_bill_id\'')->order('id desc')->limit(1)->getField('bill_id');
        if (I('limit_val')) {
            $limit_val = I('limit_val');
        } else {
            $limit_val = 100;
        }
        if (empty($power_log_bill)) {
            $power_log_bill = 0;
        }
        // 检验 date 后 有效订单数目，计算区间
        $where_count['is_show'] = $where['is_show'] = 1;
        $end_bill_id = $Model->table('tb_wms_bill')->where($where_count)->order('id desc')->limit(1)->getField('id');
        if ($power_log_bill >= $end_bill_id) {
            echo 'end bill';
            die;
        }
        // 更新订单区间
        $where_count['id'] = array('GT', $power_log_bill);
        $top_bill_id = $Model->table('tb_wms_bill')->where($where_count)->order('id asc')->limit(1)->getField('id');
        if (empty($top_bill_id)) {
            $top_bill_id = 0;
        }
        // search all list
        $where['id'] = array(array('EGT', $top_bill_id), array('ELT', $top_bill_id + $limit_val));
        $bill_arr = $Model->table('tb_wms_bill')->where($where)->field('id,warehouse_id,bill_type,bill_date')->order('id asc')->select();
        $sum_bill = count($bill_arr);
        if ($sum_bill > 0) {
            foreach ($bill_arr as $k => $v) {
                //            check type
                $type = null;
                if ($this->check_type($v['bill_type']) == 'out') {
                    $type = '-';
                }
                $bill_key_arr[$v['id']] = $bill_id_arr[] = ['id' => $v['id'], 'type' => $type, 'bill_date' => $v['bill_date']];
            }
            $bill_id_arr_id = array_column($bill_id_arr, 'id');
            $stream_where['bill_id'] = array('IN', $bill_id_arr_id);
            $stream = $Model->table('tb_wms_stream')->where($stream_where)->field('id,bill_id,GSKU,send_num,unit_price,currency_id')->select();
            foreach ($stream as $v) {
                $stream_arr[$v['bill_id']][] = $v;
            }
            foreach ($stream_arr as $key => $value) {
                foreach ($this->return_this($value) as $k => $v) {
//                update currency
//                N000590300 RMB
                    if ($bill_key_arr[$v['bill_id']]['type'] != '-') {
                        $update_currency = null;
                        if ($v['currency_id'] != 'N000590300') {
                            $update_currency = $this->update_currency($this->bill_state[$v['currency_id']], $bill_key_arr[$v['bill_id']]['bill_date']);
                            $price = $v['unit_price'] * $update_currency;
                        } else {
                            $price = $v['unit_price'];
                        }
                        $arr['type'] = $bill_key_arr[$v['bill_id']]['type'];
                        $arr['num'] = $v['send_num'];
                        $arr['price'] = $price;
                        $arr['bill_id'] = $v['bill_id'];
                        $arr['bill_date'] = $bill_key_arr[$v['bill_id']]['bill_date'];
                        $arr['currency'] = $update_currency;
                        $arrs_sku[$v['GSKU']][] = $arr;
                    } else {
                        $arr['type'] = $bill_key_arr[$v['bill_id']]['type'];
                        $arr['num'] = $bill_key_arr[$v['bill_id']]['type'].$v['send_num'];
                        $arr['bill_id'] = $v['bill_id'];
                        $arr['bill_date'] = $bill_key_arr[$v['bill_id']]['bill_date'];
                        $arrs_sku[$v['GSKU']][] = $arr;
                    }

                }
            }

            $all_sku = array_keys($arrs_sku);
            $where_power['SKU_ID'] = array('IN', $all_sku);
            $power_old_arr = $Model->table('tb_wms_power')->where($where_power)->getField('SKU_ID,weight,num');
// todo
            foreach ($arrs_sku as $k => $v) {
                //               期初
                $power_old_data = $power_old_arr[$k];
                if (empty($power_old_data)) {
                    $sku_weight = $this->weight_list($v);
                } else {
                    $sku_weight = $this->weight_list($v, $power_old_data['weight'], $power_old_data['num'], $power_old_data['num'] * $power_old_data['weight']);
                }
                $data['weight'] = $sku_weight['price'];
                $data['num'] = $sku_weight['num'];
                $data['update_time'] = date('Y-m-d H:i:s');
                if ($data['weight'] < 0) {
//                    $data['weight'] = 0;
                }
                if ($data['num'] < 0) {
//                    $data['num'] = 0;
                }

                $log['bill_id'] = $data['bill_id'] = $v[count($v) - 1]['bill_id'];
                if ($power_old_arr[$k]['SKU_ID'] == $k) {
                    $power = $Model->table('tb_wms_power')->where('SKU_ID = \'' . $k . '\'')->save($data);
                    $log['balance'] = serialize($power_old_arr[$k]);
                } else {
                    $data['SKU_ID'] = $k;
                    $datas[] = $data;
                }
                $log['add_time'] = date('Y-m-d H:i:s');
                $log['sku'] = $k;
                $log['actiontype'] = serialize($data);
                $logs[] = $log;
                unset($data);
            }
            if (count($datas)) {
                $power = $Model->table('tb_wms_power')->addAll($datas);
            }
            $power_all_log = $Model->table('tb_wms_power_log')->addAll($logs);
            $max_bill_id = max($bill_arr);
            if (!empty($max_bill_id['id'])) {
                $Power_log = M('power_log', 'tb_wms_');
                $Power_log->bill_id = $max_bill_id['id'];
                $Power_log->actiontype = 'end_bill_id';
                $Power_log->add_time = date('Y-m-d H:i:s');
                $power_log = $Power_log->add();
            }
            echo 'power sum' . $power .' max id:'.$max_bill_id['id'].'-all_log:' . $power_all_log . '-log' . $power_log;
        } else {
            printf("end sql %s ,count bill %s ,top id %s, sum bill %s , %s", $Model->getLastSql(), count($bill_arr), max($bill_arr), $sum_bill, PHP_EOL);
        }

    }

    /**
     * 期初数据
     * @param $arr
     * @param int $y_price
     * @param int $y_num
     * @param int $y_sum
     * @return mixed
     */
    private function weight_list($arr, $y_price = 0, $y_num = 0, $y_sum = 0)
    {
        foreach ($this->return_this($arr) as $key => $val) {
            if ($val['num'] > 0) {
                $y_sum += $val['num'] * $val['price'];
                $y_num += $val['num'];
                $y_price = $y_sum / abs($y_num);
            } else {
                $y_num += $val['num'];
                $y_sum = $y_num * $y_price;
            }
        }
        $res['num'] = $y_num;
        $res['price'] = $y_price;
        $res['sum'] = $y_sum;
        return $res;
    }

    private function check_type($b)
    {
        if (empty(session('get_outgoing')) || empty(session('get_out'))) {
            $this->get_outgoing();
            $this->get_out();
        }
        if (in_array($b, session('get_outgoing'))) {
            return 'out';
        } elseif (in_array($b, session('get_out'))) {
            return 'go';
        } else {
            return false;
        }
    }

    private function get_outgoing()
    {
        $Res = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '出库类型';
        $res = $Res->where($where)->getField('CD,CD_VAL');
        $res_keys = array_keys($res);
        session('get_outgoing', $res_keys);
        return $res_keys;
    }

    private function get_out()
    {
        $Res = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '入库类型';
        $res = $Res->where($where)->getField('CD,CD_VAL');
        $res_keys = array_keys($res);
        session('get_out', $res_keys);
        return $res_keys;
    }

    /**
     * @param $unit_price
     * @param $digit
     * @param $date
     */
    public function update_currency($currency, $date)
    {
        // $url = INSIGHT . '/insight-backend/external/exchangeRate?date=' . $date . '&dst_currency=CNY&src_currency=' . $currency;
        if (empty($date) || empty($currency)) {
            return false;
        }
        $url = BI_API_REVEN.'/external/exchangeRate?date=' . $date . '&dst_currency=CNY&src_currency=' . $currency;
        $url_md5 = md5($url);
        if (empty(session($url_md5))) {
            $currency = json_decode(curl_request($url), 1);
            session($url_md5, $currency);
        } else {
            $currency = session($url_md5);
        }
        if (empty($currency['data'][0]['rate'])) {
            return false;
        } else {
            return $currency['data'][0]['rate'];
        }


    }

    private function get_xchr($updated_time)
    {
        $Xchr = M('xchr', 'tb_ms_');
        $where['updated_time'] = $updated_time;
        $xchr = $Xchr->where($where)->order('updated_time desc')->find();
        return $xchr;
    }

    // 币种
    private function get_currency()
    {
        $Currency = M('cmn_cd', 'tb_ms_');
        $where['CD_NM'] = '기준환율종류코드';
        $currency = $Currency->where($where)->getField('CD,CD_VAL');
        $currency_keys = array_keys($currency);
        session('currency_keys', $currency_keys);
        return $currency_keys;
    }

    private function get_log()
    {


    }

    private function log()
    {

    }

    /**
     * 清理权值
     */
    public function clean_king()
    {
        if (I('key') == 'dfbensie1') {
            $Power = M('power', 'tb_wms_');
            $data['weight'] = 0;
            $data['num'] = 0;
            $power = $Power->where('1 = 1')->data($data)->save();
            $Power_log = M('power_log', 'tb_wms_');
            $power_log = $Power_log->where('1 = 1')->delete();
            echo 'ok';
        } else {
            echo 'false';
        }
    }

    /**
     * 测试
     */
    public function test()
    {


    }

    private function return_this($e)
    {
        foreach ($e as $k) {
            yield $k;
        }
    }
    public function go_do(){
        $this->display();
    }

}