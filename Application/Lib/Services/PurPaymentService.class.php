<?php
/**
 * 采购结算3.0 合并付款
 * User: fuming
 * Date: 19/08/25
 * Time: 10:05
 */
class PurPaymentService extends Service
{
    public $user_name;
    public $model;
    public $payment_table;
    public $transfer_table;
    public $payment_audit_table;
    public $order_detail_table;
    public $general_payment_table;
    public $general_payment_detail_table;

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
        $this->payment_table       = M('payment', 'tb_pur_');
        $this->payment_table       = M('payment', 'tb_pur_');
        $this->payment_audit_table = M('payment_audit', 'tb_pur_');
        $this->order_detail_table  = M('order_detail', 'tb_pur_');
        $this->general_payment_table         = M('general_payment', 'tb_');
        $this->general_payment_detail_table  = M('general_payment_detail', 'tb_');
        $this->transfer_table             = M('fin_account_transfer', 'tb_');
        $this->repository = new PurRepository($this->model);
    }

    /**
     * 付款单列表
     * @param $request_data
     * @param bool $is_excel
     * @return array
     */
    public function searchPaymentAuditList($request_data, $is_excel = false)
    {
        $search_map = [
            'status'                => 'pa.status',
//            'payment_audit_no'      => 'pa.payment_audit_no',
            'payable_date_after'    => 'pa.payable_date_after',
            'created_by'            => 'pa.created_by',
            'created_at'            => 'pa.created_at',
            # 'payment_manager_by'    => 'oc.payment_manager_by',
            'our_company_cd'        => 'pa.our_company_cd',
            'payment_channel_cd'    => 'pa.payment_channel_cd',
            'payment_way_cd'        => 'pa.payment_way_cd',
            'source_cd'             => 'pa.source_cd',
            # 'accounting_audit_user' => 'pa.accounting_audit_user',
            'payment_no'            => 'pp.payment_no',
            'pay_com_cd'            => 'pa.pay_com_cd',
            'pay_type'              => 'pa.pay_type',
            'bank_reference_no'     => 'pa.bank_reference_no',
            'payment_nature'        => 'gp.payment_nature',
            'supplier'              => 'gp.supplier',
            'contract_information'  => 'gp.contract_information',
            'contract_no'           => 'gp.contract_no',
            'payment_type'           => 'gp.payment_type',
            'dept_id'           => 'gp.dept_id',
        ];
//        $search_accurate_arr  = ['created_by','payment_manager_by,our_company_cd','status','payment_audit_no','payment_no'];
        $search_accurate_arr['all'] = true;
        list($where, $limit)  = WhereModel::joinSearchTemp($request_data, $search_map, [], $search_accurate_arr);

        $search = isset($request_data['search']) ? $request_data['search'] : [];
        # 审核负责人
        if($search['accounting_audit_user']) {
            $complex = [
                'pa.current_audit_user' =>  $search['accounting_audit_user'],
                '_string' => "FIND_IN_SET('{$search['accounting_audit_user']}',REPLACE(pa.accounting_audit_user ,'->',','))",
                '_logic' => 'or',
            ];
            $where['_complex'] = $complex;
        }

        # 付款负责人 查询修改
        if($search['payment_manager_by']) {
            $where['_string'] = "FIND_IN_SET('{$search['payment_manager_by']}', oc.payment_manager_by)";
        }

//        $status = TbPurPaymentAuditModel::$status_deleted;
//        $where['_string'] = "pa.status != $status";
        //权限:除了配置管理-分工配置-按公司分工-付款负责人里配置的用户，其他用户进入付款单列表，默认筛选【创建人】=当前登录用户
        /*if (!(new GeneralPaymentService(null))->getAuthByUserId()) {
            $where['pa.created_by'] = $_SESSION['m_loginname'];
        }*/
        if (!empty($request_data['search']['is_audit_list'])) {
            //付款审核展示各自审核付款单
            $where['current_audit_user'] = DataModel::userNamePinyin();
        }
        $no = $request_data['search']['payment_audit_no'];
        if (!empty($no)) {
            $where['_string'] = " (pa.payment_audit_no = '{$no}' or pa.payment_audit_no_old = '{$no}' )";
        }
        list($res_db, $pages) = $this->repository->getPaymentAuditList($where, $limit, $is_excel);
        $res_db = CodeModel::autoCodeTwoVal($res_db, [
            'our_company_cd',
            'payment_currency_cd',
            'source_cd',
            'payable_currency_cd',
            'payment_channel_cd',
            'payment_way_cd',
            'platform_cd',
            'billing_currency_cd',
            'pay_com_cd',
            'settlement_type',
            'procurement_nature',
            'invoice_information',
            'invoice_type',
            'bill_information',
            'payment_type',
        ]);
        $res_db = (new PurService())->orderStatusToVal($res_db);
        foreach ($res_db as $key => $val) {
            if ($val['source_cd'] == 'N003010004' && $val['payable_status'] == 0) {
                $res_db[$key]['payable_status_val'] = '待提交';
            }
            if ($val['source_cd'] == 'N003010006' && is_numeric($val['our_company_cd'])) {
                $res_db[$key]['our_company_cd_val'] = $val['SP_NAME'];
            }
            //付款单待审核
            if ($val['payable_status'] == TbPurPaymentAuditModel::$status_accounting_audit) {
                $res_db[$key]['accounting_audit_user'] =
                    trim(str_replace('->' . $val['current_audit_user'] . '->', '->' . '<span style="color: red;">' . $val['current_audit_user'] . '</span>' . '->', '->' . $val['accounting_audit_user'] . '->'), '->');
            }
        }
        $res_db = DataModel::formatAmount($res_db);
        return [
            'data' => $res_db,
            'pages' => $pages
        ];
    }

    /**
     * 获取付款单的付款负责人
     * @param $condtion
     * @param $field
     * @return mixed
     */
    public function getPersonUser($condtion,$field){
        $db_res = $this->model->table('tb_pur_payment_audit pa')
            ->field($field)
            ->join('left join tb_con_division_our_company oc on pa.our_company_cd = oc.our_company_cd')
            ->where($condtion)
            ->find();
        return $db_res;
    }

    /**
     * 付款单详情
     * @param $payment_audit_id 付款单id
     * @return array
     */
    public function getPaymentAuditDetail($payment_audit_id) {
        $field = "pa.*,pa.status as payable_status, pp.payment_no,pp.amount_payable,pp.amount_confirm,pp.amount_deduction,
            pp.amount_difference,pp.difference_reason,od.amount_currency,ab.open_bank,ab.company_code,ab.currency_code,
            pa.billing_currency_cd,pa.source_cd,oc.payment_manager_by,pp.id as payment_id,pp.confirm_remark,
            pp.payable_date,pp.payment_attachment,cd.CD_VAL as difference_reason_val ,od.supplier_id AS supplier";
        $db_res = $this->model->table('tb_pur_payment_audit pa')
            ->field($field)
            ->join('left join tb_pur_payment pp on pa.id = pp.payment_audit_id')
            ->join('left join tb_pur_relevance_order rel on pp.relevance_id = rel.relevance_id')
            ->join('left join tb_pur_order_detail od on rel.order_id = od.order_id')
            ->join('left join tb_fin_account_bank ab on pa.payment_account_id = ab.id')
            ->join('left join tb_con_division_our_company oc on pa.our_company_cd = oc.our_company_cd')
            ->join('left join tb_ms_cmn_cd cd on pp.difference_reason = cd.CD')
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
            'trade_type_cd',
            'account_currency',
            'commission_type',
            'account_type',
            'pay_com_cd'

        ]);
        $db_res = (new PurService())->orderStatusToVal($db_res);
        $data = [];
        array_map(function($value) use (&$data) {
            //基本信息
            $data['base_info'] = [
                'id'      => $value['id'],
                'payment_audit_no'      => $value['payment_audit_no'],
                'payable_status_val'    => $value['payable_status_val'],
                'payable_date_after'    => $value['payable_date_after'],
                'our_company_cd_val'    => $value['our_company_cd_val'],
                'payment_manager_by'    => $value['payment_manager_by'],
                'created_by'            => $value['created_by'],
                'created_at'            => $value['created_at'],
                'our_company_cd'        => $value['our_company_cd'],
                'source_cd_val'         => $value['source_cd_val'],
                'source_cd'             => $value['source_cd'],
                'supplier'              => $value['supplier'],
                'accounting_audit_user' => $value['accounting_audit_user'],
                'receive_fail_reason'   => $value['receive_fail_reason'],
            ];
            //应付明细信息
            $data['payable_info'][] = [
                'payment_id'          => $value['payment_id'],
                'payment_no'          => $value['payment_no'],
                'amount_payable'      => $value['amount_payable'] ?: '0.00',
                'amount_deduction'    => $value['amount_deduction'] ?: '0.00',
                'amount_confirm'      => $value['amount_confirm'] ?: '0.00',
                'amount_difference'   => $value['amount_difference'] ?: '0.00',
                'difference_reason'   => $value['difference_reason_val'] ?: $value['difference_reason'],
                'amount_currency_val' => $value['amount_currency_val'],
                'remark'              => $value['confirm_remark'],
                'payment_attachment'  => $value['payment_attachment'],
                'payable_date_before' => $value['payable_date'],
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
                'account_type'                => $value['account_type'],
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
//                    'company_code_val'         => $value['company_code_val'],
//                    'open_bank'                => $value['open_bank'],
//                    'payment_our_bank_account' => $value['payment_our_bank_account'],
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
     * 出账/出账确认提交
     * @param $request_data
     * @throws Exception
     */
    public function paymentSubmit($request_data) {
        $this->payment_audit_id = $request_data['payment_audit_id'];
        $this->updatePaymentBill($request_data);
        $pur_info = $this->repository->getOrderInfoByPaymentAuditIds($this->payment_audit_id);//获取更新后的采购相关信息
        switch ($request_data['type']) {
            case 2:
                if ($request_data['pay_type'] == 1) {
                    $this->kyribaPayPush();//发送kyriba支付指令

                    $this->operation_info = '推kyriba';
                    $download_url = $this->getKyribaPushFileDownloadPath($pur_info[0]['payment_audit_no']);
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
                //付款核销选择已出账或待出账出账更新各个采购单状态
                $order_ids = [];
                foreach ($pur_info as $v) {
                    if (isset($order_ids[$v['order_id']])) {
                        //同一采购单不用重复更新状态
                        continue;
                    }
                    $order_ids[$v['order_id']] = true;
                    if (false === $this->updatePurRelevanceOrderPaymentStatus($v)) {
                        throw new \Exception(L('更新采购单付款状态失败'));
                    }
                }
                if (!$turnover_id = $this->thrTurnOver($request_data)) {
                    throw new \Exception(L('添加日记账失败'));
                }
                $rel_res = (new TbFinClaimModel())->addPurToTurnoverRelation($turnover_id, $pur_info);
                if (!$rel_res) {
                    throw new \Exception(L('添加日记账关联失败'));
                }
                $this->createDeduction($pur_info);
                $this->operation_info = '确认出账';
                break;
        }
        $payment_info = $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->find();
        if (((!$request_data['submit_type'] || $request_data['is_import']) && $payment_info['pay_type'] != 1)) {
            //提交付款（未出账）或者批量核销并且不推kyriba
            $this->sendPaidEmail($pur_info, A('OrderDetail')->paid_email_content(APP_PATH. 'Tpl/Home/OrderDetail/paid_email.html'));
        }
        if (!$request_data['is_kyriba']) {
            //kyriba处理不记出账日志
            $this->setParameters($pur_info, $payment_info['status']);
            $this->recordLog();
        }
    }

    /**
     * 预付款付款，生成抵扣金记录
     * @param $pur_info 采购单相关信息
     * @throws Exception
     */
    private function createDeduction($pur_info)
    {
        // 预付款付款，生成抵扣金记录
        // 判断该应付是否为预付款？即创建订单（触发操作），因为只有创建订单的触发操作才会生成预付款一说
        foreach ($pur_info as $v) {
            if ($v['money_type'] == 1 && $v['action_type_cd'] == 'N002870001') {
                $addDataInfo                     = [];
                $addDataInfo['clause_type']      = '6';
                $addDataInfo['class']            = __CLASS__;
                $addDataInfo['function']         = __FUNCTION__;
                $amount_payable                  = $v['use_deduction'] ? $v['amount_confirm'] + $v['amount_deduction'] : $v['amount_confirm']; //确认后应付金额 + （是否使用抵扣金）
                $addDataInfo['amount_deduction'] = $amount_payable;
                $pu_res                          = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '2', 'N002870015', $v['relevance_id'], $v['payment_no']);
                if (!$pu_res) {
                    throw new \Exception(L('预付款-生成抵扣金失败'));
                }
            }
        }
    }

    /**
     * 分摊金额计算及付款单更新
     * @param $request_data
     * @throws Exception
     */
    private function updatePaymentBill($request_data)
    {
        $payment_audit_id    = $request_data['payment_audit_id'];
        $payment_audit_table = $this->payment_audit_table;
        $pur_info = $this->repository->getOrderInfoByPaymentAuditIds($payment_audit_id);//获取采购相关信息
        switch ($request_data['type']) {
            case 2:
                //待出账
                $data = $request_data['no_billing'];
                $data['payment_voucher'] = DataModel::arrToJson($data['payment_voucher']);
                if(!$payment_audit_table->create($data)) {
                    throw new \Exception(L('创建付款确认信息失败'));
                }
                $status = TbPurPaymentAuditModel::$status_no_billing;
                $payment_audit_table->payment_at = dateTime();
                $payment_audit_table->payment_by = $this->user_name;
                $payment_audit_table->status     = $status;
                $payment_audit_table->payment_our_bank_account = $request_data['payment_our_bank_account'];
                $payment_audit_table->payment_account_id = $request_data['payment_account_id'];
                $payment_audit_table->trade_type_cd = $request_data['trade_type_cd'];
                $payment_audit_table->commission_type = $request_data['commission_type']; //commission_type 保存手续费承担方式
                $payment_audit_table->pay_com_cd = $request_data['pay_com_cd'];
                $payment_audit_table->is_direct_billing  = 0;//是否直接出账0-否
                $payment_audit_table->fund_allocation_contract_no = $request_data['fund_allocation_contract_no']; //资金调配合同编号
                $res = $payment_audit_table->where(['id' => $payment_audit_id])->save();
                if (false === $res) {
                    throw new \Exception(L('确认失败'));
                }

                //分摊付款金额，应付单和付款单状态同步（待优化）
                foreach ($pur_info as $value) {
                    //本单【确认后-应付金额】 * 该付款单【提交付款金额】 / （∑合并的应付单的【确认后-应付金额】
                    $apportion_amount = $value['amount_confirm'] * $data['payment_amount'] / $value['payable_amount_after'];
                    $save = [
                        'status'        => $status,
                        'amount_paid'   => $apportion_amount,
                        'update_time'   => dateTime(),
                        'currency_paid' => $data['payment_currency_cd']
                    ];
                    $save_res = $this->payment_table
                        ->where(['id' => $value['payment_id']])
                        ->save($save);
                    if (!$save_res) {
                        throw new \Exception(L('分摊付款金额、应付单和付款单状态同步失败'));
                    }
                }
                break;
            case 3:
                //出账
                $payment_info = $payment_audit_table->find($payment_audit_id);
                $data = $request_data['already_billing'];
                if (is_array($data['billing_voucher'])) {
                    $data['billing_voucher'] = DataModel::arrToJson($data['billing_voucher']);
                }
                if(!$payment_audit_table->create($data)) {
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
                $billing_exchange_rate = exchangeRateConversion(cdVal($billing_currency_cd), cdVal($pur_info[0]['amount_currency']), date('Ymd'));
                $payment_audit_table->billing_at = dateTime();
                $payment_audit_table->billing_by = $this->user_name;
                $payment_audit_table->status = $status;
                $payment_audit_table->billing_exchange_rate = $billing_exchange_rate;
                $payment_audit_table->billing_currency_cd = $billing_currency_cd;
                if (!$request_data['is_import']) {
                    $payment_audit_table->payment_our_bank_account = $request_data['payment_our_bank_account'];
                    $payment_audit_table->payment_account_id = $request_data['payment_account_id'];
                }
                $payment_audit_table->trade_type_cd = $request_data['trade_type_cd'];
                $payment_audit_table->pay_com_cd = $request_data['pay_com_cd'];
                $payment_audit_table->fund_allocation_contract_no = $request_data['fund_allocation_contract_no']; //资金调配合同编号
                if (!$request_data['is_kyriba'] && $payment_info['pay_type'] == 2) {
                    //直接出账，不用推kyriba
                    $payment_audit_table->pay_type  = 0;//是否通过kyriba付款-否
                }
                if (null === $payment_info['is_direct_billing'] || $payment_info['status'] == TbPurPaymentAuditModel::$status_kyriba_wait_receive) {
                    $payment_audit_table->is_direct_billing  = 1;//是否直接出账1-是
                }
                $res = $payment_audit_table->where(['id' => $payment_audit_id])->save();
                if (false === $res) {
                    throw new \Exception(L('确认失败'));
                }
                //分摊扣款和手续费金额，应付单和付款单状态同步（待优化）
                foreach ($pur_info as $value) {
                    //【本单分摊扣款金额】 = 本单【确认后-应付金额】 * 该付款单【扣款金额】 / （∑合并的应付单的【确认后-应付金额】）
                    $billing_amount = $value['amount_confirm'] * $data['billing_amount'] / $value['payable_amount_after'];
                    //【本单分摊手续费】 = 本单【确认后-应付金额】 * 该付款单【扣款手续费】 / （∑合并的应付单的【确认后-应付金额】）
                    $billing_fee = $value['amount_confirm'] * $data['billing_fee'] / $value['payable_amount_after'];
                    $save = [
                        'status'               => $status,
                        'amount_account'       => $billing_amount,
                        'expense'              => $billing_fee,
                        'exchange_tax_account' => $billing_exchange_rate,
                        'update_time'          => dateTime(),
                        'currency_account'     => $billing_currency_cd
                    ];
                    //同步状态、出账汇率
                    $save_res = $this->payment_table
                        ->where(['id' => $value['payment_id']])
                        ->save($save);
                    if (false === $save_res) {
                        throw new \Exception(L('分摊出账金额、应付单和付款单状态同步失败'));
                    }
                }
                break;
        }
    }

    public function sendPaidEmail($info,$html) {
        $prepared_by         = array_unique(array_column($info, 'prepared_by'));//收件人
        $procurement_numbers = array_unique(array_column($info, 'procurement_number'));
        $recipients = [];
        if (!empty($prepared_by)) {
            $recipients =array_map(function($value) {
                return $value.'@gshopper.com';
            }, $prepared_by);
        }
        $recipients = array_unique($recipients);
        if (empty($recipients)) {
            @SentinelModel::addAbnormal('付款单付款', '收件人异常', [$recipients,$info],'pur_notice');
            return false;
        }
        $payment_company_data         = array_unique(array_column($info, 'payment_company'));
        $cmn_data = CodeModel::getCodeArr($payment_company_data);
        $cc = array(); // 抄送人
        if ($cmn_data){
            //$cc = array_unique(array_column($cmn_data, 'ETC'));
            //兼容抄送人配置为两人以上,格式：XXXX@gshopper.com,YYYY@gshopper.com
            $cc = array_unique(explode(',', implode(',', array_column($cmn_data, 'ETC'))));
        }
        $cc = array_filter($cc);

        $prepared_by         = implode(',', $prepared_by);
        $procurement_numbers = implode(',', $procurement_numbers);
        $info = $info[0];
        $str_arr = [
            '{prepared_by}'         => $prepared_by,
            '{payment_audit_no}'    => $info['payment_audit_no'],
            '{procurement_number}'  => $procurement_numbers,
            '{amount_currency}'     => cdVal($info['payment_currency_cd']),
            '{amount_payable}'      => number_format($info['payable_amount_after'], 2),
            '{paid_currency}'       => cdVal($info['payment_currency_cd']),
            '{amount_paid}'         => number_format($info['status'] == TbPurPaymentAuditModel::$status_no_billing ? $info['payment_amount'] : $info['billing_amount'], 2),
            '{paid_date}'           => date('Y-m-d'),
            '{payment_submit_user}' => $info['status'] == TbPurPaymentAuditModel::$status_no_billing ? $info['payment_by'] : $info['billing_by'],
        ];
        $content     = strtr($html, $str_arr);
        $title       = "Payment order {$info['payment_audit_no']} paid notice:";
        $fileModel        = new FileDownloadModel();
        $file_attach = $info['status'] == TbPurPaymentAuditModel::$status_no_billing ? $info['payment_voucher'] : $info['billing_voucher'];
        $file_attach = json_decode($file_attach, true);
        foreach ($file_attach as $v) {
            if($v) {
                $fileModel->fname    = $v['save_name'];
                $file = $fileModel->getFilePath();
                if (is_file($file)) {
                    $attachment[] = $file;
                }
            }
        }
        Logs([], __FUNCTION__.'test2', 'kyriba');
        $email = new SMSEmail();
//        $cc_address     = M('cmn_cd','tb_ms_')->where(['CD'=>$info['payment_company']])->getField('ETC2');
        $res = $email->sendEmail($recipients,$title,$content,$cc,$attachment);
        if(!$res) {
            @SentinelModel::addAbnormal('付款单付款', '发送邮件异常', [$recipients,$title, $content, $attachment, $res],'pur_notice');
        }
        return $res;
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
            ->field('pa.*,ab.swift_code,ab.open_bank,ab.swift_code,pa.billing_currency_cd')
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
                'transferType'     => 'N001950100',
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
     * 批量同步采购订单供应商信息(同步的目的：采购单的供应商信息在其它模块有使用，不能废弃)
     * @param $payable_ids 应付单id集合
     * @param $request_data 更新数据
     * @throws Exception
     */
    private function updateOrderSupplierInfo($payable_ids, $request_data)
    {
        if ($request_data['payment_channel_cd'] != TbPurPaymentAuditModel::$channel_bank) {
            //非银行不需要更新
            return true;
        }
        $order_detail_ids = $this->model->table('tb_pur_payment pp')
            ->join('left join tb_pur_relevance_order rel on pp.relevance_id = rel.relevance_id')
            ->join('left join tb_pur_order_detail od on rel.order_id = od.order_id')
            ->where(['pp.id' => ['in', $payable_ids]])
            ->getField('od.order_id', true);
        $order_detail_ids = array_unique($order_detail_ids);
        $save = [
            'supplier_opening_bank'       => $request_data['supplier_opening_bank'],
            'supplier_collection_account' => $request_data['supplier_collection_account'],
            'supplier_card_number'        => $request_data['supplier_card_number'],
            'supplier_swift_code'         => $request_data['supplier_swift_code'],
        ];
        $save_supplier_res = $this->order_detail_table->where(['order_id'=>['in', $order_detail_ids]])->save($save);
        if (false === $save_supplier_res) throw new Exception(L('更新采购单收款信息失败'));
    }

    /**
     * 应付单撤回到待确认
     * @param $payment_info
     *  @param $accounting_return_reason 会计退回原因
     * @throws Exception
     * @throws Exception
     */
    public function cancelConfirm($payment_info, $accounting_return_reason = null, $supply_note = null) {
        $save['status']             = TbPurPaymentAuditModel::$status_no_confirmed;
        $save['amount_confirm']     = 0;
        $save['amount_difference']  = 0;
        $save['pay_remainder']      = 0;
        $save['next_pay_time']      = '';
        $save['difference_reason']  = '';
        $save['confirm_user']       = '';
        $save['confirm_time']       = '';
        $save['use_deduction']      = 0;
        $save['amount_deduction']   = 0;
        $save['deduction_detail_id']= null;
        $save['deduction_detail_compensation_id']= null;
        $save['remark_deduction']   = '';
        $save['voucher_deduction']  = '';
        $save['payment_audit_id']   = null;
        $save['payment_attachment'] = '';
        $save['confirm_remark']     = '';
        $save['update_time']        = dateTime();
        $save['accounting_return_reason'] = $accounting_return_reason;
        $save['supply_note'] = $supply_note;
        $res = $this->payment_table->where(['id'=>['in',$this->payment_ids]])->save($save);
        if (false === $res) {
            throw new \Exception(L('清空确认信息失败'));
        }
        $purService = new PurService($this->model);
        foreach ($payment_info as $value) {
            if (!$value['use_deduction']) {
                continue;
            }
            if ($value['deduction_detail_id']) {
                $purService->cancelDeductionAmount($value['deduction_detail_id']);
            }
            if ($value['deduction_detail_compensation_id']) {
                $ded_com_id_arr = [];
                $ded_com_id_arr = explode(',', $value['deduction_detail_compensation_id']);
                foreach ($ded_com_id_arr as $k => $v) {
                    $purService->cancelDeductionAmountCompensation($v);
                }
            }
        }
    }

    // #10287 1.先处理对于预付款拆分问题抵扣金触发相关的优化 
    public function fixOperHistDetail()
    {
        $where_map = [];
        $where_map['t.pid'] = array('neq', '0');
        $resList = $this->payment_table
        ->alias('t')
        ->field('pod.procurement_number,t.payment_no,t.use_deduction,t.amount_confirm,t.amount_deduction,t.pid,t.relevance_id,t.status,t.id')
        ->join('tb_pur_relevance_order pro on pro.relevance_id = t.relevance_id')
        ->join('tb_pur_order_detail pod on pod.order_id = pro.order_id')
        ->where($where_map)
        ->select();
        foreach ($resList as $key => $value) {
            $add_oper_res = $this->dealChildPaymentOperationData($value['pid'], $value['id']);
            if (false === $add_oper_res) {
                throw new Exception('补充子单“触发操作”和“适用条款”等信息新增失败');
                return false;
            }
        }
        return true;
        
    }


    public function checkHasFundOfEnd($data = [])
    {
        $newData = [];
        $purOperationModel = D('Scm/PurOperation');
        foreach ($data as $key => $value) {
            $checkHasFundOfEndFlag = false;
            $checkHasFundOfEndFlag = $purOperationModel->checkHasFundOfEnd($value['relevance_id']);
            if ($checkHasFundOfEndFlag) {
                $newData[] = $value;
            }
        }
        return $newData;
    }

    // 检测采购单是否符合条件
    public function checkHistPurOrder($data = [])
    {
        $newData = [];
        $purOperationModel = D('Scm/PurOperation');
        foreach ($data as $key => $value) {
            $checkHasNotCancelFlag = false;
            $checkHasFundOfEndFlag = false;
            $checkAfterCreateTimeFlag = false;
            $checkHasFundOfEndFlag = $purOperationModel->checkHasFundOfEnd($value['relevance_id']);
            if ($checkHasFundOfEndFlag) {
                $checkAfterCreateTimeFlag = $purOperationModel->checkAfterCreateTime($value['relevance_id']);
                if (!$checkAfterCreateTimeFlag) {
                    $checkHasNotCancelFlag = $purOperationModel->checkHasNotCancel($value['relevance_id']);
                }
            }
            
            // 无尾款，没有取消，9798上线前创建的采购单
            if ($checkHasNotCancelFlag && $checkHasFundOfEndFlag && !$checkAfterCreateTimeFlag) {
                $newData[] = $value;
            }
        }
        return $newData;
    }

    // 生成扣减抵扣金记录
    // 生成触发操作记录（该方法已暂时弃用）
    public function delPaymentData()
    {
        /*1.预付款付款撤回，原先无尾款的采购单，如果有撤回操作，则需要生成对应的应付记录，为了方便用户，直接转化为生成扣减抵扣金的记录
        2.根据tb_pur_payment_log的筛选条件operation_info为“撤回到待付款”的记录，查看payment_id对应的采购单是否符合处理数据的条件（无尾款，采购单创建时间为9798需求上线前，且采购单状态是进行中，而不是已取消）
        3.如果符合条件，则该payment_id对应的应付记录生成一条扣减抵扣金记录，金额则为该应付的确认后金额+抵扣金金额
        4.与产品沟通，考虑到有预付款撤回的情况，也就是说一条应付完成，有可能因为有预付款撤回而还需要生成多次抵扣金记录，而不是一条，需要根据日志操作记录表查看该应付单“确认出账”的记录，如果有多条则生成多条抵扣金记录*/

        // 获取“撤回到待付款”的记录
        /*$sql = "SELECT
    pp.payment_no,
    t.payment_id,
    pp.relevance_id,
    pp.use_deduction,
    pp.amount_deduction,
    pp.amount_confirm 
FROM
    tb_pur_payment_log t
    LEFT JOIN tb_pur_payment pp ON pp.id = t.payment_id 
    LEFT JOIN tb_pur_relevance_order pro ON pro.relevance_id = pp.relevance_id 
    LEFT JOIN tb_pur_operation po ON po.main_id = pp.id
WHERE
    t.operation_info = '撤回到待付款'
AND
    pro.order_status = 'N001320300'
AND 
    pro.prepared_time < '2019-12-30 14:55:00'
AND
    pro.prepared_time > '2019-08-12 14:55:00'
AND
    po.action_type_cd = 'N002870001'
";
        $data = M()->query($sql);
        // 筛选符合要求的应付单号
        $data = $this->checkHasFundOfEnd($data);

        // 生成扣减抵扣金记录和触发操作记录
        $logic      = D('Purchase/Payment','Logic');
        foreach ($data as $key => $value) {
            $amount_money = 0; $addData = [];
            $amount_money = $value['use_deduction'] ? $value['amount_deduction'] + $value['amount_confirm'] : $value['amount_confirm'];
            if ($amount_money == 0) { // 金额为0时，不需要生成抵扣金记录
                continue;
            }
            $addData['relevance_id'] = $value['relevance_id'];
            $addData['amount_deduction'] = $amount_money;
            $addData['remark_deduction'] = '历史异常应付处理';
            $res = $logic->cutUseDeduction($addData);
            if ($res === false) {
                $log_msg = [];
                $log_msg['value'] = $value;
                $log_msg['addData'] = $addData;
                Logs(json_encode($log_msg), __FUNCTION__.'----histfixdatabug1', 'tr');
                throw new Exception("操作一扣减抵扣金记录生成失败，应付单为{$value['payment_no']}");
            }

            // 根据应付id，生成触发操作记录tb_pur_operation
            $operationAddInfo = [];
            $operationAddInfo['main_id'] = $res;
            $operationAddInfo['clause_type'] = '9';
            $operationAddInfo['money_type'] = '2';
            $operationAddInfo['action_type_cd'] = 'N002870014';
            $operationAddInfo['bill_no'] = $value['payment_no'];
            $operationAddInfo['created_by'] = 'system';
$re = $this->add($operationAddInfo);
            if (!$re) {
                $report_res = [$addData, $type, $action_type_cd, $clause_type, $bill_no];
                ELog::add('采购触发操作生成抵扣/应付记录：'.json_encode($report_res).M()->getDbError(),ELog::ERR);
                $log_msg = [];
                $log_msg = $operationAddInfo;
                $log_msg['lastsql'] = M()->_sql();
                Logs(json_encode($log_msg), __FUNCTION__.'----histfixdatabug2', 'tr');
                throw new Exception("操作一生成会触发操作记录失败，应付单为{$value['payment_no']}");
            }
        }*/
    }

    public function dealPrePaymentData()
    {
        /*9.触发操作=预付款付款（应付单状态变为“已完成”）——对应未生成的抵扣金生成，预付款付款生成抵扣金记录
        10.根据“订单创建”类型找到对应的应付单号，循环查找
        11.如果该应付单号应付状态为已完成，则去触发操作记录中查找 
        12.筛选符合条件的采购单(查找所有的无尾款的采购单,未取消，9798上线前创建采购单)
        13.生成一条抵扣金记录，金额去该tb_pur_payment获取“确认后-本期应付金额amount_confirm” + ‘使用抵扣金金额amount_deduction’
        14.补充触发操作记录，并且抵扣金记录更新*/
        $sql = "SELECT
    pp.payment_no,
    pp.relevance_id,
    pp.use_deduction,
    pp.amount_deduction,
    pp.amount_confirm 
FROM
    tb_pur_operation po
    LEFT JOIN tb_pur_payment pp ON pp.id = po.main_id
    LEFT JOIN tb_pur_relevance_order pro ON pro.relevance_id = pp.relevance_id 
WHERE
    po.action_type_cd = 'N002870001' 
    AND pp.STATUS = 3 
    AND pro.order_status = 'N001320300' 
    AND pro.prepared_time < '2019-12-30 14:55:00' AND pro.prepared_time > '2019-08-12 14:55:00'
    ";
        $data = M()->query($sql);
        // 筛选符合要求的应付单号
        $data = $this->checkHasFundOfEnd($data);

        // 生成抵扣金记录和触发操作记录
        $logic      = D('Purchase/Payment','Logic');
        $purOperationModel = D('Scm/PurOperation');
        foreach ($data as $key => $value) {
            $amount_money = 0; $addData = [];
            $amount_money = $value['use_deduction'] ? $value['amount_deduction'] + $value['amount_confirm'] : $value['amount_confirm'];
            if ($amount_money == 0) { // 金额为0时，不需要生成抵扣金记录
                continue;
            }
            $addData['relevance_id'] = $value['relevance_id'];
            $addData['amount_deduction'] = $amount_money;
            $addData['remark_deduction'] = '历史异常抵扣金处理';
            $res = $logic->regardAsDeduction($addData);
            if ($res === false) {
                $log_msg = [];
                $log_msg['value'] = $value;
                $log_msg['addData'] = $addData;
                Logs(json_encode($log_msg), __FUNCTION__.'----histfixdatabug3', 'tr');
                throw new Exception("操作三抵扣金记录生成失败，应付单为{$value['payment_no']}");
            }

            // 根据应付id，生成触发操作记录tb_pur_operation
            $operationAddInfo = [];
            $operationAddInfo['main_id'] = $res;
            $operationAddInfo['clause_type'] = '9';
            $operationAddInfo['money_type'] = '2';
            $operationAddInfo['action_type_cd'] = 'N002870015';
            $operationAddInfo['bill_no'] = $value['payment_no'];
            $operationAddInfo['created_by'] = 'system';
            $re = $purOperationModel->add($operationAddInfo);
            if (!$re) {
                $report_res = [$addData, $type, $action_type_cd, $clause_type, $bill_no];
                ELog::add('采购触发操作生成抵扣/应付记录：'.json_encode($report_res).M()->getDbError(),ELog::ERR);
                $log_msg = [];
                $log_msg = $operationAddInfo;
                $log_msg['lastsql'] = M()->_sql();
                Logs(json_encode($log_msg), __FUNCTION__.'----histfixdatabug3', 'tr');
                throw new Exception("操作三生成触发操作记录失败，应付单为{$value['payment_no']}");
            }
        }

    }

    public function fixPurWarehouseData()
    {
        /*
        3.触发操作=采购入库确认（正品）——转换为生成扣减抵扣金
        4.根据excel数据，根据采购单号，循环处理，先将同一个入库编号的数据归到同一个数组中，作为处理的一个单元
        5.根据出入库编号后面的数字，即tb_wms_stream.id查询对应的金额变量，
        6.tb_wms_stream.unit_price_origin,//采购单价（采购币种，含增值税），tb_wms_stream.po_cost_origin//  PO内费用单价（采购币种），tb_wms_stream.currency_id // 采购币种，tb_wms_stream.send_num // 数量 
        7.同一个入库单号累加金额，如果最后金额为0则无需生成扣减抵扣金的记录
        8.数组格式为采购单->入库编号->流水表id记录，以入库编号为一条扣减抵扣金记录*/
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['file']['tmp_name'];
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        //读取Excel文件
        $PHPExcel = $PHPReader->load($filePath);
        //读取excel文件中的第一个工作表
        $sheet = $PHPExcel->getSheet(0);
        //取得最大的列号
        $allColumn = $sheet->getHighestColumn();
        //取得最大的行号
        $allRow = $sheet->getHighestRow();
        $data = $orders = $purchase_no_arr = [];
        for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
            $warehouse_no = ''; $goods_num = ''; $purchase_no = ''; 
            $warehouse_no = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
            $goods_num = trim((string)$PHPExcel->getActiveSheet()->getCell("B" . $currentRow)->getValue());
            $purchase_no = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());
            if ($warehouse_no && $purchase_no) {
                $exp_arr = [];
                $exp_arr = explode('_', $warehouse_no);
                $orders[$purchase_no][$exp_arr[0]][] = $exp_arr[1] . '_' . $goods_num;
                $purchase_no_arr[] = $purchase_no;
            }
        }


        $PurRepository = new PurRepository();
        $streamModel = M('wms_stream', 'tb_');
        $purOperationModel = D('Scm/PurOperation');
        $logic      = D('Purchase/Payment','Logic');
        // 去重
        $purchase_no_arr = array_unique($purchase_no_arr);
        // 获取采购relevance_id数组
        $where = [];
        $where['ro.order_status'] = 'N001320300'; // 采购状态进行中
        $where['ro.prepared_time'] = array('lt', $purOperationModel->START_TIME); // 订单创建时间小于9798上线时间
        $where['t.procurement_number'] = array('in', $purchase_no_arr);
        $purchase_no_arr = $PurRepository->getRelevanceIdByPurOrderNo($where);
        $purchase_no_arr = $this->checkHasFundOfEnd($purchase_no_arr);
        $purchase_no_arr = array_column($purchase_no_arr, 'relevance_id','procurement_number');
        foreach ($orders as $key => $value) {
            foreach ($value as $k => $v) {
                $amount_money_total = 0;
                foreach ($v as $kk => $vv) {
                    $stream_info = $vv_arr = [];
                    $vv_arr = explode('_', $vv);
                    if ($vv_arr[0]) {
                        $stream_info = $streamModel->where(['id' => $vv_arr[0]])->find();
                    }
                    if (!$stream_info) {
                        continue;
                    }
                    $amount_money_total = bcadd($amount_money_total, bcmul(bcadd($stream_info['unit_price_origin'], $stream_info['po_cost_origin'], 8), $vv_arr[1], 8), 2);

                    //tb_wms_stream.unit_price_origin,//采购单价（采购币种，含增值税），
                    //tb_wms_stream.po_cost_origin //  PO内费用单价（采购币种），
                    //tb_wms_stream.currency_id // 采购币种，
                    //tb_wms_stream.send_num // 数量 （因不准确弃用,用需求的附件excel的数量)
                    // 获取金额相加
                }

                // 金额到手 $k作为入库编号，生成扣减抵扣金记录
                if ($amount_money_total == 0) { // 金额为0时，不需要生成抵扣金记录
                    continue;
                }
                $addData = [];
                $addData['relevance_id'] = $purchase_no_arr[$key];
                $addData['amount_deduction'] = $amount_money_total;
                $addData['remark_deduction'] = '历史异常应付处理';
                $res = $logic->cutUseDeduction($addData);
                if ($res === false) {
                    $log_msg = [];
                    $log_msg['value'] = $v;
                    $log_msg['addData'] = $addData;
                    Logs(json_encode($log_msg), __FUNCTION__.'----histfixdatabug2', 'tr');
                    throw new Exception("操作二扣减抵扣金记录生成失败，采购单为{$key}");
                }

                // 根据应付id，生成触发操作记录tb_pur_operation
                $operationAddInfo = [];
                $operationAddInfo['main_id'] = $res;
                $operationAddInfo['clause_type'] = '12';
                $operationAddInfo['money_type'] = '2';
                $operationAddInfo['action_type_cd'] = 'N002870003';
                $operationAddInfo['bill_no'] = $k;
                $operationAddInfo['created_by'] = 'system';
                $re = $purOperationModel->add($operationAddInfo);
                if (!$re) {
                    $report_res = [$addData, $type, $action_type_cd, $clause_type, $bill_no];
                    ELog::add('采购触发操作生成抵扣/应付记录：'.json_encode($report_res).M()->getDbError(),ELog::ERR);
                    $log_msg = [];
                    $log_msg = $operationAddInfo;
                    $log_msg['lastsql'] = M()->_sql();
                    Logs(json_encode($log_msg), __FUNCTION__.'----histfixdatabug2', 'tr');
                    throw new Exception("操作二生成触发操作记录失败，采购单为{$key}，入库单据为{$k}");
                }
            }
        }

    }

    // 处理线上预付款付款撤回生成问题应付单（非订单创建应付单撤回）
    public function fixDeductionRecallData()
    {
        $main_arr = '12197,12196,12199,12200,12201,12202,12203,13007';
        //$main_arr = '13245,13246,13247,13248,13249,13251,13252';
        $main_arr = explode(',', $main_arr);
        $paymentModel = M('payment', 'tb_pur_');
        $operationModel = M('operation', 'tb_pur_');
        //都是应付，直接删除触发操作记录
        // 软删除应付单记录
        $pay_where['id'] = array('in', $main_arr);
        $pay_save['status'] = '5';
        $pay_save['deleted_by'] = 'system';
        $pay_save['deleted_at'] = dateTime();
        $opr_where['main_id'] = array('in', $main_arr);
        $opr_where['action_type_cd'] = 'N002870014';
        $pay_res = $paymentModel->where($pay_where)->save($pay_save);
        $opr_res = $operationModel->where($opr_where)->delete();
        if ($pay_res === false || $opr_res === false) {
            p($pay_res);
            p($opr_res);die;
        }
        echo 'success';
    }

    // 批量处理采购单中没有营业执照的采购单，需要在供应商表中新增记录
   public function batchDealHistoryPurOrder()
   {
        // 先获取采购单中，营业执照为空的，且供应商不为空，供应商名称(去重)
        // 根据该名称，去供应商表查询，没有则添加，有的话则继续
        $sql = "SELECT
    supplier_id
FROM
    `tb_pur_order_detail` 
WHERE
    (sp_charter_no = '' or sp_charter_no IS NULL)
    AND supplier_id IS NOT NULL 
        AND (supplier_new_id IS NULL OR supplier_new_id = '')
GROUP BY
    supplier_id";
           $res = M()->query($sql);
           // p($res);die;
           $crmSpSupplierModel = M('crm_sp_supplier','tb_');
           $purOrderDetailModel = M('pur_order_detail', 'tb_');
           $purRelevanceOrderModel = M('pur_relevance_order', 'tb_');
           $adminModel = M('admin', 'bbm_');

           foreach ($res as $key => $value) {
                if ($value['supplier_id']) {
                    $re = '';
                    $re = $crmSpSupplierModel->where(['SP_NAME' => $value['supplier_id']])->getField('ID');
                    if ($re) {
                        continue;
                    }
                    $addSupplierData = [];
                    $addSupplierData['SP_NAME'] = $value['supplier_id'];
                    $addSupplierData['COPANY_TYPE_CD'] = 'N001190800';
                    $addSupplierData['SP_CHARTER_NO_TYPE'] = '1';
                    $addSupplierData['SP_STATUS'] = '3';
                    $addSupplierData['DEL_FLAG'] = '1';
                    $supplier_id = $crmSpSupplierModel->add($addSupplierData);
                    if ($supplier_id === false) {
                        echo "供应商{$value['supplier_id']}新增失败";
                        echo "<br>";
                        throw new \Exception(L('生成供应商数据失败'));
                    }
                    // 根据采购单新增供应商id，到采购单中补充new_supplier_id
                    $pur_save = []; $pur_where = []; $pur_res = false;
                    $pur_where['supplier_id'] = $value['supplier_id'];
                    $pur_save['supplier_new_id'] = $supplier_id;
                    $pur_res = $purOrderDetailModel->where($pur_where)->save($pur_save);
                    if ($pur_res === false) {
                        throw new \Exception(L("生成{$value['supplier_id']}供应商采购单补充供应商id数据失败"));
                    }
                }
           }
            $res = '';
            $res = $crmSpSupplierModel->where(['SP_CHARTER_NO_TYPE' => '1'])->select();
            foreach ($res as $key => $value) {
                // 创建时间
                $order_id = ''; $create_time = ''; $create_name = '';
                $order_id = $purOrderDetailModel->where(['supplier_id' => $value['SP_NAME']])->min('order_id');
                $create_name = $purRelevanceOrderModel->where(['order_id' => $order_id])->getField('prepared_by');
                $search_name = 'huaming';
                if (strpos($create_name, '.') !== false) { // 部分采购单是以英文名如Weslis.Li存进，而不是花名
                    $search_name = 'M_NAME';
                }
                $saveData['CREATE_USER_ID'] = $adminModel->where([$search_name => $create_name])->getField('M_ID');
                $saveData['CREATE_TIME'] = $purRelevanceOrderModel->where(['order_id' => $order_id])->getField('prepared_time');
                $saveData['AUDIT_STATE'] = '3';
                $re = $crmSpSupplierModel->where(['ID' => $value['ID']])->save($saveData);
                if (false === $re) {
                    echo "供应商{$value['supplier_id']}更新失败";
                    echo "<br>";
                    throw new \Exception(L('生成供应商数据更新失败'));
                }
                unset($saveData);
            }
   }
    //#10380 历史异常应付、抵扣金处理
    public function fixPurHistPayDeduction()
    {
        set_time_limit(0);
        $this->batchDealHistoryPurOrder();
        // 处理历史预付款撤回问题数据
        // 操作一（处理有问题的预付款付款撤回应付记录，如果是扣减抵扣金记录需要还原抵扣金金）
        $this->fixDeductionRecallData();
        // 操作二
        $this->fixPurWarehouseData();
        // 操作三(该操作不可继续重复使用，因为上线后发现会跟需求#10287有重叠，会重复生成抵扣金记录，如果要用，需要排除已经生成抵扣金记录的应付)
        $this->dealPrePaymentData();


        // 操作一(需求变更，已弃用)
        //$this->delPaymentData();
    }

    // #10287 2.再处理修复抵扣金
    public function fixPurPayHistDetail()
    {
        $this->fixOperHistDetail();
        $where_map = [];
        $where_map['t.pid'] = array('neq', '0');
        $where_map['a.money_type'] = array('eq', '1');
        $resList = $this->payment_table
        ->alias('t')
        ->field('pod.procurement_number,t.payment_no,t.use_deduction,t.amount_confirm,t.amount_deduction,t.pid,t.relevance_id,t.status,t.id,a.action_type_cd,a.clause_type, a.bill_no')
        ->join('tb_pur_operation a on a.main_id = t.pid')
        ->join('tb_pur_relevance_order pro on pro.relevance_id = t.relevance_id')
        ->join('tb_pur_order_detail pod on pod.order_id = pro.order_id')
        ->where($where_map)
        ->select();
        $purClauseModel = M('clause', 'tb_pur_');
        $operModel = M('operation', 'tb_pur_');
        $logic      = D('Purchase/Payment','Logic');
        $error_msg = [];
        foreach ($resList as $key => $value) {
            if (!$value['relevance_id']) {
               throw new Exception('没找到该应付{$id}的关联id（relevance_id）');
               return false; 
            }

            if ($value['status'] == '3' && $value['action_type_cd'] === 'N002870001') {
                // 判断是否有预付款
                $id = '';
                $id = $purClauseModel->where(['clause_type' => '1', 'purchase_id'=> $value['relevance_id']])->getField('id');
                if ($id) {
                    $addData = [];
                    // 组成数组信息传入
                    // 生成抵扣金记录，且需要更新供应商抵扣金账户金额
                    $addData['amount_deduction'] =  $value['use_deduction'] ? $value['amount_confirm'] + $value['amount_deduction'] : $value['amount_confirm']; // 是否使用抵扣金
                    $addData['relevance_id'] = $value['relevance_id'];
                    $addData['remark_deduction'] = '';
                    if ($addData['amount_deduction'] <= 0) { // 金额为0时，不需要生成抵扣金记录
                      continue;
                    }
                    $res = ''; $operationAddInfo = [];
                    $res = $logic->regardAsDeduction($addData);
                    if ($res === false) {
                        $error_msg[] = $value['procurement_number'];
                        //throw new Exception('预付款付款生成抵扣金失败');
                    } else {
                        $operationAddInfo['main_id'] = $res;
                        $operationAddInfo['clause_type'] = '9';
                        $operationAddInfo['money_type'] = '2';
                        $operationAddInfo['action_type_cd'] = 'N002870015';
                        $operationAddInfo['bill_no'] = $value['payment_no'];
                        $operationAddInfo['created_by'] = DataModel::userNamePinyin();
                        $opr_res = $operModel->add($operationAddInfo);
                        if ($opr_res === false) {
                            throw new Exception('预付款付款生成操作记录失败');
                        }
                    }  
                }
            }
            
        }
        if ($error_msg) {
            p($error_msg);
            throw new Exception('预付款付款生成抵扣金失败');
            return false;
        }
        return true;
        //2.历史数据的应付拆分，都补充下“触发操作”和“适用条款”，数据跟原父应付单保持一致
            // 筛选pid不为空的应付记录数据的信息（付款状态，触发操作，适用条款，关联单号）且pid触发操作表有关联的
            // 补充新增该childid的触发操作记录

                // 3.历史数据的应付拆分中，符合条件的（该应付单状态为已完成，且父应付单记录触发操作=订单创建 & 采购单条款有预付款（根据tb_pur_relevance_order 和tb_pur_clause是否是预付款））的，
                        // 是的话，需要生成抵扣金记录，且需要更新供应商抵扣金账户金额    
    }

    /**
     * 采购应付金额确认
     * @param $request_data
     * @throws Exception
     */
    public function payableConfirm($request_data) {
        $data                           = $request_data['confirm'];
        $data_order                     = $request_data['order'];
        $payable_ids                    = $request_data['payable_ids'];
        $payment_audit                  = $request_data['payment_audit'];
        $compensation                   = $request_data['compensation'];

        $data['voucher_deduction']      = $data['voucher_deduction'];
        $data['confirm_time']           = dateTime();
        $data['confirm_user']           = $_SESSION['m_loginname'];
        $data['update_time']            = dateTime();
        $data['status']                 = TbPurPaymentAuditModel::$status_accounting_audit;
        if ($data['amount_deduction'] > 0) { // 改版后取消传递是否使用抵扣金，所以按提交的抵扣金总金额是否大于零来判断是否使用抵扣金
            $data['use_deduction'] = '1';
        }
        if($data['pay_remainder'] == 1)
            $data['amount_payable_split']   = $data['amount_confirm'] + $data['amount_deduction'];

        $payment_m = new TbPurPaymentModel();
        $payable   = $payment_m->lock(true)->where(['id'=>$data['id']])->find();
        if($payable['status'] != TbPurPaymentModel::$status['to_confirm']) {
            throw new Exception('状态异常');
        }
        //合并应付单
        $payment_audit_id = $this->mergePayableSubmit($payable_ids, array_merge($data_order, $payment_audit), $data, $payable);//合并付款

        if($data['use_deduction'] && $data['amount_deduction'] > 0) {
            
            if ($data['amount_deduction_account'] > 0) {
                $deduction_param             = array_merge($data, ['relevance_id' => $payable['relevance_id']]);
                $deduction_param['voucher_deduction'] = json_encode([["name"=>"","savename"=>""]]); // 余额扣减无需凭证
                $deduction_param['remark_deduction'] = ''; // 余额扣减无需备注
                $deduction_param['amount_deduction'] = $data['amount_deduction_account'];
                if (!$deduction_detail_id    = $this->useDeduction($deduction_param, false)) throw new Exception($this->error);
                $data['deduction_detail_id'] = $deduction_detail_id;
            }
            if ($data['amount_deduction_compensation'] > 0) {
                if (!$compensation) {
                    throw new Exception('缺失弹窗中具体采购单号和金额参数');
                }
                $compensation = json_decode($compensation, true);
                $deduction_param = $data;
                foreach ($compensation as $key => $value) {
                    if ($value['compensation_amount'] && $value['compensation_order_no']) {
                        $deduction_param['amount_deduction'] = $value['compensation_amount'];
                        $deduction_param['order_no'] = $value['compensation_order_no'];
                        ELog::add(['info'=>'应付单请求参数','request_data'=>$deduction_param]);
                        if (!$deduction_detail_compensation_id    = $this->useDeductionCompensation($deduction_param, false)) throw new Exception($this->error);
                        $data['deduction_detail_compensation_id'] .= $deduction_detail_compensation_id . ','; 
                    } else {
                        throw new Exception('弹窗中具体采购单号和金额参数为空，请确认');
                    }
                }
                $data['deduction_detail_compensation_id'] = rtrim($data['deduction_detail_compensation_id'], ',');
            }
            ELog::add(['info'=>'应付单使用抵扣金','deduction_detail_id'=>$deduction_detail_id, 'deduction_detail_compensation_id' => $data['deduction_detail_compensation_id'], 'sql'=>M()->getLastSql(),'request_data'=>$request_data]);
        }
        if(!$payment_m->create($data) || !$payment_m->save()) {
            throw new Exception('应付保存失败');
        }
        $relevance = M('relevance_order','tb_pur_')->where(['relevance_id'=>$payable['relevance_id']])->find();
        if($relevance['order_status'] == 'N001320500') {
            throw new Exception('采购订单已取消');
        }
        $data_order['order_id'] = $relevance['order_id'];
        $res_order              = M('order_detail','tb_pur_')->save($data_order);

        if($data['amount_difference'] > 0 && $data['pay_remainder'] == 1) {
            $add_payment['payment_no']              = $payment_m->createPaymentNO();
            $add_payment['pid']                     = $data['id'];
            $add_payment['relevance_id']            = $payable['relevance_id'];
            $add_payment['payment_period']          = $payable['payment_period'];
            $add_payment['amount']                  = $payable['amount'];
            $add_payment['amount_payable']          = $payable['amount_payable'];
            $add_payment['amount_payable_split']    = $data['amount_difference'];
            $add_payment['payable_date']            = $data['next_pay_time'];
            $add_payment['create_time']             = date('Y-m-d H:i:s');
            $add_payment['update_time']             = date('Y-m-d H:i:s');
            $res_add_payment                        = $payment_m->add($add_payment);
            if(!$res_add_payment) {
                throw new Exception('创建新应付失败');
            }
            // 根据父应付单的数据（id），子应付单新增补充“触发操作”和“适用条款”
            $add_oper_res = $this->dealChildPaymentOperationData($data['id'], $res_add_payment);
            if (false === $add_oper_res) {
                throw new Exception('补充子单“触发操作”和“适用条款”等信息新增失败');
            }
        }

        if($res_order !== false) {
            $date = dateTime();
            $status_name   = TbPurPaymentAuditModel::$status_map[$data['status']];
            TbPurPaymentLogModel::recordLog($data['id'], '确认应付', $date, $status_name);
            TbPurPaymentAuditLogModel::recordLog($payment_audit_id, '创建付款单', $date, $status_name);
            TbPurActionLogModel::recordLog($payable['relevance_id'], '确认应付', $date);
            if ($payable_ids) {
                $relevance_ids = $this->payment_table->where(['id'=>['in',$payable_ids]])->getField('relevance_id',true);
                TbPurPaymentLogModel::recordLog($payable_ids, '应付单被合并', $date, $status_name);
                TbPurActionLogModel::recordLog($relevance_ids, '应付单被合并', $date);
            }
        }else {
            throw new Exception('采购单状态保存失败');
        }
        return $payment_audit_id;
    }


    /**
     * 采购应付推送企业微信消息
     * @param $id 划转记录id
     * @param $send_email 要推送的账号
     */
    public function purPaymentWechatApproval($payment_audit_id,$send_email) {
        $send_data = $this->assembleParames($payment_audit_id);
        $send_user = $send_data['base_info']['current_audit_user'];
        $send_email    = [$send_user . '@gshopper.com'];
        $wx_return_res = (new ReviewMsgTpl())->sendWeChatGeneralApproval($send_data, $send_email, 'payment_message_notice');
        Logs([$send_data, $wx_return_res], __FUNCTION__, __CLASS__);
    }

    public function assembleParames($payment_audit_id){
        $db_res = (new GeneralPaymentService())->getGeneralPayment($payment_audit_id);
        $payment_audit = $db_res['payment_audit'];
        $general_payment = $db_res['general_payment'];
        $payment_audit['payment_nature'] = $general_payment['payment_nature'];
        $payment_audit['supplier'] = $general_payment['supplier'];
        $payment_audit['contract_information'] = $general_payment['contract_information'];
        $payment_audit['contract_no'] = $general_payment['contract_no'];
        $payment_audit['contract_name'] = $general_payment['contract_name'];
        $payment_audit['settlement_type'] = $general_payment['settlement_type'];
        $payment_audit['procurement_nature'] = $general_payment['procurement_nature'];
        $payment_audit['invoice_information'] = $general_payment['invoice_information'];
        $payment_audit['invoice_type'] = $general_payment['invoice_type'];
        $payment_audit['bill_information'] = $general_payment['bill_information'];
        $payment_audit['payment_type'] = $general_payment['payment_type'];
        $payment_audit['commission_type'] = $general_payment['commission_type'];
        $payment_audit['actual_fee_applicant'] = $general_payment['actual_fee_applicant'];
        $payment_audit['actual_fee_Department'] = $general_payment['actual_fee_Department'];
        $payment_audit['payment_remark'] = $general_payment['payment_remark'];
        $payment_audit['payable_amount'] = sprintf("%.2f", $payment_audit['payable_amount_after']);//保留两位小数
        $payment_audit = CodeModel::autoCodeOneVal($payment_audit, ['settlement_type','trade_type_cd','source_cd','our_company_cd', 'procurement_nature', 'invoice_information', 'invoice_type', 'bill_information', 'commission_type', 'our_company_cd', 'payment_type', 'payment_channel_cd', 'payment_way_cd', 'payable_currency_cd'], 'all');

        if ($payment_audit['source_cd'] == TbPurPaymentAuditModel::$source_payable){
            $payment = $this->payment_table->field("payment_no,remark")->where(array('payment_audit_id'=>$payment_audit['id']))->find();
            $general_payment_detail_temp['project_summary'] = $payment['payment_no']; // Payment Key的值
        }else{
            $payment = $this->transfer_table->field("transfer_no ,`use` AS remark ")->where(array('payment_audit_id'=>$payment_audit['id']))->find();
            $general_payment_detail_temp['project_summary'] = $payment['transfer_no'];  // Payment Key的值
        }

        $general_payment_detail_temp['subdivision_type_val'] = $payment_audit['payable_currency_cd_val'];  // 币种
        $general_payment_detail_temp['actual_fee_Department'] = $payment_audit['payable_amount'];  // 确认后-应付金额
        if (empty($payment_audit['trade_type_cd']) && empty($payment['remark'])){
            $general_payment_detail_temp['subtotal'] = "";  // 【差异原因】|【提交付款备注】
        }else{
            if (!empty($payment_audit['trade_type_cd'])){
                $general_payment_detail_temp['subtotal'] = $payment_audit['trade_type_cd'];
            }
            if (!empty($payment['remark'])){
                $general_payment_detail_temp['subtotal'] = $payment['remark'];
            }
            if (!empty($payment_audit['remark']) && !empty($payment['remark'])){
                $general_payment_detail_temp['subtotal'] = $payment_audit['trade_type_cd']."|".$payment['remark'];  // 【差异原因】|【提交付款备注】
            }
        }
        $payment_audit = DataModel::formatAmount($payment_audit);
        $general_payment_detail[0] = $general_payment_detail_temp;
        return $data = [
            'base_info' => $payment_audit,
            'detail_info' => $general_payment_detail,
        ];
    }

    public function checkOperInfo($pid)
    {
        $oper_info = [];
        $oper_info = M('operation', 'tb_pur_')->field('action_type_cd,bill_no,clause_type')->where(['main_id'=> $pid, 'money_type' => '1'])->find();
        if (!$oper_info) {
            $ppid = '';
            $ppid = M('payment', 'tb_pur_')->where(['id' => $pid])->getField('pid');
            if ($ppid == 0) {
                return true;
            }
            return $this->checkOperInfo($ppid);
        }
        return $oper_info;
        
    }
    // 复制父应付记录的触发操作，适用条款
    public function dealChildPaymentOperationData($pid, $childid)
    {
        // 两个id不能为空
        if (!$pid || !$childid) {
            throw new Exception("生成子应付记录触发操作失败：父id：{$pid}或子id：{$childid}不可为空");
            return false;
        }
        $oper_info = []; $oper_child_info = []; $addData = [];
        // 获取pid对应的记录数据，如果没有，且应付单父id为0，符合正常，表明是旧数据无需处理，否则需要往上查询
        $oper_info = $this->checkOperInfo($pid);
        if ($oper_info === true) {
            return true;
        }
        // 校验child是否已经存在，如果存在无需再新增
        $oper_child_info = M('operation', 'tb_pur_')->field('id')->where(['main_id'=> $childid, 'money_type' => '1', 'action_type_cd' => $oper_info['action_type_cd']])->find();
        if ($oper_child_info) {
            //throw new Exception("子应付记录id:{$childid}的触发操作记录已存在，操作记录id为{$oper_child_info['id']}");
            return true;
        }
        $addData = [
            'main_id' => $childid,
            'money_type' => '1',
            'action_type_cd' => $oper_info['action_type_cd'],
            'bill_no' => $oper_info['bill_no'],
            'created_by' => $_SESSION['m_loginname'],
            'created_at' => date('Y-m-d H:i:s'), 
            'clause_type' => $oper_info['clause_type']
        ];
        // 新增保存child的记录数据
        $res = M('operation', 'tb_pur_')->add($addData);
        return $res;
    }


    // 使用抵扣金-赔偿返利金
    public function useDeductionCompensation($param) {
        try {
            $model = new Model();
            $model->startTrans();
            $where['t.relevance_id'] = $param['relevance_id']; // 默认用relevance_id来获取采购单信息
            if (!$param['relevance_id'] && $param['order_no']) { // 没有relevance_id时，用订单号来获取采购单相关信息
                $where = [];
                $where['a.procurement_number'] = $param['order_no'];
            }
            $order = $model
                ->table('tb_pur_relevance_order')
                ->alias('t')
                ->field('supplier_id,supplier_id_en,sp_charter_no,our_company,amount_currency,procurement_number,supplier_new_id')
                ->join('tb_pur_order_detail a on a.order_id=t.order_id')
                ->where($where)
                ->find();
            $deduction_param = [
                'our_company_cd'        => $order['our_company'],
                'deduction_currency_cd' => $order['amount_currency'],
                'deduction_amount'      => $param['amount_deduction'],
                'remark'                => $param['remark_deduction'],
                'order_no'              => $order['procurement_number'],
                'turnover_type'         => 1,
                'deduction_voucher'     => json_decode($param['voucher_deduction'], true),
                'supplier_new_id'       => $order['supplier_new_id'] ? $order['supplier_new_id'] : $param['supplier_new_id'],
            ];
            //采购退款认领参数增加抵扣类型
            if (isset($param['deduction_type_cd']) && !empty($param['deduction_type_cd'])) {
                $deduction_param['deduction_type_cd'] = $param['deduction_type_cd'];
            }
            $deduction_detail_id = (new PurService())->addDeductionAmountCompensation($deduction_param, $model);
            ELog::add('使用抵扣金'.$deduction_param,ELog::INFO);
            $model->commit();
            return $deduction_detail_id;
        } catch (Exception $exception) {
            $model->rollback();
            $this->error = $exception->getMessage();
            ELog::add(['info'=>'使用抵扣金失败：'.$exception->getMessage(),'request'=>json_encode($deduction_param)],ELog::ERR);
            return false;
        }
    }

    /**
     * 使用抵扣金
     * @param $param
     * @param $allow_less_than_zero 是否允许扣减抵扣金小于0，默认不允许
     * @return bool|mixed
     */
    public function useDeduction($param, $allow_less_than_zero = false) {
        try {
            $model = new Model();
            $model->startTrans();
            $order = $model
                ->table('tb_pur_relevance_order')
                ->alias('t')
                ->field('supplier_id,supplier_id_en,sp_charter_no,our_company,amount_currency,procurement_number,supplier_new_id')
                ->join('tb_pur_order_detail a on a.order_id=t.order_id')
                ->where(['t.relevance_id'=>$param['relevance_id']])
                ->find();
            $deduction_param = [
                'sp_charter_no'         => $order['sp_charter_no'],
                'supplier_name_cn'      => $order['supplier_id'],
                'supplier_name_en'      => $order['supplier_id_en'],
                'our_company_cd'        => $order['our_company'],
                'our_company_name'      => cdVal($order['our_company']),
                'deduction_currency_cd' => $order['amount_currency'],
                'deduction_amount'      => $param['amount_deduction'],
                'remark'                => $param['remark_deduction'],
                'order_no'              => $order['procurement_number'],
                'turnover_type'         => 1,
                'deduction_voucher'     => json_decode($param['voucher_deduction'], true),
                'supplier_new_id'       => $order['supplier_new_id'] ? $order['supplier_new_id'] : $param['supplier_new_id'],
            ];
            //采购退款认领参数增加抵扣类型
            if (isset($param['deduction_type_cd']) && !empty($param['deduction_type_cd'])) {
                $deduction_param['deduction_type_cd'] = $param['deduction_type_cd'];
            }
            $deduction_detail_id = (new PurService())->addDeductionAmount($deduction_param, $model, $allow_less_than_zero);
            ELog::add('使用抵扣金'.$deduction_param,ELog::INFO);
            $model->commit();
            return $deduction_detail_id;
        } catch (Exception $exception) {
            $model->rollback();
            $this->error = $exception->getMessage();
            ELog::add(['info'=>'使用抵扣金失败：'.$exception->getMessage(),'request'=>json_encode($deduction_param)],ELog::ERR);
            return false;
        }
    }

    /**
     * 付款单撤回到待付款
     * @param $payment_audit_id 付款单id
     * @param string $reason
     * @param string $is_payment_return 用于判断是否需要删除日记账
     * @throws Exception
     */
    public function returnToPaymentConfirm($payment_audit_id, $reason = '', $is_payment_return = 0) {
        $this->payment_audit_id = $payment_audit_id;
        $payment_audit_info = $this->payment_audit_table->lock(true)->find($payment_audit_id);
        if(!in_array($payment_audit_info['status'],[
            TbPurPaymentAuditModel::$status_no_billing,
            TbPurPaymentAuditModel::$status_finished,
            TbPurPaymentAuditModel::$status_kyriba_not_pass,
            TbPurPaymentAuditModel::$status_payment_failed,
            TbPurPaymentAuditModel::$status_kyriba_receive_failed,
        ])) {
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
        if($payment_audit_info['status'] == TbPurPaymentModel::$status['complete']) {
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
                (new TbWmsAccountTurnoverModel())->deletePurTurnover($payment_audit_info['payment_audit_no']);
            }
        }
        $res_payment = $this->payment_audit_table->where(['id'=>$payment_audit_id])->save($save);
        if(!$res_payment) {
            throw new \Exception(L('保存失败'));
        }

        $this->emptyPaymentData($payment_audit_id, $status);//应付单数据清空

        $payment_info  = $this->payment_table->where(['payment_audit_id'=>$payment_audit_id])->select();
        $this->setParameters($payment_info, $status);

        $relevance_l = D('Purchase/Relevance','Logic');
        foreach ($payment_info as $payment) {
            $res_relevance = $relevance_l->flashPaymentStatus($payment['relevance_id']);
            if (!$res_relevance) {
                throw new \Exception(L($relevance_l->getError()));
            }
        }
        $this->returnToPaymentConfirmEmail($payment_audit_info, $reason);
        $operationModel = M('operation','tb_pur_');
        foreach ($payment_info as $payment) {
            $op_re = [];
            // 只有属于预付款的应付单才需要生成扣减抵扣金记录
            if ($payment['id']) {
                $op_re = $operationModel->where(['main_id' => $payment['id'], 'money_type' => '1', 'action_type_cd' => 'N002870001'])->find();
                if ($op_re) {
                    // 生成扣减抵扣金记录
                    $addDataInfo                   = [];
                    $addDataInfo['clause_type']    = '6';
                    $addDataInfo['class']          = __CLASS__;
                    $addDataInfo['function']       = __FUNCTION__;
                    $addDataInfo['payable_date']   = $payment_audit_info['payable_date_after'];
                    $amount_payable                = $payment['use_deduction'] ? $payment['amount_confirm'] + $payment['amount_deduction'] : $payment['amount_confirm']; //确认后应付金额 + （是否使用抵扣金）
                    $addDataInfo['amount_payable'] = $amount_payable;

                    $pu_res = D('Scm/PurOperation')->DealTriggerOperation($addDataInfo, '1', 'N002870014', $payment['relevance_id'], $payment['payment_no']);
                    if (!$pu_res) {
                        throw new \Exception(L('预付款-扣减抵扣金失败'));
                    }
                }
            } 
        }
        $this->operation_info = '撤回到待付款';
        $this->recordLog();
    }

    /**
     * 付款单撤回邮件发送
     * @param $payment_audit_info 付款单信息
     * @param $relevance_ids
     * @param $reason
     * @return bool
     */
    public function returnToPaymentConfirmEmail($payment_audit_info, $reason) {
        $to                     = D('Purchase/Relevance','Logic')->getPurchaserEmail($this->relevance_ids);
        $cc                     = C('finance_email');
        $tittle                 = "付款单：{$payment_audit_info['payment_audit_no']}付款已撤回";
        $return_time            = date('Y-m-d H:i:s');
        $payment_submit_time    = $payment_audit_info['payment_at'] ? : $payment_audit_info['billing_at'];
        $content                = <<<EOF
付款单：{$payment_audit_info['payment_audit_no']}付款已撤回<br/>
付款操作时间：{$payment_submit_time}<br/>
撤回操作时间：$return_time<br/>
撤回操作人：{$_SESSION['m_loginname']}<br/>
撤回原因：{$reason}<br/>
EOF;
        $email_m    = new SMSEmail();
        $res        = $email_m->sendEmail($to, $tittle, $content, $cc);
        if (!$res) {
            @SentinelModel::addAbnormal('采购付款撤回', '发送邮件异常', [$payment_audit_info,$this->relevance_ids,$to,$content,$res],'pur_notice');
        }
        return $res;
    }

    /**
     * 清空应付单数据
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
            throw new \Exception(L('应付单和付款单状态同步失败'));
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
     * 获取付款单日志记录
     * @param $request_data
     * @return array
     */
    public function getPaymentBillLog($request_data)
    {
        $model = new TbPurPaymentAuditLogModel();
        $search_map = [
            'payment_audit_id' => 'payment_audit_id'
        ];
        $search_type['all'] = true;
        list($where, $limit)   = WhereModel::joinSearchTemp($request_data,$search_map, '', $search_type);
        $pages['total']        = $model->where($where)->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        $db_res = $model->where($where)->limit($limit[0], $limit[1])->order('created_at desc, id desc')->select();
        return [
            'data' => $db_res,
            'pages' => $pages
        ];
    }

    //预付款是否生成抵扣金/应付，弹框数据
    public function getPrePaymentInfo($request_data)
    {
        $payment_audit_id = $request_data['payment_audit_id'];
        $money_type       = $request_data['money_type'];
        $action_type_cd   = $request_data['action_type_cd'];
        $purService       = new PurService();
        $data             = [];
        $pur_info = $this->payment_table->field('id')->where(['payment_audit_id'=>$payment_audit_id])->select();
        foreach ($pur_info as $v) {
            $request = [
                'action_type_cd' => $action_type_cd,
                'money_type'     => $money_type,
                'money_id'       => $v['id'],
            ];
            switch ($money_type) {
                case '2':
                    //生成抵扣金
                    $result = $purService->getOperationAmount($request);
                    if (!empty($result['pre_pay_info'])) {
                        $data['deduction'][] = [
                            'currency_cd_val' => $result['pre_pay_info']['currency_type'],
                            'amount'          => $result['pre_pay_info']['amount'],
                        ];
                    }
                    if (!empty($result['end_pay_info'])) {
                        $data['deduction'][] = [
                            'currency_cd_val' => $result['end_pay_info']['currency_type'],
                            'amount'          => $result['end_pay_info']['amount'],
                        ];
                    }
                    break;
                case '1':
                    //生成应付
                    $result = $purService->getOperationAmount($request);
                    if (!empty($result['pre_pay_info'])) {
                        $data['payable'][] = [
                            'currency_cd_val' => $result['pre_pay_info']['currency_type'],
                            'amount'          => $result['pre_pay_info']['amount'],
                        ];
                    }
                    if (!empty($result['end_pay_info'])) {
                        $data['payable'][] = [
                            'currency_cd_val' => $result['end_pay_info']['currency_type'],
                            'amount'          => $result['end_pay_info']['amount'],
                        ];
                    }
                    break;
            }
        }
        return $data;
    }

    /**
     * 会计审核
     * @param $request_data
     * @throws Exception
     */
    public function accountingAudit($request_data) {
        $this->payment_audit_id   = $request_data['payment_audit_id'];
        $status                   = $request_data['status'];
        $is_return                = $request_data['is_return'];
        $accounting_return_reason = $request_data['accounting_return_reason'];
        $supply_note              = $request_data['supply_note'];//补充说明

        $payment_audit_info = $this->payment_audit_table->lock(true)->find($this->payment_audit_id);
        if ($payment_audit_info['status'] != TbPurPaymentAuditModel::$status_accounting_audit) {
            throw new \Exception(L('付款状态已更新'));
        }
        if (!in_array($status, [TbPurPaymentAuditModel::$status_no_payment,TbPurPaymentAuditModel::$status_no_confirmed])) {
            throw new \Exception(L('状态异常'));
        }
        $res = $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save([
            'status'                => empty($status) ? TbPurPaymentAuditModel::$status_deleted : $status,
            'updated_by'            => $this->user_name,
        ]);
        if (!$res) throw new \Exception(L('会计审核失败'));

        $payment_info = $this->payment_table
            ->field('id,payment_no,use_deduction,deduction_detail_id,relevance_id,deduction_detail_compensation_id')
            ->where(['payment_audit_id'=>$this->payment_audit_id])
            ->select();
        if(empty($payment_info)) throw new \Exception(L('未找到应付单'));
        //日志记录参数
        $payment_no_str      = implode(',', array_column($payment_info, 'payment_no'));
        $this->setParameters($payment_info, empty($status) ? TbPurPaymentAuditModel::$status_deleted : $status);
        if ($status == TbPurPaymentAuditModel::$status_no_confirmed) {
            $this->cancelConfirm($payment_info, $accounting_return_reason, $supply_note);//应付单撤回到待确认
        } else {
            $this->updatePayableBillStatus($status);//更新应付单状态
        }
        if ($is_return) {
            $this->operation_info = '会计审核退回';
            $this->remark = $payment_no_str;
        } else if ($status == TbPurPaymentAuditModel::$status_no_payment ) {
            $this->operation_info = '会计审核通过';
        } else {
            $this->operation_info = '会计审核撤回';
            $this->remark = $payment_no_str;
        }
        $this->recordLog();
    }

    /**
     * 撤回到待审核
     * @param $request_data
     * @throws Exception
     */
    public function returnToAccountingAudit($request_data) {
        $this->payment_audit_id = $request_data['payment_audit_id'];
        $payment_info = $this->payment_table->field('id,relevance_id')->where(['payment_audit_id'=>$this->payment_audit_id])->select();
        $this->setParameters($payment_info, TbPurPaymentAuditModel::$status_accounting_audit);
        $this->updatePayableBillStatus(TbPurPaymentAuditModel::$status_accounting_audit);//更新应付单状态
        $this->updatePaymentBillStatus(TbPurPaymentAuditModel::$status_accounting_audit);//更新付款单状态
        $this->operation_info = '撤回到待审核';
        $this->recordLog();
    }

    /**
     * 更新应付单状态
     * @param $status
     * @throws Exception
     */
    public function updatePayableBillStatus($status)
    {
        $res = $this->payment_table->where(['payment_audit_id'=>['in',$this->payment_audit_id]])->save(['status'=>$status]);
        if (!$res) throw new \Exception(L('更新应付单状态失败'));
    }

    /**
     * 更新付款单状态
     * @param $status
     * @throws Exception
     */
    public function updatePaymentBillStatus($status)
    {
        $res = $this->payment_audit_table->where(['id'=>['in',$this->payment_audit_id]])->save(['status'=>$status]);
        if (!$res) throw new \Exception(L('更新付款单状态失败'));
    }

    /**
     *设置参数
     * @param $payment_info
     * @param $status
     */
    public function setParameters($payment_info, $status)
    {
        $this->payment_ids   = array_unique(array_column($payment_info, 'id'));
        $this->relevance_ids = array_unique(array_column($payment_info, 'relevance_id'));
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
        TbPurPaymentLogModel::recordLog($this->payment_ids, $this->operation_info, $date, $status_name, $this->remark);
        TbPurActionLogModel::recordLog($this->relevance_ids, $this->operation_info, $date);
    }

    /**
     * 获取应付单列表/导出数据
     * @param $where
     * @param bool $is_excel
     * @return array
     */
    public function getPayableList($where, $is_excel = false)
    {
        list($list, $count, $show) =  $this->repository->getPayableList($where, $is_excel);
        $list = CodeModel::autoCodeTwoVal($list, [
            'purchase_type','payment_company','our_company',
            'online_purchase_website','sell_team','payment_channel_cd',
            'payment_way_cd','platform_cd','payment_currency_cd',
            'billing_currency_cd','amount_currency','difference_reason'
        ]);
        $list = (new PurService())->orderStatusToVal($list);
        foreach ($list as &$item) {
            $item['next_pay_time']            = strtotime($item['next_pay_time']) <= 0 ? null : $item['next_pay_time'];
            $item['updated_at']               = strtotime($item['updated_at']) <= 0 ? null : $item['updated_at'];
            $item['update_time']              = strtotime($item['update_time']) <= 0 ? null : $item['update_time'];
            $item['voucher_deduction']        = implode(',', array_column(json_decode($item['voucher_deduction'], true), 'name'));
            $item['use_deduction']            = $item['payable_status'] > TbPurPaymentAuditModel::$status_no_confirmed ? $item['use_deduction'] ? '是' : '否' : '';
            $item['pay_remainder']            = $item['pay_remainder'] ? $item['pay_remainder'] == 1 : '继续支付' ? '放弃支付，本次结束' : '不需要支付';
            $item['amount_payable_ori']       = $item['amount_payable'];
            $item['amount_payable_split_ori'] = $item['amount_payable_split'];
            $item['amount_payable']           = $item['amount_currency_val'] . ' ' . $item['amount_payable'];
            $item['amount_payable_split']     = $item['amount_currency_val'] . ' ' . $item['amount_payable_split'];
            $item['payable_amount_after']     = $item['payable_status'] > TbPurPaymentAuditModel::$status_no_confirmed ? $item['amount_currency_val'] . ' ' . $item['payable_amount_after'] : '';
            $item['amount_confirm']           = $item['payable_status'] > TbPurPaymentAuditModel::$status_no_confirmed ? $item['amount_currency_val'] . ' ' . $item['amount_confirm'] : '';
            $item['total_amount']             = $item['amount_currency_val'] . ' ' . $item['total_amount'];
            if ($item['payable_status'] < TbPurPaymentAuditModel::$status_finished) {
                $item['amount_account']       = null;
                $item['expense']              = null;
                $item['billing_total_amount'] = null;
                $item['billing_date']         = null;
            }
            if ($item['payable_status'] < TbPurPaymentAuditModel::$status_no_billing) {
                $item['amount_paid'] = null;
            }
            if ($item['payable_status'] < TbPurPaymentAuditModel::$status_no_payment) {
                $item['amount_deduction']  = null;
                $item['lave_amount']       = null;
                $item['amount_difference'] = null;
                $item['pay_remainder']     = null;
                $item['amount_difference'] = null;
                $item['next_pay_time']     = null;
            }
            $item['difference_reason'] = $item['difference_reason_val'] ? : $item['difference_reason'];
            $item['amount_difference'] = $item['amount_difference'] > 0 ? $item['amount_difference'] : '';
        }
        $list = $this->repository->getPayableExtraInfo($list);
        return [$list, $count, $show];
    }

    /**
     * 批量核销出账
     * @param $request_data
     * @throws Exception
     */
    public function batchPaymentSubmit($request_data)
    {
        $this->payment_audit_id = array_column($request_data, 'id');
        $this->batchUpdatePaymentBill($request_data);
        $pur_info = $this->repository->getOrderInfoByPaymentAuditIds($this->payment_audit_id);//获取更新后的采购相关信息

        //付款核销选择已出账或待出账出账更新各个采购单状态
        $order_ids = [];
        foreach ($pur_info as $v) {
            if (isset($order_ids[$v['order_id']])) {
                //同一采购单不用重复更新状态
                continue;
            }
            $order_ids[$v['order_id']] = true;
            if (false === $this->updatePurRelevanceOrderPaymentStatus($v)) {
                throw new \Exception(L('更新采购单付款状态失败'));
            }
        }
        foreach ($request_data as &$item) {
            $item['payment_audit_id'] = $item['id'];
            unset($item['id']);
            if (!$turnover_id = $this->thrTurnOver($item)) {
                throw new \Exception(L('添加日记账失败'));
            }
            $rel_res = (new TbFinClaimModel())->addPurToTurnoverRelation($turnover_id, $pur_info);
            if (!$rel_res) {
                throw new \Exception(L('添加日记账关联失败'));
            }
        }
        $this->createDeduction($pur_info);

        $this->operation_info = '确认出账';
        $this->setParameters($pur_info, TbPurPaymentAuditModel::$status_finished);
        $this->recordLog();

        $info = [];
        foreach ($pur_info as $item) {
            $info[$item['order_id']][] = $item;
        }
        RedisModel::client()->set('pur_batch_send_email', json_encode($info));
    }

    /**
     * 批量分摊金额计算及付款单更新
     * @param $data
     * @throws Exception
     */
    private function batchUpdatePaymentBill($data)
    {
        $excel_model         = new TempImportExcelModel();
        $payment_audit_model = new TbPurPaymentAuditModel();
        $payment_model       = new TbPurPaymentModel();
        $payment_audit_id    = $this->payment_audit_id;
        $info                = $this->repository->getOrderInfoByPaymentAuditIds($payment_audit_id);//获取采购相关信息
        if (empty($info)) {
            throw new \Exception(L('查找采购关联数据失败'));
        }
        $pur_info            = array_combine(array_column($info, 'payment_audit_id'), $info);

        //出账
        $status = TbPurPaymentAuditModel::$status_finished;
        foreach ($data as &$item) {
            $billing_exchange_rate         = exchangeRateConversion(cdVal($item['billing_currency_cd']), cdVal($pur_info[$item['id']]['amount_currency']), date('Ymd'));
            $item['billing_at']            = dateTime();
            $item['billing_by']            = $this->user_name;
            $item['status']                = $status;
            $item['billing_exchange_rate'] = $billing_exchange_rate;
            $item['pay_type']              = 0;
        }
        $res = $payment_audit_model->execute($excel_model->saveAll($data, $payment_audit_model, $pk = 'id'));
        if (false === $res) {
            throw new \Exception(L('确认失败'));
        }
        $audit_info = array_combine(array_column($data, 'id'), $data);
        //分摊扣款和手续费金额，应付单和付款单状态同步（待优化）
        foreach ($pur_info as $value) {
            //【本单分摊扣款金额】 = 本单【确认后-应付金额】 * 该付款单【扣款金额】 / （∑合并的应付单的【确认后-应付金额】）
            $billing_amount = $value['amount_confirm'] * $audit_info[$value['payment_audit_id']]['billing_amount'] / $value['payable_amount_after'];
            //【本单分摊手续费】 = 本单【确认后-应付金额】 * 该付款单【扣款手续费】 / （∑合并的应付单的【确认后-应付金额】）
            $billing_fee = $value['amount_confirm'] * $audit_info[$value['payment_audit_id']]['billing_fee'] / $value['payable_amount_after'];
            $save[]      = [
                'id'                   => $value['payment_id'],
                'status'               => $status,
                'amount_account'       => $billing_amount,
                'expense'              => $billing_fee,
                'exchange_tax_account' => $audit_info[$value['payment_audit_id']]['billing_exchange_rate'],
                'update_time'          => dateTime(),
                'currency_account'     => $audit_info[$value['payment_audit_id']]['billing_currency_cd']
            ];
        }
        //同步状态、出账汇率
        $res = $payment_model->execute($excel_model->saveAll($save, $payment_model, $pk = 'id'));
        if (false === $res) {
            throw new \Exception(L('分摊出账金额、应付单和付款单状态同步失败'));
        }
    }

    public function addExtraData ($data)
    {
        $refund_map = $bank_map = $ids = $banks = [];
        foreach ($data as $item) {
            if (!empty($item['payment_our_bank_account'])) {
                $banks[] = $item['payment_our_bank_account'];
            }
            if ($item['source_cd'] != TbPurPaymentAuditModel::$source_b2c_payable) {
                continue;
            }
            $ids[] = $item['id'];
        }
        if ($banks) {
            $bank_map = M('tb_fin_account_bank')
                ->where(['account_bank'=>['in', $banks]])
                ->getField('account_bank, open_bank', true);
        }
        if ($ids) {
            $refund_map = M('order_refund', 'tb_op_')
                ->where(['payment_audit_id'=>['in', $ids]])
                ->getField('payment_audit_id, trade_no', true);
        }
        foreach ($data as &$item) {
            $item['trade_no'] = $refund_map[$item['id']];
            $item['payment_our_bank'] = $bank_map[$item['payment_our_bank_account']];
            if ($item['is_direct_billing']) {
                $item['payment_currency_cd_val'] = null;
                $item['payment_amount'] = null;
            }

        }
        unset($refund_map);
        unset($ids);
        unset($banks);
        unset($bank_map);
        return $data;
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
            'cs.RES_NAME',
            'pa.account_currency as collection_currency',//收款币种取pa.account_currency
            'od.amount_currency as payment_currency'//付款币种
        ];
        $row = $this->model->table('tb_pur_payment_audit pa')
            ->field($filed)
            ->join('left join tb_pur_payment pp on pa.id = pp.payment_audit_id')
            ->join('left join tb_pur_relevance_order rel on pp.relevance_id = rel.relevance_id')
            ->join('left join tb_pur_order_detail od on rel.order_id = od.order_id')
            ->join('left join tb_crm_sp_supplier ss on od.supplier_new_id = ss.ID')
            ->join('left join tb_crm_site cs on ss.SP_ADDR1 = cs.ID')
            ->join('left join tb_fin_account_bank ab on ab.id = pa.payment_account_id')
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
            throw new Exception(L('不是CITI禁止推kyriba'));
        }
        if (false === $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save(['pay_type'=>1])) {
            throw new Exception(L('更新是否推kyriba状态失败'));
        }
        (new KyribaService())->putXmlToFtp($row, TbPurPaymentAuditModel::$source_payable);
    }
}