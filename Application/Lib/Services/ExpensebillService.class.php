<?php
class ExpensebillService extends Service
{

    public  $out_stock_cd;
    public  $in_stock_cd;
    public  $wms_operating_cd;
    public  $wms_added_service_cd;
    public  $wms_outbound_cost_cd;
    public  $wms_head_logistics_cd;
    public  $wms_insurance_cd;
    public  $wms_shelf_cost_cd;


    public function __construct()
    {
        $this->out_stock_cd = 'N002870019';
        $this->in_stock_cd = 'N002870020';
        $this->wms_operating_cd = 'N001950601';
        $this->wms_added_service_cd = 'N001950602';
        $this->wms_outbound_cost_cd = 'N001950605';
        $this->wms_head_logistics_cd = 'N001950606';
        $this->wms_insurance_cd = 'N001950603';
        $this->wms_shelf_cost_cd = 'N001950604';
    }

    public function createPaymentNO()
    {
        $pre_payment_no = M('payment', 'tb_wms_')->lock(true)->where(['payment_no' => ['like', 'YF' . date('Ymd') . '%']])->order('id desc')->getField('payment_no');
        if ($pre_payment_no) {
            $num = substr($pre_payment_no, -3) + 1;
        } else {
            $num = 1;
        }
        $payment_no = 'YF' . date('Ymd') . substr(1000 + $num, 1);
        return $payment_no;
    }

    /**
     *  调拨单出库   作业费用 与 增值服务费用 调拨整个流程产生 只会单条   出库费用与头程物流费及保险费用可以多条（多次出库）
     */
    public function outStockPayment($allo_id,$data)
    {
        $model = new Model();
        $expenseBillRepository = new ExpensebillRepository();
        $model->startTrans();
        // 检查是否存在 作业费用与增值服务费用    N002870019
        $where = [
            'allo_id' => $allo_id,
            'action_type_cd' => $this->out_stock_cd,
            'cost_sub_cd' => $this->wms_operating_cd,
        ];
        $ret = $expenseBillRepository->getOneAlloPayment($where);
       
        // 不存在 作业费用与增值服务费用
        if (empty($ret)) {
            $where['allo_id'] = $allo_id;
            $work = $expenseBillRepository->getWork($where);
           
            if (empty($work)) {
                Logs('调拨单-出库确认-作业费用信息不存在 :' . $allo_id);
                return false;
            }// 创建作业费用
            if ($work['operating_expenses'] > 0) {
                $ret = $this->addPayment($allo_id, $work['operating_expenses'], $work['operating_expenses_currency_cd'], $this->out_stock_cd, $this->wms_operating_cd);
                if ($ret === false) {
                    $model->rollback();
                    return false;
                }
            }
            //  创建增值服务费
            if ($work['value_added_service_fee'] > 0) {
                $ret = $this->addPayment($allo_id, $work['value_added_service_fee'], $work['value_added_service_fee_currency_cd'], $this->out_stock_cd, $this->wms_added_service_cd);
                if ($ret === false) {
                    $model->rollback();
                    return false;
                }
            }
        }
      
        // 创建 出库费用
        if (isset($data['outbound_cost_currency_cd']) && isset($data['outbound_cost']) && $data['outbound_cost'] > 0) {
            $ret = $this->addPayment($allo_id, $data['outbound_cost'], $data['outbound_cost_currency_cd'], $this->out_stock_cd, $this->wms_outbound_cost_cd);
           
            if ($ret === false) {
                $model->rollback();
                return false;
            }
        }
        //  创建  头程物流费
        if (isset($data['head_logistics_fee_currency_cd']) && isset($data['head_logistics_fee']) && $data['head_logistics_fee'] > 0) {
            $ret = $this->addPayment($allo_id, $data['head_logistics_fee'], $data['head_logistics_fee_currency_cd'], $this->out_stock_cd, $this->wms_head_logistics_cd);
            if ($ret === false) {
                $model->rollback();
                return false;
            }
        }
        //  创建  保险费用
        if (isset($data['insurance_fee_currency_cd']) && isset($data['insurance_fee']) && $data['insurance_fee'] > 0) {
            $ret = $this->addPayment($allo_id, $data['insurance_fee'], $data['insurance_fee_currency_cd'], $this->out_stock_cd, $this->wms_insurance_cd);
            if ($ret === false) {
                $model->rollback();
                return false;
            }
        }
        $model->commit();
        return true;
    }

