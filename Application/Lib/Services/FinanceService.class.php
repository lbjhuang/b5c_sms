<?php

/**
 * User: yangsu
 * Date: 18/10/17
 * Time: 15:31
 */

@import("@.Model.Orm.TbFinTaxRate");

class FinanceService extends Service
{
    /**
     * @var FinanceRepository
     */
    public $FinanceRepository;
    public $user_name;

    const WX_CALLBACK_FUNCTION = 'transfer_approval';//企业微信审批回调方法名
    const WX_CALLBACK_FUNCTION_GENERAL = 'general_approval';//一般付款申请企业微信审批回调方法名

    const ACCOUNT_TYPE_SUPPLIER = 'N003510002';//供应商账号类型

    /**
     * FinanceService constructor.
     */
    public function __construct()
    {
        $this->FinanceRepository = new FinanceRepository();
        $this->user_name = DataModel::userNamePinyin();
    }

    /**
     * @param $ids
     *
     * @return mixed
     */
    public function getAccounts($ids)
    {
        $ids = array_column($ids, 'id');
        $accounts = $this->FinanceRepository
            ->getAccounts($ids);
        $return_res = $this->codeStatusToValue($accounts);
        return $return_res;
    }

    /**
     * @param $attrs
     *
     * @return array
     */
    public function getFinanceAttrs($attrs)
    {
        return array_map(function ($attr) {
            $this->FinanceRepository->$attr;
        }, $attrs);
    }

    /**
     * @param $attr
     *
     * @return mixed
     */
    public function getFinanceAttr($attr)
    {
        return $this->FinanceRepository
            ->$attr;
    }

    /**
     *
     */
    public function getFinanceExcelAttr()
    {
        $attr_arr = [
            'exp_title',
            'exp_cell_name'
        ];
        array_map(function ($temp) {
            $this->$temp = $this->getFinanceAttr($temp);
        }, $attr_arr);
    }

    /**
     * @param $accounts
     *
     * @return array
     */
    private function codeStatusToValue($accounts)
    {
        $code_arr = CodeModel::getCodeKeyValArr(['N00124', 'N00193', 'N00059','N00100','N00334','N00351']);
        //公司名称扩展停用的公司
        $code_arr_add = CodeModel::getCodeKeyValArr(['N00124'], 'N');
        $supplier = array_column(CommonDataModel::supplier(), 'SP_NAME', 'ID');
        $code_arr = array_merge($code_arr, $code_arr_add);
        $code2val_arr = ['company_code', 'account_type', 'currency_code','payment_channel_cd','bank_account_type','account_class_cd'];
        $return_res = array_map(function ($account) use ($code_arr, $code2val_arr, $supplier) {
            foreach ($code2val_arr as $key => $value) {
                $account[$value] = $code_arr[$account[$value]];
            }
            switch ($account['state']) {
                case 1:
                    $account['state'] = '启用';
                    break;
                case 2:
                    $account['state'] = '停用';
                    break;

            }
            //供应商
            if ($account['account_class_cd'] == '供应商') $account['company_code'] = $supplier[$account['supplier_id']];
            return $account;
        }, $accounts);
        return $return_res;
    }

    /**
     * 划转推送企业微信消息
     * @param $id 划转记录id
     * @param $send_email 要推送的账号
     */
    public function bulidTransferWechatApproval($id, $send_email) {
        $send_data = $this->getTransferWechatApprovalData($id);
        $wx_return_res = (new ReviewMsgTpl())->sendWeChatTurnoverApproval($send_data, $send_email, self::WX_CALLBACK_FUNCTION);
        Logs([$send_data, $wx_return_res], __FUNCTION__, __CLASS__);
    }

    /**
     * 申请一般付款推送企业微信消息
     * @param $id 划转记录id
     * @param $send_email 要推送的账号
     */
    public function bulidGeneralWechatApproval($id, $send_email) {
        $send_data = $this->getGeneralWechatApprovalData($id);
        $wx_return_res = (new ReviewMsgTpl())->sendWeChatGeneralApproval($send_data, $send_email, 'payment_message_notice');
        Logs([$send_data, $wx_return_res], __FUNCTION__, __CLASS__);
    }

