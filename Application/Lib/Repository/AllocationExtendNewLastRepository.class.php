<?php
/**
 * User: yangsu
 * Date: 19/6/17
 * Time: 17:38
 */

@import("@.Model.StringModel");

use Application\Lib\Model\StringModel;

class AllocationExtendNewLastRepository extends Repository
{
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
        ];
        return $this->model->table('(tb_wms_allo,tb_wms_allo_new_status)')
            ->field($field)
            ->join('LEFT JOIN tb_con_division_warehouse AS dw_out on dw_out.warehouse_cd = tb_wms_allo.allo_out_warehouse')
            ->join('LEFT JOIN tb_con_division_warehouse AS dw_in on dw_in.warehouse_cd = tb_wms_allo.allo_in_warehouse')
            ->join('LEFT JOIN tb_wms_warehouse AS w_out on w_out.CD = tb_wms_allo.allo_out_warehouse')
            ->join('LEFT JOIN tb_wms_warehouse AS w_in on w_in.CD = tb_wms_allo.allo_in_warehouse')
            ->join('LEFT JOIN tb_ms_cmn_cd AS cd_1 on cd_1.CD = tb_wms_allo.allo_in_team')
            ->join('LEFT JOIN bbm_admin ON bbm_admin.M_ID = tb_wms_allo.create_user')
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

    /**
     * @param $allo_id
     *
     * @return mixed
     */
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
    public function submitInStockInfo($allo_id, $data)
    {
        $add_out_stock_infos = $data;
        unset($data['reason_difference']);
        $add_out_stock_infos['allo_id'] = $allo_id;
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
    public function submitInStockGoods($allo_id, $in_stocks_id, $data)
    {
        $user_name_pinyin = DataModel::userNamePinyin();
        foreach ($data as $datum) {
            unset($datum['upc_id']);
            $datum['allo_id'] = $allo_id;
            $datum['in_stocks_id'] = $in_stocks_id;
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

    public function outboundTagCompletion($allo_id, $reason_difference)
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
}