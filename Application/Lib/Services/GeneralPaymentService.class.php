<?php
/**
 * 一般付款
 * User: shenmo
 * Date: 20/04/16
 * Time: 16:05
 */
class GeneralPaymentService extends Service
{
    public $user_name;
    public $model;
    public $payment_table;
    public $payment_audit_table;
    public $payment_audit_log_table;
    public $general_payment_examine_table;
    public $general_payment_table;
    public $general_payment_detail_table;


    //日志记录所需参数
    private $payment_ids;
    private $payment_audit_id;
    private $operation_info;
    private $status_name;
    private $remark;

    public $repository;

    public function __construct($model)
    {
        $this->user_name                     = DataModel::userNamePinyin();
        $this->model                         = empty($model) ? new Model() : $model;
        $this->payment_audit_table           = M('payment_audit', 'tb_pur_');
        $this->general_payment_table         = M('general_payment', 'tb_');
        $this->general_payment_detail_table  = M('general_payment_detail', 'tb_');
        $this->general_payment_examine_table = M('general_payment_examine', 'tb_');
        $this->payment_audit_log_table       = M('payment_audit_log', 'tb_pur_');

        $this->repository = new PurRepository($this->model);
    }

    /**
     * 一般付款单保存
     * @param $payment_info
     * @throws Exception
     */
    public function generalPaymentAdd($payment_info)
    {
        try {
            //获取领导
            $leader_str = $this->getDeptLeader($payment_info);
            $payment_info['payment_audit']['leader'] = str_replace(',', '->', trim($leader_str, ','));
            $payment_info['payment_audit']['current_leader'] = explode(',', $leader_str)[0];//当前审核人

//            $getDeptLeader = $this->getDeptLeader($payment_info['general_payment']['actual_fee_applicant']);
//            $payment_info['payment_audit']['leader'] = $getDeptLeader ? $getDeptLeader : '';
            $snap_recover = true;
            $payment_audit_id = $this->paymentAuditSave($payment_info['payment_audit'], $snap_recover);
            $payment_info['general_payment']['payment_audit_id'] = $payment_audit_id;
            $payment_info['payment_audit']['payment_audit_id'] = $payment_audit_id;
            $this->generalPaymentSave($payment_info['general_payment']);
            $this->generalPaymentDetailSaveAll($payment_info['general_payment_detail'], $payment_audit_id);
            $wx_return_res = $this->bulidGeneralWechatApproval($payment_info['payment_audit']);
            return $payment_audit_id;
        } catch (exception $exception) {
            throw $exception;
        }
    }

    //推送微信卡片
    public function bulidGeneralWechatApproval($payment_audit)
    {
        if ($payment_audit['submit_type'] != 1) { //提交申请
            //企业微信审批
            $send_email    = [$payment_audit['current_leader'] . '@gshopper.com'];
            //$send_email    = ['Allen.Ouyang@gshopper.com'];
            $wx_return_res = (new FinanceService())->bulidGeneralWechatApproval($payment_audit['payment_audit_id'], $send_email);
        }
    }

    /**
     * 一般付款单保存
     * @param $payment_info
     * @throws Exception
     */
    public function generalPaymentEdit($payment_info)
    {
        try {
            //获取领导
            $leader_str = $this->getDeptLeader($payment_info);
            $payment_info['payment_audit']['leader'] = str_replace(',', '->', trim($leader_str, ','));
            $payment_info['payment_audit']['current_leader'] = explode(',', $leader_str)[0];//当前审核人
            //查一下当前和现在的审批链条是否一致
            $old_accounting_audit_user = $this->payment_audit_table->where('id = '.$payment_info['payment_audit']['payment_audit_id'])->getField('accounting_audit_user');
            $snap_recover = true;
            if($payment_info['payment_audit']['leader'] != $old_accounting_audit_user) {
                $snap_recover = false;
            }

            $res = $this->paymentAuditSave($payment_info['payment_audit'], $snap_recover);
            $payment_audit_id = $payment_info['payment_audit']['payment_audit_id'] ? $payment_info['payment_audit']['payment_audit_id'] : $res;
            $payment_info['general_payment']['payment_audit_id'] = $payment_audit_id;
            $this->generalPaymentSave($payment_info['general_payment']);
            $this->generalPaymentDetailEditAllNew($payment_info['general_payment_detail'], $payment_audit_id);
            $wx_return_res = $this->bulidGeneralWechatApproval($payment_info['payment_audit']);
            return true;
        } catch (exception $exception) {
            throw $exception;
        }
    }



    /**
     * 付款单保存
     * @param $request_data
     * @throws Exception
     */
    public function paymentAuditSave($payment_info, $snap_recover)
    {
        $payment_audit_data['supplier_collection_account']  = $payment_info['supplier_collection_account'];
        $payment_audit_data['supplier_opening_bank']        = $payment_info['supplier_opening_bank'];
        $payment_audit_data['supplier_card_number']         = $payment_info['supplier_card_number'];
        $payment_audit_data['supplier_swift_code']          = $payment_info['supplier_swift_code'];
        $payment_audit_data['platform_cd']                  = $payment_info['platform_cd'];
        $payment_audit_data['store_name']                   = $payment_info['store_name'];
        $payment_audit_data['platform_order_no']            = $payment_info['platform_order_no'];
        $payment_audit_data['collection_account']           = $payment_info['collection_account'];
        $payment_audit_data['collection_user_name']         = $payment_info['collection_user_name'];
        $payment_audit_data['trade_no']                     = $payment_info['trade_no'];
        $payment_audit_data['our_company_cd']               = $payment_info['our_company_cd']; // 我方公司
        $payment_audit_data['payable_amount_before']        = $payment_info['payable_amount']; // 支付金额
        $payment_audit_data['payable_amount_after']         = $payment_info['payable_amount']; // 支付金额
        $payment_audit_data['payable_currency_cd']          = $payment_info['payment_currency_cd']; // 支付币种
        $payment_audit_data['payment_currency_cd']          = $payment_info['payment_currency_cd']; // 付款币种
        $payment_audit_data['payable_date_after']           = $payment_info['payable_date']; // 预计付款日期
        $payment_audit_data['payable_date_before']          = $payment_info['payable_date']; // 预计付款日期
        $payment_audit_data['payment_channel_cd']           = $payment_info['payment_channel_cd']; // 支付渠道
        $payment_audit_data['payment_way_cd']               = $payment_info['payment_way_cd']; // 支付方式
        $payment_audit_data['current_audit_user']           = $payment_info['current_leader'] ? : TbPurPaymentAuditModel::$accounting_audit_user;//当前审核人
        $payment_audit_data['bank_settlement_code']         = $payment_info['bank_settlement_code'] ? $payment_info['bank_settlement_code'] : ''; // 收款银行本地结算代码
        $payment_audit_data['bank_address']                 = $payment_info['bank_address'] ? $payment_info['bank_address'] : ''; // 收款银行地址
        $payment_audit_data['city']                         = $payment_info['city'] ? $payment_info['city'] : ''; // 收款银行地址id
        $payment_audit_data['bank_address_detail']          = $payment_info['bank_address_detail'] ? $payment_info['bank_address_detail'] : ''; // 收款银行详细地址
        $payment_audit_data['bank_postal_code']             = $payment_info['bank_postal_code'] ? $payment_info['bank_postal_code'] : ''; // 收款银行邮编
        $payment_audit_data['account_currency']             = $payment_info['account_currency'] ? $payment_info['account_currency'] : ''; // 收款账号币种CD
        $payment_audit_data['account_type']                 = $payment_info['account_type'] ? $payment_info['account_type'] : ''; // 收款账户种类CD

        //确认前后总应付 等于 各个应付单确认前后金额之和
        if ($payment_info) {

        }
        $payment_audit_data['payment_account']              = TbPurPaymentAuditModel::getPaymentAccount($payment_audit_data['payment_channel_cd']);
        $payment_audit_data['source_cd']                    = TbPurPaymentAuditModel::$source_general_payable;
        $payment_audit_data['accounting_audit_user']        = $payment_info['leader'];
        $payment_audit_data['status']                       = TbPurPaymentAuditModel::$status_accounting_audit; //待审核
        if ($payment_info['submit_type'] == 1) { //保存草稿
            $payment_audit_data['status']                   = TbPurPaymentAuditModel::$status_no_confirmed; //待提交
        }

        if ($payment_info['payment_audit_id']) {
            $payment_audit_data['id']                = $payment_info['payment_audit_id'];
            if ($payment_info['submit_type'] == 2) {
                $financeAuditInfo = (new FinanceRepository)->getAuditInfo(['id' => $payment_info['payment_audit_id']]);
                if ($financeAuditInfo['snapshot_status'] && $financeAuditInfo['snapshot_audit_user']) {
                    //领导审批链条不变的情况下，审批人直接跳到快照字段保存的审批人，否则按当前领导来
                    if($snap_recover) {
                        $payment_audit_data['current_audit_user'] = $financeAuditInfo['snapshot_audit_user'];
                    }
                    $payment_audit_data['status'] = $financeAuditInfo['snapshot_status'];
                    if ($financeAuditInfo['snapshot_status'] == TbPurPaymentAuditModel::$status_after_payment) {
                        $payment_audit_data['status'] = TbPurPaymentAuditModel::$status_no_payment;
                    } 
                    $this->sendMsg($financeAuditInfo['snapshot_status'], $payment_info['payment_audit_id']);
                }
            }
            $payment_audit_id = $this->payment_audit_table->save($payment_audit_data);
            $this->operation_info = '编辑一般付款';
            if ($payment_audit_id === false) throw new Exception(L('编辑一般付款单失败'));
        } else {
            $payment_audit_data['payment_audit_no']          = 'FK' . date(Ymd) . TbWmsNmIncrementModel::generateNo('payment_audit');
            $payment_audit_data['created_by']                = $payment_audit_data['updated_by'] = $this->user_name;
            $payment_audit_id = $this->payment_audit_table->add($payment_audit_data);
            $this->operation_info = '申请一般付款';
            if (!$payment_audit_id) throw new Exception(L('申请一般付款单失败'));
        }
        $this->payment_audit_id = $payment_info['payment_audit_id'] ? $payment_info['payment_audit_id'] : $payment_audit_id;
        $this->status_name   = TbPurPaymentAuditModel::$status_map[$payment_audit_data['status']];
        $this->recordLog();
        return $payment_audit_id;
    }