    /**
     * 采购订单合同金额
     * @param $where
     * @param $type bool false 汇总列表|true 详情明细or导出
     */
    public function getRelevanceData($where, $type = false) {
        //先转化为人民币在转化为美元
        //扩展币种转换汇率字段，用于其他金额（如：退款认领）转换为采购单币种 注：和飞松确认以采购单创建日期取汇率转换
        $data = M('relevance_order','tb_pur_')
            ->alias('o')
            ->field('o.relevance_id, cd2.CD_VAL as payment_company, o.reconciliation_remark, a.procurement_number, a.online_purchase_order_number,tb_ms_xchr.USD_XCHR_AMT_CNY, a.amount_currency currency_old_cd, cd.CD_VAL currency_old, a.amount amount_old, a.our_company, cd1.CD_VAL as our_company_name, a.supplier_id, \'USD\' as new_currency,
            IFNULL(ROUND((a.amount * (CASE cd.CD_VAL
                WHEN \'USD\' THEN tb_ms_xchr.USD_XCHR_AMT_CNY
                WHEN \'EUR\' THEN tb_ms_xchr.EUR_XCHR_AMT_CNY
                WHEN \'HKD\' THEN tb_ms_xchr.HKD_XCHR_AMT_CNY
                WHEN \'SGD\' THEN tb_ms_xchr.SGD_XCHR_AMT_CNY
                WHEN \'AUD\' THEN tb_ms_xchr.AUD_XCHR_AMT_CNY
                WHEN \'GBP\' THEN tb_ms_xchr.GBP_XCHR_AMT_CNY
                WHEN \'CAD\' THEN tb_ms_xchr.CAD_XCHR_AMT_CNY
                WHEN \'MYR\' THEN tb_ms_xchr.MYR_XCHR_AMT_CNY
                WHEN \'DEM\' THEN tb_ms_xchr.DEM_XCHR_AMT_CNY
                WHEN \'MXN\' THEN tb_ms_xchr.MXN_XCHR_AMT_CNY
                WHEN \'THB\' THEN tb_ms_xchr.THB_XCHR_AMT_CNY
                WHEN \'PHP\' THEN tb_ms_xchr.PHP_XCHR_AMT_CNY
                WHEN \'IDR\' THEN tb_ms_xchr.IDR_XCHR_AMT_CNY
                WHEN \'TWD\' THEN tb_ms_xchr.TWD_XCHR_AMT_CNY
                WHEN \'VND\' THEN tb_ms_xchr.VND_XCHR_AMT_CNY
                WHEN \'KRW\' THEN tb_ms_xchr.KRW_XCHR_AMT_CNY
                WHEN \'JPY\' THEN tb_ms_xchr.JPY_XCHR_AMT_CNY
                WHEN \'CNY\' THEN tb_ms_xchr.CNY_XCHR_AMT_CNY
                END) ) / tb_ms_xchr.USD_XCHR_AMT_CNY, 6),0.00) AS amount,
                IFNULL((select sum(deduction_amount) from tb_pur_deduction_detail dedu 
                    where order_no = procurement_number and order_type_cd = "N001950100" and turnover_type = 1 and is_revoke = 0),0.00) as used_deduction_amount,
                IFNULL((select sum(deduction_amount) from tb_pur_deduction_detail dedu 
                    where order_no = procurement_number and order_type_cd = "N001950100" and turnover_type = 2 and is_revoke = 0),0.00) as as_deduction_amount'
            )
            ->join("left join tb_pur_order_detail a on a.order_id = o.order_id")
            ->join('left join tb_ms_cmn_cd cd ON cd.CD = a.amount_currency')
            ->join('left join tb_ms_cmn_cd cd1 on cd1.CD = a.our_company')
            ->join('left join tb_ms_cmn_cd cd2 on cd2.CD = a.payment_company')
            ->join('left join tb_ms_xchr on tb_ms_xchr.XCHR_STD_DT = DATE_FORMAT(o.prepared_time,\'%Y%m%d\')')
            ->order('o.prepared_time desc')
            ->where($where)->select();

        Logs([$data], __FUNCTION__, __CLASS__);
        return $data;
    }

