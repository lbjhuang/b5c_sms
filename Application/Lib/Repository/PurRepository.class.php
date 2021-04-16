<?php
/**
 * User: fuming
 * Date: 19/02/25
 * Time: 18:49
 */

@import("@.Model.StringModel");

use Application\Lib\Model\StringModel;

/**
 * Class PurRepository
 */
class PurRepository extends Repository
{

    public $model;

    public function __construct()
    {
        parent::__construct();
    }


    // 获取符合条件的采购单
    public function getPurOrderFieldByMap($field = [], $where = []) 
    {
        if (!$where) {
            return false;
        }
        $list = D('TbPurOrderDetail')
            ->alias('t')
            ->field($field)
            ->join('left join tb_pur_relevance_order pro on pro.order_id=t.order_id')
            ->where($where)
            ->select();
        return $list;
    } 

    public function getOrderSelect($wheres)
    {
        if ($wheres['tb_fin_claim.account_turnover_id']) {
            $account_turnover_id = $wheres['tb_fin_claim.account_turnover_id'];
            unset($wheres['tb_fin_claim.account_turnover_id']);
        }
        $wheres['tb_pur_relevance_order.order_status'] = 'N001320300';
        $where_string                                  = 'tb_pur_order_detail.order_id = tb_pur_relevance_order.order_id';
        $db_res                                        = $this->model->table('(tb_pur_relevance_order,tb_pur_order_detail)')
            ->field([
                'tb_pur_order_detail.order_id',
                'tb_pur_order_detail.procurement_number',
                'tb_pur_order_detail.online_purchase_order_number',
                'tb_pur_order_detail.supplier_id',
                'tb_pur_order_detail.amount_currency',
                'tb_pur_order_detail.amount',
                'tb_pur_order_detail.payment_company',
//                'tb_pur_order_detail.create_time as po_time',
                'tb_pur_relevance_order.relevance_id',
                'tb_pur_relevance_order.warehouse_status',
                'tb_pur_relevance_order.payment_status',
                'tb_pur_relevance_order.prepared_by',
                'tb_pur_relevance_order.has_refund',
                'tb_pur_relevance_order.has_return_goods',
                'tb_pur_relevance_order.sou_time as po_time',
                'tb_pur_sell_information.sell_team as sale_team',
                'tb_fin_claim.id AS claim_id',
                'tb_fin_claim.summary_amount',
                '(select sum(amount_account * exchange_tax_account) from tb_pur_payment where relevance_id = tb_pur_relevance_order.relevance_id) as amount_pay_ori',
            ])
            ->join("LEFT JOIN tb_fin_claim ON tb_pur_order_detail.order_id = tb_fin_claim.order_id AND tb_fin_claim.order_type = 'N001950600'  AND tb_fin_claim.account_turnover_id = {$account_turnover_id}")
            ->join("LEFT JOIN tb_pur_sell_information ON tb_pur_relevance_order.sell_id = tb_pur_sell_information.sell_id")
            ->where($wheres)
            ->where($where_string)
            ->select();

        foreach ($db_res as &$value) {
            //【已认领的退款金额（订单币种）】
            $refund       = D('TbFinClaim')
                ->field('t.id,a.account_transfer_no,a.currency_code,t.claim_amount')
                ->alias('t')
                ->join('tb_fin_account_turnover a on a.id=t.account_turnover_id')
                ->where(['t.order_id' => $value['order_id'], ['t.order_type' => 'N001950600']])
                ->select();
            $money_refund = 0;
            foreach ($refund as $v) {
                $return_rate  = exchangeRateConversion(cdVal($v['currency_code']), cdVal($value['amount_currency']), str_replace('-', '', $value['po_time']));
                $money_refund = bcadd($money_refund, bcmul($v['claim_amount'], $return_rate, 8), 8);
            }
            $value['amount_claim_ori']       = $money_refund;
            $value['amount_claim_ori_other'] = bcsub($money_refund, $value['summary_amount'], 8);

            $goods                   = $this->model->table('tb_pur_goods_information')
                ->alias('t')
                ->field('t.*,sum(b.warehouse_number) warehouse_number,sum(b.warehouse_number_broken) warehouse_number_broken')
                ->where(["relevance_id" => $value['relevance_id']])
                ->join('tb_pur_ship_goods b on b.information_id=t.information_id')
                ->where(['relevance_id' => $value['relevance_id']])
                ->group('t.information_id')
                ->select();
            $amount_in_warehouse_ori = $return_goods_amount_ori = 0.00;
            //同一个采购订单多个商品分别计算在求和
            foreach ($goods as $v) {
                $return_num              = D('Purchase/Return', 'Logic')->getPurchaseGoodsReturnOutStoreNum($v['information_id']);//退货数量
                $amount_in_warehouse_ori = bcadd($amount_in_warehouse_ori, bcmul($v['warehouse_number']
                    + $v['warehouse_number_broken'], bcadd($v['unit_price'], $v['unit_expense'], 8), 8), 8);//已入库金额

                $return_goods_amount_ori = bcadd($return_goods_amount_ori, bcmul($return_num,
                    bcadd($v['unit_price'], $v['unit_expense'], 8), 8), 8);//已退货金额
            }
            $value['amount_in_warehouse_ori'] = $amount_in_warehouse_ori;
            $value['return_goods_amount_ori'] = $return_goods_amount_ori;
        }
        return $db_res;
    }

