<?php
/**
 * User: yangsu
 * Date: 19/6/17
 * Time: 17:38
 */

@import("@.Model.StringModel");

use Application\Lib\Model\StringModel;

class AllocationExtendNewRepository extends Repository
{
    static $warehouse = ['N000689453','N000689494','N000685900','N000689496'];


    /**
     * @param $allo_id
     *
     * @return mixed
     */
    public function getAlloStatus($allo_id)
    {
        return $this->model->table('tb_wms_allo')
            ->where("id = '{$allo_id}'")
            ->getField('state');
    }

    /**
     * @param $allo_id
     * @param $type
     *
     * @return mixed
     */
    public function getStockStatus($allo_id, $type)
    {
        return $this->model->table('tb_wms_allo_new_status')
            ->where("allo_id = '{$allo_id}'")
            ->getField("allo_{$type}_status");
    }

    /**
     * @param $allo_id
     *
     * @return mixed
     */
    public function getAlloNo($allo_id)
    {
        return $this->model->table('tb_wms_allo')
            ->where("id = '{$allo_id}'")
            ->getField('allo_no');
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getAlloInfo($id)
    {
        $where['tb_wms_allo.id'] = $id;
        $field = [
            'tb_wms_allo.allo_no',
            'tb_wms_allo.state',
            'tb_wms_allo.allo_in_team',
            'tb_wms_allo.allo_out_team',
            "tb_wms_allo.allo_out_warehouse",
            "tb_wms_allo.allo_in_warehouse",
            "tb_wms_allo.allo_out_warehouse AS allo_out_warehouse_cd",
            "tb_wms_allo.allo_in_warehouse AS allo_in_warehouse_cd",
            "CONCAT_WS('-',w_out.place,w_out.address) AS originating_warehouse_location",
            "CONCAT_WS('-',w_in.place,w_in.address) AS destination_warehouse_location",
            "tb_wms_allo.transfer_use_type",
            "REPLACE(cd_1.ETC,'@gshopper.com','')AS reviewer_by",
            "dw_out.task_launch_by",
            "dw_out.transfer_out_library_by",
            "dw_in.transfer_warehousing_by",
            "tb_wms_allo.use_fawang_logistics",

            "tb_wms_allo.expected_delivery_date",
            "tb_wms_allo.expected_warehousing_date",
            "tb_wms_allo.planned_transportation_channel_cd",
            "bbm_admin.M_NAME AS create_user",
            "tb_wms_allo.create_time",

            "tb_wms_allo_new_status.allo_out_status",
            "tb_wms_allo_new_status.allo_in_status",
            "tb_wms_allo_new_status.out_reason_difference",
            "tb_wms_allo_new_status.in_reason_difference",
            "tb_wms_allo_new_status.out_mark_by",
            "tb_wms_allo_new_status.out_mark_at",
            "tb_wms_allo_new_status.in_mark_by",
            "tb_wms_allo_new_status.in_mark_at",
            "tb_wms_allo.is_cn_warehouse",
            "tb_wms_allo.quote_no",
            "tb_wms_allo.process_id",
            "tws_process.small_team_cd"
        ];
        return $this->model->table('(tb_wms_allo,tb_wms_allo_new_status)')
            ->field($field)
            ->join('LEFT JOIN tb_con_division_warehouse AS dw_out on dw_out.warehouse_cd = tb_wms_allo.allo_out_warehouse')
            ->join('LEFT JOIN tb_con_division_warehouse AS dw_in on dw_in.warehouse_cd = tb_wms_allo.allo_in_warehouse')
            ->join('LEFT JOIN tb_wms_warehouse AS w_out on w_out.CD = tb_wms_allo.allo_out_warehouse')
            ->join('LEFT JOIN tb_wms_warehouse AS w_in on w_in.CD = tb_wms_allo.allo_in_warehouse')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_1 on cd_1.CD = tb_wms_allo.allo_in_team')
            ->join('LEFT JOIN bbm_admin ON bbm_admin.M_ID = tb_wms_allo.create_user')
            ->join('LEFT JOIN tb_wms_allo_process  as  tws_process ON tws_process.id = tb_wms_allo.process_id')
            ->where($where)
            ->whereString('tb_wms_allo.id = tb_wms_allo_new_status.allo_id')
            ->find();
    }

    public function getAlloGoods($allo_id)
    {
        $xchr_currency_cd_1 = StringModel::getXchrCurrency('cd_1.CD_VAL');
        $sql = "SELECT
                    tb_wms_allo_new_guds_infos.*,
                      (tb_wms_allo_new_guds_infos.tax_free_sales_unit_price *  {$xchr_currency_cd_1} * sum_allo_child.demand_allo_num) as total_sales_sku,
                     sum_allo_child.demand_allo_num,
                    sum_out_stock_guds.number_authentic_outbound,
                    sum_out_stock_guds.number_defective_outbound,
                    (
                        sum_allo_child.demand_allo_num - sum_out_stock_guds.number_authentic_outbound - sum_out_stock_guds.number_defective_outbound
                    ) AS remaining_outbound,
                    sum_in_stock_guds.number_authentic_warehousing,
                    sum_in_stock_guds.number_defective_warehousing,
                    (
                        (
                            IFNULL(sum_out_stock_guds.number_authentic_outbound,0)+ IFNULL(sum_out_stock_guds.number_defective_outbound,0)
                        ) - (
                            IFNULL(sum_in_stock_guds.number_authentic_warehousing,0) + IFNULL(sum_in_stock_guds.number_defective_warehousing,0)
                        )
                    ) AS remaining_inventory
                FROM
                    (
                        tb_wms_allo,
                        (
                            SELECT
                                allo_id,
                                sku_id,
                                SUM(demand_allo_num) AS demand_allo_num
                            FROM
                                tb_wms_allo_child
                            #where is_write_sku_num != 0
                            GROUP BY
                                allo_id,
                                sku_id
                        ) AS sum_allo_child,
                        tb_wms_allo_new_guds_infos
                    )
                LEFT JOIN (
                    SELECT
                        allo_id,
                        sku_id,
                        SUM(
                            this_out_authentic_products
                        ) AS number_authentic_outbound,
                        SUM(
                            this_out_defective_products
                        ) AS number_defective_outbound
                    FROM
                        tb_wms_allo_new_out_stock_guds
                    GROUP BY
                        allo_id,
                        sku_id
                ) AS sum_out_stock_guds ON sum_out_stock_guds.allo_id = tb_wms_allo.id
                AND sum_out_stock_guds.sku_id = tb_wms_allo_new_guds_infos.sku_id
                LEFT JOIN (
                    SELECT
                        allo_id,
                        in_stocks_id,
                        sku_id,
                        SUM(this_in_authentic_products) AS number_authentic_warehousing,
                        SUM(this_in_defective_products) AS number_defective_warehousing
                    FROM
                        tb_wms_allo_new_in_stock_guds
                    GROUP BY
                        allo_id,
                        sku_id
                ) AS sum_in_stock_guds ON sum_in_stock_guds.allo_id = tb_wms_allo.id
                AND sum_in_stock_guds.sku_id = tb_wms_allo_new_guds_infos.sku_id
                LEFT JOIN tb_ms_cmn_cd AS cd_1 ON cd_1.CD = tb_wms_allo_new_guds_infos.tax_free_sales_unit_price_currency_cd
                LEFT JOIN tb_ms_xchr ON tb_ms_xchr.XCHR_STD_DT = DATE_FORMAT(tb_wms_allo_new_guds_infos.created_at,'%Y%m%d')
                WHERE
                    tb_wms_allo.id = '{$allo_id}'
                AND tb_wms_allo.id = tb_wms_allo_new_guds_infos.allo_id
                AND sum_allo_child.allo_id = tb_wms_allo.id
                AND sum_allo_child.sku_id = tb_wms_allo_new_guds_infos.sku_id
                ";
        $res_db = $this->model->query($sql);
        
        Logs(['res_db' => $res_db], __FUNCTION__, __CLASS__);
        return $res_db;
    }

    public function transferProducts($allo_id)
    {
        $sql = "SELECT
                    tb_wms_batch_order.SKU_ID,
                    SUM(tb_wms_batch_order.request_occupy_num) AS sum_occupy_num,
                    tb_wms_batch_order.vir_type
                FROM
                    tb_wms_allo,
                    tb_wms_batch_order
                WHERE
                    tb_wms_allo.id = '{$allo_id}'
                AND tb_wms_allo.allo_no = tb_wms_batch_order.ORD_ID 
                AND tb_wms_batch_order.use_type != 3
                GROUP BY tb_wms_batch_order.SKU_ID,tb_wms_batch_order.vir_type";
        //Q 862工单处理调拨数量不对问题，去除条件：AND tb_wms_batch_order.use_type != 3。注意：在有调拨数量多了的情况，需要排查兼容两者
        return $this->model->query($sql);
    }
    public function transferProductsAtto($change_order_no)
    {
        $sql = "SELECT
                    tb_wms_batch_order.SKU_ID,
                    SUM(tb_wms_batch_order.request_occupy_num) AS sum_occupy_num,
                    tb_wms_batch_order.vir_type
                FROM
                    tb_wms_allo_attribution,
                    tb_wms_batch_order
                WHERE
                    tb_wms_allo_attribution.change_order_no = '{$change_order_no}'
                and tb_wms_allo_attribution.deleted_by is null
                AND tb_wms_allo_attribution.change_order_no = tb_wms_batch_order.ORD_ID 
                and tb_wms_batch_order.use_type = 1
                GROUP BY tb_wms_batch_order.SKU_ID,tb_wms_batch_order.vir_type";
        //Q 862工单处理调拨数量不对问题，去除条件：AND tb_wms_batch_order.use_type != 3。注意：在有调拨数量多了的情况，需要排查兼容两者
        return $this->model->query($sql);
    }
    public function getAttrByAlloId($allo_id)
    {
        $sql = "SELECT
	                sku.sku_id,

	                sum( sku.transfer_number ) AS num 
                FROM
	                tb_wms_allo_attribution attr
	            LEFT JOIN tb_wms_allo_attribution_sku sku ON attr.id = sku.allo_attribution_id 
                WHERE attr.allo_id = {$allo_id}
                and attr.deleted_by is null
                GROUP BY sku.sku_id";
        //Q 862工单处理调拨数量不对问题，去除条件：AND tb_wms_batch_order.use_type != 3。注意：在有调拨数量多了的情况，需要排查兼容两者
        return $this->model->query($sql);
    }
    /**
     * @param $allo_id
     * @param $is_used_amount
     *
     * @return mixed
     */
    public function getWork($allo_id, $is_used_amount)
    {
        $xchr_currency_cd_1 = StringModel::getXchrCurrency('cd_1.CD_VAL');
        $xchr_currency_cd_2 = StringModel::getXchrCurrency('cd_2.CD_VAL');
        $field = [
            'tb_wms_allo_new_works.*',
            "(tb_wms_allo_new_works.operating_expenses * ({$xchr_currency_cd_1})) AS operating_expenses_cny",
            "(tb_wms_allo_new_works.value_added_service_fee * ({$xchr_currency_cd_2})) AS value_added_service_fee_cny",
        ];
        $where['tb_wms_allo_new_works.allo_id'] = $allo_id;
        if (!is_null($is_used_amount)) {
            $where['tb_wms_allo_new_works.is_used_amount'] = $is_used_amount;
        }
        return $this->model->table('tb_wms_allo_new_works')
            ->field($field)
            ->join('LEFT JOIN tb_ms_xchr ON tb_ms_xchr.XCHR_STD_DT = DATE_FORMAT(tb_wms_allo_new_works.created_at,\'%Y%m%d\')')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_1 ON cd_1.CD = tb_wms_allo_new_works.operating_expenses_currency_cd')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_2 ON cd_2.CD = tb_wms_allo_new_works.value_added_service_fee_currency_cd')
            ->where($where)
            ->find();
    }

