<?php
/**
 * User: due
 * Date: 2018/3/7
 * Time: 11:15
 */
require_once APP_PATH . 'Lib/Logic/Report/ReportBaseLogic.class.php';

class OnwayLogic extends ReportBaseLogic
{
    public function listData($params)
    {
        !empty($params['onway_date'][0]) && strtotime($params['onway_date'][0]) > strtotime('2018-08-01') ? $start = $params['onway_date'][0] : $start = '2018-08-01';
        !empty($params['onway_date'][1]) ? $end = $params['onway_date'][1] . ' 23:59:59' : $end = date('Y-m-d 23:59:59');
        empty($params['po_date'][0]) or $where['prepared_time'][] = ['egt', $params['po_date'][0]];
        empty($params['po_date'][1]) or $where['prepared_time'][] = ['elt', $params['po_date'][1] . ' 23:59:59'];
        empty($params['our_company']) or $where['od.our_company'] = ['in', $params['our_company']];
        empty($params['supplier']) or $where['od.supplier_id'] = ['like', '%' . $params['supplier'] . '%'];
        empty($params['purchase_team']) or $where['od.payment_company'] = ['in', $params['purchase_team']];
        empty($params['purchaser']) or $where['rel.prepared_by'] = ['in', $params['purchaser']];
        empty($params['sale_team']) or $where['dem.sell_team'] = ['in', $params['sale_team']];
        empty($params['procurement_number']) or $where['procurement_number'] = $params['procurement_number'];
        //  【在途库存金额（计算抵扣金）】，选项为正、负、0，可多选
        $inventory_type_one = "";
        if (count($params['inventory_type']) == 1){
            foreach ($params['inventory_type'] as $value){
                $inventory_type_one = $value;
            }
        }
        $inventory_type_two = "";
        if (count($params['inventory_type']) == 2){
            $inventory_data = ['positive','minus','zero'];
            $inventory_diff = array_diff($inventory_data,$params['inventory_type']);
            foreach ($inventory_diff as $value){
                $inventory_type_two = $value;
            }
        }
        if ($params['status'] == 1) {
            $where['_string'] = 'rel.warehouse_status <> 2 or rel.payment_status <> 2';
        }
        if ($params['status'] == 2) {
            $where['_string'] = 'rel.warehouse_status = 2 and rel.payment_status = 2';
        }
        $addStr = '';
        if ($where['_string']) {
            $addStr = ' AND ';
        }
        $where['_string'] .= $addStr . 'rel.order_status <> "N001320500"';
        $having = '';
        empty($params['page']) and $params['page'] = 1;
        empty($params['page_size']) and $params['page_size'] = 20;
        $offset = ($params['page'] - 1) * $params['page_size'];
        $query = M('relevance_order', 'tb_pur_')->alias('rel')
            ->field([
                'procurement_number',//采购单号
                'rel.prepared_time',//采购PO时间
                'od.real_currency_rate as po_date_rate',//PO日期兑换人民币汇率
                'if(rel.warehouse_status = 2 and rel.payment_status = 2, "已完结", "未完结") as status',//当前状态
                'cd3.CD_VAL as our_company',//所属公司
                'od.supplier_id as supplier',//供应商
                'cd1.CD_VAL AS amount_currency',//币种
                'cd1.CD as amount_currency_cd',//币种cd
                '(select sum(deduction_amount) from tb_pur_deduction_detail dedu 
                    where order_no = procurement_number and turnover_type = 1 and is_revoke = 0) as sum_use_deduction',// 使用抵扣金金额,订单币种
                '(select sum(deduction_amount) from tb_pur_deduction_detail dedu 
                    where order_no = procurement_number and turnover_type = 2  and is_revoke = 0 and deduction_type_cd = "N002660100") as sum_come_deduction',// 算作抵扣金金额（返利除外）,订单币种
                "(SELECT IFNULL(sum(qp.amount_account * qp.exchange_tax_account), 0) FROM tb_pur_payment qp
                    left join tb_pur_payment_audit pa on qp.payment_audit_id = pa.id
                    WHERE pa.billing_at < '$end' and pa.billing_at > '$start'  AND qp.STATUS = 3  AND qp.relevance_id = rel.relevance_id) 
                    AS amount_paid_ori",//付款金额 【实际出账金额】之和
                '(select ifnull(sum(rg.return_number*(gi.unit_price+gi.unit_expense)), 0) from tb_pur_return_goods rg
                    LEFT JOIN tb_pur_return_order ro ON ro.id=rg.return_order_id
                    LEFT JOIN tb_pur_return pr ON pr.id=ro.return_id
                    LEFT JOIN tb_pur_goods_information gi ON gi.information_id=rg.information_id
                    where ro.relevance_id = rel.relevance_id and pr.status_cd in ("N002640200","N002640300")) as return_goods_amount_ori',//退货金额 退货数量中的【退货出库数量】*[单价（含增值税）+PO内费用单价]
                "(SELECT IFNULL(sum(pi.invoice_money), 0) FROM tb_pur_invoice pi 
                    WHERE pi.confirm_time < '$end' and pi.confirm_time > '$start'  AND pi.status = 1  AND pi.relevance_id = rel.relevance_id) 
                    AS invoice_accepted_ori",//已收发票金额
                "(SELECT IFNULL(sum((wgood.warehouse_number + wgood.warehouse_number_broken) * (pgi.unit_price+pgi.unit_expense)), 0) FROM tb_pur_warehouse_goods wgood 
                    LEFT JOIN tb_pur_warehouse wh ON wgood.warehouse_id = wh.id  LEFT JOIN tb_pur_ship pship ON pship.id = wh.ship_id
                    LEFT JOIN tb_pur_ship_goods sgood2 ON wgood.ship_goods_id = sgood2.id
                    LEFT JOIN tb_pur_goods_information pgi ON pgi.information_id = sgood2.information_id
                    WHERE pship.relevance_id = rel.relevance_id AND wh.warehouse_time < '$end' and wh.warehouse_time > '$start' )
                    AS amount_in_warehouse_ori",//已入库金额
                'cd.CD_VAL AS purchase_team',//采购团队
                'rel.prepared_by as purchaser',//采购人
                'cd2.CD_VAL as sale_team',//销售团队
                'rel.relevance_id',
                'rel.reconciliation_remark'//对账备注
            ])
            ->join('LEFT JOIN tb_pur_order_detail od ON od.order_id = rel.order_id')
            ->join('LEFT JOIN tb_ms_cmn_cd cd ON cd.CD = od.payment_company')
            ->join('LEFT JOIN tb_ms_cmn_cd cd1 ON cd1.CD = od.amount_currency')
            ->join('LEFT JOIN tb_sell_quotation quo on quo.quotation_code=procurement_number')
            ->join('LEFT JOIN tb_ms_cmn_cd cd3 ON cd3.CD = od.our_company')
            ->join('left join tb_sell_demand dem on dem.id=quo.demand_id')
            ->join('LEFT JOIN tb_ms_cmn_cd cd2 ON cd2.CD = dem.sell_team')
            ->where($where);
            //->having('(amount_paid_ori > 0 or amount_in_warehouse_ori > 0)' . $having);
