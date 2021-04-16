<?php

/**
 * User: yangsu
 * Date: 19/08/06
 * Time: 15:31
 */

@import("@.Action.Report.ReportBaseAction");

class ExcelService extends Service
{


    /**
     * ExcelService constructor.
     */
    private $page_size = 999999;

    public function __construct()
    {
        $this->repository = new ExcelRepository();
        if ('stage' == $_ENV['NOW_STATUS']) {
            $this->page_size = 9999;
        }
    }

    /**
     * @param $data
     *
     * @return array|mixed
     */
    public function inventoryTurnoverRate($data, $sku = null)
    {
        $all_sku_db = $this->repository->getAllSkuAmount($sku);
        $all_skus = array_combine(array_column($all_sku_db, 'SKU_ID'), array_values($all_sku_db));
        $IncomeAction = A('Report/Income');
        $CostAction = A('Report/Cost');
        $temp_data = [];
        $transfer_out = $this->repository->transferOut();
        Logs([$all_skus, $transfer_out], __FUNCTION__, __CLASS__);
        foreach ($data as $datum) {
            $interval_data_db = $this->repository->getInterval($datum['start'], $datum['end']);
            foreach ($interval_data_db as $interval_datum) {
                $interval_data[$interval_datum['sku_id']] = $interval_datum['sum_send_num'];
            }
            $start_data = $this->getDesignationDateBill($datum['start']);
            $end_data = $this->getDesignationDateBill($datum['end']);
            $income_data = $this->getIncomeData($IncomeAction, $datum['start'], $datum['end']);
            $cost_data = $this->getCostData($CostAction, $datum['start'], $datum['end']);
            $month = date('Ym', strtotime($datum['start']));
            Logs([$month, $start_data, $end_data, $income_data, $cost_data], __FUNCTION__, __CLASS__);
            foreach (DataModel::toYield($all_skus) as $sku_value) {
                $sku_id = $sku_value['SKU_ID'];
                if (empty($interval_data[$sku_id]) && empty($income_data[$sku_id]) && empty($cost_data[$sku_id]) && empty($start_data[$sku_id]) && empty($end_data[$sku_id])) {
//                    continue;
                }
                $temp_data[] = [
                    'month' => $month,
                    'sku_id' => $sku_id,

                    'sales_volume' => $interval_data[$sku_id],

                    'sales' => $income_data[$sku_id],
                    'cost_of_sales' => $cost_data[$sku_id],

                    'early_month_amount' => $sku_value['sum_amount'] + $start_data[$sku_id]['out']['total_amount'] - $start_data[$sku_id]['in']['total_amount'] + $transfer_out[$sku_id]['sum_amount'],
                    'early_month_quantity' => $sku_value['sum_total_inventory'] + $start_data[$sku_id]['out']['total'] - $start_data[$sku_id]['in']['total'] + $transfer_out[$sku_id]['sum_occupy_num'],
                    'end_month_amout' => $sku_value['sum_amount'] + $end_data[$sku_id]['out']['total_amount'] - $end_data[$sku_id]['in']['total_amount'] + $transfer_out[$sku_id]['sum_amount'],
                    'end_month_quantity' => $sku_value['sum_total_inventory'] + $end_data[$sku_id]['out']['total'] - $end_data[$sku_id]['in']['total'] + $transfer_out[$sku_id]['sum_occupy_num'],
                ];
            }
            unset($interval_data);
        }
        $temp_data_chunk = array_chunk($temp_data, 1000);
        unset($temp_data);
        foreach ($temp_data_chunk as &$value) {
            $value = SkuModel::getInfo($value, 'sku_id', ['spu_name', 'attributes', 'brand_name']);
        }
        unset($value);
        $temp_data_return = [];
        foreach ($temp_data_chunk as $value) {
            $temp_data_return = array_merge($temp_data_return, $value);
        }
        return $temp_data_return;
    }

    /**
     * @param $data
     *
     * @return array|mixed
     */
    public function custData($data)
    {
        $temp_data = [];
        foreach ($data as $datum) {

        }

        $temp_data_return = [];
        return $temp_data_return;
    }

