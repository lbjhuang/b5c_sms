<?php
/**
 * User: yuanshixiao
 * Date: 2017/8/15
 * Time: 14:32
 */

class TbPurPaymentModel extends BaseModel
{
    protected $trueTableName    = 'tb_pur_payment';
    public static $status_name = [
        0 => '待确认',
        1 => '待付款',
        2 => '待出账',
        3 => '已完成',
    ];

    static $status = [
        'to_confirm'    => 0,
        'to_pay'        => 1,
        'to_account'    => 2,
        'complete'      => 3,
    ];

    public $_validate = [
        ['relevance_id','require','订单关联id不能为空'],
        ['amount','require','商品金额不能为空'],
        ['amount_payable','require','应付金额不能为空'],
        ['payable_date','require','付款日期不能为空'],
        ['payment_period','require','付款账期不能为空'],
    ];

    public $_validate_all_confirm = [
        ['our_company','require','付款账户名不能为空',1],
        ['our_company_bank_account','require','付款账号不能为空',1],
        ['currency_account','require','付款账户名不能为空',1],
        ['amount_account','require','扣款金额不能为空',1],
        ['currency_account','require','出账币种不能为空',1],
        ['exchange_tax_account','require','汇率异常',1],
        ['account_date','require','出账日期不能为空',1],
        ['expense','require','手续费不能为空',1],
    ];

    public $_validate_payment_confirm = [
        ['our_company','require','付款账户名不能为空',1],
        ['our_company_bank_account','require','付款账号不能为空',1],
        ['amount_paid','require','付款金额不能为空',1],
        ['currency_paid','require','付款币种不能为空',1],
        ['currency_account','require','出账币种不能为空',1],
        ['exchange_tax_account','require','汇率异常',1],
        ['voucher','require','付款凭证不能为空',1],

    ];

    public $_validate_accounting_confirm = [
        ['amount_account','require','扣款金额不能为空',1],
        ['expense','require','手续费不能为空',1],
        ['account_date','require','出账日期不能为空',1],
    ];



    public function createPayableByShip($ship_id) {
        if(!$ship_id) {
            $this->error = '参数错误';
            return false;
        }
        $ship_info = M('ship','tb_pur_')->where(['id'=>$ship_id])->find();
        $order = M('order_detail','tb_pur_')
            ->alias('t')
            ->join('left join tb_pur_relevance_order as  a on a.order_id=t.order_id')
            ->where(['relevance_id'=>$ship_info['relevance_id']])
            ->find();

        if($order['payment_type'] != 1) {
            return true;
        }
        $payment_info = json_decode($order['payment_info'],true);
        $amount = M('ship_goods','tb_pur_')
            ->alias('t')
            ->join('left join tb_pur_goods_information a on a.information_id=t.information_id')
            ->where(['ship_id'=>$ship_id])
            ->sum('t.ship_number*(a.unit_price+a.unit_expense)');

        foreach($payment_info as $k => $v) {
            if($v['payment_node'] == 'N001390200') {
                $payment = $v;
                $period = $k;
                $payable['relevance_id']   = $ship_info['relevance_id'];
                $payable['payable_date']   = date('Y-m-d',strtotime($ship_info['shipment_date'])+$payment['payment_days']*24*3600);
                $payable['amount']         = $amount;
                $payable['amount_payable'] = $amount*$payment['payment_percent']/100;
                $payable['payment_period'] = "第{$period}期-"
                    .cdVal($payment['payment_node'])
                    .$payment['payment_days']
                    .TbPurOrderDetailModel::$payment_day_type[$payment['payment_day_type']]
                    .$payment['payment_percent'].'%';
                $payable['payment_no']      = $this->createPaymentNO();
                $payable['update_time']     = date('Y-m-d H:i:s');
                $res = M('payment','tb_pur_')->add($payable);
                if(!$res) {
                    ELog::add('发货生成应付数据失败：'.json_encode($payable).M()->getDbError(),ELog::ERR);
                    $this->error = '保存失败';
                    return false;
                }
            }
        }
        if($this->getError()) return false;
        return true;
    }

