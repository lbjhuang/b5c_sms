<?php
/**
 *  调拨费用管理
 */

class ExpensebillAction extends BaseAction
{

    public function _initialize()
    {
        parent::_initialize();
    }


    public function fee_list()
    {
        $this->display();
    }

    /**
     *  列表
     */
    public function lists()
    {
        import('ORG.Util.Page');
        $params = $this->getParams();
        $expenseBill = new ExpensebillService();
        $pages = array(
            'per_page' => 10,
            'current_page' => 1
        );
        if (isset($params['pages']) && !empty($params['pages']['per_page']) && !empty($params['pages']['current_page'])){
            $pages = array(
                'per_page' =>$params['pages']['per_page'],
                'current_page' => $params['pages']['current_page']
            );
        }
        $where = $expenseBill->feeWhere($params['search']);

        list($list, $count) = $expenseBill->getFeeList($where,$pages);
        if (empty($count)) {
            $list = [];
        }
        $data = ['data' => $list, 'page' => ['total_rows' => $count]];
        $this->ajaxSuccess($data);
    }

    public function fee_info()
    {
        $this->display();
    }

    public function details()
    {
        $params = I('');
        $expenseBill = new ExpensebillService();
        $paymentId = isset($params['payment_id']) ? $params['payment_id'] : "";
        if (empty($paymentId)) {
            $this->ajaxError('', '参数有误');
        }
        $data = $expenseBill->getFeeDetails($paymentId);
        $this->ajaxSuccess($data);
    }

    /**
     * 获取的供应商键值对
     */
    public function getSupplier()
    {
        $expenseBill = new ExpensebillService();
        $where['SP_STATUS'] = 1;
        $where['DEL_FLAG'] = 1;
        $where['DATA_MARKING'] = 0;
        $where['AUDIT_STATE'] =  array('in',[2,3]);
        $data = $expenseBill->getSupplier($where);
        $this->ajaxSuccess($data);
    }

    /**
     * 根据供应商获取合同编号
     */
    public function getContract(){
        $params = I('');
        $expenseBill = new ExpensebillService();
        $SP_NAME = isset($params['SP_NAME']) ? $params['SP_NAME'] : "";
        $CON_COMPANY_CD = isset($params['CON_COMPANY_CD']) ? $params['CON_COMPANY_CD'] : "";
        if (empty($SP_NAME) || empty($CON_COMPANY_CD)) {
            $this->ajaxError('', '参数有误');
        }
        $where['SP_NAME'] = $SP_NAME;
        $where['CRM_CON_TYPE'] = 0;
        $where['CD'] = $CON_COMPANY_CD;
        $data = $expenseBill->getContract($where);
        $this->ajaxSuccess($data);
    }

    /**
     * 获取 调拨单费用细分
     */
    public function getCostSub()
    {

        $cd_m = new TbMsCmnCdModel();
        $way_cds = array(
            'N001950601',
            'N001950602',
            'N001950603',
            'N001950604',
            'N001950605',
            'N001950606',
        );
        $data = $cd_m->getPaymentWayByChannelCd($way_cds);   // 费用细分
        $this->ajaxSuccess($data);
    }

    //业务审核
    public function businessAudit()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $rClineVal = RedisModel::lock('payment_audit_id' . $request_data['payment_audit_id'], 10);
            $model = new Model();
            if ($request_data) {
                $this->validateBusinessAuditData($request_data);
            } else {
                throw new Exception('请求为空');
            }
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $model->startTrans();
            (new ExpenseBillPaymentService($model))->businessAudit($request_data);
            $model->commit();
            RedisModel::unlock('payment_audit_id' . $request_data['payment_audit_id']);
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateBusinessAuditData($data)
    {
        $rules = [
            'status' => 'required|numeric',
            'payment_audit_id' => 'required|numeric',
        ];
        $custom_attributes = [
            'status' => '审核状态',
            'payment_audit_id' => '付款id',
        ];
        if ($data['is_return']) {
            $rules['business_return_reason'] = 'required';
            $custom_attributes['business_return_reason'] = '退回原因';
        }
        $this->validate($rules, $data, $custom_attributes);
    }