    /**
     * @param $data
     *
     * @return array|mixed
     */
    public function tppCustData($data)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '1800');
        $data_return = [];
        //��ȡTPP�û�����
        $nick_nm = [];
        foreach ($data as $datum) {
            $tppCustData = $this->repository->getTppCustData($datum['start'], $datum['end']);
            foreach ($tppCustData as $key => $val) {
                //�ֻ��Ž��� ���ڡ�+����δ���ܺ���
                /*if (strpos($val['REL_TEL'], '+') === false) {
                    $tel_data = CrypMobile::deCryp($val['REL_TEL']);
                    if ($tel_data['code'] == 200) {
                        $val['REL_TEL'] = $tel_data['data'];
                    }
                }*/
                $val['ALL_ORDER_COUNT'] = 0; //�ܶ�����
                $val['USER_EMAIL'] = ''; //�û��ʼ�
                $val['COUNTRY_NAME'] = ''; //����
                $data_return[$val['RES_NAME']] = $val;
                $nick_nm[$val['RES_NAME']] = $val['RES_NAME'];
            }
        }

        //op_order ������Ƭ��װ
        foreach ($data as $datum) {
            $op_order = $this->repository->getOpOrderData($datum['start'], $datum['end']);
            foreach ($op_order as $key => $val) {
                if (isset($nick_nm[$val['ADDRESS_USER_NAME']])) {
                    $data_return[$val['ADDRESS_USER_NAME']]['ALL_ORDER_COUNT'] += 1;
                    $data_return[$val['ADDRESS_USER_NAME']]['USER_EMAIL'] = $val['USER_EMAIL'];
                    $data_return[$val['ADDRESS_USER_NAME']]['COUNTRY_NAME'] = $val['ADDRESS_USER_COUNTRY'];
                }
            }
        }

        return $data_return;
    }

    /**
     * @param $data
     *
     * @return array|mixed
     */
    public function gpCustData($data)
    {
        ini_set('memory_limit', '512M');
        $data_return = [];
        //��ȡ����GP�û�����
        $gp_cust = $this->repository->getGpCustData();
        $nick_nm = [];
        foreach ($gp_cust as $key => $val) {
            //�ֻ��Ž��� ���ڡ�+����δ���ܺ���
            /*if (strpos($val['CUST_CP_NO'], '+') === false) {
                $tel_data = CrypMobile::deCryp($val['CUST_CP_NO']);
                if ($tel_data['code'] == 200) {
                    $val['CUST_CP_NO'] = $tel_data['data'];
                }
            }*/
            $val['ALL_ORDER_COUNT'] = 0; //�ܶ�����
            $val['USER_EMAIL'] = ''; //�û��ʼ�
            $val['COUNTRY_NAME'] = ''; //����
            $data_return[$val['CUST_NICK_NM']] = $val;
            $nick_nm[$val['CUST_NICK_NM']] = $val['CUST_NICK_NM'];
        }

        //op_order ������Ƭ��װ
        foreach ($data as $datum) {
            $op_order = $this->repository->getOpOrderData($datum['start'], $datum['end']);;
            foreach ($op_order as $key => $val) {
                if (isset($nick_nm[$val['ADDRESS_USER_NAME']])) {
                    $data_return[$val['ADDRESS_USER_NAME']]['ALL_ORDER_COUNT'] += 1;
                    $data_return[$val['ADDRESS_USER_NAME']]['USER_EMAIL'] = $val['USER_EMAIL'];
                    $data_return[$val['ADDRESS_USER_NAME']]['COUNTRY_NAME'] = $val['ADDRESS_USER_COUNTRY'];
                }
            }
        }

        return $data_return;
    }

    /**
     * @param $start_date
     * @param null $end_date
     *
     * @return array
     */
    public function getDesignationDateBill($start_date, $end_date = null)
    {
        $end_date or $end_date = DateModel::now();
        $res_db = $this->repository->getDesignationDateBill($start_date, $end_date);
        $temp_sku = [];
        foreach (DataModel::toYield($res_db) as $value) {
            if (!isset($temp_sku[$value['sku_id']])) {
                $temp_sku[$value['sku_id']] = [
                    'in' => [
                        'total' => 0,
                        'total_amount' => 0
                    ],
                    'out' => [
                        'total' => 0,
                        'total_amount' => 0
                    ],
                ];
            }
            switch ($value['type']) {
                case 1:
                    $temp_sku[$value['sku_id']]['in']['total'] += $value['send_num'];
                    $temp_sku[$value['sku_id']]['in']['total_amount'] += $value['send_num'] * $value['unit_price_no_rate'];
                    break;
                case 0:
                    $temp_sku[$value['sku_id']]['out']['total'] += $value['send_num'];
                    $temp_sku[$value['sku_id']]['out']['total_amount'] += $value['send_num'] * $value['unit_price_no_rate'];
                    break;
            }
        }
        return $temp_sku;
    }

    /**
     * @param $IncomeAction
     * @param $start
     * @param $end
     *
     * @return mixed
     */
    private function getIncomeData($IncomeAction, $start, $end)
    {
        $request_array = array(
            'zd_date' =>
                array(
                    0 => $start,
                    1 => $end,
                ),
            'page' => 1,
            'page_size' => $this->page_size,
        );
        $list_data = $IncomeAction->list_data($request_array)['list'];
        $temp_list_data = [];
        $date = DateModel::now();
        foreach (DataModel::toYield($list_data) as $value) {
            if (!isset($temp_list_data[$value['sku_id']])) {
                $temp_list_data[$value['sku_id']] = 0;
            }
            $temp_list_data[$value['sku_id']] += $value['sale_amount_no_tax'] * ExchangeRateModel::conversion($value['currency'], 'CNY', $date, false);
        }
        return $temp_list_data;
    }

    /**
     * @param $CostAction
     * @param $start
     * @param $end
     *
     * @return mixed
     */
    private function getCostData($CostAction, $start, $end)
    {
        $request_array = array(
            'zd_date' =>
                array(
                    0 => $start,
                    1 => $end,
                ),
            'page' => 1,
            'page_size' => $this->page_size,
        );
        $list_data = $CostAction->list_data($request_array)['list'];
        $temp_list_data = [];
        $date = DateModel::now();
        foreach (DataModel::toYield($list_data) as $value) {
            if (!isset($temp_list_data[$value['sku_id']])) {
                $temp_list_data[$value['sku_id']] = 0;
            }
            $temp_list_data[$value['sku_id']] += $value['pur_amount_no_tax'] * ExchangeRateModel::conversion($value['pur_currency'], 'CNY', $date, false);
        }
        return $temp_list_data;
    }
}