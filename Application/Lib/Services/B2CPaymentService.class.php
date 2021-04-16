<?php
/**
 * 结算5.0 B2C退款 付款单相关逻辑
 * User: zouxuejun
 * Date: 19/12/24
 * Time: 19:05
 */
class B2CPaymentService extends Service
{
    public $user_name;
    public $model;
    public $payment_table;
    public $payment_audit_table;

    public $order_refund;
    public $order_refund_detail;

    //日志记录所需参数
    private $payment_ids;
    private $payment_audit_id;
    private $operation_info;
    private $status_name;
    private $remark;
    private $refund_id;

    public function __construct($model) {
        $this->user_name           = DataModel::userNamePinyin();
        $this->model               = empty($model) ? new Model() : $model;
        $this->payment_table       = M('payment', 'tb_wms_');
        $this->payment_audit_table = M('payment_audit', 'tb_pur_');
        
        $this->order_refund       = M('order_refund', 'tb_op_');
        $this->order_refund_detail       = M('order_refund_detail', 'tb_op_');
    }

    /**
     * 出账/出账确认提交
     * @param $request_data
     * @throws Exception
     */
    public function paymentSubmit($request_data) {
        $this->payment_audit_id = $request_data['payment_audit_id'];
        $this->updatePaymentBill($request_data);
        $refund_info = $this->getAlloRelation($this->payment_audit_id);//获取B2C退款单相关信息
        switch ($request_data['type']) {
            case 2:
                $this->operation_info = '确认付款';
                $order_status = '';
                $this->kyribaPayPush();//发送kyriba支付指令
                break;
            case 3:
                if (!$turnover_id = $this->thrTurnOver($request_data)) {
                    throw new \Exception(L('添加日记账失败'));
                }
                $rel_res   = (new TbFinClaimModel())->addRefundToTurnoverRelation($turnover_id,$refund_info);
                if (!$rel_res) {
                    throw new \Exception(L('添加日记账关联失败'));
                }
                $this->operation_info = '确认出账';
                $order_status = 'N000550900';//交易关闭
                break;
        }
        $status = $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->getField('status');
        (new OmsAfterSaleService())->synOrderStatus($this->payment_audit_id, TbPurPaymentAuditModel::$refund_status_map[$status], $order_status);

        if (!$request_data['is_kyriba']) {
            //kyriba处理不记出账日志
            $this->setParameters($refund_info, $status);
            $this->recordLog();
        }
    }

    private function getAlloRelation($payment_audit_id)
    {
        $field = "tb_op_order_refund.id as payment_id,
                tb_op_order_refund.id,
                tb_op_order_refund.after_sale_no,
                tb_op_order_refund.order_no,
                tb_pur_payment_audit.billing_amount as amount_account,
                tb_pur_payment_audit.billing_fee as expense,
                tb_pur_payment_audit.billing_at,
                tb_pur_payment_audit.billing_by,
                tb_pur_payment_audit.payment_audit_no,
                tb_op_order_refund_detail.sales_team_cd as refund_in_team
                ";
        $db_res = $this->order_refund->field($field)
            ->join("left join tb_pur_payment_audit on tb_pur_payment_audit.id = tb_op_order_refund.payment_audit_id")
            ->join("left join tb_op_order_refund_detail on tb_op_order_refund_detail.refund_id = tb_op_order_refund.id")
            ->where(['payment_audit_id'=>$payment_audit_id])->select();
        return $db_res;

    }

    private function getAlloRelationAll($ids)
    {
        $field = "tb_op_order_refund.id as payment_id,
                tb_op_order_refund.id,
                tb_op_order_refund.after_sale_no,
                tb_op_order_refund.order_no,
                tb_pur_payment_audit.billing_amount as amount_account,
                tb_pur_payment_audit.billing_fee as expense,
                tb_pur_payment_audit.billing_at,
                tb_pur_payment_audit.billing_by,
                tb_op_order_refund_detail.sales_team_cd as refund_in_team
                ";
        $db_res = $this->order_refund->field($field)
            ->join("left join tb_pur_payment_audit on tb_pur_payment_audit.id = tb_op_order_refund.payment_audit_id")
            ->join("left join tb_op_order_refund_detail on tb_op_order_refund_detail.refund_id = tb_op_order_refund.id")
            ->where(['payment_audit_id'=>array('in',$ids)])->select();
        return $db_res;

    }

