<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of OmsAfterSaleRepository
 *
 * @author Administrator
 */
class OmsAfterSaleRepository extends Repository{
    
    public $model;
    public function __construct($model) {
        parent::__construct();
        $this->model = $model;
    }

    //售后列表
    public function searchList($where_return_str, $where_reissue_str, $where_refund_str, $limit, $is_excel) {
        $sql_return = "SELECT tb_ms_store.STORE_NAME AS store_name,oo.REMARK_MSG as remark, r1.platform_country_code, r1.order_id, r2.*, '退货' AS type_name,oo.PAY_CURRENCY as pay_currency,oo.PAY_TOTAL_PRICE as pay_total_price
            FROM tb_op_order_after_sale_relevance r1
            LEFT JOIN
                (SELECT rr2.id, rr2.after_sale_no, rr2.order_no, rr2.status_code, '' AS child_order_no,
                rr2.return_reason AS reason, rr2.created_at, rr2.created_by, gg2.upc_id,gg2.sku_id,
                GROUP_CONCAT(gg2.sku_id) AS upc_str,GROUP_CONCAT(gg2.yet_return_num) AS num_str,
                '无' AS audit_opinion,
                '' AS attachment,
                '无' AS audit_status_cd_val,'' AS audit_status_cd, rr2.reply_status_code,
                '' as  refund_amount,'' as amount_currency_cd
                FROM tb_op_order_return rr2
                LEFT JOIN tb_op_order_return_goods gg2 ON rr2.id = gg2.return_id GROUP BY return_id)
            AS r2 
            ON r1.after_sale_id = r2.id
            LEFT JOIN tb_op_order oo ON oo.ORDER_ID = r1.order_id AND oo.PLAT_CD = r1.platform_country_code 
            LEFT JOIN tb_ms_store ON tb_ms_store.ID = oo.STORE_ID " . $where_return_str;
        $sql_reissue = "SELECT tb_ms_store.STORE_NAME AS store_name, oo.REMARK_MSG as remark, r1.platform_country_code, r1.order_id, r2.*, '补发' AS type_name,oo.PAY_CURRENCY as pay_currency,oo.PAY_TOTAL_PRICE as pay_total_price
            FROM tb_op_order_after_sale_relevance r1
            LEFT JOIN
                (SELECT rr2.id, rr2.after_sale_no, rr2.order_no, rr2.status_code, rr2.child_order_no,
                rr2.reissue_reason AS reason, rr2.created_at, rr2.created_by, gg2.upc_id,
                gg2.sku_id,GROUP_CONCAT(gg2.sku_id) AS upc_str,GROUP_CONCAT(gg2.yet_reissue_num) AS num_str,
                '无' AS audit_opinion,
                '' AS attachment,
                '无' AS audit_status_cd_val,'' AS audit_status_cd, '' as reply_status_code,
                '' as  refund_amount,'' as amount_currency_cd
                FROM tb_op_order_reissue rr2
                LEFT JOIN tb_op_order_reissue_goods gg2 ON rr2.id = gg2.reissue_id GROUP BY reissue_id)
            AS r2 
            ON r1.after_sale_id = r2.id
            LEFT JOIN tb_op_order oo ON oo.ORDER_ID = r1.order_id AND oo.PLAT_CD = r1.platform_country_code 
            LEFT JOIN tb_ms_store ON tb_ms_store.ID = oo.STORE_ID  ". $where_reissue_str;

        $sql_refund = "SELECT
           tb_ms_store.STORE_NAME AS store_name, 
           oo.REMARK_MSG as remark,
            r1.platform_country_code,
            r1.order_id,
            r2.*, '退款' AS type_name,oo.PAY_CURRENCY as pay_currency,oo.PAY_TOTAL_PRICE as pay_total_price
        FROM
            tb_op_order_after_sale_relevance r1
        LEFT JOIN (
            SELECT
                rr2.id,
                rr2.after_sale_no,
                rr2.order_no,
                rr2.status_code,
                '' AS child_order_no,
                gg2.refund_reason_cd AS reason,
                rr2.created_at,
                rr2.created_by,
                '' AS upc_id,
                GROUP_CONCAT(guds.B5C_SKU_ID) AS sku_id,
                GROUP_CONCAT(guds.B5C_SKU_ID) AS upc_str,
                GROUP_CONCAT(guds.ITEM_COUNT) AS num_str,
                rr2.audit_opinion,
                rr2.attachment,
                cd1.CD_VAL AS audit_status_cd_val,
                rr2.audit_status_cd,        
                '' as reply_status_code,
                gg2.refund_amount, gg2.amount_currency_cd
            FROM
                tb_op_order_refund rr2      
            LEFT JOIN tb_op_order_refund_detail gg2 ON rr2.id = gg2.refund_id
            LEFT JOIN tb_ms_cmn_cd cd1 ON rr2.audit_status_cd = cd1.CD
            LEFT JOIN tb_op_order_guds guds ON rr2.order_id = guds.ORDER_ID and rr2.platform_cd = guds.PLAT_CD GROUP BY guds.ORDER_ID,rr2.after_sale_no
        ) AS r2 ON r1.after_sale_id = r2.id
        LEFT JOIN tb_op_order oo ON oo.ORDER_ID = r1.order_id AND oo.PLAT_CD = r1.platform_country_code 
        LEFT JOIN tb_ms_store ON tb_ms_store.ID = oo.STORE_ID  ".$where_refund_str.
        " ORDER BY
            created_at DESC";

        $sql = $sql_return . ' UNION ' . $sql_reissue. ' UNION ' . $sql_refund;
        $pages['total']        = count($this->model->query($sql));
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        
        if (false === $is_excel) {
            $sql .= ' limit ' . implode(',', $limit);
        }
        $db_res = $this->model->query($sql);
        return [$db_res, $pages];
    }
    