    public function formatRecociliationDataNew($data, $onway_data = [], $type = 0)
    {
        $tem = [];
        foreach ($data as $key => &$item) {
            $k = $item['relevance_id'];
            $payment_amount = isset($onway_data[$k]) ? $onway_data[$k]['amount_paid_ori'] : '0'; //付款认领
            $warehouse_amount = isset($onway_data[$k]) ? $onway_data[$k]['amount_in_warehouse_ori'] : '0'; //入库
            $item['po_date_rate'] = isset($onway_data[$k]) ? $onway_data[$k]['po_date_rate'] : '0';
            $item['payment_amount_num'] = round($payment_amount, 6); //付款
            $item['warehouse_amount_num'] = round(($warehouse_amount), 6); //退货
            $item['surplus_amount_num'] = round(($item['payment_amount_num'] - $item['warehouse_amount_num']), 6);
            $item['contract_amount_num'] = round($item['contract_amount'], 2);
            $item['amount'] = number_format(round($item['amount_old'], 2), 2);
            $item['surplus_amount'] = number_format($item['surplus_amount_num'], 2);
            $item['payment_amount'] = number_format(round($item['payment_amount_num'], 2), 2); //付款
            $item['warehouse_amount'] = number_format(round($item['warehouse_amount_num'], 2), 2); //退货
            $item['contract_amount'] = number_format(round($item['contract_amount_num'], 2), 2);

            $item['as_deduction_amount']   = round($item['as_deduction_amount'], 2);
            $item['used_deduction_amount'] = round($item['used_deduction_amount'], 2);
            $item['surplus_deduction_amount'] = round($item['surplus_amount_num']+$item['used_deduction_amount']-$item['as_deduction_amount'],6);
            $item['as_deduction_amount']   = number_format($item['as_deduction_amount'], 2);
            $item['used_deduction_amount'] = number_format($item['used_deduction_amount'], 2);
            $tem[$k] = $item;
        }
        $list = array_values($tem);
        return $list;
    }

    //汇率转换
    //如果其他金额币种与采购单币种一致则直接使用原始币种（原因：截止时间不同导致汇率不同转换金额有偏差）
    //不一致则1将转为美元的金额转换为人民币  2用当前当前采购单币种转人民币汇率 采购单币种_XCHR_AMT_CNY
    public function formatCurrency($relevance_data, $check_data)
    {
        //如果其他金额币种与采购单币种一致则直接使用原始币种（原因：截止时间不同导致汇率不同转换金额有偏差）
        $xchr = $relevance_data['currency_old'] . '_XCHR_AMT_CNY';
        //不一致则1将转为美元的金额转换为人民币  2用当前当前采购单币种转人民币汇率 采购单币种_XCHR_AMT_CNY
        $payment_amount =  $check_data['amount_tmp'] * $check_data['USD_XCHR_AMT_CNY'] / $check_data[$xchr];
        return $payment_amount;
    }

    //汇率转换
    //如果其他金额币种与采购单币种一致则直接使用原始币种（原因：截止时间不同导致汇率不同转换金额有偏差）
    //不一致则1将转为美元的金额转换为人民币  2用当前当前采购单币种转人民币汇率 采购单币种_XCHR_AMT_CNY
    public function formatCurrencyNew($relevance_data, $check_data)
    {
        //如果其他金额币种与采购单币种一致则直接使用原始币种（原因：截止时间不同导致汇率不同转换金额有偏差）
        $xchr = $relevance_data['currency_old'] . '_XCHR_AMT_CNY';
        //不一致则1将转为美元的金额转换为人民币  2用当前当前采购单币种转人民币汇率 采购单币种_XCHR_AMT_CNY
        $payment_amount =  bcdiv((bcmul($check_data['amount_tmp'], $check_data['USD_XCHR_AMT_CNY'],8)), $check_data[$xchr],8);
        return $payment_amount;
    }