//        $params['page_size'] == -1 or $query->limit($offset . ',' . $params['page_size']);
        $list = $query->order('prepared_time desc')->select();
        // echo M()->_sql();die;
        $pur_order_nos = array_column($list, 'procurement_number');
        $return_amount_data = M('claim', 'tb_fin_')->alias('cl')
            ->field(['cl.order_no as pur_order_no', 'cl.claim_amount', 'cd.cd_val as currency', 'cd1.cd_val as pur_currency', 'xchr.*', '1 as CNY_XCHR_AMT_CNY'])
            ->join('LEFT JOIN tb_fin_account_turnover at ON at.id = cl.account_turnover_id')
            ->join('LEFT JOIN tb_pur_order_detail pod ON pod.procurement_number = cl.order_no')
            ->join('LEFT JOIN tb_pur_relevance_order pro ON pro.order_id = pod.order_id')//prepared_time
            ->join('LEFT JOIN tb_ms_xchr xchr ON xchr.XCHR_STD_DT = date_format(pro.prepared_time,"%Y%m%d")')
            ->join('LEFT JOIN tb_ms_cmn_cd cd ON cd.CD = at.currency_code')
            ->join('LEFT JOIN tb_ms_cmn_cd cd1 ON cd1.CD = pod.amount_currency')
            ->where(['cl.order_type' => 'N001950600', 'cl.order_no' => ['in', $pur_order_nos]])
            ->select();
        $return_amount_list = [];
        foreach ($return_amount_data as $v) {
            $rate = $v[$v['currency'] . '_XCHR_AMT_CNY'] / $v[$v['pur_currency'] . '_XCHR_AMT_CNY'];
            $amount = $v['claim_amount'] * $rate;
            $return_amount_list[$v['pur_order_no']] += $amount;
        }
        foreach ($list as $key => &$v) {
            // 综合付款金额 = 付款金额 - 退款金额 （2019-10-09 需求9551-供应商对账需求改动）
            // 综合付款金额 = 付款金额 + 使用抵扣金金额 - 退款金额 - 算作抵扣金金额（返利除外）
            if (isset($return_amount_list[$v['procurement_number']])) {
                $v['amount_paid_ori'] -= $return_amount_list[$v['procurement_number']];
            }
            //$v['amount_paid_ori'] = $v['amount_paid_ori'] + $v['use_deduction_amount'] - $v['as_deduction_amount'];
            $v['amount_in_warehouse_ori'] -= $v['return_goods_amount_ori'];
            $v['amount_paid'] = number_format($v['amount_paid_ori'], 2);
            $v['invoice_accepted'] = number_format($v['invoice_accepted_ori'], 2);
            $v['amount_paid_cny'] = number_format($v['amount_paid_ori'] * $v['po_date_rate'], 2);
            $v['amount_in_warehouse'] = number_format($v['amount_in_warehouse_ori'], 2);
            $v['amount_in_warehouse_cny'] = number_format($v['amount_in_warehouse_ori'] * $v['po_date_rate'], 2);
            $v['amount_onway_ori'] = $v['amount_paid_ori'] - $v['amount_in_warehouse_ori'];
            $v['amount_onway_cny'] = $v['amount_onway_ori'] * $v['po_date_rate'];
            $v['amount_onway'] = number_format($v['amount_onway_ori'], 2);
            $v['invoice_onway'] = number_format($v['amount_paid_ori'] - $v['invoice_accepted_ori'], 2);
            $v['sum_inventory_deduction'] = number_format($v['amount_onway_ori'] + $v['sum_use_deduction'] - $v['sum_come_deduction'], 2);
            $v['sum_come_deduction'] = number_format($v['sum_come_deduction'], 2);
            $v['sum_use_deduction'] = number_format($v['sum_use_deduction'], 2);
            switch ($inventory_type_one){
                case 'positive' :
                    if ($v['sum_inventory_deduction'] <= 0) unset($list[$key]);
                    break;
                case 'minus' :
                    if ($v['sum_inventory_deduction'] >= 0) unset($list[$key]);
                    break;
                case 'zero' :
                    if ($v['sum_inventory_deduction'] != 0) unset($list[$key]);
                    break;
            }
            switch ($inventory_type_two){
                case 'positive' :
                    if ($v['sum_inventory_deduction'] > 0) unset($list[$key]);
                    break;
                case 'minus' :
                    if ($v['sum_inventory_deduction'] < 0) unset($list[$key]);
                    break;
                case 'zero' :
                    if ($v['sum_inventory_deduction'] == 0) unset($list[$key]);
                    break;
            }
        }
        unset($v);
        if (!empty($params['onway_amount_type']) || $params['onway_amount_type'] === '0') {
            switch ($params['onway_amount_type']) {
                case '0':
                    $func = function ($v) {return $v['amount_onway'] > -0.005 && $v['amount_onway'] < 0.005;};
                    break;
                case '1':
                    $func = function ($v) {return $v['amount_onway'] >= 0.005;};
                    break;
                case '-1':
                    $func = function ($v) {return $v['amount_onway'] <= -0.005;};
                    break;
            }
            $list = array_filter($list, $func);
        }
        if (!empty($params['onway_invoice_type']) || $params['onway_invoice_type'] === '0') {
            switch ($params['onway_invoice_type']) {
                case '0':
                    $func = function ($v) {return $v['invoice_onway'] > -0.005 && $v['invoice_onway'] < 0.005;};
                    break;
                case '1':
                    $func = function ($v) {return $v['invoice_onway'] >= 0.005;};
                    break;
                case '-1':
                    $func = function ($v) {return $v['invoice_onway'] <= -0.005;};
                    break;
            }
            $list = array_filter($list, $func);
        }
        $onway_amount = 0;
        foreach ($list as $v) {
            $onway_amount += $v['amount_onway_cny'];
        }