    /**
     * 一般付款单保存
     * @param $payment_info
     * @throws Exception
     */
    public function generalPaymentSave($payment_info)
    {
        $general_payment['payment_audit_id']      = $payment_info['payment_audit_id'];
        $general_payment['payment_nature']        = $payment_info['payment_nature'];
        $general_payment['supplier']              = $payment_info['supplier'];
        $general_payment['contract_information']  = $payment_info['contract_information'];
        $general_payment['contract_no']           = $payment_info['contract_no'];
        $general_payment['settlement_type']       = $payment_info['settlement_type'];
        $general_payment['procurement_nature']    = $payment_info['procurement_nature'];
        $general_payment['invoice_information']   = $payment_info['invoice_information'];
        $general_payment['invoice_type']          = $payment_info['invoice_type'];
        $general_payment['bill_information']      = $payment_info['bill_information'];
        $general_payment['payment_type']          = !empty($payment_info['payment_type']) ? $payment_info['payment_type'] : '';
        $general_payment['actual_fee_applicant']  = isset($payment_info['actual_fee_applicant']) ? $payment_info['actual_fee_applicant'] : '';
        $general_payment['actual_fee_Department'] = isset($payment_info['actual_fee_Department']) ? $payment_info['actual_fee_Department'] : '';
        $general_payment['actual_fee_department_id'] = isset($payment_info['actual_fee_department_id']) ? $payment_info['actual_fee_department_id'] : '';
        $general_payment['payment_remark']        = $payment_info['payment_remark'];
        $general_payment['created_by']            = $payment_audit_data['updated_by'] = $this->user_name;
        $general_payment['created_at']            = dateTime();
        $general_payment['dept_id']               = $payment_info['dept_id'];
        $general_payment['invoice_attachment']    = !empty($payment_info['invoice_attachment']) ? json_encode($payment_info['invoice_attachment']) : '';
        $general_payment['bill_attachment']       = !empty($payment_info['bill_attachment']) ? json_encode($payment_info['bill_attachment']) : '';
        $general_payment['other_attachment']      = !empty($payment_info['other_attachment']) ? json_encode($payment_info['other_attachment']) : '';
       
        if ($payment_info['general_payment_id']) {
            $where['id']                = $payment_info['general_payment_id'];
            $payment_audit_id = $this->general_payment_table->where($where)->save($general_payment);
            if ($payment_audit_id === false) throw new Exception(L('编辑一般付款单失败'));
        } else {
            $payment_audit_id = $this->general_payment_table->add($general_payment);
            if (!$payment_audit_id) throw new Exception(L('生成一般付款单失败'));
        }

        //$this->recordLog();
        return $payment_audit_id;
    }

    /**
     * 一般付款单详情保存
     * @param $payment_detail_info
     * @throws Exception
     */
    public function generalPaymentDetailSave($payment_detail_info)
    {
        $general_payment['payment_audit_id']   = $payment_detail_info['payment_audit_id'];
        $general_payment['project_summary']    = $payment_detail_info['project_summary'];
        $general_payment['subdivision_type']   = $payment_detail_info['subdivision_type'];
        $general_payment['amount']             = $payment_detail_info['amount'];
        $general_payment['vat_rate']           = $payment_detail_info['vat_rate'];
        $general_payment['subtotal']           = $payment_detail_info['subtotal'];
        $general_payment['invoice_attachment'] = $payment_detail_info['invoice_attachment'];
        $general_payment['bill_attachment']    = $payment_detail_info['bill_attachment'];
        $general_payment['other_attachment']   = $payment_detail_info['other_attachment'];
        $general_payment['relation_bill_type'] = $payment_detail_info['relation_bill_type'];
        $general_payment['relation_bill_no']   = $payment_detail_info['relation_bill_no'];
        $general_payment['remark']             = $payment_detail_info['remark'];
        $general_payment['created_by']         = $payment_audit_data['updated_by'] = $this->user_name;
        $general_payment['created_at']         = dateTime();
        $payment_audit_id = $this->general_payment_detail_table->add($general_payment);
        if (!$payment_audit_id) throw new Exception(L('生成一般付款单详情失败'));

        //$this->recordLog();
        return $payment_audit_id;
    }

    public function createPaymentNO() {
        $pre_payment_no = $this->general_payment_detail_table->lock(true)->where(['payment_no'=>['like','YF'.date('Ymd').'%']])->order('id desc')->getField('payment_no');
        if($pre_payment_no) {
            $num = substr($pre_payment_no,-3)+1;
        }else {
            $num = 1;
        }
        $payment_no = 'YF'.date('Ymd').substr(1000+$num,1);
        return $payment_no;
    }

    /**
     * 一般付款单详情保存
     * @param $payment_detail_info
     * @param $payment_audit_id
     * @throws Exception
     */
    public function generalPaymentDetailSaveAll($payment_detail_info, $payment_audit_id = 0)
    {
        if (empty($payment_detail_info)) return [];
        $data = [];
        $payment_no = $this->createPaymentNO();
        foreach ($payment_detail_info as $item) {
            $general_payment['payment_audit_id']   = $payment_audit_id ? $payment_audit_id : $item['payment_audit_id'];
            $general_payment['payment_no']         = $payment_no;
            $general_payment['project_summary']    = $item['project_summary'] ? $item['project_summary'] : '';
            $general_payment['subdivision_type']   = $item['subdivision_type'] ? $item['subdivision_type'] : '';
            $general_payment['amount']             = $item['amount'] ? $item['amount'] : 0;
            $general_payment['vat_rate']           = $item['vat_rate'] ? $item['vat_rate'] : 0;
            $general_payment['subtotal']           = $item['subtotal'];
            $general_payment['actual_fee_applicant'] = isset($item['actual_fee_applicant']) ? $item['actual_fee_applicant'] : '';
            $general_payment['actual_fee_Department'] = isset($item['actual_fee_Department']) ? $item['actual_fee_Department'] : '';
            $general_payment['actual_fee_department_id'] = isset($item['actual_fee_department_id']) ? $item['actual_fee_department_id'] : '';
            // $general_payment['invoice_attachment'] = !empty($item['invoice_attachment']) && $item['invoice_attachment'] ? json_encode($item['invoice_attachment']) : '';
            // $general_payment['bill_attachment']    = !empty($item['bill_attachment']) && $item['bill_attachment'] ? json_encode($item['bill_attachment']) : '';
            // $general_payment['other_attachment']   = !empty($item['other_attachment']) && $item['other_attachment'] ? json_encode($item['other_attachment']) : '';
            $general_payment['relation_bill_type'] = $item['relation_bill_type'] ? $item['relation_bill_type'] : '';
            //关联单据类型为无，关联订单号置空
            $general_payment['relation_bill_no']   = $item['relation_bill_type'] != 'N003310005' && $item['relation_bill_no'] ? $item['relation_bill_no'] : '';
            $general_payment['remark']             = $item['remark'] ? $item['remark'] : '';
            $general_payment['created_by']         = $payment_audit_data['updated_by'] = $this->user_name;
            $general_payment['created_at']         = dateTime();
            $data[] = $general_payment;
            $num = substr($payment_no,-3)+1;
            $payment_no = 'YF'.date('Ymd').substr(1000+$num,1);
        }
        $payment_audit_id = $this->general_payment_detail_table->addAll($data);
        ELog::add(['info'=>'一般付款详情新增记录','res'=>$payment_audit_id, 'request' => json_encode($payment_detail_info), 'data' => json_encode($data)],ELog::INFO);
        if (!$payment_audit_id) throw new Exception(L('生成一般付款单详情失败'));

        //$this->recordLog();
        return $payment_audit_id;
    }

    //部门领导
    public function getDeptLeader($payment_info)
    {
        //根据登录用户and审核部门查询审批领导列
        $leader_str = (new TbHrDeptModel())->getDeptLeaderByDeptId($_SESSION['m_loginname'], $payment_info['general_payment']['dept_id']);
        //先截取再去重（创建人是部门领导）
        if (strpos(',' . $leader_str . ',', ',' . $_SESSION['m_loginname'] . ',') !== false) {
            $key = strpos(',' . $leader_str . ',', ',' . $_SESSION['m_loginname'] . ',');
            $leader_str = substr($leader_str, $key + strlen($_SESSION['m_loginname']) + 1);
        }
        foreach ($payment_info['general_payment_detail'] as $v) {
            if (in_array($v['subdivision_type'], TbMsCmnCdModel::getAttendanceSubdivisionType())) {
                $attendance_audit_user = TbPurPaymentAuditModel::$attendance_audit_user;
                if (empty($leader_str)) {
                    $leader_str = $attendance_audit_user;//考勤审核人
                } else {
                    //去重
                    $leader_arr = explode(',', $leader_str);
                    if (in_array($attendance_audit_user, $leader_arr)) {
                        unset($leader_arr[array_search($attendance_audit_user, $leader_arr)]);
                        if (!empty($leader_arr)) {
                            $leader_str = implode(',', array_values($leader_arr));
                            $leader_str = $attendance_audit_user. ','. $leader_str;
                        } else {
                            $leader_str = $attendance_audit_user;//考勤审核人
                        }
                    } else {
                        $leader_str = $attendance_audit_user. ','. $leader_str;
                    }
                }
                break;
            }
        }
        //自定义配置审批人
        $cd = $this->getDeptByPaymentType($payment_info['general_payment']['payment_type']);
        if ($cd['ETC4']) $leader_str = $leader_str ? $leader_str . ',' . $cd['ETC4'] : $cd['ETC4'];
        $leader_str .= ',' . TbPurPaymentAuditModel::$accounting_audit_user;

        //根据分工配置里的按我方公司分工的付款负责人，如果有多个算第一个
        $payment_manager_by = M('con_division_our_company', 'tb_')->where(['our_company_cd'=>$payment_info['payment_audit']['our_company_cd']])->getField('payment_manager_by');
        if (!empty($payment_manager_by)) {
            $payment_manager_by = explode(',', $payment_manager_by);
            $leader_str .= ','. $payment_manager_by[0];
        }

        if ($cd['ETC5']) $leader_str .= ',' . $cd['ETC5'];

        //自定义审批人中有创建人 截取创建人
        if (strpos(',' . $leader_str . ',', ',' . $_SESSION['m_loginname'] . ',') !== false) {
            $leader_str = trim(str_replace($_SESSION['m_loginname'] . ',', '', ',' . $leader_str . ','), ',');
        }
        //先截取再去重
        $leader_str = implode(',', array_filter(array_unique(explode(',', $leader_str))));

        return $leader_str;
    }