    public function createPayableByWarehouse($warehouse_id) {
        if(!$warehouse_id) {
            $this->error = '参数错误';
            return false;
        }
        $ship_info  = M('warehouse','tb_pur_')
            ->field('a.*')
            ->alias('t')
            ->join('left join tb_pur_ship a on a.id=t.ship_id')
            ->where(['t.id'=>$warehouse_id])->find();
        $order      = M('order_detail','tb_pur_')
            ->alias('t')
            ->join('left join tb_pur_relevance_order as  a on a.order_id=t.order_id')
            ->where(['relevance_id'=>$ship_info['relevance_id']])
            ->find();

        if($order['payment_type'] != 1) {
            return true;
        }
        $payment_info = json_decode($order['payment_info'],true);
        foreach($payment_info as $k => $v) {
            if($v['payment_node'] == 'N001390400') {
                $payment = $v;
                $period = $k;
                break;
            }
        }
        if(!$payment) return true;
        $amount = M('warehouse_goods','tb_pur_')
            ->alias('t')
            ->join('left join tb_pur_ship_goods a on a.id=t.ship_goods_id')
            ->join('left join tb_pur_goods_information b on b.information_id=a.information_id')
            ->where(['warehouse_id'=>$warehouse_id])
            ->sum('(t.warehouse_number+t.warehouse_number_broken)*(b.unit_price+b.unit_expense)');

        $payable['relevance_id']    = $ship_info['relevance_id'];
        $payable['payable_date']    = date('Y-m-d',time()+$payment['payment_days']*24*3600);
        $payable['amount']          = $amount;
        $payable['amount_payable']  = $amount*$payment['payment_percent']/100;
        $payable['payment_period']  = "第{$period}期-"
            .cdVal($payment['payment_node'])
            .$payment['payment_days']
            .TbPurOrderDetailModel::$payment_day_type[$payment['payment_day_type']]
            .$payment['payment_percent'].'%';
        $payable['payment_no']      = $this->createPaymentNO();
        $payable['update_time']     = date('Y-m-d H:i:s');
        $res                        = M('payment','tb_pur_')->add($payable);
        if(!$res) {
            ELog::add('入库生成应付数据失败：'.json_encode($payable).M()->getDbError(),ELog::ERR);
            return false;
        }
        return true;
    }

    public function createPaymentNO() {
        $pre_payment_no = $this->lock(true)->where(['payment_no'=>['like','YF'.date('Ymd').'%']])->order('id desc')->getField('payment_no');
        if($pre_payment_no) {
            $num = substr($pre_payment_no,-3)+1;
        }else {
            $num = 1;
        }
        $payment_no = 'YF'.date('Ymd').substr(1000+$num,1);
        return $payment_no;
    }

    /**
     * 是否有已付款应付
     * @param $relevance_id
     */
    public function hasPaid($relevance_id) {
        $paid_payment = $this->where(['relevance_id'=>$relevance_id,'status'=>2])->find();
        return $paid_payment ? true : false;
    }

    /**
     * 删除订单所有应付
     * @param $relevance_id
     * @return mixed
     */
    public function deleteOrderPayment($relevance_id) {
        return $this->where(['relevance_id'=>$relevance_id])->delete();
    }

    //废弃
    public function paymentWriteOff($data) {
        $data['update_time']    = date('Y-m-d H:i:s');
        $data['update_user']    = $_SESSION['m_loginname'];
        $this->startTrans();
        $payment_status = $this->lock(true)->where(['id'=>$data['id']])->getField('status');
        $res = $payment_status == 1 ? $this->paymentConfirm($data) : $this->accountingConfirm($data);
        $res ? $this->commit() : $this->rollback();
        return $res;
    }