    //搜索退货单信息公共方法(补充查询物流轨迹，获取平台)
    public function searchReturnList($where, $limit, $is_excel, $order = '') {
        $field = "oor.*, oor.status_code AS total_status_code, org.id as return_goods_id, org.upc_id,
            org.order_goods_num, org.over_return_num, org.sku_id,
            org.yet_return_num, org.warehouse_code, org.status_code,oor.order_id,
            org.refuse_warehouse_num, org.over_warehouse_num, org.yet_warehouse_num, ooasr.platform_country_code";
        $query = $this->model->table('tb_op_order_return oor')
            ->field($field)
            ->join('left join tb_op_order_return_goods org on oor.id = org.return_id')
            ->join('left join tb_op_order_after_sale_relevance ooasr on ooasr.after_sale_id = oor.id')
            ->where($where);
        $query_copy = clone $query;

        if ($order) {
            $query_copy = $query_copy->order($order);
        }

        $pages['total']        = $query->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        if (false === $is_excel) {
            $query_copy->limit($limit[0], $limit[1]);
        }
        $db_res = $query_copy->select();

        if (empty($db_res)) {
            $db_res = [];
        }
        $db_res = SkuModel::productInfo($db_res);
        return [$db_res, $pages];
    }
    
    //退货入库列表
    public function searchReturnWarehouseList($where, $limit, $is_excel) {
        $field = "oor.*, org.id as return_goods_id, org.upc_id,org.sku_id
            org.order_goods_num, org.over_return_num,
            org.yet_return_num, org.warehouse_code, org.status_code,
            orw.type, orw.warehouse_num, orw.warehouse_num_broken,
            orw.warehouse_num_refuse, orw.refuse_reason,
            orw.created_by as in_warehouse_user, orw.created_at as in_warehouse_date";

        $query = $this->model->table('tb_op_order_return_warehouse orw')
            ->field($field)
            ->join('left join tb_op_order_return_goods org on orw.return_goods_id = org.id')
            ->join('left join tb_op_order_return oor on org.return_id = oor.id')
            ->where($where);
        $query_copy = clone $query;

        $pages['total']        = $query->count();
        $pages['current_page'] = $limit[0];
        $pages['per_page']     = $limit[1];
        if (false === $is_excel) {
            $query_copy->limit($limit[0], $limit[1]);
        }
        $db_res = $query_copy->select();
        return [$db_res, $pages];
    }


    public function getRefundInfoById($id)
    {
        $db_res = $this->model->table('tb_op_order_refund r')
            ->field('r.*, rd.refund_id, rd.refund_account,rd.type, rd.order_pay_date,rd.current_date,rd.refund_channel_cd,rd.refund_user_name,rd.refund_amount,rd.amount_currency_cd,rd.sales_team_cd,rd.refund_reason_cd, store.STORE_NAME as store_name,store.company_cd')
            ->join('left join tb_op_order_refund_detail rd on r.id = rd.refund_id')
            ->join('left join tb_ms_store store on r.store_id = store.ID')
            ->where(['r.id' => $id])
            ->find();
        $db_res = CodeModel::autoCodeOneVal($db_res, ['refund_reason_cd']);
        if (empty($db_res['refund_reason_cd_val'])) {
            $db_res['refund_reason_cd_val'] = $db_res['refund_reason_cd'];
        }
        return $db_res;
    }