    /**
     * 分摊金额计算及付款单更新
     * @param $request_data
     * @throws Exception
     */
    private function updatePaymentBill($request_data)
    {
        $payment_audit_id = $request_data['payment_audit_id'];
        switch ($request_data['type']) {
            case 2:
                //待出账
                $data = $request_data['no_billing'];
                $data['payment_voucher'] = DataModel::arrToJson($data['payment_voucher']);
                if(!$this->payment_audit_table->create($data)) {
                    throw new \Exception(L('创建付款确认信息失败'));
                }
                $status = TbPurPaymentAuditModel::$status_no_billing;
                $this->payment_audit_table->payment_at = dateTime();
                $this->payment_audit_table->payment_by = $this->user_name;
                $this->payment_audit_table->status     = $status;
                $this->payment_audit_table->payment_our_bank_account = $request_data['payment_our_bank_account'];
                $this->payment_audit_table->payment_account_id = $request_data['payment_account_id'];
                $this->payment_audit_table->trade_type_cd = $request_data['trade_type_cd'];
                $this->payment_audit_table->commission_type = $request_data['commission_type']; //commission_type 保存手续费承担方式
                $this->payment_audit_table->pay_com_cd = $request_data['pay_com_cd']; // 付款公司
                $this->payment_audit_table->pay_type  = 0;//是否通过kyriba付款-否
                $this->payment_audit_table->is_direct_billing  = 0;//是否直接出账0-否
                $this->payment_audit_table->fund_allocation_contract_no = $request_data['fund_allocation_contract_no']; //资金调配合同编号
                $res = $this->payment_audit_table->where(['id' => $payment_audit_id])->save();
                if (false === $res) {
                    throw new \Exception(L('确认失败'));
                }

                //同步付款金额和状态到售后单
//                $save = [
//                    'status'        => $status,
//                    'amount_paid'   => $data['payment_amount'],
//                    'update_time'   => dateTime(),
//                    'currency_paid' => $data['payment_currency_cd']
//                ];
                $save_data = [
                    'status_code' => TbPurPaymentAuditModel::$refund_status_map[$status],
                    'audit_status_cd' => TbPurPaymentAuditModel::$audit_status_map[$status],
                    'update_time'   => dateTime()
                ];
                $save_res = $this->order_refund->where(['payment_audit_id' => $payment_audit_id])->save($save_data);
                if ($save_res === false) {
                    throw new \Exception(L('付款金额、售后单和付款单状态同步失败'));
                }


                break;
            case 3:
                //出账
                $payment_info = $this->payment_audit_table->find($payment_audit_id);
                $data = $request_data['already_billing'];
                if (is_array($data['billing_voucher'])) {
                    $data['billing_voucher'] = DataModel::arrToJson($data['billing_voucher']);
                }
                if(!$this->payment_audit_table->create($data)) {
                    throw new \Exception(L('创建出账确认信息失败'));
                }
                $status = TbPurPaymentAuditModel::$status_finished;
                if (empty($request_data['payment_account_id'])) {
                    $request_data['payment_account_id'] = $payment_info['payment_account_id'];
                }
                $billing_currency_cd = TbFinAccountBankModel::getCurrencyCdByBankAccount($request_data['payment_account_id']);
                if (!$billing_currency_cd) {
                    throw new \Exception(L('出账币种不能为空'));
                }
                $this->payment_audit_table->billing_at = dateTime();
                $this->payment_audit_table->billing_by = $this->user_name;
                $this->payment_audit_table->status = $status;
                $this->payment_audit_table->billing_currency_cd = $billing_currency_cd;
                $this->payment_audit_table->payment_our_bank_account = $request_data['payment_our_bank_account'];
                $this->payment_audit_table->payment_account_id = $request_data['payment_account_id'];
                $this->payment_audit_table->trade_type_cd = $request_data['trade_type_cd'];
                $this->payment_audit_table->pay_com_cd = $request_data['pay_com_cd']; // 付款公司
                $this->payment_audit_table->pay_type  = 0;//是否通过kyriba付款-否
                $this->payment_audit_table->fund_allocation_contract_no = $request_data['fund_allocation_contract_no']; //资金调配合同编号

                if (null === $payment_info['is_direct_billing']) {
                    $this->payment_audit_table->is_direct_billing  = 1;//是否直接出账1-是
                }
                $res = $this->payment_audit_table->where(['id' => $payment_audit_id])->save();
                if (false === $res) {
                    throw new \Exception(L('确认失败'));
                }
                //同步出账金额和状态到售后单
//                $save = [
//                    'status'               => $status,
//                    'amount_account'       => $data['billing_amount'],
//                    'expense'              => $data['billing_fee'],
//                    'exchange_tax_account' => $billing_exchange_rate,
//                    'update_time'          => dateTime(),
//                    'currency_account'     => $billing_currency_cd
//                ];
                $save_data = [
                    'status_code' => TbPurPaymentAuditModel::$refund_status_map[$status],
                    'audit_status_cd' => TbPurPaymentAuditModel::$audit_status_map[$status],
                    'update_time'   => dateTime()
                ];
                //同步状态、出账汇率
                $save_res = $this->order_refund->where(['payment_audit_id' => $payment_audit_id])->save($save_data);
                if (false === $save_res) {
                    throw new \Exception(L('出账金额、售后单和付款单状态同步失败'));
                }

                $refund = $this->order_refund->where(['payment_audit_id' => $payment_audit_id])->field('order_id,platform_cd')->find();
                $afterSaleService = new OmsAfterSaleService();
                $refund_res = $afterSaleService->upOrderChargeOffStatus($refund);
                if (false === $refund_res) {
                    throw new \Exception(L('更新订单收入成本冲销状态状态失败'));
                }
                break;
        }
    }