    /**
     * 认领详情订单信息展示
     * @param $where
     * @return mixed
     */
    public function getClaimOrders($where)
    {
        $where_string = 'tb_fin_claim.order_id = tb_pur_order_detail.order_id
            AND tb_fin_claim.order_id = tb_pur_relevance_order.order_id  
            AND tb_fin_claim.order_type = \'N001950600\'
        ';
        $db_res       = $this->model->table('(tb_fin_claim,tb_pur_relevance_order,tb_pur_order_detail)')
            ->field([
                'tb_pur_order_detail.order_id',
                'tb_pur_order_detail.procurement_number',
                'tb_pur_order_detail.online_purchase_order_number',
                'tb_pur_order_detail.supplier_id',
                'tb_pur_order_detail.amount_currency',
                'tb_pur_order_detail.amount',
                'tb_pur_order_detail.payment_company',
                'tb_pur_relevance_order.relevance_id',
                'tb_pur_relevance_order.warehouse_status',
                'tb_pur_relevance_order.payment_status',
                'tb_pur_relevance_order.prepared_by',
                'tb_pur_relevance_order.has_refund',
                'tb_pur_relevance_order.has_return_goods',
                'tb_pur_relevance_order.sou_time as po_time',
                'tb_fin_claim.claim_amount',
                'tb_fin_claim.summary_amount',
                'tb_fin_claim.current_remaining_receivable',
                'tb_fin_claim.id AS claim_id',
                'tb_fin_claim.updated_by',
                'tb_fin_claim.updated_at',
                'tb_pur_sell_information.sell_team as sale_team',
                'IFNULL (tb_fin_account_turnover_status.claim_status, "N002550100") as claim_status',
            ])
            ->join('LEFT JOIN tb_fin_account_turnover ON tb_fin_account_turnover.id = tb_fin_claim.account_turnover_id ')
            ->join("LEFT JOIN tb_pur_sell_information ON tb_pur_relevance_order.sell_id = tb_pur_sell_information.sell_id")
            ->join("LEFT JOIN tb_fin_account_turnover_status ON tb_fin_account_turnover.id = tb_fin_account_turnover_status.account_turnover_id")
            ->where($where)
            ->where($where_string, null, true)
            ->select();

        $pur_payment_model = M('pur_payment', 'tb_');
        foreach ($db_res as &$value) {
            //该采购单的【已付款金额（不包含手续费）】
            $pur_payment    = $pur_payment_model->field(['amount_account', 'exchange_tax_account'])->where(['relevance_id' => $value['relevance_id']])->select();
            $amount_pay_ori = 0.00;
            array_map(function ($v) use (&$amount_pay_ori) {
                $amount_pay_ori += bcmul($v['amount_account'], $v['exchange_tax_account'], 8);
            }, $pur_payment);
            $value['amount_pay_ori'] = $amount_pay_ori;
            $refund = D('TbFinClaim')
                ->field('t.id,a.account_transfer_no,a.currency_code,t.claim_amount')
                ->alias('t')
                ->join('tb_fin_account_turnover a on a.id=t.account_turnover_id')
                ->where(['t.order_id' => $value['order_id'], ['t.order_type' => 'N001950600']])
                ->select();
            $money_refund = 0;
            foreach ($refund as $v) {
                $return_rate  = exchangeRateConversion(cdVal($v['currency_code']), cdVal($value['amount_currency']), str_replace('-', '', $value['po_time']));
                $money_refund = bcadd($money_refund, bcmul($v['claim_amount'], $return_rate, 8), 8);
            }
            $value['amount_claim_ori']       = $money_refund;
            $value['amount_claim_ori_other'] = bcsub($money_refund, $value['summary_amount'], 8);

//            $goods = $this->model->table('tb_pur_warehouse_goods wgood')
//                ->field('wgood.warehouse_number, wgood.warehouse_number_broken, pgi.unit_price, pgi.unit_expense, pgi.information_id')
//                ->join('LEFT JOIN tb_pur_warehouse wh ON wgood.warehouse_id = wh.id')
//                ->join('LEFT JOIN tb_pur_ship pship ON pship.id = wh.ship_id')
//                ->join('LEFT JOIN tb_pur_ship_goods sgood2 ON wgood.ship_goods_id = sgood2.id')
//                ->join('LEFT JOIN tb_pur_goods_information pgi ON pgi.information_id = sgood2.information_id')
//                ->where(['pship.relevance_id' => $value['relevance_id']])
//                ->select();
            $goods                   = $this->model->table('tb_pur_goods_information')
                ->alias('t')
                ->field('t.*,sum(b.warehouse_number) warehouse_number,sum(b.warehouse_number_broken) warehouse_number_broken')
                ->where(["relevance_id" => $value['relevance_id']])
                ->join('tb_pur_ship_goods b on b.information_id=t.information_id')
                ->where(['relevance_id' => $value['relevance_id']])
                ->group('t.information_id')
                ->select();
            $amount_in_warehouse_ori = $return_goods_amount_ori = 0.00;
            //同一个采购订单多个商品分别计算在求和
            foreach ($goods as $v) {
                $return_num              = D('Purchase/Return', 'Logic')->getPurchaseGoodsReturnOutStoreNum($v['information_id']);//退货数量
                $amount_in_warehouse_ori = bcadd($amount_in_warehouse_ori, bcmul($v['warehouse_number']
                    + $v['warehouse_number_broken'], bcadd($v['unit_price'], $v['unit_expense'], 8), 8), 8);//已入库金额

                $return_goods_amount_ori = bcadd($return_goods_amount_ori, bcmul($return_num,
                    bcadd($v['unit_price'], $v['unit_expense'], 8), 8), 8);//已退货金额
            }
            $value['amount_in_warehouse_ori'] = $amount_in_warehouse_ori;
            $value['return_goods_amount_ori'] = $return_goods_amount_ori;
        }
        return $db_res;
    }

