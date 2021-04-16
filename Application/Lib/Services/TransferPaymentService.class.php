<?php
/**
 * 转账换汇付款单相关逻辑
 * User: mark.zhong
 * Date: 20/04/20
 * Time: 13:05
 */
class TransferPaymentService extends Service
{
    public $user_name;
    public $model;
    public $payment_table;
    public $payment_audit_table;
    public $transfer_table;
    private $payment_audit_detail_table;

    //日志记录所需参数
    public $payment_audit_id;
    private $operation_info;
    private $status_name;
    private $remark;

    public function __construct($model) {
        $this->user_name                  = DataModel::userNamePinyin();
        $this->model                      = empty($model) ? new Model() : $model;
        $this->payment_audit_table        = M('payment_audit', 'tb_pur_');
        $this->transfer_table             = M('fin_account_transfer', 'tb_');
        $this->payment_audit_detail_table = M('general_payment', 'tb_');
    }

    /**
     * 出账/出账确认提交
     * @param $request_data
     * @throws Exception
     */
    public function paymentSubmit($request_data) {
        $this->payment_audit_id = $request_data['payment_audit_id'];
        $this->updatePaymentBill($request_data);
        $transfer_info = $this->getTransferRelation($this->payment_audit_id);//获取转账换汇和付款单相关信息
        switch ($request_data['type']) {
            case 2:
                if ($request_data['pay_type'] == 1) {
                    $this->kyribaPayPush();//发送kyriba支付指令

                    $this->operation_info = '推kyriba';
                    $download_url = $this->getKyribaPushFileDownloadPath($transfer_info[0]['payment_audit_no']);
                    if (!empty($download_url)) {
                        $this->remark = "<a href='{$download_url}' style='color:blue;'>推kyriba加密前的文件的附件链接，点击可以下载</a>";
                    }
                    //付款单状态变成待kyriba接收
                    $this->payment_audit_table
                        ->where(['id'=>$this->payment_audit_id])
                        ->save(['status'=>TbPurPaymentAuditModel::$status_kyriba_wait_receive]);
                } else {
                    //不推kyriba
                    $this->operation_info = '确认付款';
                    $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save(['pay_type'=>0]);
                }
                break;
            case 3:
                if (!$turnover_id = $this->thrTurnOver($request_data)) {
                    throw new \Exception(L('添加日记账失败'));
                }
                $rel_res   = (new TbFinClaimModel())->addTransferToTurnoverRelation($turnover_id,$transfer_info);
                if (!$rel_res) {
                    throw new \Exception(L('添加日记账关联失败'));
                }
                $this->operation_info = '确认出账';
                break;
        }
        $status = $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->getField('status');

        if (!$request_data['is_kyriba']) {
            //kyriba处理不记出账日志
            $this->setParameters($status);
            $this->recordLog();
        }
    }