    public function getRefundInfo($order_id, $platform_cd)
    {
        $db_res = $this->model->table('tb_op_order_refund r')
            ->field('r.*,rd.*')
            ->join('left join tb_op_order_refund_detail rd on r.id = rd.refund_id')
            ->where(['r.order_id' => $order_id, 'r.platform_cd' => $platform_cd])
            ->select();
        $db_res = CodeModel::autoCodeTwoVal($db_res, ['refund_reason_cd']);
        return $db_res;
    }

    public function getOrderInfo($order_id, $platform_cd)
    {
        $db_res = $this->model->table('tb_op_order oo')
            ->field('oo.PLAT_NAME,oo.PLAT_CD,oo.ADDRESS_USER_NAME,STORE_ID,og.B5C_SKU_ID,og.ITEM_COUNT,oo.CHILD_ORDER_ID,oo.PARENT_ORDER_ID,oo.SEND_ORD_STATUS,oo.BWC_ORDER_STATUS')
            ->join('left join tb_op_order_guds og on oo.ORDER_ID = og.ORDER_ID AND oo.PLAT_CD = og.PLAT_CD')
            ->where(['oo.ORDER_ID' => $order_id, 'og.PLAT_CD' => $platform_cd])
            ->order('og.guds_type asc')
            ->select();
        return $db_res;
    }

    public function getNewestAfterSaleInfo($where_str)
    {
       $sql ="SELECT
            r.after_sale_no AS after_sale_no_refund,
            r.updated_at AS updated_at_refund,
            rr.after_sale_no AS after_sale_no_return,
            rr.updated_at AS updated_at_return,
            rrr.after_sale_no AS after_sale_no_reissue,
            rrr.updated_at AS updated_at_reissue
        FROM
            tb_op_order_after_sale_relevance rel
        LEFT JOIN tb_op_order_refund r ON rel.after_sale_id = r.id
        AND rel.type = 3
        AND r.status_code != 'N002800008'
        AND r.status_code != 'N002800014'
        LEFT JOIN tb_op_order_return rr ON rel.after_sale_id = rr.id 
        AND rel.type = 1
        AND rr.status_code != 'N002800008'
        AND rr.status_code != 'N002800017'
        LEFT JOIN tb_op_order_reissue rrr ON rel.after_sale_id = rrr.id
        AND rel.type = 2
        AND rrr.status_code != 'N002800008'
        AND rrr.status_code != 'N002800015'
        WHERE $where_str";
        $db_res = $this->model->query($sql);
        return $db_res;
    }

    public function getReissueInfos($where_str)
    {
        $where_str .= " and r.status_code != 'N002800008' and r.status_code != 'N002800015'";
        $where['_string'] = $where_str;
        $db_res = $this->model->table('tb_op_order_after_sale_relevance rel')
            ->field('rel.platform_country_code,r.*')
            ->join('left join tb_op_order_reissue r on r.id = rel.after_sale_id and rel.type = 2')
            ->where($where)
            ->order('rel.created_at asc')
            ->select();
        return $db_res;
    }

    public function getReturnInfos($where_str)
    {
        $where_str .= " and r.status_code != 'N002800008' and r.status_code != 'N002800017'";
        $where['_string'] = $where_str;
        $db_res = $this->model->table('tb_op_order_after_sale_relevance rel')
            ->field('rel.platform_country_code,r.*')
            ->join('left join tb_op_order_return r on r.id = rel.after_sale_id and rel.type = 1')
            ->where($where)
            ->order('rel.created_at asc')
            ->select();
        return $db_res;
    }

    public function getRefundInfos($where_str)
    {
        $where_str .= " and r.status_code != 'N002800008' and r.status_code != 'N002800014'";
        $where['_string'] = $where_str;
        $db_res = $this->model->table('tb_op_order_after_sale_relevance rel')
            ->field('rel.platform_country_code,r.*')
            ->join('left join tb_op_order_refund r on r.id = rel.after_sale_id and rel.type = 3')
            ->where($where)
            ->order('rel.created_at asc')
            ->select();
        return $db_res;
    }
}