    /**
     * @param $order_id
     * @param $has_refund
     * @return mixed
     */
    public function updatePurRelevanceOrderRefund($order_id, $has_refund)
    {
        $where['order_id']  = $order_id;
        $save['has_refund'] = $has_refund;
        return $this->model->table('tb_pur_relevance_order')
            ->where($where)
            ->save($save);
    }


    public function checkPurchaseRefundStatus($order_id)
    {
        $where['order_id']   = $order_id;
        $where['order_type'] = 'N001950600';
        return $this->model->table('tb_fin_claim')
            ->where($where)
            ->count(1);
    }

    public function getRelevanceIdByPurOrderNo($where)
    {
        return $this->model->table('tb_pur_order_detail')
            ->alias('t')
            ->field('ro.relevance_id, t.procurement_number')
            ->join('tb_pur_relevance_order ro on t.order_id = ro.order_id')
            ->where($where)
            ->select();
    }
    public function getRelevanceIdByStreamID($stream_id)
    {
        $where['t.stream_id'] = $stream_id;
        return $this->model->table('tb_wms_batch')
            ->alias('t')
            ->field('ro.relevance_id')
            ->where($where)
            ->join('tb_pur_order_detail od on od.procurement_number = t.purchase_order_no')
            ->join('tb_pur_relevance_order ro on od.order_id = ro.order_id')
            ->find();
    }

    // 根据tb_wms_stream.id 获取
    public function getPriceByStreamID($stream_id)
    {
        $where['id'] = $stream_id;
        return $this->model->table('tb_wms_stream')
            ->field('unit_price_origin, currency_id, po_cost')
            ->where($where)
            ->find();
    }

    // 根据payment.id或ship_id获取相关适用条款
    public function getClauseInfoByOperID($oper_id, $table_name)
    {
        $where['t.id'] = $oper_id;
        return $this->model->table($table_name)
            ->alias('t')
            ->field('pc.clause_type, pc.action_type_cd, pc.purchase_id')
            ->where($where)
            ->join('tb_pur_clause pc on pc.purchase_id = t.relevance_id')
            ->select();
    }

    //根据return_id获取相关退货出库的商品数量和单价（正品）
    public function getReturnInfoByReturnID($return_id)
    {
        $where['t.return_id']   = $return_id;
        $where['t.vir_type_cd'] = 'N002440100'; //正品
        return $this->model->table('tb_pur_return_goods')
            ->alias('t')
            ->field('t.return_number, t.information_id, gi.unit_price, gi.unit_expense, od.amount_currency')
            ->where($where)
            ->join('tb_pur_goods_information gi on gi.information_id = t.information_id')
            ->join('tb_pur_relevance_order ro on ro.relevance_id = gi.relevance_id')
            ->join('tb_pur_order_detail od on od.order_id = ro.order_id')
            ->select();
    }

    public function getReturnOrderInfoByReturnID($return_id)
    {
        $where['t.return_id']   = $return_id;
        $where['t.vir_type_cd'] = 'N002440100'; //正品
        return $this->model->table('tb_pur_return_goods')
            ->alias('t')
            ->field('t.return_number, t.information_id, gi.unit_price, gi.unit_expense, od.amount_currency, pro.relevance_id')
            ->where($where)
            ->join('tb_pur_goods_information gi on gi.information_id = t.information_id')
            ->join('tb_pur_relevance_order ro on ro.relevance_id = gi.relevance_id')
            ->join('tb_pur_order_detail od on od.order_id = ro.order_id')
            ->join('tb_pur_return_order pro on pro.id = t.return_order_id')
            ->select();
    }

    // 根据payment.id获取币种，确认付款金额
    public function getInfoByPaymentID($payment_id)
    {
        $where['id'] = $payment_id;
        return $this->model->table('tb_pur_payment')
            ->alias('t')
            ->field('t.use_deduction, t.amount_deduction, t.amount_confirm, od.amount_currency')
            ->where($where)
            ->join('tb_pur_relevance_order ro on ro.relevance_id = t.relevance_id')
            ->join('tb_pur_order_detail od on od.order_id = ro.order_id')
            ->find();
    }

    // 根据information_id获取对应的价格
    public function getPriceByInformationID($information_id)
    {
        $where['t.information_id'] = $information_id;
        return $this->model->table('tb_pur_goods_information')
            ->alias('t')
            ->field('t.unit_price, t.unit_expense, t.relevance_id, od.amount_currency')
            ->where($where)
            ->join('tb_pur_relevance_order ro on ro.relevance_id = t.relevance_id')
            ->join('tb_pur_order_detail od on od.order_id = ro.order_id')
            ->find();
    }

