<?php
/**
 * 结算5.0调拨费用付款单相关逻辑
 * User: fuming
 * Date: 19/11/25
 * Time: 19:05
 */
class ExpenseBillPaymentService extends Service
{
    public $user_name;
    public $model;
    public $payment_table;
    public $payment_audit_table;

    //日志记录所需参数
    private $relevance_ids;
    private $payment_ids;
    private $payment_audit_id;
    private $operation_info;
    private $status_name;
    private $remark;

    public $repository;

    public function __construct($model) {
        $this->user_name           = DataModel::userNamePinyin();
        $this->model               = empty($model) ? new Model() : $model;
        $this->payment_table       = M('payment', 'tb_wms_');
        $this->payment_audit_table = M('payment_audit', 'tb_pur_');

        $this->repository = new PurRepository($this->model);
    }

    /**
     * 出账/出账确认提交
     * @param $request_data
     * @throws Exception
     */
    public function paymentSubmit($request_data) {
        $this->payment_audit_id = $request_data['payment_audit_id'];
        $this->updatePaymentBill($request_data);
        $allo_info = $this->getAlloRelation($this->payment_audit_id);//获取调拨单相关信息
        switch ($request_data['type']) {
            case 2:
                $this->operation_info = '确认付款';
                $this->kyribaPayPush();//发送kyriba支付指令
                break;
            case 3:
                if (!$turnover_id = $this->thrTurnOver($request_data)) {
                    throw new \Exception(L('添加日记账失败'));
                }
                $rel_res   = (new TbFinClaimModel())->addAlloToTurnoverRelation($turnover_id, $allo_info);
                if (!$rel_res) {
                    throw new \Exception(L('添加日记账关联失败'));
                }
                $this->operation_info = '确认出账';
                break;
        }
        $status = $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->getField('status');
        if (!$request_data['is_kyriba']) {
            //kyriba处理不记出账日志
            $this->setParameters($allo_info, $status);
            $this->recordLog();
        }
    }