    //废弃
    public function paymentConfirm($data) {
        $this->startTrans();
        $payment = $this->lock(true)->where(['id'=>$data['id']])->find();
        $order = M('relevance_order','tb_pur_')
            ->alias('t')
            ->field('a.*,t.ship_status,t.warehouse_status,t.sou_time,b.sell_team')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('left join tb_pur_sell_information b on b.sell_id=t.sell_id')
            ->where(['t.relevance_id'=>$payment['relevance_id']])
            ->find();

        $data['paid_date']              = date('Y-m-d');
        $data['exchange_tax_account']   = exchangeRateConversion(cdVal($data['currency_account']), cdVal($order['amount_currency']), str_replace('-','',$order['sou_time']));
        if($data['has_account']) {
            $data['account_submit_time']    = date('Y-m-d H:i:s');
            $data['account_submit_user']    = $_SESSION['m_loginname'];
            $data['status']                 = 3;
            $validate                       = $this->_validate_all_confirm;
        }else {
            unset($data['account_date']);
            $data['payment_submit_time']    = date('Y-m-d H:i:s');
            $data['payment_submit_user']    = $_SESSION['m_loginname'];
            $data['status']                 = 2;
            $validate                       = $this->_validate_payment_confirm;
        }

        if(!$this->validate($validate)->create($data) || !$this->save()) {
            $this->rollback();
            $this->error = $this->getError() ? : '付款信息保存失败';
            return false;
        }


        if($data['has_account']) {
            //付款状态判断
            $save_order['payment_status'] = $this->paymentStatusCheck($order);
            $res_order = D('TbPurRelevanceOrder')->where(['order_id'=>$order['order_id']])->save($save_order);
            if($res_order === false) {
                $this->rollback();
                $this->error = '订单状态保存失败';
                return false;
            }
            if($data['amount_account'] > 0 || $data['expense'] > 0) {
                //采购应付金额和利息合并写进一条流水，同时写进两条日记账关联记录
                $param = [
                    'data' => [
                        'accountBank' => $data['our_company_bank_account'],
                        'openBank' => $data['our_company_bank'],
                        'swiftCode' => $data['our_company_swift_code'],
                        'transferTime' => $data['account_date'] . ' 0000-00-00',
                        'companyCode' => $data['our_company'],
                        'companyName' => cdVal($data['our_company']),
                        'payOrRec' => 0,
                        'amountMoney' => $data['amount_account'],
                        'transferNo' => $order['procurement_number'],
                        'childTransferNo' => $payment['payment_no'],
                        'transferType' => 'N001950100',
                        'currencyCode' => $data['currency_account'],

                        'oppCompanyName' => $order['supplier_collection_account'],//供应商账户名
                        'oppOpenBank' => $order['supplier_opening_bank'],//供应商银行开户行
                        'oppAccountBank' => $order['supplier_card_number'],//供应商银行卡号
                        'oppSwiftCode' => $order['supplier_swift_code'],//供应商SWIFT CODE
                        'handlingFee' => $data['expense'],//手续费
                        'orderId' => $order['order_id'],
                        'saleTeams' => $order['sell_team'],
                        'transferVoucher' => $data['bank_receipt'],
                        'remark' => $payment['confirm_remark'],
                        'createdBy'  => $_SESSION['m_loginname'],
                        'CreateAt'   => date('Y-m-d H:i:s'),
                        'createUser' => $_SESSION['userId'],
                        'createTime' => date('Y-m-d H:i:s'),
                    ]
                ];
                if(!$this->thrTurnOver($param)) {
                    $this->rollback();
                    return false;
                }
            }
        }

        $this->sendPaidEmail($data['id'], A('OrderDetail')->paid_email_content());
        if ($data['status'] === 3) { // 预付款付款，生成抵扣金记录
            // 判断该应付是否为预付款？即创建订单（触发操作），因为只有创建订单的触发操作才会生成预付款一说
            $op_re = D('Scm/PurOperation')->where(['main_id' => $data['id'], 'money_type' => '1', 'action_type_cd' => 'N002870001'])->getField('id');
            if ($op_re) {
                $addDataInfo = [];
                $addDataInfo['clause_type'] = '6';
                $addDataInfo['class'] = __CLASS__;
                $addDataInfo['function'] = __FUNCTION__;
                $amount_payable = $payment['use_deduction'] ? $payment['amount_confirm'] + $payment['amount_deduction'] : $payment['amount_confirm']; //确认后应付金额 + （是否使用抵扣金）
                $addDataInfo['amount_deduction'] = $amount_payable;
                $pu_res = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', 'N002870015', $payment['relevance_id'], $payment['payment_no']);
                if (!$pu_res) {
                    $this->rollback();
                    return false;
                }
            }
        }
        (new TbPurActionLogModel())->addLog($payment['relevance_id']);
        $this->commit();
        return true;
    }