    /**
     *  调拨单入库产生 上架费用与增值服务费
     * @param $allo_id
     * @param $data
     */
    public function inStockPayment($allo_id,$data){
        $model = new Model();
        $model->startTrans();
        //  创建 上架费用
        if (isset($data['shelf_cost_currency_cd']) && isset($data['shelf_cost']) && $data['shelf_cost'] > 0) {
            $ret = $this->addPayment($allo_id, $data['shelf_cost'], $data['shelf_cost_currency_cd'], $this->in_stock_cd, $this->wms_shelf_cost_cd);
            if ($ret === false) {
                $model->rollback();
                return false;
            }
        }

        //  创建 增值服务费
        if (isset($data['value_added_service_fee_currency_cd']) && isset($data['value_added_service_fee']) && $data['value_added_service_fee'] > 0) {
            $ret = $this->addPayment($allo_id, $data['value_added_service_fee'], $data['value_added_service_fee_currency_cd'], $this->in_stock_cd, $this->wms_added_service_cd);
            if ($ret === false) {
                $model->rollback();
                return false;
            }
        }

        $model->commit();
        return true;
    }

    /**
     * 添加费用单记录
     * @param $alloId
     * @param $actionTypeCd
     * @param $data
     * @param $costSubCd
     * @return mixed
     */

    private function addPayment($allo_id,$amount,$amount_currency_cd,$action_type_cd,$cost_sub_cd){
        $expenseBillRepository = new ExpensebillRepository();
        $add_data = array(
            'payment_no' => $this->createPaymentNO(),
            'allo_id' => $allo_id,
            'status' => 0,
            'amount_currency_cd' => $amount_currency_cd,
            'amount' => $amount,
            'action_type_cd' => $action_type_cd,
            'cost_sub_cd' => $cost_sub_cd,
            'created_by' => session('m_loginname'),
            'created_at' => date('Y-m-d H:i:s'),
        );
        $ret = $expenseBillRepository->addOneAlloPayment($add_data);
        if ($ret === false) {
            Logs('调拨单-出库确认-创建费用单失败 :' . $cost_sub_cd);
        }
        $this->addLog($ret,'创建费用单','待确认');
        return $ret;
    }

    /***
     * 列表
     * @param $where
     */
    public function getFeeList($where,$pages)
    {

        $expenseBillRepository = new ExpensebillRepository();
        list($list, $count) = $expenseBillRepository->getList($where,$pages);
        $list =  CodeModel::autoCodeTwoVal($list, ['cost_sub_cd','our_company_cd','amount_currency_cd']);
        $list =  DataModel::formatAmount($list);
        $list  = (new PurService())->orderStatusToVal($list);
        return [$list, $count];
    }