    private function getTransferRelation($payment_audit_id)
    {
        $field = "
            pa.*,
            at.transfer_no,
            at.id as payment_id";
        $db_res = $this->model->table('tb_pur_payment_audit pa')
            ->field($field)
            ->join('left join tb_fin_account_transfer at on pa.id = at.payment_audit_id')
            ->where(['pa.id' => $payment_audit_id])->select();
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
        $this->payment_audit_id = $payment_audit_id;
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
                $this->payment_audit_table->is_direct_billing  = 0;//是否直接出账0-否
                $this->payment_audit_table->fund_allocation_contract_no = $request_data['fund_allocation_contract_no']; //资金调配合同编号
                $res = $this->payment_audit_table->where(['id' => $payment_audit_id])->save();
                if (false === $res) {
                    throw new \Exception(L('确认失败'));
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
                if ($request_data['is_import']) {
                    //批量核销/kyriba付款回传
                    $billing_currency_cd = $data['billing_currency_cd'];
                } else {
                    if (empty($request_data['payment_account_id'])) {
                        $request_data['payment_account_id'] = $payment_info['payment_account_id'];
                    }
                    $billing_currency_cd = TbFinAccountBankModel::getCurrencyCdByBankAccount($request_data['payment_account_id']);
                    if (!$billing_currency_cd) {
                        throw new \Exception(L('出账币种不能为空'));
                    }
                }
                $this->payment_audit_table->billing_at = dateTime();
                $this->payment_audit_table->billing_by = $this->user_name;
                $this->payment_audit_table->status = $status;
                $this->payment_audit_table->billing_currency_cd = $billing_currency_cd;
                $this->payment_audit_table->payment_our_bank_account = $request_data['payment_our_bank_account'];
                $this->payment_audit_table->trade_type_cd = $request_data['trade_type_cd'];
                $this->payment_audit_table->pay_com_cd = $request_data['pay_com_cd']; // 付款公司
                $this->payment_audit_table->fund_allocation_contract_no = $request_data['fund_allocation_contract_no']; //资金调配合同编号
                if (!$request_data['is_import']) {
                    $this->payment_audit_table->payment_our_bank_account = $request_data['payment_our_bank_account'];
                    $this->payment_audit_table->payment_account_id = $request_data['payment_account_id'];
                }
                if (!$request_data['is_kyriba'] && $payment_info['pay_type'] == 2) {
                    //直接出账，不用推kyriba
                    $this->payment_audit_table->pay_type  = 0;//是否通过kyriba付款-否
                }
                if (null === $payment_info['is_direct_billing'] || $payment_info['status'] == TbPurPaymentAuditModel::$status_kyriba_wait_receive) {
                    $this->payment_audit_table->is_direct_billing  = 1;//是否直接出账1-是
                }
                $res = $this->payment_audit_table->where(['id' => $payment_audit_id])->save();
                if (false === $res) {
                    throw new \Exception(L('确认失败'));
                }
                //同步付款单状态
                $this->synPaymentBillStatusToTransfer($payment_audit_id, TbFinAccountTransferModel::$status_wait_collection);
                $this->transferStepHandel();

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
            $vouchers[] = ['name'=>$v['original_name'], 'savename'=>$v['save_name']];
        }
        $field = "
            a.*,
            a.id as payment_id,
            f.open_bank,
            f.swift_code,
            at.transfer_no
        ";
        $info = $this->model->table('tb_pur_payment_audit a')
            ->field($field)
            ->join('left join tb_fin_account_bank f on f.id = a.payment_account_id')
            ->join('left join tb_fin_account_transfer at on at.payment_audit_id = a.id')
            ->where(['a.id' => $billing_info['payment_audit_id']])->find();
        if (empty($info)) {
            return false;
        }

        if($billing_info['billing_amount'] > 0 || $billing_info['billing_fee'] > 0) {
            //应付金额和利息合并写进一条流水，同时写进两条日记账关联记录
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
                'childTransferNo'  => $info['transfer_no'],
                'transferType'     => "N001950300",//划转转出
                'currencyCode'     => $info['billing_currency_cd'],
                'oppCompanyName'   => $info['supplier_collection_account'] ? : $info['collection_account'],//收款账户名
                'oppOpenBank'      => $info['supplier_opening_bank'],//供应商银行开户行
                'oppAccountBank'   => $info['supplier_card_number'],//供应商银行卡号
                'oppSwiftCode'     => $info['supplier_swift_code'],//供应商SWIFT CODE
                'handlingFee'      => $billing_info['billing_fee'],//手续费
                'transferVoucher'  => $vouchers,
                'createTime'       => $info['billing_at'],
                'createUser'       => $_SESSION['user_id'],
                'paymentChannelCd' => $info['payment_channel_cd'],
                'remark'           => $info['confirmation_remark'],
                'tradeType'       => 1//是关联交易
            ];
            return (new TbFinAccountTurnoverModel())->thrTurnOver($param);
        }
        return true;
    }

    /**
     * 转账换汇付款单撤回到待付款
     * @param $payment_audit_id 付款单id
     * @param string $reason
     * @param string $is_payment_return 用于判断是否需要删除日记账
     * @throws Exception
     */
    public function returnToPaymentConfirm($payment_audit_id, $reason = '', $is_payment_return) {
        //判断如果转账换汇已经收款完成，则付款单不能撤回
        $transfer_info = $this->transfer_table->where(['payment_audit_id' => $payment_audit_id])->find();
        if (!empty($transfer_info['rec_transfer_time']) || !empty($transfer_info['rec_actual_money'])) {
            throw new \Exception(L('转账换汇单已经确认收款，不允许撤回'));
        }

        $this->payment_audit_id = $payment_audit_id;
        $payment_audit_info = $this->payment_audit_table->lock(true)->find($payment_audit_id);
        if(!in_array($payment_audit_info['status'],[
            TbPurPaymentAuditModel::$status_no_billing,
            TbPurPaymentAuditModel::$status_finished,
            TbPurPaymentAuditModel::$status_kyriba_not_pass,
            TbPurPaymentAuditModel::$status_payment_failed,
            TbPurPaymentAuditModel::$status_kyriba_receive_failed,
        ])){
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
            if (($payment_audit_info['billing_amount'] > 0 || $payment_audit_info['billing_fee'] > 0) && !$is_payment_return)
            {
                (new TbWmsAccountTurnoverModel())->deleteTransferTurnover($payment_audit_info['payment_audit_no']);
            }
        }
        $res_payment = $this->payment_audit_table->where(['id'=>$payment_audit_id])->save($save);
        if(!$res_payment) {
            throw new \Exception(L('保存失败'));
        }

        $this->synPaymentBillStatusToTransfer($this->payment_audit_id, TbFinAccountTransferModel::$status_wait_pay);
        $this->transferStepHandel(false);

        $this->setParameters($status);
        $this->operation_info = '撤回到待付款';
        $this->recordLog();
    }

    private function synPaymentBillStatusToTransfer($payment_audit_id, $status)
    {
        $save_data = [
            'state' => $status
        ];
        $save_res = $this->transfer_table->where(['payment_audit_id' => $payment_audit_id])->save($save_data);
        if (false === $save_res) {
            throw new \Exception(L('转账换汇同步付款单状态失败'));
        }
    }

    /**
     * 付款单会计审核
     * @param $request_data
     * @throws Exception
     */
    public function accountingAudit($request_data) {
        $this->payment_audit_id   = $request_data['payment_audit_id'];
        $status                   = $request_data['status'] ? : TbPurPaymentAuditModel::$status_deleted;
        $is_return                = $request_data['is_return'];

        $payment_audit_info = $this->payment_audit_table->lock(true)->find($this->payment_audit_id);
        if ($payment_audit_info['status'] != TbPurPaymentAuditModel::$status_accounting_audit) {
            throw new \Exception(L('付款状态已更新'));
        }
        $res = $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save([
            'status'                => $status,
            'updated_by'            => $this->user_name,
        ]);
        if (!$res) throw new \Exception(L('会计审核失败'));

        $this->transferStepHandel();

        $audit_info = '会计审核';
        $transfer_no = $this->transfer_table->where(['payment_audit_id'=>$this->payment_audit_id])->getField('transfer_no');
        $transfer_where = ['order_no'=>$transfer_no,'msg'=>$audit_info];
        if (M('fin_account_bank_log', 'tb_')->where($transfer_where)->find()) {
            M('fin_account_bank_log', 'tb_')->where($transfer_where)->save(['create_time'=>dateTime()]);
        } else {
            (new TbWmsAccountBankLogModel())->createLog($transfer_no, $audit_info, null, 2);
        }

        if ($status == TbPurPaymentAuditModel::$status_deleted) {
            //会计审核退回/撤回
            $this->cancelTransferData($this->payment_audit_id);
        }
        //日志记录参数
        $this->setParameters($status);

        $this->synPaymentBillStatusToTransfer($this->payment_audit_id, TbFinAccountTransferModel::$status_wait_pay);

        if ($is_return) {
            $this->operation_info = '会计审核退回';
        } else if ($status == TbPurPaymentAuditModel::$status_no_payment ) {
            $this->operation_info = '会计审核通过';
        } else {
            $this->operation_info = '会计审核撤回';
        }
        $this->recordLog();
    }
    
    //转账换汇步骤控制
    public function transferStepHandel($inc = true)
    {
        if ($inc) {
            $this->transfer_table->where(['payment_audit_id' => $this->payment_audit_id])->setInc('current_step', 1);
        } else {
            $this->transfer_table->where(['payment_audit_id' => $this->payment_audit_id])->setDec('current_step', 1);
        }
    }

    private function cancelTransferData($payment_audit_id)
    {
        $save = [
            'state' => TbFinAccountTransferModel::$status_audit_fail,
            'payment_audit_id'=>null
        ];
        $res = $this->transfer_table->where(['payment_audit_id' => $payment_audit_id])->save($save);
        if (!$res) {
            throw new \Exception(L('转账换汇单状态更新失败'));
        }
    }


    /**
     * 转账换汇操作关闭付款单
     * @param $id
     */
    public function cancelPaymentBill($id)
    {
        $this->payment_audit_id = $id;
        $this->setParameters(TbPurPaymentAuditModel::$status_deleted);
        $this->updatePaymentBillStatus(TbPurPaymentAuditModel::$status_deleted);
        $this->operation_info = '转账换汇重新编辑提交';
        $this->recordLog();
    }

    /**
     * 付款单撤回到待会计审核
     * @param $request_data
     * @throws Exception
     */
    public function returnToAccountingAudit($request_data) {
        $this->payment_audit_id = $request_data['payment_audit_id'];
        $this->setParameters(TbPurPaymentAuditModel::$status_accounting_audit);

        $this->updatePaymentBillStatus(TbPurPaymentAuditModel::$status_accounting_audit);//更新付款单状态

        $this->synPaymentBillStatusToTransfer($this->payment_audit_id, TbFinAccountTransferModel::$status_wait_accounting);
        $this->transferStepHandel(false);
        $this->operation_info = '撤回到待会计审核';
        $this->recordLog();
    }

    /**
     * 更新付款单状态
     * @param $status
     * @throws Exception
     */
    public function updatePaymentBillStatus($status)
    {
        $res = $this->payment_audit_table->where(['id'=>['in',$this->payment_audit_id]])->save(['status'=>$status]);
        if (!$res) throw new \Exception(L('更新转账换汇付款单状态失败'));
    }

    /**
     * 转账换汇付款单详情
     * @param $payment_audit_id 付款单id
     * @return array
     */
    public function getPaymentAuditDetail($payment_audit_id) {
        $field = "
            pa.*,
            pa.status as payable_status,
            ab.open_bank,ab.company_code,
            ab.currency_code,
            oc.payment_manager_by,
            gp.*,
            at.*,
            ab2.open_bank as bank_open_bank,
            ab2.company_code as bank_company_code,
            ab2.bank_settlement_code,
            ab2.bank_address,
            ab2.bank_address_detail,
            ab2.bank_postal_code,
            ab2.bank_account_type,
            ab2.swift_code supplier_swift_code,
            ab2.city,
            ab2.currency_code as bank_currency_code,
            ab2.bank_short_name,
            ab2.supplier_id,
            ab2.account_class_cd,
            at.id as payment_id";
        $db_res = $this->model->table('tb_pur_payment_audit pa')
            ->field($field)
            ->join('left join tb_fin_account_transfer at on pa.id = at.payment_audit_id')
            ->join('left join tb_general_payment gp on pa.id = gp.payment_audit_id')
            ->join('left join tb_fin_account_bank ab on pa.payment_account_id = ab.id')
            ->join('left join tb_fin_account_bank ab2 on pa.supplier_card_number = ab2.account_bank')
            ->join('left join tb_con_division_our_company oc on pa.our_company_cd = oc.our_company_cd')
            ->where(['pa.id' => $payment_audit_id])->order('pa.updated_at desc')->select();
        if (empty($db_res)) {
            return [];
        }
        $db_res = CodeModel::autoCodeTwoVal($db_res, [
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
            'settlement_type',
            'procurement_nature',
            'invoice_information',
            'invoice_type',
            'bill_information',
            'payment_type',
            'commission_type',
            'trade_type_cd',
            'account_currency',
            'bank_account_type',
            'bank_currency_code',
            'bank_company_code',
            'account_type',
            'pay_com_cd'
        ]);
        $db_res = (new PurService())->orderStatusToVal($db_res);
        $data = [];
        array_map(function($value) use (&$data) {
            if ($value['source_cd'] == 'N003010006') {
                //账号公司归属为供应商 查询供应商数据
                if (is_numeric($value['our_company_cd'])) {
                    $value['our_company_cd_val'] = $this->model->table('tb_crm_sp_supplier')->where(['ID' => $value['our_company_cd']])->getField('SP_NAME');
                }
                if ($value['account_class_cd'] == 'N003510002') {
                    $value['bank_company_code_val'] = $this->model->table('tb_crm_sp_supplier')->where(['ID' => $value['supplier_id']])->getField('SP_NAME');
                }
            }
            //基本信息
            $data['base_info'] = [
                'payment_audit_no'        => $value['payment_audit_no'],
                'payable_status_val'      => $value['payable_status_val'],
                'source_cd_val'           => $value['source_cd_val'],
                'payment_nature'          => $value['payment_nature'] == 1 ? '对公' : '对私',
                'our_company_cd'          => $value['our_company_cd'],
                'our_company_cd_val'      => $value['our_company_cd_val'],
                'supplier'                => $value['supplier'],
                'contract_information'    => $value['contract_information'] == 1 ? '有' : '',
                'contract_no'             => $value['contract_no'],
                'settlement_type_val'     => $value['settlement_type_val'],
                'procurement_nature_val'  => $value['procurement_nature_val'],
                'invoice_information_val' => $value['invoice_information_val'],
                'invoice_type_val'        => $value['invoice_type_val'],
                'bill_information_val'    => $value['bill_information_val'],
                'payment_type_val'        => $value['payment_type_val'],
                'actual_fee_applicant'    => $value['actual_fee_applicant'],
                'actual_fee_department'   => $value['actual_fee_Department'],
                'payable_currency_cd_val' => $value['payable_currency_cd_val'],//付款币种
                'accounting_audit_user'   => TbPurPaymentAuditModel::$accounting_audit_user,
                'payment_manager_by'      => $value['payment_manager_by'],
                'created_by'              => $value['created_by'],
                'created_at'              => $value['created_at'],
                'use'                     => $value['use'],
                'supplier'                => '',
                'accounting_audit_user'   => $value['accounting_audit_user'],
            ];
            //应付明细信息
            $data['payable_info'][] = [
                'payment_id'              => $value['payment_id'],
                'payment_no'              => $value['transfer_no'],
                'payable_date_before'     => $value['payable_date_before'],
                'amount_payable'          => $value['payable_amount_before'] ?: '0.00',
                'payable_date_after'      => $value['payable_date_after'],
                'amount_confirm'          => $value['payable_amount_after'] ?: '0.00',
                'payable_currency_cd_val' => $value['payable_currency_cd_val'],
                'amount_deduction'        => '0.00',
                'payable_amount_after'    => $value['payable_amount_after'] ?: '0.00',
                'amount_difference'       => '0.00',
                'difference_reason'       => $value['difference_reason_val'] ?: $value['difference_reason'],
                'payment_remark'          => $value['reason'],
                'payment_attachment'      => '',
            ];
            //支付信息
            $data['payment_info'] = [
                'payment_channel_cd_val' => $value['payment_channel_cd_val'],
                'payment_way_cd_val'     => $value['payment_way_cd_val'],
                'payable_amount_before'  => $value['payable_amount_before'] ?: '0.00',
                'payable_amount_after'   => $value['payable_amount_after'] ?: '0.00',
                'amount_currency_val'    => $value['payable_currency_cd_val'],
                'trade_type_cd'          => $value['trade_type_cd'],
                'trade_type_cd_val'      => $value['trade_type_cd_val'],
                'pay_type'               => TbPurPaymentAuditModel::$pay_type_map[$value['pay_type']],
            ];

            // 收款方账户/订单信息
            $data['receipt_info'] = [
                //'supplier_collection_account' => $value['supplier_collection_account'],
                //'supplier_opening_bank'       => $value['supplier_opening_bank'],
                'supplier_collection_account' => $value['bank_company_code_val'],
                'supplier_opening_bank'       => $value['bank_open_bank'],
                'supplier_card_number'        => $value['supplier_card_number'],
                'supplier_swift_code'         => $value['supplier_swift_code'],
                'bank_settlement_code'        => $value['bank_settlement_code'],
                'bank_address'                => $value['bank_address'],
                'bank_address_detail'         => $value['bank_address_detail'],
                'bank_postal_code'            => $value['bank_postal_code'],
                'account_type_val'            => $value['bank_account_type_val'],
                'account_currency_val'        => $value['bank_currency_code_val'],
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
        }, $db_res);

        
        return DataModel::formatAmount($data);
    }

    /**
     *设置参数
     * @param $payment_info
     * @param $status
     */
    public function setParameters($status)
    {
        $this->status_name = TbPurPaymentAuditModel::$status_map[$status];
    }

    //日志记录
    public function recordLog()
    {
        $date = dateTime();
        TbPurPaymentAuditLogModel::recordLog($this->payment_audit_id, $this->operation_info, $date, $this->status_name, $this->remark);
    }

    public function createPaymentAuditBill($data)
    {
        $source_cd = $data['account_transfer_type'] == '1' ? TbPurPaymentAuditModel::$source_transfer_payable : TbPurPaymentAuditModel::$source_transfer_payable_indirect;
        $add_data = [
            'payment_audit_no'            => 'FK' . date(Ymd) . TbWmsNmIncrementModel::generateNo('payment_audit'),
            'our_company_cd'              => $data['pay_company_code'] ?: '',
            'status'                      => 4,
            'payable_amount_before'       => $data['amount_money'],
            'payable_amount_after'        => $data['amount_money'],
            'payable_date_after'          => dateTime(),
            'payable_date_before'         => dateTime(),
            'supplier_opening_bank'       => $data['rec_company_name'],
            'supplier_collection_account' => $data['rec_open_bank'],
            'supplier_card_number'        => $data['rec_account_bank'],
            'supplier_swift_code'         => M('fin_account_bank','tb_')->where(['account_bank'=>$data['rec_account_bank']])->getField('swift_code') ? : '',
            'created_by'                  => $this->user_name,
            'created_at'                  => dateTime(),
            'payment_channel_cd'          => TbPurPaymentAuditModel::$channel_bank,
            'payment_way_cd'              => TbPurPaymentAuditModel::$way_transfer,
            'source_cd'                   => $source_cd,//关联交易
            'payable_currency_cd'         => $data['currency_code'],
            'accounting_audit_user'       => TbPurPaymentAuditModel::$accounting_audit_user
        ];
        if (!$id = $this->payment_audit_table->add($add_data)) {
            throw new Exception(L('创建付款单失败'));
        }
        $this->createPaymentAuditBillExtend($id, $data);
        return $id;
    }

    private function createPaymentAuditBillExtend($payment_audit_id, $transfer_info) {
        $add_data = [
            'payment_audit_id' => $payment_audit_id,
            'payment_nature' => 1,
            'supplier' => '',
            'contract_information' => '',
            'contract_no' => '',
            'settlement_type' => 'N003270004',//结算类型-其它
            'procurement_nature' => 'N003280003',//采购性质-其它
            'invoice_information' => 'N003290003',
            'bill_information' => 'N003300003',
            'payment_type' => 'N002930009',
            'commission_type' => 'N003320001',//手续费承担方式-无
            'actual_fee_applicant' => DataModel::getUserNameById($transfer_info['create_user']),
            'actual_fee_Department' => '',
            'payment_remark' => '',
            'created_by' => $this->user_name,
            'updated_by' => $this->user_name,
        ];
        if (!$this->payment_audit_detail_table->add($add_data)) {
            throw new Exception(L('创建付款单扩展数据失败'));
        }
    }

    private function kyribaPayPush()
    {
        $filed = [
            'pa.*',
            'pp.account_transfer_type',
            'ab.company_code',
            'ab.open_bank',
            'ab.account_bank',
            'ab.swift_code',
            'ab.currency_code',
            'ab.bank_short_name',
            'area.two_char as country_short_name',//国家别名CN
            'area.two_char as bank_country_short_name',//收款银行国家二字码
            'abr.bank_settlement_code',//本地结算代码
            'abr.currency_code as collection_currency',//收款币种
            'abr.city',//城市列表
            'abr.account_class_cd',//账户归属
            'abr.swift_code as supplier_swift_code',//收款账号swiftcode
            'abr.supplier_id',//供应商id
            'ab.currency_code as payment_currency'//付款币种
        ];
        $row = $this->model->table('tb_pur_payment_audit pa')
            ->field($filed)
            ->join('left join tb_fin_account_transfer pp on pa.id = pp.payment_audit_id')
            ->join('left join tb_fin_account_bank abr on abr.id = pp.rec_account_bank_id')
            ->join('left join tb_crm_company_management cm on cm.our_company_cd = abr.company_code')
            ->join('left join tb_ms_user_area area on cm.reg_country = area.id')
            ->join('left join tb_fin_account_bank ab on ab.id = pa.payment_account_id')
            ->where(['pa.id' => $this->payment_audit_id])
            ->find();
        //付款单来源 关联交易（间接）收款银行国家二字码取收款账号维护的国家对应二字码
        if ($row['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable_indirect) {
            $city_id = explode(',', $row['city'])[0];
            $two_char = $this->model->table('tb_ms_user_area')->where(['id' => $city_id])->getField('two_char');
            $row['bank_country_short_name'] = $two_char;
            //收款账号归属:供应商 收款人国家代码取收款账号对应供应商的国家二字码
            if ($row['account_class_cd'] == 'N003510002') {
                //根据地区id获取对应的国家信息
                $two_char = $this->model->table('tb_crm_sp_supplier css')
                    ->join('left join tb_crm_site cs on cs.ID = css.SP_ADDR1')
                    ->join('left join tb_ms_user_area area on cs.USER_AREA_ID = area.id')
                    ->where(['css.ID' => $row['supplier_id']])->getField('area.two_char');
                $row['country_short_name'] = $two_char ? $two_char : '';
            }
        }
        if (strtoupper($row['bank_short_name']) != 'CITI') {
            throw new Exception(L('不是CITI禁止推kyriba'));
        }
        if (false === $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save(['pay_type'=>1])) {
            throw new Exception(L('更新是否推kyriba状态失败'));
        }
        //转账换汇区分直接间接
        $source_cd = $row['account_transfer_type'] == '1' ? TbPurPaymentAuditModel::$source_transfer_payable : TbPurPaymentAuditModel::$source_transfer_payable_indirect;
        (new KyribaService())->putXmlToFtp($row, $source_cd);
    }
}