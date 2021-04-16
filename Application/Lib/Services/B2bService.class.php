<?php

/**
 * User: yangsu
 * Date: 18/10/9
 * Time: 11:31
 */

@import("@.Model.Orm.TbB2bReceivable");
@import("@.Model.BaseModel");

/**
 * Class B2bService
 */
class B2bService extends Service
{

    /**
     * @var array
     */
    private $claim_list = [
        ['PO_ID', 'B2B订单号'],
        ['THR_PO_ID', 'PO单号'],
        ['CLIENT_NAME', '客户'],
        ['PO_USER', '销售人员'],
        ['SALES_TEAM_val', '销售团队'],
        ['po_currency_val', '币种'],
        ['order_account', '收入（含税）'],
        ['actual_collection', '实际收款'],
        ['sum_deduction_amount', '客户扣费'],
        ['current_receivable', '应收'],
        ['rate_losses', '汇率损益'],
        ['current_receivable_cny', '应收（CNY）'],
        ['created_at', '订单创建时间'],
        ['receivable_status_val', '应收状态'],
        ['submit_by', '提交人'],
        ['submit_at', '提交时间'],
        ['verification_by', '核销人'],
        ['verification_at', '核销时间'],

    ];

    /**
     * @var array
     */
    private $order_excel_sheet_names = [
        'order_info' => '订单汇总信息',
        'ship_info' => '发货明细信息',
        'receipt_info' => '收款明细信息',
        'deduction_info' => '扣费明细信息',
    ];

    /**
     * @var array
     */
    private $order_column_name = [
        ['PO_ID', 'B2B订单号'],
        ['THR_PO_ID', 'PO单号'],
        ['CLIENT_NAME', '客户名称'],
        ['our_company', '我方公司'],
        ['contract', '适用合同'],
        ['target_city_detail', '目标城市'],
        ['target_city', '详细地址'],
        ['delivery_time', '预计发货日期'],
        ['receipt_node', '收款节点'],
        ['delivery_method_val', '发货方式'],
        ['invoice_code_val', '销售发票'],
        ['tax_point_val', '税率'],
        ['SALES_TEAM_VAL', '销售团队'],
        ['PO_USER', '销售人员'],
        ['po_time', 'PO日期'],
        ['po_currency_val', '订单币种'],
        ['po_amount', 'PO金额（订单币种，含增值税）'],
        ['po_amount_excluding_tax', 'PO金额（订单币种，不含增值税）'],
        ['rebate_rate', '返利比例（预估）'],
        ['rebate_amount', '返利金额（预估，订单币种）'],
        ['sum_shipping_cost', '累计发货成本（CNY，含增值税）'],
        ['sum_shipping_cost_excludeing_tax', '累计发货成本（CNY，不含增值税）'],
        ['sum_shipping_revenue', '累计发货收入（订单币种，含增值税）'],
        ['sum_shipping_revenue_excludeing_tax', '累计发货收入（订单币种，不含增值税）'],
        ['sum_shipping_revenue_excludeing_tax_cny', '累计发货收入（CNY，不含增值税）'],
        ['all_sum_warehousing', '累计理货扣款（订单币种，含增值税）'],
        ['all_sum_warehousing_no_tax', '累计理货扣款（订单币种，不含增值税）'],
        ['all_claim_amount_to_po_currency', '累计收款金额（订单币种）'],
        ['all_deduction_amount', '累计扣费金额（订单币种）'],
        ['rate_losses', '汇率损益(订单币种)'],
        ['current_receivable', '应收（订单币种）'],
        ['current_receivable_cny', '应收（CNY）'],
        ['receivable_status_val', '应收状态'],
        ['order_state_val', '发货状态'],
        ['warehousing_state_val', '理货状态'],
        ['remarks', '备注'],
        ['verification_by', '应收核销人'],
        ['verification_at', '应收核销时间'],
        ['cancel_note', '核销备注'],
        ['estimated_income', '预估收入（USD）'],
        ['estimated_cost', '预估成本（USD）'],
        ['estimated_gross_profit', '预估毛利（USD）'],
        ['estimated_gross_profit_margin', '预估毛利率'],
        ['logistics_estimat', '预估费用（USD）'],
    ];

    /**
     * @var array
     */
    private $shipping_detail = [
        ['PO_ID', 'B2B订单号'],
        ['THR_PO_ID', 'PO单号'],
        ['po_currency_val', '订单币种'],
        ['DOSHIP_ID', '发货单号'],
        ['DELIVERY_TIME', '发货日期'],
        ['sum_shipping_cost', '发货成本（CNY，含增值税）'],
        ['sum_shipping_cost_excludeing_tax', '发货成本（CNY，不含增值税）'],
        ['sum_shipping_revenue', '发货收入（订单币种，含增值税）'],
        ['sum_shipping_revenue_excludeing_tax', '发货收入（订单币种，不含增值税）'],
        ['warehouse_list_status_val', '理货状态'],
        ['AUTHOR', '理货确认人'],
        ['WAREING_DATE', '理货确认时间'],
        ['sum_warehousing', '理货扣款（订单币种，含增值税）'],
        ['sum_warehousing_no_tax', '理货扣款（订单币种，不含增值税）'],
    ];
    /**
     * @var array
     */
    private $receipt_detail = [
        ['PO_ID', 'B2B订单号'],
        ['THR_PO_ID', 'PO单号'],
        ['po_currency_val', '订单币种'],
        ['account_transfer_no', '流水ID'],
        ['currency_code_val', '流水币种'],
        ['claim_amount', '认领金额（流水币种）'],
        ['claim_amount_to_po_currency', '认领金额（订单币种）'],
        ['transfer_time', '收款时间'],
        ['created_by', '认领人'],
        ['created_at', '认领时间'],
        ['opp_company_name', '付款方账户'],
        ['company_code_val', '我方账户'],
        ['swift_code', '我方银行或平台名称'],
        ['account_bank', '我方账号'],
    ];
    /**
     * @var array
     */
    private $deduction_detail = [
        ['PO_ID', 'B2B订单号'],
        ['THR_PO_ID', 'PO单号'],
        ['po_currency_val', '订单币种'],
        ['deduction_type_val', '扣费类型'],
        ['instructions', '扣费说明'],
        ['deduction_amount', '扣费金额（订单币种）'],
        ['updated_by', '扣费录入人'],
        ['updated_at', '扣费录入时间'],
    ];

    /**
     * @var array
     */
    private $purchase_status = [
        'coping_status' => ['待付款', '部分付款', '完成付款',],
        'send_status' => ['待发货', '部分发货', '完成发货',],
        'inbound_status' => ['待入库', '部分入库', '完成入库',],
        'invoice_status' => [],
    ];

    const B2B_CLAIM_CODE        = '';//B2B收款认领
    const PUR_REFUND_CLAIM_CODE = 'N002630200';//采购退款认领
    const NORMAL_TALLY          = 'N002780001';//正常理货
    const RECEIVABLE_NO_SUBMINT = 'N002540100';//应收待提交

    /**
     * @var B2bRepository
     */
    private $B2bRepository;

    public $claim_type;

    private $model;

    /**
     * B2bService constructor.
     * @param string $claim_type
     */
    private $tbPurActionLogModel;

    public function __construct($claim_type = "", $model = "")
    {
        $this->claim_type = $claim_type;
        $this->B2bRepository = new B2bRepository($claim_type);
        if ($model) {
            $this->model = $model;
        } else {
            $this->model = new Model();
        }
    }

    /**
     * @param $data_arr
     * @param $main_date_key
     * @param string $overdue_status_key
     * @param string $overdue_day_key
     * @param bool $updateDateShow
     * @return mixed
     */
    public function checkOverdueDay($data_arr, $main_date_key, $overdue_status_key = 'overdue_statue', $overdue_day_key = 'overdue_day', $updateDateShow = false)
    {
        $now_date = new DateTime();
        $order_id_arr = array_column($data_arr, 'ORDER_ID');
        $last_pay_date_arr = (new B2bRepository())
            ->getLastPaymentDate($order_id_arr);
        list($main_receipt_operation_status_arr, $main_expect_receipt_date_arr) = (new B2bRepository())
            ->getMainReceiptInfos($order_id_arr);
        foreach ($data_arr as &$value) {
            switch ($main_receipt_operation_status_arr[$value['ORDER_ID'] . $value['receiving_code']]) {
                case 0:
                    $value = $this->getPayment(
                        $now_date,
                        $value,
                        $main_date_key,
                        $overdue_status_key,
                        $overdue_day_key
                    );
                    $value['main_expect_receipt_date'] = $main_expect_receipt_date_arr[$value['ORDER_ID'] . $value['receiving_code']];
                    break;
                case 1:
                    $value = $this->getPayment(
                        new DateTime($last_pay_date_arr[$value['ORDER_ID'] . $value['receiving_code']]),
                        $value,
                        $main_date_key,
                        $overdue_status_key,
                        $overdue_day_key
                    );
                    $value = $this->listDataUpdate($updateDateShow,
                        $last_pay_date_arr,
                        $value,
                        $main_expect_receipt_date_arr);
                    $value[$overdue_status_key] += 2;
                    break;
                case 2:
                    $value = $this->getPayment(
                        $now_date,
                        $value,
                        $main_date_key,
                        $overdue_status_key,
                        $overdue_day_key
                    );
                    $value = $this->listDataUpdate($updateDateShow,
                        $last_pay_date_arr,
                        $value,
                        $main_expect_receipt_date_arr);
                    break;
            }
            unset($value);
        }
        return $data_arr;
    }

    /**
     * @param $now_date
     * @param $value
     * @param $date_key
     * @param $overdue_status_key
     * @param $overdue_day_key
     *
     * @return mixed
     */
    private function getPayment($date, $value, $main_date_key, $overdue_status_key, $overdue_day_key)
    {

        $overdue_status = 0;
        $overdue_day = null;
        if ($value[$main_date_key]) {
            $expeceted_date = new DateTime(date('Y-m-d', strtotime($value[$main_date_key])));
            $temp_overdue_day = $date->diff($expeceted_date)->days;
            if ($date->getTimestamp() > $expeceted_date->getTimestamp() && $temp_overdue_day > 0) {
                $overdue_status = 1;
                $overdue_day = $temp_overdue_day;
            }
        }
        $value[$overdue_status_key] = $overdue_status;
        $value[$overdue_day_key] = $overdue_day;
        return $value;
    }

    /**
     * @param $updateDateShow
     * @param $last_pay_date_arr
     * @param $value
     * @param $main_expect_receipt_date_arr
     *
     * @return mixed
     */
    private function listDataUpdate($updateDateShow, $last_pay_date_arr, $value, $main_expect_receipt_date_arr)
    {
        if ($updateDateShow) {
            $value['actual_receipt_date'] = $last_pay_date_arr[$value['ORDER_ID'] . $value['receiving_code']];
        }
        $value['main_expect_receipt_date'] = $main_expect_receipt_date_arr[$value['ORDER_ID'] . $value['receiving_code']];
        return $value;
    }

    /**
     * @param $ids
     * @param bool $is_excel
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function orderExcelExport($ids, $is_excel = true)
    {
        $many_sheet_contents = [
            'order_info' => $this->getExcelOrderInfo($ids),
            'ship_info' => $this->getExcelShipInfo($ids),
            'receipt_info' => $this->getExcelReceiptInfo($ids),
            'deduction_info' => $this->getExcelDeductionInfo($ids),
        ];

        $order_info_value_key = array_flip(array_column($many_sheet_contents['order_info'], 'ORDER_ID'));
        $B2b = new B2bAction();
        $many_sheet_contents = $this->joinOrderInfoAssembly($B2b, $many_sheet_contents, $ids);
        $many_sheet_contents = $this->joinShipInfoAssembly($B2b, $many_sheet_contents, $ids);
        $many_sheet_contents['ship_info'] = CodeModel::autoCodeTwoVal($many_sheet_contents['ship_info'], ['po_currency']);
        $many_sheet_contents['ship_info'] = $this->orderStatusToVal($many_sheet_contents['ship_info']);
        $many_sheet_contents['receipt_info'] = array_merge(
            DataModel::toArray($many_sheet_contents['receipt_info']),
            DataModel::toArray($this->appendHistoryReceipt($ids)));
        $many_sheet_contents['receipt_info'] = CodeModel::autoCodeTwoVal($many_sheet_contents['receipt_info'],
            [
                'po_currency',
                'currency_code',
                'company_code',
                'original_currency',
            ]);
        $many_sheet_contents['receipt_info'] = array_map(function ($value) {
            $value['claim_amount_to_po_currency'] = round($value['claim_amount'] * ExchangeRateModel::conversion(
                    $value['currency_code_val'],
                    $value['po_currency_val'],
                    $value['po_time'],
                    false), 2);
            return $value;
        }, $many_sheet_contents['receipt_info']);
        $many_sheet_contents['deduction_info'] = array_merge(
            DataModel::toArray($many_sheet_contents['deduction_info']),
            DataModel::toArray($this->appendHistoryDeduction($ids)));
        $many_sheet_contents['deduction_info'] = CodeModel::autoCodeTwoVal(
            $many_sheet_contents['deduction_info'], [
            'po_currency',
            'deduction_type',
        ]);
        if (false !== $is_excel) { // 仅限于导出时补充字段
            $many_sheet_contents['order_info'] = $this->getEstimatedProfitInfo($many_sheet_contents['order_info']); // 获取预估利润相关信息
        }


        $many_sheet_contents = $this->mapExcelOrderInfo($many_sheet_contents, $order_info_value_key);
        if (false === $is_excel) {
            return $many_sheet_contents;
        }
        $many_sheet_cell_names = [
            'order_info' => $this->order_column_name,
            'ship_info' => $this->shipping_detail,
            'receipt_info' => $this->receipt_detail,
            'deduction_info' => $this->deduction_detail,
        ];
        $sheet_name_maps = $this->order_excel_sheet_names;
        $Excel = new ExcelModel();
        $Excel->manySheetExport(
            'B2B订单列表',
            $many_sheet_cell_names,
            $many_sheet_contents,
            $sheet_name_maps
        );
    }

    /**
     * @param $ids
     * @return array
     */
    private function appendHistoryReceipt($ids)
    {
        $data = $this->B2bRepository->getAppendClaims($ids, true);
        $res = [];
        foreach ($data as $datum) {
            $temp_res['PO_ID'] = $datum['PO_ID'];
            $temp_res['ORDER_ID'] = $datum['ORDER_ID'];
            $temp_res['THR_PO_ID'] = $datum['THR_PO_ID'];
            $temp_res['po_time'] = $datum['po_time'];
            $temp_res['po_currency'] = $datum['po_currency'];
            $temp_res['currency_code'] = $datum['po_currency'];
            $temp_res['claim_amount'] = $datum['actual_payment_amount'];
            $temp_res['created_by'] = $datum['operator_id'];
            $temp_res['created_at'] = $datum['updated_at'];
            $temp_res['opp_company_name'] = $datum['company_our'];
            $temp_res['transfer_time'] = DateModel::toYmd($datum['actual_receipt_date']);
            $res[] = $temp_res;
        }
        return $res;
    }

