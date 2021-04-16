<?php


class ExpensebillRepository extends Repository
{

    /**
     * 获取单条应对记录
     * @param $paymentId
     * @return mixed
     */
    public function getOneAlloPayment($where)
    {
        $ret = M('payment', 'tb_wms_')->where($where)->find();
        return $ret;
    }

    /**
     * 获取单条应对记录
     * @param $paymentId
     * @return mixed
     */
    public function getOneAlloPaymentLock($where)
    {
        $ret = M('payment', 'tb_wms_')->lock(true)->where($where)->find();
        return $ret;
    }

    /**
     * 添加单条应对记录
     * @param $paymentId
     * @return mixed
     */
    public function addOneAlloPayment($data)
    {
        $ret = M('payment', 'tb_wms_')->add($data);
        return $ret;
    }

    /**
     * 添加单条付款单记录
     * @param $paymentId
     * @return mixed
     */
    public function addOnePaymentAudit($data)
    {
        $ret = M('payment_audit', 'tb_pur_')->add($data);
        return $ret;
    }

    /**
     * 添加单条应对记录
     * @param $paymentId
     * @return mixed
     */
    public function updateAlloPayment($where,$data)
    {
        $ret = M('payment', 'tb_wms_')->where($where)->save($data);
        return $ret;
    }

    /**
     * 删除应付记录  删除修改状态
     * @param $paymentId
     * @return mixed
     */
    public function deleteOneAlloPayment($where)
    {
        $ret = M('payment', 'tb_wms_')->where($where)->save(['status'=> 5,'payment_audit_id'=>0]);
        return $ret;
    }
    /**
     * 列表
     * @param $paymentId
     * @return mixed
     */
    public function getList($where,$pages)
    {

        if (empty($where)){
            $where = " 1 = 1 ";
        }
        $count = M('payment', 'tb_wms_')
            ->join('LEFT JOIN tb_wms_allo ON tb_wms_payment.allo_id = tb_wms_allo.id')
            ->join('LEFT JOIN tb_pur_payment_audit ON tb_pur_payment_audit.id = tb_wms_payment.payment_audit_id')
            ->join('LEFT JOIN tb_ms_cmn_cd ON tb_wms_allo.allo_in_team = tb_ms_cmn_cd.CD')
            ->where($where)
            ->count();
        $list = M('payment', 'tb_wms_')
            ->field('tb_wms_payment.id as payment_id ,payment_no,cost_sub_cd,payment_audit_no,our_company_cd,CD_VAL,payable_amount_after,payable_date_after,tb_wms_payment.`status` as fee_status,ETC2,tb_crm_sp_supplier.SP_NAME,amount_currency_cd')
            ->join('LEFT JOIN tb_wms_allo ON tb_wms_payment.allo_id = tb_wms_allo.id')
            ->join('LEFT JOIN tb_pur_payment_audit ON tb_pur_payment_audit.id = tb_wms_payment.payment_audit_id')
            ->join('LEFT JOIN tb_ms_cmn_cd ON tb_wms_allo.allo_in_team = tb_ms_cmn_cd.CD')
            ->join('tb_crm_sp_supplier ON tb_wms_payment.supplier_id = tb_crm_sp_supplier.id')
            ->where($where)
            ->order('tb_wms_payment.id desc')
            ->limit(($pages['current_page'] - 1) * $pages['per_page'], $pages['per_page'])
            ->select();
        return [$list, $count];
    }

    /**
     * 详情
     * @param $paymentId
     * @return mixed
     */
    public function getDetails($paymentId)
    {
        $where['tb_wms_payment.id '] = $paymentId;
        $list = M('payment', 'tb_wms_')
            ->field('payable_amount_before,next_pay_amount,next_pay_time,supply_note,allo_id,billing_voucher,source_cd,tb_wms_payment.id as payment_id,payment_audit_id,payment_no,tb_wms_payment.`status` as fee_status ,CD_VAL,action_type_cd,tb_wms_allo.allo_no,tb_wms_allo.create_user,cost_sub_cd,
            allo_out_warehouse, allo_in_warehouse,ETC,ETC2,our_company_cd,supplier_collection_account,contract_no,tb_wms_payment.amount,tb_wms_payment.amount_currency_cd,
            payable_amount_after, payable_currency_cd,payable_date_after,payment_channel_cd,payment_way_cd,supplier_collection_account,supplier_card_number,
            supplier_opening_bank,supplier_swift_code,amount_difference,pay_remainder,difference_reason,payment_attachment,
            tb_pur_payment_audit.confirmation_remark as confirmation_remark_audit,business_return_reason,tb_crm_sp_supplier.SP_NAME,tb_crm_sp_supplier.SP_NAME_EN,tb_wms_payment.amount_currency_cd,payment_audit_no,tb_wms_payment.confirmation_remark,accounting_return_reason')
            ->join('LEFT JOIN tb_wms_allo ON tb_wms_payment.allo_id = tb_wms_allo.id')
            ->join('LEFT JOIN tb_pur_payment_audit ON tb_pur_payment_audit.id = tb_wms_payment.payment_audit_id')
            ->join('LEFT JOIN tb_ms_cmn_cd ON tb_wms_allo.allo_in_team = tb_ms_cmn_cd.CD')
            ->join('tb_crm_sp_supplier ON tb_wms_payment.supplier_id = tb_crm_sp_supplier.id')
            ->where($where)
            ->find();
        return $list;
    }

    /**
     * 获取所有的供应商
     */
    public function getSupplier($where){
        $list = M('supplier','tb_crm_sp_')->field('ID,SP_NAME,SP_NAME_EN')->where($where)->select();
        return $list;
    }

    /**
     *  调拨单-增加操作日志
     */
    public function addLog($data){
        $ret = M('payment_log','tb_wms_')->add($data);
        return $ret;
    }

    /**
     * 获取所有的供应商
     */
    public function getContract($where){
        $list = M('contract','tb_crm_')->field('CON_NO,CON_NAME')
                ->join('LEFT JOIN tb_ms_cmn_cd ON tb_crm_contract.CON_COMPANY_CD = tb_ms_cmn_cd.ETC')
               ->where($where)->select();
        return $list;
    }

    /**
     * 获取所有的作业费用
     */
    public function getWork($where){
        $data = M('allo_new_works','tb_wms_')->where($where)->find();
        return $data;
    }

    /**
     * 获取日志
     */
    public function getLog($where){
        $data = M('payment_log','tb_wms_')->where($where)->order('id desc')->select();
        return $data;
    }

    /**
     *  获取合同ID
     */
    public function getContractId($where){
        $data = M('contract','tb_crm_')->field('ID')->where($where)->find();
        return $data;
    }

}