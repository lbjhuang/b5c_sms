<?php

/**
 * User: yangsu
 * Date: 19/5/9
 * Time: 11:31
 */


@import("@.Model.BaseModel");

/**
 * Class DivisionLaborService
 */
class DivisionLaborService extends Service
{
    private $todo_list_map = [
        'pending_payment_amount_to_be_confirmed' => '待确认采购应付金额',
        'pending_accounting_audit' => '待审核付款',
        'pending_purchase_payment_amount' => '待确认付款',
        'pending_payment_amount_out_confirmed' => '待确认出账金额',
        'pending_purchase_confirmation' => '待确认采购发货',
        'pending_purchase_warehousing' => '待确认采购入库',
        'waiting_for_purchase' => '待跟催采购入库',
        'pending_confirmation_of_purchase_invoice' => '待确认收取采购发票',
        'pending_purchase_invoice' => '待核销采购发票',
        'pending_confirmation_purchase_return_tally' => '待确认采购退货理货',

        'pending_b2b_order_delivery' => '待确认B2B订单发货',
        'waiting_for_the_b2b_order_to_be_shipped' => '待跟催B2B订单发货',
        'pending_b2b_order_receipts' => '待认领B2B订单收款',
        'pending_b2b_to_be_written_off' => '待核销B2B应收',

        'pending_purchase_return' => '待确认采购退货出库',
        'waiting_for_the_purchase_of_the_return_of_the_goods' => '待跟催采购退货出库',
        'to_be_confirmed_and_transferred_out_of_the_library_old' => '待确认调拨出库',
        //'to_be_confirmed_to_transfer_to_the_warehouse_old' => '待确认调拨入库',
        'to_be_confirmed_and_transferred_to_the_task' => '待确认作业信息',
        'to_be_confirmed_and_transferred_out_of_the_library' => '待确认调拨出库',
        'to_be_confirmed_to_transfer_to_the_warehouse' => '待确认调拨入库',

        'pending_offer' => '待选择报价',
        'sales_leadership_review_needs' => '待销售领导审核需求',
        'waiting_demand_cash_apply' => '待现金效率审批',
        'pending_up_sales_order' => '待上传销售PO',
        'pending_up_purchase_order' => '待上传采购PO',
        'pending_sales_order' => '待审核销售PO',
        'pending_purchase_order' => '待审核采购PO',

        'work_order_dealing' => '待处理问题',
        'work_order_accepting' => '待验收问题',
        'wms_pending_to_be_confirmed' => '待确认调拨费用付款',
        'wms_pending_business_audit'  => '待审核调拨费用付款',
        'configure_the_store_affirm'  => '待确认店铺信息准确性',
        'configure_the_store_handover'  => '待确认店铺信息准确性（交接）',
        'configure_the_store_income_company'  => '待确认店铺收入记录公司',
        'configure_the_store_account_bank'  => '待确认店铺收款账号',
        'the_requirements_assigned_to_me'  => '指派给我的需求',

        'gp_order_deal' => 'Gshopper平台超过24h未派单订单',
        'auditing_transfer_fund' => '待审核资金划转',
        'business_audit_bill' => '待业务审核平台账单',
        'finance_audit_bill' => '待财务审核账单',
        'auditing_gp_refund_apply' => '待审核GP退款申请',
        'auditing_tpp_refund_apply' => '待审核TPP退款申请',

        'inve_auditing' => '盘点差异待审核（盘点仓库负责人）',
        'inve_fin_confirming' => '盘点差异待确认（盘点财务负责人）',
        'inve_auditing_again' => '盘点差异待复核（盘点仓库负责人）',
        'inve_checking_again' => '盘点差异待复盘（盘点仓库操作人）',

        'legal_leader_audit' => '待领导审批合同',
        'legal_legal_audit' => '待法务审批合同',
        'legal_fin_audit' => '待财务审批合同',
        'legal_upload' => '待法务盖章',
        'legal_upload_sec' => '待上传定稿合同',
        'legal_upload_thr' => '待法务确认归档',
		'wait_logistics_quote'     => '待物流报价',
        'wait_operator_confirm_quote'     => '待运营确认报价',
        'wait_logistics_twice_quote'      => '待物流二次报价',
        'wait_operator_twice_confirm_quote'  => '待运营确认二次报价',

        'await_affirm_coupon' => '待确认优惠码',
        'await_promotion_task' => '待推广任务',

        'promotion_approver' => '待审批晋升加薪单',

        'await_improt_bill_audit' => '待审批EXCEL出入库',




        
    ];

    /**
     *事项标签
     */
    public $tabTagConf = [
        'gp_order_deal' => '去派单',
        'business_audit_bill' => '去审核',
        'finance_audit_bill' => '去审核',
    ];

    public function __construct()
    {
        $this->repository = new DivisionLaborRepository();
    }

    public function ourCompanysIndex($data)
    {
        $search_map = [
            'our_company' => 'tb_ms_cmn_cd.CD_VAL',
            'payment_manager_by' => 'tb_con_division_our_company.payment_manager_by',
            'invoice_person_charge_by' => 'tb_con_division_our_company.invoice_person_charge_by',
        ];
        list($wheres, $limit) = WhereModel::joinSearchTemp($data, $search_map);
        if (!empty($data['search']['b2b_manager_by'])) {
            $wheres['tb_con_division_our_company.b2b_manager_by'] = ['like', "%{$data['search']['b2b_manager_by']}%"];
        }
        list($res_return['data'], $res_return['pages']) = $this->repository->ourCompanysIndex($wheres, $limit);
        return $res_return;
    }

