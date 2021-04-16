<?php
/**
 * User: yangsu
 * Date: 19/8/17
 * Time: 17:38
 */

class B2bReceivableRepository extends Repository
{
    public function getList($where, $limit, $is_excel)
    {
        $field = [
            'tb_report_b2b_receivable_aggregation.*',
            'tb_report_b2b_receivable_aggregation.ship_id AS ship_no',
            'tb_b2b_info.PO_ID AS b2b_order_no',
            'tb_b2b_info.THR_PO_ID AS po_no',
            'tb_b2b_info.CLIENT_NAME AS client_name',
            'tb_b2b_info.po_user',
            'tb_b2b_info.our_company',
            'tb_b2b_info.po_currency AS order_currency_cd',
            'tb_report_b2b_receivable_aggregation.from_today',
            'tb_b2b_info.SALES_TEAM',
            'tb_report_b2b_receivable_aggregation.order_id',
        ];
        $where_string = 'tb_report_b2b_receivable_aggregation.order_id = tb_b2b_info.order_id AND tb_b2b_receivable.order_id = tb_report_b2b_receivable_aggregation.order_id AND tb_b2b_receivable.receivable_status != \'N002540300\' ';
        $query = $this->model->table('tb_report_b2b_receivable_aggregation,tb_b2b_info,tb_b2b_receivable')
            ->field($field)
            ->where($where)
            ->where($where_string, null, true)
            ->order('from_today desc');
        $query_copy = clone $query;
        $query_sum = clone $query;
        $pages['total'] = $query->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page'] = $limit[1];
        $sum_current_receivable_cny = $sum_current_receivable_usd = 0;
        if (false === $is_excel) {
            $sum_res_db = $query_sum->select();
            $sum_current_receivable_cny = array_sum(array_column($sum_res_db, 'remaining_receivabl_cny'));
            $sum_current_receivable_usd = array_sum(array_column($sum_res_db, 'remaining_receivabl_usd'));
            $query_copy->limit($limit[0], $limit[1]);
        }
        $db_res = $query_copy->select();
        $pages['current_page'] += 1;
        return [$db_res, $pages, $sum_current_receivable_cny, $sum_current_receivable_usd];
    }

    public function addLevelOne($data)
    {
        return $this->model->table('tb_report_b2b_receivable')
            ->add($data);
    }

    public function addLevelOneAll($data)
    {
        if (empty($data)) {
            return false;
        }
        return $this->model->table('tb_report_b2b_receivable')
            ->addAll($data);
    }

    public function deleteLevelOne($order_id)
    {
        $where['order_id'] = $order_id;
        return $this->model->table('tb_report_b2b_receivable')
            ->where($where)
            ->delete();
    }
    
    public function deleteLevelSecond($order_id)
    {
        $where['order_id'] = $order_id;
        return $this->model->table('tb_report_b2b_receivable_aggregation')
            ->where($where)
            ->delete();
    }

    public function addLevelSecond($data)
    {
        return $this->model->table('tb_report_b2b_receivable_aggregation')
            ->addAll($data);
    }

    public function getB2bReceivable($order_id)
    {
        $field = [
            'tb_report_b2b_receivable.*',
            'tb_b2b_info.PO_ID',
            'tb_b2b_info.THR_PO_ID',
            'tb_b2b_info.po_currency',
        ];
        $where['tb_report_b2b_receivable.order_id'] = $order_id;
        $where_string = 'tb_report_b2b_receivable.order_id = tb_b2b_info.order_id';
        return $this->model->table('tb_report_b2b_receivable,tb_b2b_info')
            ->field($field)
            ->where($where)
            ->where($where_string, null, true)
            ->order('tb_report_b2b_receivable.created_at')
            ->select();
    }

    public function getAllOrderId()
    {
        $where['create_time'] = ['GT', '2018-07-31'];
        return $this->model->table('tb_b2b_order')
            ->field(['id'])
            ->where($where)
            ->select();
    }

    public function getThisShipList($order_id)
    {
        $sql = "SELECT
                    tb_b2b_ship_list.order_id,
                    tb_b2b_ship_list.ID AS ship_id,
                    'ship' AS type,
                    tb_b2b_ship_list.SUBMIT_TIME AS created_at,
                    SUM(
                        IFNULL(
                            tb_b2b_ship_goods.DELIVERED_NUM,
                            0
                        ) * tb_b2b_goods_group.price_goods
                    ) AS amount,
                   tb_b2b_ship_list.AUTHOR AS created_by
                FROM
                    (
                        tb_b2b_ship_list,
                        tb_b2b_ship_goods
                    )
                LEFT JOIN (
                    SELECT
                        *
                    FROM
                        tb_b2b_goods
                    WHERE
                        ORDER_ID = '{$order_id}'
                    GROUP BY
                        SKU_ID
                ) AS tb_b2b_goods_group ON tb_b2b_goods_group.SKU_ID = tb_b2b_ship_goods.sku_show
                WHERE
                    tb_b2b_ship_list.order_id = '{$order_id}'
                AND tb_b2b_ship_list.ID = tb_b2b_ship_goods.SHIP_ID
                GROUP BY
	                tb_b2b_ship_list.ID";
        return $this->model->query($sql);
    }