    // 根据tb_pur_ship_goods.id获取商品价格
    public function getPriceByGoodsID($goods_id)
    {
        $where['t.id'] = $goods_id;
        return $this->model->table('tb_pur_ship_goods')
            ->alias('t')
            ->field('gi.unit_price, gi.unit_expense, od.amount_currency')
            ->where($where)
            ->join('tb_pur_goods_information gi on gi.information_id = t.information_id')
            ->join('tb_pur_relevance_order ro on ro.relevance_id = gi.relevance_id')
            ->join('tb_pur_order_detail od on od.order_id = ro.order_id')
            ->find();
    }

    // tb_pur_relevance_order.relevance_id获取商品价格
    public function getPriceByRelevanceID($relevance_id)
    {
        $where['ro.relevance_id'] = $relevance_id;;
        return $this->model->table('tb_pur_goods_information')
            ->alias('gi')
            ->field('gi.goods_number, gi.shipped_number, gi.return_number, gi.ship_end_number, gi.unit_price, gi.unit_expense, od.amount_currency')
            ->where($where)
            ->join('tb_pur_relevance_order ro on ro.relevance_id = gi.relevance_id')
            ->join('tb_pur_order_detail od on od.order_id = ro.order_id')
            ->select();
    }

    // 获取累计生成预付款应付金额
    public function advanceAmountCal($relevance_id)
    {
        $where['pp.relevance_id']   = $relevance_id;
        $where['po.money_type']     = '1';
        $where['po.action_type_cd'] = 'N002870001';

        return $this->model->table('tb_pur_payment')
            ->alias('pp')
            ->field('sum(pp.amount_payable) as advanceAmount')
            ->where($where)
            ->join('tb_pur_operation po on po.main_id = pp.id')
            ->find();
    }

    // 根据claim_id获取 tb_fin_claim.summary_amount
    public function getFinClaim($claim_id)
    {
        $where['t.id'] = $claim_id;
        return $this->model->table('tb_fin_claim')
            ->alias('t')
            ->field('t.summary_amount, pod.amount_currency')
            ->where($where)
            ->join('tb_pur_order_detail pod on pod.order_id = t.order_id')
            ->find();
    }

    // 根据ship_id获取抵扣/应付金额
    public function getShipGoodsAmountSum($ship_id)
    {
        //2019-08-23 产品调整需求 标记发货完结时入库数量需要加上残次品入库数量
        $res = $this->model->table('tb_pur_ship_goods')
            ->alias('t')
            ->field('t.ship_number, t.warehouse_number, t.warehouse_number_broken, gi.unit_price, gi.unit_expense, od.amount_currency')
            ->where(["t.ship_id" => $ship_id])
            ->join('tb_pur_goods_information gi on t.information_id = gi.information_id')
            ->join('tb_pur_relevance_order ro on ro.relevance_id = gi.relevance_id')
            ->join('tb_pur_order_detail od on od.order_id = ro.order_id')
            ->select();
        if (!$res) {
            return false;
        }
        return $res;
    }

    /*************start:采购抵扣金相关**************/
    public function getDeductionCompensationList($where, $limit, $is_excel)
    {
        $query      = M('pur_deduction_compensation', 'tb_')->where($where);
        $query_copy = clone $query;

        $pages['total']        = $query->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        if (false === $is_excel) {
            $query_copy->limit($limit[0], $limit[1]);
        }
        $db_res = $query_copy->order('created_at desc')->select();
        return [$db_res, $pages];
    }
    /**抵扣金账户列表
     * @param $where
     * @param $limit
     * @param $is_excel
     * @return array
     */
    public function getDeductionList($where, $limit, $is_excel)
    {
        $query      = M('pur_deduction', 'tb_')->where($where);
        $query_copy = clone $query;

        $pages['total']        = $query->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        if (false === $is_excel) {
            $query_copy->limit($limit[0], $limit[1]);
        }
        $db_res = $query_copy->order('created_at desc')->select();
        return [$db_res, $pages];
    }

    /**抵扣金账户详情列表
     * @param $where
     * @param $limit
     * @param $is_excel
     * @return array
     */
    public function getDeductionDetail($where, $limit, $is_excel)
    {
        $deduction_count = [];
        if ($where['deduction_id']) {
            $deduction_count = M('pur_deduction', 'tb_')
                ->field("used_deduction_amount,unused_deduction_amount,deduction_currency_cd")
                ->find($where['deduction_id']);
            $deduction_count = CodeModel::autoCodeOneVal($deduction_count, ['deduction_currency_cd']);
        }

        $query      = M('pur_deduction_detail', 'tb_')->where($where);
        $query_copy = clone $query;

        $pages['total']        = $query->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        if (false === $is_excel) {
             $query_copy->limit($limit[0], $limit[1]);
        }
        $db_res = $query_copy->order('created_at asc')->select();
        if ($db_res && $deduction_count) {
            $db_res['deduction_count'] = $deduction_count;
        }
        return [$db_res, $pages];
    }

