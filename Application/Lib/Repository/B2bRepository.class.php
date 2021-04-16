<?php
/**
 * User: yangsu
 * Date: 18/10/12
 * Time: 14:49
 */

@import("@.Model.Orm.TbFinAccountTurnoverStatus");
@import("@.Model.Orm.TbWmsWarehouseChild");
@import("@.Model.StringModel");

use Application\Lib\Model\StringModel;

/**
 * Class B2bRepository
 */
class B2bRepository extends Repository
{
    const B2B_CLAIM_CODE = '';//B2B收款认领
    const PUR_REFUND_CLAIM_CODE = 'N002630200';//采购退款认领

    public $claim_type;

    public function __construct($claim_type = "")
    {
        parent::__construct();
        $this->claim_type = $claim_type;
    }

    /**
     * @param array $order_id_arr
     *
     * @return array
     */
    public function getLastPaymentDate(array $order_id_arr)
    {
        $where_order['ORDER_ID'] = ['IN', $order_id_arr];
        $order_last_pay_date_arr = $this->model->table('tb_b2b_receipt')
            ->field('concat(ORDER_ID,receiving_code) as order_key,max(actual_receipt_date) as max_actual_receipt_date')
            ->where($where_order)
            ->group('ORDER_ID,receiving_code')
            ->select();
        return array_column($order_last_pay_date_arr, 'max_actual_receipt_date', 'order_key');
    }

    /**
     *
     */
    public function getMainReceiptInfos(array $order_id_arr)
    {
        $where_order['ORDER_ID'] = ['IN', $order_id_arr];
        $where_order['tb_b2b_receipt.transaction_type'] = ['EXP', 'IS NULL'];
        $order_last_pay_date_arr = $this->model->table('tb_b2b_receipt')
            ->field('concat(ORDER_ID,receiving_code) as order_key,main_receipt_operation_status,expect_receipt_date')
            ->where($where_order)
            ->group('ORDER_ID,receiving_code')
            ->select();
        return [array_column($order_last_pay_date_arr, 'main_receipt_operation_status', 'order_key'),
            array_column($order_last_pay_date_arr, 'expect_receipt_date', 'order_key')];
    }