    public function getThisWarehouseList($order_id)
    {
        $sql = "SELECT
                    tb_b2b_warehouse_list.ORDER_ID AS order_id,
                    tb_b2b_warehouse_list.SHIP_LIST_ID AS ship_id,
                    'warehouse' AS type,
                    tb_b2b_warehouse_list.WAREING_DATE AS created_at,
                    SUM(
                        IFNULL(
                            tb_b2b_warehousing_goods.TOBE_WAREHOUSING_NUM - tb_b2b_warehousing_goods.DELIVERED_NUM,
                            0
                        ) * tb_b2b_goods_group.price_goods
                    ) AS amount,
                   tb_b2b_warehouse_list.AUTHOR AS created_by
                FROM
                    (
                        tb_b2b_warehouse_list,
                        tb_b2b_warehousing_goods
                    )
                LEFT JOIN (
                    SELECT
                        *
                    FROM
                        tb_b2b_goods
                    WHERE
                        ORDER_ID = '{$order_id}'
                    GROUP BY
                        SKU_ID
                ) AS tb_b2b_goods_group ON tb_b2b_goods_group.SKU_ID = tb_b2b_warehousing_goods.sku_show
                WHERE
                    tb_b2b_warehouse_list.ORDER_ID = '{$order_id}'
                AND tb_b2b_warehouse_list.ID = tb_b2b_warehousing_goods.warehousing_id
                AND tb_b2b_warehouse_list.status = 2
                ";
        return $this->model->query($sql);
    }

    public function getThisReturnList($order_id)
    {
        $sql = "SELECT
                    tb_b2b_return.order_id,
                    tb_b2b_return.ship_id,
                    'return' AS type,
                    tb_b2b_return.created_at,
                    SUM(
                        IFNULL(
                            tb_b2b_return_goods.warehouse_num_quality + tb_b2b_return_goods.warehouse_num_broken,
                            0
                        ) * tb_b2b_goods_group.price_goods
                    ) AS amount,
                   tb_b2b_return.created_by
                FROM
                    (
                        tb_b2b_return,
                        tb_b2b_return_goods
                    )
                LEFT JOIN (
                    SELECT
                        *
                    FROM
                        tb_b2b_goods
                    WHERE
                        ORDER_ID = '{$order_id}'
                    GROUP BY
                        SKU_ID
                ) AS tb_b2b_goods_group ON tb_b2b_goods_group.SKU_ID = tb_b2b_return_goods.sku_id
                WHERE
                    tb_b2b_return.order_id = '{$order_id}'
                AND tb_b2b_return.id = tb_b2b_return_goods.return_id
                ";
        return $this->model->query($sql);
    }

    public function getThisClaimList($order_id)
    {
        $sql = "SELECT
                    order_id,       
                    'claim' AS type,           
                    sum(summary_amount) AS amount,
                    created_by,
                    created_at                  
                FROM
                    `tb_fin_claim`
                WHERE
                    `order_type` LIKE 'N001950200%'
                AND order_id = {$order_id}";
        return $this->model->query($sql);
    }

    public function getThisReceiptList($order_id)
    {
        $sql = "SELECT
                    ORDER_ID AS order_id,
                    'receipt' AS type,
                    SUM(actual_payment_amount) AS amount,
                    operator_id AS created_by,
                    create_time AS created_at
                FROM
                    tb_b2b_receipt
                WHERE
                    (
                        transaction_type != 1
                        OR transaction_type IS NULL
                    )
                AND receipt_operation_status = 1
                AND ORDER_ID = '{$order_id}'";
        return $this->model->query($sql);
    }

    public function updateFromToday()
    {
        $b2b_receivable_aggregations = $this->model->table('tb_report_b2b_receivable_aggregation')
            ->field('id,due_date')
            ->select();
        $now_date = new DateTime(date('Y-m-d'));
        foreach ($b2b_receivable_aggregations as $datum) {
            $temp_where['id'] = $datum['id'];
            $temp_date = new DateTime(date('Y-m-d', strtotime($datum['due_date'])));
            $temp_save['from_today'] = $now_date->diff($temp_date)->days;
            $this->model->table('tb_report_b2b_receivable_aggregation')
                ->where($temp_where)
                ->save($temp_save);
            unset($temp_where, $temp_save);
        }
    }
}