    /***
     * 列表
     * @param $where
     */
    public function getFeeDetails($paymentId)
    {
        $expenseBillRepository = new ExpensebillRepository();
        $ret = $expenseBillRepository->getDetails($paymentId);
        $ret = CodeModel::autoCodeOneVal($ret, ['cost_sub_cd','action_type_cd','payment_channel_cd','payment_way_cd','allo_out_warehouse','allo_in_warehouse','amount_currency_cd','source_cd','difference_reason','our_company_cd','accounting_return_reason']);
        $ret  = (new PurService())->orderStatusToVal($ret,true);
        //$ret =  DataModel::formatAmount($ret);
        // 组装数据
        $info = array(
            'payment_id' => isset($ret['payment_id']) ? $ret['payment_id'] : "",
            'payment_no' => isset($ret['payment_no']) ? $ret['payment_no'] : "",
            'payment_audit_id' => isset($ret['payment_audit_id']) ? $ret['payment_audit_id'] : "",
            'allo_id' => isset($ret['allo_id']) ? $ret['allo_id'] : "",
            'contract_id' => isset($ret['contract_no']) ? $expenseBillRepository->getContractId(['CON_NO'=>$ret['contract_no']])["ID"]  : "",
            'fee_status' => isset($ret['fee_status']) ? $ret['fee_status'] : "",
            'fee_status_val' => isset($ret['fee_status_val']) ? $ret['fee_status_val'] : "",
            'CD_VAL' => isset($ret['CD_VAL']) ? $ret['CD_VAL'] : "",
            'action_type_cd_val' => isset($ret['action_type_cd_val']) ? $ret['action_type_cd_val'] : "",
            'allo_no' => isset($ret['allo_no']) ? $ret['allo_no'] : "",
            'create_user' => isset($ret['create_user']) ? DataModel::getUserNameById($ret['create_user']) : "",
            'cost_sub_cd_val' => isset($ret['cost_sub_cd_val']) ? $ret['cost_sub_cd_val'] : "",
            'allo_out_warehouse' => isset($ret['allo_out_warehouse_val']) ? $ret['allo_out_warehouse_val'] : "",
            'allo_in_warehouse' => isset($ret['allo_in_warehouse_val']) ? $ret['allo_in_warehouse_val'] : "",
            'ETC' => isset($ret['ETC']) ? $ret['ETC'] : "",
            'ETC2' => isset($ret['ETC2']) ? $ret['ETC2'] : "",
            'our_company_cd_val' => isset($ret['our_company_cd_val']) ? $ret['our_company_cd_val']: "",
            'SP_NAME' => isset($ret['SP_NAME']) ? $ret['SP_NAME'] : "",
            'SP_NAME_EN' => isset($ret['SP_NAME_EN']) ? $ret['SP_NAME_EN'] : "",
            'contract_no' => isset($ret['contract_no']) ? $ret['contract_no'] : "",
            'payment_audit_no' => isset($ret['payment_audit_no']) ? $ret['payment_audit_no'] : "",
            'source_cd' => isset($ret['payment_audit_no']) ? $ret['source_cd'] : "",
            'source_cd_val' => isset($ret['source_cd_val']) ? $ret['source_cd_val'] : "",
        );
        //  确认后的金额
        if (isset($ret['payable_amount_before']) && !empty($ret['payable_amount_before'])){
            $ret['amount'] = $ret['payable_amount_before'];
        }

        $payment = array(
            'amount' => isset($ret['amount']) ? $ret['amount'] : "",
            'currency_cd_val' => isset($ret['amount_currency_cd_val']) ? $ret['amount_currency_cd_val'] : "",
            'currency_cd' => isset($ret['amount_currency_cd']) ? $ret['amount_currency_cd'] : "",
            'payable_amount_after' => isset($ret['payable_amount_after']) ? $ret['payable_amount_after'] : "",
            'payable_date_after' => isset($ret['payable_date_after']) ? $ret['payable_date_after'] : "",
            'payment_way_cd' => isset($ret['payment_way_cd_val']) ?  $ret['payment_way_cd_val']: "",
            'payment_channel_cd' => isset($ret['payment_channel_cd_val']) ? $ret['payment_channel_cd_val'] : "",
            'supplier_collection_account' => isset($ret['supplier_collection_account']) ? $ret['supplier_collection_account'] : "",
            'supplier_card_number' => isset($ret['supplier_card_number']) ? $ret['supplier_card_number'] : "",
            'supplier_opening_bank' => isset($ret['supplier_opening_bank']) ? $ret['supplier_opening_bank'] : "",
            'supplier_swift_code' => isset($ret['supplier_swift_code']) ? $ret['supplier_swift_code'] : "",
        );
        //  继续支付剩余部分 为 本次结束 则无 下次支付金额及下次支付时间
        if (isset($ret['pay_remainder']) && $ret['pay_remainder'] == 2){
            $ret['next_pay_amount'] = 0.00;
            $ret['next_pay_time'] = '';
        }

        $remark = array(
            'amount_difference' => isset($ret['amount_difference']) ? $ret['amount_difference'] : "",
            'currency_cd_val' => isset($ret['amount_currency_cd_val']) ? $ret['amount_currency_cd_val'] : "",
            'pay_remainder' => isset($ret['pay_remainder']) ? $ret['pay_remainder'] : "",
            'pay_remainder_val' => isset($ret['pay_remainder_val']) ? $ret['pay_remainder_val'] : "",
            'difference_reason' => isset($ret['difference_reason']) ? $ret['difference_reason'] : "",
            'difference_reason_val' => isset($ret['difference_reason_val']) ? $ret['difference_reason_val'] : "",
            'payment_attachment' => isset($ret['payment_attachment']) ? json_decode($ret['payment_attachment']) : "",
            'confirmation_remark' => isset($ret['confirmation_remark']) ? $ret['confirmation_remark'] : "",
            'business_return_reason' => isset($ret['business_return_reason']) ? $ret['business_return_reason'] : "",
            'billing_voucher' => isset($ret['billing_voucher']) ? json_decode($ret['billing_voucher']) : "",
            'confirmation_remark_audit' => isset($ret['confirmation_remark_audit']) ? $ret['confirmation_remark_audit'] : "",
            'accounting_return_reason' => isset($ret['accounting_return_reason']) ? $ret['accounting_return_reason'] : "",
            'accounting_return_reason_val' => isset($ret['accounting_return_reason_val']) ? $ret['accounting_return_reason_val'] : "",
            'supply_note' => isset($ret['supply_note']) ? $ret['supply_note'] : "",
            'next_pay_amount' => isset($ret['next_pay_amount']) ? $ret['next_pay_amount'] : "",
            'next_pay_time' => (!isset($ret['next_pay_time']) || $ret['next_pay_time'] == '0000-00-00 00:00:00' || empty($ret['next_pay_time']) ) ?  "" :  date("Y-m-d",strtotime($ret['next_pay_time'])),

        );
        $data['info'] = $info;
        $data['payment'] = $payment;
        $data['remark'] = $remark;
        return $data;
    }