    //日记账
    private function thrTurnOver($data)
    {
        $billing_info = $data['already_billing'];
        unset($data['already_billing']);
        $billing_info = array_merge((array)$billing_info, $data);
        $vouchers = [];
        foreach ($billing_info['billing_voucher'] as $v) {
            $voucher['name'] = $v['original_name'];
            $vouchers[]      = ['name'=>$v['original_name'], 'savename'=>$v['save_name']];
        }
        $field = "
            a.*,
            a.id as payment_id,
            f.open_bank,
            f.swift_code
           
        ";
        $info = $this->model->table('tb_pur_payment_audit a')
            ->field($field)
            ->join('LEFT JOIN tb_op_order_refund AS c ON  a.id = c.payment_audit_id ')
            ->join('LEFT JOIN tb_op_order_refund_detail AS b ON  c.id = b.refund_id'  )
            ->join('LEFT JOIN tb_op_order AS e ON e.ORDER_ID = c.order_id')
            ->join('left join tb_fin_account_bank f on f.id = a.payment_account_id')
            ->where(['a.id' => $billing_info['payment_audit_id']])->find();
        //var_dump($info);die;
        if (empty($info)) {
            return false;
        }

        if($billing_info['billing_amount'] > 0 || $billing_info['billing_fee'] > 0) {
            //采购应付金额和利息合并写进一条流水，同时写进两条日记账关联记录
            $param = [
                'accountBank'      => $info['payment_our_bank_account'],
                'openBank'         => $info['open_bank'],
                'swiftCode'        => $info['swift_code'],
                'transferTime'     => $billing_info['billing_date'] . ' 0000-00-00',//出账时间
                'companyCode'      => $info['pay_com_cd'],
                'companyName'      => cdVal($info['pay_com_cd']),
                'payOrRec'         => 0,
                'amountMoney'      => $billing_info['billing_amount'],
                'transferNo'       => $info['payment_audit_no'],
                'childTransferNo'  => null,
                'transferType'     => "N001950607",
                'currencyCode'     => $info['billing_currency_cd'],
                'oppCompanyName'   => $info['supplier_collection_account'] ? : $info['collection_account'],//供应商账户名/该支付渠道收款账号
                'oppOpenBank'      => $info['supplier_opening_bank'],//供应商银行开户行
                'oppAccountBank'   => $info['supplier_card_number'],//供应商银行卡号
                'oppSwiftCode'     => $info['supplier_swift_code'],//供应商SWIFT CODE
                'handlingFee'      => $billing_info['billing_fee'],//手续费
                'transferVoucher'  => $vouchers,
                'createTime'       => $info['billing_at'],
                'createUser'       => $_SESSION['user_id'],
                'paymentChannelCd' => $info['payment_channel_cd'],
                'remark'           => $info['confirmation_remark'],
            ];
            return (new TbFinAccountTurnoverModel())->thrTurnOver($param);
        }
        return true;
    }

    /**
     * B2C退款付款单撤回到待付款
     * @param $payment_audit_id 付款单id
     * @param string $reason
     * @throws Exception
     */
    public function returnToPaymentConfirm($payment_audit_id, $reason = '') {
        $this->payment_audit_id = $payment_audit_id;
        $payment_audit_info = $this->payment_audit_table->lock(true)->find($payment_audit_id);
        if(!in_array($payment_audit_info['status'],[TbPurPaymentAuditModel::$status_no_billing,TbPurPaymentAuditModel::$status_finished])) {
            throw new \Exception(L('状态异常'));
        }
        $status = TbPurPaymentAuditModel::$status_no_payment;
        $save = [
            'status'                   => $status,
            'payment_our_bank_account' => '',
            'payment_account_id'       => '',
            'payment_voucher'          => '',
            'payment_amount'           => '',
            'payment_currency_cd'      => '',
            'payment_at'               => null,
            'payment_by'               => null,
            'pay_com_cd'               => null,
            'pay_type'                 => 2,
            'is_direct_billing'        => null
        ];
        if($payment_audit_info['status'] == TbPurPaymentAuditModel::$status_finished) {
            $save['billing_amount']        = 0;
            $save['billing_date']          = '';
            $save['billing_voucher']       = '';
            $save['billing_fee']           = null;
            $save['billing_exchange_rate'] = '';
            $save['billing_at']            = null;
            $save['billing_by']            = null;
            $save['billing_currency_cd']   = null;
            if ($payment_audit_info['billing_amount'] > 0 || $payment_audit_info['billing_fee'] > 0)
            {
                (new TbWmsAccountTurnoverModel())->deleteRefundTurnover($payment_audit_info['payment_audit_no']);
            }
        }
        $res_payment = $this->payment_audit_table->where(['id'=>$payment_audit_id])->save($save);
        if(!$res_payment) {
            throw new \Exception(L('保存失败'));
        }

        $this->emptyPaymentData($payment_audit_id, $status);//应付单数据清空

        (new OmsAfterSaleService())->synOrderStatus($this->payment_audit_id, TbPurPaymentAuditModel::$refund_status_map[$status], 'restore');

        $payment_info  = $this->order_refund->field('id as payment_id')->where(['payment_audit_id'=>$payment_audit_id])->select();
        $this->setParameters($payment_info, $status);
        $this->operation_info = '撤回到待付款';
        $this->recordLog();
    }