    public function getDeptByPaymentType($payment_type)
    {
        return CodeModel::getValue($payment_type);
    }

    /**
     * 一般付款单详情编辑
     * @param $payment_detail_info
     * @param $payment_audit_id
     * @throws Exception
     */
    public function generalPaymentDetailEditAll($payment_detail_info, $payment_audit_id = 0)
    {
        if (empty($payment_audit_id)) return false;
        $res_del_detail = $this->general_payment_detail_table->where(['payment_audit_id'=>$payment_audit_id])->delete();
        if ($res_del_detail === false) throw new Exception(L('保存失败:一般付款单详情删除失败'));
        $data = [];
        $payment_no = $this->createPaymentNO();
        foreach ($payment_detail_info as $item) {
            $general_payment['payment_audit_id']   = $payment_audit_id ? $payment_audit_id : $item['payment_audit_id'];
            $general_payment['payment_no']         = $payment_no;
            $general_payment['project_summary']    = $item['project_summary'] ? $item['project_summary'] : '';
            $general_payment['subdivision_type']   = $item['subdivision_type'] ? $item['subdivision_type'] : '';
            $general_payment['amount']             = $item['amount'] ? $item['amount'] : 0;
            $general_payment['vat_rate']           = $item['vat_rate'] ? $item['vat_rate'] : 0;
            $general_payment['subtotal']           = $item['subtotal'];
            $general_payment['invoice_attachment'] = !empty($item['invoice_attachment']) && $item['invoice_attachment'] ? json_encode($item['invoice_attachment']) : '';
            $general_payment['bill_attachment']    = !empty($item['bill_attachment']) && $item['bill_attachment'] ? json_encode($item['bill_attachment']) : '';
            $general_payment['other_attachment']   = !empty($item['other_attachment']) && $item['other_attachment'] ? json_encode($item['other_attachment']) : '';
            $general_payment['relation_bill_type'] = $item['relation_bill_type'];
            $general_payment['relation_bill_no']   = $item['relation_bill_no'];
            $general_payment['remark']             = $item['remark'] ? $item['remark'] : '';
            $general_payment['created_by']         = $payment_audit_data['updated_by'] = $this->user_name;
            $general_payment['created_at']         = dateTime();
            $data[] = $general_payment;
            $num = substr($payment_no,-3)+1;
            $payment_no = 'YF'.date('Ymd').substr(1000+$num,1);
        }
        $payment_audit_id = $this->general_payment_detail_table->addAll($data);
        if (!$payment_audit_id) throw new Exception(L('编辑一般付款单详情失败'));

        //$this->recordLog();
        return $payment_audit_id;
    }

    /**
     * 一般付款单详情编辑
     * @param $payment_detail_info
     * @param $payment_audit_id
     * @throws Exception
     */
    public function generalPaymentDetailEditAllNew($payment_detail_info, $payment_audit_id = 0)
    {
        if (empty($payment_audit_id)) return false;
        $res_detail = $this->general_payment_detail_table->field('id')->where(['payment_audit_id'=>$payment_audit_id])->select();
        $ids = array_column($res_detail, 'id');
        $add_payment_detail = $edit_payment_detail = $edit_ids = [];
        foreach ($payment_detail_info as $key => $item) {
            //非新增的付款明细
            if (!empty($item['general_detail_id']) && isset($item['general_detail_id'])) {
                //编辑的付款明细
                if (in_array($item['general_detail_id'], $ids)) {
                    $edit_payment_detail[] = $item;
                    $edit_ids[] = $item['general_detail_id'];
                    unset($payment_detail_info[$key]);
                }
            }
            //新增的付款明细
            if (empty($item['general_detail_id'])) {
                $add_payment_detail[] = $item;
                unset($payment_detail_info[$key]);
            }
        }
        //删除的付款明细id
        $del_ids = array_diff($ids, $edit_ids);
        if (!empty($del_ids)) {
            $res_del_detail = $this->general_payment_detail_table->where(['id'=> ['in', $del_ids]])->delete();
            if ($res_del_detail === false) throw new Exception(L('保存失败:一般付款单详情删除失败'));
        }
        //新增的付款明细
        if (!empty($add_payment_detail)) {
            $res = $this->generalPaymentDetailSaveAll($add_payment_detail, $payment_audit_id);
            if ($res === false) throw new Exception(L('保存失败:一般付款单详情新增失败'));
        }
        //编辑的付款明细
        if (!empty($edit_payment_detail)) {
            $data = [];
            foreach ($edit_payment_detail as $item) {
                $general_payment['id']                 = $item['general_detail_id'];
                $general_payment['payment_audit_id']   = $payment_audit_id ? $payment_audit_id : $item['payment_audit_id'];
                $general_payment['project_summary']    = $item['project_summary'] ? $item['project_summary'] : '';
                $general_payment['subdivision_type']   = $item['subdivision_type'] ? $item['subdivision_type'] : '';
                $general_payment['amount']             = $item['amount'] ? $item['amount'] : 0;
                $general_payment['vat_rate']           = $item['vat_rate'] ? $item['vat_rate'] : 0;
                $general_payment['subtotal']           = $item['subtotal'];
                $general_payment['actual_fee_applicant'] = isset($item['actual_fee_applicant']) ? $item['actual_fee_applicant'] : '';
                $general_payment['actual_fee_Department'] = isset($item['actual_fee_Department']) ? $item['actual_fee_Department'] : '';
                $general_payment['actual_fee_department_id'] = isset($item['actual_fee_department_id']) ? $item['actual_fee_department_id'] : '';
                // $general_payment['invoice_attachment'] = !empty($item['invoice_attachment']) && $item['invoice_attachment'] ? json_encode($item['invoice_attachment']) : '';
                // $general_payment['bill_attachment']    = !empty($item['bill_attachment']) && $item['bill_attachment'] ? json_encode($item['bill_attachment']) : '';
                // $general_payment['other_attachment']   = !empty($item['other_attachment']) && $item['other_attachment'] ? json_encode($item['other_attachment']) : '';
                $general_payment['relation_bill_type'] = $item['relation_bill_type'];
                $general_payment['relation_bill_no']   = $item['relation_bill_no'];
                $general_payment['remark']             = $item['remark'] ? $item['remark'] : '';
                $data[] = $general_payment;
            }
            $model = M('_general_payment_detail', 'tb_');
            $res = $this->saveAll($data, 'tb_general_payment_detail', $model);
            ELog::add(['info'=>'一般付款详情编辑记录','res'=>$res, 'request' => json_encode($payment_detail_info), 'data' => json_encode($data)],ELog::INFO);
            if ($res === false) throw new Exception(L('编辑一般付款单详情失败'));
        }

        //$this->recordLog();
        return $payment_audit_id;
    }

