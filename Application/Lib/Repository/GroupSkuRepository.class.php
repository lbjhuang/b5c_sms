<?php
/**
 * User: yangsu
 * Date: 18/10/24
 * Time: 15:19
 */

@import("@.Model.Orm.TbWmsGroupBill");

class GroupSkuRepository extends Repository
{
    public $order_list_column = [
        ['bill_code', '单据号'],
        ['group_type_val', '类型'],
        ['group_sku_id', '组合SKU编码'],
        ['upc_id', '条形码'],
        ['sku_name', '商品名称'],
        ['warehouse_cd_val', '仓库'],
        ['sale_team_cd_val', '销售团队'],
        ['small_sale_team_code_val', '销售小团队'],
        ['all_num', '数量'],
        ['created_by', '申请人'],
        ['audit_user', '审核人'],
        ['created_at', '申请时间'],
        ['audit_time', '审核时间'],
        ['current_in_num', '在库数量'],
        ['current_take_num', '占用数量'],
        ['remaining_num', '剩余库存数量'],
    ];

    /**
     * @param array $where_arr
     * @param $paginateper_page
     * @param $current_page
     * @param $where_other_arr 其他筛选条件(如新增归属店铺查询)
     * @return mixed
     */
    public function searchOrderList($where_arr = [], $paginateper_page, $current_page, $where_other_arr)
    {
        $Model = new SlaveModel();
        $data['total'] = count($this->getOrderListCount($where_arr, $Model, $where_other_arr));
        $res = $Model->table('(tb_wms_group_bill)')
            ->field('tb_wms_group_bill.sku_id,
            tb_wms_group_bill.warehouse_cd,
            tb_wms_group_bill.sale_team_cd,
            tb_wms_group_bill.small_sale_team_code,
            history_num,
            current_in_num,
            current_take_num,
            remaining_num,
            
            IFNULL(tb_audit.count_audit,0) AS approval_num,  
            all_audit_id
            ')
            ->JOIN('LEFT JOIN (SELECT GROUP_CONCAT(tb_wms_group_bill.id) AS all_audit_id,sku_id,warehouse_cd,sale_team_cd,count(id) AS count_audit FROM tb_wms_group_bill WHERE audit_status = \'N002470100\' GROUP BY sku_id,warehouse_cd,sale_team_cd) AS tb_audit ON  tb_audit.sku_id = tb_wms_group_bill.sku_id 
                 AND tb_audit.sale_team_cd = tb_wms_group_bill.sale_team_cd  
                 AND tb_audit.warehouse_cd = tb_wms_group_bill.warehouse_cd')
            ->JOIN($this->getBatchLeftSql($where_other_arr))
            ->where($where_arr)
            ->group('tb_wms_group_bill.sku_id,tb_wms_group_bill.warehouse_cd,tb_wms_group_bill.sale_team_cd')
            ->order('approval_num DESC,tb_wms_group_bill.created_at DESC');
        if (isset($paginateper_page) && $current_page > 0) {
            $res->limit($paginateper_page, $current_page);
        }
        $data['data'] = $res->select();
        return $data;
    }

    public function getGroupSkuToSku($group_sku_id)
    {
        $Model = new Model();
        $where['cb_sku_id'] = $group_sku_id;
        $sku_arr = $Model->table(PMS_DATABASE . '.product_combine_map')
            ->field('cb_sku_id,sku_id,number')
            ->where($where)
            ->select();
        return $sku_arr;
    }

    public function getOrderCodeToSku($order_code)
    {
        return TbWmsGroupBill::where('bill_code', $order_code)
            ->select('sku_id')
            ->first()['sku_id'];
    }

    public function getGroupBillIdCount($group_bill_id)
    {
        return TbWmsGroupBill::find($group_bill_id);
    }

    public function getDetaileds($request_data)
    {
        $res = TbWmsGroupBill::select('*', 'all_num AS num')
            ->where([
                'sku_id' => $request_data['group_sku_id'],
                'warehouse_cd' => $request_data['warehouse_cd'],
                'sale_team_cd' => $request_data['sale_team_cd'],
            ]);
        if ($request_data['small_sale_team_code']) {
            $res = $res->where('small_sale_team_code', '=', $request_data['small_sale_team_code']);
        }
        if ($request_data['time_begin'] && $request_data['time_end']) {
            $res = $res->whereBetween($request_data['time_type'], [$request_data['time_begin'], $request_data['time_end']]);
        } elseif ($request_data['time_begin']) {
            $res = $res->where($request_data['time_type'], '>=', $request_data['time_begin']);
        } elseif ($request_data['time_end']) {
            $res = $res->where($request_data['time_type'], '<=', $request_data['time_end']);
        }
        $res = $res->orderBy('audit_status', 'ASC')
            ->orderBy('created_at', 'DESC')
            ->get();
        if ($res) {
            $res = $res->toArray();
        }
        return $res;
    }

    /**
     * @param $where_arr
     * @param $Model
     * @param $where_other_arr
     * @return mixed
     */
    private function getOrderListCount($where_arr, $Model, $where_other_arr = [])
    {
        if (empty($where_arr)) {
            $res = $Model->table('(tb_wms_group_bill)')
                ->field('tb_wms_group_bill.id')
                ->group('tb_wms_group_bill.sku_id,tb_wms_group_bill.warehouse_cd,tb_wms_group_bill.sale_team_cd')
                ->select();
        }else{
            $res = $Model->table('(tb_wms_group_bill)')
                ->field('tb_wms_group_bill.id')
                ->JOIN('LEFT JOIN (SELECT GROUP_CONCAT(tb_wms_group_bill.id) AS all_audit_id,sku_id,warehouse_cd,sale_team_cd,count(id) AS count_audit FROM tb_wms_group_bill WHERE audit_status = \'N002470100\' GROUP BY sku_id,warehouse_cd,sale_team_cd) AS tb_audit ON  tb_audit.sku_id = tb_wms_group_bill.sku_id 
                 AND tb_audit.sale_team_cd = tb_wms_group_bill.sale_team_cd  
                 AND tb_audit.warehouse_cd = tb_wms_group_bill.warehouse_cd')
                ->JOIN($this->getBatchLeftSql($where_other_arr))
                ->where($where_arr)
                ->group('tb_wms_group_bill.sku_id,tb_wms_group_bill.warehouse_cd,tb_wms_group_bill.sale_team_cd')
                ->select();
        }
        if (!$res) {
            $res = [];
        }
        return $res;
    }

    public function listExcelData($where_arr)
    {
        $Model = new SlaveModel();
        $get_batch_sql = $this->getAllBatchSql();
        $db_data_arr = $Model->table('(tb_wms_group_bill)')
            ->field('tb_wms_group_bill.*,
            tb_wms_group_bill.sku_id AS group_sku_id,
            temp_tb_batch.history_num,
            temp_tb_batch.current_in_num,
            temp_tb_batch.current_take_num,
            temp_tb_batch.remaining_num')
            ->JOIN('LEFT JOIN (' . $get_batch_sql . ') AS temp_tb_batch 
                    ON temp_tb_batch.sku_id = tb_wms_group_bill.sku_id
                        AND temp_tb_batch.sale_team_cd = tb_wms_group_bill.sale_team_cd
                        AND temp_tb_batch.warehouse_cd = tb_wms_group_bill.warehouse_cd
		    ')
            ->where($where_arr)
            ->order('tb_wms_group_bill.created_at DESC')
            ->select();
        return $db_data_arr;
    }

    /**
     * @return string
     */
    private function getAllBatchSql()
    {
        return 'SELECT
                tb_wms_group_bill.sku_id,
                tb_wms_group_bill.warehouse_cd,
                tb_wms_group_bill.sale_team_cd,
                
                history_num,
                current_in_num,
                current_take_num,
                remaining_num,
                
                IFNULL(tb_audit.count_audit, 0) AS approval_num,
                `all_audit_id`
            FROM
                (
                  tb_wms_group_bill
                )
            LEFT JOIN (
                SELECT
                    GROUP_CONCAT(tb_wms_group_bill.id) AS all_audit_id,
                    sku_id,
                    warehouse_cd,
                    sale_team_cd,
                    count(id) AS count_audit
                FROM
                    tb_wms_group_bill
                WHERE
                    audit_status = \'N002470100\'
                GROUP BY
                    sku_id,
                    warehouse_cd,
                    sale_team_cd
            ) AS tb_audit ON tb_audit.sku_id = tb_wms_group_bill.sku_id
            AND tb_audit.sale_team_cd = tb_wms_group_bill.sale_team_cd
            AND tb_audit.warehouse_cd = tb_wms_group_bill.warehouse_cd
            ' . $this->getBatchLeftSql() . '
            GROUP BY
                tb_wms_group_bill.sku_id,
                tb_wms_group_bill.warehouse_cd,
                tb_wms_group_bill.sale_team_cd
            ORDER BY
                approval_num DESC,
                tb_wms_group_bill.created_at DESC';
    }

    /**
     * @return string
     * $where_arr array 补充归属店铺的查询（可选）后续如还有筛选条件，可以通过传数组不同的值，进行sql拼接处理
     */
    private function getBatchLeftSql($where_arr = [])
    {
        // 补充店铺筛选 #9485 组合商品打包增加店铺限制，如果有店铺参数，则筛选该店铺的可售数量总和，否则获取店铺值为空的总可售数量
        $store_sql = $where_arr['ascription_store'] ? "AND tb_wms_bill.ascription_store =
                        '" . $where_arr['ascription_store'] . "' " : "AND tb_wms_bill.ascription_store IS NULL";

        $store_sql = $where_arr['small_sale_team_code'] ? "AND tb_wms_batch.small_sale_team_code =
                        '" . $where_arr['small_sale_team_code'] . "' " : "AND (tb_wms_batch.small_sale_team_code IS NULL OR tb_wms_batch.small_sale_team_code = '')";

        $store_sql = $where_arr['list_search'] ? '' : $store_sql;

        return "LEFT JOIN (
                        SELECT
                            tb_wms_batch.all_total_inventory,
                            tb_wms_batch.total_inventory,
                            tb_wms_batch.occupied,
                            tb_wms_batch.available_for_sale_num,
                            tb_wms_batch.sku_id,
                            tb_wms_batch.sale_team_code,
                            tb_wms_batch.vir_type
                            , SUM(
                                tb_wms_batch.all_total_inventory
                            ) AS history_num,
                            IFNULL(
                                SUM(
                                    tb_wms_batch.total_inventory
                                ),
                                0
                            ) AS current_in_num,
                            IFNULL(
                                SUM(tb_wms_batch.occupied),
                                0
                            ) AS current_take_num,
                            SUM(
                                tb_wms_batch.available_for_sale_num
                            ) AS remaining_num,
                            tb_wms_bill.warehouse_id AS warehouse_cd
                        FROM
                            tb_wms_batch,
                            tb_wms_bill
                        WHERE
                            tb_wms_batch.bill_id = tb_wms_bill.id
                        ". $store_sql ."
                    GROUP BY
                        tb_wms_batch.sku_id,
                        tb_wms_bill.warehouse_id,
                        tb_wms_batch.sale_team_code
                    ) AS tb_wms_batch ON tb_wms_group_bill.sku_id = tb_wms_batch.SKU_ID
                    AND tb_wms_group_bill.sale_team_cd = tb_wms_batch.sale_team_code
                    AND tb_wms_batch.warehouse_cd = tb_wms_group_bill.warehouse_cd
                    AND tb_wms_batch.vir_type = 'N002440100'";
    }

}