    public function getDeductionCompensationDetail($where, $limit, $is_excel)
    {
        $deduction_count = [];
        if ($where['deduction_id']) {
            $deduction_count = M('pur_deduction_compensation', 'tb_')
                ->field("used_deduction_amount,unused_deduction_amount,deduction_currency_cd")
                ->find($where['deduction_id']);
            $deduction_count = CodeModel::autoCodeOneVal($deduction_count, ['deduction_currency_cd']);
        }

        $query      = M('pur_deduction_compensation_detail', 'tb_')->where($where);
        $query_copy = clone $query;

        $pages['total']        = $query->count();
        //$pages['current_page'] = $limit[0];
        //$pages['per_page']     = $limit[1];
        if (false === $is_excel) {
            //$query_copy->limit($limit[0], $limit[1]); // 详情展示暂时不需要分页 by 产品飞松 #10737
        }
        $db_res['list'] = $query_copy->order('created_at asc')->select();
        if ($db_res && $deduction_count) {
            $db_res['deduction_count'] = $deduction_count;
        }
        return [$db_res, $pages];
    }
    /*************end:采购抵扣金相关**************/

    /*******************************采购结算3.0 start*******************************/
    public function getPaymentAuditList($where, $limit, $is_excel)
    {
        $field = "
        pa.*, pa.status as payable_status, IFNULL((pa.billing_amount + pa.billing_fee), '0.00') as billing_total_amount, oc.payment_manager_by,ss.SP_NAME";
        if (true === $is_excel) {
            $field = " gp.payment_nature,css.SP_NAME as supplier_name, gp.contract_information, gp.contract_no,gp.settlement_type,gp.procurement_nature,gp.invoice_information,
            gp.invoice_type,gp.bill_information,gp.payment_type,gp.payment_remark,
            pa.*, pa.status as payable_status,IFNULL((pa.billing_amount + pa.billing_fee), '0.00') as billing_total_amount, oc.payment_manager_by,hd.DEPT_NM dept_name,ss.SP_NAME";
        }
        $this->model->table();
        //当前用户非Astor.Zhang则只能看到审核人为自己的付款单
        /*if ($_SESSION['m_loginname'] != 'Astor.Zhang') {
            $where['pa.accounting_audit_user'] = ['like',$_SESSION['m_loginname'] . ' %'];
        }*/
        if (!empty($where['pp.payment_no'])) {
            $ids = array('0');
            // 采购应付
            $pur_payment = M('payment','tb_pur_')->field('payment_audit_id')->where(['payment_no'=>$where['pp.payment_no']])->select();
            if (!empty($pur_payment)){
                $ids = array_merge($ids,array_column($pur_payment,'payment_audit_id'));
            }
            // 一般付款
            $pur_payment = M('general_payment_detail', 'tb_')->field('payment_audit_id')->where(['payment_no'=>$where['pp.payment_no']])->select();
            if (!empty($pur_payment)){
                $ids = array_merge($ids,array_column($pur_payment,'payment_audit_id'));
            }
            // 调拨应付
            $wms_payment = M('payment','tb_wms_')->field('payment_audit_id')->where(['payment_no'=>$where['pp.payment_no']])->select();
            if (!empty($wms_payment)){
                $ids = array_merge($ids,array_column($wms_payment,'payment_audit_id'));
            }
            //  售后应付
            $refund_payment = M('order_refund','tb_op_')->field('payment_audit_id')->where(['after_sale_no'=>$where['pp.payment_no']])->select();
            if (!empty($refund_payment)){
                $ids = array_merge($ids,array_column($refund_payment,'payment_audit_id'));
            }
            // 转账换汇
            $transfer_payment = M('fin_account_transfer','tb_')->field('payment_audit_id')->where(['transfer_no'=>$where['pp.payment_no']])->select();
            if (!empty($transfer_payment)){
                $ids = array_merge($ids,array_column($transfer_payment,'payment_audit_id'));
            }
            $where['pa.id'] = array('in',$ids);
            unset( $where['pp.payment_no'] );
        }
        if (true === $is_excel) {
            $query = $this->model->table('tb_pur_payment_audit pa')
                ->field($field)
                ->join('left join tb_con_division_our_company oc on pa.our_company_cd = oc.our_company_cd')
                ->join('left join tb_general_payment gp on pa.id = gp.payment_audit_id')
                ->join('left join tb_crm_sp_supplier ss on ss.ID = pa.our_company_cd')
                ->join('left join tb_hr_dept hd on hd.ID = gp.dept_id')
                ->join('LEFT JOIN tb_crm_sp_supplier css ON css.ID = gp.supplier ')
                ->where($where)
                ->group('pa.id');
        } else {
            $query = $this->model->table('tb_pur_payment_audit pa')
                ->field($field)
                ->join('left join tb_general_payment gp on pa.id = gp.payment_audit_id')
                ->join('left join tb_con_division_our_company oc on pa.our_company_cd = oc.our_company_cd')
                ->join('left join tb_crm_sp_supplier ss on ss.ID = pa.our_company_cd')
                ->where($where)
                ->group('pa.id');
        }
        $query_copy            = clone $query;
        $sub_sql               = $query->buildSql();
        $pages['total']        = M()->table($sub_sql . ' tmp')->count('id');
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        if (false === $is_excel) {
            $query_copy->limit($limit[0], $limit[1]);
        }
        $db_res = $query_copy->order('pa.created_at desc')->select();
        return [$db_res, $pages];
    }