    /**
     * 清空售后单数据
     * @param $payment_audit_id 付款单id
     * @param $status 状态
     * @throws Exception
     */
    public function emptyPaymentData($payment_audit_id, $status)
    {
        $save_data = [
            'status_code'         => TbPurPaymentAuditModel::$refund_status_map[$status],
            'audit_status_cd'     => TbPurPaymentAuditModel::$audit_status_map[$status],
            'update_time'         => dateTime(),
        ];
        $save_res = $this->order_refund
            ->where(['payment_audit_id' => $payment_audit_id])
            ->save($save_data);
        if ($save_res === false){
            throw new \Exception(L('售后单和付款单状态同步失败'));
        }
    }

    /**
     * 获取应付单日志记录
     * @param $request_data
     * @return array
     */
    public function getPayableBillLog($request_data)
    {
        $model = new TbPurPaymentLogModel();
        $search_map = [
            'payment_id' => 'payment_id'
        ];
        $search_type['all'] = true;
        list($where, $limit)   = WhereModel::joinSearchTemp($request_data,$search_map, '', $search_type);
        $pages['total']        = $model->where($where)->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        $db_res = $model->where($where)->limit($limit[0], $limit[1])->order('created_at desc')->select();
        return [
            'data' => $db_res,
            'pages' => $pages
        ];
    }

    /**
     * B2C退款付款单会计审核
     * @param $request_data
     * @throws Exception
     */
    public function accountingAudit($request_data) {
        $this->payment_audit_id   = $request_data['payment_audit_id'];
        $status                   = $request_data['status'] ? : TbPurPaymentAuditModel::$status_deleted;
        $is_return                = $request_data['is_return'];
//        $accounting_return_reason = $request_data['accounting_return_reason'];
//        $supply_note              = $request_data['supply_note'];//补充说明

        $payment_audit_info = $this->payment_audit_table->lock(true)->find($this->payment_audit_id);
        if ($payment_audit_info['status'] != TbPurPaymentAuditModel::$status_accounting_audit) {
            throw new \Exception(L('付款状态已更新'));
        }
        $res = $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save([
            'status'                => $status,
            'updated_by'            => $this->user_name,
        ]);
        if (!$res) throw new \Exception(L('会计审核失败'));
        $payment_info = $this->order_refund
            ->field('id as payment_id')
            ->where(['payment_audit_id'=>$this->payment_audit_id])
            ->select();
        if(empty($payment_info)) throw new \Exception(L('未找到售后单'));
        //日志记录参数
        $this->setParameters($payment_info, $status);
        $save_data = array(
            'status_code' => TbPurPaymentAuditModel::$refund_status_map[$status],
            'audit_status_cd' => TbPurPaymentAuditModel::$audit_status_map[$status]
        );
        $res = $this->order_refund
            ->where(['payment_audit_id'=>['in',$this->payment_audit_id]])
            ->save($save_data);
        if ($res === false){
            throw new \Exception(L('更新售后单状态失败'));
        }
        (new OmsAfterSaleService())->synOrderStatus($this->payment_audit_id, TbPurPaymentAuditModel::$refund_status_map[$status]);
        if ($is_return) {
            $this->operation_info = '会计审核退回';
        } else if ($status == TbPurPaymentAuditModel::$status_no_payment ) {
            $this->operation_info = '会计审核通过';
        } else {
            $this->operation_info = '会计审核撤回';
        }
        $this->recordLog();
    }

    /**
     * B2C退款付款单撤回到待审核
     * @param $request_data
     * @throws Exception
     */
    public function returnToAccountingAudit($request_data) {
        $this->payment_audit_id = $request_data['payment_audit_id'];
        $payment_info = $this->order_refund->field('id as payment_id')->where(['payment_audit_id'=>$this->payment_audit_id])->select();
        $this->setParameters($payment_info, TbPurPaymentAuditModel::$status_accounting_audit);
        $status_code = TbPurPaymentAuditModel::$refund_status_map[TbPurPaymentAuditModel::$status_accounting_audit];
        $save_data = [
            'status_code' => $status_code,
            'audit_status_cd' => TbPurPaymentAuditModel::$audit_status_map[TbPurPaymentAuditModel::$status_accounting_audit]
        ];
        $this->updatePayableBillStatus($save_data);//更新应付单状态
        $this->updatePaymentBillStatus(TbPurPaymentAuditModel::$status_accounting_audit);//更新付款单状态
        (new OmsAfterSaleService())->synOrderStatus($this->payment_audit_id, $status_code);
        $this->operation_info = '撤回到待审核';
        $this->recordLog();
    }