//        $onway_amount = M()->table($query2->buildSql().' tmp')->field('sum((amount_paid_ori-amount_in_warehouse_ori) * po_date_rate) as onway_amount')->find();
        $this->data['onway_amount'] = number_format($onway_amount, 2);
        $this->data['total'] = count($list);
        $this->data['list'] = $params['page_size'] > 0 ? array_slice($list, $offset, $params['page_size']) : $list;
        $this->data['page'] = $params['page'];
        $this->data['page_size'] = $params['page_size'];
        $this->code = 2000;
        return true;
    }

    public function export($params)
    {
        $this->listData($params);
        $data = $this->data['list'];
        $exportExcel = new ExportExcelModel();
        $key = 'A';

        $exportExcel->attributes = [
            $key++ => ['name' => L('采购单号'), 'field_name' => 'procurement_number'],
            $key++ => ['name' => L('当前状态'), 'field_name' => 'status'],
            $key++ => ['name' => L('采购po时间'), 'field_name' => 'prepared_time'],
            $key++ => ['name' => L('所属公司'), 'field_name' => 'our_company'],
            $key++ => ['name' => L('供应商'), 'field_name' => 'supplier'],
            $key++ => ['name' => L('采购团队'), 'field_name' => 'purchase_team'],
            $key++ => ['name' => L('采购人员'), 'field_name' => 'purchaser'],
            $key++ => ['name' => L('销售团队'), 'field_name' => 'sale_team'],
            $key++ => ['name' => L('币种'), 'field_name' => 'amount_currency'],
            $key++ => ['name' => L('综合付款金额'), 'field_name' => 'amount_paid'],
            $key++ => ['name' => L('综合入库金额'), 'field_name' => 'amount_in_warehouse'],
            $key++ => ['name' => L('已收发票金额'), 'field_name' => 'invoice_accepted'],
            $key++ => ['name' => L('在途库存金额'), 'field_name' => 'amount_onway'],
            $key++ => ['name' => L('在途发票金额'), 'field_name' => 'invoice_onway'],
            $key++ => ['name' => L('产生抵扣金'), 'field_name' => 'sum_come_deduction'],
            $key++ => ['name' => L('使用抵扣金'), 'field_name' => 'sum_use_deduction'],
            $key++ => ['name' => L('在途库存金额（计算抵扣金）'), 'field_name' => 'sum_inventory_deduction'],
            $key++ => ['name' => L('po日期兑人民币汇率'), 'field_name' => 'po_date_rate'],
            $key++ => ['name' => L('对账备注'), 'field_name' => 'reconciliation_remark'],
        ];

        $exportExcel->data = $data;
        $exportExcel->export();
    }
}