    //获取在途报表的付款金额、入库金额
    public function getOnwayData($params)
    {
        if (!empty($params['start_date'])) {
            $start_date = $params['start_date'];
        } else {
            $start_date = '2018-08-01';
        }
        $data['page'] = 0; //导出全部
        $data['page_size'] = -1; //导出全部
        //付款金额、入库金额：在途计算日期 = 截止日期
        $data['onway_date'] = [$start_date, date('Y-m-d', strtotime($params['end_date']))];
        $data['po_date'] = [$start_date, date('Y-m-d', strtotime($params['end_date']))];
        //$data['supplier'] = $params['supplier_name'];
        empty($params['supplier_name']) or $data['supplier'] = $params['supplier_name'];
        empty($params['our_company']) or $data['our_company'] = (array)$params['our_company'];
        $logic = D('Report/Onway', 'Logic');
        $logic->listData($data);
        $list = $logic->getRet();
        //var_dump($list);exit;
        return $list['data']['list'];
    }

    /**
     * 企业微信审批-获取审核详情
     * @param $id
     * @return array
     */
    private function getTransferWechatApprovalData($id) {
        $transfer_field = 'tb_fin_account_transfer.id,pay_account_bank_id,rec_account_bank_id,amount_money,reason,
            tb_fin_account_transfer.transfer_no,attachment,create_user,currency_code,use,create_user_nm,currency_code,
            GROUP_CONCAT(am.msg)as audit_msg_str, GROUP_CONCAT(am.auditor_nm)as audit_user_str';

        $transfer = M('fin_account_transfer', 'tb_')
            ->field($transfer_field)
            ->join('left join tb_fin_account_audit_msg am on tb_fin_account_transfer.transfer_no = am.transfer_no')
            ->where(['tb_fin_account_transfer.id' => $id])->find();

        //不知道为啥要获取这个时间，直接读fin_account_transfer表里的时间不就完了吗，差那么个几秒
        $transfer['create_time'] = M('fin_account_bank_log', 'tb_')
            ->where(['order_no' => $transfer['transfer_no'], 'tag' => ['in',[2,3]]])
            ->order('create_time asc')
            ->getField('create_time');
        if (empty($transfer['create_user_nm'])) {
            $transfer['create_user_nm'] = DataModel::getUserScNameById($transfer['create_user']);
        }
        if (empty($transfer['create_user_nm'])) {
            $transfer['create_user_nm'] = DataModel::getUserNameById($transfer['create_user']);
        }
        //组装审批人及备注
        $reason = $vocher = [];
        $msg_arr = explode(',',$transfer['audit_msg_str']);
        $user_arr = explode(',',$transfer['audit_user_str']);
        foreach ($user_arr as $key => $value) {
            $reason[] = ['name' => $value,'reason' => $msg_arr[$key]];
        }
        $transfer['audit_reason'] = $reason;
        //输出图片字节流
        if ($transfer['attachment']) {
            $temp = json_decode($transfer['attachment'], true);
            foreach ($temp as $key => &$value) {
                $vocher[] = base64_encode(file_get_contents(ATTACHMENT_DIR_IMG. $value ['saveName']));
            }
        }
        $transfer['attachment'] = $vocher;
        $transfer = CodeModel::autoCodeOneVal($transfer, ['currency_code']);

        //获取付款公司与收款公司信息
        $where = [
            'id' => ['in', [$transfer['pay_account_bank_id'], $transfer['rec_account_bank_id']]]
        ];
        $pay_account = $rec_account = [];
        $bank_field = 'id,open_bank,account_bank,swift_code,company_code,currency_code,currency_code,account_class_cd,supplier_id';
        $banks = M('fin_account_bank', 'tb_')->field($bank_field)->where($where)->select();

        $supplier_model = M('crm_sp_supplier', 'tb_');
        $banks = CodeModel::autoCodeTwoVal($banks, ['currency_code', 'company_code']);
        foreach ($banks as &$value) {
            if ($value['account_class_cd'] == self::ACCOUNT_TYPE_SUPPLIER && $value['supplier_id']) {
                //供应商账户类型
                $value['company_code_val'] = $supplier_model->where(['ID' => $value['supplier_id']])->getField('SP_NAME');
            }
            if ($value['id'] == $transfer['pay_account_bank_id']) {
                $pay_account = $value;
            } else {
                $rec_account = $value;
            }
        }
        unset($value);
        if (count($banks) == 1) {
            //转入转出是同一个账号
            $rec_account = $pay_account;
        }
        return $data = [
            'base_info' => $transfer,
            'rec_account' => $rec_account,
            'pay_account' => $pay_account
        ];
    }