    //废弃
    public function accountingConfirm($data) {
        $this->startTrans();
        $payment = $this
            ->alias('t')
            ->field('t.*,a.swift_code,open_bank,swift_code')
            ->join('left join tb_fin_account_bank a on a.account_bank=t.our_company_bank_account')
            ->where(['t.id'=>$data['id']])
            ->find();
        $order = M('relevance_order','tb_pur_')
            ->alias('t')
            ->field('a.*,t.ship_status,t.warehouse_status,t.sou_time,b.sell_team')
            ->join('left join tb_pur_order_detail a on a.order_id=t.order_id')
            ->join('left join tb_pur_sell_information b on b.sell_id=t.sell_id')
            ->where(['t.relevance_id'=>$payment['relevance_id']])
            ->find();
        if($payment['status'] != 2) {
            $this->error = '状态异常';
            return false;
        }
        $data['status']                 = 3;
        $data['exchange_tax_account']   = exchangeRateConversion(cdVal($payment['currency_account']), cdVal($order['amount_currency']), str_replace('-','',$order['sou_time']));

        $data['account_submit_time']    = date('Y-m-d H:i:s');
        $data['account_submit_user']    = $_SESSION['m_loginname'];
        $validate                       = $this->_validate_accounting_confirm;
        if(!$this->validate($validate)->create($data) || !$this->save()) {
            $this->rollback();
            $this->error = $this->getError() ? : '付款信息保存失败';
            return false;
        }
        //付款状态判断
        $save_order['payment_status'] = $this->paymentStatusCheck($order);

        $res_order = D('TbPurRelevanceOrder')->where(['order_id'=>$order['order_id']])->save($save_order);
        if($res_order === false) {
            $this->rollback();
            $this->error = '订单状态保存失败';
            return false;
        }

        if($data['amount_account'] > 0 || $data['expense'] > 0) {
            //采购应付金额和利息合并写进一条流水，同时写进两条日记账关联记录
            $param = [
                'data' => [
                    'accountBank'       => $payment['our_company_bank_account'],
                    'openBank'          => $payment['open_bank'],
                    'swiftCode'         => $payment['swift_code'],
                    'transferTime'      => $data['account_date'].' 0000-00-00',
                    'companyCode'       => $payment['our_company'],
                    'companyName'       => cdVal($payment['our_company']),
                    'payOrRec'          => 0,
                    'amountMoney'       => $data['amount_account'],
                    'transferNo'        => $order['procurement_number'],
                    'childTransferNo'   => $payment['payment_no'],
                    'transferType'      => 'N001950100',
                    'currencyCode'      => $payment['currency_account'],

                    'oppCompanyName'    => $order['supplier_collection_account'],//供应商账户名
                    'oppOpenBank'       => $order['supplier_opening_bank'],//供应商银行开户行
                    'oppAccountBank'    => $order['supplier_card_number'],//供应商银行卡号
                    'oppSwiftCode'      => $order['supplier_swift_code'],//供应商SWIFT CODE
                    'handlingFee'       => $data['expense'],//手续费
                    'orderId'           => $order['order_id'],
                    'saleTeams'         => $order['sell_team'],
                    'transferVoucher'   => $data['bank_receipt'],
                    'remark'            => $payment['confirm_remark'],
                    'createdBy'         => $_SESSION['m_loginname'],
                    'CreateAt'          => date('Y-m-d H:i:s'),
                    'createUser'        => $_SESSION['userId'],
                    'createTime'        => date('Y-m-d H:i:s'),
                ]
            ];
            if(!$this->thrTurnOver($param)) return false;
        }
        (new TbPurActionLogModel())->addLog($payment['relevance_id']);
        // 生成抵扣金记录
        // 判断该应付是否为预付款？即创建订单（触发操作），因为只有创建订单的触发操作才会生成预付款一说
        $op_re = M('operation','tb_pur_')->where(['main_id' => $data['id'], 'money_type' => '1', 'action_type_cd' => 'N002870001'])->find();
        if ($op_re) {
            $addDataInfo = [];
            $addDataInfo['clause_type'] = '6';
            $addDataInfo['class'] = __CLASS__;
            $addDataInfo['function'] = __FUNCTION__;
            $amount_payable = $payment['use_deduction'] ? $payment['amount_confirm'] + $payment['amount_deduction'] : $payment['amount_confirm']; //确认后应付金额 + （是否使用抵扣金）
            $addDataInfo['amount_deduction'] = $amount_payable;
            $pu_res = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', 'N002870015', $payment['relevance_id'], $payment['payment_no']);
            if (!$pu_res) {
                $this->rollback();
                return false;
            }
        }
        $this->commit();
        return true;
    }