    /**
     * @param $allo_id
     * @param null $is_used_amount
     *
     * @return mixed
     */
    public function getOutStock($allo_id, $is_used_amount = null)
    {
        $xchr_currency_cd_1 = StringModel::getXchrCurrency('cd_1.CD_VAL');
        $xchr_currency_cd_2 = StringModel::getXchrCurrency('cd_2.CD_VAL');
        $field = [
            'tb_wms_allo_new_out_stocks.*',
            'tb_wms_allo_new_out_stocks.id as out_stock_id',
            'tb_crm_sp_supplier.SP_NAME AS transport_company_id_val',
            "(tb_wms_allo_new_out_stocks.outbound_cost * ({$xchr_currency_cd_1})) AS outbound_cost_cny",
            "(tb_wms_allo_new_out_stocks.head_logistics_fee * ({$xchr_currency_cd_2})) AS head_logistics_fee_cny",
            "(tb_wms_allo_new_out_stocks.insurance_fee * ({$xchr_currency_cd_2})) AS insurance_fee_cny",
            'tb_wms_allo_new_out_stock_guds.*',
            'GROUP_CONCAT(tb_wms_bill.bill_id) AS bill_id',
        ];
        if (!is_null($is_used_amount)) {
            $where['tb_wms_allo_new_out_stocks.is_used_amount'] = $is_used_amount;
        }
        $where['tb_wms_allo_new_out_stocks.allo_id'] = $allo_id;
        $where_string = " tb_wms_allo_new_out_stocks.bill_ids IS NOT NULL ";
        $bill_ids = trim(
            $this->model->table('tb_wms_allo_new_out_stocks')->where($where)->where($where_string, null, true)->getField('bill_ids'),
            ',');
        if (empty($bill_ids)) {
            return [];
        }
       return $this->model->table('tb_wms_allo_new_out_stocks')
            ->field($field)
            ->join('LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.ID = tb_wms_allo_new_out_stocks.transport_company_id ')
            ->join('LEFT JOIN tb_wms_allo_new_out_stock_guds ON tb_wms_allo_new_out_stock_guds.allo_id = tb_wms_allo_new_out_stocks.allo_id AND tb_wms_allo_new_out_stock_guds.out_stocks_id = tb_wms_allo_new_out_stocks.id')
            ->join('LEFT JOIN tb_ms_xchr ON tb_ms_xchr.XCHR_STD_DT = DATE_FORMAT(tb_wms_allo_new_out_stocks.created_at,\'%Y%m%d\')')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_1 ON cd_1.CD = tb_wms_allo_new_out_stocks.outbound_cost_currency_cd')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_2 ON cd_2.CD = tb_wms_allo_new_out_stocks.head_logistics_fee_currency_cd')
            ->join("LEFT JOIN tb_wms_bill  ON tb_wms_bill.id IN ({$bill_ids})")
            ->where($where)
            ->group('tb_wms_allo_new_out_stock_guds.id')
            ->select();
       
           
    }
    public function getWarehouse($warehouse_cd,$field)
    {
        #查询仓库负责人
        $res = $this->model
            ->table('tb_con_division_warehouse')
            ->where(['warehouse_cd' => $warehouse_cd])
            ->getField($field);
        return $res;
    }
    /**
     * @param $allo_id
     *
     * @return mixed
     */
    public function getNewInStock($allo_id, $out_stocks){
        #已入库 需要查正品和次品
        $res = $this->model
            ->table('tb_wms_allo_new_in_stock_guds')
            ->field('sum(this_in_authentic_products) as in_authentic_sum,sum(this_in_defective_products) as in_defective_sum,CONCAT(out_stock_id,"-",sku_id) as out_stock_id_sku')
            ->where(['allo_id' => $allo_id])
            ->group('out_stock_id,sku_id')
            ->select();
        return $res;
    }
    public function getNewOutStock($allo_id)
    {
       
        $res = $this->model
            ->table('tb_wms_allo_new_out_stock_guds')
            ->field('sum(this_out_authentic_products) as authentic_sum,sum(this_out_defective_products) as defective_products,sku_id')
            ->where(['allo_id' => $allo_id])
            ->group('sku_id')
            ->select();
        return $res;
    }
    public function getNewInStockListBySku($allo_id, $out_stock_id,$sku_id)
    {
        #已入库 需要查正品和次品
        $res = $this->model
            ->table('tb_wms_allo_new_in_stock_guds')
            ->field('created_at,created_by,this_in_authentic_products+this_in_defective_products as in_sum')
            ->where(['allo_id' => $allo_id, 'out_stock_id'=>$out_stock_id, 'sku_id'=> $sku_id])
            ->select();
        return $res;
    }
    public function getInStock($allo_id)
    {
        $xchr_currency_cd_1 = StringModel::getXchrCurrency('cd_1.CD_VAL');
        $xchr_currency_cd_2 = StringModel::getXchrCurrency('cd_2.CD_VAL');
        $xchr_currency_cd_3 = StringModel::getXchrCurrency('cd_3.CD_VAL');
        $field = [
            'tb_wms_allo_new_in_stocks.*',
            "(tb_wms_allo_new_in_stocks.tariff * ({$xchr_currency_cd_1})) AS tariff_cny",
            "(tb_wms_allo_new_in_stocks.shelf_cost * ({$xchr_currency_cd_2})) AS shelf_cost_cny",
            "(tb_wms_allo_new_in_stocks.value_added_service_fee * ({$xchr_currency_cd_3})) AS value_added_service_fee_cny",
            'tb_wms_allo_new_in_stock_guds.*',
            'GROUP_CONCAT(tb_wms_bill.bill_id) AS bill_id',
        ];
        $where['tb_wms_allo_new_in_stocks.allo_id'] = $allo_id;
        $where_string = " tb_wms_allo_new_in_stocks.bill_ids IS NOT NULL ";
        $bill_ids = trim(
            $this->model->table('tb_wms_allo_new_in_stocks')->where($where)->where($where_string, null, true)->getField('bill_ids'),
            ',');
        if (empty($bill_ids)) {
            return [];
        }
        return $this->model->table('tb_wms_allo_new_in_stocks')
            ->field($field)
            ->join('LEFT JOIN tb_wms_allo_new_in_stock_guds ON tb_wms_allo_new_in_stock_guds.allo_id = tb_wms_allo_new_in_stocks.allo_id AND tb_wms_allo_new_in_stock_guds.in_stocks_id = tb_wms_allo_new_in_stocks.id')
            ->join('LEFT JOIN tb_ms_xchr ON tb_ms_xchr.XCHR_STD_DT = DATE_FORMAT(tb_wms_allo_new_in_stocks.created_at,\'%Y%m%d\')')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_1 ON cd_1.CD = tb_wms_allo_new_in_stocks.tariff_currency_cd')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_2 ON cd_2.CD = tb_wms_allo_new_in_stocks.shelf_cost_currency_cd')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_3 ON cd_3.CD = tb_wms_allo_new_in_stocks.value_added_service_fee_currency_cd')
            ->join("LEFT JOIN tb_wms_bill  ON tb_wms_bill.id IN ({$bill_ids})")
            ->where($where)
            ->group('tb_wms_allo_new_in_stock_guds.id')
            ->select();
    }

    /**
     * @param $allo_id
     * @param $data
     * @param $Model
     *
     * @throws Exception
     */
    public function editAlloInfo($allo_id, $data, $Model)
    {
        switch ($data['type']) {
            case 'submit':
                $state = 'N001970100';
                break;
            case 'save':
            default:
                $state = 'N001970601';
        }
        $this->editAlloNewInfo($allo_id, $data['info'], $state, $Model);
        $this->editAlloNewGoods($allo_id, $data['goods'], $Model);
    }

    /**
     * @param $allo_id
     * @param $data
     * @param $Model
     *
     * @throws Exception
     */
    private function editAlloNewInfo($allo_id, $data, $state, $Model)
    {
        $where_allo['id'] = $allo_id;
        $do_edit_status_cds = ['N001970601'];
        $where_allo['state'] = ['IN', $do_edit_status_cds];
        $save_allo['expected_delivery_date'] = strtotime($data['expected_delivery_date']) ? $data['expected_delivery_date'] : null;
        $save_allo['expected_warehousing_date'] = strtotime($data['expected_warehousing_date']) ? $data['expected_warehousing_date'] : null;
        $save_allo['planned_transportation_channel_cd'] = $data['planned_transportation_channel_cd'];
        $save_allo['update_time'] = DateModel::now();
        $save_allo['state'] = $state;
        if (1 !== $Model->table('tb_wms_allo')
                ->where($where_allo)
                ->save($save_allo)) {
            throw new Exception(L('更新调拨失败,请检查订单信息'));
        };
    }

    /**
     * @param $allo_id
     * @param $data
     * @param $Model
     *
     * @throws Exception
     */
    private function editAlloNewGoods($allo_id, $data, $Model)
    {
        $update_goods_num = 0;
        foreach ($data as $datum) {
            $where_goods['allo_id'] = $allo_id;
            $where_goods['sku_id'] = $datum['sku_id'];
            $save_goods['tax_free_sales_unit_price_currency_cd'] = $datum['tax_free_sales_unit_price_currency_cd'];
            $save_goods['tax_free_sales_unit_price'] = $datum['tax_free_sales_unit_price'];
            $save_goods['updated_by'] = DataModel::userNamePinyin();
            $temp_goods_update = $update_goods_num += $Model->table('tb_wms_allo_new_guds_infos')
                ->where($where_goods)
                ->save($save_goods);
            if (false === $temp_goods_update) {
                throw new Exception(L('更新调拨商品失败') . "{$datum['sku_id']}");
            }
        }
        if (count($data) !== $update_goods_num) {
        }
    }

    /**
     * @param $allo_id
     *
     * @return mixed
     */
    public function submitAllo($allo_id)
    {
        $where_allo['id'] = $allo_id;
        $do_update_status_cds = ['N001970601'];
        $where_allo['state'] = ['IN', $do_update_status_cds];

        $save_allo['update_time'] = DateModel::now();
        $save_allo['state'] = 'N001970100';
        return $this->model->table('tb_wms_allo')
            ->where($where_allo)
            ->save($save_allo);
    }

    /**
     * @param $allo_id
     *
     * @return mixed
     */
    public function deleteAllo($allo_id)
    {
        $where_allo['id'] = $allo_id;
        $do_update_status_cds = ['N001970601'];
        $where_allo['state'] = ['IN', $do_update_status_cds];

        $save_allo['deleted_at'] = $save_allo['update_time'] = DateModel::now();
        $save_allo['deleted_by'] = DataModel::userNamePinyin();
        return $this->model->table('tb_wms_allo')
            ->where($where_allo)
            ->save($save_allo);
    }

    /**
     * @param $allo_id
     * @param $type_cd
     *
     * @return mixed
     */
    public function updateReviewAllo($allo_id, $type_cd, $updated_by)
    {
        $where_allo['id'] = $allo_id;
        $do_review_status_cds = ['N001970100'];
        $where_allo['state'] = ['IN', $do_review_status_cds];

        $save_allo['update_user'] = $updated_by;
        $save_allo['update_time'] = DateModel::now();
        $save_allo['state'] = $type_cd;
        return $this->model->table('tb_wms_allo')
            ->where($where_allo)
            ->save($save_allo);
    }

    public function updateReviewAlloWeChat($allo_id, $review_status, $updated_by)
    {
        $where['order_id'] = $allo_id;

        $save['review_status'] = $review_status;
        $save['review_at'] = DateModel::now();
        $save['updated_by'] = $save['review_by'] = $updated_by;
        return $this->model->table('tb_sys_reviews')
            ->where($where)
            ->save($save);
    }
    
    public function updateAlloWorkStatus($data)
    {
        $where_allo['id'] = $data['allo_id'];
        $where_allo['state'] = 'N001970602';
        $save_allo['tb_wms_allo.state'] = 'N001970603';
        $save_allo['tb_wms_allo.update_user'] = DataModel::userNamePinyin();
        return $this->external_model->table('tb_wms_allo')->where($where_allo)->save($save_allo);

    }

    /**
     * @param $data
     *
     * @return mixed
     * @throws Exception
     */
    public function addtWork($data)
    {
        $save_work = $data;
        $save_work['tb_wms_allo_new_works.job_photos'] = json_encode($save_work['job_photos'], JSON_UNESCAPED_UNICODE);
        $save_work['tb_wms_allo_new_works.updated_by'] = DataModel::userNamePinyin();
        $res_add = $this->external_model->table('tb_wms_allo_new_works')->add($save_work);
        return $res_add;
    }

    /**
     * @param $allo_id
     * @param $data
     *
     * @throws Exception
     */
    public function submitWorkGoods($allo_id, $data)
    {
        $submit_work_goods_num = 0;
        foreach ($data as $datum) {
            $where_goods['allo_id'] = $allo_id;
            $where_goods['sku_id'] = $datum['sku_id'];
            $save_goods = $datum;
            unset($save_goods['sku_id']);
            $save_goods['updated_by'] = DataModel::userNamePinyin();
            $save_goods['updated_at'] = DateModel::now();
            $temp_goods_update = $submit_work_goods_num += $this->external_model->table('tb_wms_allo_new_guds_infos')
                ->where($where_goods)
                ->save($save_goods);
            if (false === $temp_goods_update) {
                throw new Exception(L('更新调拨商品失败') . "{$datum['sku_id']}");
            }
        }
        if (count($data) !== $submit_work_goods_num) {
            throw new Exception(L('更新调拨商品失败,商品改动不一致'));
        }
    }

    /**
     * @todo 验证出入库状态
     *
     * @param $allo_id
     *
     * @return mixed
     */

    public function waitingAssignmentWithdrawn($allo_id)
    {
        $where_state['tb_wms_allo.id'] = $allo_id;
        $where_state['tb_wms_allo.state'] = ['IN', ['N001970602', 'N001970603']];
        $save_state['tb_wms_allo.state'] = 'N001970601';
        return $this->model->table('tb_wms_allo')
            ->where($where_state)
            ->save($save_state);
    }

    /**
     * @param $allo_id
     *
     * @return mixed
     */
    public function deleteWork($allo_id)
    {
        $where_state['allo_id'] = $allo_id;
        return $this->model->table('tb_wms_allo_new_works')
            ->where($where_state)
            ->delete();
    }

    /**
     * @param $allo_id
     * @param $data
     *
     * @return mixed
     */
    public function submitOutStockInfo($allo_id, $data)
    {
        $add_out_stock_infos = $data;
        unset($add_out_stock_infos['reason_difference']);
        $add_out_stock_infos['allo_id'] = $allo_id;
        $add_out_stock_infos['created_by'] = $add_out_stock_infos['updated_by'] = DataModel::userNamePinyin();
        return $this->external_model->table('tb_wms_allo_new_out_stocks')->add($add_out_stock_infos);
    }
    /*
     * @param $allo_id
     * @param $out_stock_id
     * @param $data
     *
     * @return mixed
     */
    public function submitOutStockLogisticsInfo($allo_id,$out_stock_id, $data)
    {
        $update_out_stock_infos = $data;
        // unset($update_out_stock_infos['reason_difference']);
        $where['allo_id'] = $allo_id;
        $where['id'] = $out_stock_id;
        $update_out_stock_infos['updated_by'] = DataModel::userNamePinyin();
        $fields = array_keys(AllocationExtendNewService::$outStockLogisticsCustomAttributes);
        $update_out_stock_infos = array_only($update_out_stock_infos, $fields);
        $empty_null_fields = [
            'insurance_type','cube_feet_type'
        ];
        foreach ($update_out_stock_infos as $k =>  $val) {
            if(in_array($k, $empty_null_fields) && empty($val)) {
                $update_out_stock_infos[$k] =NULL;
             }
        }
        return $this->external_model->table('tb_wms_allo_new_out_stocks')->where($where)->save($update_out_stock_infos);
    }
    /*
     * @param $allo_id
     * @param $out_stock_id
     *
     *
     * @return mixed
     */
    public function getOutStockNode($allo_id, $out_stock_id){
        $where['allo_id'] = $allo_id;
        $where['out_stock_id'] = $out_stock_id;
        return $this->external_model->table('tb_wms_allo_new_out_stocks_node')->order('type')->where($where)->select();
    }
    public function getOutStockNodeReason($node_id)
    {
        $where['node_id'] = ['in', $node_id];
        return $this->external_model->table('tb_wms_allo_new_out_stocks_node_reason')->order('id')->where($where)->select();
    }
     /*
     * @param $allo_id
     * @param $out_stock_id
     * @param $data
     *
     * @return mixed
     */
    public function submitOutStockNode($allo_id, $out_stock_id, $data){
        if($data['method'] == 1){
            //插入
            $re = $this->external_model->table('tb_wms_allo_new_out_stocks_node')->addAll($data['data']);
        }else{
            //更新
            //条数不多 且每次大概率只更新一条
            $re = true;
            foreach($data['data'] as $value){
                $tmpWhere = [
                    'allo_id'=> $allo_id,
                    'out_stock_id' => $out_stock_id,
                    'type'=>$value['where']['type'],
                ];
                $tmpBl = $this->external_model->table('tb_wms_allo_new_out_stocks_node')->where($tmpWhere)->save($value['data']);
                if($tmpBl === false){
                    $re = false;
                }
            }
           
        }
        if($re === false){
            return false;
        }
        #更新物流轨迹
        $tmpWhere = [
            'allo_id' => $allo_id,
            'out_stock_id' => $out_stock_id,
            'node_operate' =>['gt',0]
            
        ];
        $type = $this->external_model->table('tb_wms_allo_new_out_stocks_node')->where($tmpWhere)->order('type desc')->getField('type');

        return $this->external_model->table('tb_wms_allo_new_out_stocks')->where(['id' => $out_stock_id])->save(['logistics_state' => $type]);
        
    }
    /*
     * @param $allo_id
     * @param $out_stock_id
     * @param $data
     *
     * @return mixed
     */
    public function submitOutStockNodeReason($data){
        return $this->external_model->table('tb_wms_allo_new_out_stocks_node_reason')->add($data);
        
    }
    
    /**
     * @param $allo_id
     * @param $out_stocks_id
     * @param $data
     *
     * @return mixed
     */
    public function submitOutStockGoods($allo_id, $out_stocks_id, $data)
    {
        $user_name_pinyin = DataModel::userNamePinyin();
        foreach ($data as $datum) {
            $datum['allo_id'] = $allo_id;
            $datum['out_stocks_id'] = $out_stocks_id;
            $datum['created_by'] = $datum['updated_by'] = $user_name_pinyin;
            $save_goods[] = $datum;
        }
        return $this->external_model->table('tb_wms_allo_new_out_stock_guds')
            ->addAll($save_goods);
    }

    /**
     * @param $allo_id
     * @param $data
     *
     * @return mixed
     */
    public function submitInStockInfo($allo_id,$out_stock_id, $data)
    {
        $add_out_stock_infos = $data;
        unset($data['reason_difference']);
        $add_out_stock_infos['allo_id'] = $allo_id;
        $add_out_stock_infos['out_stock_id'] = $out_stock_id;
        $add_out_stock_infos['created_by'] = $add_out_stock_infos['updated_by'] = DataModel::userNamePinyin();
        return $this->external_model->table('tb_wms_allo_new_in_stocks')->add($add_out_stock_infos);
    }
    

    /**
     * @param $allo_id
     * @param $out_stocks_id
     * @param $data
     *
     * @return mixed
     */
    public function submitInStockGoods($allo_id, $in_stocks_id, $out_stock_id, $data)
    {
        $user_name_pinyin = DataModel::userNamePinyin();
        foreach ($data as $datum) {
            unset($datum['upc_id']);
            $datum['allo_id'] = $allo_id;
            $datum['in_stocks_id'] = $in_stocks_id;
            $datum['out_stock_id'] = $out_stock_id;
            $datum['created_by'] = $datum['updated_by'] = $user_name_pinyin;
            $save_goods[] = $datum;
        }
        return $this->external_model->table('tb_wms_allo_new_in_stock_guds')
            ->addAll($save_goods);
    }

    public function getLogs($allo_id)
    {
        return $this->model->table('tb_wms_allo_new_logs')
            ->where("allo_id = '{$allo_id}'")
            ->select();
    }

    public function  outboundTagCompletion($allo_id, $reason_difference)
    {
        $where['allo_id'] = $allo_id;
        $where['allo_out_status'] = 0;
        $save['allo_out_status'] = 1;
        $save['out_reason_difference'] = $reason_difference;
        $save['updated_by'] = $save['out_mark_by'] = DataModel::userNamePinyin();
        $save['out_mark_at'] = DateModel::now();
        $in_status = $this->getStockStatus($allo_id, 'in');
        if (1 == $in_status) {
            $this->updateAlloStateToComplete($allo_id);
        }
        return $this->model->table('tb_wms_allo_new_status')
            ->where($where)
            ->save($save);
    }

    public function inboundTagCompletion($allo_id, $reason_difference)
    {
        $where['allo_id'] = $allo_id;
        $where['allo_out_status'] = 1;
        $where['allo_in_status'] = 0;
        $save['allo_in_status'] = 1;
        $save['in_reason_difference'] = $reason_difference;
        $save['updated_by'] = $save['in_mark_by'] = DataModel::userNamePinyin();
        $save['in_mark_at'] = DateModel::now();
        $this->updateAlloStateToComplete($allo_id);
        return $this->model->table('tb_wms_allo_new_status')
            ->where($where)
            ->save($save);
    }

    public function getAlloSaleLeader($allo_id)
    {
        $where_string = 'tb_wms_allo.allo_in_team = tb_ms_cmn_cd.CD';
        return $this->model->table('tb_wms_allo,tb_ms_cmn_cd')
            ->where("tb_wms_allo.id = '{$allo_id}'")
            ->where($where_string, null, true)
            ->getField('tb_ms_cmn_cd.ETC');
    }

    public function getAlloWeChatInfo($allo_id)
    {
        return $this->model->table('tb_wms_allo')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_1 ON cd_1.CD = tb_wms_allo.allo_in_team')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_2 ON cd_2.CD = tb_wms_allo.allo_out_warehouse')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_3 ON cd_3.CD = tb_wms_allo.allo_in_warehouse')
            ->where("tb_wms_allo.id = '{$allo_id}'")
            ->getField('tb_wms_allo.id,tb_wms_allo.allo_no,
            cd_1.CD_VAL AS allo_in_team_val,
            cd_2.CD_VAL AS allo_out_warehouse_val,
            cd_3.CD_VAL AS allo_in_warehouse_val,
            tb_wms_allo.create_time,
            tb_wms_allo.create_user');
    }

    public function getTransportCompany()
    {
        $sql = "SELECT ID,SP_NAME FROM `tb_crm_sp_supplier` WHERE `COPANY_TYPE_CD` LIKE '%N001190900%' 
                AND DATA_MARKING = 0 
                AND DEL_FLAG = 1 
                AND SP_STATUS = 1";
        return $this->model->query($sql);
    }

    public function updateStockStatus($allo_id, $type)
    {
        $sql = "SELECT
                    tb_wms_allo.id,
                    tb_wms_allo_child.sku_id,
                    SUM(
                        tb_wms_allo_child.demand_allo_num
                    ) AS demand_allo_num_sum,
                    
                    tb_wms_allo_new_in_stock_guds.in_guds_id,
                    tb_wms_allo_new_in_stock_guds.in_product,
                    tb_wms_allo_new_out_stock_guds.out_guds_id,
                    tb_wms_allo_new_out_stock_guds.out_product
                FROM
                    (
                        tb_wms_allo,
                        tb_wms_allo_child
                    )
                LEFT JOIN (
                    SELECT
                        tb_wms_allo_new_out_stock_guds.allo_id,
                        tb_wms_allo_new_out_stock_guds.id AS out_guds_id,
                        tb_wms_allo_new_out_stock_guds.sku_id,
                        SUM(
                            tb_wms_allo_new_out_stock_guds.this_out_authentic_products + tb_wms_allo_new_out_stock_guds.this_out_defective_products
                        ) AS out_product
                    FROM
                        tb_wms_allo_new_out_stock_guds
                    GROUP BY
                        allo_id,
                        sku_id
                ) AS tb_wms_allo_new_out_stock_guds ON tb_wms_allo.id = tb_wms_allo_new_out_stock_guds.allo_id
                AND tb_wms_allo_child.sku_id = tb_wms_allo_new_out_stock_guds.sku_id
                LEFT JOIN (
                    SELECT
                        tb_wms_allo_new_in_stock_guds.allo_id,
                        tb_wms_allo_new_in_stock_guds.id AS in_guds_id,
                        tb_wms_allo_new_in_stock_guds.sku_id,
                        SUM(
                            tb_wms_allo_new_in_stock_guds.this_in_authentic_products + tb_wms_allo_new_in_stock_guds.this_in_defective_products
                        ) AS in_product
                    FROM
                        tb_wms_allo_new_in_stock_guds
                    GROUP BY
                        allo_id,
                        sku_id
                ) AS tb_wms_allo_new_in_stock_guds ON tb_wms_allo.id = tb_wms_allo_new_in_stock_guds.allo_id
                AND tb_wms_allo_new_in_stock_guds.sku_id = tb_wms_allo_new_out_stock_guds.sku_id
                WHERE
                    tb_wms_allo.id = {$allo_id}
                AND tb_wms_allo_child.allo_id = tb_wms_allo.id
                GROUP BY
                    tb_wms_allo_child.allo_id,
                    tb_wms_allo_child.sku_id";
        $stock_dbs = $this->model->query($sql);
        $out_err = $in_err = 0;
        foreach ($stock_dbs as $stock_db) {
            if ($stock_db['demand_allo_num_sum'] < $stock_db["{$type}_product"]) {
                throw new Exception(L('更新调拨出入库状态异常-总商品数量超出'));
                break;
            }
            if ('out' == $type) {
                if ($stock_db['demand_allo_num_sum'] != $stock_db["{$type}_product"]) {
                    $out_err++;
                    continue;
                }
            }
            if ('in' == $type) {
                if ($stock_db['out_product'] < $stock_db['in_product']) {
                    throw new Exception(L('更新调拨入库状态异常-总商品数量超出'));
                }
                if ($stock_db['out_product'] !== $stock_db['in_product']) {
                    $in_err++;
                    continue;
                }
            }
        }
        if ($in_err || $out_err) {
            return null;
        }
        $product_out_sum = array_sum(array_column($stock_dbs, "out_product"));
        $product_in_sum = array_sum(array_column($stock_dbs, "in_product"));
        $out_status = $this->getStockStatus($allo_id, 'out');
        $allo_num_sum = array_sum(array_column($stock_dbs, 'demand_allo_num_sum'));
        $product_sum = array_sum(array_column($stock_dbs, "{$type}_product"));
        if (!((0 != $product_out_sum && $product_out_sum == $product_in_sum) ||
            $allo_num_sum == $product_sum)) {
            return null;
        }

        $where['allo_id'] = $allo_id;
        $where["allo_{$type}_status"] = 0;
        $save["allo_{$type}_status"] = 1;
        $save["updated_by"] = DataModel::userNamePinyin();
        switch ($type) {
            case 'out':
                $save["actual_outgoing_num"] = ['EXP', 'demand_allo_num_sum'];
                break;
            case 'in':
                $save["actual_storage_num"] = ['EXP', 'demand_allo_num'];
                break;
        }

        if ('in' == $type && 1 == $save["allo_in_status"] && 1 == $out_status) {
            $this->updateAlloStateToComplete($allo_id);
        }
        return $this->model->table('tb_wms_allo_new_status')
            ->where($where)
            ->save($save);
    }

    public function updateAlloInStatus($allo_id)
    {
        $sql = "SELECT
                    tb_wms_allo_new_in_stock_guds.*, tb_wms_allo_new_out_stock_guds.*
                FROM
                    tb_wms_allo_new_status
                LEFT JOIN (
                    SELECT
                        allo_id,
                        SUM(
                            tb_wms_allo_new_in_stock_guds.this_in_authentic_products + tb_wms_allo_new_in_stock_guds.this_in_defective_products
                        ) AS sum_in_stock
                    FROM
                        tb_wms_allo_new_in_stock_guds
                    GROUP BY
                        allo_id
                ) AS tb_wms_allo_new_in_stock_guds ON tb_wms_allo_new_in_stock_guds.allo_id = tb_wms_allo_new_status.allo_id
                LEFT JOIN (
                    SELECT
                        allo_id,
                        SUM(
                            tb_wms_allo_new_out_stock_guds.this_out_authentic_products + tb_wms_allo_new_out_stock_guds.this_out_defective_products
                        ) AS sum_out_stock
                    FROM
                        tb_wms_allo_new_out_stock_guds
                    GROUP BY
                        allo_id
                ) AS tb_wms_allo_new_out_stock_guds ON tb_wms_allo_new_out_stock_guds.allo_id = tb_wms_allo_new_status.allo_id
                WHERE
                    tb_wms_allo_new_status.allo_id = {$allo_id}";
        $res_db = $this->model->query($sql)[0];
        if (empty($res_db) || $res_db['sum_in_stock'] != $res_db['sum_out_stock']) {
            $save["allo_in_status"] = 0;
        } else {
            $save["allo_in_status"] = 1;
        }
        $where['allo_id'] = $allo_id;
        $save["updated_by"] = DataModel::userNamePinyin();
        return $this->model->table('tb_wms_allo_new_status')
            ->where($where)
            ->save($save);
    }

    public function checkOutStockNum($allo_id, $allo_no)
    {
        $sql = "SELECT
                    tb_wms_allo.allo_no,
                    sum_batch_order.SKU_ID,
                    sum_batch_order.vir_type,
                    out_stock_guds.sum_out_authentic_products AS sum_authentic_products,
                    out_stock_guds.sum_out_defective_products AS sum_defective_products,
                    sum_batch_order.sum_occupy_num
                FROM
                    tb_wms_allo,
                    (
                        SELECT
                            tb_wms_allo_new_out_stock_guds.allo_id,
                            tb_wms_allo_new_out_stock_guds.sku_id,
                            SUM(
                                tb_wms_allo_new_out_stock_guds.this_out_authentic_products
                            ) AS sum_out_authentic_products,
                            SUM(
                                tb_wms_allo_new_out_stock_guds.this_out_defective_products
                            ) AS sum_out_defective_products
                        FROM
                            tb_wms_allo_new_out_stock_guds
                        GROUP BY
                            tb_wms_allo_new_out_stock_guds.allo_id,
                            tb_wms_allo_new_out_stock_guds.sku_id
                    ) AS out_stock_guds,
                    (
                        SELECT
                            tb_wms_batch_order.ord_id,
                            tb_wms_batch_order.SKU_ID,
                            tb_wms_batch_order.vir_type,
                            SUM(
                                tb_wms_batch_order.request_occupy_num
                            ) AS sum_occupy_num
                        FROM
                            tb_wms_batch_order
                        WHERE  ORD_ID = '{$allo_no}'
                        GROUP BY
                            tb_wms_batch_order.ord_id,
                            tb_wms_batch_order.SKU_ID,
                            tb_wms_batch_order.vir_type
                    ) AS sum_batch_order
                WHERE
                    tb_wms_allo.id = {$allo_id}
                AND tb_wms_allo.id = out_stock_guds.allo_id
                AND tb_wms_allo.allo_no = sum_batch_order.ORD_ID
                AND out_stock_guds.sku_id = sum_batch_order.SKU_ID";
        return $this->model->query($sql);
    }

    public function checkInStockNum($allo_id, $allo_no)
    {
        $sql = "SELECT
                    tb_wms_allo.allo_no,
                    sum_batch_order.SKU_ID,
                    sum_batch_order.vir_type,
                    in_stock_guds.sum_in_authentic_products AS sum_authentic_products,
                    in_stock_guds.sum_in_defective_products AS sum_defective_products,
                    sum_batch_order.sum_occupy_num
                FROM
                    tb_wms_allo,
                    (
                        SELECT
                            tb_wms_allo_new_in_stock_guds.allo_id,
                            tb_wms_allo_new_in_stock_guds.sku_id,
                            SUM(
                                tb_wms_allo_new_in_stock_guds.this_in_authentic_products
                            ) AS sum_in_authentic_products,
                            SUM(
                                tb_wms_allo_new_in_stock_guds.this_in_defective_products
                            ) AS sum_in_defective_products
                        FROM
                            tb_wms_allo_new_in_stock_guds
                        GROUP BY
                            tb_wms_allo_new_in_stock_guds.allo_id,
                            tb_wms_allo_new_in_stock_guds.sku_id
                    ) AS in_stock_guds,
                    (
                        SELECT
                            tb_wms_batch_order.ord_id,
                            tb_wms_batch_order.SKU_ID,
                            tb_wms_batch_order.vir_type,
                            SUM(
                                tb_wms_batch_order.request_occupy_num
                            ) AS sum_occupy_num
                        FROM
                            tb_wms_batch_order
                         WHERE  ORD_ID = '{$allo_no}'
                        GROUP BY
                            tb_wms_batch_order.ord_id,
                            tb_wms_batch_order.SKU_ID,
                            tb_wms_batch_order.vir_type
                    ) AS sum_batch_order
                WHERE
                    tb_wms_allo.id = {$allo_id}
                AND tb_wms_allo.id = in_stock_guds.allo_id
                AND tb_wms_allo.allo_no = sum_batch_order.ORD_ID
                AND in_stock_guds.sku_id = sum_batch_order.SKU_ID";
        return $this->model->query($sql);
    }

    public function saveLog($allo_id, $log_msg)
    {
        $save = [
            'allo_id' => $allo_id,
            'operation_detail' => $log_msg,
            'created_by' => DataModel::userNamePinyin(),
            'updated_by' => DataModel::userNamePinyin(),
        ];
        return $this->model->table('tb_wms_allo_new_logs')->add($save);
    }
    public function getBatchOrdersAttr($change_order_no){
        $sql = "SELECT
                    tb_wms_batch_order.ORD_ID,
                    tb_wms_batch_order.SKU_ID,
                    tb_wms_batch_order.occupy_num,
                    tb_wms_stream.unit_price,
                    round(
                        sum(
                           ( tb_wms_stream.unit_price / (1+ifnull(tb_wms_stream.pur_invoice_tax_rate,0)) )* tb_wms_batch_order.request_occupy_num
                        ) / sum_allo_child.demand_allo_num , 2
                    ) AS average_price_goods_without_tax_cny,
                    round(
                        sum(
                            tb_wms_stream_cost_log.po_cost * tb_wms_batch_order.request_occupy_num
                        ) / sum_allo_child.demand_allo_num , 2
                    ) AS average_po_internal_cost_cny,
                    tb_wms_stream_cost_log.log_service_cost,
                    tb_wms_stream_cost_log.carry_cost,
                   tb_wms_batch.warehouse_cost,
                   (
                 		(
                        tb_wms_stream.unit_price + (
                            sum(
                                tb_wms_stream_cost_log.po_cost * tb_wms_batch_order.request_occupy_num
                            ) / sum_allo_child.demand_allo_num
                        )
                    ) * sum_allo_child.demand_allo_num
                ) AS total_value_good,
                    tb_wms_stream.log_service_cost,
                    tb_wms_stream.po_cost_origin,
                    sum_allo_child.demand_allo_num,
                    SUM(
                    (tb_wms_stream_cost_log.log_service_cost + tb_wms_stream_cost_log.carry_cost + IFNULL(tb_wms_batch.warehouse_cost,0))
                     * tb_wms_batch_order.request_occupy_num / sum_allo_child.demand_allo_num
                     ) AS po_outside_cost_unit_price_cny
                FROM
                    tb_wms_batch_order,
                    tb_wms_batch,
                    tb_wms_stream,
                    tb_wms_stream_cost_log,
                    tb_wms_allo,
										tb_wms_allo_attribution,
                    (SELECT allo_id,sku_id,SUM(demand_allo_num) AS demand_allo_num FROM tb_wms_allo_child GROUP BY allo_id,sku_id) AS sum_allo_child
                WHERE
                 tb_wms_batch_order.ORD_ID = '{$change_order_no}'
                AND tb_wms_allo.id = tb_wms_allo_attribution.allo_id
				AND tb_wms_allo_attribution.change_order_no = tb_wms_batch_order.ORD_ID
                AND tb_wms_allo.id = sum_allo_child.allo_id
                AND sum_allo_child.sku_id = tb_wms_batch.SKU_ID
                AND tb_wms_batch_order.batch_id = tb_wms_batch.id
                and tb_wms_batch_order.use_type = 1
                AND tb_wms_batch.stream_id = tb_wms_stream.id
                AND tb_wms_stream_cost_log.stream_id = tb_wms_stream.id
                AND tb_wms_stream_cost_log.currency_id = 'N000590300'
                GROUP BY
                    tb_wms_batch.SKU_ID
        ";
        return $this->model->query($sql);
    }
    public function getBatchOrders($allo_no)
    {
        $sql = "SELECT
                    tb_wms_batch_order.ORD_ID,
                    tb_wms_batch_order.SKU_ID,
                    tb_wms_batch_order.occupy_num,
                    tb_wms_stream.unit_price,
                    round(
                        sum(
                           ( tb_wms_stream.unit_price / (1+ifnull(tb_wms_stream.pur_invoice_tax_rate,0)) )* tb_wms_batch_order.request_occupy_num
                        ) / sum_allo_child.demand_allo_num , 2
                    ) AS average_price_goods_without_tax_cny,
                    round(
                        sum(
                            tb_wms_stream_cost_log.po_cost * tb_wms_batch_order.request_occupy_num
                        ) / sum_allo_child.demand_allo_num , 2
                    ) AS average_po_internal_cost_cny,
                    tb_wms_stream_cost_log.log_service_cost,
                    tb_wms_stream_cost_log.carry_cost,
                   tb_wms_batch.warehouse_cost,
                   (
                 		(
                        tb_wms_stream.unit_price + (
                            sum(
                                tb_wms_stream_cost_log.po_cost * tb_wms_batch_order.request_occupy_num
                            ) / sum_allo_child.demand_allo_num
                        )
                    ) * sum_allo_child.demand_allo_num
                ) AS total_value_good,
                    tb_wms_stream.log_service_cost,
                    tb_wms_stream.po_cost_origin,
                    sum_allo_child.demand_allo_num,
                    SUM(
                    (tb_wms_stream_cost_log.log_service_cost + tb_wms_stream_cost_log.carry_cost + IFNULL(tb_wms_batch.warehouse_cost,0))
                     * tb_wms_batch_order.request_occupy_num / sum_allo_child.demand_allo_num
                     ) AS po_outside_cost_unit_price_cny
                FROM
                    tb_wms_batch_order,
                    tb_wms_batch,
                    tb_wms_stream,
                    tb_wms_stream_cost_log,
                    tb_wms_allo,
                    (SELECT allo_id,sku_id,SUM(demand_allo_num) AS demand_allo_num FROM tb_wms_allo_child GROUP BY allo_id,sku_id) AS sum_allo_child
                WHERE
                 tb_wms_batch_order.ORD_ID = '{$allo_no}'
                AND tb_wms_allo.allo_no = tb_wms_batch_order.ORD_ID
                AND tb_wms_allo.id = sum_allo_child.allo_id
                AND sum_allo_child.sku_id = tb_wms_batch.SKU_ID
                AND tb_wms_batch_order.batch_id = tb_wms_batch.id
                AND tb_wms_batch.stream_id = tb_wms_stream.id
                AND tb_wms_stream_cost_log.stream_id = tb_wms_stream.id
                AND tb_wms_stream_cost_log.currency_id = 'N000590300'
                GROUP BY
                    tb_wms_batch.SKU_ID";
        return $this->model->query($sql);
    }

    public function sendWorkWeChatMsg($allo_id)
    {
        $sql = "SELECT
                    tb_wms_allo.allo_no,
                    tb_ms_cmn_cd.ETC
                FROM
                    tb_wms_allo,
                    tb_ms_cmn_cd
                WHERE
                    tb_wms_allo.allo_out_warehouse = tb_ms_cmn_cd.CD
                AND tb_wms_allo.id = '{$allo_id}'";
        return $this->model->query($sql);
    }

    public function getAlloReviewBy($allo_id)
    {
        $user = DataModel::userNamePinyin();
        $where['tb_wms_allo.id'] = $allo_id;
        $where['tb_ms_cmn_cd.ETC'] = ['LIKE', "%{$user}%"];
        return $this->model->table('tb_wms_allo,tb_ms_cmn_cd')
            ->where($where)
            ->whereString('tb_ms_cmn_cd.CD = tb_wms_allo.allo_in_team')
            ->getField('tb_ms_cmn_cd.ETC');
    }

    /**
     * @param $allo_id
     *
     * @throws Exception
     */
    private function updateAlloStateToComplete($allo_id)
    {
        $allo_where['id'] = $allo_id;
        $allo_where['state'] = 'N001970603';
        $allo_save['state'] = 'N001970400';
        if (!$this->model->table('tb_wms_allo')
            ->where($allo_where)
            ->save($allo_save)) {
            throw new Exception(L('更新调拨状态单异常'));
        }
    }

    /**
     * @param $allo_id
     *
     * @return array|mixed
     */
    public function getRemainAlloOccupy($allo_id)
    {
        $sql = "SELECT
                    tb_wms_batch_order.SKU_ID,
                    tb_wms_batch_order.occupy_num,
                    tb_wms_batch_order.vir_type,
                    tb_wms_batch_order.use_type,
                    tb_wms_batch_order.occupy_num
                FROM
                    tb_wms_allo,
                    tb_wms_batch_order
                WHERE
                    tb_wms_allo.allo_no = tb_wms_batch_order.ORD_ID
                AND tb_wms_batch_order.use_type = 1
                AND tb_wms_batch_order.occupy_num > 0
                AND tb_wms_allo.id = '{$allo_id}'";
        return $this->model->query($sql);
    }
    /**
     * @param $allo_id
     *
     * @return array|mixed
     */
    public function getRemainAlloOccupyAttr($allo_id)
    {
        $sql = "SELECT
                    tb_wms_batch_order.SKU_ID,
                    tb_wms_batch_order.occupy_num,
                    tb_wms_batch_order.vir_type,
                    tb_wms_batch_order.use_type,
                    tb_wms_batch_order.occupy_num,
                    tb_wms_allo_attribution.change_order_no
                FROM
                    tb_wms_allo_attribution,
                    tb_wms_batch_order
                WHERE
                    tb_wms_allo_attribution.change_order_no = tb_wms_batch_order.ORD_ID
                AND tb_wms_batch_order.use_type = 1
                AND tb_wms_batch_order.occupy_num > 0
                AND tb_wms_allo_attribution.allo_id =  '{$allo_id}'";
        return $this->model->query($sql);
    }
    public function updateOutBillId($out_stock_id, $bill_ids)
    {
        $where['id'] = $out_stock_id;
        $save['bill_ids'] = implode(',', array_unique($bill_ids));
        return $this->model->table('tb_wms_allo_new_out_stocks')
            ->where($where)
            ->save($save);
    }

    public function updateInBillId($in_stock_id, $bill_ids)
    {
        $where['id'] = $in_stock_id;
        $save['bill_ids'] = implode(',', array_unique($bill_ids));
        return $this->model->table('tb_wms_allo_new_in_stocks')
            ->where($where)
            ->save($save);
    }

    public function updateOutAndWorkState($allo_id)
    {
        $where['allo_Id'] = $allo_id;
        $save['is_used_amount'] = 1;
        $save['updated_by'] = DataModel::userNamePinyin();
        $save['updated_at'] = DateModel::now();
        $save_out = $this->model->table('tb_wms_allo_new_out_stocks')
            ->where($where)
            ->save($save);
        if (false === $save_out) {
            throw new Exception('更新出库标记失败');
        }
        $save_work = $this->model->table('tb_wms_allo_new_works')
            ->where($where)
            ->save($save);
        if (false === $save_work) {
            throw new Exception('更新作业标记失败');
        }
    }

    /**
     * 废弃，改调用AllocationExtendAttributionRepository.approval
     * @param $data
     * @return mixed
     */
    public function approval($data)
    {
        $where['tb_wms_allo_attribution.id'] = $data['id'];
        $where['tb_wms_allo_attribution.review_type_cd'] = 'N003000001';
        $now = DateModel::now();
        if ('N003000004' == $data['review_type_cd']) {
            $save['tb_wms_allo_attribution.cancel_by'] = DataModel::userNamePinyin();
            $save['tb_wms_allo_attribution.cancel_at'] = $now;
        } else {
            if ('ErpAdmin' != DataModel::userNamePinyin()) {
                $where['tb_wms_allo_attribution.reviewer_by'] = DataModel::userNamePinyin();
            }
            $save['tb_wms_allo_attribution.review_by'] = DataModel::userNamePinyin();
            $save['tb_wms_allo_attribution.review_at'] = $now;
        }
        $save['tb_wms_allo_attribution.review_type_cd'] = $data['review_type_cd'];
        $save['tb_wms_allo_attribution.updated_by'] = DataModel::userNamePinyin();
        $save['tb_wms_allo_attribution.updated_at'] = $now;
        return $this->model->table('tb_wms_allo_attribution,tb_wms_allo_attribution_sku')
        ->where($where)
        ->save($save);
    }
    public function attribution($allo_attribution_id, $change_order_no = null)
    {
        $field = ['tb_wms_allo_attribution.*'];
        if (!empty($allo_attribution_id)) {
            $where['tb_wms_allo_attribution.id'] = $allo_attribution_id;
        }
        if (!empty($change_order_no)) {
            $where['tb_wms_allo_attribution.change_order_no'] = $change_order_no;
        }
        return $this->model->table('tb_wms_allo_attribution')
        ->field($field)
            ->where($where)
            ->find();
    }
    public function getAlloNoById($allo_id){
        return $this->model->table('tb_wms_allo')
        ->where(['id'=>$allo_id])
        ->getField('allo_no');
    }

    public function updateAlloChild($data, $allo_id)
    {
       foreach($data as $value){
            $re = $this->model->table('tb_wms_allo_child')
            ->where(['allo_id' => $allo_id,'sku_id'=>$value['skuId']])
            ->save(['is_write_sku_num'=>1, 'batch_id'=>$value['batchId']]);
            if(empty($re)){
                throw new Exception(L('回写批次失败'));
            }
       }
    }


    //时效数据前置条件
    public function getEffectiveCondition($type, $recent_days){
        $recent_days = empty($recent_days) ? 30 : $recent_days;
        $start_time = date("Y-m-d", strtotime('-'.$recent_days.' day',time()));
       
        $where['b.type'] = $type;
        $where['a.state'] = 'N001970400';
        $where['a.allo_in_team'] = 'N001282800';
        $where['a.create_time'] = ['egt', $start_time];
        //$where["DATE_FORMAT(a.create_time,'%Y-%m')"] = $month;
        //$where['a.create_time'][] = ['egt', $month.'-01 00:00:00'];
        //$where['a.create_time'][] = ['elt', $month.'-31 23:59:59'];
        $where['a.allo_out_warehouse'] = ['in',self::$warehouse];
        $where['f.language'] = 'N000920100';
        //$where['a.allo_no'] = 'DB202101270003';
        return $where;
    }


    //时效数据
    public function makeInEffective(){
        $recent_days = $_GET['recent_days'];
//        preg_match('/\d{4}-\d{2}$/',$month, $match);
//        if(empty($match)){
//            echo "请传入一个月份，如2020-02";
//            return false;
//        }

        $where = $this->getEffectiveCondition(1, $recent_days);
        $stream = $this->getInStreamData($where);
        //计算该批次该sku 的重量体积等
        $batch_ids = array_values(array_unique(array_column($stream, 'batch_id')));
        $stream = $this->getTotalNumByAlloNo($stream, $batch_ids,'id');

        //入库的对应出库时效相关
        $stream = $this->getInStockTime($stream);

        //数据处理完毕，插入数据库
        $this->insertData($stream);
    }


    //初始数据
    public function getInStreamData($where) {
        $field = [
            'a.id as allo_id,a.create_time,a.update_time,a.allo_no,a.allo_in_team,a.allo_in_warehouse,a.allo_out_warehouse,a.create_user,
             b.type,b.id as tb_bill_id,c.storage_date,c.pur_storage_date,b.zd_date as in_stock_date,
             d.all_total_inventory,d.batch_code,d.SKU_ID as sku_id,d.sale_team_code,d.small_sale_team_code,d.id as batch_id,e.sku_length,e.sku_width,e.sku_height,e.sku_weight,f.spu_name,
             g.total_weight,g.total_volume,h.M_NAME as create_user_name'
        ];
        $product_table = PMS_DATABASE;
        $stream_data = $this->model->table('tb_wms_allo')->alias('a')
            ->join('left join tb_wms_bill  b on a.allo_no = b.link_bill_id')
            ->join('left join tb_wms_allo_new_works  g on a.id = g.allo_id')
            ->join('left join bbm_admin h on a.create_user = h.m_id')
            ->join('left join tb_wms_stream c on b.id = c.bill_id')
            ->join('left join tb_wms_batch d on b.id = d.bill_id')
            ->join("left join $product_table.product_sku e on d.SKU_ID = e.sku_id")
            ->join("left join $product_table.product_detail f on e.spu_id = f.spu_id")
            ->where($where)
            ->field($field)
            ->order('a.id desc')
            ->select();
        return $stream_data;
    }


    //多个单据/流 共同组成一个调拨单，查询或计算他单个批次单个sku的数量，加权体积，加权重量
    public function getTotalNumByAlloNo($stream, $batch_ids, $batch_column){
        $res = $this->model->table('tb_wms_batch')->where([$batch_column=>['in', $batch_ids]])->field("id,all_total_inventory,SKU_ID")->select();
        //计算一个调拨单的总数量和体积
        $this_allo_data = [];
        foreach($stream as $tk=>$tv){
            foreach($res as $k=>$v){
                if($tv['batch_id'] == $v['id']){
                    $this_allo_data[$tv['allo_no']]['all_sku_weight'] += $tv['sku_weight']*$v['all_total_inventory']/1000;
                    $this_allo_data[$tv['allo_no']]['all_sku_volume'] += $tv['sku_length'] * $tv['sku_width'] * $tv['sku_height']*$v['all_total_inventory']/1000/1000;
                }
            }
        }

        //计算一个批次下一个sku的体积重量加权值
        foreach($stream as $tk2=>$tv2){
            $stream[$tk2]['cal_weight'] = $tv2['total_weight'] / $this_allo_data[$tv2['allo_no']]['all_sku_weight'] * $tv2['sku_weight']/1000;
            $stream[$tk2]['cal_volume'] = $tv2['total_volume'] / $this_allo_data[$tv2['allo_no']]['all_sku_volume'] * $tv2['sku_length'] * $tv2['sku_width'] * $tv2['sku_height']/1000/1000;
        }

        return $stream;
    }


    //计算每个批次 sku 下入库对应的出库的相关时效
    public function getInStockTime($stream) {
        #1. 通过入库单据找入库表对应的入库id
        $tb_bill_ids = array_column($stream, 'tb_bill_id');
        $condition = "";
        $in_sql = "select allo_id,id,bill_ids from tb_wms_allo_new_in_stocks where ";
        foreach($tb_bill_ids as $sk=>$sv){
            $condition .= " or FIND_IN_SET('$sv',bill_ids)";
        }
        $condition = trim($condition, ' or');
        $in_id_result = $this->model->query($in_sql.$condition);
        foreach($stream as $mk=>$mv){
            foreach($in_id_result as $tk=>$tv){
                if(strpos($tv['bill_ids'], $mv['tb_bill_id']) !== false  && $tv['allo_id'] == $mv['allo_id'] ){
                    $stream[$mk]['in_stocks_id'] = $tv['id'];
                }
            }
        }
        #2. 通过调拨id，入库id，sku 去入库商品表绑定表找出库id
        $out_info_sql = "select allo_id,out_stock_id,sku_id,in_stocks_id from tb_wms_allo_new_in_stock_guds where ";
        $out_info_condition = "";
        foreach($stream as $ek=>$ev){
            if(!empty($ev['in_stocks_id'])){
                $out_info_condition .= " or (allo_id = {$ev['allo_id']} and in_stocks_id = {$ev['in_stocks_id']} and sku_id  = {$ev['sku_id']} )";
            }
        }
        $out_info_condition = trim($out_info_condition, ' or');
        $out_id_result = $this->model->query($out_info_sql.$out_info_condition);

        foreach($stream as $smk=>$smv) {
            foreach($out_id_result as $otk=>$otv){
                if($otv['allo_id'] == $smv['allo_id'] && $otv['sku_id'] == $smv['sku_id'] && $otv['in_stocks_id'] == $smv['in_stocks_id']){
                    $stream[$smk]['out_stocks_id'] = $otv['out_stock_id'];
                }
            }
        }
        #3. 出库表查出出库的物流信息，并通过连表，从从单据表匹配出库的一个或多个出库单据数据，任取一个单据的zd_date 就是出库的出库时间
        $out_stock_ids = implode(array_filter(array_column($stream, 'out_stocks_id')),',');
        $out_stock_bills_sql = "select a.id as out_id,a.transport_company_id,a.planned_transportation_channel_cd,bill_ids,b.zd_date,c.SP_NAME AS transport_company_name  from tb_wms_allo_new_out_stocks a left join tb_wms_bill b ON FIND_IN_SET(b.id,a.bill_ids) left join tb_crm_sp_supplier c on a.transport_company_id = c.ID where a.id in ($out_stock_ids)";

        $out_stock_bill = $this->model->query($out_stock_bills_sql);
        foreach ($stream as $rmk => $rmv){
            foreach($out_stock_bill as $osv){
                if($rmv['out_stocks_id'] == $osv['out_id']){
                    $stream[$rmk]['out_stock_date'] = $osv['zd_date'];
                    $stream[$rmk]['transport_company_id'] = $osv['transport_company_id'];
                    $stream[$rmk]['transport_company_name'] = $osv['transport_company_name'];
                    $stream[$rmk]['planned_transportation_channel_cd'] = $osv['planned_transportation_channel_cd'];
                    $stream[$rmk]['in_warehouse_days'] = ceil(round((strtotime($osv['zd_date']) - strtotime($rmv['pur_storage_date']))/3600/24,2));
                    $stream[$rmk]['transport_days'] =  ceil(round((strtotime($rmv['in_stock_date']) - strtotime($osv['zd_date']))/3600/24,2));
                    $stream[$rmk]['all_days'] =  ceil(round((strtotime($rmv['in_stock_date']) - strtotime($rmv['pur_storage_date']))/3600/24,2));

                }
            }
        }
        return $stream;
    }


    public function insertData($stream){
        //数据处理完毕，插入数据库
        foreach($stream as $ink=>$inv) {
            $insert['allo_no'] = $inv['allo_no'];
            $insert['bill_id'] = $inv['tb_bill_id'];
            $insert['type'] = $inv['type'];
            $insert['sale_team'] = $inv['sale_team_code'];
            $insert['allo_in_warehouse'] = $inv['allo_in_warehouse'];
            $insert['allo_out_warehouse'] = $inv['allo_out_warehouse'];
            $insert['create_user'] = $inv['create_user_name'];
            $insert['sku_id'] = $inv['sku_id'];
            $insert['batch_id'] = $inv['batch_id'];
            $insert['small_sale_team_code'] = $inv['small_sale_team_code'];
            $insert['goods_name'] = $inv['spu_name'];
            $insert['batch_code'] = $inv['batch_code'];
            $insert['amount'] = $inv['all_total_inventory'];
            $insert['volume'] = $inv['cal_volume'];
            $insert['weight'] = $inv['cal_weight'];
            $insert['allo_create_time'] = $inv['create_time'];
            $insert['allo_out_time'] = $inv['out_stock_date'];
            $insert['transport_company'] = $inv['transport_company_id'];
            $insert['transport_company_name'] = $inv['transport_company_name'];
            $insert['transport_type'] = $inv['planned_transportation_channel_cd'];
            $insert['allo_in_time'] = $inv['in_stock_date'];
            $insert['purchase_in_time'] = $inv['pur_storage_date'];
            $insert['in_warehouse_days'] = $inv['in_warehouse_days'];
            $insert['transport_days'] = $inv['transport_days'];
            $insert['all_days'] = $inv['all_days'];
            $insert['created_at'] = date("Y-m-d H:i:s");
            $insert['allo_finish_time'] = $inv['update_time'];

            $res = $this->model->table('tb_wms_sku_effective')->add($insert);
            if($res > 0){
                echo ($ink+1)."allow_no-bill_id-sku_id-batch_code: {$inv['allow_no']}_{$inv['bill_id']}_{$inv['sku']}_{$inv['batch_code']} has been insert into mysql \n";
            }
        }
    }


    //时效列表数据筛选
    public function getEffectiveList($params){
        $page_size = $params['page_size'] ? $params['page_size'] : 20;
        $page = $params['page'] ? $params['page'] : 1;

        if(!empty($params['allo_no'])){
            $where['allo_no'] = ['in', $params['allo_no']];
        }
        if(!empty($params['transport_type'])){
            $where['transport_type'] = ['in', $params['transport_type']];
        }
        if(!empty($params['small_sale_team_code'])){
            $where['small_sale_team_code'] = ['in', $params['small_sale_team_code']];
        }
        if(!empty($params['allo_in_warehouse'])){
            $where['allo_in_warehouse'] = ['in', $params['allo_in_warehouse']];
        }
        if(!empty($params['allo_out_warehouse'])){
            $where['allo_out_warehouse'] =  ['in', $params['allo_out_warehouse']];
        }
        if(!empty($params['transport_company'])){
            $where['transport_company'] =  ['in', $params['transport_company']];
        }
        if(!empty($params['lunch_start_time'])){
            $where['allo_create_time'] = array(array('egt', $params ['lunch_start_time'] . ' 00:00:00'), array('elt', $params ['lunch_end_time'] . ' 59:59:59'), 'and');
        }
        //完成时间
        if ($params ['finish_start_time']) {
            $where['allo_finish_time'] = array(array('egt', $params ['finish_start_time'] . ' 00:00:00'), array('elt', $params ['finish_end_time'] . ' 59:59:59'), 'and');
        }
        $where['sale_team'] = 'N001282800';
        $count = $this->model->table('tb_wms_sku_effective')->where($where)->count();
        $start = ($page-1) * $page_size;
        $list = $this->model->table('tb_wms_sku_effective')->where($where)->order('allo_no desc' )->limit($start, $page_size)->select();
        $list = $this->getSomeName($list);
        return ['list'=>$list,'total'=>$count];
    }


    //处理下名称
    public function getSomeName($list, $is_export=false){
        foreach($list as $lk=>$lv){
            $list[$lk]['allo_in_warehouse_name'] = cdVal($lv['allo_in_warehouse']);
            $list[$lk]['allo_out_warehouse_name'] = cdVal($lv['allo_out_warehouse']);
            $list[$lk]['transport_type_name'] = cdVal($lv['transport_type']);
            $list[$lk]['sale_team_name'] = cdVal($lv['sale_team']);
            $list[$lk]['small_sale_team_name'] = cdVal($lv['small_sale_team_code']);
            if($is_export){
                $list[$lk]['batch_code'] =  $list[$lk]['batch_code']."\t";
            }
        }
        return $list;
    }

    //根据账户名获取wid
    public function getWidByIds($user_ids)
    {
        $user_ids = (array) $user_ids;
        if (empty($user_ids)) {
            return;
        }
        $user_info = $this->model->table('bbm_admin')
            ->field('b.wid, bbm_admin.M_NAME')
            ->join('left join tb_hr_empl_wx as b on bbm_admin.empl_id = b.uid')
            ->where(['bbm_admin.M_ID' => ['in', $user_ids]])
            ->group('b.uid')
            ->select(); // 防止多个相同账号的情况（比如个别用户先辞职后重新入职，tb_hr_empl_wx.uid有出现一对多的情况）
        $user_info = array_unique(array_column($user_info, 'wid'));
        return $user_info;
    }
}