    /**
     * 企业微信审批-获取一般付款详情
     * @param $id
     * @return array
     */
    public function getGeneralWechatApprovalData($id) {
        $db_res = (new GeneralPaymentService())->getGeneralPayment($id);
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
        $general_payment_detail = $db_res['general_payment_detail'];
        foreach ($general_payment_detail as $key => $item) {
            $general_payment_detail[$key]['actual_fee_Department'] = $this->getMinFeeDepartment($item['actual_fee_Department']);
        }
        $payment_audit['payable_amount'] = sprintf("%.2f", array_sum(array_column($general_payment_detail, 'subtotal')));//保留两位小数
        $payment_audit = CodeModel::autoCodeOneVal($payment_audit, ['settlement_type','source_cd','our_company_cd', 'procurement_nature', 'invoice_information', 'invoice_type', 'bill_information', 'commission_type', 'our_company_cd', 'payment_type', 'payment_channel_cd', 'payment_way_cd', 'payable_currency_cd'], 'all');
        $general_payment_detail = CodeModel::autoCodeTwoVal($general_payment_detail, ['subdivision_type', 'payment_channel_cd', 'payment_way_cd', 'payable_currency_cd']);
        return $data = [
            'base_info' => $payment_audit,
            'detail_info' => $general_payment_detail,
        ];
    }

    //获取最小的费用归属部门
    public function getMinFeeDepartment($actual_fee_Department) {
        $tem = strrev($actual_fee_Department);
        if (strpos($actual_fee_Department, '>') !== false) {
            $actual_fee_Department = strrev(strstr($tem, '>', true));
        }
        if (strpos($actual_fee_Department, ',') !== false) {
            $actual_fee_Department = strrev(strstr($tem, ',', true));
        }
        return $actual_fee_Department;
    }