    /**
     * B2C退款付款单业务审核审核 (废弃)
     * @param $request_data
     * @throws Exception
     */
    public function businessAudit($request_data) {
        $this->payment_audit_id   = $request_data['payment_audit_id'];
        $status                   = $request_data['status'];
        $is_return                = $request_data['is_return'];
        $business_return_reason = $request_data['business_return_reason'];

        $payment_audit_info = $this->payment_audit_table->lock(true)->find($this->payment_audit_id);
        if ($payment_audit_info['status'] != TbPurPaymentAuditModel::$status_business_audit) {
            throw new \Exception(L('付款状态已更新'));
        }
        if (!in_array($status, [TbPurPaymentAuditModel::$status_no_confirmed,TbPurPaymentAuditModel::$status_accounting_audit])) {
            throw new \Exception(L('状态异常'));
        }
        $res = $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save([
            'status'     => $status == TbPurPaymentAuditModel::$status_no_confirmed ? TbPurPaymentAuditModel::$status_deleted : $status,
            'updated_by' => $this->user_name,
        ]);
        if (!$res) throw new \Exception(L('会计审核失败'));

        $payment_info = $this->payment_table
            ->field('id as payment_id, payment_no')
            ->where(['payment_audit_id'=>$this->payment_audit_id])
            ->select();
        if(empty($payment_info)) throw new \Exception(L('未找到售后单'));
        //日志记录参数
        $payment_no_str      = implode(',', array_column($payment_info, 'payment_no'));
        $this->setParameters($payment_info, empty($status) ? TbPurPaymentAuditModel::$status_deleted : $status);
        if ($status == TbPurPaymentAuditModel::$status_no_confirmed) {
            $this->cancelConfirm($business_return_reason);//应付单撤回到待确认
        } else {
            $this->updatePayableBillStatus($status);//更新应付单状态
        }
        if ($is_return) {
            $this->operation_info = '业务审核退回';
            $this->remark = $payment_no_str;
        } else if ($status == TbPurPaymentAuditModel::$status_accounting_audit ) {
            $this->operation_info = '业务审核通过';
        } else {
            $this->operation_info = '业务审核撤回';
            $this->remark = $payment_no_str;
        }
        $this->recordLog();
    }

    /**
     * 售后单撤回到待确认
     *  @param ￥business_return_reason 业务退回原因
     * @throws Exception
     * @throws Exception
     */
    public function cancelConfirm($business_return_reason = null) {
        $save['status']              = TbPurPaymentAuditModel::$status_no_confirmed;
        $save['amount_confirm']      = 0;
        $save['amount_difference']   = 0;
        $save['pay_remainder']       = 0;
        $save['next_pay_amount']     = 0;
        $save['next_pay_time']       = '';
        $save['difference_reason']   = '';
        $save['confirm_user']        = '';
        $save['confirm_time']        = '';
        $save['payment_audit_id']    = null;
        $save['supplier_id']         = '';
        $save['confirmation_remark'] = '';
        $save['payment_attachment']  = '';
        $save['contract_no']         = '';
        $save['accounting_return_reason'] = '';
        $save['business_return_reason'] = $business_return_reason;
        $res = $this->payment_table->where(['id'=>['in',$this->payment_ids]])->save($save);
        if (false === $res) {
            throw new \Exception(L('清空确认信息失败'));
        }
    }

    /**
     * 更新售后单状态
     * @param $status
     * @throws Exception
     */
    public function updatePayableBillStatus($save_data)
    {
        $res = $this->order_refund->where(['payment_audit_id'=>['in',$this->payment_audit_id]])->save($save_data);
        if ($res === false){
            throw new \Exception(L('更新售后单状态失败'));
        }
    }

    /**
     * 更新B2C退款付款单状态
     * @param $status
     * @throws Exception
     */
    public function updatePaymentBillStatus($status)
    {
        $res = $this->payment_audit_table->where(['id'=>['in',$this->payment_audit_id]])->save(['status'=>$status]);
        if (!$res) throw new \Exception(L('更新B2C退款付款单状态失败'));
    }