    //应付详情
    public function getPayableDetail($payable_id, $pur_info)
    {
        $filed = 'pa.*,pa.id as payment_audit_id,t.*,ab.company_code,ab.open_bank,ab.account_bank,ab.swift_code,
            od.sp_charter_no,od.supplier_new_id,po.action_type_cd,po.clause_type,po.bill_no,od.online_purchase_order_number,
            od.online_purchase_website,od.contract_number';
        $info = M('payment','tb_pur_')
            ->alias('t')
            ->field($filed)
            ->join('left join tb_pur_payment_audit pa on t.payment_audit_id = pa.id')
            ->join('left join tb_pur_relevance_order rel on t.relevance_id = rel.relevance_id')
            ->join('left join tb_pur_order_detail od on rel.order_id = od.order_id')
            ->join('left join tb_fin_account_bank ab on ab.account_bank = pa.payment_our_bank_account')
            ->join('left join tb_pur_operation po on t.id = po.main_id')
            ->where(['t.id'=>$payable_id])
            ->find();
        $info = CodeModel::autoCodeOneVal($info, [
            'company_code', 'payment_currency_cd','payment_channel_cd',
            'payment_way_cd','platform_cd','online_purchase_website','accounting_return_reason',
            'payable_currency_cd', 'account_currency', 'account_type',
        ]);
        $info['voucher_deduction']    = json_decode($info['voucher_deduction'], true);
        $info['payment_voucher']      = json_decode($info['payment_voucher'], true);
        $info['billing_voucher']      = json_decode($info['billing_voucher'], true);
        $info['payment_attachment']   = json_decode($info['payment_attachment'], true);
        $info['total_average_amount'] = bcadd($info['amount_account'], $info['expense'], 4);
        $info = $this->getPayableExtraInfo($info, $pur_info, false);
        //确认前第一个合同中的收款账号信息
//        $supplier_info = M('crm_contract', 'tb_')->where(['SP_CHARTER_NO'=>$info['sp_charter_no'], 'CRM_CON_TYPE'=>0])->find();
        //  10678-采购应付账户按合同展示  取采购单绑定的合同编号
        $supplier_info = M('crm_contract', 'tb_')->where(['CON_NO'=>$info['contract_number'], 'CRM_CON_TYPE'=>0])->find();
        //读取供应商信息
        $supplier = M('crm_sp_supplier', 'tb_')->where(['ID'=>$info['supplier_new_id'], 'DATA_MARKING'=>0])->find();
        if (!empty($supplier)) {
            $supplier_info['BANK_SETTLEMENT_CODE'] = $supplier['BANK_SETTLEMENT_CODE'];
            $supplier_info['BANK_ADDRESS'] = $supplier['BANK_ADDRESS'];
            $supplier_info['CITY'] = $supplier['CITY'];
            $supplier_info['BANK_ADDRESS_DETAIL'] = $supplier['BANK_ADDRESS_DETAIL'];
            $supplier_info['BANK_POSTAL_CODE'] = $supplier['BANK_POSTAL_CODE'];
            $supplier_info['account_currency'] = $supplier['ACCOUNT_CURRENCY'];
            $supplier_info['account_type'] = $supplier['ACCOUNT_TYPE'];
        }
        $supplier_info = CodeModel::autoCodeTwoVal([$supplier_info], ['account_currency', 'account_type'])[0];
        return [$info, $supplier_info];
    }

    //关联应付单信息
    public function getRelPayableInfo($payment_audit_id, $payment_id)
    {
        if (!$payment_audit_id) return [];
        $field = 'pp.id,rel.relevance_id,pp.payment_no,pp.confirm_user,pp.amount_confirm,
        od.procurement_number,po.action_type_cd,od.amount_currency';
        $db_res = $this->model->table('tb_pur_payment pp')
            ->field($field)
            ->join('left join tb_pur_relevance_order rel on pp.relevance_id = rel.relevance_id')
            ->join('left join tb_pur_order_detail od on rel.order_id = od.order_id')
            ->join('left join tb_pur_operation po on pp.id = po.main_id')
            ->where(['pp.payment_audit_id'=>$payment_audit_id, 'pp.id'=>['neq',$payment_id]])
            ->select();
        return $db_res;
    }

    //获取符合条件的可合并付款的应付单
    public function getMergedPaymentBill($where)
    {
        $filed = 'rel.relevance_id,t.id,pa.payment_audit_no, t.payment_no,od.procurement_number,od.online_purchase_order_number,
            rel.prepared_by,t.amount_payable,t.amount_confirm,pa.payable_date_after,po.action_type_cd,po.clause_type,po.bill_no,od.amount_currency';
        $db_res = M('payment','tb_pur_')
            ->alias('t')
            ->field($filed)
            ->join('left join tb_pur_payment_audit pa on t.payment_audit_id = pa.id')
            ->join('left join tb_pur_relevance_order rel on t.relevance_id = rel.relevance_id')
            ->join('left join tb_pur_order_detail od on rel.order_id = od.order_id')
            ->join('left join tb_pur_operation po on t.id = po.main_id')
            ->where($where)
            ->select();
        $db_res = $this->getPayableExtraInfo($db_res);
        return $db_res;
    }