    public function ourCompanysUpdate($data)
    {
        foreach ($data as $datum) {
            $temp_data = $datum;
            $temp_data = $this->joinTempSaveData($datum, $temp_data);
            $where_id['id'] = $datum['id'];
            $res[] = $this->repository->ourCompanysUpdate($where_id, $temp_data);
        }
        return $res;
    }

    public function clientsIndex($data)
    {
        $search_map = [
            'client_name' => 'tb_crm_sp_supplier.SP_NAME',
            'sales_assistant_by' => 'tb_con_division_client.sales_assistant_by',
        ];
        list($wheres, $limit) = WhereModel::joinSearchTemp($data, $search_map);
        list($res_return['data'], $res_return['pages']) = $this->repository->clientsIndex($wheres, $limit);
        return $res_return;

    }

    public function clientsUpdate($data)
    {
        foreach ($data as $datum) {
            $temp_data = $datum;
            $temp_data = $this->joinTempSaveData($datum, $temp_data);
            $where_id['id'] = $datum['id'];
            $res[] = $this->repository->clientsUpdate($where_id, $temp_data);
        }
        return $res;
    }

    public function warehousesIndex($data)
    {
        $search_map = [
            'warehouse' => 'tb_ms_cmn_cd.CD_VAL',
            'purchase_warehousing_by' => 'tb_con_division_warehouse.purchase_warehousing_by',
            'transfer_warehousing_by' => 'tb_con_division_warehouse.transfer_warehousing_by',
            'b2b_order_outbound_by' => 'tb_con_division_warehouse.b2b_order_outbound_by',
            'transfer_out_library_by' => 'tb_con_division_warehouse.transfer_out_library_by',
            'prchasing_return_by' => 'tb_con_division_warehouse.prchasing_return_by',
            'task_launch_by' => 'tb_con_division_warehouse.task_launch_by',
        ];
        list($wheres, $limit) = WhereModel::joinSearchTemp($data, $search_map);
        $where_sting = '';
        $address = ['country', 'province', 'city'];
        foreach ($address as $add) {
            $where_sting = $this->joinWhereString($data, $where_sting, $add);
        }
        list($res_return['data'], $res_return['pages']) = $this->repository->warehousesIndex($wheres, $where_sting, $limit);
        return $res_return;

    }

    public function warehousesUpdate($data)
    {
        foreach ($data as $datum) {
            $temp_data = $datum;
            $temp_data = $this->joinTempSaveData($datum, $temp_data);
            $where_id['id'] = $datum['id'];
            $res[] = $this->repository->warehousesUpdate($where_id, $temp_data);
        }
        return $res;

    }

    /**
     * @param $datum
     * @param $temp_data
     * @return array
     */
    private function joinTempSaveData($datum, $temp_data)
    {
        if (empty($datum['id'])) {
            $temp_data['created_by'] = DataModel::userNamePinyin();
        }
        $temp_data['updated_by'] = DataModel::userNamePinyin();
        return $temp_data;
    }

    /**
     * @param $data
     * @param $where_sting
     * @param $key
     * @return string
     */
    private function joinWhereString($data, $where_sting, $key)
    {
        if ($data['search'][$key]) {
            if ($where_sting) {
                $where_sting = ' AND ';
            }
            $where_sting .= '  FIND_IN_SET (' . $data['search'][$key].',tb_wms_warehouse.city)';
        }
        return $where_sting;
    }

    public function todoIndex($data)
    {
        if (empty($data)) {
            $data = array_keys($this->todo_list_map);
        }
        foreach ($data as $datum) {
            $TodoModel = new ReflectionMethod('TodoModel', $datum);
            
            if ($TodoModel->isStatic()) {
                $data_call_user_func = call_user_func("TodoModel::$datum");
                if (is_array($data_call_user_func)) {
                    $temp_count = count($data_call_user_func);
                    $response_data[$datum]['name'] = $this->todo_list_map[$datum];
                    $response_data[$datum]['count'] = $temp_count;
                    $response_data[$datum]['list_column_map'] = TodoModel::$column_map[$datum];
                    $response_data[$datum]['url_map'] = $this->assembleUrlMap($response_data[$datum]['list_column_map'],$datum);
                    $response_data[$datum]['list'] = $data_call_user_func;
                    if (isset($this->tabTagConf[$datum])) $response_data[$datum]['tag'] = $this->tabTagConf[$datum];
                    if (isset(TodoModel::$column_map[$datum]['translate'])) $response_data[$datum]['translate'] = TodoModel::$column_map[$datum]['translate'];
                    unset($response_data[$datum]['list_column_map']['detail_url'], $response_data[$datum]['list_column_map']['title'], $response_data[$datum]['list_column_map']['translate']);
                }
            } else {
                $response_data[$datum] = [
                    'error' => '方法不存在或非静态',
                    'count' => 0
                ];
            }
        }
        return $response_data;
    }

    private function assembleUrlMap($data,$datum)
    {
        if ($datum == 'the_requirements_assigned_to_me'){
            $temp_data['detail_url'] = $data['detail_url'];
            $temp_data['title'] = $data['title'];
        }else{
            $erp_url = ERP_URL;
            $erp_url = '//' . $_SERVER['HTTP_HOST'].'/';
            $temp_data['detail_url'] = $erp_url . $data['detail_url'];
            $temp_data['title'] = $data['title'];
        }

        return $temp_data;
    }

    private function joinDetailUrl($data_call_user_func, $datum)
    {
        foreach ($data_call_user_func as &$value) {
            if (TodoModel::$column_map[$datum]['detail_url']) {
                $value['detail_url'] = ERP_URL . TodoModel::$column_map[$datum]['detail_url'] . $value['detail_url_key'];
            }
        }
        unset($value);
        return $data_call_user_func;
    }

}