    /**
     * @param $ids
     * @return array
     */
    private function appendHistoryDeduction($ids)
    {
        $data = $this->B2bRepository->getAppendClaimsDeductions($ids, true);
        $data = CodeModel::autoCodeTwoVal($data, ['DEVIATION_REASON']);
        $res = [];
        foreach ($data as $datum) {
            $temp_res['PO_ID'] = $datum['PO_ID'];
            $temp_res['ORDER_ID'] = $datum['ORDER_ID'];
            $temp_res['THR_PO_ID'] = $datum['THR_PO_ID'];
            $temp_res['po_currency'] = $datum['po_currency'];
            $temp_res['deduction_type'] = null;
            $temp_res['instructions'] = $datum['DEVIATION_REASON_val'];
            $temp_res['deduction_amount'] = $datum['expect_receipt_amount'] - $datum['actual_payment_amount'];;
            $temp_res['updated_by'] = $datum['operator_id'];
            $temp_res['updated_at'] = $datum['updated_at'];
            $res[] = $temp_res;
        }
        return $res;
    }

    /**
     * @param $ids
     * @return array|float|int
     */
    private function getExcelOrderInfo($ids)
    {
        $accounts        = $this->B2bRepository->getOrderAccounts($ids);
        $accounts        = CodeModel::autoCodeTwoVal($accounts, ['delivery_method', 'invoice_code', 'tax_point']);

        $accounts_output = $this->orderStatusToVal($accounts);
        $accounts_output = $this->codeStatusToValue($accounts_output);
        $accounts_output = $this->joinPoAmountExcludingTax($accounts_output);
        return $accounts_output;
    }

    /**
     * @param $ids
     * @return mixed
     */
    private function getExcelShipInfo($ids)
    {
        $db_data = $this->B2bRepository->getExcelShipInfo($ids);
        return $db_data;
    }

    /**
     * @param $ids
     * @return mixed
     */
    private function getExcelReceiptInfo($ids)
    {
        $db_data = $this->B2bRepository
            ->getExcelReceiptInfo($ids);
        return $db_data;
    }

    /**
     * @param $ids
     * @return mixed
     */
    private function getExcelDeductionInfo($ids)
    {
        $db_data = $this->B2bRepository
            ->getExcelDeductionInfo($ids);
        return $db_data;
    }