    //应付单额外信息
    public function getPayableExtraInfo($data, $pur_info = [], $is_two_dimension = true)
    {
        if (empty($pur_info)) {
            $pur_info = D('Scm/PurOperation')->pur_info;
        }
        // 触发操作等信息
        $clause_type = D('Scm/PurOperation')->clause_type;
        if ($is_two_dimension) {
            foreach ($data as &$value) {
                //计算公式
                $value['formula']        = $pur_info[$value['action_type_cd']]['formula'];
                //适用条款
                $value['clause_type']    = $clause_type[$value['clause_type']];
                //关联单据号
                $value['bill_no']        = $value['bill_no'] ? $pur_info[$value['action_type_cd']]['bill_no'] . " : {$value['bill_no']}" : '';
                $value['action_type_cd'] = $pur_info[$value['action_type_cd']]['name'];
            }
        } else {
            //计算公式
            $data['formula'] = $pur_info[$data['action_type_cd']]['formula'];
            //适用条款
            $data['clause_type'] = $clause_type[$data['clause_type']];
            //关联单据号
            $data['bill_no'] = $data['bill_no'] ? $pur_info[$data['action_type_cd']]['bill_no'] . " : {$data['bill_no']}" : '';
            $data['action_type_cd'] = $pur_info[$data['action_type_cd']]['name'];
        }
        return $data;
    }

    /**
     * 根据付款单id获取采购相关信息
     * @param $payment_audit_ids
     * @return mixed
     */
    public function getOrderInfoByPaymentAuditIds($payment_audit_ids)
    {
        $payment_audit_ids = (array) $payment_audit_ids;
        $field = 'a.*,t.ship_status,t.warehouse_status,t.sou_time,t.prepared_by,b.sell_team,pp.id as payment_id,
            pp.payment_no,pp.expense,pp.amount_account,pp.amount_confirm,pp.use_deduction,
            pp.amount_deduction,pp.relevance_id,pp.id,pa.payment_amount,pa.payment_audit_no,
            pa.payment_currency_cd,pa.billing_amount,pa.billing_fee,pa.payable_amount_after,
            pa.billing_at,pa.billing_by,pa.payment_by,pa.status,pa.payment_voucher,pa.billing_voucher,
            pa.id as payment_audit_id,po.money_type,po.action_type_cd';
        return M('relevance_order', 'tb_pur_')
            ->alias('t')
            ->field($field)
            ->join('left join tb_pur_order_detail a on a.order_id = t.order_id')
            ->join('left join tb_pur_sell_information b on b.sell_id =t.sell_id')
            ->join('left join tb_pur_payment pp on t.relevance_id = pp.relevance_id')
            ->join('left join tb_pur_payment_audit pa on pp.payment_audit_id = pa.id')
            ->join('left join tb_pur_operation po on pp.id = po.main_id')
            ->where(['pp.payment_audit_id' => ['in',$payment_audit_ids]])
            ->select();
    }

    /**
     * 根据付款单id获取付款单关信息（一般付款模拟采购单相关信息）
     * @param $payment_audit_ids
     * @return mixed
     */
    public function getPaymentAuditInfoByPaymentAuditIds($payment_audit_ids, $request = [])
    {
        $field = 'pa.id,pa.payment_amount,pa.payment_audit_no,pa.payment_currency_cd,pa.billing_amount,pa.billing_fee,
            pa.payable_amount_before,pa.payable_amount_after,pd.actual_fee_Department,pd.actual_fee_department_id,pp.payment_type,
            pa.billing_at,pa.billing_by,pa.payment_by,pa.status,pa.payment_voucher,pa.billing_voucher,pa.id payment_audit_id,
            pd.payment_no,pd.subdivision_type,pd.subtotal,cd1.CD_VAL payment_type_val,cd2.CD_VAL subdivision_type_val';
        $data = M('pur_payment_audit', 'tb_')->alias('pa')->field($field)
            ->where(['pa.id' => ['in',$payment_audit_ids]])
            ->join('left join tb_general_payment pp on pa.id = pp.payment_audit_id')
            ->join('left join tb_general_payment_detail pd on pa.id = pd.payment_audit_id')
            ->join('left join tb_ms_cmn_cd cd1 on pp.payment_type = cd1.CD')
            ->join('left join tb_ms_cmn_cd cd2 on pd.subdivision_type = cd2.CD')
            ->select();

        foreach ($data as $key => $val) {
            //一般付款追加字段 一级部门 二级部门 模拟采购相关字段
            //如果某付款单对应的部门只有到【Gshopper】这个层级，则此处显示为空。
            $set_null = ($val['actual_fee_Department'] == 'Gshopper') ? true : false;
            $department = explode('>', $val['actual_fee_Department']);
            $department_id = explode(',', $val['actual_fee_department_id']);
            if (!empty($department)) {
                $data[$key]['department_1']       = $set_null ? '' : $department[count($department) - 2];
                $data[$key]['department_2']       = $set_null ? '' : $department[count($department) - 1];
            }
            if (!empty($department_id)) {
                $data[$key]['department_id_1'] = $set_null ? '' : $department_id[count($department_id) - 2];
                $data[$key]['department_id_2'] = $set_null ? '' : $department_id[count($department_id) - 1];
            }
            $data[$key]['billing_fee']        = isset($request['billing_fee']) ? $request['billing_fee'] : $val['billing_fee'];
            $data[$key]['billing_amount']     = isset($request['billing_amount']) ? $request['billing_amount'] : $val['billing_amount'];
            $data[$key]['prepared_by']        = '';
            $data[$key]['procurement_number'] = '';
            //费用细分类型：其他CD_VAL 映射处理去除括号内容
            if ($val['subdivision_type'] == 'N002940013' && strpos($val['subdivision_type_val'], '其他') !== false) {
                $data[$key]['subdivision_type_val'] = '其他';
            }
        }
        return $data;
    }
    /*******************************采购结算3.0 end*******************************/