    /**
     *  确认 费用单
     */
    public function feeConfirm() {
        if(IS_POST) {
            try {
                $model = new Model();
                $expenseBill = new ExpensebillService();
                $request_data = ZUtils::filterBlank($_POST);
                if (empty($request_data)){
                    throw new Exception('请求为空');
                }else{
                    $this->confirmValidate($request_data);
                }

                $rClineVal = RedisModel::lock('payment_id' . $request_data['payment_id'], 10);
                if (!$rClineVal) {
                    throw new Exception('获取流水锁失败');
                }
                $res = DataModel::$success_return;
                $res['code'] = 200;
                $model->startTrans();
                $expenseBill->feeConfirm($request_data);
                $model->commit();
                RedisModel::unlock('payment_id' . $request_data['payment_id']);
            } catch (Exception $exception) {
                $model->rollback();
                $res = $this->catchException($exception);
                $this->ajaxReturn($res);
            }
            $this->ajaxReturn($res);
        }
    }
    /**
     *  确认 费用单数据验证
     */
    public function confirmValidate($request_data){

        // 规则
        $rules['payment_id'] = 'required';
        $rules['supplier_id'] = 'required';
        $rules['our_company_cd'] = 'required|size:10';
        //$rules['contract_no'] = 'required';  from #9871 放开合同限制

        $rules['payable_date_after'] = 'required';
        $rules['payable_amount_after'] = 'required';
        $rules['payable_amount_before'] = 'required';


        $rules['payment_attachment'] = 'required';
        //$rules['confirmation_remark'] = 'required';

        $rules['payment_way_cd'] = 'required|size:10';
        $rules['payment_channel_cd'] = 'required|size:10';

        $rules['supplier_opening_bank'] = 'required';
        $rules['supplier_collection_account'] = 'required';
        $rules['supplier_card_number'] = 'required';
        $rules['supplier_swift_code'] = 'required';

        $rules['payable_currency_cd'] = 'required|size:10';

        // 反馈消息
        $custom_attributes['payment_id'] = '费用单ID';
        $custom_attributes['supplier_id'] = '供应商ID';
        $custom_attributes['our_company_cd'] = '我方公司';
        //$custom_attributes['contract_no'] = '合同';

        $custom_attributes['payable_date_after'] = '预计付款日期';
        $custom_attributes['payable_amount_after'] = '确认前-本期应付金额';
        $custom_attributes['payable_amount_before'] = '确认后-本期应付金额';

        if (isset( $request_data['payable_amount_after']) && $request_data['payable_amount_after'] <= 0 ){
            throw new Exception('确认前-本期应付金额 有误');
        }

        if (isset( $request_data['payable_amount_before']) && $request_data['payable_amount_before'] <= 0 ){
            throw new Exception('确认后-本期应付金额 有误');
        }
        
        //   应付金额前后不一致 存在差异
        if (isset( $request_data['payable_amount_after']) && isset( $request_data['payable_amount_before'])
            && $request_data['payable_amount_before'] !=  $request_data['payable_amount_after']){

            //$rules['amount_difference'] = 'required';
            $rules['pay_remainder'] = 'required';
            $rules['difference_reason'] = 'required';

            //$rules['next_pay_amount'] = 'required';



            //$custom_attributes['amount_difference'] = '应付差额';
            $custom_attributes['pay_remainder'] = '继续支付剩余部分';
            $custom_attributes['difference_reason'] = '差异原因';

            //$custom_attributes['next_pay_amount'] = '下一次付款金额';
            if (isset($request_data['pay_remainder']) && $request_data['pay_remainder'] == 1){
                $rules['next_pay_time'] = 'required';
                $custom_attributes['next_pay_time'] = '下一次付款时间';
            }

        }



        $custom_attributes['payment_attachment'] = '付款需求附件';
        //$custom_attributes['confirmation_remark'] = '提交付款备注';

        $custom_attributes['payment_way_cd'] = '支付渠道';
        $custom_attributes['payment_channel_cd'] = '支付方式';

        $custom_attributes['supplier_opening_bank'] = '收款账户开户行';
        $custom_attributes['supplier_collection_account'] = '收款账户名';
        $custom_attributes['supplier_card_number'] = '收款银行账号';
        $custom_attributes['supplier_swift_code'] = '收款银行SWIFT/CODE';

        $custom_attributes['payable_currency_cd'] = '币种';

        $this->validate($rules, $request_data, $custom_attributes);

    }



    public function getLog()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $data['data'] = (new ExpensebillService())->getLog($request_data);
            $res['data']  = $data;
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }
}