    /**
     * @param $ids
     *
     * @return mixed
     */
    public function getOrderAccounts($ids)
    {
        $where['tb_b2b_order.id'] = ['IN', $ids];
        $accounts = $this->model->table('(tb_b2b_order,tb_b2b_info,tb_b2b_receivable)')
            ->field('tb_b2b_order.PO_ID,
            tb_b2b_info.ORDER_ID,
            tb_b2b_info.THR_PO_ID,
            tb_b2b_info.TAX_POINT,
            tb_b2b_info.CLIENT_NAME,
            tb_b2b_info.remarks,
            tb_b2b_info.our_company,
            tb_b2b_info.contract,
            tb_b2b_info.TARGET_PORT,
            tb_b2b_info.PAYMENT_NODE,
            tb_b2b_info.delivery_time,
            tb_b2b_info.DELIVERY_METHOD as delivery_method,
            tb_b2b_info.INVOICE_CODE as invoice_code,
            tb_b2b_info.TAX_POINT as tax_point,
            tb_b2b_info.logistics_estimat,
            tb_b2b_info.po_time,
            cd1.CD_VAL AS SALES_TEAM_VAL,tb_b2b_info.PO_USER,tb_b2b_info.po_time,
            cd2.CD_VAL AS po_currency_val,tb_b2b_info.po_amount,
            sum(tb_b2b_receipt.actual_payment_amount) AS sum_actual_payment_amount,
            MAX(tb_b2b_receipt.actual_receipt_date) AS last_actual_receipt_date,
            tb_b2b_receivable.rate_losses,
            tb_b2b_receivable.current_receivable,
            IFNULL(ROUND((tb_b2b_receivable.current_receivable * (CASE cd2.CD_VAL
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
                END) ),2),0.00) AS current_receivable_cny,
            cd3.CD_VAL AS receivable_status_val,
            tb_b2b_receivable.verification_by,
            tb_b2b_receivable.verification_at,
            tb_b2b_receivable.cancel_note,
            tb_b2b_order.order_state,
            tb_b2b_order.warehousing_state,
            tb_b2b_order.receipt_state,
            tb_b2b_order.order_overdue_statue,
            tb_b2b_info.rebate_rate,
            tb_b2b_info.rebate_amount')
            ->join('left join tb_ms_cmn_cd AS cd1 ON cd1.CD = tb_b2b_info.SALES_TEAM')
            ->join('left join tb_ms_cmn_cd AS cd2 ON cd2.CD = tb_b2b_info.po_currency')
            ->join('left join tb_ms_cmn_cd AS cd3 ON cd3.CD = tb_b2b_receivable.receivable_status')
            ->join('left join tb_ms_xchr on tb_ms_xchr.XCHR_STD_DT = DATE_FORMAT(tb_b2b_info.po_time,\'%Y%m%d\')')
            ->join('left join tb_b2b_receipt ON tb_b2b_receipt.order_id = tb_b2b_order.id AND tb_b2b_receipt.unconfirmed_state = 1 AND tb_b2b_receipt.receipt_operation_status = 1')
            ->where($where)
            ->where('tb_b2b_info.ORDER_ID = tb_b2b_order.id', null, true)
            ->where('tb_b2b_receivable.order_id = tb_b2b_order.id', null, true)
            ->group('tb_b2b_order.id')
            ->select();
        $area_init = B2bModel::get_area(true);
        $cd_model = M('ms_cmn','tb_');
        $node_types = BaseModel::periods();
        $node_dates = BaseModel::day();
        foreach ($accounts as &$v) {
            $area_info = json_decode($v['TARGET_PORT'], true);
            if (!empty($area_info)) {
                $v['target_city_detail'] = trim($area_init[$area_info['country']] . '-'. $area_init[$area_info['stareet']] . '-'. $area_init[$area_info['city']],'-');
            }
            if (!empty($area_info['targetCity'])) {
                $city = $area_init[$area_info['targetCity']];
                $v['target_city'] = empty($city) ? $area_info['targetCity'] : $city;
            }
            //收款节点
            $number_th = ['1st', '2nd', '3rd'];
            $node_info = json_decode($v['PAYMENT_NODE'], true);
            $node_str = '';
            if (!empty($node_info)) {
                foreach ($node_info as $node) {
                    if (!$node['nodeType']) {
                        continue;
                    }
                    $node_str .= $number_th[$node['nodei']]. ':' .$node_types[$node['nodeType']] .$node_dates[$node['nodeDate']]. '天'. '-'. $node['nodeProp']. '%;';
                }
            }
            $v['receipt_node'] = $node_str;
            if (null !== $v['rebate_rate']) {
                $v['rebate_rate'] = $v['rebate_rate'] * 100 . '%';
            }
            if (null !== $v['rebate_amount'] && "" !== $v['rebate_amount']) {
                $v['rebate_amount'] = $v['po_currency_val'].' '. $v['rebate_amount'];
            }

        }
        return $accounts;

    }

    /**
     * @return mixed
     */
    public function getDeliveryWithinOrder()
    {
        $where['tb_b2b_info.delivery_time'] = ['LT', date('Y-m-d') . ' 00:00:00'];
        $where_string = 'tb_b2b_info.ORDER_ID = tb_b2b_order.ID 
                AND tb_b2b_info.create_time >= \'2018-08-01 00:00:00\'
                AND tb_b2b_order.order_state != 2
                ';
        $db_results_arr = $this->model
            ->table('tb_b2b_info,tb_b2b_order')
            ->field('
                tb_b2b_info.PO_ID,
                tb_b2b_info.THR_PO_ID,
                tb_b2b_info.CLIENT_NAME,
                tb_b2b_info.TARGET_PORT,
                tb_b2b_info.SALES_TEAM,
                tb_b2b_info.PO_USER,
                DATE(tb_b2b_info.delivery_time) AS delivery_time
                ')
            ->where($where)
            ->where($where_string, null, true)
            ->select();
        return $db_results_arr;
    }

    /**
     * @param array $orders
     *
     * @return mixed
     */
    public function getOrderTakeWarehouses(array $orders)
    {
        $where['tb_wms_batch_order.ORD_ID'] = ['IN', $orders];
        $where_string = 'tb_wms_batch_order.delivery_warehouse = tb_ms_cmn_cd.CD';
        $db_results_arr = $this->model
            ->table('(
                    SELECT
                        *
                    FROM
                        `tb_wms_batch_order`
                    WHERE 
                        use_type = 1
                    GROUP BY
                        tb_wms_batch_order.ORD_ID,
                        tb_wms_batch_order.delivery_warehouse
                    ) AS tb_wms_batch_order,
                    tb_ms_cmn_cd')
            ->field('
                tb_wms_batch_order.ORD_ID,
                GROUP_CONCAT(tb_ms_cmn_cd.CD_VAL) AS warehouses           
                ')
            ->where($where)
            ->where($where_string, null, true)
            ->group('tb_wms_batch_order.ORD_ID')
            ->select();
        return $db_results_arr;
    }

    /**
     * @return mixed
     */
    public function getTallyWithinOrder()
    {
        $where['tb_b2b_ship_list.Estimated_arrival_DATE'] = ['LT', date('Y-m-d', strtotime('-30 days')) . ' 00:00:00'];
        $where['tb_b2b_receivable.receivable_status'] = ['NEQ', 'N002540300'];
        $where_string = 'tb_b2b_ship_list.order_id = tb_b2b_order.ID 
                AND tb_b2b_warehouse_list.ORDER_ID = tb_b2b_order.ID 
                AND tb_b2b_ship_list.ID = tb_b2b_warehouse_list.SHIP_LIST_ID 
                AND tb_b2b_info.create_time >= \'2018-08-01 00:00:00\'
                AND tb_b2b_order.warehousing_state != 2
                AND tb_b2b_warehouse_list.status != 2
                AND tb_b2b_info.ORDER_ID = tb_b2b_order.ID
                AND tb_b2b_receivable.order_id = tb_b2b_order.ID
                AND tb_b2b_order.create_time >= \'2018-11-01 00:00:00\'
                ';
        $db_results_arr = $this->model
            ->table('tb_b2b_order,tb_b2b_info,tb_b2b_ship_list,tb_b2b_warehouse_list,tb_b2b_receivable')
            ->field('
                tb_b2b_ship_list.BILL_LADING_CODE,
                tb_b2b_info.PO_ID,
                tb_b2b_info.THR_PO_ID,
                tb_b2b_ship_list.warehouse,
                tb_b2b_ship_list.AUTHOR,
                tb_b2b_info.CLIENT_NAME,
                tb_b2b_info.TARGET_PORT,
                tb_b2b_info.SALES_TEAM,
                tb_b2b_info.PO_USER,                               
                DATE(tb_b2b_ship_list.Estimated_arrival_DATE) AS Estimated_arrival_DATE
                ')
            ->where($where)
            ->where($where_string, null, true)
            ->select();
        return $db_results_arr;
    }

    /**
     * @param $join_where
     * @param $limit
     * @param bool $is_excel
     *
     * @return array
     */
    public function getClaimList($join_where, $limit, $is_excel = false)
    {
        $join_where['tb_fin_account_turnover.collection_type'] = 'N002520100';
        $pages['total'] = $this->model->table('tb_fin_account_turnover')
            ->field('
                tb_fin_account_turnover.id           
                ')
            ->join('LEFT JOIN (SELECT IFNULL(claim_status,\'N002550100\')  AS claim_status,account_turnover_id FROM tb_fin_account_turnover_status) AS tb_fin_account_turnover_status ON tb_fin_account_turnover_status.account_turnover_id = tb_fin_account_turnover.id')
            ->join('LEFT JOIN (SELECT SUM(claim_amount) AS sum_claim_amount,account_turnover_id FROM tb_fin_claim WHERE order_type = \'N001950200\' GROUP BY account_turnover_id ) AS t1 ON t1.account_turnover_id = tb_fin_account_turnover.id')
            ->where($join_where)
            ->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page'] = $limit[1];
        $db_join = $this->model->table('tb_fin_account_turnover')
            ->field('
                tb_fin_account_turnover.id,
                tb_fin_account_turnover.account_transfer_no,
                tb_fin_account_turnover.original_currency,
                tb_fin_account_turnover.original_amount,
                tb_fin_account_turnover.company_name,
                tb_fin_account_turnover.company_code,
                tb_fin_account_turnover.our_remark,
                     
                tb_fin_account_turnover.open_bank AS our_bank,
                tb_fin_account_turnover.opp_account_bank,
                tb_fin_account_turnover.account_bank AS our_open_bank,
                tb_fin_account_turnover.opp_open_bank,
                tb_fin_account_bank.swift_code AS our_swift_code,
                tb_fin_account_turnover.opp_swift_code AS other_swift_code,
                
                tb_fin_account_turnover.opp_company_name,
                tb_fin_account_turnover.transfer_time,
                tb_fin_account_turnover.create_time,
                tb_fin_account_turnover.remark,
                IFNULL(t1.sum_claim_amount,0) AS claimed_amount,
                IFNULL((tb_fin_account_turnover.original_amount - IFNULL(t1.sum_claim_amount,0)),0) AS claim_amount,               
                IFNULL(tb_fin_account_turnover_status.claim_status,\'N002550100\') AS claim_status 
                ')
            ->join('LEFT JOIN (SELECT IFNULL(claim_status,\'N002550100\')  AS claim_status,account_turnover_id FROM tb_fin_account_turnover_status) AS tb_fin_account_turnover_status ON tb_fin_account_turnover_status.account_turnover_id = tb_fin_account_turnover.id')
            ->join('LEFT JOIN (SELECT SUM(claim_amount) AS sum_claim_amount,account_turnover_id FROM tb_fin_claim WHERE order_type in( \'N001950200\',\'N001950600\',\'N001950656\') GROUP BY account_turnover_id ) AS t1 ON t1.account_turnover_id = tb_fin_account_turnover.id')
            ->join('LEFT JOIN tb_fin_account_bank AS tb_fin_account_bank ON tb_fin_account_bank.account_bank = tb_fin_account_turnover.account_bank ')
            ->where($join_where);
        if (false === $is_excel) {
            $db_join->limit($limit[0], $limit[1]);
        }
        $db_res = $db_join->order('tb_fin_account_turnover.id desc')
            ->select();
        return [$db_res, $pages];
    }

    /**
     * @param $request_where
     * @param $request_data
     *
     * @return mixed
     */
    public function getClaimDetailInfo($request_where, $request_data)
    {
        if ($request_data['account_transfer_no']) {
            $where['tb_fin_account_turnover.account_transfer_no'] = $request_data['account_transfer_no'];
        }
//            tb_fin_account_turnover.waiting_claimed_amount,
        $db_res = $this->model->table('tb_fin_account_turnover')
            ->field('
            tb_fin_account_turnover.id AS account_transfer_id,
            tb_fin_account_turnover.account_transfer_no,
            tb_fin_account_turnover.pay_or_rec,
            tb_fin_account_turnover.currency_code,
            tb_fin_account_turnover.amount_money,
            tb_fin_account_turnover.company_code,
            tb_fin_account_turnover.opp_company_name,
            tb_fin_account_turnover.our_remark,
            
            tb_fin_account_turnover.open_bank AS our_bank,
            tb_fin_account_turnover.opp_account_bank,
            tb_fin_account_turnover.account_bank AS our_open_bank,
            tb_fin_account_turnover.opp_open_bank,
            tb_fin_account_bank.swift_code AS our_swift_code,
            tb_fin_account_turnover.opp_swift_code AS other_swift_code,
              
            tb_fin_account_turnover.transfer_voucher AS credentials_file,
            tb_fin_account_turnover.transfer_time,
            bbm_admin.M_NAME AS create_user,
            tb_fin_account_turnover.create_time,
            tb_fin_account_turnover.collection_type AS transfer_type,
            tb_fin_account_turnover.transfer_type AS this_transfer_type,
            tb_fin_account_turnover.remark,
            
            tb_fin_account_turnover.original_currency,
            tb_fin_account_turnover.original_amount,
            tb_fin_account_turnover.amount_money,
            tb_fin_account_turnover.other_currency,
            tb_fin_account_turnover.other_cost,
            tb_fin_account_turnover.remitter_currency,
            tb_fin_account_turnover.remitter_cost,
            
            tb_fin_account_turnover.original_currency,
            tb_fin_account_turnover.currency_code,
            IFNULL(tb_fin_account_turnover_status.claim_status,\'N002550100\') AS claim_status,
            tb_fin_claim.sum_claim_amount,
            tb_fin_account_turnover_status.tag_by,
            tb_fin_account_turnover_status.tag_at
            ')
            ->join('LEFT JOIN tb_fin_account_turnover_status  ON tb_fin_account_turnover_status.account_turnover_id = tb_fin_account_turnover.id ')
            ->join('LEFT JOIN tb_fin_account_bank  ON tb_fin_account_bank.account_bank = tb_fin_account_turnover.account_bank ')
            ->join('LEFT JOIN bbm_admin ON bbm_admin.M_ID = tb_fin_account_turnover.create_user ')
            ->join('LEFT JOIN (SELECT account_turnover_id,SUM(claim_amount) AS sum_claim_amount FROM tb_fin_claim WHERE order_type in(\'N001950200\', \'N001950600\',\'N001950656\') GROUP BY account_turnover_id ) AS tb_fin_claim ON tb_fin_claim.account_turnover_id = tb_fin_account_turnover.id ')
            ->where($where)
            ->find();
        return $db_res;
    }

    /**
     * @param $where
     *
     * @return mixed
     */
    public function getClaimOrders($where)
    {
        $where_string = 'tb_fin_claim.order_id = tb_b2b_order.id
            AND tb_fin_claim.order_id = tb_b2b_info.ORDER_ID  
            AND tb_fin_claim.order_id = tb_b2b_receivable.order_id
            AND tb_fin_claim.order_type = \'N001950200\'
        ';
        $db_res = $this->model->table('(tb_fin_claim,tb_b2b_info,tb_b2b_order,tb_b2b_receivable)')
            ->field('
                 tb_b2b_info.PO_ID,   
                 tb_b2b_info.THR_PO_ID,   
                 tb_b2b_info.ORDER_ID,   
                 tb_b2b_info.po_time,   
                 tb_b2b_info.CLIENT_NAME,   
                 tb_b2b_info.po_user,   
                 tb_b2b_info.po_currency,   
                 tb_b2b_info.SALES_TEAM,   
                 
                 tb_b2b_order.order_state,   
                 tb_b2b_order.warehousing_state,   
                 tb_b2b_receivable.receivable_status,   
                 tb_b2b_receivable.order_account,   
                 tb_fin_claim.claim_amount,           
                 tb_fin_claim.summary_amount,           
                 tb_b2b_receivable.current_receivable,           
                 tb_fin_claim.id AS claim_id,
                     
                 tb_fin_claim.updated_by,   
                 tb_fin_claim.updated_at
                         
            ')
            ->join('LEFT JOIN tb_fin_account_turnover ON tb_fin_account_turnover.id = tb_fin_claim.account_turnover_id ')
            ->where($where)
            ->where($where_string, null, true)
            ->select();

        foreach ($db_res as &$value) {
            $value['claim_code'] = 'N002630100';
        }
        return $db_res;
    }

    /**
     * @param $where
     *
     * @return mixed
     */
    public function getClaimDeduction($where)
    {
        return $this->model->table('tb_b2b_claim_deduction')->where($where)->select();
    }

    /**
     * @param $wheres
     *
     * @return mixed
     */
    public function getOrderSelect($wheres)
    {
        if ($wheres['tb_fin_claim.account_turnover_id']) {
            $account_turnover_id = $wheres['tb_fin_claim.account_turnover_id'];
            unset($wheres['tb_fin_claim.account_turnover_id']);
        }
        $where_string = 'tb_b2b_receivable.order_id = tb_b2b_info.ORDER_ID
            AND  tb_b2b_info.ORDER_ID = tb_b2b_order.id 
            AND  tb_b2b_receivable.receivable_status = \'N002540100\' ';
        $db_res = $this->model->table('(tb_b2b_info,tb_b2b_receivable,tb_b2b_order)')
            ->field('tb_b2b_info.PO_ID,
            tb_b2b_info.ORDER_ID,
            tb_b2b_info.THR_PO_ID,
            tb_b2b_info.PO_USER,
            tb_b2b_info.po_time,
            tb_b2b_info.SALES_TEAM,
            tb_b2b_info.CLIENT_NAME,
            tb_b2b_receivable.receivable_status,
            tb_b2b_receivable.order_account,
            tb_b2b_receivable.current_receivable  AS current_remaining_receivable,
            tb_b2b_info.po_currency,
            tb_b2b_info.po_amount,
            tb_b2b_order.order_state AS ship_state,
            tb_b2b_order.warehousing_state,
            tb_fin_claim.id AS claim_id ')
            ->join("LEFT JOIN tb_fin_claim ON tb_fin_claim.order_id = tb_b2b_info.ORDER_ID   AND tb_fin_claim.order_type = 'N001950200'  AND tb_fin_claim.account_turnover_id = {$account_turnover_id}")
            ->where($wheres)
            ->where($where_string, null, true)
            ->select();
        return $db_res;
    }

    /**
     * @param $where
     * @param $save
     *
     * @return TbFinAccountTurnoverStatus
     */
    public function updateClaimStatus($where, $save)
    {
        $save['tag_by'] = $save['updated_by'] = DataModel::userNamePinyin();
        $save['tag_at'] = date('Y-m-d H:i:s');
        return TbFinAccountTurnoverStatus::updateOrCreate($where, $save);
    }

    /**
     * @param $claim_ids
     * @param $Model
     * @param $claim_type
     *
     * @return mixed
     */
    public function deleteClaim($claim_ids, $Model)
    {
        $where['tb_fin_claim.id'] = ['IN', $claim_ids];
        if ($this->claim_type == self::B2B_CLAIM_CODE) {
            $where['tb_fin_claim.order_type'] = 'N001950200';
        }
        if ($this->claim_type == self::PUR_REFUND_CLAIM_CODE) {
            $where['tb_fin_claim.order_type'] = 'N001950600';
        }
        if ($this->claim_type == B2bAction::GP_BIG_ORDER_CLAIM_CODE) {
            $where['tb_fin_claim.order_type'] = 'N001950656';
        }
//        $where['tb_fin_account_turnover_status.id'] = ['IN', $claim_ids];
//        $where_string = 'tb_fin_account_turnover_status.account_turnover_id = tb_fin_claim.account_turnover_id';
        return $Model->table('tb_fin_claim')
            ->where($where)
            ->delete();
    }

    /**
     * @param array $order_ids
     */
    public function excludingTax(array $order_ids)
    {
        $where['tb_b2b_order.id'] = ['IN', $order_ids];
        $where_string = 'tb_b2b_goods.ORDER_ID = tb_b2b_order.id';
        $this->model->table('tb_b2b_order,tb_b2b_goods')
            ->field('')
            ->join('LEFT JOIN tb_b2b_ship_list ON tb_b2b_ship_list.order_id = tb_b2b_order.id')
            ->join('LEFT JOIN tb_b2b_ship_goods ON tb_b2b_ship_list.ID = tb_b2b_ship_goods.SHIP_ID')
            ->join('LEFT JOIN tb_b2b_warehouse_list ON tb_b2b_warehouse_list.ORDER_ID = tb_b2b_order.id')
            ->join('LEFT JOIN tb_b2b_warehousing_goods ON tb_b2b_warehouse_list.ID = tb_b2b_warehousing_goods.warehousing_id')
            ->where($where)
            ->where($where_string, null, true)
            ->group('tb_b2b_order.id,tb_b2b_ship_list.ID')
            ->select();
    }


    /**
     * @param $claim_ids
     * @param $Model
     *
     * @return mixed
     */
    public function deleteClaimDeduction($claim_ids, $Model)
    {
        $where['claim_id'] = ['IN', $claim_ids];
        return $Model->table('tb_b2b_claim_deduction')
            ->where($where)
            ->delete();
    }

    /**
     * @param $join_where
     * @param $limit
     * @param bool $is_excel
     *
     * @return array
     */
    public function getReceivableList($join_where, $limit, $is_excel = false)
    {
        $where_string = 'tb_b2b_receivable.order_id = tb_b2b_order.id 
            AND tb_b2b_info.ORDER_ID = tb_b2b_order.id';
        if (isset($join_where['tb_b2b_info.PO_USER']) && $join_where['tb_b2b_info.PO_USER']) {
            $where_string .= " AND (tb_b2b_info.PO_USER = '{$join_where['tb_b2b_info.PO_USER']}' OR tb_con_division_client.sales_assistant_by = '{$join_where['tb_b2b_info.PO_USER']}')";
            $other_where = $join_where['tb_b2b_info.PO_USER'];
            unset($join_where['tb_b2b_info.PO_USER']);
        }
        $sql = $this->model->table('(tb_b2b_receivable,tb_b2b_info,tb_b2b_order)')
            ->field('
                tb_b2b_receivable.id
              ')
            ->where($join_where)
            ->where($where_string, null, true);

        if (isset($join_where['tb_b2b_info.CLIENT_NAME']) || isset($other_where)) {
            $sql->join('LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = tb_b2b_info.CLIENT_NAME AND tb_crm_sp_supplier.DATA_MARKING = 1')
                ->join('left join tb_con_division_client on tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID');
        }
        $pages['total'] = $sql->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page'] = $limit[1];
        $group_where_model = clone $this->model;
        $other_sql = $this->model->table('(tb_b2b_receivable,tb_b2b_info,tb_b2b_order)')
            ->field('
                tb_b2b_receivable.id,
                tb_b2b_info.PO_ID,
                tb_b2b_info.THR_PO_ID,
                tb_b2b_info.ORDER_ID,
                tb_b2b_info.CLIENT_NAME,
                tb_b2b_info.PO_USER,
                tb_b2b_info.SALES_TEAM,
                tb_b2b_info.po_currency,
                tb_b2b_info.po_time,               
            
                IFNULL(
                    tb_b2b_receivable.order_account,
                    0.00
                ) AS order_account,
                IFNULL(
                    tb_b2b_receivable.rate_losses,
                    0.00
                ) AS rate_losses,
                IFNULL(
                    tb_b2b_receivable.actual_collection,
                    0.00
                ) AS actual_collection,
                tb_b2b_receivable.submit_by,
                tb_b2b_receivable.submit_at,
                tb_b2b_receivable.verification_by,
                tb_b2b_receivable.verification_at,
                IFNULL(tb_b2b_receivable.current_receivable,0.00) AS current_receivable,
                IFNULL((
                ROUND(tb_b2b_receivable.current_receivable * (CASE tb_ms_cmn_cd.CD_VAL
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
                    END)
               ,2) ),0.00)  AS current_receivable_cny,
                tb_b2b_receivable.created_at,
                tb_b2b_receivable.receivable_status
              ')
            ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_b2b_info.po_currency')
            ->join('left join tb_ms_xchr on tb_ms_xchr.XCHR_STD_DT = DATE_FORMAT(tb_b2b_info.po_time,\'%Y%m%d\')');
        if (isset($where['tb_b2b_info.CLIENT_NAME']) || isset($other_where)) {
            $other_sql->join('LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = tb_b2b_info.CLIENT_NAME AND tb_crm_sp_supplier.DATA_MARKING = 1')
                ->join('left join tb_con_division_client on tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID');
        }
        $db_where = $other_sql->where($join_where)->where($where_string, null, true);
        if (false === $is_excel) {
            $db_where = $db_where->limit($limit[0], $limit[1]);
        }
        $db_res = $db_where
            ->order('tb_b2b_receivable.id desc')
            ->select();
        $sum_current_receivable_db = [];
        if (false === $is_excel) {
            $where_doing_receivable_status = ['N002540100', 'N002540200'];
            if (!isset($join_where['tb_b2b_receivable.receivable_status'])){
                $join_where['tb_b2b_receivable.receivable_status'] = ['IN', $where_doing_receivable_status];
            }
            $sum_current_receivable_db = $group_where_model->table('(tb_b2b_receivable,tb_b2b_info,tb_b2b_order)')
                ->field('            
               sum(
                ROUND(tb_b2b_receivable.current_receivable * (CASE tb_ms_cmn_cd.CD_VAL
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
                    END)
               ,2 ))  AS sum_current_receivable_cny
              ')
                ->join('left join tb_ms_cmn_cd on tb_ms_cmn_cd.CD = tb_b2b_info.po_currency')
                ->join('left join tb_ms_xchr on tb_ms_xchr.XCHR_STD_DT = DATE_FORMAT(tb_b2b_info.po_time,\'%Y%m%d\')')
                ->join('LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = tb_b2b_info.CLIENT_NAME AND tb_crm_sp_supplier.DATA_MARKING = 1')
                ->join('left join tb_con_division_client on tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID')
                ->where($join_where)
                ->where($where_string, null, true)
                ->find();
        }
        $db_res = $this->joinSumDeductionAmount($db_res, 'ORDER_ID');
        return [$db_res, $pages, $sum_current_receivable_db['sum_current_receivable_cny']];
    }

    /**
     * @param $data
     * @param $order_key
     * @param bool $is_one
     *
     * @return mixed
     */
    private function joinSumDeductionAmount($data, $order_key, $is_one = false)
    {
        if ($is_one) {
            $order_ids[] = $data[$order_key];
        } else {
            $order_ids = array_column($data, $order_key);
        }
        if ($order_ids) {
            $where_string = 'tb_fin_claim.order_id = tb_b2b_receivable.order_id';
            $where['tb_fin_claim.order_id'] = ['IN', $order_ids];
            $where['tb_fin_claim.order_type'] = 'N001950200';
            $sum_deduction_amounts = $this->model->table('tb_fin_claim,tb_b2b_receivable')
                ->field('tb_fin_claim.order_id,tb_b2b_receivable.sum_deduction_amount')
                ->where($where)
                ->where($where_string, null, true)
                ->group('tb_fin_claim.order_id')
                ->select();
            $sum_deduction_amount_key_vals = array_column($sum_deduction_amounts, 'sum_deduction_amount', 'order_id');
            if ($is_one) {
                $data['sum_deduction_amount'] = $sum_deduction_amount_key_vals[$data[$order_key]] ? $sum_deduction_amount_key_vals[$data[$order_key]] : '0.00';
            } else {
                foreach ($data as &$datum) {
                    $datum['sum_deduction_amount'] = $sum_deduction_amount_key_vals[$datum[$order_key]] ? $sum_deduction_amount_key_vals[$datum[$order_key]] : '0.00';
                }
            }
        }
        return $data;
    }

    /**
     * @param $order_id
     *
     * @return mixed
     */
    public function getReceivableDetail($order_id)
    {
        $where_claim['tb_fin_claim.order_id'] = $where_info['tb_b2b_order.id'] = $order_id;
        $where_claim['tb_fin_claim.order_type'] = 'N001950200';
        $where_info_string = 'tb_b2b_info.ORDER_ID = tb_b2b_order.id
            AND tb_b2b_order.id = tb_b2b_receivable.order_id';
        $res['info'] = $this->model->table('(tb_b2b_info,tb_b2b_order,tb_b2b_receivable)')
            ->field('tb_b2b_info.*,tb_b2b_order.*,tb_b2b_receivable.*,tb_con_division_client.sales_assistant_by')
            ->join('LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = tb_b2b_info.CLIENT_NAME AND tb_crm_sp_supplier.DATA_MARKING = 1')
            ->join('LEFT JOIN tb_con_division_client ON tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID')
            ->where($where_info)
            ->where($where_info_string, null, true)
            ->group('tb_b2b_order.id')
            ->find();
        $res['info']['sum_power_all'] = $this->getPowerAll([$order_id])[$order_id];
        list($sum_warehouse_arr, $sum_deviation_arr) = $this->getWarehouseAll([$order_id]);
        $res['info']['sum_warehousing_all'] = $sum_warehouse_arr[$order_id];
        $res['info'] = $this->joinSumDeductionAmount($res['info'], 'ORDER_ID', true);
        $where_claim_string = 'tb_fin_account_turnover.id = tb_fin_claim.account_turnover_id';
        $res['claims'] = $this->model->table('(tb_fin_account_turnover,tb_fin_claim)')
            ->field('tb_fin_account_turnover.*,
            tb_fin_claim.*,
            tb_fin_account_turnover.open_bank AS our_bank,
            tb_fin_account_turnover.opp_account_bank,
            tb_fin_account_turnover.account_bank AS our_open_bank,
            tb_fin_account_turnover.opp_open_bank,
            tb_fin_account_bank.swift_code AS our_swift_code,
            tb_fin_account_turnover.opp_swift_code AS other_swift_code')
            ->join('LEFT JOIN tb_fin_account_bank AS tb_fin_account_bank ON tb_fin_account_bank.account_bank = tb_fin_account_turnover.account_bank ')
            ->where($where_claim)
            ->where($where_claim_string, null, true)
            ->select();
        if ($res['claims']) {
            $where_claim_deduction['tb_b2b_claim_deduction.claim_id'] = ['IN', array_column($res['claims'], 'id')];
            $where_claim_deduction['tb_fin_claim.order_type'] = 'N001950200';
            $where_claim_deduction_string = 'tb_b2b_claim_deduction.claim_id = tb_fin_claim.id 
            AND tb_fin_account_turnover.id = tb_fin_claim.account_turnover_id';
            $res['claim_deductions'] = $this->model->table('tb_b2b_claim_deduction,tb_fin_claim,tb_fin_account_turnover')
                ->field('tb_b2b_claim_deduction.*,
                tb_fin_account_turnover.original_currency,
                tb_fin_account_turnover.account_transfer_no
                ')
                ->where($where_claim_deduction)
                ->where($where_claim_deduction_string, null, true)
                ->select();
        }
        return $res;
    }

    /**
     * @param array $order_ids
     *
     * @return array
     */
    private function getWarehouseAll(array $order_ids)
    {
        $where['tb_b2b_goods.ORDER_ID'] = ['IN', $order_ids];
        $where_string = 'tb_b2b_goods.ORDER_ID = tb_b2b_warehouse_list.ORDER_ID
        AND tb_b2b_warehousing_goods.warehousing_id = tb_b2b_warehouse_list.ID
        AND tb_b2b_warehouse_list.status = 2
        AND tb_b2b_warehousing_goods.sku_show = tb_b2b_goods.SKU_ID';
        $res = $this->model->table("(SELECT ORDER_ID,SKU_ID,price_goods FROM tb_b2b_goods GROUP BY ORDER_ID,SKU_ID) AS tb_b2b_goods,
        tb_b2b_warehouse_list,
        tb_b2b_warehousing_goods")
            ->field('tb_b2b_goods.ORDER_ID,
            tb_b2b_goods.SKU_ID,
            SUM(tb_b2b_warehousing_goods.DELIVERED_NUM*tb_b2b_goods.price_goods) AS sum_warehouse_all,
            SUM((tb_b2b_warehousing_goods.TOBE_WAREHOUSING_NUM - tb_b2b_warehousing_goods.DELIVERED_NUM)*tb_b2b_goods.price_goods) AS sum_deviation_all')
            ->where($where)
            ->where($where_string, null, true)
            ->group('tb_b2b_goods.ORDER_ID')
            ->select();
        $sum_warehouse_arr = array_column($res, 'sum_warehouse_all', 'ORDER_ID');
        $sum_deviation_arr = array_column($res, 'sum_deviation_all', 'ORDER_ID');
        return [$sum_warehouse_arr, $sum_deviation_arr];
    }

    /**
     * @param array $order_ids
     *
     * @return array
     */
    private function getPowerAll(array $order_ids)
    {
        $where['tb_b2b_goods.ORDER_ID'] = ['IN', $order_ids];
        $res = $this->model->table("tb_b2b_goods")
            ->field('tb_b2b_goods.ORDER_ID,tb_b2b_goods.SKU_ID,SUM(tb_b2b_goods.SHIPPED_NUM*tb_b2b_goods.price_goods) AS sum_power_all')
            ->where($where)
            ->group('tb_b2b_goods.ORDER_ID')
            ->select();
        $res_return = array_column($res, 'sum_power_all', 'ORDER_ID');
        return $res_return;
    }

    /**
     * @param $order_id
     * @param null $update_key
     *
     * @return bool
     */
    public function updateOrderReceivableAccount($order_id, $update_key = null)
    {
        $res = false;
        $save = [];
        if ($update_key) {
            switch ($update_key) {
                case 'order_account':
                    $sum_power_all = $this->getPowerAll([$order_id])[$order_id];
                    list($sum_warehouse_arr, $sum_deviation_arr) = $this->getWarehouseAll([$order_id]);
                    $sum_return_all = (new B2bService())->getReturnMoneyAll($order_id);
                    $save['order_account'] = $sum_power_all - $sum_deviation_arr[$order_id] - $sum_return_all;
                    break;
                case 'actual_collection':
                    $save['actual_collection'] = $this->sumActualCollection([$order_id])[$order_id];
                    break;
                case 'current_receivable':
                    $save['current_receivable'] = $this->currentReceivable([$order_id])[$order_id];
                    break;
                case 'sum_deduction_amount':
                    $save['sum_deduction_amount'] = $this->sumDeductionAmount([$order_id])[$order_id];
                    break;
            }
        } else {
            $sum_power_all = $this->getPowerAll([$order_id])[$order_id];
            list($sum_warehouse_arr, $sum_deviation_arr) = $this->getWarehouseAll([$order_id]);
            $sum_return_all = (new B2bService())->getReturnMoneyAll($order_id);
            $save['order_account'] = $sum_power_all - $sum_deviation_arr[$order_id] - $sum_return_all;
            $save['actual_collection'] = $this->sumActualCollection([$order_id])[$order_id];
            $save['sum_deduction_amount'] = $this->sumDeductionAmount([$order_id])[$order_id];
            $end_history_deduction = 0;
            $append_claims_deductions_db = $this->getAppendClaimsDeductions($order_id);
            if (false !== $append_claims_deductions_db) {
                $end_history_deduction = array_sum(array_column($append_claims_deductions_db, 'deductible_receivable_amount'));
            }
            $save['current_receivable'] = $this->currentReceivable(
                [$order_id],
                $save['order_account'],
                $save['actual_collection'],
                $end_history_deduction
            )[$order_id];
        }
        $where['order_id'] = $order_id;
        if ($save) {
            $res = $this->model->table('tb_b2b_receivable')->where($where)->save($save);
            Logs([$res, $where, $save], __FUNCTION__, __CLASS__);
        }
        return $res;
    }

    /**
     * @param null $out_bill_id
     * @param integer $type
     * @return bool
     */
    public function b2bVirtualWarehouseRevoke($out_bill_id, $type = 0)
    {
        $return = [
            'code' => -1,
            'msg' => '',
            'out_bill_id' => $out_bill_id,
            'type' => $type,
        ];
        $M = M();
        if ($out_bill_id) {
            //单据 单据发货、入库
            $where = ['bill_id' => $out_bill_id];
            $wms_bill = $M->table('tb_wms_bill')->where($where)->find();
            Logs([$wms_bill,'tb_wms_bill'], __FUNCTION__, __CLASS__);
            if (empty($wms_bill)) {
                $return['msg'] = '该单据不存在';
                return $return;
            }
            //提单号
            $bill_of_landing = $wms_bill['batch'];

            //b2b发货信息
            $where = ['out_bill_id' => $out_bill_id];
            $b2b_ware_ship_list = $M->table('tb_b2b_ship_list')->where($where)->find();
            $SHIPMENTS_NUMBER = $b2b_ware_ship_list['SHIPMENTS_NUMBER']; //本次发货数量NUM(撤回的发货数量)
            Logs([$b2b_ware_ship_list,'tb_b2b_ship_list'], __FUNCTION__, __CLASS__);
            if (empty($b2b_ware_ship_list)) {
                $return['msg'] = 'b2b发货信息不存在';
                return $return;
            }
            //校验
            if ($resCheck = $this->b2bVirtualWarehouseRevokeCheck($b2b_ware_ship_list['order_id'], $b2b_ware_ship_list['ID'])) {
                $return['msg'] = $resCheck['msg'];
                return $return;
            }

            $procurement_number = $wms_bill['procurement_number'];
            $where = ['procurement_number' => $procurement_number];
            $order_detail = $M->table('tb_pur_order_detail')->where($where)->find();
            Logs([$order_detail,'tb_pur_order_detail'], __FUNCTION__, __CLASS__);
            //校验关联采购单的付款条款未包含【发货后付款】N001390200、【入库后付款】N001390400
            //tb_pur_order_detail payment_info 付款信息:付款类型json
            if ($order_detail['payment_info'] && (strpos($order_detail['payment_info'], 'N001390200') !== false || strpos($order_detail['payment_info'], 'N001390400') !== false)) {
                $return['msg'] = '关联采购单的付款条款不能包含【发货后付款】N001390200、【入库后付款】N001390400';
                return $return;
            }

            //b2b待发货
            //截取采购单id获得PO_ID
            $po_id = explode('-', $procurement_number)[0];
            $where_doship = ['PO_ID' => $po_id];
            $b2b_doship = $M->table('tb_b2b_doship')->where($where_doship)->find();
            Logs([$b2b_doship,'tb_b2b_doship'], __FUNCTION__, __CLASS__);

            $M->startTrans();
            //编辑记录
            //tb_b2b_order b2b单据 入库状态
            $where = ['ID' => $b2b_ware_ship_list['order_id']];
            $b2b_order = $M->table('tb_b2b_order')->where($where)->find();
            Logs([$b2b_order,'tb_b2b_order'], __FUNCTION__, __CLASS__);
            $order_state = 0;
            if ($SHIPMENTS_NUMBER < $b2b_doship['sent_num']) {
                $order_state = 1;
            }
            //撤回发货数量 < 总已发数量 发货状态调整:部分发货
            //撤回发货数量 = 总已发数量 发货状态调整:待（未）发货
            $save_b2b_order['order_state'] = $order_state; //发货状态调整
            $save_b2b_order['update_user'] = DataModel::userNamePinyin();
            $res = $M->table('tb_b2b_order')->where($where)->save($save_b2b_order);
            $return['tb_b2b_order'] = $res;
            /*if (!$res) {
                $return['code'] = -1;
                $return['msg'] = 'b2b单据状态未修改-请确认虚拟仓发货记录是否已经撤回';
                return $return;
            }*/

            //采购单的出库、入库状态
            $where = ['order_id' => $order_detail['order_id']];
            $relevance = $M->table('tb_pur_relevance_order')->where($where)->find();
            Logs([$relevance,'tb_pur_relevance_order'], __FUNCTION__, __CLASS__);
            $order_state = 0;//待发货 //待入库
            if ($SHIPMENTS_NUMBER < $relevance['shipped_number']) {
                $order_state = 1;//部分发货 //部分入库
            }
            $save_relevance['ship_status'] = $order_state; //待发货
            $save_relevance['warehouse_status'] = $order_state; //待入库
            $save_relevance['shipped_number'] = $relevance['shipped_number'] - $SHIPMENTS_NUMBER; //发货数(扣除本次撤回数)
            $res = $M->table('tb_pur_relevance_order')->where($where)->save($save_relevance);
            $return['tb_pur_relevance_order'] = $res;
            /*if (!$res) {
                $return['msg'] = '采购单的出库、入库状态未修改-请确认虚拟仓发货记录是否已经撤回';
                $M->rollback();
                return $return;
            }*/

            //流水 先查流水表 通过 GSKU 查询 tb_b2b_goods
            //一次撤回、发货可能有多个sku 修改2019-07-11 sm
            $where = ['bill_id' => $wms_bill['id']];
            $wms_stream_all = $M->table('tb_wms_stream')->where($where)->select();
            Logs([$wms_stream_all,'tb_wms_stream'], __FUNCTION__, __CLASS__);
            //应扣金额
            $deducted_amount = 0;
            //--循环处理 流水表tb_wms_stream 存在多个sku记录
            $price_map = M('goods', 'tb_b2b_')->where(['ORDER_ID' => $b2b_ware_ship_list['order_id']])->getField('SKU_ID,price_goods');
            foreach ($wms_stream_all as $val) {
                //sku相关逻辑
                $GSKU = $val['GSKU'];
                $send_num = $val['send_num']; //实发数量
                $batch_id = $val['batch']; //获取批次
                //采购单商品详情
                $where_information['relevance_id'] = $relevance['relevance_id']; //根据Grelevance_id查询
                $where_information['sku_information'] = $GSKU; //根据GSKU查询
                $goods_information = $M->table('tb_pur_goods_information')->where($where_information)->find(); //多个商品详情
                Logs([$goods_information,'tb_pur_goods_information'], __FUNCTION__, __CLASS__);
                $save_information['shipped_number'] = $goods_information['shipped_number'] - $send_num; //已发货数量
                //$save_information['ship_end_number'] = 0; //
                $res = $M->table('tb_pur_goods_information')->where($where_information)->save($save_information);
                $return['tb_pur_goods_information'] = $res;
                /*if (!$res) {
                    $return['msg'] = '采购单商品详情未修改-请确认虚拟仓发货记录是否已经撤回';
                    $M->rollback();
                    return $return;
                }*/

                //删除记录 单据
                $ret = $this->editWmsBatchAndBatchOrder($procurement_number, $GSKU, $send_num);
                //反馈错误信息
                if ($ret['code'] != 0) {
                    $return['msg'] = $ret['msg'];
                    $M->rollback();
                    return $return;
                }
                //批次库存恢复
                $return['tb_wms_batch'] = $ret['tb_wms_batch'];
                //恢复在途库存
                $return['tb_wms_batch_order'] = $ret['tb_wms_batch_order'];

                //删除当前批次的出入库记录
                $where_stream['batch'] = $batch_id;
                $wms_stream = $M->table('tb_wms_stream')->where($where_stream)->select();
                Logs([$wms_stream,'tb_wms_stream'], __FUNCTION__, __CLASS__);
                $res = $M->table('tb_wms_stream')->where($where_stream)->delete();

                $where_b2b_goods['procurement_number'] = $procurement_number; //根据采购单号查询
                $where_b2b_goods['ORDER_ID'] = $b2b_order['ID']; //b2b商品列表
                $where_b2b_goods['SKU_ID'] = $GSKU; //根据GSKU查询
                //$where_b2b_goods['batch_id'] = $wms_batch['id']; //batch_id null
                //b2b商品列表
                $b2b_goods = $M->table('tb_b2b_goods')->where($where_b2b_goods)->find();
                Logs([$b2b_goods,'tb_b2b_goods'], __FUNCTION__, __CLASS__);
                if (!empty($b2b_goods)) {
                    //b2b商品列表数据调整
                    $save_b2b_goods['TOBE_DELIVERED_NUM'] = $b2b_goods['TOBE_DELIVERED_NUM'] + $send_num;
                    $save_b2b_goods['SHIPPED_NUM'] = $b2b_goods['SHIPPED_NUM'] - $send_num;
                    $res = $M->table('tb_b2b_goods')->where($where_b2b_goods)->save($save_b2b_goods);
                    $return['tb_b2b_goods'] = $res;
                    if (!$res) {
                        $return['msg'] = 'b2b商品列表未更新';
                        $M->rollback();
                        return $return;
                    }
                    if (empty($price_map[$val['GSKU']])) {
                        $return['msg'] = $val['GSKU'].'商品销售价格未找到';
                        $M->rollback();
                        return $return;
                    }
                    //应扣金额累计 （发货数量 * 含税单价）
                    $deducted_amount += $send_num * $price_map[$val['GSKU']];
                }
            }

            //--循环处理

            //删除记录 单据
            $ret = $this->b2bWmsBillAndB2bShipList($out_bill_id);
            //反馈错误信息
            if ($ret['code'] != 0) {
                $return['msg'] = $ret['msg'];
                $M->rollback();
                return $return;
            }
            $return['tb_wms_bill'] = $ret['tb_wms_bill'];
            //b2b发货信息
            $return['tb_b2b_ship_list'] = $ret['tb_b2b_ship_list'];

            //b2b发货商品信息
            $where = ['SHIP_ID' => $b2b_ware_ship_list['ID']];
            $b2b_ship_goods = $M->table('tb_b2b_ship_goods')->where($where)->select();
            Logs([$b2b_ship_goods,'tb_b2b_ship_goods'], __FUNCTION__, __CLASS__);
            $res = $M->table('tb_b2b_ship_goods')->where($where)->delete();
            $return['tb_b2b_ship_goods'] = $res;
            /*if (!$res) {
                $return['msg'] = 'b2b发货商品信息未删除';
                $M->rollback();
                return $return;
            }*/

            //b2b待发货
            //截取采购单id获得PO_ID
            $save_b2b_doship['sent_num'] = $b2b_doship['sent_num'] - $SHIPMENTS_NUMBER; //已发数量复原
            $save_b2b_doship['todo_sent_num'] = $b2b_doship['todo_sent_num'] + $SHIPMENTS_NUMBER; //待发货数量重置
            //撤回发货数量 < 总已发数量 发货状态调整:部分发货
            //撤回发货数量 = 总已发数量 发货状态调整:待（未）发货
            $shipping_status = $order_state + 1; // doshop 发货状态不一致 （发货状态(0全部，1待发，2部分,2 已发)）
            $save_b2b_doship['shipping_status'] = $shipping_status; //发货状态调整
            $res = $M->table('tb_b2b_doship')->where($where_doship)->save($save_b2b_doship);
            $return['tb_b2b_doship'] = $res;
            /*if (!$res) {
                $return['msg'] = 'b2b待发货未删除';
                $M->rollback();
                return $return;
            }*/

            //根据出库批次查询入库记录 获取入库单号
            $batch_ids = array_column($wms_stream_all, 'batch');
            $bill_in_nos = $this->getWarehouseCodeByBatchIds($batch_ids);

            //处理 发货商品信息&入库表&入库商品表
            $ret = $this->delPurShipGoodsAndWarehouse($bill_in_nos);
            //反馈错误信息
            if ($ret['code'] != 0) {
                $return['msg'] = $ret['msg'];
                $M->rollback();
                return $return;
            }
            //发货信息
            $return['tb_pur_ship'] = $ret['tb_pur_ship'];
            //发货商品信息
            $return['tb_pur_ship_goods'] = $ret['tb_pur_ship_goods'];
            //入库表
            $return['tb_pur_warehouse'] = $ret['tb_pur_warehouse'];
            //入库商品表
            $return['tb_pur_warehouse_goods'] = $ret['tb_pur_warehouse_goods'];

            //通过tb_b2b_doship b2b代发货 ORDER_ID 查询
            if (!empty($b2b_doship)) {
                //删除记录
                $ret = $this->delB2bWarehouseListAndGoods($b2b_doship['ORDER_ID'], $b2b_ware_ship_list['ID']);
                //反馈错误信息
                if ($ret['code'] != 0) {
                    $return['msg'] = $ret['msg'];
                    $M->rollback();
                    return $return;
                }
                //b2b客户入库单据
                $return['tb_b2b_warehouse_list'] = $ret['tb_b2b_warehouse_list'];
                //b2b入库商品
                $return['tb_b2b_warehousing_goods'] = $ret['tb_b2b_warehousing_goods'];

            }
            //更新b2b应付金额
            //$res = $this->updateOrderReceivableAccount($b2b_order['ID']);

            //tb_b2b_receivable 应收 应收状态
            $where = ['order_id' => $b2b_order['ID']];
            $b2b_receivable = $M->table('tb_b2b_receivable')->where($where)->find();
            Logs([$b2b_receivable,'tb_b2b_receivable'], __FUNCTION__, __CLASS__);
            $save_b2b_receivable['order_account'] = $b2b_receivable['order_account'] - $deducted_amount;
            $save_b2b_receivable['current_receivable'] = $b2b_receivable['current_receivable'] - $deducted_amount;
            $res = $M->table('tb_b2b_receivable')->where($where)->save($save_b2b_receivable);
            $return['tb_b2b_receivable'] = $res;

            //删除对应收入报表信息
            $wms_stream_ids = array_column($wms_stream_all, 'id');
            foreach ($wms_stream_ids as $val) {
                $res = ApiModel::deletIncomeDataById($out_bill_id, $val);
                if($res['code'] != 200) {
                    $return['income_data']['error'][] = '删除收入报表失败 bill_id: ' . $out_bill_id . 'stream_id: ' . $val . '-res:' . $res['msg'];
                } else {
                    $return['income_data']['success'][] = '删除收入报表成功 bill_id: ' . $out_bill_id . 'stream_id: ' . $val . '-res:' . $res['msg'];
                }
            }
            //删除入库批次
            $res_del = ApiModel::removeBtch($procurement_number);
            if($res_del['code'] != 2000) {
                @SentinelModel::addAbnormal('提示：B2B发货撤回', $procurement_number.'删除入库批次API返回异常', [$res_del, $procurement_number],'b2b_notice');
            }
            if ($type == 1) {
                B2bModel::addLog($b2b_order['ID'], 200, '虚拟仓发货撤回');
                D('TbPurActionLog')->addLog($relevance['relevance_id'], 'virtual_warehouse_ship_revoke');
                (new B2bReceivableService())->updateWholeOrder($b2b_order['ID']);
                $M->commit();
                $return['msg'] = '虚拟仓发货记录撤回成功';
            } else {
                $M->rollback();
                $return['msg'] = '请确认虚拟仓发货记录撤回涉及的表以及记录数';
            }
            $return['code'] = 0;
            return $return;
        } else {
            $return['msg'] = 'error';
            return $return;

        }
    }

    /**
     * @param integer $order_id
     * @param integer $ship_list_id
     *
     * @return array
     */
    private function b2bVirtualWarehouseRevokeCheck($order_id, $ship_list_id)
    {
        $return['code'] = 0;
        $return['msg'] = '';
        $M = M();
        //校验b2b客户入库单据的入库状态 【待提交】
        //tb_b2b_warehouse_list  入库状态
        $where = [
            'ORDER_ID' => $order_id,
            'SHIP_LIST_ID' => $ship_list_id,
        ];
        $b2b_warehousing_list = $M->table('tb_b2b_warehouse_list')->where($where)->find();
        if ($b2b_warehousing_list['status'] != 0) {
            $return['code'] = -1;
            $return['msg'] = 'B2B订单的应收状态必须是【待确认】';
            return $return;
        }

        //校验B2B订单的应收状态 【待提交】
        //tb_b2b_receivable 应收 应收状态
        $where = ['order_id' => $order_id];
        $b2b_receivable = $M->table('tb_b2b_receivable')->where($where)->find();
        if ($b2b_receivable['receivable_status'] != 'N002540100') {
            $return['code'] = -1;
            $return['msg'] = 'B2B订单的应收状态必须是【待提交】';
            return $return;
        }

        return false;
    }

    /**
     * @param integer $out_bill_id
     *
     * @return array
     */
    private function b2bWmsBillAndB2bShipList($out_bill_id)
    {
        $return = [
            'code' => -1,
            'msg' => '',
        ];
        $M = M();
        //删除记录 单据
        $where = ['bill_id' => $out_bill_id];
        $res = $M->table('tb_wms_bill')->where($where)->delete();
        $return['tb_wms_bill'] = $res;
        if (!$res) {
            $return['msg'] = '单据未删除-请确认虚拟仓发货记录是否已经撤回';
            return $return;
        }

        //b2b发货信息
        $where = ['out_bill_id' => $out_bill_id];
        $res = $M->table('tb_b2b_ship_list')->where($where)->delete();
        $return['tb_b2b_ship_list'] = $res;
        if (!$res) {
            $return['msg'] = 'b2b发货信息未删除';
            return $return;
        }

        $return['code'] = 0;
        $return['msg'] = '操作成功';
        return $return;
    }

    /**
     * @param array $bill_in_nos
     *
     * @return array
     */
    private function delPurShipGoodsAndWarehouse($bill_in_nos)
    {
        $return = [
            'code' => -1,
            'msg' => '',
        ];
        if (empty($bill_in_nos)) {
            $return['msg'] = '入库单号不存在';
            return $return;
        }
        $M = M();
        //入库表
        if (count($bill_in_nos) > 1) {
            $where_pur_warehouse = "warehouse_code in ('" . implode("','", $bill_in_nos) . "')";
        } else {
            $where_pur_warehouse['warehouse_code'] = implode(',', $bill_in_nos);
        }
        $pur_warehouse = $M->table('tb_pur_warehouse')->where($where_pur_warehouse)->find();
        Logs([$pur_warehouse,'tb_pur_warehouse'], __FUNCTION__, __CLASS__);
        $res = $M->table('tb_pur_warehouse')->where($where_pur_warehouse)->delete();
        $return['tb_pur_warehouse'] = $res;
        /*if (!$res) {
            $return['msg'] = '入库表记录未删除';
            return $return;
        }*/

        //仓储
        //发货信息 追加提单号筛选
        $where_pur_ship = ['id' => $pur_warehouse['ship_id']];
        $pur_ship = $M->table('tb_pur_ship')->where($where_pur_ship)->find();
        Logs([$pur_ship,'tb_pur_ship'], __FUNCTION__, __CLASS__);
        $res = $M->table('tb_pur_ship')->where($where_pur_ship)->delete();
        $return['tb_pur_ship'] = $res;
        /*if (!$res) {
            $return['msg'] = '发货信息未删除';
            $M->rollback();
            return $return;
        }*/

        //发货商品信息
        $where = ['ship_id' => $pur_warehouse['ship_id']];
        $pur_ship_goods = $M->table('tb_pur_ship_goods')->where($where)->select();
        Logs([$pur_ship_goods,'tb_pur_ship_goods'], __FUNCTION__, __CLASS__);
        $res = $M->table('tb_pur_ship_goods')->where($where)->delete();
        $return['tb_pur_ship_goods'] = $res;
        /*if (!$res) {
            $return['msg'] = '发货商品信息未删除';
            return $return;
        }*/

        //入库商品表
        $where = ['warehouse_id' => $pur_warehouse['id']];
        $pur_warehouse_goods = $M->table('tb_pur_warehouse_goods')->where($where)->select();
        Logs([$pur_warehouse_goods,'tb_pur_warehouse_goods'], __FUNCTION__, __CLASS__);
        $res = $M->table('tb_pur_warehouse_goods')->where($where)->delete();
        $return['tb_pur_warehouse_goods'] = $res;
        /*if (!$res) {
            $return['msg'] = '入库商品表记录未删除';
            return $return;
        }*/

        $return['code'] = 0;
        $return['msg'] = '操作成功';
        return $return;
    }

    /**
     * @param array $batch_ids
     *
     * @return array
     */
    private function getWarehouseCodeByBatchIds($batch_ids)
    {
        $M = M();
        $where_batch = "tb_wms_batch.id in (" . implode(',', $batch_ids) . ")";

        $data = $M->table('tb_wms_batch')
            ->field('wb.bill_id')
            ->join('LEFT JOIN tb_wms_bill AS wb ON wb.id = tb_wms_batch.bill_id')
            ->where($where_batch)->select();
        //获取入库单号
        $bill_in_nos = array_column($data, 'bill_id');
        return $bill_in_nos;
    }

    /**
     * @param integer $procurement_number
     * @param integer $sku
     * @param integer $send_num
     *
     * @return array
     */
    private function editWmsBatchAndBatchOrder($procurement_number, $sku, $send_num)
    {
        $return = [
            'code' => -1,
            'msg' => '',
        ];
        $M = M();
        //$where_batch['bill_id'] = $wms_bill['id']; //tb_wms_batch tb_wms_bill bill_id 不一致
        $where_batch['purchase_order_no'] = $procurement_number; //根据采购单号查询
        $where_batch['SKU_ID'] = $sku; //根据GSKU查询
        $where_batch['vir_type'] = 'N002440200'; //在途库存
        //批次库存恢复
        $wms_batch = $M->table('tb_wms_batch')->where($where_batch)->find();
        Logs([$wms_batch,'tb_wms_batch'], __FUNCTION__, __CLASS__);
        $return['tb_wms_batch'] = 0;
        if (!empty($wms_batch)) {
            $save_batch['total_inventory'] = $wms_batch['total_inventory'] + $send_num; //在途数据调整
            $save_batch['occupied'] = $wms_batch['occupied'] + $send_num; //占用恢复
            $res = $M->table('tb_wms_batch')->where($where_batch)->save($save_batch);
            $return['tb_wms_batch'] = $res;
            if (false === $res) {
                $return['msg'] = '入库表记录未删除';
                @SentinelModel::addAbnormal('B2B 撤回虚拟仓更新批次异常',  $procurement_number. 'B2B 撤回虚拟仓更新批次异常', $res, 'b2b_notice');
                 return $return;
            }
            /*if (!$res) {
                $return['msg'] = '批次未更新';
                return $return;
            }*/
        }

        //恢复在途库存 tb_wms_batch_order occupy_num 占据数量
        $where_batch_order['batch_id'] = $wms_batch['id'];
        $batch_order = $M->table('tb_wms_batch_order')->where($where_batch_order)->find();
        Logs([$batch_order,'tb_wms_batch_order'], __FUNCTION__, __CLASS__);
        if (!empty($batch_order)) {
            //正常情况下撤回
            $save_batch_order['occupy_num'] = $batch_order['occupy_num'] + $send_num; //在途占据数量恢复
        } else {
            $po_id = explode('-', $procurement_number)[0];
            //全部发货 在途占用会被删除 重新生成
            //与石鹤讨论将完全出库的删除batch_order ORD_ID 改为ordid . "_d_" . batchId, use_type 改为2
            //撤回时执行相反操作
            $where_batch_order = [];
            $where_batch_order['ORD_ID'] = $po_id . '_d_' . $wms_batch['id'];
            $where_batch_order['use_type'] = 2;
            $dl_batch_order = $M->table('tb_wms_batch_order')->where($where_batch_order)->find();
            Logs([$dl_batch_order,'dl_tb_wms_batch_order'], __FUNCTION__, __CLASS__);
            /*if (empty($dl_batch_order)) {
                $return['msg'] = '删除后的在途库存占据记录不存在';
                return $return;
            }*/
            $save_batch_order['ORD_ID'] = $po_id;
            $save_batch_order['batch_id'] = $wms_batch['id'];
            $save_batch_order['use_type'] = 1;
        }
        $res = $M->table('tb_wms_batch_order')->where($where_batch_order)->save($save_batch_order);
        $return['tb_wms_batch_order'] = $res;
        /*if (!$res) {
            $return['msg'] = '在途库存占据数量未恢复';
            return $return;
        }*/

        $return['code'] = 0;
        $return['msg'] = '操作成功';
        return $return;
    }

    /**
     * @param integer $order_id
     * @param integer $ship_list_id
     *
     * @return array
     */
    private function delB2bWarehouseListAndGoods($order_id, $ship_list_id)
    {
        $return = [
            'code' => -1,
            'msg' => '',
        ];
        $M = M();
        //b2b客户入库单据
        $where = [
            'ORDER_ID' => $order_id,
            'SHIP_LIST_ID' => $ship_list_id,
        ];
        $b2b_warehousing_list = $M->table('tb_b2b_warehouse_list')->where($where)->find();
        Logs([$b2b_warehousing_list,'tb_b2b_warehouse_list'], __FUNCTION__, __CLASS__);
        $res = $M->table('tb_b2b_warehouse_list')->where($where)->delete();
        $return['tb_b2b_warehouse_list'] = $res;
        /*if (!$res) {
            $return['msg'] = 'b2b客户入库单据记录未删除';
            return $return;
        }*/

        //b2b入库商品
        $where = ['warehousing_id' => $b2b_warehousing_list['ID']];
        $b2b_warehousing_goods = $M->table('tb_b2b_warehousing_goods')->where($where)->select();
        Logs([$b2b_warehousing_goods,'tb_b2b_warehousing_goods'], __FUNCTION__, __CLASS__);
        $res = $M->table('tb_b2b_warehousing_goods')->where($where)->delete();
        $return['tb_b2b_warehousing_goods'] = $res;
        /*if (!$res) {
            $return['msg'] = 'b2b入库商品记录未删除';
            return $return;
        }*/

        $return['code'] = 0;
        $return['msg'] = '操作成功';
        return $return;
    }

    /**
     * @param array $order_ids
     *
     * @return array
     */
    private function sumActualCollection(array $order_ids)
    {
        $where_receipt['tb_b2b_receipt.ORDER_ID'] = $where['tb_fin_claim.order_id'] = ['IN', $order_ids];
        $where['tb_fin_claim.order_type'] = 'N001950200';
        $where_string = 'tb_fin_account_turnover.id = tb_fin_claim.account_turnover_id
         AND tb_fin_claim.order_id = tb_b2b_info.ORDER_ID';
        $res = $this->model->table('tb_fin_claim,tb_fin_account_turnover,tb_b2b_info')
            ->field('tb_fin_claim.order_id,
            tb_fin_account_turnover.original_currency,
            tb_b2b_info.po_currency,
            tb_b2b_info.po_time,
            SUM(tb_fin_claim.claim_amount) AS sum_claim_amount')
            ->where($where)
            ->where($where_string, null, true)
            ->group('tb_fin_account_turnover.id,tb_fin_claim.order_id')
            ->select();
        $where_receipt_string = 'receipt_operation_status = 1';
        $res_receipt = $this->model->table('tb_b2b_receipt')
            ->field('ORDER_ID,SUM(actual_payment_amount) AS sum_actual_payment_amount')
            ->where($where_receipt)
            ->where($where_receipt_string, null, true)
            ->group('ORDER_ID')
            ->select();
        $res_receipt_columns = array_column($res_receipt, 'sum_actual_payment_amount', 'ORDER_ID');
        $res_return = [];
        foreach ($res as &$re) {
            $re['sum_claim_amount'] = $re['sum_claim_amount'] * ExchangeRateModel::conversion(
                    $re['original_currency'],
                    $re['po_currency'],
                    $re['po_time']
                );
            if (!$res_return[$re['order_id']]) {
                $res_return[$re['order_id']] = 0;
            }
            $res_return[$re['order_id']] += $re['sum_claim_amount'];
        }
        unset($re);
        foreach ($res_receipt_columns as $key => $value) {
            $res_return[$key] += $value;
        }
        return $res_return;
    }

    /**
     * @param $order_ids
     * @param null $order_account
     * @param null $actual_collection
     * @param int $end_history_deduction
     *
     * @return array
     */
    public function currentReceivable($order_ids, $order_account = null, $actual_collection = null, $end_history_deduction = 0)
    {
        $where['tb_b2b_receivable.order_id'] = ['IN', $order_ids];
        $res = $this->model->table('tb_b2b_receivable')
            ->field('tb_b2b_receivable.order_id,tb_b2b_receivable.order_account,
            tb_b2b_receivable.actual_collection,
            tb_b2b_receivable.rate_losses,tb_b2b_receivable.sum_deduction_amount')
            ->where($where)
            ->select();
        $sum_deduction_amount = $this->sumDeductionAmount($order_ids);
        foreach ($res as &$re) {
            if ($order_account !== null || $actual_collection !== null || $end_history_deduction) {
                $re['current_receivable'] = $order_account - (double)$actual_collection - (double)$sum_deduction_amount[$re['order_id']] - (double)$re['rate_losses'] - (double)$end_history_deduction;
            } else {
                $re['current_receivable'] = $re['order_account'] - (double)$re['actual_collection'] - (double)$sum_deduction_amount[$re['order_id']] - (double)$re['rate_losses'];
            }
            Logs([
                $re['order_id'],
                $order_account,
                $actual_collection,
                $sum_deduction_amount[$re['order_id']],
                $re['rate_losses'],
                $end_history_deduction,
            ], __FUNCTION__, __CLASS__);
        }
        $res_return = array_column($res, 'current_receivable', 'order_id');
        return $res_return;
    }

    /**
     * @param $order_ids
     *
     * @return array
     */
    private function sumDeductionAmount($order_ids)
    {
        $where['tb_fin_claim.order_id'] = ['IN', $order_ids];
        $where['tb_fin_claim.order_type'] = 'N001950200';
        $where_string = 'tb_fin_account_turnover.id = tb_fin_claim.account_turnover_id
         AND tb_fin_claim.order_id = tb_b2b_info.ORDER_ID
         AND tb_b2b_claim_deduction.claim_id = tb_fin_claim.id';
        $res = $this->model->table('(tb_fin_claim,tb_fin_account_turnover,tb_b2b_claim_deduction,tb_b2b_info)')
            ->field('tb_fin_account_turnover.id,
            tb_fin_claim.order_id,
            tb_fin_account_turnover.original_currency,
            tb_b2b_info.po_currency,
            tb_b2b_info.po_time,
            SUM(tb_b2b_claim_deduction.deduction_amount) AS sum_deduction_amount')
            ->where($where)
            ->where($where_string, null, true)
            ->group('tb_fin_account_turnover.id,tb_fin_claim.order_id')
            ->select();
        $res_return = [];
        foreach ($res as &$re) {
            $re['sum_deduction_amount'] = $re['sum_deduction_amount'] * 1;
            if (!$res_return[$re['order_id']]) {
                $res_return[$re['order_id']] = 0;
            }
            $res_return[$re['order_id']] += $re['sum_deduction_amount'];
        }
        return $res_return;

    }

    /**
     * @param $order_id
     * @param bool $is_arr
     *
     * @return mixed
     */
    public function getAppendClaims($order_id, $is_arr = false)
    {
        if ($is_arr) {
            $where['tb_b2b_info.order_id'] = ['IN', $order_id];
        } else {
            $where['tb_b2b_info.order_id'] = $order_id;
        }
        $where_string = 'tb_b2b_info.order_id = tb_b2b_receipt.order_id
         AND tb_b2b_receipt.receipt_operation_status = 1';
        $data_db = $this->model->table('(tb_b2b_receipt,tb_b2b_info)')
            ->field('
              tb_b2b_info.PO_ID,
              tb_b2b_info.po_time,
              tb_b2b_info.ORDER_ID,
            tb_b2b_info.THR_PO_ID,          
            tb_b2b_info.po_currency,
            tb_b2b_receipt.actual_receipt_date,
            tb_b2b_receipt.updated_at,
            tb_b2b_receipt.operator_id,
            tb_b2b_receipt.actual_payment_amount,
            tb_b2b_receipt.company_our
            ')
            ->where($where)
            ->where($where_string, null, true)
            ->select();
        return $data_db;
    }

    /**
     * @param $order_id
     * @param bool $is_arr
     *
     * @return mixed
     */
    public function getAppendClaimsDeductions($order_id, $is_arr = false)
    {
        if ($is_arr) {
            $where['tb_b2b_info.order_id'] = ['IN', $order_id];
        } else {
            $where['tb_b2b_info.order_id'] = $order_id;
        }
        $where['t3.main_receipt_operation_status'] = 1;
        $where_string = 'tb_b2b_info.order_id = tb_b2b_receipt.order_id
        AND tb_b2b_order.id = tb_b2b_receipt.order_id 
        AND tb_b2b_order.receipt_state = 2';
        $data_db = $this->model->table('(tb_b2b_receipt,tb_b2b_info,tb_b2b_order)')
            ->field('tb_b2b_receipt.expect_receipt_amount,
            tb_b2b_info.PO_ID,
            tb_b2b_info.ORDER_ID,
            tb_b2b_info.THR_PO_ID,
            tb_b2b_info.po_currency,
            SUM(tb_b2b_receipt.actual_payment_amount) AS actual_payment_amount,
            tb_b2b_receipt.estimated_amount - SUM(tb_b2b_receipt.actual_payment_amount) AS deductible_receivable_amount, 
            t2.DEVIATION_REASON,
            t2.file_path,
            t2.file_name,
            tb_b2b_info.po_currency')
            ->join('LEFT JOIN tb_b2b_receipt AS t2 ON tb_b2b_receipt.order_id = t2.order_id AND t2.DEVIATION_REASON IS NOT NULL')
            ->join('LEFT JOIN tb_b2b_receipt AS t3 ON tb_b2b_receipt.order_id = t3.order_id AND t3.P_ID IS NULL')
            ->where($where)
            ->where($where_string, null, true)
            ->group('tb_b2b_receipt.PO_ID,tb_b2b_receipt.receiving_code')
            ->select();
        return $data_db;
    }

    /**
     * @return mixed
     */
    public function syncReceiptToReceivable()
    {
        $sql = 'SELECT
                    tb_b2b_order.ID AS order_id,                
                IF (
                    tb_b2b_order.receipt_state = 2,
                    \'N002540300\',
                    \'N002540100\'
                ) AS receivable_status,
                 tb_b2b_order.create_time AS created_at,
                 tb_b2b_order.create_user AS created_by,
                 tb_b2b_receipt_2.operator_id AS verification_by,
                 tb_b2b_receipt_2.create_time AS verification_at,
                 \'-\' AS submit_by
                FROM
                    tb_b2b_order
                LEFT JOIN (
                    SELECT
                        MAX(ID) AS MAX_ID,
                        ORDER_ID
                    FROM
                        tb_b2b_receipt
                    GROUP BY
                        ORDER_ID
                ) AS tb_b2b_receipt_1 ON tb_b2b_order.ID = tb_b2b_receipt_1.ORDER_ID
                LEFT JOIN tb_b2b_receipt AS tb_b2b_receipt_2 ON tb_b2b_receipt_1.MAX_ID = tb_b2b_receipt_2.ID
                WHERE
                    tb_b2b_order.ID NOT IN (
                        SELECT
                            order_id
                        FROM
                            tb_b2b_receivable
                    )';
        $db_orders = $this->model->query($sql);
        return $this->model->table('tb_b2b_receivable')
            ->addAll($db_orders);
    }

    /**
     * @param $ids
     *
     * @return mixed
     */
    public function getExcelShipInfo($ids)
    {
        $where['tb_b2b_order.ID'] = ['IN', $ids];
        $where_string = "tb_b2b_order.ID = tb_b2b_info.ORDER_ID
        AND tb_b2b_order.ID = tb_b2b_warehouse_list.ORDER_ID 
        AND tb_b2b_ship_list.ID = tb_b2b_warehouse_list.SHIP_LIST_ID
        AND tb_b2b_warehouse_list.ID = tb_b2b_warehousing_goods.warehousing_id
        AND tb_b2b_warehousing_goods.goods_id = tb_b2b_goods.ID        
        ";
        return $this->model->table('(tb_b2b_order,tb_b2b_info,tb_b2b_goods,tb_b2b_ship_list,tb_b2b_warehouse_list,tb_b2b_warehousing_goods)')
            ->field('tb_b2b_info.PO_ID,
            tb_b2b_info.THR_PO_ID,
            tb_b2b_info.ORDER_ID,
            tb_b2b_info.po_currency,
            tb_b2b_ship_list.ID AS ship_list_id,
            tb_b2b_ship_list.DOSHIP_ID,
            tb_b2b_ship_list.BILL_LADING_CODE,
            tb_b2b_ship_list.DELIVERY_TIME,
            tb_b2b_warehouse_list.status AS warehouse_list_status,
            tb_b2b_warehouse_list.AUTHOR,
            tb_b2b_warehouse_list.SUBMIT_TIME,
            tb_b2b_warehouse_list.WAREING_DATE,
            IF(tb_b2b_warehouse_list.STATUS = 2,SUM((tb_b2b_warehousing_goods.TOBE_WAREHOUSING_NUM - tb_b2b_warehousing_goods.DELIVERED_NUM) * tb_b2b_goods.price_goods),0) AS sum_warehousing,
            IF(tb_b2b_warehouse_list.STATUS = 2,FORMAT(SUM(((tb_b2b_warehousing_goods.TOBE_WAREHOUSING_NUM - tb_b2b_warehousing_goods.DELIVERED_NUM) * tb_b2b_goods.price_goods)/(1 + (IF(tb_ms_cmn_cd.CD_VAL, replace(tb_ms_cmn_cd.CD_VAL, \'%\', \'\'), 0) / 100))),2),0) AS sum_warehousing_no_tax
            ')
            ->join('left join tb_ms_cmn_cd ON tb_ms_cmn_cd.CD = tb_b2b_goods.purchase_invoice_tax_rate')
            ->where($where)
            ->where($where_string, null, true)
            ->group('tb_b2b_ship_list.ID')
            ->select();
    }

    /**
     * @param $ids
     *
     * @return mixed
     */
    public function getExcelReceiptInfo($ids)
    {
        $where['tb_b2b_order.ID'] = ['IN', $ids];
        $where_string = "tb_b2b_order.ID = tb_b2b_info.ORDER_ID
        AND tb_fin_claim.ORDER_ID = tb_b2b_order.ID
        AND tb_fin_claim.order_type = 'N001950200'
        AND tb_fin_account_turnover.id = tb_fin_claim.account_turnover_id
        ";
        return $this->model->table('(tb_b2b_order,tb_b2b_info,tb_fin_claim,tb_fin_account_turnover)')
            ->field('tb_b2b_info.PO_ID,
            tb_b2b_info.THR_PO_ID,
            tb_b2b_info.ORDER_ID,
            tb_b2b_info.po_time,
            tb_b2b_info.po_currency,
            tb_fin_account_turnover.account_transfer_no,
            tb_fin_account_turnover.original_currency AS currency_code,
            tb_fin_claim.claim_amount,
            tb_fin_account_turnover.transfer_time,
            tb_fin_claim.created_by,
            tb_fin_claim.created_at,
            tb_fin_account_turnover.opp_company_name,
            tb_fin_account_turnover.company_code,
            tb_fin_account_turnover.swift_code,
            tb_fin_account_turnover.account_bank
            ')
            ->where($where)
            ->where($where_string, null, true)
            ->select();
    }

    /**
     * @param $ids
     *
     * @return mixed
     */
    public function getExcelDeductionInfo($ids)
    {
        $where['tb_b2b_order.ID'] = ['IN', $ids];
        $where_string = "tb_b2b_order.ID = tb_b2b_info.ORDER_ID
        AND tb_fin_claim.order_id = tb_b2b_order.id
        AND tb_b2b_claim_deduction.claim_id = tb_fin_claim.id";
        return $this->model->table('(tb_b2b_order,tb_b2b_info,tb_fin_claim,tb_b2b_claim_deduction)')
            ->field('tb_b2b_info.PO_ID,
            tb_b2b_info.THR_PO_ID,
            tb_b2b_info.ORDER_ID,
            tb_b2b_info.po_currency,
            tb_b2b_claim_deduction.deduction_type,
            tb_b2b_claim_deduction.instructions,
            tb_b2b_claim_deduction.deduction_amount,
            tb_b2b_claim_deduction.updated_by,
            tb_b2b_claim_deduction.updated_at
            ')
            ->where($where)
            ->where($where_string, null, true)
            ->select();
    }

    /**
     * @param $order_id
     *
     * @return mixed
     */
    public function getGoodsBatch($order_id)
    {
        $where['ORDER_ID'] = $order_id;
        return $this->model->table('tb_b2b_goods')
            ->field('batch_id,procurement_number')
            ->where($where)
            ->select();
    }

    /**
     * @param $order_id
     *
     * @return mixed
     */
    public function getOccupyBatch($order_id)
    {
        $where['ORD_ID'] = $this->orderIdToPOId($order_id);
        return $this->model->table('tb_wms_batch_order')
            ->field('batch_id')
            ->where($where)
            ->select();

    }

    /**
     * @param $order_id
     *
     * @return mixed
     */
    private function orderIdToPOId($order_id)
    {
        $where['ID'] = $order_id;
        return $this->model->table('tb_b2b_order')
            ->where($where)
            ->getField('PO_ID');
    }

    /**
     * @param $batch_ids
     *
     * @return mixed
     */
    public function getPurchase(array $batch_ids)
    {
        $where['id'] = ['IN', array_column($this->getBillId($batch_ids), 'bill_id')];
        $where['procurement_number'] = ['EXP', 'IS NOT NULL'];
        return $this->model->table('tb_wms_bill')
            ->field('procurement_number')
            ->where($where)
            ->select();
    }

    /**
     * @param $batch_ids
     *
     * @return mixed
     */
    private function getBillId(array $batch_ids)
    {
        $where['id'] = ['IN', $batch_ids];
        return $this->model->table('tb_wms_batch')
            ->field('bill_id')
            ->where($where)
            ->select();
    }

    /**
     * @param array $online_purchase_order_numbers
     *
     * @return array|mixed
     */
    public function getPurchaseData($procurement_number)
    {
        $sql = "SELECT
                    t11.*, t12.*, IFNULL(
                        SUM(
                            t11.order_number - (
                                t12.qualified_product_number + t12.defective_product_number
                            )
                        ),
                        0
                    ) AS diff_number
                FROM
                    (
                        SELECT
                            t.relevance_id,
                            t.prepared_time,
                            a.procurement_number AS purchase_order_no,
                            a.online_purchase_order_number AS purchase_po_no,
                            a.payment_company AS purchasing_team,
                            prepared_by AS procurement_staff,
                            `payment_status` AS coping_status,
                            `ship_status` AS send_status,
                            t.warehouse_status AS inbound_status,
                            t.relevance_id AS order_id,
                            `order_status` AS order_status,
                            `invoice_status`,
                            SUM(c.goods_number) AS order_number,
                            SUM(c.shipped_number) AS send_number
                        FROM
                            tb_pur_relevance_order t
                        LEFT JOIN tb_pur_order_detail a ON a.order_id = t.order_id
                        LEFT JOIN tb_pur_sell_information b ON b.sell_id = t.sell_id
                        LEFT JOIN tb_pur_goods_information c ON c.relevance_id = t.relevance_id
                        WHERE
                            (
                                a.procurement_number IN ({$procurement_number})
                            )
                        GROUP BY
                            t.relevance_id
                    ) AS t11,
                    (
                        SELECT
                            t.relevance_id,
                            IFNULL(
                                SUM(
                                    tb_pur_ship_goods.warehouse_number
                                ),
                                0
                            ) AS qualified_product_number,
                            IFNULL(
                                SUM(
                                    tb_pur_ship_goods.warehouse_number_broken
                                ),
                                0
                            ) AS defective_product_number
                        FROM
                            tb_pur_relevance_order t
                        LEFT JOIN tb_pur_order_detail a ON a.order_id = t.order_id
                        LEFT JOIN tb_pur_ship ON tb_pur_ship.relevance_id = t.relevance_id
                        LEFT JOIN tb_pur_ship_goods ON tb_pur_ship_goods.ship_id = tb_pur_ship.id
                        WHERE
                            (
                                a.procurement_number IN ({$procurement_number})
                            )
                        GROUP BY
                            t.relevance_id
                    ) AS t12
                WHERE
                    t11.relevance_id = t12.relevance_id
                GROUP BY
                    t11.relevance_id
                ORDER BY
                    t11.prepared_time DESC";
        return $this->model->query($sql);
    }

    public function getEstimatedProfit($order_id)
    {
        $where['tb_b2b_goods.order_id'] = $order_id;
        $xchr_currency = StringModel::getXchrCurrency('cd_1.CD_VAL');
        $xchr_purchasing_currency = StringModel::getXchrCurrency('cd_2.CD_VAL');
        return $this->model->table('(tb_b2b_goods,tb_b2b_info)')
            ->field("
                    IFNULL(SUM(( price_goods / (1 + (IF(cd_4.CD_VAL, replace(cd_4.CD_VAL, '%', ''), 0) / 100))) * required_quantity*({$xchr_currency})),0) AS estimated_income,
                    IFNULL(SUM(( purchasing_price / (1 + (IF(cd_3.CD_VAL, replace(cd_3.CD_VAL, '%', ''), 0) / 100))) * required_quantity*({$xchr_purchasing_currency})),0) AS estimated_cost
                ")
            ->join('left join tb_ms_xchr on tb_ms_xchr.XCHR_STD_DT = DATE_FORMAT(tb_b2b_info.po_time,\'%Y%m%d\')')
            ->join('left join tb_ms_cmn_cd AS cd_1 on cd_1.CD = tb_b2b_goods.currency')
            ->join('left join tb_ms_cmn_cd AS cd_2 on cd_2.CD = tb_b2b_goods.purchasing_currency')
            ->join('left join tb_ms_cmn_cd AS cd_3 on cd_3.CD = tb_b2b_goods.purchase_invoice_tax_rate')
            ->join('left join tb_ms_cmn_cd AS cd_4 on cd_4.CD = tb_b2b_info.TAX_POINT')
            ->where($where)
            ->where('tb_b2b_goods.order_id = tb_b2b_info.order_id', null, true)
            ->group('tb_b2b_goods.order_id')
            ->find();
    }

    public function getReceivableInformation($order_id)
    {
        $where['order_id'] = $order_id;
        return TbB2BReceivable::where($where)
            ->first();
    }

    public function getB2bStatus($order_id)
    {
        $where['order_id'] = $order_id;
        return $this->model->table('tb_b2b_order,tb_b2b_receivable')
            ->field('tb_b2b_order.order_state,
            tb_b2b_order.warehousing_state,
            tb_b2b_receivable.receivable_status,
            tb_b2b_order.return_status_cd')
            ->where($where)
            ->where('tb_b2b_order.id = tb_b2b_receivable.order_id', null, true)
            ->find();
    }

    public function getInvoicingTax($order_id)
    {
        $where['tb_b2b_goods.order_id'] = $order_id;
        $xchr_currency = StringModel::getXchrCurrency('cd_3.CD_VAL');
        $xchr_purchasing_currency = StringModel::getXchrCurrency('cd_4.CD_VAL');
        $db_res = $this->model->table('(tb_b2b_goods,tb_b2b_info)')
            ->field("
            IFNULL(SUM((price_goods - price_goods / (1 + (IF(cd_1.CD_VAL, replace(cd_1.CD_VAL, '%', ''), 0) / 100))) * required_quantity*({$xchr_currency})),0) AS output_tax_cny,
            IFNULL(SUM((purchasing_price - purchasing_price / (1 + (IF(cd_2.CD_VAL, replace(cd_2.CD_VAL, '%', ''), 0) / 100))) * required_quantity*({$xchr_purchasing_currency})),0) AS input_tax_cny
            ")
            ->join('left join tb_ms_xchr on tb_ms_xchr.XCHR_STD_DT = DATE_FORMAT(tb_b2b_info.po_time,\'%Y%m%d\')')
            ->join('left join tb_ms_cmn_cd AS cd_1 on cd_1.CD = tb_b2b_info.TAX_POINT')
            ->join('left join tb_ms_cmn_cd AS cd_2 on cd_2.CD = tb_b2b_goods.purchase_invoice_tax_rate')
            ->join('left join tb_ms_cmn_cd AS cd_3 on cd_3.CD = tb_b2b_goods.currency')
            ->join('left join tb_ms_cmn_cd AS cd_4 on cd_4.CD = tb_b2b_goods.purchasing_currency')
            ->where($where)
            ->where('tb_b2b_info.ORDER_ID =  tb_b2b_goods.order_id', null, true)
            ->group('tb_b2b_goods.order_id')
            ->find();
        return [$db_res['output_tax_cny'], $db_res['input_tax_cny']];
    }

    public function getSendNetWarehouseCds()
    {
        $db_res = TbWmsWarehouseChild::where('instance_of', 'FINE_EX_WAREHOUSE')
            ->get(['cd']);
        if ($db_res) {
            $db_res = $db_res->toArray();
        }
        return $db_res;
    }

    public function withdrawEndShipment($order_id)
    {
        $this->model->startTrans();
        $update_sql = "UPDATE tb_b2b_order,
                         tb_b2b_doship,
                         tb_b2b_goods
                        SET tb_b2b_order.order_state = 1,
                         tb_b2b_doship.shipping_status = 2,
                         tb_b2b_doship.todo_sent_num =  tb_b2b_doship.order_num -  tb_b2b_doship.sent_num,
                         tb_b2b_goods.TOBE_DELIVERED_NUM = tb_b2b_goods.required_quantity - tb_b2b_goods.SHIPPED_NUM 
                        WHERE
                          tb_b2b_order.ID = '{$order_id}'
                        AND tb_b2b_order.ID = tb_b2b_doship.ORDER_ID
                        AND tb_b2b_order.ID = tb_b2b_goods.ORDER_ID
                        AND tb_b2b_doship.shipping_status = 3";
        $user = DataModel::userNamePinyin();
        $add_log_sql = "INSERT INTO `tb_b2b_log` (`ORDER_ID`, `STATE`, `USER_ID`, `COUNT`) VALUES ('{$order_id}', '200', '{$user}', '撤回发货完结')";
        $update_res = $this->model->execute($update_sql);
        if (false === $update_res) {
            throw new Exception(L('更新失败'));
        }
        $add_log_res = $this->model->execute($add_log_sql);
        if ($update_res > 0 && false === $add_log_res) {
            $this->model->rollback();
            throw new Exception(L('插入日志失败'));
        }
        $this->model->commit();
        return $update_res;
    }


    /**
     * @param $wheres
     *
     * @return mixed
     */
    public function getGPOrderSelect($where)
    {
        $res = $this->model->table('tb_op_order')->alias('a')
            ->field('a.PLAT_CD as plat_cd,a.ORDER_PAY_TIME as po_time,a.ORDER_NO as order_no,a.PAY_CURRENCY as pay_currency,a.PAY_TOTAL_PRICE as pay_total_price,a.ORDER_ID as order_id,b.STORE_NAME as store_name')
            ->join("LEFT JOIN tb_ms_store b ON a.STORE_ID = b.ID")
            ->where($where)
            ->limit()
            ->select();
        return $res;
    }
}