    /**
     * 获取采购综合付款金额
     * @param $relevance_ids
     * @return float
     */
    public function getOverPaidAmount($relevance_ids)
    {
        $relevance_ids_str = " '" . join("','", $relevance_ids) . "' ";
        $sql = "SELECT
            TRUNCATE (
                SUM(
                    TRUNCATE (
                        amount_account * exchange_tax_account,
                        2
                    )
                ),
                2
            ) money_paid,
            relevance_id
        FROM
            tb_pur_payment
        WHERE
            relevance_id IN ({$relevance_ids_str})
        GROUP BY relevance_id";
        $money_paid_list = $this->model->query($sql);

        foreach ($money_paid_list as $item) {
            $money_paid[$item['relevance_id']] = $item['money_paid'];
        }
        //查询退款金额
        $refund = D('TbFinClaim')
            ->field('a.currency_code,t.claim_amount,od.amount_currency,rel.sou_time,rel.relevance_id')
            ->alias('t')
            ->join('tb_fin_account_turnover a on a.id=t.account_turnover_id')
            ->join('left join tb_pur_order_detail od on t.order_id=od.order_id')
            ->join('left join tb_pur_relevance_order rel on rel.order_id=od.order_id')
            ->where(['rel.relevance_id'=>['in',$relevance_ids], 't.order_type'=>'N001950600'])
            ->select();
        $money_refund = [];
        foreach ($refund as $v) {
            $return_rate = exchangeRateConversion(cdVal($v['currency_code']), cdVal($v['amount_currency']), str_replace('-', '', $v['sou_time']));
            $money_refund[$v['relevance_id']] += bcmul($v['claim_amount'], $return_rate, 2);
        }

        foreach ($money_paid as $key => $item) {
            $result[$key] =  bcsub($item, $money_refund[$key], 2) ? : 0.00;
        }
        if ($diff = array_diff_key($money_refund, $money_paid)) {
            foreach ($diff as $key => $item) {
                $result[$key] =  -$item;
            }
        }
        return $result;
    }

    /**
     * 应付单列表、导出数据
     * @param $where
     * @param $is_excel
     * @return array
     */
    public function getPayableList($where, $is_excel)
    {
        $count = $this->model->table('tb_pur_payment t')
            ->join('left join tb_pur_relevance_order a on t.relevance_id=a.relevance_id')
            ->join('left join tb_pur_order_detail b on b.order_id=a.order_id')
            ->join('left join tb_pur_sell_information c on c.sell_id=a.sell_id')
            ->join('left join tb_pur_payment_audit pa on t.payment_audit_id = pa.id')
//            ->join('left join tb_con_division_our_company on tb_con_division_our_company.our_company_cd=b.our_company')
            ->join('left join tb_pur_operation po on t.id = po.main_id')
            ->where($where)
            ->count();
        import('ORG.Util.Page');
        $page = new Page($count, 20); //每页显示3条数据
        $show = $page->show(); //分页显示
        $query = $this->model->table('tb_pur_payment t')
            ->field('pa.*,pa.id as audit_id,t.*,t.status as payable_status,a.prepared_by,b.purchase_type,b.procurement_number,
                b.supplier_id,b.supplier_id_en,b.our_company,b.amount_currency, b.payment_company,b.online_purchase_order_number,
                b.online_purchase_website,b.amount as total_amount,b.online_purchase_account,
                c.sell_team,c.seller,(t.amount_account + t.expense) as billing_total_amount,
                (t.amount_payable - t.amount_deduction) as lave_amount,po.action_type_cd,po.clause_type,po.bill_no')
            ->join('left join tb_pur_relevance_order a on t.relevance_id=a.relevance_id')
            ->join('left join tb_pur_order_detail b on b.order_id=a.order_id')
            ->join('left join tb_pur_sell_information c on c.sell_id=a.sell_id')
            ->join('left join tb_pur_payment_audit pa on t.payment_audit_id = pa.id')
//            ->join('left join tb_con_division_our_company on tb_con_division_our_company.our_company_cd=b.our_company')
            ->join('left join tb_pur_operation po on t.id = po.main_id')
            ->where($where)
            ->order('pa.updated_at desc');
        if (!$is_excel) {
            $query->limit($page->firstRow.','.$page->listRows);
        }
        $list = $query->select();
        return [$list, $count, $show];
    }
}