    /**
     * 批更新
     *
     * @param array $datas 需要更新的数据集合
     * @param object $model 模型
     * @param string $pk 主键
     *
     * @return string $sql
     */
    public function saveAll($datas, $model, $Models)
    {
        $sql = ''; //Sql
        $lists = []; //记录集$lists
        $pk = 'id';//获取主键
        foreach ($datas as $data) {
            foreach ($data as $key => $value) {
                if ($pk === $key) {
                    $ids[] = $value;
                } else {
                    $lists[$key] .= sprintf("WHEN %u THEN '%s' ", $data[$pk], $value);
                }
            }
        }
        foreach ($lists as $key => $value) {
            $sql .= sprintf("`%s` = CASE `%s` %s END,", $key, $pk, $value);
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s IN ( %s )', strtolower($model), rtrim($sql, ','), $pk, implode(',', $ids));
        if (empty($Models)) {
            $Models = M();
        }
        return $Models->execute($sql);
    }

    /**
     * 一般付款单详情
     * @param $payment_audit_id 付款单id
     * @return array
     */
    public function getGeneralPaymentDetail($payment_audit_id)
    {
        $field = "pa.*,pa.id as payment_audit_id,pa.status as payable_status,pa.billing_currency_cd,pa.source_cd,css.SP_NAME,
            oc.payment_manager_by,gp.id as general_payment_id,gp.*,gp.supplier supplier_id,
            pd.id as general_detail_id,pd.*,ab.open_bank,ab.account_class_cd,cc.CON_NAME contract_name,pa.commission_type as commission_type,gp.dept_id,hd.DEPT_NM dept_name,
            gp.invoice_attachment as invoice_attachment_new,gp.bill_attachment as bill_attachment_new,gp.other_attachment as other_attachment_new
            ";
        $db_res = $this->model->table('tb_pur_payment_audit pa')
            ->field($field)
            ->join('left join tb_general_payment gp on pa.id = gp.payment_audit_id')
            ->join('left join tb_general_payment_detail pd on pa.id = pd.payment_audit_id')
            ->join('left join tb_fin_account_bank ab on pa.payment_account_id = ab.id')
            ->join('left join tb_con_division_our_company oc on pa.our_company_cd = oc.our_company_cd')
            ->join('LEFT JOIN tb_crm_sp_supplier css ON css.ID = gp.supplier ')
            ->join('left join tb_crm_contract cc on cc.CON_NO = gp.contract_no and cc.CON_NO != "" and cc.CON_NO is not null')
            ->join('left join tb_hr_dept hd on hd.ID = gp.dept_id')
            ->where(['pa.id' => $payment_audit_id])->order('pa.updated_at desc')->select();
        if (empty($db_res)) {
            return [];
        }
        $db_res = CodeModel::autoCodeTwoVal($db_res, [
            'amount_currency',
            'our_company_cd',
            'payment_currency_cd',
            'payable_currency_cd',
            'company_code',
            'billing_currency_cd',
            'currency_code',
            'payment_channel_cd',
            'payment_way_cd',
            'platform_cd',
            'source_cd',
            'settlement_type',
            'procurement_nature',
            'invoice_information',
            'invoice_type',
            'bill_information',
            'payment_type',
            'subdivision_type',
            'relation_bill_type',
            'commission_type',
            'trade_type_cd',
            'account_currency',
            'account_type',
            'pay_com_cd'
        ]);
        $db_res = (new PurService())->orderStatusToVal($db_res);
        $is_optional_payment = $this->isOptionalPayment($db_res[0]['payment_type']);
        $data = [];
        
        array_map(function($value) use (&$data, $is_optional_payment) {
            //基本信息
            //付款状态映射
            if ($value['source_cd'] == 'N003010004' && $value['payable_status'] == 0) {
                $value['payable_status_val'] = '待提交';
            }
            if ($value['payable_status'] == TbPurPaymentAuditModel::$status_accounting_audit) {
                $current_audit_user = '('.$value['current_audit_user']. ')';
            } else {
                $current_audit_user = '';
            }
            $payment_type = $this->model->table('tb_ms_cmn_cd')->field("ETC2")->where(['CD' => $value['payment_type']])->find();
            $is_return = $this->getAuthByPaymentAuditId($value['payment_audit_id']);
            $is_show = $this->isShowByPaymentAudit($value);
            //付款单待审核
            $accounting_audit_user = $value['accounting_audit_user'];
            if ($value['payable_status'] == TbPurPaymentAuditModel::$status_accounting_audit) {
                $accounting_audit_user =
                    trim(str_replace('->' . $value['current_audit_user'] . '->', '->' . '<span style="color: red;">' . $value['current_audit_user'] . '</span>' . '->', '->' . $value['accounting_audit_user'] . '->'), '->');
            }
            $need_show = 'N';
            if ($value['snapshot_status'] == TbPurPaymentAuditModel::$status_after_payment) {
                $need_show = 'Y';
            }
            $data['base_info'] = [
                'need_show'               => $need_show,
                'payment_audit_id'        => $value['payment_audit_id'],
                'payment_audit_no'        => $value['payment_audit_no'],
                'payment_audit_no_old'    => $value['payment_audit_no_old'],
                'payable_status_val'      => $value['payable_status_val'],
                'payable_date_after'      => $value['payable_date_after'],
                'our_company_cd_val'      => $value['our_company_cd_val'],
                'payment_manager_by'      => $value['payment_manager_by'],
                'created_by'              => $value['created_by'],
                'created_at'              => $value['created_at'],
                'our_company_cd'          => $value['our_company_cd'],
                'source_cd_val'           => $value['source_cd_val'],
                'general_payment_id'      => $value['general_payment_id'],
                'payment_nature'          => $value['payment_nature'],
                'contract_information'    => $value['contract_information'],
                'payment_nature_val'      => $value['payment_nature'] == 1 ? '对公' : '对私',
                'contract_information_val' => $value['contract_information'] == 1 ? '有合同' : '无合同',
                'supplier_id'             => $value['supplier_id'],
                'supplier'                => $value['SP_NAME'],
                'contract_no'             => $value['contract_no'],
                'contract_name'           => $value['contract_name'],
                'settlement_type'         => $value['settlement_type'],
                'procurement_nature'      => $value['procurement_nature'],
                'settlement_type_val'     => $value['settlement_type_val'],
                'procurement_nature_val'  => $value['procurement_nature_val'],
                'invoice_information'     => $value['invoice_information'],
                'invoice_type'            => $value['invoice_type'],
                'invoice_information_val' => $value['invoice_information_val'],
                'invoice_type_val'        => $value['invoice_type_val'],
                'bill_information'        => $value['bill_information'],
                'bill_information_val'    => $value['bill_information_val'],
                'payment_type'            => $value['payment_type'],
                'payment_type_val'        => $value['payment_type_val'],
                'payment_type_comment2'   => $payment_type['ETC2'],
                'payment_currency_cd'     => $value['billing_currency_cd'] ? : $value['payment_currency_cd'],
                'payment_currency_cd_val' => $value['billing_currency_cd_val'] ? : $value['payment_currency_cd_val'],
                'actual_fee_applicant'    => $value['actual_fee_applicant'],
                'actual_fee_Department'   => $value['actual_fee_Department'],
                'actual_fee_department_id' => $value['actual_fee_department_id'],
                'applicant_manager_by'    => $value['current_audit_user'] ? : '', //当前审核负责人
                'payment_remark'          => $value['payment_remark'],
                'source_cd'               => $value['source_cd'],
                'current_audit_user'      => $current_audit_user,//当前审核负责人
                'accounting_audit_user'   => $accounting_audit_user, //审核负责人集合
                'is_return'               => $is_return, //【来源】=一般付款申请（采购&B2C退款除外）&【状态】=待确认付款是否展示退回按钮
                'dept_id'                 => !empty($value['dept_id']) ? $value['dept_id'] : '',
                'dept_name'               => $value['dept_name'],
                'receive_fail_reason'     => $value['receive_fail_reason'],
                'invoice_attachment'     => !empty($value['invoice_attachment_new']) ? json_decode($value['invoice_attachment_new'], 1) : [],
                'bill_attachment'     => !empty($value['bill_attachment_new']) ? json_decode($value['bill_attachment_new'], 1) : [],
                'other_attachment'     => !empty($value['other_attachment_new']) ? json_decode($value['other_attachment_new'], 1) : [],
               
            ];
            
            //应付明细信息
            $data['payable_info'][] = [
                'general_detail_id'      => $value['general_detail_id'],
                'payment_id'             => $value['payment_id'],
                'payment_no'             => $value['payment_no'],
                'project_summary'        => $value['project_summary'] ?: '',
                'subdivision_type_val'   => $value['subdivision_type_val'] ?: '',
                'subdivision_type'       => $value['subdivision_type'],
                'amount'                 => $value['amount'] ? number_format($value['amount'], 4) : '0.00',
                'vat_rate'               => $value['vat_rate'] ? number_format($value['vat_rate'], 4) : '0.00',
                'subtotal'               => $value['subtotal'] ? number_format($value['subtotal'], 4) : '0.00',
                'actual_fee_applicant'   => $value['actual_fee_applicant'],
                'actual_fee_Department'  => $value['actual_fee_Department'],
                'actual_fee_department_id' => $value['actual_fee_department_id'],
                'invoice_attachment'     => $value['invoice_attachment'],
                'bill_attachment'        => $value['bill_attachment'],
                'other_attachment'       => $value['other_attachment'],
                'relation_bill_type'     => $value['relation_bill_type'] ?: '',
                'relation_bill_type_val' => $value['relation_bill_type_val'] ?: '',
                'relation_bill_no'       => $value['relation_bill_no'],
            ];
            
            //支付信息
            $data['payment_info'] = [
                'payment_channel_cd_val' => $value['payment_channel_cd_val'],
                'payment_way_cd_val'     => $value['payment_way_cd_val'],
                'payment_channel_cd'     => $value['payment_channel_cd'],
                'payment_way_cd'         => $value['payment_way_cd'],
                'payable_amount_before'  => $value['payable_amount_before'] ?: '0.00',
                'payable_amount_after'   => $value['payable_amount_after'] ?: '0.00',
                'amount_currency_val'    => $value['amount_currency_val'] ? : $value['payable_currency_cd_val'],
                'payment_currency_cd'    => $value['payment_currency_cd'],
                'payment_currency_cd_val'=> $value['payment_currency_cd_val'],
                'trade_type_cd'          => $value['trade_type_cd'],
                'trade_type_cd_val'      => $value['trade_type_cd_val'],
                'commission_type_val'     => $value['commission_type_val'],
                'pay_type'                => TbPurPaymentAuditModel::$pay_type_map[$value['pay_type']],
            ];

            // 收款方账户/订单信息
            $data['receipt_info'] = [
                'supplier_collection_account' => $is_show ? $value['supplier_collection_account'] : '',
                'supplier_opening_bank'       => $is_show ? $value['supplier_opening_bank'] : '',
                'supplier_card_number'        => $is_show ? $value['supplier_card_number'] : '',
                'supplier_swift_code'         => $is_show ? $value['supplier_swift_code'] : '',
                'platform_cd'                 => $value['platform_cd'],
                'platform_cd_val'             => $value['platform_cd_val'],
                'store_name'                  => $value['store_name'],
                'platform_order_no'           => $value['platform_order_no'],
                'collection_account'          => $value['collection_account'],
                'collection_user_name'        => $value['collection_user_name'],
                'trade_no'                    => $value['trade_no'],
                'bank_settlement_code'        => $is_show ? $value['bank_settlement_code'] : '',
                'bank_address'                => $is_show ? $value['bank_address'] : '',
                'city'                        => $is_show ? $value['city'] : '',
                'bank_address_detail'         => $is_show ? $value['bank_address_detail'] : '',
                'bank_postal_code'            => $is_show ? $value['bank_postal_code'] : '',
                'account_currency'            => $is_show ? $value['account_currency'] : '',
                'account_currency_val'        => $is_show ? $value['account_currency_val'] : '',
                'account_type'                => $is_show ? $value['account_type'] : '',
                'account_type_val'            => $is_show ? $value['account_type_val'] : '',
                'commission_type'             => $value['commission_type'],
                'commission_type_val'         => $value['commission_type_val'],
                'bank_reference_no'           => $value['bank_reference_no'],
                'bank_payment_reason'         => $value['bank_payment_reason'],
            ];
            //账号公司归属为供应商 查询供应商数据
            //if ($value['account_class_cd'] == 'N003510002') {
                if (is_numeric($value['pay_com_cd'])) {
                    $value['pay_com_cd_val'] = $this->model->table('tb_crm_sp_supplier')->where(['ID' => $value['pay_com_cd']])->getField('SP_NAME');
                }
            //}
            //我方账户信息
            $data['our_account_info'] = [
                'payment_account'          => $value['payment_account'],
                'company_code_val'         => $value['company_code_val'],
                'open_bank'                => $value['open_bank'],
                'payment_our_bank_account' => $value['payment_our_bank_account'],
                'pay_com_cd'               => $value['pay_com_cd'],
                'pay_com_cd_val'           => $value['pay_com_cd_val'],
                'fund_allocation_contract_no' => $value['fund_allocation_contract_no'],
                'payment_account_id'       => $value['payment_account_id'],
                'account_class_cd'         => $value['account_class_cd'],
            ];

            if (in_array($value['payable_status'], [
                TbPurPaymentAuditModel::$status_no_billing,
                TbPurPaymentAuditModel::$status_finished,
                TbPurPaymentAuditModel::$status_kyriba_wait_receive,
                TbPurPaymentAuditModel::$status_kyriba_receive_failed,
            ])) {
                //提交付款信息
                $data['submit_payment_info'] = [
                    'payment_currency_cd'      => $value['billing_currency_cd'] ? : $value['payment_currency_cd'],
                    'payment_currency_cd_val'  => $value['billing_currency_cd_val'] ? : $value['payment_currency_cd_val'],
                    'payment_amount'           => $value['payment_amount'] ? : '0.00',
                    'payment_voucher'          => $value['payment_voucher'],
                ];
                //出账确认信息
                $data['billing_info'] = [
                    'payment_currency_cd'      => $value['billing_currency_cd'] ? : $value['payment_currency_cd'],
                    'payment_currency_cd_val'  => $value['billing_currency_cd_val'] ? : $value['payment_currency_cd_val'],
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
                'is_optional_payment'     => $is_optional_payment,//是否推kyriba，这是条件之一，还有结合其它的条件
            ];
            $data['remark_info'] = [
                'confirmation_remark' => $value['confirmation_remark']
            ];
        }, $db_res);
        #对历史数据进行兼容
        foreach ($data['base_info']['invoice_attachment'] as $key => $v) {
            if (!empty($v['saveName']) && empty($v['lastName'])) {
                $data['base_info']['invoice_attachment'][$key]['lastName'] = $v['saveName'];
            }
        }
        foreach ($data['base_info']['bill_attachment'] as $key => $v) {
            if (!empty($v['saveName']) && empty($v['lastName'])) {
                $data['base_info']['bill_attachment'][$key]['lastName'] = $v['saveName'];
            }
        }
        foreach ($data['base_info']['other_attachment'] as $key => $v) {
            if (!empty($v['saveName']) && empty($v['lastName'])) {
                $data['base_info']['other_attachment'][$key]['lastName'] = $v['saveName'];
            }
        }
        $return_reason = $this->general_payment_examine_table
            ->field('examine_by,return_reason,return_reason as return_reason_cd,cd.CD_VAL as return_reason,supply_note')
            ->where(['payment_audit_id'=>$payment_audit_id, 'status' => 1])
            ->join("left join tb_ms_cmn_cd cd ON cd.CD = tb_general_payment_examine.return_reason")
            ->order('id desc')->find();
        //审核信息
        $data['examine_info'] = !empty($return_reason) ? [
            'return_reason'   => $return_reason['return_reason'],
            'supply_note'     => $return_reason['supply_note'],
            'examine_by' => $return_reason['examine_by'],
        ] : [];
        return DataModel::formatAmount($data, 2);
    }

    /**
     * 一般付款单详情
     * @param $payment_audit_id 付款单id
     * @return array
     */
    public function getGeneralPayment($payment_audit_id)
    {
        $field = "pa.*,pa.id as payment_audit_id,pa.status as payable_status,pa.billing_currency_cd,pa.source_cd,gp.supplier supplier_id,css.SP_NAME,cc.CON_NAME contract_name,
           gp.id as general_payment_id,gp.*";
        $db_res = $this->model->table('tb_pur_payment_audit pa')
            ->field($field)
            ->join('left join tb_general_payment gp on pa.id = gp.payment_audit_id')
            ->join('LEFT JOIN tb_crm_sp_supplier css ON css.ID = gp.supplier ')
            ->join('tb_crm_contract cc on cc.CON_NO = gp.contract_no')
            ->where(['pa.id' => $payment_audit_id])->order('pa.updated_at desc')->find();
        if (empty($db_res)) {
            return [];
        }

        $payment_audit = $this->payment_audit_table->where(['id' => $payment_audit_id])->find();
        $general_payment = $this->general_payment_table->where(['payment_audit_id' => $payment_audit_id])->find();
        $payment_detail = $this->general_payment_detail_table->where(['payment_audit_id' => $payment_audit_id])->select();
        $payment_audit['payable_date'] = $payment_audit['payable_date_after'];
        $payment_audit['supplier_name'] = $db_res['SP_NAME'];
        $general_payment['contract_name'] = $db_res['contract_name'];
        $data['payment_audit'] = $payment_audit;
        $data['general_payment'] = $general_payment;
        $data['general_payment_detail'] = $payment_detail;
        return $data;
    }

    /**
     * 出账/出账确认提交
     * @param $request_data
     * @throws Exception
     */
    public function paymentSubmit($request_data) {
      
        $this->payment_audit_id = $request_data['payment_audit_id'];
        $this->updatePaymentBill($request_data);
        $pur_info = $this->repository->getPaymentAuditInfoByPaymentAuditIds($this->payment_audit_id, $request_data['already_billing']);//获取更新后的采购相关信息
       
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
                    $this->general_payment_detail_table
                        ->where(['payment_audit_id'=>$this->payment_audit_id])
                        ->save(['status'=>TbPurPaymentAuditModel::$status_kyriba_wait_receive]);
                } else {
                    $this->operation_info = '确认付款';
                    //不推kyriba
                    $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save(['pay_type'=>0]);
                }
                break;
            case 3:
                //付款核销选择已出账或待出账出账
                if (!$turnover_id = $this->thrTurnOver($request_data)) {
                    throw new \Exception(L('添加日记账失败'));
                }
                $rel_res = (new TbFinClaimModel())->addGeneralToTurnoverRelation($turnover_id, $pur_info);
                if (!$rel_res) {
                    throw new \Exception(L('添加日记账关联失败'));
                }
                $this->operation_info = '确认出账';
                break;
        }
      
        $field = "pa.*, gp.payment_type";
        $payment_info = $this->model->table('tb_pur_payment_audit pa')
            ->field($field)
            ->join('left join tb_general_payment gp on pa.id = gp.payment_audit_id')
            ->where(['pa.id' => $this->payment_audit_id])
            ->find();
        $log_model = new TbPurPaymentAuditLogModel();
        //获取已完成的付款单日志创建人
        $finish_log_user = $log_model->where(['payment_audit_id' => $this->payment_audit_id, 'status_name' => '已完成'])->order('created_at desc, id desc')->getField('created_by');
        //一般付款手动触发已完成需要发送邮件  10952 一般付款表单结构调整 触发用户为 空字符串则为自动
        if ($request_data['type'] == 3 && $finish_log_user != '') {
            //一般付款已出账确认并且不推kyriba触发邮件需要发送邮件
            $this->sendPaidEmail($payment_info, A('OrderDetail')->paid_email_content(APP_PATH. 'Tpl/Home/Finance/paid_general_email.html'));
        }
        if (!$request_data['is_kyriba']) {
            //kyriba处理不记出账日志
            $this->setParameters($pur_info, $payment_info['status']);
            $this->recordLog();
        }
    }