    /**
     * B2C退款付款单详情
     * @param $payment_audit_id 付款单id
     * @return array
     */
    public function getPaymentAuditDetail($payment_audit_id) {
        $field = "
            a.payment_audit_no,
            a.status as payable_status,
            a.our_company_cd,
            a.source_cd,
            a.payable_date_before,
            a.accounting_audit_user,
            d.payment_manager_by,
            a.created_by,
            a.created_at,
            a.payment_channel_cd,
            a.payment_way_cd,
            a.payable_amount_after,
            a.payable_amount_before,
            a.supplier_collection_account,
            a.supplier_opening_bank,
            a.supplier_card_number,
            a.supplier_swift_code,
            c.order_no,
            f.STORE_NAME as store_name,
            c.platform_cd,
            b.refund_account,
            a.payable_currency_cd,
            a.payable_date_after,
            a.billing_amount,
            a.billing_fee,
            a.billing_date,
            a.billing_voucher,
            a.billing_currency_cd,
            a.confirmation_remark,
            b.refund_user_name,
            a.payment_amount,
            a.payment_currency_cd,
            a.payment_voucher,
            g.open_bank,
            g.company_code,
            a.payment_our_bank_account,
            a.payment_account,
            c.id as payment_id,
            c.after_sale_no,
            b.refund_amount,
            c.attachment,
             b.refund_reason_cd,
             b.amount_currency_cd,
             c.order_id,
             c.trade_no,
             a.bank_settlement_code,
             a.bank_address,
             a.bank_address_detail,
             a.bank_postal_code,
             a.account_currency,
             a.account_type,
             a.commission_type,
             a.trade_type_cd,
             a.accounting_audit_user,
             a.pay_com_cd,
	         a.accounting_audit_user,
	         a.pay_type,
	         a.bank_reference_no,
	         a.bank_payment_reason
        ";
        $db_res = $this->model->table('tb_pur_payment_audit a')
            ->field($field)
            ->join('LEFT JOIN tb_op_order_refund AS c ON  a.id = c.payment_audit_id   ')
            ->join('LEFT JOIN tb_op_order_refund_detail AS b ON  c.id = b.refund_id'  )
            ->join('LEFT JOIN tb_con_division_our_company AS d ON d.our_company_cd = a.our_company_cd')
            ->join('LEFT JOIN tb_op_order AS e ON e.ORDER_ID = c.order_id AND e.PLAT_CD = c.platform_cd')
            ->join('LEFT JOIN tb_ms_store AS f ON f.ID = e.STORE_ID')
            ->join('LEFT JOIN  tb_fin_account_bank as g ON a.payment_account_id = g.id')
            ->where(['a.id' => $payment_audit_id])->find();
//        var_dump(M()->_sql());die;
        if (empty($db_res)) {
            return [];
        }
        $value = CodeModel::autoCodeOneVal($db_res, [
            'our_company_cd',
            'source_cd',
            'company_code',
            'payment_channel_cd',
            'payment_way_cd',
            'platform_cd',
            'payable_currency_cd',
            'payment_currency_cd',
            'billing_currency_cd',
            'amount_currency_cd',
            'refund_reason_cd',
            'trade_type_cd',
            'account_currency',
            'commission_type',
            'account_type',
            'pay_com_cd'
        ]);
        $value = (new PurService())->orderStatusToVal($value,true);
        $data['base_info'] = [
            'payment_audit_no'   => $value['payment_audit_no'],
            'payable_status_val' => $value['payable_status_val'],
            'payable_date_after' => $value['payable_date_after'],
            'our_company_cd_val' => $value['our_company_cd_val'],
            'payment_manager_by' => $value['payment_manager_by'],
            'created_by'         => $value['created_by'],
            'created_at'         => $value['created_at'],
            'our_company_cd'     => $value['our_company_cd'],
            'source_cd_val'      => $value['source_cd_val'],
            'order_id'           => $value['order_id'],
            'after_sale_no'      => $value['after_sale_no'],
            'order_no'           => $value['order_no'],
            'platform_cd'        => $value['platform_cd'],
            'supplier'           => '',
            'accounting_audit_user' => $value['accounting_audit_user'],
        ];;
        //应付明细信息
        $data['payable_info'][] = [
            'payment_id'          => $value['payment_id'],
            'payment_no'          => $value['after_sale_no'],
            'amount_payable'      => $value['refund_amount'] ?: '0.00',
            'amount_deduction'    => '0.00',
            'amount_confirm'      => $value['refund_amount'] ?: '0.00',
            'amount_difference'   => $value['amount_difference'] ?: '0.00',
            'difference_reason'   => $value['refund_reason_cd_val'],
            'amount_currency_val' => $value['amount_currency_cd_val'],
            'remark'              => $value['confirmation_remark'],
            'payment_attachment'  => $value['attachment'],
            'payable_date_before' => $value['payable_date_before'],
        ];
        //支付信息
        $data['payment_info'] = [
            'payment_channel_cd_val' => $value['payment_channel_cd_val'],
            'payment_way_cd_val'     => $value['payment_way_cd_val'],
            'payment_channel_cd'     => $value['payment_channel_cd'],
            'payment_way_cd'         => $value['payment_way_cd'],
            'payable_amount_before'  => $value['payable_amount_before'] ?: '0.00',
            'payable_amount_after'   => $value['payable_amount_after'] ?: '0.00',
            'amount_currency_val'    => $value['amount_currency_val'] ?: $value['payable_currency_cd_val'],
            'trade_type_cd'          => $value['trade_type_cd'],
            'trade_type_cd_val'      => $value['trade_type_cd_val'],
            'pay_type'               => TbPurPaymentAuditModel::$pay_type_map[$value['pay_type']],
        ];

        // 收款方账户/订单信息
        $data['receipt_info'] = [
            'supplier_collection_account' => $value['supplier_collection_account'],
            'supplier_opening_bank'       => $value['supplier_opening_bank'],
            'supplier_card_number'        => $value['supplier_card_number'],
            'supplier_swift_code'         => $value['supplier_swift_code'],
            'platform_cd_val'             => $value['platform_cd_val'],
            'store_name'                  => $value['store_name'],
            'platform_order_no'           => $value['order_no'],
            'collection_user_name'        => $value['refund_user_name'],
            'collection_account'          => $value['refund_account'],
            'trade_no'                    => $value['trade_no'],
            'bank_settlement_code'        => $value['bank_settlement_code'],
            'bank_address'                => $value['bank_address'],
            'bank_address_detail'         => $value['bank_address_detail'],
            'bank_postal_code'            => $value['bank_postal_code'],
            'account_currency_val'        => $value['account_currency_val'],
            'account_type_val'            => $value['account_type_val'],
            'commission_type'             => $value['commission_type'],
            'commission_type_val'         => $value['commission_type_val'],
            'bank_reference_no'           => $value['bank_reference_no'],
            'bank_payment_reason'         => $value['bank_payment_reason'],
        ];
        //我方账户信息
        $data['our_account_info'] = [
            'payment_account'          => $value['payment_account'],
            'company_code_val'         => $value['company_code_val'],
            'open_bank'                => $value['open_bank'],
            'payment_our_bank_account' => $value['payment_our_bank_account'],
            'refund_account'           => $value['refund_account'],
            'pay_com_cd_val'           => $value['pay_com_cd_val'],
            'pay_com_cd'               => $value['pay_com_cd'],
            'payment_account_id'       => $value['payment_account_id'],
            'fund_allocation_contract_no' => $value['fund_allocation_contract_no'],
        ];

        if (in_array($value['payable_status'], [
            TbPurPaymentAuditModel::$status_no_billing,
            TbPurPaymentAuditModel::$status_finished,
            TbPurPaymentAuditModel::$status_kyriba_wait_receive,
            TbPurPaymentAuditModel::$status_kyriba_receive_failed,
        ])) {
            //提交付款信息
            $data['submit_payment_info'] = [
                'payment_currency_cd'      => $value['payment_currency_cd'],
                'payment_currency_cd_val'  => $value['payment_currency_cd_val'],
                'payment_amount'           => $value['payment_amount'] ? : '0.00',
                'payment_voucher'          => $value['payment_voucher'],
            ];
            //出账确认信息
            $data['billing_info'] = [
                'payment_currency_cd'      => $value['billing_currency_cd'] ? : $value['currency_code'],
                'payment_currency_cd_val'  => $value['billing_currency_cd_val'] ? : $value['currency_code_val'],
                'billing_amount'           => $value['billing_amount'] ? : '0.00',
                'billing_fee'              => $value['billing_fee'] ? : '0.00',
                'billing_total_amount'     => bcadd($value['billing_amount'],$value['billing_fee'],2) ? : '0.00',
                'billing_voucher'          => $value['billing_voucher'],
                'billing_date'             => $value['billing_date'],
            ];
        } else {
            $data['submit_payment_info'] = [];
            $data['billing_info'] = [];
        }
        //操作判断
        $data['operation_info'] = [
            'payable_status'     => $value['payable_status'],
            'payment_channel_cd' => $value['payment_channel_cd'],
            'payment_way_cd'     => $value['payment_way_cd'],
            'source_cd'          => $value['source_cd'],
            'pay_type'           => $value['pay_type'],
            'is_show_submit_payment_info' => $value['is_direct_billing'] ? false : true,
            'is_optional_payment'=> true
        ];
        $data['remark_info'] = [
            'confirmation_remark' => $value['confirmation_remark']
        ];
        return DataModel::formatAmount($data);
    }