    private function getAlloRelation($payment_audit_id) {
        $field = 't.*,wp.id as payment_id,wp.payment_no,wp.expense,wp.amount_account,pa.billing_at,pa.billing_by,wp.cost_sub_cd';
        return M('allo', 'tb_wms_')
            ->alias('t')
            ->field($field)
            ->join('left join tb_wms_payment wp on wp.allo_id = t.id')
            ->join('left join tb_pur_payment_audit pa on wp.payment_audit_id = pa.id')
            ->where(['wp.payment_audit_id' => ['in',$payment_audit_id]])
            ->select();
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

                //同步付款金额和状态到调拨应付单
                $save = [
                    'status'        => $status,
                    'amount_paid'   => $data['payment_amount'],
                    'update_time'   => dateTime(),
                    'currency_paid' => $data['payment_currency_cd']
                ];
                $save_res = $this->payment_table->where(['payment_audit_id' => $payment_audit_id])->save($save);
                if (!$save_res) {
                    throw new \Exception(L('付款金额、调拨应付单和付款单状态同步失败'));
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
                //同步出账金额和状态到调拨应付单
                $save = [
                    'status'               => $status,
                    'amount_account'       => $data['billing_amount'],
                    'expense'              => $data['billing_fee'],
                    'exchange_tax_account' => 1,
                    'update_time'          => dateTime(),
                    'currency_account'     => $billing_currency_cd
                ];
                //同步状态、出账汇率
                $save_res = $this->payment_table->where(['payment_audit_id' => $payment_audit_id])->save($save);
                if (false === $save_res) {
                    throw new \Exception(L('出账金额、调拨应付单和付款单状态同步失败'));
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
        $info = $this->model->table('tb_pur_payment_audit pa')
            ->field('pa.*,ab.swift_code,ab.open_bank,ab.swift_code,pa.billing_currency_cd,wp.cost_sub_cd')
            ->join('left join tb_wms_payment wp on pa.id = wp.payment_audit_id')
            ->join('left join tb_fin_account_bank ab on ab.id = pa.payment_account_id')
            ->where(['pa.id'=>$billing_info['payment_audit_id']])
            ->find();
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
                'transferType'     => $info['cost_sub_cd'],
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

    //更新采购单付款状态
    private function updatePurRelevanceOrderPaymentStatus($order_info)
    {
        //付款状态判断
        $save_order['payment_status'] = D('TbPurPayment')->paymentStatusCheck($order_info);
        $res_order = D('TbPurRelevanceOrder')->where(['order_id'=>$order_info['order_id']])->save($save_order);
        return $res_order;
    }



    //获取符合条件的可合并付款的应付单
    public function searchMergedPaymentBill($request_data)
    {
        $search_data = $request_data['search'];
        $search_map = TbPurPaymentAuditModel::getMergedPaymentBillSearchMap($search_data['payment_channel_cd'],$search_data['payment_way_cd']);
        $search_map['our_company_cd']  = 'pa.our_company_cd';
        $search_map['amount_currency'] = 'od.amount_currency';
        $search_map['supplier_name']   = 'od.supplier_id';
        $search_map['payment_channel_cd'] = 'pa.payment_channel_cd';
        $search_map['payment_way_cd']     = 'pa.payment_way_cd';

        $search_type['all'] = true;
        list($where, $limit)  = WhereModel::joinSearchTemp($request_data, $search_map, [], $search_type);
        $where['pa.status'] = TbPurPaymentAuditModel::$status_accounting_audit;//待审核
        $where['t.id'] = ['neq', $search_data['payment_id']];
        $list = $this->repository->getMergedPaymentBill($where);
        $list = CodeModel::autoCodeTwoVal($list, ['amount_currency']);
        return DataModel::formatAmount($list);
    }

    /**
     * 合并付款提交
     * @param array $payable_ids 要被合并的应付单id集合
     * @param $payment_audit_data 前端提交的付款单信息
     * @param $payment_data 前端提交的应付单信息
     * @param $payable_info 本应付单信息
     * @return mixed
     * @throws Exception
     */
    public function mergePayableSubmit(array $payable_ids, $payment_audit_data, $payment_data, $payable_info)
    {
        $payment_audit_data = TbPurPaymentAuditModel::filterData($payment_audit_data);
        $payment_info = [];
        if (!empty($payable_ids)) {
            $payment_info = $this->payment_table
                ->field('payment_audit_id, amount_payable, amount_confirm')
                ->where(['id'=>['in', $payable_ids]])
                ->select();
            $payment_audit_ids = array_unique(array_column($payment_info, 'payment_audit_id'));
            if (empty($payment_audit_ids)) throw new Exception(L('付款单为空'));

//            $list    = $this->payment_audit_table->where(['id' => ['in', $payment_audit_ids]])->select();
            $save_res = $this->payment_audit_table->where(['id' => ['in', $payment_audit_ids]])->save(['status'=>TbPurPaymentAuditModel::$status_deleted]);
            if (!$save_res) throw new Exception(L('旧付款单更新失败'));
            array_push($payable_ids, $payment_data['id']);
//            Logs($list, __FUNCTION__, __CLASS__);
        } else {
            $payable_ids[] = $payment_data['id'];
        }

        //创建付款单
        $payment_audit_id = $this->createPaymentAuditBill($payment_audit_data, $payment_info, $payment_data, $payable_info);

        //更新应付单信息
        $this->updatePayableInfo($payment_audit_id, $payable_ids, $payment_audit_data);

        //更新采购订单供应商信息
        $this->updateOrderSupplierInfo($payable_ids, $payment_audit_data);

        return $payment_audit_id;
    }

    /**
     * 创建付款单
     * @param $payment_audit_data 付款单信息
     * @param array $payment_info 各个要被合并的应付单确认前后应付金额信息
     * @param $payment_data 本应付单确认后应付金额信息
     * @param $payable_info 本应付单确认前应付金额信息
     * @return mixed
     * @throws Exception
     */
    private function createPaymentAuditBill($payment_audit_data, $payment_info = [], $payment_data, $payable_info)
    {
        $payment_audit_data['status']           = TbPurPaymentAuditModel::$status_accounting_audit;
        $payment_audit_data['payment_audit_no'] = 'FK' . date(Ymd) . TbWmsNmIncrementModel::generateNo('payment_audit');
        $payment_audit_data['created_by']       = $payment_audit_data['updated_by'] = $this->user_name;
        //确认前后总应付 等于 各个应付单确认前后金额之和
        if ($payment_info) {
            $payment_audit_data['payable_amount_before'] = array_sum(array_column($payment_info, 'amount_payable')) + $payable_info['amount_payable'];
            $payment_audit_data['payable_amount_after']  = array_sum(array_column($payment_info, 'amount_confirm')) + $payment_data['amount_confirm'];
        } else {
            $payment_audit_data['payable_amount_before'] = $payable_info['amount_payable'] ? : 0.00;
            $payment_audit_data['payable_amount_after']  = $payment_data['amount_confirm'] ? : 0.00;
        }
        $payment_audit_data['payment_account'] = TbPurPaymentAuditModel::getPaymentAccount($payment_audit_data['payment_channel_cd']);
        $payment_audit_data['source_cd'] = TbPurPaymentAuditModel::$source_payable;
        $payment_audit_data['accounting_audit_user'] = TbPurPaymentAuditModel::$accounting_audit_user;
        $payment_audit_id = $this->payment_audit_table->add($payment_audit_data);
        if (!$payment_audit_id) throw new Exception(L('生成新付款单失败'));
        return $payment_audit_id;
    }

    /**
     * 更新/同步应付单信息
     * @param $payment_audit_id
     * @param $payable_ids
     * @param $payment_audit_data
     * @throws Exception
     */
    private function updatePayableInfo($payment_audit_id, $payable_ids, $payment_audit_data)
    {
        $save = [
            'status'           => TbPurPaymentAuditModel::$status_accounting_audit,
            'payment_audit_id' => $payment_audit_id,
//            'payable_date'     => $payment_audit_data['payable_date_after'],//同步预计付款时间到应付单
        ];
        $save_res = $this->payment_table
            ->where(['id'=>['in', $payable_ids]])
            ->save($save);
        if (false === $save_res) throw new Exception(L('更新应付单状态失败'));
    }

    /**
     * 调拨付款单撤回到待付款
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
                (new TbWmsAccountTurnoverModel())->deleteAlloTurnover($payment_audit_info['payment_audit_no']);
            }
        }
        $res_payment = $this->payment_audit_table->where(['id'=>$payment_audit_id])->save($save);
        if(!$res_payment) {
            throw new \Exception(L('保存失败'));
        }

        $this->emptyPaymentData($payment_audit_id, $status);//应付单数据清空

        $payment_info  = $this->payment_table->field('id as payment_id')->where(['payment_audit_id'=>$payment_audit_id])->select();
        $this->setParameters($payment_info, $status);
        $this->operation_info = '撤回到待付款';
        $this->recordLog();
    }

    /**
     * 清空调拨应付单数据
     * @param $payment_audit_id 付款单id
     * @param $status 状态
     * @throws Exception
     */
    public function emptyPaymentData($payment_audit_id, $status)
    {
        $save_res = $this->payment_table
            ->where(['payment_audit_id' => $payment_audit_id])
            ->save([
                'status'                   => $status,
                'update_time'              => dateTime(),
                'our_company_bank_account' => '',
                'exchange_tax_account'     => '',
                'currency_account'         => '',
                'expense'                  => '',
                'amount_account'           => '',
                'currency_paid'            => '',
                'amount_paid'              => '',
            ]);
        if (!$save_res) {
            throw new \Exception(L('调拨应付单和付款单状态同步失败'));
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
     * 调拨付款单会计审核
     * @param $request_data
     * @throws Exception
     */
    public function accountingAudit($request_data) {
        $this->payment_audit_id   = $request_data['payment_audit_id'];
        $status                   = $request_data['status'] ? : TbPurPaymentAuditModel::$status_business_audit;
        $is_return                = $request_data['is_return'];
        $accounting_return_reason = $request_data['accounting_return_reason'];
        $supply_note              = $request_data['supply_note'];//补充说明

        $payment_audit_info = $this->payment_audit_table->lock(true)->find($this->payment_audit_id);
        if ($payment_audit_info['status'] != TbPurPaymentAuditModel::$status_accounting_audit) {
            throw new \Exception(L('付款状态已更新'));
        }
        if (!in_array($status, [TbPurPaymentAuditModel::$status_no_payment,TbPurPaymentAuditModel::$status_business_audit])) {
            throw new \Exception(L('状态异常'));
        }
        $res = $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save([
            'status'                => $status,
            'updated_by'            => $this->user_name,
        ]);
        if (!$res) throw new \Exception(L('会计审核失败'));

        $payment_info = $this->payment_table
            ->field('id as payment_id')
            ->where(['payment_audit_id'=>$this->payment_audit_id])
            ->select();
        if(empty($payment_info)) throw new \Exception(L('未找到调拨应付单'));
        //日志记录参数
        $this->setParameters($payment_info, $status);
        $res = $this->payment_table
            ->where(['payment_audit_id'=>['in',$this->payment_audit_id]])
            ->save(['status'=>$status, 'accounting_return_reason'=>$accounting_return_reason,'supply_note'=>$supply_note]);
        if (!$res) throw new \Exception(L('更新调拨应付单状态失败'));
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
     * 调拨付款单撤回到待审核
     * @param $request_data
     * @throws Exception
     */
    public function returnToAccountingAudit($request_data) {
        $this->payment_audit_id = $request_data['payment_audit_id'];
        $payment_info = $this->payment_table->field('id as payment_id')->where(['payment_audit_id'=>$this->payment_audit_id])->select();
        $this->setParameters($payment_info, TbPurPaymentAuditModel::$status_accounting_audit);
        $this->updatePayableBillStatus(TbPurPaymentAuditModel::$status_accounting_audit);//更新应付单状态
        $this->updatePaymentBillStatus(TbPurPaymentAuditModel::$status_accounting_audit);//更新付款单状态
        $this->operation_info = '撤回到待审核';
        $this->recordLog();
    }

    /**
     * 调拨付款单业务审核审核
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
        if(empty($payment_info)) throw new \Exception(L('未找到调拨应付单'));
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
     * 调拨应付单撤回到待确认
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
     * 更新调拨应付单状态
     * @param $status
     * @throws Exception
     */
    public function updatePayableBillStatus($status)
    {
        $res = $this->payment_table->where(['payment_audit_id'=>['in',$this->payment_audit_id]])->save(['status'=>$status]);
        if (!$res) throw new \Exception(L('更新调拨应付单状态失败'));
    }

    /**
     * 更新调拨付款单状态
     * @param $status
     * @throws Exception
     */
    public function updatePaymentBillStatus($status)
    {
        $res = $this->payment_audit_table->where(['id'=>['in',$this->payment_audit_id]])->save(['status'=>$status]);
        if (!$res) throw new \Exception(L('更新调拨付款单状态失败'));
    }

    /**
     * 调拨付款单详情
     * @param $payment_audit_id 付款单id
     * @return array
     */
    public function getPaymentAuditDetail($payment_audit_id) {
        $field = "wp.id as payment_id,pa.confirmation_remark as confirmation_remark_audit,pa.status as payable_status,pa.*,wp.*,wp.amount_currency_cd as amount_currency,
         ab.open_bank,ab.company_code,ab.currency_code,oc.payment_manager_by,cd.CD_VAL as difference_reason_val,tb_crm_sp_supplier.SP_NAME as supplier,pa.bank_reference_no,pa.bank_payment_reason";
        $db_res = $this->model->table('tb_pur_payment_audit pa')
            ->field($field)
            ->join('left join tb_wms_payment wp on pa.id = wp.payment_audit_id')
            ->join('left join tb_fin_account_bank ab on pa.payment_account_id = ab.id')
            ->join('left join tb_con_division_our_company oc on pa.our_company_cd = oc.our_company_cd')
            ->join('left join tb_ms_cmn_cd cd on wp.difference_reason = cd.CD')
            ->join('tb_crm_sp_supplier ON wp.supplier_id = tb_crm_sp_supplier.id')
            ->where(['pa.id' => $payment_audit_id])->order('pa.updated_at desc')->select();
        if (empty($db_res)) {
            return [];
        }
        $db_res = CodeModel::autoCodeTwoVal($db_res, [
            'amount_currency',
            'our_company_cd',
            'payment_currency_cd',
            'company_code',
            'billing_currency_cd',
            'currency_code',
            'payment_channel_cd',
            'payment_way_cd',
            'platform_cd',
            'source_cd',
            'payable_currency_cd',
            'commission_type',
            'trade_type_cd',
            'account_currency',
            'account_type',
            'pay_com_cd'
        ]);
        $db_res = (new PurService())->orderStatusToVal($db_res);
        $data = [];
        array_map(function($value) use (&$data) {
            //基本信息
            $data['base_info'] = [
                'payment_audit_no'      => $value['payment_audit_no'],
                'payable_status_val'    => $value['payable_status_val'],
                'payable_date_after'    => $value['payable_date_after'],
                'our_company_cd_val'    => $value['our_company_cd_val'],
                'payment_manager_by'    => $value['payment_manager_by'],
                'created_by'            => $value['created_by'],
                'created_at'            => $value['created_at'],
                'our_company_cd'        => $value['our_company_cd'],
                'source_cd_val'         => $value['source_cd_val'],
                'supplier'              => $value['supplier'],
                'accounting_audit_user' => $value['accounting_audit_user'],
            ];
            //应付明细信息
            $data['payable_info'][] = [
                'payment_id'          => $value['payment_id'],
                'payment_no'          => $value['payment_no'],
                'amount_payable'      => $value['amount'] ?: '0.00',
                'amount_deduction'    => '0.00',
                'amount_confirm'      => $value['amount_confirm'] ?: '0.00',
                'amount_difference'   => $value['amount_difference'] ?: '0.00',
                'difference_reason'   => $value['difference_reason_val'] ?: $value['difference_reason'],
                'amount_currency_val' => $value['amount_currency_val'],
                'remark'              => $value['confirmation_remark'],
                'payment_attachment'  => $value['payment_attachment'],
                'payable_date_before' => $value['payable_date_before'],
            ];
            //支付信息
            $data['payment_info'] = [
                'payment_channel_cd_val' => $value['payment_channel_cd_val'],
                'payment_way_cd_val'     => $value['payment_way_cd_val'],
                'payable_amount_before'  => $value['payable_amount_before'] ?: '0.00',
                'payable_amount_after'   => $value['payable_amount_after'] ?: '0.00',
                'amount_currency_val'    => $value['amount_currency_val'] ? : $value['payable_currency_cd_val'],
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
                'platform_order_no'           => $value['platform_order_no'],
                'collection_account'          => $value['collection_account'],
                'collection_user_name'        => $value['collection_user_name'],
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
                'confirmation_remark' => $value['confirmation_remark_audit']
            ];
        }, $db_res);
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
        TbWmsPaymentLogModel::recordLog($this->payment_ids, $this->operation_info, $date, $status_name, $this->remark);
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
            'ab.bank_short_name',
            'cs.RES_NAME',//国家别名CN
            'ss.BANK_SETTLEMENT_CODE as bank_settlement_code',//本地结算代码
            'ss.CITY as city',//收款银行地址
            'ss.ACCOUNT_CURRENCY as collection_currency',//收款币种
            'wp.amount_currency_cd as payment_currency'//付款币种
        ];
        $row = $this->model->table('tb_pur_payment_audit pa')
            ->field($filed)
            ->join('left join tb_wms_payment wp on pa.id = wp.payment_audit_id')
            ->join('tb_crm_sp_supplier ss ON wp.supplier_id = ss.ID')
            ->join('left join tb_crm_site cs on ss.SP_ADDR1 = cs.ID')
            ->join('left join tb_fin_account_bank ab on pa.payment_account_id = ab.id')
            ->where(['pa.id' => $this->payment_audit_id])
            ->find();
        $country_id = explode(',', $row['city'])[0];//收款银行国家id
        //收款银行国家二字码
        $row['bank_country_short_name'] = M('ms_user_area', 'tb_')->where(['id'=>$country_id])->getField('two_char');

        //收款人国家二字码
        if ($row['RES_NAME'] == 'CN') {
            $row['country_short_name'] = 'CN';
        } else {
            $row['country_short_name'] = M('ms_user_area', 'tb_')->where(['three_char'=>$row['RES_NAME']])->getField('two_char');
        }
        if (strtoupper($row['bank_short_name']) != 'CITI') {
            if (false === $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save(['pay_type'=>0])) {
                throw new Exception(L('更新支付类型失败'));
            }
            return true;
        }
        if (false === $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save(['pay_type'=>1])) {
            throw new Exception(L('更新支付类型失败'));
        }
        //飞松：调拨应付功能废弃了，先不弄
        throw new \Exception(L('调拨应付找不到供应商信息'));
        (new KyribaService())->putXmlToFtp($row);
    }
}