    /**
     * 付款单更新
     * @param $request_data
     * @throws Exception
     */
    private function updatePaymentBill($request_data)
    {
        $payment_audit_id    = $request_data['payment_audit_id'];
        $payment_audit_table = $this->payment_audit_table;
        //$pur_info = $this->repository->getOrderInfoByPaymentAuditIds($payment_audit_id);//获取采购相关信息
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
                //commission_type 保存手续费承担方式
                /*$save['commission_type'] = $request_data['commission_type'];
                $res = $this->general_payment_table->where(['payment_audit_id' => $request_data['payment_audit_id']])->save($save);
                if ($res === false) {
                    throw new \Exception(L('一般付款单更新失败'));
                }*/
                $this->updatePayableBillStatus($status);//更新应付单状态
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
                //$billing_exchange_rate = exchangeRateConversion(cdVal($billing_currency_cd), cdVal($pur_info[0]['amount_currency']), date('Ymd'));
                $payment_audit_table->billing_at = dateTime();
                $payment_audit_table->billing_by = $this->user_name;
                $payment_audit_table->status = $status;
                //$payment_audit_table->billing_exchange_rate = $billing_exchange_rate;
                $payment_audit_table->billing_currency_cd = $billing_currency_cd;
                $payment_audit_table->trade_type_cd = $request_data['trade_type_cd'];
                $payment_audit_table->commission_type = $request_data['commission_type'];
                $payment_audit_table->pay_com_cd = $request_data['pay_com_cd'];
                $payment_audit_table->fund_allocation_contract_no = $request_data['fund_allocation_contract_no']; //资金调配合同编号
                if (!$request_data['is_import']) {
                    $payment_audit_table->payment_our_bank_account = $request_data['payment_our_bank_account'];
                    $payment_audit_table->payment_account_id = $request_data['payment_account_id'];
                }
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
                break;
        }
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
        $pur_info = $this->repository->getPaymentAuditInfoByPaymentAuditIds($this->payment_audit_id);//获取更新后的采购相关信息
        foreach ($request_data as &$item) {
            $item['payment_audit_id'] = $item['id'];
            unset($item['id']);
            if (!$turnover_id = $this->thrTurnOver($item)) {
                throw new \Exception(L('添加日记账失败'));
            }
            $rel_res = (new TbFinClaimModel())->addGeneralToTurnoverRelation($turnover_id, $pur_info);
            if (!$rel_res) {
                throw new \Exception(L('添加日记账关联失败'));
            }
        }
        $this->operation_info = '一般付款确认出账';
        $this->setParameters($pur_info, TbPurPaymentAuditModel::$status_finished);
        $this->recordLog();
    }

    private function batchUpdatePaymentBill($data)
    {
        $excel_model         = new TempImportExcelModel();
        $payment_audit_model = new TbPurPaymentAuditModel();

        //出账
        $status = TbPurPaymentAuditModel::$status_finished;
        foreach ($data as &$item) {
            $item['billing_at'] = dateTime();
            $item['billing_by'] = $this->user_name;
            $item['status']     = $status;
            $item['pay_type']   = 0;
        }
        $res = $payment_audit_model->execute($excel_model->saveAll($data, $payment_audit_model, $pk = 'id'));
        if (false === $res) {
            throw new \Exception(L('确认失败'));
        }
    }


    //日记账
    private function thrTurnOver($data)
    {
        $billing_info = $data['already_billing'];
        unset($data['already_billing']);
        $billing_info = array_merge((array)$billing_info, $data);
        $vouchers = [];
        if (!is_array($billing_info['billing_voucher'])) {
            $billing_info['billing_voucher'] = json_decode($billing_info['billing_voucher'], true);
        }
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
        $general_payment = $this->general_payment_table->where(['payment_audit_id' => $billing_info['payment_audit_id']])->find();
        if (empty($general_payment)) {
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

    /**
     * 更新付款单状态
     * @param $params
     * @param $type
     * @throws Exception
     */
    public function updatePaymentBillStatus($params, $type = 0)
    {
        $payment_info = $this->payment_audit_table->where(['id'=>['in',$params['payment_audit_id']]])->find();
        $res = $this->payment_audit_table->where(['id'=>['in',$params['payment_audit_id']]])->save(['status'=>$params['status']]);
        $this->payment_audit_id = $params['payment_audit_id'];
        if ($params['status'] == 4) {
            $this->operation_info = '提交付款单';
            //提交付款单申请
            //企业微信审批
            $payment_info['payment_audit_id'] = $params['payment_audit_id'];
            if ($payment_info['snapshot_status'] && $payment_info['snapshot_audit_user']) {
                $payment_info = $this->checkReturnInfo($payment_info);
                // 消息发送通知
                $this->sendMsg($payment_info['snapshot_status'], $payment_info['payment_audit_id']);
            }
            $payment_info['submit_type'] = 2;
            $payment_info['current_leader'] = $payment_info['current_audit_user'];
            $wx_return_res = $this->bulidGeneralWechatApproval($payment_info);
        } else if ($params['status'] == 5) {
            $this->operation_info = '删除付款单';
        } else {
            $this->operation_info = '撤回';
        }
        $this->status_name   = TbPurPaymentAuditModel::$status_map[$params['status']];
        if ($type) {
            $this->recordLog();
        }
        if (!$res) throw new \Exception(L('更新付款单状态失败'));
    }

    public function sendMsg($status, $id)
    {
        // 根据状态发送不同的消息
        $type = TbPurPaymentAuditModel::$return_type_before_confirmed;
        if ($status == TbPurPaymentAuditModel::$status_after_payment) {
            $type = TbPurPaymentAuditModel::$return_type_after_confirmed;
        }
        (new FinanceNotifyService())->send($id, $type);
    }

    //权限为付款单对应我方公司在配置管理-分工配置-按我方公司分工-付款负责人里的用户，如果有多个则都有权限
    public function getAuthByPaymentAuditId($payment_audit_id)
    {
        $division_our_company = $this->model->table('tb_pur_payment_audit pa')
            ->field("oc.payment_manager_by")
            ->join('left join tb_con_division_our_company oc on pa.our_company_cd = oc.our_company_cd')
            ->where(['pa.id' => $payment_audit_id])->find();
        $is_return = false;
        //权限为付款单对应我方公司在配置管理-分工配置-按我方公司分工-付款负责人里的用户，如果有多个则都有权限
        if (!empty($division_our_company) && strpos(',' . $division_our_company['payment_manager_by'] . ',', ',' . $_SESSION['m_loginname'] . ',') !== false) {
            $is_return = true;
        }
        return $is_return;
    }

    //权限为付款单对应我方公司在配置管理-分工配置-按我方公司分工-付款负责人里的用户，如果有多个则都有权限
    public function isShowByPaymentAudit($payment_audit)
    {
        $baseModel = new Model();
        $payment_type_cd = $baseModel->table('tb_ms_cmn_cd')->where('CD = "%s"', $payment_audit['payment_type'])->find();
        $is_return = true;
        //权限为付款单对应我方公司在配置管理-分工配置-按我方公司分工-付款负责人里的用户，如果有多个则都有权限
        if ($payment_audit['payment_channel_cd'] == 'N001000301' && $payment_type_cd['ETC2'] == '员工个人账户') {
            $is_return = false;
        }
        return $is_return;
    }

    //权限:除了配置管理-分工配置-按公司分工-付款负责人里配置的用户，其他用户进入付款单列表，默认筛选【创建人】=当前登录用户
    public function getAuthByUserId()
    {
        $division_our_company = $this->model->table('tb_con_division_our_company')
            ->field("id")
            ->where(['payment_manager_by' => ['like', '%' . $_SESSION['m_loginname'] . '%']])->find();
        $is_all = false;
        if (!empty($division_our_company)) $is_all = true;
        return $is_all;
    }


    public function checkReturnInfo($payment_info)
    {
        if ($payment_info['snapshot_status'] && $payment_info['snapshot_audit_user']) {   
            $payment_info['current_audit_user'] = $payment_info['snapshot_audit_user'];
            $payment_info['status'] = $payment_info['snapshot_status'];
            $status = $payment_info['snapshot_status'];
            if ($payment_info['snapshot_status'] == TbPurPaymentAuditModel::$status_after_payment) {
                $payment_info['status'] = TbPurPaymentAuditModel::$status_no_payment;
                $status = TbPurPaymentAuditModel::$status_no_payment;
            }       
            $this->payment_audit_table->where(['id'=>$payment_info['id']])->save(['current_audit_user' => $payment_info['snapshot_audit_user'], 'status' => $status]);
        }
        return $payment_info;
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
        if (!in_array($payment_audit_info['status'], [TbPurPaymentAuditModel::$status_no_payment,TbPurPaymentAuditModel::$status_accounting_audit])) {
            throw new \Exception(L('付款状态异常'));
        }
        //权限为付款单对应我方公司在配置管理-分工配置-按我方公司分工-付款负责人里的用户，如果有多个则都有权限
        $is_return_auth = $this->getAuthByPaymentAuditId($request_data['payment_audit_id']);
        if (!$is_return_auth && $payment_audit_info['status'] == TbPurPaymentAuditModel::$status_no_payment) {
            throw new \Exception(L('没有待付款退回权限'));
        }
        if (!in_array($status, [TbPurPaymentAuditModel::$status_no_payment,TbPurPaymentAuditModel::$status_no_confirmed])) {
            throw new \Exception(L('状态异常'));
        }

        $payment_info = $this->general_payment_detail_table
            ->field('id,payment_no')
            ->where(['payment_audit_id' => $this->payment_audit_id])
            ->select();
        if (empty($payment_info)) throw new \Exception(L('未找到应付单'));

        $this->setParameters($payment_info, TbPurPaymentAuditModel::$status_accounting_audit);

        $audit_users = explode('->', $payment_audit_info['accounting_audit_user']);//审核人集合
        $current_audit_user = $payment_audit_info['current_audit_user'];//当前审核人
        $current_audit_key = array_search($current_audit_user, $audit_users);
        $final_audit_user = $audit_users[count($audit_users) - 1];//最终审核人
        $operation_str = $current_audit_user == 'Astor.Zhang' ? '会计审核' : '审核';
        if ($current_audit_user != $final_audit_user) {
            //付款退回优化
            if ($status == TbPurPaymentAuditModel::$status_no_confirmed) {
                //退回
                /*if ($current_audit_key > 0) {
                    $current_audit_key = $current_audit_key - 1;
                } else {
                    $this->setParameters($payment_info, TbPurPaymentAuditModel::$status_no_confirmed);
                    //第一个审核人退回设置状态
                    $res = $this->payment_audit_table->where(['id' => $this->payment_audit_id])->save([
                        'status'     => TbPurPaymentAuditModel::$status_no_confirmed,
                        'updated_by' => $this->user_name,
                    ]);
                    if (!$res) throw new \Exception(L($operation_str . '退回失败'));
                    $this->cancelConfirm($accounting_return_reason, $supply_note);//应付单撤回到待确认
                }*/
                //退回
                $this->setParameters($payment_info, TbPurPaymentAuditModel::$status_no_confirmed);
                //第一个审核人退回设置状态
                $res = $this->payment_audit_table->where(['id' => $this->payment_audit_id])->save([
                    'status'     => TbPurPaymentAuditModel::$status_no_confirmed,
                    'updated_by' => $this->user_name,
                ]);
                if (!$res) throw new \Exception(L($operation_str . '退回失败'));
                $this->cancelConfirm($accounting_return_reason, $supply_note);//应付单撤回到待确认
                //$current_audit_user = $audit_users[$current_audit_key] ? : $audit_users[0];
                //退回到第一个审核人
                $current_audit_user = $audit_users[0];
                $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save(['current_audit_user' => $current_audit_user]);
            } else {
                //审核通过
                $current_audit_user = $audit_users[$current_audit_key + 1];
                $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save(['current_audit_user' => $current_audit_user]);
            }
            //日志记录参数
            $payment_no_str = implode(',', array_column($payment_info, 'payment_no'));
            if ($is_return) {
                $this->operation_info = $operation_str . '退回';
                $this->remark         = $payment_no_str;
                $this->addExamineReason($accounting_return_reason, $supply_note);
            } else if ($status == TbPurPaymentAuditModel::$status_no_payment) {
                $this->operation_info = $operation_str . '通过';
            } else {
                $this->operation_info = $operation_str . '撤回';
                $this->remark         = $payment_no_str;
            }
        } else {
            //审核最后一步
            //日志记录参数
            if (count($audit_users) == 1) {
                $payment_no_str = implode(',', array_column($payment_info, 'payment_no'));
                if ($status == TbPurPaymentAuditModel::$status_no_confirmed) {
                    //审核人只有冷安一个人，退回处理
                    $this->setParameters($payment_info, TbPurPaymentAuditModel::$status_no_confirmed);
                    //第一个审核人退回设置状态
                    $res = $this->payment_audit_table->where(['id' => $this->payment_audit_id])->save([
                        'status'     => TbPurPaymentAuditModel::$status_no_confirmed,
                        'updated_by' => $this->user_name,
                    ]);
                    if (!$res) throw new \Exception(L($operation_str . '退回失败'));
                    $this->cancelConfirm($accounting_return_reason, $supply_note);//应付单撤回到待确认
                } else {
                    $this->setParameters($payment_info, $status);
                    $res = $this->payment_audit_table->where(['id' => $this->payment_audit_id])->save([
                        'status'     => $status,
                        'updated_by' => $this->user_name,
                    ]);
                    if (!$res) throw new \Exception(L($operation_str . '失败'));
                    $this->updatePayableBillStatus($status);//更新应付单状态
                }
            } else {
                $payment_no_str = implode(',', array_column($payment_info, 'payment_no'));
                if ($status == TbPurPaymentAuditModel::$status_no_confirmed) {
                    //退回到上一个审核人
                    //$current_audit_user = $audit_users[$current_audit_key] ? : $audit_users[0];
                    //退回到第一个审核人
                    $current_audit_user = $audit_users[0];
                    $res = $this->payment_audit_table->where(['id' => $this->payment_audit_id])->save([
                        'current_audit_user' => $current_audit_user,
                        'status'     => TbPurPaymentAuditModel::$status_no_confirmed,
                        'updated_by' => $this->user_name,
                    ]);
                    if (!$res) throw new \Exception(L($operation_str . '退回失败'));
                    $this->cancelConfirm($accounting_return_reason, $supply_note);//应付单撤回到待确认
                } else {
                    $this->setParameters($payment_info, $status);
                    $res = $this->payment_audit_table->where(['id' => $this->payment_audit_id])->save([
                        'status'     => $status,
                        'updated_by' => $this->user_name,
                    ]);
                    if (!$res) throw new \Exception(L($operation_str . '失败'));
                    $this->updatePayableBillStatus($status);//更新应付单状态
                }
            }
            if ($is_return) {
                $this->operation_info = $operation_str . '退回';
                $this->remark         = $payment_no_str;
                $this->addExamineReason($accounting_return_reason, $supply_note);
            } else if ($status == TbPurPaymentAuditModel::$status_no_payment) {
                $this->operation_info = $operation_str . '通过';
            } else {
                $this->operation_info = $operation_str . '撤回';
                $this->remark         = $payment_no_str;
            }
        }
        $this->recordLog();
    }

    /**
     *设置参数
     * @param $payment_info
     * @param $status
     */
    public function setParameters($payment_info, $status)
    {
        $this->payment_ids   = array_unique(array_column($payment_info, 'id'));
        if ($status == TbPurPaymentAuditModel::$status_no_confirmed) {
            $this->status_name   = '待提交';
        } else {
            $this->status_name   = TbPurPaymentAuditModel::$status_map[$status];
        }
    }

    /**
     * 应付单撤回到待确认
     * @param $payment_info
     *  @param $accounting_return_reason 会计退回原因
     *  @param $supply_note 会计退回原因
     * @throws Exception
     * @throws Exception
     */
    public function cancelConfirm($accounting_return_reason = null, $supply_note = null) {
        $save['status']             = TbPurPaymentAuditModel::$status_no_confirmed;
        /*$save['project_summary']    = '';
        $save['subdivision_type']   = '';
        $save['amount']             = 0;
        $save['vat_rate']           = 0;
        $save['subtotal']           = 0;
        $save['invoice_attachment'] = '';
        $save['bill_attachment']    = '';
        $save['other_attachment']   = '';
        $save['relation_bill_type'] = '';
        $save['relation_bill_no']   = '';
        $save['remark']             = '';*/
        $save['update_time']        = dateTime();
        $res = $this->general_payment_detail_table->where(['id'=>['in',$this->payment_ids]])->save($save);
        if (false === $res) {
            throw new \Exception(L('清空确认信息失败'));
        }
//        $examine['payment_audit_id'] = $this->payment_audit_id;
//        $examine['return_reason']    = $accounting_return_reason;
//        $examine['supply_note']      = $supply_note;
//        $examine['examine_by']       = $_SESSION['m_loginname'];;
//        $examine['created_at']       = dateTime();
//        $res = $this->general_payment_examine_table->add($examine);
//        if (false === $res) {
//            throw new \Exception(L('审核信息保存失败'));
//        }
    }

    /**
     * 更新应付单状态
     * @param $status
     * @throws Exception
     */
    public function updatePayableBillStatus($status)
    {
        $res = $this->general_payment_detail_table->where(['payment_audit_id'=>['in',$this->payment_audit_id]])->save(['status'=>$status]);
        if ($res === false) throw new \Exception(L('更新应付单状态失败'));
    }

    // 退回/撤回记录退回前状态和操作人
    public function recordReturnInfo($request_data, $map = [])
    {
        if (empty($request_data['payment_audit_id'])) {
            throw new \Exception(L("参数缺失payment_audit_id{$request_data['payment_audit_id']}"));
        }
        $payment_info = M('payment_audit', 'tb_pur_')->where(['id'=>['in',$request_data['payment_audit_id']]])->find();
        if (empty($payment_info)) {
            throw new \Exception(L("该付款单信息为空{$request_data['payment_audit_id']}"));
        }
        if (empty($map)) { 
            $map = ['id'=>$request_data['payment_audit_id']];
        }
        $save['snapshot_status'] = $request_data['status'] ? $request_data['status'] : $payment_info['status']; // 待付款（含）之前用数据表具体状态记录（待审核，待付款），之后操作退回的，统一用传参状态值即可，因为后面不管是付款失败还是kyriba接受失败等都跳到待确认付款账户状态
        $save['snapshot_audit_user'] = $payment_info['current_audit_user'] ? $payment_info['current_audit_user'] : $this->user_name;
        $res = $this->payment_audit_table->where($map)->save($save); 
        if ($res === false) {
            $lastSql = M()->_sql();
            $dbError = M()->getDbError();
            @SentinelModel::addAbnormal('付款单审批流程撤回', '撤回保存快照失败', [$request_data, $map, $save, $lastSql, $dbError],'fin_flow_notice');
        }
        if ($res === false) throw new \Exception(L('更新应付单快照信息状态失败'));
    }

    /**
     * 撤回到待会计审核
     * @param $request_data
     * @throws Exception
     */
    public function returnToAccountingAudit($request_data) {
        $this->payment_audit_id = $request_data['payment_audit_id'];

        $payment_info = $this->general_payment_detail_table->field('id,payment_no')
            ->where(['payment_audit_id'=>$this->payment_audit_id])->select();

        $payment_audit = $this->payment_audit_table->where(['id'=>['in',$request_data['payment_audit_id']]])->find();
        $this->operation_info = '撤回到待提交';
        $status = TbPurPaymentAuditModel::$status_no_confirmed;
        //审核信息不展示
        $this->general_payment_examine_table
            ->where(['payment_audit_id'=>$request_data['payment_audit_id']])
            ->save(['status' => 2]);

        $this->setParameters($payment_info, $status);
        $this->updatePayableBillStatus($status);//更新应付单状态
        $request_data['status'] = $status;
        $this->updatePaymentBillStatus($request_data);//更新付款单状态
        //重置当前审核人
        $this->payment_audit_table->where(['id'=>['in',$request_data['payment_audit_id']]])->save([
            'current_audit_user' => explode('->',$payment_audit['accounting_audit_user'])[0]
        ]);
        $this->recordLog();
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
            'fund_allocation_contract_no' => '',
//            'payment_currency_cd'      => '',
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
                (new TbWmsAccountTurnoverModel())->deleteGeneralTurnover($payment_audit_info['payment_audit_no']);
            }
        }
        $res_payment = $this->payment_audit_table->where(['id'=>$payment_audit_id])->save($save);
        if(!$res_payment) {
            throw new \Exception(L('保存失败'));
        }

        $this->updatePayableBillStatus($status);//更新应付单状态
        $payment_info  = $this->general_payment_detail_table->where(['payment_audit_id'=>$payment_audit_id])->select();
        $this->setParameters($payment_info, $status);

        $relevance_l = D('Purchase/Relevance','Logic');
        foreach ($payment_info as $payment) {
            /*$res_relevance = $relevance_l->flashPaymentStatus($payment['relevance_id']);
            if (!$res_relevance) {
                throw new \Exception(L($relevance_l->getError()));
            }*/
        }
        $this->returnToPaymentConfirmEmail($payment_audit_info, $reason);
        $this->operation_info = '撤回到待付款';
        $this->recordLog();
    }

    /**
     * 付款单撤回邮件发送
     * @param $payment_audit_info 付款单信息
     * @param $reason
     * @return bool
     */
    public function returnToPaymentConfirmEmail($payment_audit_info, $reason) {
        $to                     = $payment_audit_info['created_by']. '@gshopper.com';
        $cc                     = C('finance_email');
        $tittle                 = "付款单：{$payment_audit_info['payment_audit_no']}付款已撤回";
        $return_time            = date('Y-m-d H:i:s');
        $payment_submit_time    = $payment_audit_info['payment_at'] ? : $payment_audit_info['billing_at'];
        $content = <<<EOF
付款单：{$payment_audit_info['payment_audit_no']}付款已撤回<br/>
付款操作时间：{$payment_submit_time}<br/>
撤回操作时间：$return_time<br/>
撤回操作人：{$_SESSION['m_loginname']}<br/>
撤回原因：{$reason}<br/>
EOF;
        $email_m = new SMSEmail();
        $res = $email_m->sendEmail($to, $tittle, $content, $cc);
        if (!$res) {
            @SentinelModel::addAbnormal('付款单付款撤回', '发送邮件异常', [$to, $content, $payment_audit_info],'pur_notice');
        }
        return $res;
    }

    //日志记录
    public function recordLog()
    {
        $date = dateTime();
        $status_name = $this->status_name;
        //一般付款状态映射
        if ($status_name == '待确认') {
            $status_name = '待提交';
        }
        TbPurPaymentAuditLogModel::recordLog($this->payment_audit_id, $this->operation_info, $date, $status_name, $this->remark);
        TbPurPaymentLogModel::recordLog($this->payment_ids, $this->operation_info, $date, $status_name, $this->remark);
    }

    public function addExamineReason ($reason, $supply_note)
    {
        $examine['payment_audit_id'] = $this->payment_audit_id;
        $examine['return_reason']    = $reason;
        $examine['supply_note']      = $supply_note;
        $examine['examine_by']       = $_SESSION['m_loginname'];;
        $examine['created_at']       = dateTime();
        $this->general_payment_examine_table->add($examine);
    }

    private function kyribaPayPush()
    {
        $filed = [
            'gp.payment_type',//付款类型
            'cd.ETC2 as person_account',//员工个人账户
            'cd.ETC3 as main_type',//一般付款/Kyriba维护收款方账户付款等
            'pa.*',
            'ab.company_code',
            'ab.open_bank',
            'ab.account_bank',
            'ab.swift_code',
            'ab.bank_short_name',
            'cs.RES_NAME',
//            'ss.BANK_SETTLEMENT_CODE as bank_settlement_code',//本地结算代码
//            'ss.CITY as city',//收款银行地址
            'pa.account_currency as collection_currency',//收款币种
            'ab.currency_code as payment_currency', //付款币种,
            'gp.payment_nature'//付款性质1:对公,2:对私
        ];
        $row = $this->model->table('tb_pur_payment_audit pa')
            ->field($filed)
            ->join('left join tb_general_payment gp on pa.id = gp.payment_audit_id')
            ->join('tb_crm_sp_supplier ss ON gp.supplier = ss.ID')
//            ->join('tb_crm_sp_supplier ss ON gp.supplier = ss.SP_NAME')//暂时改成供应商名称匹配
            ->join('left join tb_crm_site cs on ss.SP_ADDR1 = cs.ID')
            ->join('left join tb_fin_account_bank ab on pa.payment_account_id = ab.id')
            ->join('left join tb_ms_cmn_cd cd on cd.CD = gp.payment_type')
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
       
        #对私  收款人二字码等于收款银行国家二字码
        if($row['payment_nature'] == 2){
            $row['country_short_name'] = $row['bank_country_short_name'];
        }
        
        if (false === $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save(['pay_type'=>0])) {
            throw new Exception(L('更新支付类型失败'));
        }
        if (strtoupper($row['bank_short_name']) != 'CITI') {
            throw new Exception(L('不是CITI禁止推kyriba'));
        }
        if (!$this->isOptionalPayment($row['payment_type'])) {
            throw new Exception(L('推kyriba条件验证不通过'));
        }
        $this->payment_audit_table->where(['id'=>$this->payment_audit_id])->save(['pay_type'=>1]);
        if (trim($row['person_account']) == '员工个人账户') {
            //实际费用申请人从一般付款详情列表取 第一个
            $actual_fee_applicant = $this->general_payment_detail_table->where(['payment_audit_id'=>$this->payment_audit_id])->getField('actual_fee_applicant');
            $row['supplier_collection_account'] = M('hr_card', 'tb_')->where(['ERP_ACT'=>$actual_fee_applicant])->getField('WORK_NUM');
        }
        (new KyribaService())->putXmlToFtp($row, TbPurPaymentAuditModel::$source_general_payable);
    }

    //判断是否可选kyriba支付与否
    private function isOptionalPayment($payment_type)
    {
        $etc3 = CodeModel::getValue($payment_type)['ETC3'];
        if ($etc3 == '不对接') {
            return false;
        }
        return true;
    }

    //kyriba重新提交，生成新的付款单
    public function renewCreatePaymentBill ($payment_audit_id)
    {
        $payment_audit_no_new = 'FK' . date(Ymd) . TbWmsNmIncrementModel::generateNo('payment_audit');
        $payment_info = $this->payment_audit_table->find($payment_audit_id);
        $old_audit_no = $payment_info['payment_audit_no'];
        unset($payment_info['id']);
        $payment_info['payment_audit_no'] = $payment_audit_no_new;
        $payment_info['status'] = 0;
        $payment_info['created_at'] = dateTime();
        $payment_info['updated_at'] = dateTime();
        $payment_info['deleted_by'] = null;
        $payment_info['deleted_at'] = null;
        $payment_info['payment_audit_no_old'] = $old_audit_no;
        $payment_info['snapshot_status'] = TbPurPaymentAuditModel::$status_after_payment; 
        $payment_info['snapshot_audit_user'] = $payment_info['current_audit_user'];
        if (!$new_id = $this->payment_audit_table->add($payment_info)) {
            throw new Exception(L('新生成付款单失败'));
        }
        $general_res = $this->general_payment_table->where(['payment_audit_id'=>$payment_audit_id])->save(['payment_audit_id'=>$new_id]);
        if (false === $general_res) {
            throw new Exception(L('一般付款单关联新付款单id失败'));
        }
        $general_detail_res = $this->general_payment_detail_table->where(['payment_audit_id'=>$payment_audit_id])->save(['payment_audit_id'=>$new_id]);
        if (false === $general_detail_res) {
            throw new Exception(L('一般付款单详情关联新付款单id失败'));
        }
        $examine_detail_res = $this->general_payment_examine_table->where(['payment_audit_id'=>$payment_audit_id])->save(['payment_audit_id'=>$new_id]);
        if (false === $examine_detail_res) {
            throw new Exception(L('一般付款单审核关联新付款单id失败'));
        }
        $this->operation_info = '申请一般付款';
        $this->payment_audit_id = $new_id;
        $this->status_name   = TbPurPaymentAuditModel::$status_map[TbPurPaymentAuditModel::$status_no_confirmed];
        $this->recordLog();
        return $payment_audit_no_new;
    }

    public function sendPaidEmail($info,$html) {
        $recipient = $info['created_by'];//收件人
        //实际费用申请人从一般付款详情列表取 邮件发送取所有最小一级部门领导抄送
        $general_payment_detail = $this->general_payment_detail_table->where(['payment_audit_id'=>$info['id']])->select();
        $cc_s = [];
        foreach ($general_payment_detail as $key => $value) {
            $dept_id = array_pop(explode(',', $value['actual_fee_department_id']));
            $cc = TbHrDeptModel::getDeptLeaderNew($dept_id);
            if (!empty($cc)) {
                $cc = explode(',', $cc);
            } else {
                $cc = [];
            }
            $cc[] = $value['actual_fee_applicant'];
            $cc_s = array_merge($cc_s, $cc);
        }
        $cc = array_unique($cc_s);//抄送人集合
        if (in_array($recipient, $cc)) {
            unset($cc[array_search($recipient, $cc)]);
        }
        $cc = array_filter($cc);
        if (!empty($cc)) {
            $cc = array_filter($cc, function ($v) {
                if (M('admin', 'bbm_')->where(['M_NAME' => $v])->find()) {
                    return true;
                }
                return false;
            });
        }
        $cc_set = [];
        $recipient = $recipient. '@gshopper.com';
        if (!empty($cc)) {
            $cc_set =array_map(function($value) {
                return $value.'@gshopper.com';
            }, $cc);
        }
        $str_arr = [
            '{payment_audit_no}'        => $info['payment_audit_no'],
            '{payment_type}'            => cdVal($info['payment_type']),
            '{payable_amount_after}'    => number_format($info['payable_amount_after'], 2),
            '{payable_currency_cd_val}' => cdVal($info['payment_currency_cd']),//付款币种
            '{billing_date}'            => $info['billing_date'],
        ];
        $content     = strtr($html, $str_arr);
        $title       = "Payment order {$info['payment_audit_no']} paid notice:";
        $fileModel        = new FileDownloadModel();
        $file_attach = $info['status'] == TbPurPaymentAuditModel::$status_no_billing ? $info['payment_voucher'] : $info['billing_voucher'];
        $file_attach = json_decode($file_attach, true);
        foreach ($file_attach as $v) {
            if($v) {
                $fileModel->fname = $v['save_name'];
                $file = $fileModel->getFilePath();
                if (is_file($file)) {
                    $attachment[] = $file;
                }
            }
        }
        $email = new SMSEmail();
        $res = $email->sendEmail($recipient,$title,$content,$cc_set,$attachment);
        if(!$res) {
            @SentinelModel::addAbnormal('一般付款单', '发送邮件异常', [$recipient,$title, $content, $cc_set, $attachment, $res],'pur_notice');
        }
        return $res;
    }
}