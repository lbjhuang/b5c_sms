<?php
/**
 * User: yuanshixiao
 * Date: 2018/11/23
 * Time: 14:31
 */

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class RelevanceLogic extends BaseLogic
{
    private $relevance_m;
    private $order_m;
    private $goods_m;
    private $ship_m;
    private $warehouse_m;
    private $return_m;
    private $invoice_m;

    public $relevance;

    public function orderCancel($relevance_id,$cancel_reason = '',$cancel_voucher = '') {
        M()->startTrans();
        $cancel_voucher_file = $cancel_voucher['save_name'];
        if(!$cancel_reason && !$cancel_voucher_file) {
            $this->error = '请填写取消原因或上传取消凭证';
            return false;
        }
        $relevance = $this->relevanceM()
            ->field('ship_status,payment_status,invoice_status,order_id,has_refund,has_return_goods')
            ->lock(true)
            ->where(['relevance_id'=>$relevance_id])
            ->find();
        if(!($relevance && $relevance['ship_status'] == 0 && $relevance['payment_status'] == 0 && $relevance['invoice_status'] == 0 && $relevance['has_refund'] == 0 && $relevance['has_return_goods'] == 0)) {
            $this->error = '待付款、待发货、待入库、无退款、无退货的采购单可以取消';
            M()->rollback();
            return false;
        }
        $res = $this
            ->relevanceM()
            ->where(['relevance_id'=>$relevance_id])
            ->save([
                'order_status'      => 'N001320500',
                'cancel_reason'     => $cancel_reason,
                'cancel_voucher'    => json_encode($cancel_voucher),
                'last_update_time'  => date('Y-m-d H:i:s'),
                'last_update_user'  => $_SESSION['m_loginname'],
            ]);
        if(!$res) {
            $this->error = '保存失败';
            M()->rollback();
            return false;
        }
//        $warehouse = $this->orderM()->where(['order_id'=>$relevance['order_id']])->getField('warehouse');
//        if($warehouse != 'N000680800') {
            $purchase_no    = $this->orderM()->where(['order_id'=>$relevance['order_id']])->getField('procurement_number');
            $res_on_way_j   = ApiModel::onWayRemove($purchase_no);
            $res_on_way     = json_decode($res_on_way_j,true);
            if($res_on_way['code'] != 2000) {
                $this->error = '删除在途失败';
                ELog::add('删除在途失败'.$res_on_way_j,ELog::ERR);
                M()->rollback();
                return false;
            }
//        }
        (new TbPurActionLogModel())->addLog($relevance_id);
        M()->commit();
        $this->cancelEmail($relevance_id);
        return true;
    }

    public function isReserved($relevance_id) {
        $procurement_number = $this->relevanceM()
            ->alias('t')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->where(['relevance_id'=>$relevance_id])
            ->getField('procurement_number');
        $is_reserved = D('TbWmsBatch')
            ->where(['purchase_order_no' => $procurement_number, 'vir_type' => 'N002440200', 'occupied' => ['gt', 0]])
            ->getField('id') ? true : false;
        return $is_reserved;
    }

    public function relevanceM() {
        if($this->relevance_m)
            return $this->relevance_m;
        return $this->relevance_m = D('TbPurRelevanceOrder');
    }

    public function orderM() {
        if($this->order_m)
            return $this->order_m;
        return $this->order_m = D('TbPurOrderDetail');
    }

    public function model($name) {
        $name_arr = ['relevance', 'order', 'goods', 'ship', 'warehouse', 'payment', 'invoice', 'sell', 'deduction_detail', 'compensation_detail'];
        $model_name_arr = [
            'relevance'         => 'TbPurRelevanceOrder',
            'order'             => 'TbPurOrderDetail',
            'goods'             => 'TbPurGoodsInformation',
            'ship'              => 'TbPurShip',
            'warehouse'         => 'TbPurWarehouse',
            'payment'           => 'TbPurPayment',
            'invoice'           => 'TbPurInvoice',
            'sell'              => 'TbPurSellInformation',
            'deduction_detail'  => 'Purchase/DeductionDetail',
            'compensation_detail'      => 'Purchase/DeductionCompensationDetail' 
        ];
        if(!in_array($name,$name_arr)) {
            $this->error = $name . '模型不存在';
            return false;
        }
        $key = $name.'_m';
        if($this->$key) {
            return $this->$key;
        }
        $this->$key = D($model_name_arr[$name]);
        return $this->$key;
    }

    private function cancelEmail($relevance_id) {
        //采购单收件人
        $purchase_info = $this->relevanceM()
            ->alias('t')
            ->field('t.prepared_by,c.ETC3 legal_man,a.procurement_number,c.CD_VAL payment_company,cancel_reason,cancel_voucher')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('left join tb_ms_cmn_cd b on b.CD=a.payment_company')
            ->join('left join tb_ms_cmn_cd c on c.CD=a.payment_company')
            ->where(['t.relevance_id'=>$relevance_id])
            ->find();
        $to     = explode(',',$purchase_info['legal_man']);
        $to[]   = $purchase_info['prepared_by'];
        //预定在途需求对应销售收件人
        $demand_to = M('demand','tb_sell_')
            ->alias('t')
            ->field('t.seller,d.sales_assistant_by')
            ->join('inner join tb_wms_batch_order a on a.ORD_ID=t.demand_code')
            ->join('inner join tb_wms_batch b on b.id=a.batch_id')
            ->join('left join tb_crm_sp_supplier c on c.SP_CHARTER_NO=t.customer_charter_no and c.DATA_MARKING=1')
            ->join('left join tb_con_division_client d on d.supplier_id=c.ID')
            ->where(['b.purchase_order_no'=>$purchase_info['procurement_number'],'a.vir_type'=>'N002440200'])
            ->select();
        if($demand_to) {
            $to = array_merge($to,array_column($demand_to, 'seller'), explode(',',implode(',', array_column($demand_to, 'sales_assistant_by'))));
        }
        $to = array_unique($to);
        $title = '采购订单取消提醒';
        $content = <<<EOF
采购单{$purchase_info['procurement_number']}已取消。<br />
操作人：{$_SESSION['m_loginname']}<br />
采购同事：{$purchase_info['prepared_by']}<br />
采购团队：{$purchase_info['payment_company']}<br />
取消原因：{$purchase_info['cancel_reason']}<br />
取消凭证：见附件（或无）
EOF;
        $attachment_name    = json_decode($purchase_info['cancel_voucher'],true)['save_name'];
        $attachment         = $attachment_name ? (new FileDownloadModel())->getFilePath($attachment_name) : '';
        $email_m            = new SMSEmail() ;
        foreach ($to as $v) {
            $to_addr = $v.'@gshopper.com';
            $email_m->sendEmail($to_addr,$title,$content,'',$attachment);
        }
    }

    public function flashPaymentStatus($relevance_id) {
        $this->relevanceM()->startTrans();
        $order_id       = $this->relevanceM()->lock(true)->where(['relevance_id'=>$relevance_id])->getField('order_id');
        $order          = $this->orderM()->where(['order_id'=>$order_id])->find();
        $payment_status = D('TbPurPayment')->paymentStatusCheck($order);
        if($payment_status === false) {
            $this->error = D('TbPurPayment')->getError();
            $this->relevanceM()->rollback();
            return false;
        }
        $res = $this->relevanceM()->where(['relevance_id'=>$relevance_id])->save(['payment_status'=>$payment_status]);
        if($res !== false) {
            $this->relevanceM()->commit();
            return true;
        }else {
            $this->relevanceM()->rollback();
            $this->error = '采购单付款状态保存失败';
            return false;
        }
    }

    public function getPurchaserEmail($relevance_ids) {
        $purchaser  = $this->relevanceM()->where(['relevance_id'=>['in', (array)$relevance_ids]])->getField('prepared_by',true);
        $recipients =array_map(function($value) {
            return $value.'@gshopper.com';
        }, $purchaser);
        return $recipients;
    }

    public function orderDetail($relevance_id) {
        $relevance              = $this->relevanceM()->where(['relevance_id'=>$relevance_id])->find();
        $order                  = $this->orderM()->where(['order_id'=>$relevance['order_id']])->find();
        $order['payment_info']  = json_decode($order['payment_info'],true);
        $order['source_country_val'] = D('TbCrmSite')->where(['ID'=>$order['source_country']])->getField('NAME');
        if($order['payment_type'] == 1) {
            foreach ($order['payment_info'] as $k => $v) {
                $order['payment_info'][$k]['payment_node_val'] = cdVal($v['payment_node']);
            }
        }
        $order['sell_team']     = $this->model('sell')->where(['sell_id'=>$relevance['sell_id']])->getField('sell_team');
        $order['has_small_team'] = 'N';
        if ($order['sell_team']) {
            // 是否有小团队，根据销售团队的CD值的ETC5是否为“有小团队”
            $has_small_team = M('ms_cmn_cd', 'tb_')->where(['CD' => $order['sell_team']])->getField('ETC5');
            if ($has_small_team === '有小团队') {
                $order['has_small_team'] = 'Y';
            }
        }
        

        // 预付款和尾款相关信息
        $pay_field_info = "action_type_cd,days,percent,pre_paid_date";
        $order['pre_pay'] = D('Scm/PurClause')->field($pay_field_info)->where(['purchase_id'=>$relevance_id, 'clause_type' => '1'])->select();
        foreach ($order['pre_pay'] as $key => &$vl) {
            $vl['percent'] = sprintf("%.2f",$vl['percent']);
        }
        $order['end_pay'] = D('Scm/PurClause')->field($pay_field_info)->where(['purchase_id'=>$relevance_id, 'clause_type' => '2'])->find();

        $goods                  = $this->model('goods')
            ->alias('t')
            ->field('t.*,a.upc_id,a.upc_more,sum(b.warehouse_number) warehouse_number,sum(b.warehouse_number_broken) warehouse_number_broken,sum(case c.warehouse_status when 1 then 0 else b.ship_number - b.warehouse_number - b.warehouse_number_broken end) unwarehoused_number')
            ->join(PMS_DATABASE.'.product_sku a on a.sku_id=t.sku_information')
            ->join('tb_pur_ship_goods b on b.information_id=t.information_id')
            ->join('tb_pur_ship c on c.id=b.ship_id')
            ->where(['t.relevance_id'=>$relevance_id])
            ->group('t.information_id')
            ->select();
        foreach ($goods as $k => $v) {
            $goods[$k]['return_number']             = D('Purchase/Return','Logic')->getPurchaseGoodsReturnNum($v['information_id']);
            $goods[$k]['return_out_store_number']   = D('Purchase/Return','Logic')->getPurchaseGoodsReturnOutStoreNum($v['information_id']);
            $goods[$k]['sell_small_team_arr'] = json_decode($goods[$k]['sell_small_team_json'], true);
            if($v['upc_more']) {
                $upc_more_arr = explode(',', $v['upc_more']);
                array_unshift($upc_more_arr, $v['upc_id']);
                $goods[$k]['upc_id'] = implode(",\n", $upc_more_arr);
            }
        }
        $goods      = SkuModel::getInfo($goods,'sku_information',['spu_name','attributes','image_url']);
        $payment    = (new Model())->table('tb_pur_payment pp')
            ->field('pp.*, pa.payable_date_after,pa.payment_at,pa.billing_at,pa.billing_currency_cd')
            ->join('left join tb_pur_payment_audit pa on pp.payment_audit_id = pa.id')
            ->where(['pp.relevance_id'=>$relevance_id])
            ->select();
        // 根据应付id数组，获取对应的应付触发操作
        $payment = D('Scm/PurOperation')->getAssemOperationInfo($payment, '1');

        $ship       = $this->model('ship')
            ->alias('t')
            ->field('t.id,t.bill_of_landing,t.warehouse_id,t.need_warehousing,t.shipping_number,t.warehouse_status,warehouse,t.arrival_date,a.warehouse_number+a.warehouse_number_broken warehouse_number,warehouse_code,a.warehouse_user,a.warehouse_time')
            ->join('tb_pur_warehouse a on a.ship_id=t.id')
            ->where(['relevance_id'=>$relevance_id])
            ->select();
        $refund     = D('TbFinClaim')
            ->field('t.id,a.account_transfer_no,a.currency_code,t.claim_amount')
            ->alias('t')
            ->join('tb_fin_account_turnover a on a.id=t.account_turnover_id')
            ->where(['t.order_id'=>$relevance['order_id'],['t.order_type'=>'N001950600']])
            ->select();
        $return     = D('Purchase/Return','Logic')->getPurchaseReturn($relevance_id);
        $invoice    = $this->model('invoice')->where(['relevance_id'=>$relevance_id])->select();
        $deduction  = $this
            ->model('deduction_detail')
            ->field('tb_pur_deduction_detail.id,turnover_type,deduction_type_cd,deduction_amount,tb_pur_deduction_detail.remark,deduction_voucher,created_at,tb_sell_quotation.ship_time')
            ->join("left join tb_pur_order_detail on tb_pur_order_detail.procurement_number = tb_pur_deduction_detail.order_no")
            ->join("left join tb_sell_quotation on tb_sell_quotation.quotation_code = tb_pur_order_detail.procurement_number")
            ->where(['order_no'=>$order['procurement_number'],'order_type_cd'=>DeductionDetailModel::$order_type['purchase'],'is_revoke'=>0])
            ->select();
        // 根据抵扣金详情的id数组，获取对应的抵扣金触发操作
        $deduction = D('Scm/PurOperation')->getAssemOperationInfo($deduction, '2');
        // 赔偿返利金明细记录 来源，金额，备注，凭证文件，变动时间
        $compensation = $this
            ->model('compensation_detail')
            ->field('tb_pur_deduction_compensation_detail.id, deduction_type_cd, turnover_type, tb_pur_deduction_compensation_detail.remark, deduction_voucher, tb_pur_deduction_compensation_detail.created_at, deduction_amount')
            ->join("left join tb_pur_deduction_compensation on tb_pur_deduction_compensation.id = tb_pur_deduction_compensation_detail.deduction_id")
            ->where(['order_no' => $order['procurement_number'], 'is_revoke'=>0])
            ->select();

        $money_paid = 0;
        foreach ($payment as $v) {
            $money_paid = bcadd($money_paid, bcmul($v['amount_account'],$v['exchange_tax_account'],8), 8);
        }
        $money_deduction_create = 0;
        $money_deduction_use    = 0;
        foreach ($deduction as $v) {
            if($v['turnover_type'] == DeductionDetailModel::$turnover_type['use']) {
                $money_deduction_use = bcadd($money_deduction_use,$v['deduction_amount'],8);
            }elseif ($v['deduction_type_cd'] == DeductionDetailModel::$deduction_type['over_pay']) {
                $money_deduction_create = bcadd($money_deduction_create,$v['deduction_amount'],8);
            }
        }
        foreach ($compensation as &$vv) {
            if($vv['turnover_type'] == DeductionDetailModel::$turnover_type['use']) {
                $money_deduction_use = bcadd($money_deduction_use,$vv['deduction_amount'],8);
                $vv['deduction_type_cd_val'] = '应付单使用'; // 按产品飞松说法，使用抵扣金类型只用这个，后期如果多个可以新增补充CODE
            }elseif ($vv['deduction_type_cd'] == DeductionDetailModel::$deduction_type['over_pay']) {
                $money_deduction_create = bcadd($money_deduction_create,$vv['deduction_amount'],8);
            }
        }
        $money_refund   = 0;
        foreach ($refund as $v) {
            $return_rate = exchangeRateConversion(cdVal($v['currency_code']), cdVal($order['amount_currency']), str_replace('-','',$relevance['sou_time']));
            $money_refund = bcadd($money_refund, bcmul($v['claim_amount'], $return_rate, 8), 8);
        }
        $money_paid_over_all = bcsub($money_paid, $money_refund, 8);

        $money_warehouse    = 0;
        $money_return       = 0;
        foreach ($goods as $v) {
            $money_warehouse    = bcadd($money_warehouse, bcmul($v['warehouse_number'] + $v['warehouse_number_broken'], bcadd($v['unit_price'], $v['unit_expense'], 8), 8), 8);
            $money_return       = bcadd($money_return, bcmul($v['return_out_store_number'], bcadd($v['unit_price'], $v['unit_expense'], 8), 8), 8);
        }
        $money_warehouse_over_all = bcsub($money_warehouse, $money_return, 8);

        $money_invoice              = 0;
        $money_invoice_write_off    = 0;
        foreach ($invoice as $v) {
            if($v['status'] == 1) {
                $money_invoice = bcadd($money_invoice, $v['invoice_money'], 8);
            }
        }
        $money_invoice_over_all = bcsub($money_invoice, $money_invoice_write_off, 8);
        $money_on_way           = bcsub($money_paid_over_all, $money_warehouse_over_all, 8);
        $money_invoice_on_way   = bcsub($money_paid_over_all, $money_invoice_over_all, 8);

        $finance = [];
        $finance_keys = [
            'money_paid',
            'money_refund',
            'money_deduction_use',
            'money_deduction_create',
            'money_paid_over_all',
            'money_warehouse',
            'money_return',
            'money_warehouse_over_all',
            'money_invoice',
            'money_invoice_write_off',
            'money_invoice_over_all',
            'money_on_way',
            'money_invoice_on_way',
        ];
        foreach ($finance_keys as $v) {
            $finance[$v] = round($$v,2);
        }

        $detail_keys = [
            'relevance',
            'order',
            'goods',
            'payment',
            'ship',
            'refund',
            'return',
            'invoice',
            'finance',
            'deduction',
            'compensation'
        ];
        foreach ($detail_keys as $v) {
            $detail[$v] = $$v;
        }
        $OrderDetailAction = A('Home/OrderDetail');
        $detail['relevance']['is_marking_end_billing'] = $OrderDetailAction->checkIsMarkingEndBilling($relevance_id);
        return $this->formatOrderDetailData($detail);
    }

    public function orderDetailArray($relevance_ids,$order_ids)
    {
        $where['relevance_id'] = ['IN', $relevance_ids];
        $relevance = D('TbPurRelevanceOrder')
            ->where($where)
            ->find();
        $where_orderM['order_id'] = ['IN', $order_ids];
        $order = D('TbPurOrderDetail')
            ->where($where_orderM)
            ->find();
        $payment = $this->model('payment')->where($where)->select();;
        $refund = D('TbFinClaim')
            ->field('t.id,a.account_transfer_no,a.currency_code,t.claim_amount')
            ->alias('t')
            ->join('tb_fin_account_turnover a on a.id=t.account_turnover_id')
            ->where(['t.order_id' =>['IN',$order_ids] , ['t.order_type' => 'N001950600']])
            ->select();
        $money_paid_over_all = $this->calculationMoneyPaidOverAll($payment, $refund, $order, $relevance);

        return $money_paid_over_all;
    }
    public function formatOrderDetailData($detail) {
        foreach ($detail as $k => $v) {
            if(is_array($v)) {
                $detail[$k] = $this->formatOrderDetailData($v);
            }else {
                if(strpos($v, 'N00') !== false) {
                    $detail[$k.'_val'] = cdVal($v);
                }
            }
        }
        return $detail;
    }

    public function doc($detail) {
        foreach ($detail as $k => $v) {
            echo " **{$k}参数说明** \n\n";
            echo "|参数名|类型|说明|\n";
            echo "|:-----  |:-----|-----                           |\n";
            foreach ($v as $key => $val) {
                if(is_array($val)) {
                    foreach ($val as $key_t => $value) {
                        if($key == 0) {
                            echo "|$key_t |string   |  |\n";
                        }
                    }
                }else {
                    echo "|$key |string   |  |\n";
                }
            }
            echo "\n";
        }
        exit;
    }

    public function logList($param) {
        import('ORG.Util.Page');
        $count  = D('TbPurActionLog')->where(['relevance_id'=>$param['relevance_id']])->count();
        $page   = new Page($count,$param['rows']?$param['rows']:20);
        $list   = D('TbPurActionLog')
            ->where(['relevance_id'=>$param['relevance_id']])
            ->limit($page->firstRow.','.$page->listRows)
            ->order('id desc')
            ->select();
        return ['list'=>$list,'page'=>['total_rows'=>$count]];
    }

    public function updateReturnStatus($relevance_id) {
        $has_return = D('Purchase/Return','Logic')->relevanceHasReturn($relevance_id);
        $save['has_return_goods'] = $has_return ? 1 : 0;
        $res = D('TbPurRelevanceOrder')->where(['relevance_id'=>$relevance_id])->save($save);
        if($res === false) {
            $this->error = '保存失败';
            return false;
        }
    }

    /**
     * @param $payment
     * @param $refund
     * @param $order
     * @param $relevance
     * @return string
     */
    private function calculationMoneyPaidOverAll($payment, $refund, $order, $relevance)
    {
        $money_paid = 0;
        foreach ($payment as $v) {
            $money_paid = bcadd($money_paid, $v['amount_account'], 8);
        }
        $money_refund = 0;
        foreach ($refund as $v) {
            $return_rate = exchangeRateConversion(cdVal($v['currency_code']), cdVal($order['amount_currency']), str_replace('-', '', $relevance['sou_time']));
            $money_refund = bcadd($money_refund, bcmul($v['claim_amount'], $return_rate, 8), 8);
        }
        $money_paid_over_all = bcsub($money_paid, $money_refund, 8);
        return $money_paid_over_all;
    }

}