    /**
     * 列表查询 where 组装
     * @param $params
     */
    public function feeWhere($params)
    {
        $where = array();
        //  Payment Key
        if (isset($params['payment_no']) && !empty($params['payment_no'])) {
            $where['payment_no'] = $params['payment_no'];
        }
        // 调拨单号
        if (isset($params['allo_no']) && !empty($params['allo_no'])) {
            $where['allo_no'] = $params['allo_no'];
        }
        // 关联付款单号
        if (isset($params['payment_audit_no']) && !empty($params['payment_audit_no'])) {
            $where['payment_audit_no'] = $params['payment_audit_no'];
        }
        // 销售团队
        if (isset($params['allo_in_team']) && !empty($params['allo_in_team'])) {
            $data = explode(',', $params['allo_in_team']);
            $where['allo_in_team'] = array('in', $data);
        }
        // 我方公司
        if (isset($params['our_company_cd']) && !empty($params['our_company_cd'])) {
            $data = explode(',', $params['our_company_cd']);
            $where['our_company_cd'] = array('in', $data);
        }
        // 供应商
        if (isset($params['supplier_id']) && !empty($params['supplier_id'])) {
            $data = explode(',', $params['supplier_id']);
            $where['supplier_id'] = array('in', $data);
        }
        // 费用细分
        if (isset($params['cost_sub_cd']) && !empty($params['cost_sub_cd'])) {
            $data = explode(',', $params['cost_sub_cd']);
            $where['cost_sub_cd'] = array('in', $data);
        }
        // 费用负责人
        if (isset($params['ETC2']) && !empty($params['ETC2'])) {
            $where['ETC2'] = ['like', '%' . $params['ETC2'] . '%'];
        }
        // 状态
        if (isset($params['fee_status'])) {
            if ($params['fee_status'] != "" ){
                $where['tb_wms_payment.status'] = $params['fee_status'];
            }
        }
        return $where;
    }

    /***
     * 供应商数据
     * @param $where
     * @return mixed
     */
    public function getSupplier($where){
        $expenseBillRepository = new ExpensebillRepository();
        $ret = $expenseBillRepository->getSupplier($where);
        return $ret;
    }

    /***
     * 供应商数据
     * @param $where
     * @return mixed
     */
    public function getContract($where){
        $expenseBillRepository = new ExpensebillRepository();
        $ret = $expenseBillRepository->getContract($where);
        return $ret;
    }