    /**
     *设置参数
     * @param $payment_info
     * @param $status
     */
    public function setParameters($payment_info, $status)
    {
        $this->payment_ids   = array_unique(array_column($payment_info, 'payment_id'));
        $this->status_name   = TbPurPaymentAuditModel::$status_map[$status];
    }

    //日志记录
    public function recordLog()
    {
        $date = dateTime();
        $status_name = $this->status_name;
        if ($status_name == TbPurPaymentAuditModel::$status_map[TbPurPaymentAuditModel::$status_deleted]) {
            $status_name = TbPurPaymentAuditModel::$status_map[TbPurPaymentAuditModel::$status_no_confirmed];
        }
        TbPurPaymentAuditLogModel::recordLog($this->payment_audit_id, $this->operation_info, $date, $this->status_name, $this->remark);
        TbOpOrderRefundLogModel::recordLog($this->payment_ids,$this->operation_info,$this->status_name,$this->remark);
    }

    public function createPaymentAuditBill($data)
    {
        $add_data = [
            'payment_audit_no'            => 'FK' . date(Ymd) . TbWmsNmIncrementModel::generateNo('payment_audit'),
            'our_company_cd'              => $data['company_cd'] ?: '',
            'status'                      => 4,
            'payable_amount_before'       => $data['refund_amount'],
            'payable_amount_after'        => $data['refund_amount'],
            'payable_date_after'          => $data['current_date'],
            'supplier_opening_bank'       => '',
            'supplier_collection_account' => '',
            'supplier_card_number'        => '',
            'supplier_swift_code'         => '',
            'created_by'                  => $data['created_by'],
            'created_at'                  => date('Y-m-d H:i:s'),
            'payment_channel_cd'          => $data['refund_channel_cd'],
            'source_cd'                   => 'N003010003',//B2C退款
            'payable_currency_cd'         => $data['amount_currency_cd'],
            'accounting_audit_user'       => TbPurPaymentAuditModel::$accounting_audit_user,
            'sales_team_cd'               => $data['sales_team_cd'],
            'platform_cd'                 => $data['platform_cd'],
            'store_name'                  => $data['store_name'],
            'platform_order_no'           => $data['order_no'],
            'trade_no'                    => $data['trade_no'],
        ];
        if ($data['type'] == 0 && $data['refund_channel_cd'] == TbPurPaymentAuditModel::$channel_bank) {
//            $add_data['payment_way_cd'] = TbPurPaymentAuditModel::$way_transfer;//银行转账
            $add_data['payment_way_cd'] = TbPurPaymentAuditModel::$way_trade_no;//按交易号退款

            $add_data['supplier_collection_account'] = $data['refund_user_name'];
            $add_data['supplier_card_number']        = $data['refund_account'];
        } else if ($data['type'] == 1) {
//            $add_data['payment_way_cd']              = TbPurPaymentAuditModel::$way_transfer;//银行转账
            $add_data['payment_way_cd']              = TbPurPaymentAuditModel::$way_trade_no;//按交易号退款
            $add_data['supplier_collection_account'] = $data['refund_user_name'];
            $add_data['supplier_card_number']        = $data['refund_account'];
        } else {
//            $add_data['payment_way_cd']       = TbPurPaymentAuditModel::$way_order_pay;//按订单支付
            $add_data['payment_way_cd']       = TbPurPaymentAuditModel::$way_trade_no;//按交易号退款
            $add_data['collection_user_name'] = $data['refund_user_name'];
            $add_data['collection_account']   = $data['refund_account'];
        }
        return $this->payment_audit_table->add($add_data);
    }