    public function thrTurnOver($param) {
        $url = U('finance/thrTurnOver','','',false,true);
        $res_turn_over_j = curl_request($url,$param);
        $res_turn_over = json_decode($res_turn_over_j,true);
        if($res_turn_over['code'] != 2000) {
            $this->rollback();
            $this->error = $res_turn_over['msg'];
            ELog::add(['info'=>'日记账保存失败','request'=>$param,'response'=>$res_turn_over_j],Elog::ERR);
            return false;
        }
        return true;
    }

    //判断这次付款确认后采购单付款状态
    public function paymentStatusCheck($order) {
        /*if(!isset($order['payment_type'])) {
            $this->error = '判断采购单付款状态参数异常';
            return false;
        }
        if($order['payment_type'] == 0) {
            $all_payment_created = true;
        }else {
            $payment_info = json_decode($order['payment_info'],true);
            $sign = [
                'has_ship'      => false,
                'has_warehouse' => false,
            ];
            foreach ($payment_info as $v) {
                switch ($v['payment_node']) {
                    case 'N001390200' :
                        $sign['has_ship'] = true;
                        break;
                    case 'N001390400':
                        $sign['has_warehouse'] = true;
                        break;
                    default :
                        break;
                }
            }

            if($sign['has_warehouse']) {
                $all_payment_created = $order['warehouse_status'] == 2 ? true : false;
            }elseif($sign['has_ship']) {
                $all_payment_created = $order['ship_status'] == 2 ? true : false;
            }else {
                $all_payment_created = true;
            }
        }*/
        //取消对发货、入库状态判断逻辑
        $count_paid = M('payment','tb_pur_')
            ->alias('t')
            ->join('inner join tb_pur_relevance_order a on a.relevance_id=t.relevance_id')
            ->where(['a.order_id'=>$order['order_id'],'status'=>['in',[0,1,2]]])
            ->count();
        if(!$count_paid) return 2;

        $count_to_pay = M('payment','tb_pur_')
            ->alias('t')
            ->join('inner join tb_pur_relevance_order a on a.relevance_id=t.relevance_id')
            ->where(['a.order_id'=>$order['order_id'],'t.status'=> ['in',[2,3]]])
            ->count();
        if($count_to_pay > 0) return 1;
        return 0;
    }

}