    /**
     * 增加操作日志
     */
    public function addLog($paymentid,$operationInfo,$status_name){
        $expenseBillRepository = new ExpensebillRepository();
        // 增加日志
        $logData = array(
            'payment_id' => $paymentid,
            'operation_info' => $operationInfo,
            'status_name' => $status_name,
            'created_by' => session('m_loginname'),
            'created_at' => date('Y-m-d H:i:s'),
        );
        $ret = $expenseBillRepository->addLog($logData);
        return $ret;
    }
    /**
     *   创建付款单
     * @param $request_data
     * @throws Exception
     */
    public function feeConfirm($request_data)
    {
        $expenseBillRepository = new ExpensebillRepository();
        $feePayment =  $expenseBillRepository->getOneAlloPaymentLock(['id'=>$request_data['payment_id']]);
        if (empty($feePayment)){
            throw new Exception('费用记录不存在');
        }
        if (isset($feePayment['payment_audit_id']) && $feePayment['payment_audit_id'] != 0){
            throw new Exception('费用记录异常');
        }
        if ($request_data['pay_remainder'] == 1){
            $data['amount_payable_split'] = $request_data['amount_confirm'] + $request_data['amount_deduction'];
        }
        if($feePayment['status'] != TbPurPaymentModel::$status['to_confirm']) {
            throw new Exception('状态异常');
        }
        // 组装数据
        // 付款单
        $addData = array(
            'payment_audit_no' => 'FK' . date(Ymd) . TbWmsNmIncrementModel::generateNo('payment_audit'),
            'our_company_cd' => $request_data['our_company_cd'],
            'status' => 6,
            'payable_amount_before' => $request_data['payable_amount_before'],
            'payable_amount_after' => $request_data['payable_amount_after'],
            'payable_date_after' => $request_data['payable_date_after'],
            'supplier_opening_bank' => $request_data['supplier_opening_bank'],
            'supplier_collection_account' => $request_data['supplier_collection_account'],
            'supplier_card_number' => $request_data['supplier_card_number'],
            'supplier_swift_code' => $request_data['supplier_swift_code'],
            'created_by' => session('m_loginname'),
            'created_at' => date('Y-m-d H:i:s'),
            'payment_channel_cd' => $request_data['payment_channel_cd'],
            'payment_way_cd' => $request_data['payment_way_cd'],
            'source_cd' => 'N003010002',
            'payable_currency_cd' => $request_data['payable_currency_cd'],
            'accounting_audit_user' => TbPurPaymentAuditModel::$accounting_audit_user
        );
        $res = $expenseBillRepository->addOnePaymentAudit($addData);
        if (!$res){
            throw new Exception('生成付款单失败');
        }
        // 增加 付款单日志
        TbPurPaymentAuditLogModel::recordLog($res, '创建付款单', '', '待审核','');

        if (isset($request_data['amount_difference']) && $request_data['amount_difference'] == 0 ){
            $request_data['pay_remainder'] = 0;
        }

        //  费用单
        $updateData = array(
            'payment_audit_id' => $res,
            'status' => 6,
            'supplier_id' => $request_data['supplier_id'],
            'contract_no' => $request_data['contract_no'],
            'amount_confirm' => $request_data['payable_amount_after'],
            'amount_difference' => isset($request_data['amount_difference']) ? $request_data['amount_difference'] : "",
            'difference_reason' => $request_data['difference_reason'],
            'pay_remainder' => $request_data['pay_remainder'],
            'next_pay_amount' => isset($request_data['next_pay_amount']) ? $request_data['next_pay_amount'] : "",
            'next_pay_time' => $request_data['next_pay_time'],
            'confirmation_remark' => isset($request_data['confirmation_remark']) ? $request_data['confirmation_remark'] : "",
            'payment_attachment' => json_encode($request_data['payment_attachment']),
            'confirm_user' => session('m_loginname'),
            'confirm_time' => date('Y-m-d H:i:s'),
            'updated_by' => session('m_loginname'),
            'updated_at' => date('Y-m-d H:i:s'),
        );
        $ret = $expenseBillRepository->updateAlloPayment(['id'=>$request_data['payment_id']],$updateData);
        if ($ret === false){
            throw new Exception('费用单保存异常');
        }
        // 增加费用单操作日志
        $this->addLog($request_data['payment_id'],'确认费用单','待业务审核');

        return true;
    }

    /**
     *  获取日志
     */
    public function getLog($request_data)
    {
        $search_map = [
            'payment_id' => 'payment_id'
        ];
        $search_type['all'] = true;
        list($where, $limit)   = WhereModel::joinSearchTemp($request_data,$search_map, '', $search_type);
        $pages['total']        = M('payment_log','tb_wms_')->where($where)->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        $db_res = M('payment_log','tb_wms_')->where($where)->limit($limit[0], $limit[1])->order('created_at desc')->select();
        return [
            'data' => $db_res,
            'pages' => $pages
        ];
    }
}