    /**
     * 批量核销出账
     * @param $request_data
     * @throws Exception
     */
    public function batchPaymentSubmit($request_data)
    {
        $this->payment_audit_id = array_column($request_data, 'id');
        $refund_info = $this->getAlloRelationAll($this->payment_audit_id);//获取B2C退款单相关信息
        $this->batchUpdatePaymentBill($request_data,$refund_info);
        //付款核销选择已出账或待出账出账更新各个采购单状态
        $order_ids = [];
        $status_code = TbPurPaymentAuditModel::$refund_status_map[TbPurPaymentAuditModel::$status_finished];
        foreach ($refund_info as $v) {
            if (isset($order_ids[$v['order_no']])) {
                //同一采购单不用重复更新状态
                continue;
            }
            $order_ids[$v['order_no']] = true;
            $save_data = [
                'status_code' => $status_code ,
                'audit_status_cd' => TbPurPaymentAuditModel::$audit_status_map[TbPurPaymentAuditModel::$status_finished]
            ];
            $this->updatePayableBillStatus($save_data);
        }
        $omsService = new OmsAfterSaleService();
        foreach($this->payment_audit_id as $id) {
            $omsService->synOrderStatus($id, $status_code, 'N000550900');//同步订单退款状态
        }

        foreach ($request_data as &$item) {
            $item['payment_audit_id'] = $item['id'];
            $tmpId = M('op_order_refund', 'tb_')->where(['payment_audit_id' => $item['payment_audit_id']])->getField('id');
            #发送企业微信消息
            $afterSaleService = new OmsAfterSaleService();
            $afterSaleService->after_sale_refund_pass_wx_msg($tmpId, 4);
            unset($item['id']);
            if (!$turnover_id = $this->thrTurnOver($item)) {
                throw new \Exception(L('添加日记账失败'));
            }
            $rel_res = (new TbFinClaimModel())->addPurToTurnoverRelation($turnover_id, $refund_info);
            if (!$rel_res) {
                throw new \Exception(L('添加日记账关联失败'));
            }
        }

        $this->operation_info = '确认出账';
        $this->setParameters($refund_info, TbPurPaymentAuditModel::$status_finished);
        $this->recordLog();
        $info = [];

        foreach ($refund_info as $item) {
            $info[$item['order_no']][] = $item;
        }
    }

    /**
     * 付款单更新
     * @param $data
     * @throws Exception
     */
    private function batchUpdatePaymentBill($request_data,$refund_info)
    {
        $excel_model         = new TempImportExcelModel();
        $payment_audit_model = new TbPurPaymentAuditModel();
        $info                = $refund_info;
        if (empty($info)) {
            throw new \Exception(L('查找采购关联数据失败'));
        }
        $pur_info            = array_combine(array_column($info, 'payment_audit_id'), $info);;
        //出账
        $status = TbPurPaymentAuditModel::$status_finished;
        foreach ($request_data as &$item) {
            $billing_exchange_rate         = exchangeRateConversion(cdVal($item['billing_currency_cd']), cdVal($pur_info[$item['id']]['amount_currency']), date('Ymd'));
            $item['billing_at']            = dateTime();
            $item['billing_by']            = $this->user_name;
            $item['status']                = $status;
            $item['billing_exchange_rate'] = $billing_exchange_rate;
            $item['pay_type']              = 0;
        }
        $res = $payment_audit_model->execute($excel_model->saveAll($request_data, $payment_audit_model, $pk = 'id'));
        if (false === $res) {
            throw new \Exception(L('确认失败'));
        }
    }

    private function kyribaPayPush()
    {
        $filed = [
            'pa.*',
            'ab.company_code',
            'ab.open_bank',
            'ab.account_bank',
            'ab.swift_code',
            'ab.currency_code',
            'ab.bank_short_name'
        ];
        $row = $this->model->table('tb_pur_payment_audit pa')
            ->field($filed)
            ->join('left join tb_fin_account_bank ab on ab.id = pa.payment_account_id')
            ->where(['pa.id' => $this->payment_audit_id])
            ->find();
        if (strtoupper($row['bank_short_name']) != 'CITI') {
            if (false === $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save(['pay_type'=>0])) {
                throw new Exception(L('更新支付类型失败'));
            }
            return true;
        }
        if (false === $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save(['pay_type'=>1])) {
            throw new Exception(L('更新支付类型失败'));
        }
        //飞松：就让它取不到，走不通
        throw new \Exception(L('B2C退款暂时找不到供应商信息'));
//        (new KyribaService())->putXmlToFtp($row);
    }
}