    /**
     * @param $accounts
     * @return array
     */
    private function codeStatusToValue($accounts)
    {
        $return_res = array_map(function ($account) {
            if ($account['last_actual_receipt_date']) {
                $account['last_actual_receipt_date'] = DateModel::toYmd($account['last_actual_receipt_date']);
            }
            switch ($account['receipt_state']) {
                case 0:
                    $account['receipt_state'] = '未收款';
                    break;
                case 1:
                    $account['receipt_state'] = '部分收款';
                    break;
                case 2:
                    $account['receipt_state'] = '全部收款';
                    break;

            }
            switch ($account['order_overdue_statue']) {
                case 0:
                    $account['order_overdue_statue'] = '未逾期';
                    break;
                case 1:
                    $account['order_overdue_statue'] = '逾期';
                    break;
            }
            return $account;
        }, $accounts);
        return $return_res;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getDeliveryMail()
    {
        $db_results_arr = $this->B2bRepository
            ->getDeliveryWithinOrder();
        $all_order_warehouses = $this->B2bRepository
            ->getOrderTakeWarehouses(array_column($db_results_arr, 'PO_ID'));
        $all_order_warehouses_key_vel = array_column($all_order_warehouses, 'warehouses', 'ORD_ID');
        $db_results_arr = array_map(function ($temp_value) use ($all_order_warehouses_key_vel) {
            $temp_value['warehouses'] = $all_order_warehouses_key_vel[$temp_value['PO_ID']];
            return $temp_value;
        }, $db_results_arr);
        if (empty($db_results_arr)) {
            throw new Exception('查询数据为空');
        }
        $res_arr = CodeModel::autoCodeTwoVal($db_results_arr, ['SALES_TEAM'], 'all');
        $res_arr = $this->toBeyondNumber($res_arr, 'delivery_time');
        $res_arr = $this->targetPortToArr($res_arr);
        return $res_arr;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getTallyMail()
    {
        $db_results_arr = $this->B2bRepository
            ->getTallyWithinOrder();
        if (empty($db_results_arr)) {
            throw new Exception('查询数据为空');
        }
        $res_arr = CodeModel::autoCodeTwoVal($db_results_arr, ['SALES_TEAM', 'warehouse'], 'all');
        $res_arr = $this->toBeyondNumber($res_arr, 'Estimated_arrival_DATE');
        $res_arr = $this->targetPortToArr($res_arr);
        return $res_arr;
    }

    /**
     * @param array $data
     * @return array
     */
    private function targetPortToArr(array $data)
    {
        $area_init = B2bModel::get_area(true);
        $data = array_map(function ($value) use ($area_init) {
            $temp_arr = json_decode($value['TARGET_PORT'], true);
            $addrNames['country'] = isset($area_init[$temp_arr['country']]) ? $area_init[$temp_arr['country']] : '';
            $addrNames['stareet'] = isset($area_init[$temp_arr['stareet']]) ? $area_init[$temp_arr['stareet']] : '';
            $addrNames['city'] = isset($area_init[$temp_arr['city']]) ? $area_init[$temp_arr['city']] : '';
            $addrNames['targetCity'] = isset($temp_arr['targetCity']) ? $temp_arr['targetCity'] : '';
            $value['target_city'] = $addrNames['country'] . '-' . $addrNames['stareet'] . '-' . $addrNames['city'];
            $value['target_port'] = $value['target_city'] . '-' . $addrNames['targetCity'];
            return $value;
        }, $data);
        return $data;
    }

    /**
     * @param $data
     * @param string $key
     * @return array|float|int
     */
    public function joinPoAmountExcludingTax($data, $key = 'TAX_POINT')
    {
        $data = CodeModel::autoCodeTwoVal($data, [$key]);
        $data = DataModel::percentageToDecimal($data, $key . '_val');
        $data = array_map(function ($value) use ($key) {
            $value['po_amount_excluding_tax'] = sprintf("%0.2f", $value['po_amount'] / (1 + $value[$key . '_val_decimal']));
            if (!$value['po_amount_excluding_tax']) {
                $value['po_amount_excluding_tax'] = 0.00;
            }
            return $value;
        }, $data);
        return $data;
    }

    /**
     * @param $request_data
     * @param bool $is_excel
     * @return mixed
     */
    public function searchClaimList($request_data, $is_excel = false)
    {
        $join_where_string = null;
        $temp_where = [];
        if ('N002550100' == $request_data['search']['claim_status']) {
            unset($request_data['search']['claim_status']);
            $Model = new Model();
            $where['claim_status'] = 'N002550200';
            $temp_res = $Model->table('tb_fin_account_turnover_status')
                ->where($where)
                ->select();
            $temp_id = array_column($temp_res, 'account_turnover_id');
            if ($temp_id) {
                $temp_where['tb_fin_account_turnover.id'] = ['NOT IN', $temp_id];
            }
        }
        list($join_where, $limit) = $this->joinSearchClaimWhere($request_data);
        $join_where = array_merge($join_where, $temp_where);
        list($res_db, $pages) = $this->B2bRepository->getClaimList($join_where, $limit, $is_excel);
        $res_return['data'] = $this->claimListCodeConversion($res_db);
        $res_return['pages'] = $pages;
        return $res_return;
    }

    /**
     * @param $data
     * @return array
     */
    private function claimListCodeConversion($data)
    {
        $data = CodeModel::autoCodeTwoVal($data, ['original_currency', 'claim_status', 'company_code']);
        return $data;
    }

    /**
     * @param $request_data
     * @return array
     */
    private function joinSearchClaimWhere($request_data)
    {
        $search_map = [
            'claim_status' => 'tb_fin_account_turnover_status.claim_status',
            'running_id' => 'tb_fin_account_turnover.account_transfer_no',
            'payment_name' => 'tb_fin_account_turnover.opp_company_name',
            'created_at' => 'tb_fin_account_turnover.create_time',
        ];
        return WhereModel::joinSearchTemp($request_data, $search_map);
    }

    /**
     * @param $request_data
     * @return array|mixed
     */
    public function getClaimDetail($request_data)
    {
        $where = $this->buildClaimDetailWhere($request_data);
        $res_db['info'] = $this->B2bRepository->getClaimDetailInfo($where, $request_data);
        $res_db['clime_order'] = $this->B2bRepository->getClaimOrders($where);

        $PurRepository = new PurRepository();
        $pur_orders = $PurRepository->getClaimOrders($where);
        foreach ($pur_orders as &$value) {
            $value['claim_code'] = 'N002630200';
            $res_db['clime_order'][] = $value;
        }
        //GP订单
        $gp_orders = $this->getClaimGpOrders($where);
        foreach ($gp_orders as &$gv) {
            $gv['claim_code'] = 'N002630201';
            $res_db['clime_order'][] = $gv;
        }

        $res_data = $this->claimDetailConversion($res_db);
        $res_data['basis']['pay_or_rec_val'] = BaseModel::checkTransactionType($res_data['basis']['this_transfer_type']);
        return $res_data;
    }

    /**
     * @param $data
     * @return array
     */
    private function buildClaimDetailWhere($data)
    {
        $map = [
            'account_transfer_no' => 'tb_fin_account_turnover.account_transfer_no',
            'claim_id' => 'tb_fin_claim.id',
        ];
        $wheres = DataModel::joinDbWheres($data, $map);
        return $wheres;
    }

    /**
     * @param $data
     * @param array $res
     * @return array|mixed
     */
    private function claimDetailConversion($data, $res = [])
    {
        $res = $this->joinDetailBasis($data, $res);
        $res = $this->joinDetailAmount($data, $res);
        $res = $this->joinDetailClaim($data, $res);
        $res['clime_order'] = DataModel::toArray(
            $this->joinClimeOrder($data['clime_order'], $data['info']['original_currency'])
        );
        $res['basis'] = $this->orderStatusToVal($res['basis'], true);
        unset($res['basis']['pay_or_rec_val'], $res['basis']['pay_or_rec']);
        $res['basis'] = CodeModel::autoCodeOneVal($res['basis'], [
            'currency_code',
            'company_code',
            'transfer_type',
        ]);
        $res['amount'] = CodeModel::autoCodeOneVal($res['amount'], [
            'original_currency',
            'other_currency',
            'remitter_currency',
            'currency_code',
        ]);
        $res['claim'] = CodeModel::autoCodeOneVal($res['claim'], [
            'original_currency',
            'currency_code',
            'claim_status',
        ]);
        return $res;
    }

    /**
     * @param $request_data
     * @return array
     */
    public function getOrderSelect($request_data)
    {
        $wheres = $this->joinOrderSelectWhere($request_data);
//        $wheres['tb_b2b_receivable.order_account'] = ['GT',0];
        $res_db = $this->B2bRepository->getOrderSelect($wheres);
        $res_db = $this->orderSelectConversion($res_db);
        return $res_db;
    }

    /**
     * @param $data
     * @return array
     */
    private function joinOrderSelectWhere($data)
    {
        $map = [
            'client_name' => 'tb_b2b_info.CLIENT_NAME',
            'account_transfer_id' => 'tb_fin_claim.account_turnover_id',
            'sale_pur_person' => 'tb_b2b_info.PO_USER'
        ];
        $like_keys = [
            'client_name',
        ];
        $wheres = DataModel::joinDbWheres($data, $map, $like_keys);
        /*if ($data['search_type'] && $data['search_value']) {
            switch ($data['search_type']) {
                case 'PO_ID':
                    $where_order_key = 'tb_b2b_info.PO_ID';
                    break;
                case 'THR_PO_ID':
                    $where_order_key = 'tb_b2b_info.THR_PO_ID';
                    break;
            }
            if ($where_order_key) {
                $wheres[$where_order_key] = ['LIKE', "%{$data['search_value']}%"];
            }
        }*/
        if ($data['search_value']) {
            $conditions['tb_b2b_info.PO_ID']           = $data['search_value'];
            $conditions['tb_b2b_info.THR_PO_ID'] = $data['search_value'];
            $conditions['_logic'] = 'or';
            $wheres['_complex']   = $conditions;
        }
        return $wheres;
    }

    /**
     * @param $res_db
     * @return array
     */
    private function orderSelectConversion($res_db)
    {
        foreach ($res_db as $key => &$value) {
            if ($value['claim_id']) {
                unset($res_db[$key]);
            }
            $value['claim_code'] = self::B2B_CLAIM_CODE;
        }
        $res_db = array_values($res_db);
        $res_db = CodeModel::autoCodeTwoVal($res_db, ['po_currency', 'SALES_TEAM', 'receivable_status', 'claim_code']);
        $res_db = $this->orderStatusToVal($res_db);
        return $res_db;
    }

    /**
     * @param $data
     * @param $Model
     * @throws Exception
     */
    public function postClaimSubmit($data, $Model)
    {
        $this->tbPurActionLogModel = new TbPurActionLogModel();
        foreach ($data['orders'] as $order) {
            $claim_type = $order['claim_type'];
            if ($claim_type == self::B2B_CLAIM_CODE) {
                //采购退款没有这些逻辑
                $where_receivable = $this->joinReceivableWhere($order['order_info']['ORDER_ID']);
                $save_receivable = $this->joinPatchReceivableSave();
                if (false === $Model->table('tb_b2b_receivable')->where($where_receivable)->save($save_receivable) ||
                    false === $Model->table('tb_b2b_receivable')->where($where_receivable)->setInc('actual_collection', $order['order_info']['claim_amount'])
                ) {
                    Logs([$where_receivable, $save_receivable]);
                    throw new Exception('更新应收失败');
                }
            }
//            $add_claim = $this->joinPostClaimAdd($data['account_transfer_id'], $data['sale_team'], $order, $claim_type);
            $add_claim = $this->joinPostClaimAdd($data['account_transfer_id'], $order['order_info']['sale_team'], $order, $claim_type);
            if ($order['order_info']['claim_id']) {
                $where_claim['id'] = $order['order_info']['claim_id'];
                if ($claim_type == self::B2B_CLAIM_CODE) {
                    $where_claim['order_type'] = 'N001950200';
                } else if ($claim_type == self::PUR_REFUND_CLAIM_CODE) {
                    $where_claim['order_type'] = 'N001950600';
                } else if ($claim_type == B2bAction::GP_BIG_ORDER_CLAIM_CODE) {
                    $where_claim['order_type'] = 'N001950656';
                }
                unset($add_claim['created_by'], $add_claim['created_at']);
                $claim_res = $Model->table('tb_fin_claim')
                    ->where($where_claim)
                    ->save($add_claim);
                $claim_id = $order['order_info']['claim_id'];
            } else {
                $claim_id = $claim_res = $claim_res = $Model->table('tb_fin_claim')->add($add_claim);
            }
            if (false === $claim_res) {
                Logs($add_claim);
                throw new Exception('流水认领关联失败');
            }
            $where_claim_all['claim_id'] = $order['order_info']['claim_id'];
            $all_claim_deductions = $Model->table('tb_b2b_claim_deduction')
                ->field('id')
                ->where($where_claim_all)
                ->select();
            $all_deductions_ids = array_column($all_claim_deductions, 'id');
            $now_deductions_ids = array_column($order['deductions'], 'id');
            $del_deductions_ids = array_diff($all_deductions_ids, $now_deductions_ids);
            if ($all_deductions_ids && $del_deductions_ids) {
                $where_claim_del['id'] = ['IN', $del_deductions_ids];
                $del_res = $Model->table('tb_b2b_claim_deduction')
                    ->where($where_claim_del)
                    ->delete();
                if (false === $del_res) {
                    throw new Exception(L('删除扣费失败'));
                }
            }
            if ($order['deductions']) {
                foreach ($order['deductions'] as $deduction) {
                    if ($deduction['id']) {
                        $where_claim_deductions['id'] = $deduction['id'];
                        $save_claim_deductions = $this->joinPostClaimDeductionUpdate($deduction);
                        $claim_deduction = $Model->table('tb_b2b_claim_deduction')
                            ->where($where_claim_deductions)
                            ->save($save_claim_deductions);
                    } else {
                        $add_deductions = $this->joinPostClaimDeductionAdd($claim_id, $deduction);
                        $claim_deduction = $Model->table('tb_b2b_claim_deduction')->addAll($add_deductions);
                    }
                    if (false === $claim_deduction) {
                        Logs($add_deductions);
                        throw new Exception('收款认领扣费操作失败');
                    }
                }


            }

            if ($claim_type == self::PUR_REFUND_CLAIM_CODE) {
                if ($order['order_info']['claim_id']) {
                    $action_name = 'edit_pur_claim';
                } else {
                    $action_name = 'add_pur_claim';
                }
                $relevance_id = $Model->table('tb_pur_relevance_order')
                    ->where(['order_id' => $order['order_info']['ORDER_ID']])
                    ->getField('relevance_id');
                $this->tbPurActionLogModel->addLog($relevance_id, $action_name);
            }
        }
        if (1 == $data['is_end']) {
            $where_turnover_status['account_turnover_id'] = $data['account_transfer_id'];
            $save_turnover_status['claim_status'] = 'N002550200';
            $save_turnover_status['account_turnover_id'] = $data['account_transfer_id'];
            $save_turnover_status['tag_by'] = $save_turnover_status['updated_by'] = DataModel::userNamePinyin();
            $save_turnover_status['tag_at'] = $save_turnover_status['updated_at'] = DateModel::now();
            $status_res = $Model->table('tb_fin_account_turnover_status')
                ->where($where_turnover_status)
                ->save($save_turnover_status);
            if (!$status_res) {
                $save_turnover_status['created_by'] = DataModel::userNamePinyin();
                $save_turnover_status['created_at'] = DateModel::now();
                $status_res = $Model->table('tb_fin_account_turnover_status')
                    ->add($save_turnover_status);
            }
            if (false === $status_res) {
                throw new Exception('更新流水状态失败');
            }
        }
        $where_check_excess['tb_fin_claim.account_turnover_id'] = ['EQ', $data['account_transfer_id']];
        $where_check_excess['tb_fin_claim.order_type'] = ['in', ['N001950200', 'N001950600', 'N001950656']];
        $where_check_excess_string = 'tb_fin_account_turnover.id = tb_fin_claim.account_turnover_id';
        $check_excess = $Model->table('tb_fin_account_turnover,tb_fin_claim')
            ->field('SUM(tb_fin_claim.claim_amount) AS sum_claim_amount,tb_fin_account_turnover.original_amount')
            ->where($where_check_excess)
            ->where($where_check_excess_string, null, true)
            ->find();
        if ($check_excess['sum_claim_amount'] > $check_excess['original_amount']) {
            throw new Exception(L('认领金额超出原始金额'));
        }
    }

    /**
     * @param $data
     * @return mixed
     */
    private function joinPostClaimDeductionUpdate($data)
    {
        $save['claim_id'] = $data['claim_id'];
        $save['deduction_type'] = $data['deduction_type'];
        $save['deduction_amount'] = $data['deduction_amount'];
        $save['credentials_show_name'] = $data['credentials_show_name'];
        $save['credentials_path'] = json_encode($data['credentials_path']);
        $save['instructions'] = $data['instructions'];
        $save['updated_by'] = DataModel::userNamePinyin();
        $save['updated_at'] = DateModel::now();
        return $save;
    }

    /**
     * @param $claim_id
     * @param $deductions
     * @return array
     */
    private function joinPostClaimDeductionAdd($claim_id, $deductions)
    {
        $adds = [];
        $temp_value = $deductions;
//        foreach ($deductions as $temp_value) {
        $add['claim_id'] = $claim_id;
        $add['deduction_type'] = $temp_value['deduction_type'];
        $add['deduction_amount'] = $temp_value['deduction_amount'];
        $add['credentials_show_name'] = $temp_value['credentials_show_name'];
        $add['credentials_path'] = json_encode($temp_value['credentials_path']);
        $add['instructions'] = $temp_value['instructions'];
        $add['created_by'] = $add['updated_by'] = DataModel::userNamePinyin();
        $add['created_at'] = $add['updated_at'] = DateModel::now();
        $adds[] = $add;
//        }
        return $adds;
    }

    /**
     * @param $order_id
     * @return mixed
     */
    private function joinReceivableWhere($order_id)
    {
        $where['tb_b2b_receivable.order_id'] = $order_id;
        $where['tb_b2b_receivable.receivable_status'] = 'N002540100';
        return $where;
    }

    /**
     * @return mixed
     */
    private function joinPatchReceivableSave()
    {
        $save['updated_by'] = DataModel::userNamePinyin();
        $save['updated_at'] = date('Y-m-d H:i:s');
        return $save;
    }

    /**
     * @param $account_transfer_id
     * @param $sale_team
     * @param $order
     * @return mixed
     */
    private function joinPostClaimAdd($account_transfer_id, $sale_team, $order, $claim_type)
    {
        if($claim_type == B2bAction::GP_BIG_ORDER_CLAIM_CODE) {
            $save['order_type'] = 'N001950656';
            $save['order_no'] = $order['order_info']['order_no'];
        }else{
            if ($claim_type == self::B2B_CLAIM_CODE) {
                $save['order_type'] = 'N001950200';
            } else if ($claim_type == self::PUR_REFUND_CLAIM_CODE) {
                $save['order_type'] = 'N001950600';
            }
            $save['order_no'] = $order['order_info']['PO_ID'];
            $save['sale_teams'] = $sale_team;
        }
        $save['order_id'] = $order['order_info']['order_id'] or $save['order_id'] = $order['order_info']['ORDER_ID'];
        $save['account_turnover_id'] = $account_transfer_id;
        $save['claim_amount'] = $order['order_info']['claim_amount'];
        $save['summary_amount'] = $order['order_info']['summary_amount'];
        $save['current_remaining_receivable'] = $order['order_info']['current_remaining_receivable'];
        $save['updated_by'] = $save['created_by'] = DataModel::userNamePinyin();
        $save['updated_at'] = $save['created_at'] = date('Y-m-d H:i:s');


        return $save;
    }

    /**
     * @param $request_data
     */
    public function updateClaimStatus($request_data)
    {
        $save['claim_status'] = $request_data['claim_status'];
        $save['account_turnover_id'] = $where['account_turnover_id'] = $request_data['account_turnover_id'];
        $this->B2bRepository->updateClaimStatus($where, $save);
    }

    /**
     * @param $claim_ids
     * @param $Model
     * @param $claim_type
     * @throws Exception
     */
    public function deleteClaimAll($claim_ids, $Model)
    {
        $claim_type = $this->claim_type;
        if ($claim_type == self::B2B_CLAIM_CODE) {
            //B2B应收逻辑,大额GP应收
            $where_claim['id'] = ['IN', $claim_ids];
            $where_claim['order_type'] = ['IN', ['N001950200','N001950656']];

            $res_claims = $Model->table('tb_fin_claim')
                ->field('order_id,claim_amount')
                ->where($where_claim)
                ->select();
            foreach ($res_claims as $claim) {
                $where_upd['order_id'] = $claim['order_id'];
                $temp_res = $Model->table('tb_b2b_receivable')
                    ->where($where_upd)
                    ->setDec('actual_collection', $claim['claim_amount']);
                if (false === $temp_res) {
                    throw new Exception('撤回 B2B 应收失败');
                }
            }
        } else if ($claim_type == self::PUR_REFUND_CLAIM_CODE) {
            //只用于记录所用
            $where_claim['tb_fin_claim.id'] = ['IN', $claim_ids];
            $where_claim['tb_fin_claim.order_type'] = 'N001950600';
            $where_string = 'tb_fin_claim.order_id = tb_pur_relevance_order.order_id';
            $res_claims = $Model->table('(tb_fin_claim, tb_pur_relevance_order)')
                ->field('tb_pur_relevance_order.relevance_id')
                ->where($where_claim)
                ->where($where_string)
                ->select();
        }
        $delete_claims = $this->B2bRepository->deleteClaim($claim_ids, $Model);
        if (false === $delete_claims) {
            throw new Exception('删除流水认领关联失败');
        }
        if ($claim_type == self::B2B_CLAIM_CODE) {
            $delete_claim_deduction = $this->B2bRepository->deleteClaimDeduction($claim_ids, $Model);
            if (false === $delete_claim_deduction) {
                throw new Exception('删除流水认领费用失败');
            }
        }

        if ($claim_type == self::PUR_REFUND_CLAIM_CODE) {
            $this->tbPurActionLogModel = new TbPurActionLogModel();
            foreach ($res_claims as $v) {
                $this->tbPurActionLogModel->addLog($v['relevance_id']);
            }
        }
    }

    /**
     * @param $request_data
     * @param bool $is_excel
     * @return mixed
     */
    public function receivableList($request_data, $is_excel = false)
    {
        $order_type = $request_data['search']['order_type'];
        $order_type_value = $request_data['search']['order_type_value'];
        if ($request_data['search']) {
            unset($request_data['search']['order_type'], $request_data['search']['order_type_value']);
        }
        list($wheres, $limit) = $this->joinReceivableListWhere($request_data);

        /*if ($order_type && $order_type_value) {
            switch ($order_type) {
                case 'PO_ID':
                    $where_order_key = 'tb_b2b_info.PO_ID';
                    break;
                case 'THR_PO_ID':
                    $where_order_key = 'tb_b2b_info.THR_PO_ID';
                    break;
            }
            if ($where_order_key) {
                if (strstr($order_type_value, ',')) {
                    $explode_arr = explode(',', $order_type_value);
                    $order_type_value = array_slice($explode_arr, 0, 10);
                }
                $wheres[$where_order_key] = ['IN',$order_type_value];
            }
        }*/
        if ($order_type_value) {
            $explode_arr = strReplaceComma($order_type_value);
            //$order_type_value = array_slice($explode_arr, 0, 10);
            $condition['tb_b2b_info.PO_ID'] = array('IN', $explode_arr);
            $condition['tb_b2b_info.THR_PO_ID'] = array('IN', $explode_arr);
            $condition['_logic'] = 'or';
            $wheres['_complex'] = $condition;
        }
        list($res_db, $pages, $sum_current_receivable_cny) = $this->B2bRepository->getReceivableList($wheres, $limit, $is_excel);
        $res_return['data'] = $this->receivableCodeConversion($res_db);
        $res_return['pages'] = $pages;
        $res_return['sum_current_receivable_cny'] = $sum_current_receivable_cny;
        return $res_return;
    }

    /**
     * @param $request_data
     * @return array
     */
    private function joinReceivableListWhere($request_data)
    {
        $search_map = [
            'receivable_status' => 'tb_b2b_receivable.receivable_status',
            'order_type' => 'PO_ID',
            'CLIENT_NAME' => 'tb_b2b_info.CLIENT_NAME',
            'SALES_TEAM' => 'tb_b2b_info.SALES_TEAM',
            'verification_by' => 'tb_b2b_receivable.verification_by',
            'verification_at' => 'tb_b2b_receivable.verification_at',
            'created_at' => 'tb_b2b_receivable.created_at',
            'start_reminding_date_receipt' => 'tb_b2b_info.start_reminding_date_receipt',
//            'verification_leader_by' => 'tb_b2b_info.verification_leader_by',
        ];
        if ($request_data['search']['start_reminding_date_receipt'] && is_string($request_data['search']['start_reminding_date_receipt'])) {
            $temp = $request_data['search']['start_reminding_date_receipt'];
            $request_data['search']['start_reminding_date_receipt'] = [];
            $request_data['search']['start_reminding_date_receipt'] = [
                'start' => $temp,
                'end' => $temp
            ];
        }
        list($wheres, $limit) = WhereModel::joinSearchTemp($request_data, $search_map);
        if (!is_null($request_data['search']['current_receivable_status']) && '' !== $request_data['search']['current_receivable_status']) {
            switch ($request_data['search']['current_receivable_status']) {
                case 0:
                    $where_condition = 'EQ';
                    break;
                case 1:
                    $where_condition = 'GT';
                    break;
                case -1:
                    $where_condition = 'LT';
                    break;
            }
            $wheres['tb_b2b_receivable.current_receivable'] = [$where_condition, 0];
        }
        if ($request_data['search']['PO_USER'] && '' !== $request_data['search']['PO_USER']) {
            $wheres['tb_b2b_info.PO_USER'] = $request_data['search']['PO_USER'];
        }
        if (!empty($request_data['search']['verification_leader_by'])) {
            $wheres['tb_b2b_info.verification_leader_by'] = ['like', "%{$request_data['search']['verification_leader_by']}%"];
        }
        return [$wheres, $limit];
    }

    /**
     * @param $data
     * @return array
     */
    private function receivableCodeConversion($data)
    {
        $data = CodeModel::autoCodeTwoVal($data, ['SALES_TEAM', 'receivable_status', 'po_currency']);
        return $data;
    }

    /**
     * @param $request_data
     * @return null
     * @throws Exception
     */
    public function updateSubmitCheckSales($request_data)
    {
        $res = null;
        $where_order['ID'] = $where['order_id'] = $request_data['order_id'];
        $where_order['order_state'] = 2;
        $where_order['warehousing_state'] = 2;
        if (true == $request_data['submit_check']) {
            if (!(new Model())->table('tb_b2b_order')
                ->where($where_order)
                ->count()) {
                throw  new  Exception(L('发货状态必须已发货，理货状态必须已理货'));
            }
            $update_arr['remark'] = $request_data['remark'];
            $update_arr['updated_by'] = $update_arr['submit_by'] = DataModel::userNamePinyin();
            $update_arr['updated_at'] = $update_arr['submit_at'] = DateModel::now();
            $update_arr['receivable_status'] = 'N002540200';
            $res = TbB2BReceivable::where($where)
                ->whereNull('submit_by')
                ->whereNull('verification_by')
                ->update($update_arr);
        }
        if (false == $request_data['submit_check']) {
            $update_arr['remark'] = $update_arr['submit_at'] = $update_arr['submit_by'] = null;
            $update_arr['updated_by'] = DataModel::userNamePinyin();
            $update_arr['receivable_status'] = 'N002540100';
            $update_arr['updated_at'] = DateModel::now();
            $res = TbB2BReceivable::where($where)
                ->whereNotNull('submit_by')
                ->whereNull('verification_by')
                ->update($update_arr);
        }
        if (empty($res)) {
            throw new  Exception(L('更新失败,请检查提交状态'));
        }
        return $res;
    }

    /**
     * @param $request_data
     * @return mixed
     * @throws Exception
     */
    public function updateCheckSales($request_data)
    {
        $where['order_id'] = $request_data['order_id'];
        if (true == $request_data['check']) {
            $update_arr['updated_by'] = $update_arr['verification_by'] = DataModel::userNamePinyin();
            $update_arr['verification_at'] = DateModel::now();
            $update_arr['rate_losses'] = $request_data['rate_losses'];
            $update_arr['current_receivable'] = 0;
            $update_arr['cancel_note'] = $request_data['cancel_note'];
            $update_arr['receivable_status'] = 'N002540300';
            $res = TbB2BReceivable::where($where)
                ->whereNotNull('submit_by')
                ->whereNull('verification_by')
                ->update($update_arr);
        }
        if (false == $request_data['check']) {
            $update_arr['updated_by'] = DataModel::userNamePinyin();
            $update_arr['verification_at'] = null;
            $update_arr['verification_by'] = null;
            $update_arr['rate_losses'] = 0;
            $update_arr['current_receivable'] = $request_data['rate_losses'];
            $update_arr['cancel_note'] = null;
            $update_arr['updated_at'] = DateModel::now();
            $update_arr['receivable_status'] = 'N002540200';
            $res = TbB2BReceivable::where($where)
                ->whereNotNull('submit_by')
                ->whereNotNull('verification_by')
                ->update($update_arr);
        }
        if (empty($res)) {
            throw new  Exception(L('更新失败'));
        }
        return $res;
    }

    /**
     * @param $data
     * @param $res
     * @return mixed
     */
    private function joinDetailBasis($data, $res)
    {
        $res['basis']['account_transfer_id'] = $data['info']['account_transfer_id'];
        $res['basis']['account_transfer_no'] = $data['info']['account_transfer_no'];

        $res['basis']['transfer_type'] = $data['info']['transfer_type'];
        $res['basis']['this_transfer_type'] = $data['info']['this_transfer_type'];

        $res['basis']['currency_code'] = $data['info']['currency_code'];
        $res['basis']['amount_money'] = $data['info']['amount_money'];

        $res['basis']['company_code'] = $data['info']['company_code'];
        $res['basis']['opp_company_name'] = $data['info']['opp_company_name'];
        $res['basis']['our_bank'] = $data['info']['our_bank'];
        $res['basis']['opp_account_bank'] = $data['info']['opp_account_bank'];
        $res['basis']['our_open_bank'] = $data['info']['our_open_bank'];
        $res['basis']['opp_open_bank'] = $data['info']['opp_open_bank'];
        $res['basis']['our_swift_code'] = $data['info']['our_swift_code'];
        $res['basis']['other_swift_code'] = $data['info']['other_swift_code'];

        $res['basis']['credentials_file'] = DataModel::jsonToArr($data['info']['credentials_file']);
        $res['basis']['transfer_time'] = $data['info']['transfer_time'];
        $res['basis']['create_user'] = $data['info']['create_user'];
        $res['basis']['create_time'] = $data['info']['create_time'];
        $res['basis']['transfer_type'] = $data['info']['transfer_type'];
        $res['basis']['remark'] = $data['info']['remark'];
        $res['basis']['our_remark'] = $data['info']['our_remark'];
        return $res;
    }

    /**
     * @param $data
     * @param $res
     * @return mixed
     */
    private function joinDetailAmount($data, $res)
    {
        $res['amount']['original_currency'] = $data['info']['original_currency'];
        $res['amount']['original_amount'] = (double)$data['info']['original_amount'];
        $res['amount']['amount_money'] = (double)$data['info']['amount_money'];
        $res['amount']['other_currency'] = $data['info']['other_currency'];
        $res['amount']['currency_code'] = $data['info']['currency_code'];
        $res['amount']['other_cost'] = (double)$data['info']['other_cost'];
        $res['amount']['remitter_currency'] = $data['info']['remitter_currency'];
        $res['amount']['remitter_cost'] = $data['info']['remitter_cost'];
        return $res;
    }

    /**
     * @param $data
     * @param $res
     * @return mixed
     */
    private function joinDetailClaim($data, $res)
    {
        $res['claim']['original_currency'] = $data['info']['original_currency'];
        $res['claim']['original_amount'] = (double)$data['info']['original_amount'];
        $res['claim']['currency_code'] = $data['info']['currency_code'];
        $res['claim']['claim_status'] = $data['info']['claim_status'];
        $res['claim']['claimed_amount'] = (double)$data['info']['sum_claim_amount'];
        $res['claim']['waiting_claimed_amount'] = $res['claim']['original_amount'] - $res['claim']['claimed_amount'];
        $res['claim']['tag_by'] = $data['info']['tag_by'];
        $res['claim']['tag_at'] = $data['info']['tag_at'];
        return $res;
    }

    /**
     * @param $orders
     * @param $original_currency
     * @return array
     */
    private function joinClimeOrder($orders, $original_currency)
    {
        $temp_orders = [];
        $claim_ids = array_column($orders, 'claim_id');
        if ($claim_ids) {
            $where['claim_id'] = ['IN', $claim_ids];
            $claim_deductions = $this->B2bRepository
                ->getClaimDeduction($where);
            foreach ($claim_deductions as $claim_deduction) {
                $claim_deduction['deduction_amount'] = (double)$claim_deduction['deduction_amount'];
                $claim_deduction['credentials_path'] = DataModel::jsonToArr($claim_deduction['credentials_path']);
                $join_claim_deductions[$claim_deduction['claim_id']][] = $claim_deduction;
            }
        }
        foreach ($orders as $order) {
            $temp_order = [];
            if (!isset($order['procurement_number'])) {
                if ($order){
                    if($order['claim_code'] == B2bAction::GP_BIG_ORDER_CLAIM_CODE){
                        $temp_order['order_info']['po_time'] = $order['po_time'];
                        $temp_order['order_info']['order_id'] = $order['order_id'];
                        $temp_order['order_info']['pay_total_price'] = $order['pay_total_price'];
                        $temp_order['order_info']['store_name'] = $order['store_name'];
                        $temp_order['order_info']['pay_currency'] = $order['pay_currency'];
                        $temp_order['order_info']['order_id'] = $order['order_id'];
                        $temp_order['order_info']['order_no'] = $order['order_no'];
                        $temp_order['order_info']['plat_cd'] = $order['plat_cd'];
                        $temp_order['order_info']['claim_id'] = $order['claim_id'];
                        $temp_order['order_info']['claim_currency'] = $original_currency;
                        $temp_order['order_info']['claim_amount'] = (double)$order['claim_amount'];
                        $temp_order['order_info']['created_at'] =  $order['created_at'];
                        $temp_order['order_info']['claim_code'] = $order['claim_code'];
                    }else{
                        $temp_order['order_info']['PO_ID'] = $order['PO_ID'];
                        $temp_order['order_info']['THR_PO_ID'] = $order['THR_PO_ID'];
                        $temp_order['order_info']['ORDER_ID'] = $order['ORDER_ID'];
                        $temp_order['order_info']['po_time'] = $order['po_time'];
                        $temp_order['order_info']['PO_USER'] = $order['po_user'];
                        $temp_order['order_info']['po_currency'] = $order['po_currency'];
                        $temp_order['order_info']['claim_id'] = $order['claim_id'];
                        $temp_order['order_info']['CLIENT_NAME'] = $order['CLIENT_NAME'];
                        $temp_order['order_info']['SALES_TEAM'] = $order['SALES_TEAM'];
                        $temp_order['order_info']['order_state'] = $order['order_state'];
                        $temp_order['order_info']['warehousing_state'] = $order['warehousing_state'];
                        $temp_order['order_info']['receivable_status'] = $order['receivable_status'];
                        $temp_order['order_info']['claim_currency'] = $original_currency;
                        $temp_order['order_info']['claim_amount'] = (double)$order['claim_amount'];
                        $temp_order['order_info']['claim_code'] = $order['claim_code'];
                    }
                }
            } else {
                if ($order)
                    $temp_order['order_info']['order_id'] = $order['order_id'];
                $temp_order['order_info']['procurement_number'] = $order['procurement_number'];
                $temp_order['order_info']['online_purchase_order_number'] = $order['online_purchase_order_number'];
                $temp_order['order_info']['supplier_id'] = $order['supplier_id'];
                $temp_order['order_info']['relevance_id'] = $order['relevance_id'];
                $temp_order['order_info']['amount_currency'] = $order['amount_currency'];
                $temp_order['order_info']['amount'] = $order['amount'];
                $temp_order['order_info']['payment_company'] = $order['payment_company'];
                $temp_order['order_info']['warehouse_status'] = $order['warehouse_status'];
                $temp_order['order_info']['payment_status'] = $order['payment_status'];
                $temp_order['order_info']['prepared_by'] = $order['prepared_by'];

                $temp_order['order_info']['has_refund'] = $order['has_refund'];
                $temp_order['order_info']['has_return_goods'] = $order['has_return_goods'];
                $temp_order['order_info']['claim_currency'] = $original_currency;
                $temp_order['order_info']['claim_amount'] = $order['claim_amount'];
                $temp_order['order_info']['claim_code'] = $order['claim_code'];
                $temp_order['order_info']['po_time'] = $order['po_time'];
                $temp_order['order_info']['sale_team'] = $order['sale_team'];
                $temp_order['order_info']['claim_status'] = $order['claim_status'];
                $temp_order['order_info']['amount_in_warehouse_ori'] = $order['amount_in_warehouse_ori'];
                $temp_order['order_info']['return_goods_amount_ori'] = $order['return_goods_amount_ori'];
                $temp_order['order_info']['amount_claim_ori'] = $order['amount_claim_ori'];
                $temp_order['order_info']['amount_pay_ori'] = $order['amount_pay_ori'];
                $temp_order['order_info']['claim_id'] = $order['claim_id'];
                $temp_order['order_info']['amount_claim_ori_other'] = $order['amount_claim_ori_other'];
            }

            $temp_order['deductions'] = $join_claim_deductions[$order['claim_id']];
            $temp_order['deductions'] = DataModel::toArray($temp_order['deductions']);

            $temp_order['order_summary']['summary_amount'] = $order['summary_amount'];
            if (!isset($order['procurement_number'])) {
                $temp_order['order_summary']['current_remaining_receivable'] = $order['current_receivable'];
            } else {
                $money_on_way = (float)(D('Purchase/Relevance', 'Logic')->orderDetail($order['relevance_id'])['finance']['money_on_way']);
                $temp_order['order_summary']['current_remaining_receivable'] = $money_on_way;
                //$temp_order['order_summary']['current_remaining_receivable'] = $money_on_way - $order['summary_amount'];
            }


            $temp_order['order_summary']['updated_by'] = $order['updated_by'];
            $temp_order['order_summary']['updated_at'] = $order['updated_at'];

            if (!isset($order['procurement_number'])) {
                $temp_order['order_info'] = CodeModel::autoCodeOneVal($temp_order['order_info'], [
                    'SALES_TEAM',
                    'receivable_status',
                    'claim_currency',
                    'po_currency',
                    'claim_code',
                ]);
            } else {
                $temp_order['order_info'] = CodeModel::autoCodeOneVal($temp_order['order_info'], [
                    'amount_currency',
                    'payment_company',
                    'claim_code',
                    'claim_currency',
                    'sale_team',
                    'claim_status',
                ]);
            }
            $temp_order['deductions'] = CodeModel::autoCodeTwoVal($temp_order['deductions'], ['deduction_type']);
            if (!isset($order['procurement_number'])) {
                $temp_order['order_info'] = $this->orderStatusToVal($temp_order['order_info'], true);
                if($temp_order['order_info']['claim_code'] == B2bAction::GP_BIG_ORDER_CLAIM_CODE){
                    $temp_order['claim_type'] = B2bAction::GP_BIG_ORDER_CLAIM_CODE;
                }
            } else {
                $temp_order['order_info'] = (new PurService())->orderStatusToVal($temp_order['order_info'], true);
                $temp_order['claim_type'] = self::PUR_REFUND_CLAIM_CODE;
            }
            $temp_orders[] = $temp_order;
        }
        return $temp_orders;
    }

    /**
     * @param $request_data
     * @return mixed
     */
    public function getReceivableDetail($request_data)
    {
        $res = $this->B2bRepository
            ->getReceivableDetail($request_data['order_id']);
        $res = $this->joinReceivableDetailReturn($res);
        $res = $this->appendHistoryReceiptRecord($res);
        $res = $this->receivableDetailCodeConversion($res);
        return $res;
    }


    /**
     * @param $res
     * @return mixed
     */
    private function receivableDetailCodeConversion($res)
    {
        $res['info'] = CodeModel::autoCodeOneVal($res['info'], [
            'SALES_TEAM',
            'receivable_status',
            'po_currency',
        ]);
        $res['info'] = $this->orderStatusToVal($res['info'], true);
        $res['claims'] = CodeModel::autoCodeTwoVal($res['claims'], ['company_code', 'original_currency']);
        $res['claim_deductions'] = CodeModel::autoCodeTwoVal($res['claim_deductions'],
            ['deduction_type', 'original_currency']
        );
        return $res;
    }

    /**
     * @param $data
     * @return mixed
     */
    private function joinReceivableDetailReturn($data)
    {
        $temp_data['info']['PO_ID'] = $data['info']['PO_ID'];
        $temp_data['info']['THR_PO_ID'] = $data['info']['THR_PO_ID'];
        $temp_data['info']['order_id'] = $data['info']['ORDER_ID'];
        $temp_data['info']['CLIENT_NAME'] = $data['info']['CLIENT_NAME'];
        $temp_data['info']['po_currency'] = $data['info']['po_currency'];
        $temp_data['info']['SALES_TEAM'] = $data['info']['SALES_TEAM'];
        $temp_data['info']['sales_assistant_by'] = $data['info']['sales_assistant_by'];

        $temp_data['info']['order_state'] = $data['info']['order_state'];
        $temp_data['info']['remark'] = $data['info']['remark'];
        $temp_data['info']['sum_power_all'] = $data['info']['sum_power_all'];

        $temp_data['info']['warehousing_state'] = $data['info']['warehousing_state'];
        $temp_data['info']['sum_warehousing_all'] = $data['info']['sum_warehousing_all'];

        $temp_data['info']['receivable_status'] = $data['info']['receivable_status'];
        $temp_data['info']['current_receivable'] = $data['info']['current_receivable'];

        $temp_data['info']['actual_collection'] = $data['info']['actual_collection'];
        $temp_data['info']['sum_deduction_amount'] = $data['info']['sum_deduction_amount'];
        $temp_data['info']['PO_USER'] = $data['info']['PO_USER'];
        $temp_data['info']['po_time'] = $data['info']['po_time'];
        $temp_data['info']['start_reminding_date_receipt'] = $data['info']['start_reminding_date_receipt'];
        $temp_data['info']['verification_leader_by'] = $data['info']['verification_leader_by'];
        foreach ($data['claims'] as $key => $claim) {
            $temp_data['claims'][$key]['id'] = $claim['id'];
            $temp_data['claims'][$key]['account_transfer_id'] = $claim['account_turnover_id'];
            $temp_data['claims'][$key]['account_transfer_no'] = $claim['account_transfer_no'];
            $coefficient = ExchangeRateModel::conversion($claim['original_currency'],
                $data['info']['po_currency'],
                $data['info']['po_time']);
            $temp_data['claims'][$key]['coefficient'] = $coefficient;
            $temp_data['claims'][$key]['deductible_receivable_amount'] = $claim['claim_amount'] * $coefficient;
            $temp_data['claims'][$key]['account_bank'] = $claim['account_bank'];

            $temp_data['claims'][$key]['company_code'] = $claim['company_code'];
            $temp_data['claims'][$key]['opp_company_name'] = $claim['opp_company_name'];
            $temp_data['claims'][$key]['our_bank'] = $claim['our_bank'];
            $temp_data['claims'][$key]['opp_account_bank'] = $claim['opp_account_bank'];
            $temp_data['claims'][$key]['our_open_bank'] = $claim['our_open_bank'];
            $temp_data['claims'][$key]['opp_open_bank'] = $claim['opp_open_bank'];
            $temp_data['claims'][$key]['our_swift_code'] = $claim['our_swift_code'];
            $temp_data['claims'][$key]['other_swift_code'] = $claim['other_swift_code'];

            $temp_data['claims'][$key]['original_currency'] = $claim['original_currency'];
            $temp_data['claims'][$key]['original_amount'] = (double)$claim['original_amount'];
            $temp_data['claims'][$key]['claim_amount'] = $claim['claim_amount'];
            $temp_data['claims'][$key]['created_by'] = $claim['created_by'];
            $temp_data['claims'][$key]['created_at'] = $claim['created_at'];
            $temp_data['claims'][$key]['occur_at'] = DateModel::toYmd($claim['created_at']);
        }
        $temp_data['claims'] = DataModel::toArray($temp_data['claims']);

        foreach ($data['claim_deductions'] as $key => $deduction) {
            $temp_data['claim_deductions'][$key]['claim_id'] = $deduction['claim_id'];
            $temp_data['claim_deductions'][$key]['account_transfer_no'] = $deduction['account_transfer_no'];
            $temp_data['claim_deductions'][$key]['deduction_type'] = $deduction['deduction_type'];
            $temp_data['claim_deductions'][$key]['instructions'] = $deduction['instructions'];
            $temp_data['claim_deductions'][$key]['credentials_show_name'] = $deduction['credentials_show_name'];
            $temp_data['claim_deductions'][$key]['credentials_path'] = DataModel::jsonToArr($deduction['credentials_path']);
            $temp_data['claim_deductions'][$key]['original_currency'] = $deduction['original_currency'];
            /*$coefficient = ExchangeRateModel::conversion($deduction['original_currency'],
                $data['info']['po_currency'],
                $data['info']['po_time']);
            $temp_data['claim_deductions'][$key]['coefficient'] = $coefficient;*/
            $temp_data['claim_deductions'][$key]['deductible_receivable_amount'] = $deduction['deduction_amount'] * 1;
            $temp_data['claim_deductions'][$key]['deduction_amount'] = $deduction['deduction_amount'];
            $temp_data['claim_deductions'][$key]['created_by'] = $deduction['created_by'];
            $temp_data['claim_deductions'][$key]['created_at'] = $deduction['created_at'];
        }
        $temp_data['claim_deductions'] = DataModel::toArray($temp_data['claim_deductions']);
        if ($data['info']['submit_by']) {
            $temp_data['write_off_info']['after_amount_receivable'] = (double)($data['info']['current_receivable'] + $data['info']['rate_losses']);
            $temp_data['write_off_info']['before_amount_receivable'] = 0;
            $temp_data['write_off_info']['rate_losses'] = $data['info']['rate_losses'];
            $temp_data['write_off_info']['cancel_note'] = $data['info']['cancel_note'];
            $temp_data['write_off_info']['created_by'] = $data['info']['submit_by'];
            $temp_data['write_off_info']['created_at'] = $data['info']['submit_at'];
            $temp_data['write_off_info']['verification_by'] = $data['info']['verification_by'];
            $temp_data['write_off_info']['verification_at'] = $data['info']['verification_at'];
        }
        $temp_data['write_off_info'] = DataModel::toArray($temp_data['write_off_info']);
        return $temp_data;
    }

    /**
     * @param $res_db
     * @param bool $is_one
     * @return mixed
     */
    public function orderStatusToVal($res_db, $is_one = false)
    {
        $maps = [
            'warehousing_state' => [
                '待确认',
                '部分确认',
                '已确认',
            ],
            'ship_state' => [
                '待发货',
                '部分发货',
                '已发货',
            ],
            'pay_or_rec' => [
                '支出',
                '收入',
            ],
            'order_state' => [
                '待发货',
                '部分发货',
                '已发货',
            ],
            'warehouse_list_status' => [
                '未确认',
                null,
                '已确认',
            ],
            'claim_code' => [
                self::B2B_CLAIM_CODE => 'B2B收款认领'
            ],
        ];
        return $this->statusToValMap($res_db, $maps, $is_one);
    }

    /**
     * @param $map_keys
     * @param $value
     * @param $maps
     * @return mixed
     */
    private function statusKeyMapForeach($map_keys, $value, $maps)
    {
        foreach ($map_keys as $map_key) {
            if (false !== $value[$map_key] && $maps[$map_key][$value[$map_key]]) {
                $value[$map_key . '_val'] = $maps[$map_key][$value[$map_key]];
            }
        }
        return $value;
    }

    /**
     * @param $data
     */
    public function buildAndExecutionReceivableListExport($data)
    {
        Logs($data);
        $this->outputExcel('B2B应收列表',
            $this->claim_list,
            $data
        );
    }

    /**
     * @param $order_id
     * @param null $update_key
     * @return bool
     */
    public function updateOrderReceivableAccount($order_id, $update_key = null)
    {
        (new B2bReceivableService())->updateWholeOrder($order_id);
        return $this->B2bRepository->updateOrderReceivableAccount($order_id, $update_key);
    }

    /**
     * @param null $out_bill_id
     * @param integer $type
     * @return bool
     */
    public function b2bVirtualWarehouseRevoke($out_bill_id, $type = 0)
    {
        return $this->B2bRepository->b2bVirtualWarehouseRevoke($out_bill_id, $type);
    }


    /**
     * @param $data
     * @return mixed
     */
    private function appendHistoryReceiptRecord($data)
    {
        $data['claims'] = array_merge($data['claims'], $this->appendClaims($data['info']['order_id']));
        $data['claim_deductions'] = array_merge($data['claim_deductions'], $this->appendClaimsDeductions($data['info']['order_id']));
        return $data;
    }

    /**
     * @param $order_id
     * @return array
     */
    public function appendClaims($order_id)
    {
        $data = $this->B2bRepository->getAppendClaims($order_id);
        $temp_arr = [];
        foreach ($data as $datum) {
            $temp_data['id'] = null;
            $temp_data['account_transfer_id'] = null;
            $temp_data['account_transfer_no'] = null;
            $temp_data['coefficient'] = null;

            $temp_data['account_bank'] = null;
            $temp_data['company_code'] = null;
            $temp_data['opp_company_name'] = $datum['company_our'];
            $temp_data['our_bank'] = null;
            $temp_data['opp_account_bank'] = null;
            $temp_data['our_open_bank'] = null;
            $temp_data['opp_open_bank'] = null;
            $temp_data['our_swift_code'] = null;
            $temp_data['other_swift_code'] = null;

            $temp_data['original_currency'] = $datum['po_currency'];
            $temp_data['original_amount'] = null;
            $temp_data['claim_amount'] = $datum['actual_payment_amount'];
            $temp_data['deductible_receivable_amount'] = $datum['actual_payment_amount'];

            $temp_data['created_by'] = $datum['operator_id'];
            $temp_data['created_at'] = $datum['updated_at'];
            $temp_data['occur_at'] = DateModel::toYmd($datum['actual_receipt_date']);
            if (0 < $temp_data['deductible_receivable_amount']) {
                $temp_arr[] = $temp_data;
            }
        }
        return $temp_arr;
    }

    /**
     * @param $order_id
     * @param array $temp_data_arr
     * @return array
     */
    public function appendClaimsDeductions($order_id, $temp_data_arr = [])
    {
        $data = $this->B2bRepository->getAppendClaimsDeductions($order_id);
        foreach ($data as $datum) {
            $temp_data['claim_id'] = null;
            $temp_data['account_transfer_no'] = null;
            $temp_data['deduction_type'] = null;
            $temp_data['instructions'] = $datum['DEVIATION_REASON'];
            $temp_data['credentials_show_name'] = $datum['file_name'];
            $temp_data['credentials_path'] = '[{"savepath":"\/opt\/b5c-disk\/img\/","savename":"' . $datum['file_path'] . '","name":"' . $datum['file_name'] . '"}]
';
            $temp_data['original_currency'] = $datum['po_currency'];

            $temp_data['deduction_amount'] = $temp_data['deductible_receivable_amount'] = $datum['expect_receipt_amount'] - $datum['actual_payment_amount'];
            $temp_data['created_by'] = $datum['operator_id'];
            $temp_data['created_at'] = $datum['updated_at'];
            if (0 > $temp_data['deduction_amount']) {
                $temp_data_arr[] = $temp_data;
            }
        }
        return $temp_data_arr;
    }

    /**
     * @param array $data
     */
    private function appendHistoryAmount(array $data)
    {

    }

    /**
     * @return mixed
     */
    public function syncReceiptToReceivable()
    {
        return $this->B2bRepository->syncReceiptToReceivable();
    }

    /**
     * @param $B2b
     * @param $many_sheet_contents
     * @param $ids
     * @return mixed
     */
    public function joinOrderInfoAssembly($B2b, $many_sheet_contents, $ids)
    {
        $order_assembly = $B2b->assemblyTaxMapping(
            $B2b->assemblyShipExcludingTax($ids, true, false),
            'order_id'
        );
        $many_sheet_contents['order_info'] = $B2b->joinShipExcludingData($many_sheet_contents['order_info'],
            $order_assembly,
            'ORDER_ID');
        return $many_sheet_contents;
    }

    /**
     * @param $B2b
     * @param $many_sheet_contents
     * @param $ids
     * @return mixed
     */
    public function joinShipInfoAssembly($B2b, $many_sheet_contents, $ids)
    {
        $order_assembly = $B2b->assemblyTaxMapping(
            $B2b->assemblyShipExcludingTax($ids, false, false),
            'ship_list_id'
        );
        $many_sheet_contents['ship_info'] = $B2b->joinShipExcludingData($many_sheet_contents['ship_info'],
            $order_assembly,
            'ship_list_id');
        return $many_sheet_contents;
    }

    /**
     * @param $many_sheet_contents
     * @param $order_info_value_key
     * @return mixed
     */
    private function mapExcelOrderInfo($many_sheet_contents, $order_info_value_key)
    {
        foreach ($many_sheet_contents['ship_info'] as $value) {
            if (!isset($many_sheet_contents['order_info'][$order_info_value_key[$value['ORDER_ID']]]['all_sum_warehousing'])) {
                $many_sheet_contents['order_info'][$order_info_value_key[$value['ORDER_ID']]]['all_sum_warehousing'] = 0;
            }
            if (!isset($many_sheet_contents['order_info'][$order_info_value_key[$value['ORDER_ID']]]['all_sum_warehousing_no_tax'])) {
                $many_sheet_contents['order_info'][$order_info_value_key[$value['ORDER_ID']]]['all_sum_warehousing_no_tax'] = 0;
            }
            $many_sheet_contents['order_info'][$order_info_value_key[$value['ORDER_ID']]]['all_sum_warehousing'] += (float)$value['sum_warehousing'];
            $many_sheet_contents['order_info'][$order_info_value_key[$value['ORDER_ID']]]['all_sum_warehousing_no_tax'] = (float)$value['sum_warehousing_no_tax'];
        }
        foreach ($many_sheet_contents['receipt_info'] as $value) {
            if (!isset($many_sheet_contents['order_info'][$order_info_value_key[$value['ORDER_ID']]]['all_claim_amount_to_po_currency'])) {
                $many_sheet_contents['order_info'][$order_info_value_key[$value['ORDER_ID']]]['all_claim_amount_to_po_currency'] = 0;
            }
            $many_sheet_contents['order_info'][$order_info_value_key[$value['ORDER_ID']]]['all_claim_amount_to_po_currency'] += (float)$value['claim_amount_to_po_currency'];
        }
        foreach ($many_sheet_contents['deduction_info'] as $value) {
            if (!isset($many_sheet_contents['order_info'][$order_info_value_key[$value['ORDER_ID']]]['all_deduction_amount'])) {
                $many_sheet_contents['order_info'][$order_info_value_key[$value['ORDER_ID']]]['all_deduction_amount'] = 0;
            }
            $many_sheet_contents['order_info'][$order_info_value_key[$value['ORDER_ID']]]['all_deduction_amount'] += (float)$value['deduction_amount'];
        }
        return $many_sheet_contents;
    }

    /**
     * @param $order_id
     * @param $batch_ids
     * @return array
     */
    public function getPurchaseOrder($order_id, $batch_ids)
    {
        $spot_batch_ids = [];
        if (!$batch_ids) {
            $spot_batch_ids = $this->B2bRepository->getGoodsBatch($order_id);
            $occupy_batch_ids = $this->B2bRepository->getOccupyBatch($order_id);
            $batch_ids = array_unique(
                array_merge(array_column(DataModel::toArray($spot_batch_ids), 'batch_id'),
                    array_column(DataModel::toArray($occupy_batch_ids), 'batch_id')));
        }
        $purchase_orders = $this->B2bRepository->getPurchase($batch_ids);
        $purchase_order_values = $this->appendGoodsPurchase($purchase_orders, $spot_batch_ids);
        if (!$purchase_order_values) {
            return [];
        }
        $procurement_number = WhereModel::arrayToInString($purchase_order_values);
        $db_res = $this->B2bRepository->getPurchaseData($procurement_number);
        $db_res = CodeModel::autoCodeTwoVal($db_res, ['order_status', 'purchasing_team']);
        return $this->statusToValMap($db_res, $this->purchase_status, false);
    }

    /**
     * @param $res_db
     * @param $is_one
     * @param $maps
     * @param $value
     * @return array
     */
    private function statusToValMap($res_db, $maps, $is_one)
    {
        $map_keys = array_keys($maps);
        if ($is_one) {
            $res_db = $this->statusKeyMapForeach($map_keys, $res_db, $maps);
        } else {
            foreach ($res_db as &$value) {
                $value = $this->statusKeyMapForeach($map_keys, $value, $maps);
            }
        }
        return $res_db;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function expansionOrderDetail($data)
    {
        $order_id = $data['info'][0]['ORDER_ID'];
        $db_receipts = $this->getReceivableDetail(['order_id' => $order_id]);
        $data = $this->receiptInformationMap($data, $db_receipts);
        $data['status'] = $this->B2bRepository->getB2bStatus($order_id);
        $data['status'] = CodeModel::autoCodeOneVal($data['status'], ['receivable_status','return_status_cd']);
        $data['status'] = $this->orderStatusToVal($data['status'], true);
        $data['estimated_profit'] = $this->B2bRepository->getEstimatedProfit($order_id);
        $data['estimated_profit'] = $this->mapEstimatedProfit($data['estimated_profit'], $data['info'][0]['po_time']);
        $data['receivable_information'] = $this->B2bRepository->getReceivableInformation($order_id);
        $data['receivable_information'] = DataModel::toNumberFormat($this->mapReceivableInformation($data['receivable_information']));
        return $data;
    }

    // 获取预估利润信息
    public function getEstimatedProfitInfo($value)
    {
        if (!$value) {
            return false;
        }
        foreach ($value as $k => $v) {
            $estimated_profit = '';
            $estimated_profit = $this->B2bRepository->getEstimatedProfit($v['ORDER_ID']);
            $estimated_profit = $this->mapEstimatedProfit($estimated_profit, $v['po_time']); 
            $value[$k]['estimated_income'] = $estimated_profit['estimated_income'];
            $value[$k]['estimated_cost'] = $estimated_profit['estimated_cost'];
            $value[$k]['estimated_gross_profit'] = $estimated_profit['estimated_gross_profit'];
            $value[$k]['estimated_gross_profit_margin'] = $estimated_profit['estimated_gross_profit_margin'];         
        }

        return $value;
    }

    /**
     * @param $value
     * @param $po_time
     * @return mixed
     */
    private function mapEstimatedProfit($value, $po_time)
    {
        $cny_to_usd = ExchangeRateModel::cnyToUsd($po_time);
        $value['estimated_income'] = (float)$value['estimated_income'] * $cny_to_usd;
        $value['estimated_cost'] = (float)$value['estimated_cost'] * $cny_to_usd;
        $value['estimated_gross_profit'] = $value['estimated_income'] - $value['estimated_cost'];
        if (0 != $value['estimated_income']) {
            $value['estimated_gross_profit_margin'] = (round($value['estimated_gross_profit'] / $value['estimated_income'] * 100, 2)) . '%';
        } else {
            $value['estimated_gross_profit_margin'] = '0%';
        }
        foreach ($value as $key => $item) {
            if ('estimated_gross_profit_margin' != $key) {
                $value[$key] = number_format($item, 2);
            }
        }
        return $value;
    }

    /**
     * @param $value
     * @return mixed
     */
    private function mapReceivableInformation($value)
    {
        $temp_value['total_receivable'] = $value['order_account'];
        $temp_value['actual_harvest'] = $value['actual_collection'];
        $temp_value['customer_charge'] = $value['sum_deduction_amount'];
        $temp_value['exchange_rate_gains_and_losses'] = $value['rate_losses'];

        $temp_value['remaining_receivable'] = $temp_value['total_receivable'] - $temp_value['actual_harvest'] - $temp_value['customer_charge'] - $temp_value['exchange_rate_gains_and_losses'];

        return $temp_value;
    }

    /**
     * @param $data
     * @param $db_receipts
     * @return mixed
     */
    private function receiptInformationMap($data, $db_receipts)
    {
        $data['receipt_information'] = array_map(function ($value) {
            $temp_value["flow_id"] = $value['account_transfer_no'];
            $temp_value["payer_information"] = [
                'opp_company_name' => $value['opp_company_name'],
                'opp_account_bank' => $value['opp_account_bank'],
                'opp_open_bank' => $value['opp_open_bank'],
            ];
            $temp_value["currency"] = $value['original_currency'];
            $temp_value["amount_this_claim"] = $value['claim_amount'];
            $temp_value["claimant"] = $value['created_by'];
            $temp_value["claim_time"] = $value['created_at'];
            $temp_value["date_occurrence"] = $value['occur_at'];
            return $temp_value;
        }, $db_receipts['claims']);
        $data['deduction_information'] = array_map(function ($value) {
            $temp_value['flow_id'] = $value['account_transfer_no'];
            $temp_value['deduction_type'] = $value['deduction_type'];
            $temp_value['deduction_instructions'] = $value['instructions'];
            $temp_value['deduction_certificate'] = $value['credentials_show_name'];
            $temp_value['credentials_path'] = $value['credentials_path'];
            $temp_value['currency'] = $value['original_currency'];
            $temp_value['deduction_amount'] = $value['deduction_amount'];
            $temp_value['entering_person'] = $value['created_by'];
            $temp_value['entry_time'] = $value['created_at'];
            return $temp_value;
        }, $db_receipts['claim_deductions']);
        $data['receipt_information'] = CodeModel::autoCodeTwoVal($data['receipt_information'], ['currency']);
        $data['deduction_information'] = CodeModel::autoCodeTwoVal($data['deduction_information'], ['deduction_type', 'currency']);
        return $data;
    }

    /**
     * @param $order_id
     * @param $drawback_estimate
     * @param $date
     * @param $backend_currency
     * @param $info
     * @return float|int|null
     */
    public function getB2bDetailWholeVat($order_id, $drawback_estimate, $date, $backend_currency, $info)
    {
        list($output_tax_cny, $input_tax_cny) = $this->B2bRepository->getInvoicingTax($order_id);
        $is_same_country = $this->checkSameCountry($info['our_company'], $info['CLIENT_NAME']);
        $whole_vat = null;
        $cny_to_usd = ExchangeRateModel::cnyToUsd($date);
        $to_usd = ExchangeRateModel::conversion($backend_currency, 'N000590100', $date);
        switch ($is_same_country) {
            case 0:
                $whole_vat = $input_tax_cny * $cny_to_usd - $drawback_estimate * $to_usd;
                break;
            case 1:
                $whole_vat = $output_tax_cny * $cny_to_usd - $input_tax_cny * $cny_to_usd;
                break;
        }
        Logs(['order_id' => $order_id, 'output_tax_cny' => $output_tax_cny * $cny_to_usd, 'input_tax_cny' => $input_tax_cny * $cny_to_usd,],
            __CLASS__,
            __FUNCTION__);
        return number_format($whole_vat, 2);
    }

    /**
     * @param $our_company
     * @param $client_name
     * @return int
     */
    public function checkSameCountry($our_company, $client_name)
    {
        $our_company_country = CodeModel::getInfoFromCodeVal($our_company)['ETC3'];
        $user_area = TbMsUserArea::whereTwoChar($our_company_country)->first();
        $sp_suppliser = TbCrmSpSupplier::where('SP_NAME', $client_name)
            ->where('DATA_MARKING', 1)
            ->first();
        $Model = new Model();
        $site_name = $Model->table('tb_crm_site')
            ->where(['ID' => $sp_suppliser->SP_ADDR5])
            ->getField('NAME');
        if ($user_area->zh_name == $site_name) {
            return 1;
        }
        return 0;
    }

    /**
     * @param $purchase_orders
     * @param $spot_batch_ids
     * @return array
     */
    private function appendGoodsPurchase($purchase_orders, $spot_batch_ids)
    {
        $purchase_order_values = array_column($purchase_orders, 'procurement_number');
        $good_procurement_numbers = array_column($spot_batch_ids, 'procurement_number');
        if ($good_procurement_numbers) {
            $purchase_order_values = array_unique(array_merge(
                DataModel::toArray($purchase_order_values),
                DataModel::toArray($good_procurement_numbers)));
        }
        return $purchase_order_values;
    }

    public function getSendNetWarehouseCds()
    {
        return array_column($this->B2bRepository->getSendNetWarehouseCds(), 'cd');
    }


    public function orderReturnGoodsDetail($params) {
        $order  = M('info','tb_b2b_')
            ->alias('t')
            ->field('PO_ID,THR_PO_ID,CLIENT_NAME,a.CD_VAL SALES_TEAM,PO_USER')
            ->join('tb_ms_cmn_cd a on a.CD=t.SALES_TEAM')
            ->where(['ORDER_ID'=>$params['order_id']])
            ->find();
        $goods  = M('goods','tb_b2b_')
            ->field('t.ID,a.sku_id,a.upc_id,a.upc_more,sum(t.is_inwarehouse_num) is_inwarehouse_num,sum(t.return_num) return_num')
            ->alias('t')
            ->join(PMS_DATABASE.'.product_sku a on a.sku_id=t.SKU_ID')
            ->where(['ORDER_ID'=>$params['order_id']])
            ->group('t.SKU_ID')
            ->select();
        $goods = SkuModel::getInfo($goods,'sku_id',['spu_name','image_url','attributes']);
        foreach ($goods as $k => &$v) {
            if($v['upc_more']) {
                 $upc_more_arr = explode(',', $v['upc_more']);
                 array_unshift($upc_more_arr, $v['upc_id']);
                $v['upc_id'] = implode(",\r\n", $upc_more_arr); # 返回br标签 前端显示换行

            }   
        }

        return ['order'=>$order, 'goods'=>$goods];
    }

    public function returnGoodsList($params) {
        import('ORG.Util.Page');// 导入分页类
        $_GET['p']  = $params['p'];
        $model      = new B2bReturnModel();
        $where      = $this->returnGoodsWhere($params);
        $count_sql  = $model->alias('t')
            ->field('t.id')
            ->join('tb_b2b_info a on a.ORDER_ID=t.order_id')
            ->join('tb_b2b_return_goods e on e.return_id=t.id')
            ->join(PMS_DATABASE .'.product_sku f on f.sku_id=e.sku_id')
            ->where($where)
            ->group('t.id')
            ->buildSql();

        $count = M()->table($count_sql.' a')->count();
        $page   = new Page($count,$params['rows'] ? : 20);
        $list = $model
            ->alias('t')
            ->field('t.id,t.return_no,a.PO_ID,a.THR_PO_ID,t.logistics_number,b.CD_VAL warehouse_status,a.CLIENT_NAME,c.CD_VAL sale_team,d.CD_VAL warehouse,t.expected_arrival_date,t.created_by,t.created_at')
            ->join('tb_b2b_info a on a.ORDER_ID=t.order_id')
            ->join('tb_ms_cmn_cd b on b.CD=t.status_cd')
            ->join('tb_ms_cmn_cd c on c.CD=a.SALES_TEAM')
            ->join('tb_ms_cmn_cd d on d.CD=t.warehouse_cd')
            ->join('tb_b2b_return_goods e on e.return_id=t.id')
            ->join(PMS_DATABASE .'.product_sku f on f.sku_id=e.sku_id')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->group('t.id')
            ->order('id desc')
            ->select();
        return ['list'=>$list,'page'=>['total_rows'=>$count]];
    }

    public function returnGoodsWhere($params) {
        if($params['return_no']) $where['return_no'] = $params['return_no'];
        if($params['warehouse_cd']) $where['warehouse_cd'] = $params['warehouse_cd'];
        if($params['logistics_number']) $where['logistics_number'] = $params['logistics_number'];
        if($params['sale_team']) $where['SALES_TEAM'] = $params['sale_team'];
        if($params['created_by']) $where['t.created_by'] = $params['created_by'];
        if($params['status_cd']) $where['t.status_cd'] = $params['status_cd'];
        if($params['created_at_start'] && $params['created_at_end']) {
            $where['t.created_at'] = ['between',[$params['created_at_start'],$params['created_at_end'] . ' 23:59:59']];
        }elseif ($params['created_at_start']) {
            $where['t.created_at'] = ['gt',$params['created_at_start']];
        }elseif ($params['created_at_end']) {
            $where['t.created_at'] = ['elg',$params['created_at_end'] . ' 23:59:59'];
        }
        if($params['sku_or_barcode']) {
            $complex['f.sku_id']    = $params['sku_or_barcode'];
            $complex['f.upc_id']    = $params['sku_or_barcode'];
            $complex['_string'] = "FIND_IN_SET('{$params['sku_or_barcode']}',f.upc_more)";
            $complex['_logic']      = 'or';
            $where['_complex']      = $complex;
        }
        /*$po_key = $params['po_type'] ? 'THR_PO_ID' : 'PO_ID';
        if($params['PO_ID']) $where[$po_key] = $params['PO_ID'];*/
        if ($params['PO_ID']) {
            //$where['_string'] = "THR_PO_ID = '{$params['PO_ID']}' or PO_ID = '{$params['PO_ID']}'";
            $po_ids = strReplaceComma($params['PO_ID']);
            $where['_string'] = "THR_PO_ID in ('".join("','", $po_ids)."') or PO_ID in ('".join("','", $po_ids)."')";
        }
        if($params['client_name']) $where['CLIENT_NAME'] = ['like','%'.$params['client_name'].'%'];
        return $where;
    }


    public function returnGoodsDetail($id) {
        $detail = (new B2bReturnModel())
            ->alias('t')
            ->field('return_no,f.cd_val status,t.warehouse_cd,d.CD_VAL warehouse,t.logistics_number,expected_arrival_date,e.CD_VAL expected_logistics_cost_currency,expected_logistics_cost,PO_ID,THR_PO_ID,CLIENT_NAME,a.our_company,b.CD_VAL SALES_TEAM,PO_USER,a.remarks,t.warehoused_by,t.warehoused_at,t.created_by,t.created_at')
            ->join('tb_b2b_info a on a.ORDER_ID=t.order_id')
            ->join('tb_ms_cmn_cd b on b.CD=a.SALES_TEAM')
            ->join('tb_b2b_ship_list c on c.ID=t.ship_id')
            ->join('tb_ms_cmn_cd d on d.CD=t.warehouse_cd')
            ->join('tb_ms_cmn_cd e on e.CD=t.expected_logistics_cost_currency')
            ->join('tb_ms_cmn_cd f on f.CD=t.status_cd')
            ->where(['t.id'=>$id])
            ->find();
        $goods  = M('return_goods','tb_b2b_')
            ->field('t.id,a.sku_id,a.upc_id,a.upc_more,t.return_num,t.warehouse_num_quality,t.warehouse_num_broken')
            ->alias('t')
            ->join(PMS_DATABASE.'.product_sku a on a.sku_id=t.sku_id')
            ->where(['return_id'=>$id])
            ->group('t.SKU_ID')
            ->select();
        $goods = SkuModel::getInfo($goods,'sku_id',['spu_name','image_url','attributes']);

        foreach ($goods as $k => &$v) {
            if($v['upc_more']) {
                 $upc_more_arr = explode(',', $v['upc_more']);
                 array_unshift($upc_more_arr, $v['upc_id']);
                $v['upc_id'] = implode(",\r\n", $upc_more_arr); # 返回br标签 前端显示换行

            }   
        }
        $LocationService = new LocationService();
        $LocationService->obtain($detail['warehouse_cd'], array_column($goods, 'sku_id'), $goods);
        return ['detail'=>$detail, 'goods'=>$goods];
    }

    /**
     * @param $params
     * @return array|bool
     * @throws Exception
     * 发起退货
     */
    public function returnGoods($params) {
        $return_m       = new B2bReturnModel();
        $return_goods_m = new B2bReturnGoodsModel();
        $goods_m        = M('goods','tb_b2b_');
        $return_m->startTrans();
        if(!$this->returnGoodsValidate($params)) {
            B2bModel::addLog($params['order_id'], 300, '发起退货');
            return false;
        }
        $return_no = $this->createReturnNo();
        $save_return = [
            'return_no'                         => $return_no,
            'order_id'                          => $params['order_id'],
            'logistics_number'                  => $params['logistics_number'],
            'warehouse_cd'                      => $params['warehouse_cd'],
            'expected_arrival_date'             => $params['expected_arrival_date'],
            'expected_logistics_cost_currency'  => $params['expected_logistics_cost_currency'],
            'expected_logistics_cost'           => $params['expected_logistics_cost']
        ];
        if(!($return_m->create($save_return) && $return_id = $return_m->add())) {
            $return_m->rollback();
            B2bModel::addLog($params['order_id'], 300, '发起退货');
            Throw new Exception('退货信息保存失败');
        }
        //更新商品退货数量
        try {
            $this->updateGoodsReturnNum($params);
        }catch (Exception $exception) {
            $return_m->rollback();
            B2bModel::addLog($params['order_id'], 300, '发起退货');
            Throw new Exception($exception->getMessage());
        }
        //保存退货商品
        foreach ($params['goods'] as $v) {
            $v['return_id'] = $return_id;
            if(!($return_goods_m->create($v) && $return_goods_m->add())) {
                $return_m->rollback();
                B2bModel::addLog($params['order_id'], 300, '发起退货');
                Throw new Exception('退货商品信息保存失败');
            }
        }
        try {
            $this->updateOrderReturnGoodsStatus($params['order_id']);
        }catch (Exception $exception) {
            $return_m->rollback();
            B2bModel::addLog($params['order_id'], 300, '发起退货');
            Throw new Exception($exception->getMessage());
        }
        B2bModel::addLog($params['order_id'], 200, '发起退货', '退货单号：' . $return_no);
        $return_m->commit();
        return ['id'=>$return_id];
    }

    /**
     * @param $params
     * @return bool
     * @throws Exception
     * 更新商品退货总数
     */
    private function updateGoodsReturnNum($params) {
        $goods_m    = M('goods','tb_b2b_');
        $goods      = $goods_m->where(['ORDER_ID'=>$params['order_id']])->select();
        $goods_arr  = [];
        foreach ($goods  as $v) {
            $goods_arr[$v['SKU_ID']][] = $v;
        }
        foreach ($params['goods'] as $v) {
            foreach ($goods_arr[$v['sku_id']] as $val) {
                $can_return_num = $val['is_inwarehouse_num'] - $val['return_num'];
                if($can_return_num && $can_return_num >= $v['return_num']) {
                    if(!$goods_m->where(['ID'=>$val['ID']])->setInc('return_num', $v['return_num']))
                        Throw new Exception('商品退货总数更新失败');
                    $v['return_num'] = 0;
                }elseif ($can_return_num && $can_return_num <$v['return_num']) {
                    if(!$goods_m->where(['ID'=>$val['ID']])->setInc('return_num', $can_return_num))
                        Throw new Exception('商品退货总数更新失败');
                    $v['return_num'] -= $can_return_num;
                }
            }
            if($v['return_num'] != 0)
                Throw new Exception('商品退货数异常');
        }
        return true;
    }

    public function createReturnNO() {
        $last_return_no = D('BTB/B2bReturn')
            ->lock(true)
            ->where(['return_no'=>['like','TH'.date('Ymd').'%']])
            ->order('id desc')
            ->getField('return_no');
        if($last_return_no) {
            $return_no = 'TH' . (substr($last_return_no, 2)+1);
        }else {
            $return_no = 'TH'.date('Ymd') . '0001';
        }
        return $return_no;
    }

    /**
     * @param $params
     * @return bool
     * @throws Exception
     * 发起退货校验
     */
    private function returnGoodsValidate($params) {
        if(!$params['order_id'] || !$params['goods']) {
            Throw new Exception('参数异常');
        }

        if (empty($params['expected_arrival_date'])){
            Throw new Exception('参数异常-预计到仓日期');
        }
        if (empty($params['warehouse_cd'])){
            Throw new Exception('参数异常-退回仓库');
        }
        if (empty($params['expected_logistics_cost'])){
            Throw new Exception('参数异常-预估物流费用');
        }
        if (empty($params['expected_logistics_cost_currency'])){
            Throw new Exception('参数异常-预估物流费用-币种');
        }

        $order  = M('order','tb_b2b_')->field('receipt_state')->lock(true)->where(['ID'=>$params['order_id']])->find();
        if(!$order)
            Throw new Exception('订单不存在');
        $receivable_status = M('receivable','tb_b2b_')
            ->where(['order_id'=>$params['order_id']])
            ->getField('receivable_status');
        if($receivable_status != 'N002540100')
            Throw new Exception('应收状态为待提交时才可以发起退货');
        if($order['receipt_state'] != 0)
            Throw new Exception('订单应收状态必须为未提交');
        $goods_num_arr  = M('goods','tb_b2b_')
            ->field('SKU_ID,sum(return_num),sum(IFNULL(is_inwarehouse_num,0)) is_inwarehouse_num')
            ->where(['ORDER_ID'=>$params['order_id']])
            ->group('SKU_ID')
            ->select();
        $goods_num_arr = array_column_key($goods_num_arr, 'SKU_ID');
        foreach ($params['goods'] as $v) {
            if($v['return_num'] <= 0)
                Throw new Exception('退货数量异常');
            if($v['return_num'] > $goods_num_arr[$v['sku_id']]['is_inwarehouse_num'] - $goods_num_arr[$v['sku_id']]['return_num'])
                Throw new Exception('退货数量超过剩余可退数量');
        }
        return true;
    }


    /**
     * @param $params
     * @return bool
     * @throws Exception
     * 退货入库
     */
    public function returnGoodsWarehouse($params) {
        $return_m   = D('BTB/B2bReturn');
        $goods_m    = D('BTB/B2bReturnGoods');
        $return_m->startTrans();
        $this->returnGoodsWarehouseValidate($params);
        $save_return = [
            'id'            => $params['id'],
            'status_cd'     => B2bReturnModel::$warehouse_status['complete'],
            'warehoused_by' => session('m_loginname'),
            'warehoused_at' => date('Y-m-d H:i:s')
        ];
        $return_info = $return_m->field('order_id,return_no')->where(['id'=>$params['id']])->find();
        if(!($return_m->create($save_return) && $return_m->save())) {
            $return_m->rollback();
            B2bModel::addLog($return_info['order_id'], 300, '退货入库');
            Throw new Exception('退货状态修改失败');
        }
        foreach ($params['goods'] as $v) {
            if(!($goods_m->create($v) && $goods_m->save() !== false)) {
                $return_m->rollback();
                B2bModel::addLog($return_info['order_id'], 300, '退货入库');
                Throw new Exception('商品入库数量保存失败');
            }
        }
        try {
            $this->returnGoodsWarehouseApi($params);
            $this->updateOrderReturnGoodsStatus($return_info['order_id']);
            $this->updateOrderReceivableAccount($return_info['order_id']);
        }catch (Exception $exception) {
            $return_m->rollback();
            B2bModel::addLog($return_info['order_id'], 300, '退货入库');
            Throw new Exception($exception->getMessage());
        }
        $return_m->commit();
        B2bModel::addLog($return_info['order_id'], 200, '退货入库', '退货单号：' . $return_info['return_no']);
        return true;
    }

    /**
     * @param $params
     * @return bool
     * @throws Exception
     * 退货入库
     */
    private function returnGoodsWarehouseApi($params) {
        $bill_info = D('BTB/B2bReturn')
            ->alias('t')
            ->field('a.PO_ID,t.return_no,t.warehouse_cd,a.SALES_TEAM')
            ->join('left join tb_b2b_info a on a.ORDER_ID=t.order_id')
            ->where(['t.id'=>$params['id']])
            ->find();
        $goods = D('BTB/B2bReturnGoods')->where(['return_id'=>$params['id']])->getField('id,SKU_ID',true);
        $bill_data = [
            "bill" => [
                "billType"=> "N000941000",
                "relationType"=> "N002350702",
                "virType"=> "N002440100",
                "orderId"=> $bill_info['PO_ID'],
                "saleNo"=> $bill_info['return_no'],
                "warehouseId"=> $bill_info['warehouse_cd'],
                "saleTeam"=> $bill_info['SALES_TEAM'],
                "operatorId"=> session('userId')
            ]
        ];
        foreach ($params['goods'] as $v) {
            $bill_data['guds'][] = [
                "skuId"     => $goods[$v['id']],
                "num"       => $v['warehouse_num_quality'],
                "brokenNum" => $v['warehouse_num_broken'],
            ];
        }
        $res_j  = ApiModel::warehouse($bill_data);
        $res    = json_decode($res_j,true);
        ELog::add(['msg'=>'调用入库接口'.($res['code'] == 2000 ? '成功' : '失败'), 'request'=>$bill_data,'response'=>$res_j],ELog::INFO);
        if($res['code'] != 2000)
            Throw new Exception('调用入库接口失败'.$res['msg']);
        return true;
    }

    /**
     * @param $params
     * @return bool
     * @throws Exception
     * 退货入库参数校验
     */
    private function returnGoodsWarehouseValidate($params) {
        if(!$params['id'] || !$params['goods']) {
            Throw new Exception('参数异常');
        }
        $return_m = D('BTB/B2bReturn');
        $warehouse_status = $return_m->lock(true)->where(['id'=>$params['id']])->getField('status_cd');
        if($warehouse_status != $return_m::$warehouse_status['to_warehouse'])
            Throw new Exception('退货状态异常');
        $goods = D('BTB/B2bReturnGoods')->where(['return_id'=>$params['id']])->getField('id,return_num',true);
        foreach ($params['goods'] as $v) {
            if(!$v['warehouse_num_quality'] && !$v['warehouse_num_broken'])
                Throw new Exception('商品数量异常');
            if($v['warehouse_num_quality'] + $v['warehouse_num_broken'] > $goods[$v['id']])
                Throw new Exception('入库总数不能超过退货数');
        }
        return true;
    }

    /**
     * @param $params
     * @return bool
     * @throws Exception
     */
    public function warehouseReturn($params) {
        if(!$params['id']) {
            Throw new Exception('参数异常');
        }
        M()->startTrans();
        $warehouse_info     = M('warehouse_list','tb_b2b_')->lock(true)->where(['ID'=>$params['id']])->find();
        $warehouse_goods    = M('warehousing_goods','tb_b2b_')->where(['warehousing_id'=>$params['id']])->select();
        if($warehouse_info['status'] != 0) {
            M()->rollback();
            Throw new Exception('入库单状态异常');
        }
        $warehouse_num = 0;
        $return_param = [];
        foreach ($warehouse_goods as $v) {
            $warehouse_num += $v['TOBE_WAREHOUSING_NUM'];
            $return_param['goods'][] = [
                'goods_id'      => $v['goods_id'],
                'return_num'    => $v['TOBE_WAREHOUSING_NUM'],
                'sku_id'        => $v['warehouse_sku'],
            ];
            $tally_param['goods'][] = [
                'warehouse_num' => $v['TOBE_WAREHOUSING_NUM'],
                'sku_id'        => $v['warehouse_sku'],
            ];
        }
        $tally_param['order_id'] = $warehouse_info['ORDER_ID'];
        //B2B订单商品理货数量保存
        try {
            $this->updateGoodsWarehouseNum($tally_param);
        }catch (Exception $exception) {
            M()->rollback();
            Throw new Exception('更新B2B订单理货数量失败');
        }

        //理货单数据保存
        $save_warehouse['WAREHOUSEING_NUM'] = ['exp','WAREHOUSEING_NUM+'.$warehouse_num];
        $save_warehouse['SUBMIT_TIME']      = date('Y-m-d H:i:s');
        $save_warehouse['submit_user']      = session('m_loginname');
        $save_warehouse['status']           = 2;
        $save_warehouse['tally_type_cd']    = 'N002780002';
        $res_warehouse = M('warehouse_list','tb_b2b_')->where(['ID'=>$params['id']])->save($save_warehouse);
        if(!$res_warehouse) {
            M()->rollback();
            Throw new Exception('理货单数据保存失败');
        }

        //理货商品数据保存
        $save_warehouse_goods['DELIVERED_NUM']  = ['exp','TOBE_WAREHOUSING_NUM'];
        $save_warehouse_goods['SUBMIT_TIME']    = $save_warehouse['SUBMIT_TIME'];
        $save_warehouse_goods['SUBMIT_USER_ID'] = session('m_loginname');
        $res_warehouse_goods = M('warehousing_goods','tb_b2b_')->where(['warehousing_id'=>$params['id']])->save($save_warehouse_goods);
        if(!$res_warehouse_goods) {
            M()->rollback();
            Throw new Exception('理货单数商品据保存失败');
        }

        //退货数据保存
        $return_param['order_id']                           = $warehouse_info['ORDER_ID'];
        $return_param['logistics_number']                   = $params['logistics_number'];
        $return_param['warehouse_cd']                       = $params['warehouse_cd'];
        $return_param['expected_arrival_date']              = $params['expected_arrival_date'];
        $return_param['expected_logistics_cost_currency']   = $params['expected_logistics_cost_currency'];
        $return_param['expected_logistics_cost']            = $params['expected_logistics_cost'];
        try {
            $this->returnGoods($return_param);
        }catch (Exception $exception) {
            M()->rollback();
            Throw new Exception($exception->getMessage());
        }

        A('B2b')->upd_warehosing_status($warehouse_info['ORDER_ID']);
        B2bModel::addLog($warehouse_info, 1, '理货', "发货子单号:{$warehouse_info['SHIP_LIST_ID']}");
        M()->commit();
        return true;
    }

    /**
     * @param $params
     * @return bool
     * @throws Exception
     * 更新订单理货数量
     */
    public function updateGoodsWarehouseNum($params) {
        $goods_m    = M('goods','tb_b2b_');
        $goods      = $goods_m->field('ID,SKU_ID,SHIPPED_NUM,is_inwarehouse_num')->where(['ORDER_ID'=>$params['order_id']])->select();
        $goods_arr  = [];
        foreach ($goods  as $v) {
            $goods_arr[$v['SKU_ID']][] = $v;
        }
        foreach ($params['goods'] as $v) {
            foreach ($goods_arr[$v['sku_id']] as $val) {
                $can_warehouse_num = $val['SHIPPED_NUM'] - $val['is_inwarehouse_num'];
                if($can_warehouse_num && $can_warehouse_num >= $v['warehouse_num']) {
                    if(!$goods_m->where(['ID'=>$val['ID']])->setInc('is_inwarehouse_num', $v['warehouse_num']))
                        Throw new Exception('商品理货总数更新失败');
                    $v['warehouse_num'] = 0;
                }elseif ($can_warehouse_num && $can_warehouse_num <$v['warehouse_num']) {
                    if(!$goods_m->where(['ID'=>$val['ID']])->setInc('is_inwarehouse_num', $can_warehouse_num))
                        Throw new Exception('商品理货总数更新失败');
                    $v['warehouse_num'] -= $can_warehouse_num;
                }
            }
            if($v['warehouse_num'] != 0)
                Throw new Exception('商品理货数异常');
        }
        return true;
    }

    public function getReturnMoneyAll($order_id) {
//        $money_all = M('goods','tb_b2b_')
//            ->alias('t')
//            ->field('sum(ifnull(a.warehouse_num_quality,0)+ifnull(a.warehouse_num_broken,0))*t.price_goods money_all')
//            ->join('tb_b2b_return_goods a on a.goods_id=t.ID')
//            ->where(['t.ORDER_ID'=>$order_id])
//            ->find();
        $sql = "select sum(money) as money_all from (SELECT a.sku_id,sum(ifnull(a.warehouse_num_quality,0)+ifnull(a.warehouse_num_broken,0))*t.price_goods money
          from tb_b2b_goods t LEFT JOIN tb_b2b_return_goods a on t.ID=a.goods_id
          where t.ORDER_ID={$order_id} and a.id is not null group by a.sku_id) as b";
        $money_all = M()->query($sql); 
        return $money_all[0]['money_all']; //原生的 mysqli 里面直接会返回数组的，所以取第0个元素
    }

    /**
     * @param $order_id
     * @return bool
     * @throws Exception
     * 更新订单退货状态
     */
    private function updateOrderReturnGoodsStatus($order_id) {
        if(!$order_id)
            Throw new Exception('参数异常');
        $return_goods = D('BTB/B2bReturn')->where(['order_id'=>$order_id])->select();
        $has_warehouse = false;
        $has_unwarehouse = false;
        foreach ($return_goods as $v) {
            if($v['status_cd'] == B2bReturnModel::$warehouse_status['to_warehouse']) $has_unwarehouse = true;
            if($v['status_cd'] == B2bReturnModel::$warehouse_status['complete']) $has_warehouse = true;
        }
        if(!$has_warehouse && !$has_unwarehouse) {
            $return_status = 'N002770001';
        }elseif(!$has_warehouse && $has_unwarehouse) {
            $return_status = 'N002770002';
        }elseif($has_warehouse && $has_unwarehouse) {
            $return_status = 'N002770003';
        }else {
            $return_status = 'N002770004';
        }
        $res = M('order', 'tb_b2b_')->where(['ID'=>$order_id])->save(['return_status_cd'=>$return_status]);
        if($res === false) {
            Throw new Exception('订单退货状态保存失败');
        }
        return true;
    }
    
    public function withdrawEndShipment($order_id)
    {
        return $this->B2bRepository->withdrawEndShipment($order_id);

    }

    /**
     * 理货撤回
     * @param $warehouse_list_id 入库单据id
     * @param $order_id 订单id
     * @throws Exception
     */
    public function warehouseRevokeSubmit($warehouse_list_id, $order_id)
    {
        $warehouse_info = $this->model->table('tb_b2b_warehouse_list wl')
            ->field('wl.ID as id, wl.status, wl.tally_type_cd, wl.SHIP_LIST_ID, wl.SHIPMENTS_NUMBER,
             wl.WAREHOUSEING_NUM, IFNULL(wg.DELIVERED_NUM, 0) as delivered_num, wg.warehouse_sku, wg.incomplete_number')
            ->join('left join tb_b2b_warehousing_goods wg on wl.ID = wg.warehousing_id')
            ->where(['wl.order_id' => $order_id, 'wl.ID' => $warehouse_list_id])
            ->select();
        $this->checkWarehouseRevoke($order_id, $warehouse_info);
        $goods_data = [
            'DELIVERED_NUM'     => 0,
            'DEVIATION_NUM'     => 0,
            'DEVIATION_REASON'  => null,
            'OR_AGAIN_WAREING'  => null,
            'AGAIN_WAREING_NUM' => 0,
            'RECOVE_MONEY'      => 0.00,
            'recove_curreny'    => null,
            'end_amount'        => null,
            'defective_stored'  => 0,
            'incomplete_number' => null,
            'other_number'      => null,
            'REMARKS'           => null,
            'SUBMIT_TIME'       => null,
            'WAREING_DATE'      => null,
            'SUBMIT_USER_ID'    => null,
        ];
        $warehouse_goods_res = M('warehousing_goods', 'tb_b2b_')->where(['warehousing_id' => $warehouse_list_id])->save($goods_data);
        if (!$warehouse_goods_res) {
            throw new Exception(L('撤回理货商品失败'));
        }
        $warehouse_data = [
            'WAREHOUSEING_NUM'    => 0,
            'file_name'           => null,
            'SUBMIT_TIME'         => null,
            'WAREING_DATE'        => null,
            'submit_user'         => null,
            'RECOVE_MONEY'        => 0,
            'status'              => 0,
            'recove_curreny'      => null,
            'tally_statement'     => null,
            'return_warehouse_cd' => null,
            'tally_type_cd'       => null,
            'updated_by'       => DataModel::userNamePinyin(),
            'updated_id'       => DataModel::userId(),
        ];
        $warehouse_res = M('warehouse_list', 'tb_b2b_')->where(['ID' => $warehouse_list_id])->save($warehouse_data);
        $withdraw_res = M('warehouse_list', 'tb_b2b_')->where(['ID' => $warehouse_list_id])->setInc('withdraw_index');
        if (!$warehouse_res || !$withdraw_res) {
            throw new Exception(L('撤回理货单据失败'));
        }
        $goods_model = M('goods', 'tb_b2b_');
        $ship_goods_model = M('ship_goods', 'tb_b2b_');
        foreach ($warehouse_info as $value) {
            $goods_id = $ship_goods_model->where(['SHIP_ID'=>$value['SHIP_LIST_ID'], 'SHIPPING_SKU'=>$value['warehouse_sku']])->getField('goods_id');
            $map = [
                'ORDER_ID' => $order_id,
                'SKU_ID'   => $value['warehouse_sku'],
                'ID'       =>$goods_id
            ];
            $is_inwarehouse_num = $goods_model->where($map)->getField('is_inwarehouse_num');
            $is_inwarehouse_num = empty($is_inwarehouse_num) ? 0 : $is_inwarehouse_num;
            if ($is_inwarehouse_num < $value['delivered_num']) {
                throw new Exception(L('确认入库数小于本次撤回入库数'));
            }
            $goods_res = $goods_model->where($map)->setDec('is_inwarehouse_num', $value['delivered_num']);
            if (false === $goods_res) {
                throw new Exception(L('撤回已入库数失败'));
            }
        }

        $order_model = M('order', 'tb_b2b_');
        $where = ['ID' => $order_id];
        $inwarehouse_num = $goods_model->where(['ORDER_ID' => $order_id])->sum('is_inwarehouse_num');
        if ($inwarehouse_num > 0) {
            $order_res = $order_model->where($where)->save(['warehousing_state' => 1]);
        } else {
            $order_res = $order_model->where($where)->save(['warehousing_state' => 0]);
        }
        if (FALSE === $order_res) {
            throw new Exception(L('撤回订单数据失败'));
        }
        $ship_logistics_costs = M('ship_list', 'tb_b2b_')->where(['ID' => $warehouse_info[0]['SHIP_LIST_ID']])->getField('LOGISTICS_COSTS');
        $ship_logistics_costs = empty($ship_logistics_costs) ? 0 : $ship_logistics_costs;

        $profit_model = M('profit', 'tb_b2b_');
        $diff_num = $warehouse_info[0]['SHIPMENTS_NUMBER'] - $warehouse_info[0]['WAREHOUSEING_NUM'];//总差异数

        $condition = ['ORDER_ID' => $order_id];
        $profit_info = $profit_model->field('logistics_costs, defective_num')->where($condition)->find();
        $profit_logistics_costs = empty($profit_info['logistics_costs']) ? 0 : $profit_info['logistics_costs'];

        if ($profit_logistics_costs < $ship_logistics_costs) {
            throw new Exception(L('撤回物流成本大于物流总成本'));
        }
        if($diff_num > $profit_info['defective_num'] ) {
            throw new Exception(L('撤回差异数大于总差异入库数'));
        }
        $profit_data = [
            'logistics_costs'    => $profit_logistics_costs - $ship_logistics_costs,
            'recoverable_amount' => 0,
            'defective_num'      => $profit_info['defective_num'] - $diff_num
        ];
        $profit_res = $profit_model->where($condition)->save($profit_data);
        if (false === $profit_res) {
            throw new Exception(L('撤回利润数据失败'));
        }
        //批次记录删除及残次品入库数量减少
        $incomplete_num = array_sum(array_column($warehouse_info, 'incomplete_number'));
        if ($incomplete_num > 0) {
            //有残次品才可删除批次
            (new WmsModel())->b2bBatchDelete($warehouse_list_id);
        }
        //更新应收
        $this->updateOrderReceivableAccount($order_id);
    }

    //检查是否可理货撤回
    private function checkWarehouseRevoke($order_id, $warehouse_info)
    {
        $warehouse_list = $this->model->table('tb_b2b_warehouse_list wl')
            ->field('IFNULL(sum(wg.DELIVERED_NUM), 0) as delivered_num, wg.warehouse_sku')
            ->join('left join tb_b2b_warehousing_goods wg on wl.ID = wg.warehousing_id')
            ->where(['wl.order_id' => $order_id])
            ->group('wg.warehouse_sku')
            ->select();
        $return_num_info = $this->model->table('tb_b2b_return')
            ->field('tb_b2b_return_goods.sku_id, IFNULL(sum(tb_b2b_return_goods.return_num), 0) as return_num')
            ->join('left join tb_b2b_return_goods on tb_b2b_return.id = tb_b2b_return_goods.return_id')
            ->where(['tb_b2b_return.order_id' => $order_id])
            ->group('tb_b2b_return_goods.sku_id')
            ->select();
        $warehouse_num_map       = array_column($warehouse_info, 'delivered_num', 'warehouse_sku');//本次入库数和sku的映射
        $warehouse_total_num_map = array_column($warehouse_list, 'delivered_num', 'warehouse_sku');//总入库数和sku的映射
        $return_num_map          = array_column($return_num_info, 'return_num', 'sku_id');//退货数和sku的映射
        foreach ($warehouse_num_map as $sku_id => $num) {
            if ($num + $return_num_map[$sku_id] > $warehouse_total_num_map[$sku_id]) {
                throw new Exception(L('编码为：'.$sku_id.'的商品合格品数大于(已确认的合格品数量-已发起退货的合格品数量)'));
            }
        }

        $receivable_info = M('receivable','tb_b2b_')->where(['order_id' => $order_id])->find();
        if ($warehouse_info[0]['status'] != 2) {
            throw new Exception(L('理货单状态未确认'));
        }
        if ($warehouse_info[0]['tally_type_cd'] != self::NORMAL_TALLY) {
            throw new Exception(L('理货类型不是正常理货'));
        }
        if ($receivable_info['receivable_status'] != self::RECEIVABLE_NO_SUBMINT) {
            throw new Exception(L('B2B应收状态不是待提交'));
        }

    }

    //B2B发货关联出库单据
    public function updateShipOutBillId($b2b_order_no, $out_bill_id)
    {
        $b2b_order_id = M('b2b_order', 'tb_')->where(['PO_ID' => $b2b_order_no])->getField('ID');
        $ship_model = M('b2b_ship_list', 'tb_');
        $ship_id = $ship_model->where(['order_id' => $b2b_order_id])->order('id desc')->getField('ID');//取最新的一条更新
        $res = M('b2b_ship_list', 'tb_')->where(['ID' => $ship_id])->save(['out_bill_id' => $out_bill_id]);
        if(false === $res) {
            @SentinelModel::addAbnormal('B2B发货关联出库单据失败', $out_bill_id, [$out_bill_id, $b2b_order_id, $ship_id, $res],'pur_notice');
        }
    }

    /**
     * @param $request_data
     * @return array
     */
    public function getGPOrderSelect($request_data)
    {
        //查询订单状态=待付款 & 订单支付类型=银行转账 & 平台=Gshopper & 订单获取方式=系统拉单的所有订单
        $gp_cds = CodeModel::getGpPlatCds();
        $where['a.BWC_ORDER_STATUS'] = 'N000550300'; //待付款
        $where['a.PAY_METHOD'] = 'bankpay';  //付款方式，必填！！！
        $where['a.PLAT_CD'] = ['in', $gp_cds];
        if($request_data['shop_id']){
            $where['b.ID'] = $request_data['shop_id'];
        }
        if($request_data['third_order_no']){
            $where['a.ORDER_NO'] = $request_data['third_order_no'];
        }
        $res_db = $this->B2bRepository->getGPOrderSelect($where);
        foreach($res_db as $rk=>$rv){
            $order_list[$rv['order_id'].'_'.$rv['order_no']]  = $rv;
        }
        //每个GP订单的剩余应收
        $order_ids = array_column($res_db, 'order_id');
        $order_nos = array_column($res_db, 'order_no');

        $remind_should_receive = $this->model->table('tb_fin_claim')->where(['order_id'=>['in', $order_ids],'order_no'=>['in', $order_nos]])->select();
        foreach($remind_should_receive as $mk=>$mv){
            $remind_should_receive[$mv['order_id'].'_'.$mv['order_no']]['has_receive'] += $mv['summary_amount'];
        }

        foreach($order_list as $ok=>$ov){
            $order_list[$ok]['should_receive']  = $order_list[$ok]['pay_total_price'] - $remind_should_receive[$ok]['has_receive'];
        }

        return array_values($order_list);
    }


    //详情页获取GP的订单，进行下一步汇总
    public function getClaimGpOrders($where){
        $db_res = $this->model->table('tb_fin_claim')
            ->field(
                'tb_op_order.ORDER_NO as order_no,
                tb_op_order.PLAT_CD as plat_cd,
                tb_op_order.ORDER_PAY_TIME as po_time,
                tb_op_order.BWC_ORDER_STATUS as bwc_order_status,
                tb_op_order.PAY_CURRENCY as pay_currency,
                tb_op_order.PAY_TOTAL_PRICE as pay_total_price,
                tb_op_order.ORDER_ID as order_id,
                tb_op_order.pay_total_price,
                tb_ms_store.STORE_NAME as store_name,
                tb_fin_claim.claim_amount,   
                tb_fin_claim.summary_amount,
                tb_fin_claim.current_remaining_receivable as current_receivable,
                tb_fin_claim.id AS claim_id,
                tb_fin_claim.created_at,
                tb_fin_claim.updated_by,  
                tb_fin_claim.updated_at')
            ->join('LEFT JOIN tb_fin_account_turnover ON tb_fin_account_turnover.id = tb_fin_claim.account_turnover_id ')
            ->join('LEFT JOIN tb_op_order ON tb_fin_claim.order_id = tb_op_order.ORDER_ID  and  tb_fin_claim.order_no = tb_op_order.ORDER_NO')
            ->join("LEFT JOIN tb_ms_store  ON tb_op_order.STORE_ID = tb_ms_store.ID")
            ->where($where)
            ->where("tb_fin_claim.order_type = 'N001950656'")
            ->select();
        return $db_res;
    }

}