    /**
     * @param $request_data
     * @param $payment_info
     *
     * @return string
     */
    public function WorkWxSendMessage($request_data, $payment_info)
    {
        //企业微信信息发送
        $send_old_user = $payment_info['current_audit_user']; //原先的审核人
        //$send_old_user = 'shenmo';
        //$send_user = 'shenmo';
        $payment_info_new = M('payment_audit', 'tb_pur_')->where(['id'=>['in',$request_data['payment_audit_id']]])->find();
        if ($payment_info_new['status'] == TbPurPaymentAuditModel::$status_no_confirmed) {
            //待确认代表退回了给创建人发信息
            $send_user = $payment_info_new['created_by']; //创建人发起人
            $data = '您的付款申请' . $payment_info_new['payment_audit_no'] . '已经被退回。';
            $wx_return_res = (new ApiModel())->WorkWxMessage($send_user, $data);
            //从待付款退回（erp操作）给操作人发信息
            if ($payment_info['status'] == TbPurPaymentAuditModel::$status_no_payment) {
                $send_old_user = $_SESSION['m_loginname']; //当前登录操作人
            }
            $data = '您已拒绝付款单号' . $payment_info_new['payment_audit_no'] . '的申请。';
        } else {
            if ($payment_info_new['status'] == TbPurPaymentAuditModel::$status_accounting_audit) { //待审核
                $send_user = $payment_info_new['current_audit_user']; //下一个审核人
                //企业微信审批卡片
                $send_email    = [$send_user . '@gshopper.com'];
                $wx_return_res = (new FinanceService())->bulidGeneralWechatApproval($request_data['payment_audit_id'], $send_email);
            }
            if ($payment_info_new['status'] == TbPurPaymentAuditModel::$status_no_payment) { //待付款
                $send_user = $payment_info_new['payment_manager_by']; //付款负责人
                $data = '付款单' . $payment_info_new['payment_audit_no'] . '已经审批通过，等待付款。';
                $wx_return_res = (new ApiModel())->WorkWxMessage($send_user, $data);
            }
            $data = '您已同意付款单号' . $payment_info_new['payment_audit_no'] . '的申请。';
        }
        $wx_return_res = (new ApiModel())->WorkWxMessage($send_old_user, $data);
    }

    //税号提交/编辑
    public function saveTaxNumber($request_data)
    {
//        $model = M('fin_tax_rate', 'tb_');
//        if(!$model->create($request_data)) {
//            throw new \Exception(L('创建数据失败'));
//        }
//        $model->is_direct_billing  = 0;//是否直接出账0-否
//        $res = $payment_audit_table->where(['id' => $payment_audit_id])->save();
        $where = [];
        $data = [
            'our_company_cd' => $request_data['our_company_cd'],
            'country_id'     => $request_data['country_id'],
            'vat_number'     => $request_data['vat_number'],
            'tax_rate'       => $request_data['tax_rate'],
        ];
        if (!empty($request_data['id'])) {
            $tax_info = TbFinTaxRate::query()
                ->where('our_company_cd', $request_data['our_company_cd'])
                ->where('country_id', $request_data['country_id'])
                ->where('id', '!=', $request_data['id'])
                ->first();
            if (!empty($tax_info)) {
                throw new \Exception(L('我方公司和税号所属国组合已存在'));
            }
            $where['id'] = $request_data['id'];
            $data['updated_by'] = $this->user_name;
        } else {
            $tax_info = TbFinTaxRate::query()
                ->where('our_company_cd', $request_data['our_company_cd'])
                ->where('country_id', $request_data['country_id'])
                ->first();
            if (!empty($tax_info)) {
                throw new \Exception(L('我方公司和税号所属国组合已存在'));
            }
            $data['created_by'] = $this->user_name;
            $data['updated_by'] = $this->user_name;
            $where['id'] = null;
        }
        $res = TbFinTaxRate::updateOrCreate($where, $data);
        if (!$res) {
            throw new \Exception(L('新增/更新失败'));
        }
    }

    //税号列表
    public function getTaxNumberList($request_data, $is_excel = false)
    {
        $search_map = [
            'our_company_cd' => 'tax.our_company_cd',
            'country_id'     => 'tax.country_id',
            'vat_number'     => 'tax.vat_number',
        ];
        list($where, $limit) = WhereModel::joinSearchTemp($request_data, $search_map);
        list($res_db, $pages) = $this->FinanceRepository->searchTaxNumberList($where, $limit, $is_excel);
        if ($res_db) {
            $res_db = CodeModel::autoCodeTwoVal($res_db, ['our_company_cd']);
        }
        foreach ($res_db as &$v) {
            $v['our_company_cd_val'] = L( $v['our_company_cd_val']);
            $v['country_name'] = L( $v['country_name']);
            if ($is_excel) $v['tax_rate'] = $v['tax_rate']. '%';
        }
        return [
            'data'  => $res_db,
            'pages' => $pages
        ];
    }
}