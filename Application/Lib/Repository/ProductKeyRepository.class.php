<?php
/**
 * User: yangsu
 * Date: 19/12/24
 * Time: 12:08
 */

/**
 * Class ProductKeyRepository
 */
class ProductKeyRepository extends Repository
{
    public $product_key;
    public $sku_id;
    public $batch_code;
    public $key_no;

    public function getStock()
    {
        $where['sku_id'] = $this->sku_id;
        $where['batch_code'] = $this->batch_code;
        return $this->model->table('tb_wms_batch')->where($where)->find();
    }

    public function getCostPrice()
    {
        $where['tb_wms_batch.sku_id'] = $this->sku_id;
        $where['tb_wms_batch.batch_code'] = $this->batch_code;
        return $this->model->table('tb_wms_batch')
            ->field('tb_wms_stream.unit_price_usd / (1+ifnull(tb_wms_stream.pur_invoice_tax_rate,0)) AS cost_price')
            ->join('left join tb_wms_stream ON tb_wms_stream.id = tb_wms_batch.stream_id')
            ->where($where)
            ->find();
    }

    public function getBill($batch_id = null)
    {
        if (empty($batch_id)) {
            $where['tb_wms_batch.sku_id'] = $this->sku_id;
            $where['tb_wms_batch.batch_code'] = $this->batch_code;
        } else {
            $where['tb_wms_batch.batch_id'] = $batch_id;
        }
        return $this->model->table('tb_wms_batch')
            ->field('tb_wms_bill.procurement_number AS purchase_order_no,
            tb_pur_relevance_order.relevance_id,
            tb_wms_stream.from_batch,
            tb_wms_bill.zd_date AS created_at,
            tb_wms_bill.zd_user AS purchased_by')
            ->join('left join tb_wms_bill ON tb_wms_bill.id = tb_wms_batch.bill_id')
            ->join('left join tb_wms_stream ON tb_wms_stream.id = tb_wms_batch.stream_id')
            ->join('left join tb_pur_order_detail ON tb_pur_order_detail.procurement_number = tb_wms_bill.procurement_number')
            ->join('left join tb_pur_relevance_order ON tb_pur_relevance_order.order_id = tb_pur_order_detail.order_id')
            ->where($where)
            ->find();
    }

    public function getSalesInformation()
    {
        $where['tb_wms_batch.sku_id'] = $this->sku_id;
        $where['tb_wms_batch.batch_code'] = $this->batch_code;
//        $where['tb_wms_batch_order.use_type'] = ['NOT IN', [8, 9, 10]];
        $where['tb_wms_bill.type'] = 0;
        return $this->model->table('tb_wms_batch')
            ->field([
                'tb_wms_bill.link_bill_id AS order_no',
                'tb_wms_bill.zd_user AS create_user_id',
                'tb_wms_bill.zd_date AS order_time',
                'tb_b2b_ship_list.order_id AS b2b_order_id',
                'tb_wms_bill.link_b5c_no',
                'tb_op_order.order_id',
                'tb_op_order.plat_cd',
                'tb_op_order.order_no AS op_order_no',
            ])
            ->join('left join tb_wms_stream ON tb_wms_stream.batch = tb_wms_batch.id')
            ->join('left join tb_wms_bill ON tb_wms_bill.id = tb_wms_stream.bill_id')
//            ->join('left join tb_wms_batch_order ON tb_wms_batch_order.ORD_ID  = tb_wms_bill.link_bill_id')
            ->join('left join tb_b2b_ship_list ON tb_wms_bill.link_bill_id = tb_b2b_ship_list.order_batch_id')
            ->join('left join tb_op_order ON tb_op_order.B5C_ORDER_NO = tb_wms_bill.link_b5c_no')
            ->where($where)
            ->where('  (tb_b2b_ship_list.order_id IS NOT NULL OR tb_wms_bill.link_b5c_no IS NOT NULL)',null,true)
            ->limit(20)
            ->select();
    }

    public function getRelatedPaymentKeys()
    {
        $where['tb_wms_batch.sku_id'] = $this->sku_id;
        $where['tb_wms_batch.batch_code'] = $this->batch_code;
        $where['tb_wms_batch_order.use_type'] = ['IN', [8, 9, 10]];
        $where['tb_wms_payment.payment_no'] = ['EXP', 'IS NOT NULL'];
        return $this->model->table('tb_wms_batch')
            ->field(['tb_wms_payment.payment_no AS payment_key',
                'tb_wms_payment.id',
                'tb_wms_payment.created_by',
                'tb_wms_payment.created_at',
                'tb_ms_cmn_cd.CD_VAL AS trigger_action'
            ])
            ->join('left join tb_wms_batch_order ON tb_wms_batch_order.batch_id = tb_wms_batch.id')
            ->join('left join tb_wms_allo ON tb_wms_allo.allo_no = tb_wms_batch_order.ORD_ID')
            ->join('left join tb_wms_payment ON  tb_wms_payment.allo_id = tb_wms_allo.id')
            ->join('left join tb_ms_cmn_cd ON  tb_wms_payment.action_type_cd = tb_ms_cmn_cd.CD')
            ->where($where)
            ->limit(20)
            ->select();
    }

    public function getHandle($purchase_order_no)
    {
        return $this->model->query("SELECT
                        t.payment_no AS payment_key,
                        b.procurement_number,
                        t.id,
                        po.action_type_cd,
                        tb_ms_cmn_cd.CD_VAL AS trigger_action,
                        po.created_by,
                        po.created_at
                    FROM
                        tb_pur_payment t
                    LEFT JOIN tb_pur_relevance_order a ON t.relevance_id = a.relevance_id
                    LEFT JOIN tb_pur_order_detail b ON b.order_id = a.order_id
                    LEFT JOIN tb_pur_operation po ON t.id = po.main_id
                    LEFT JOIN tb_ms_cmn_cd ON  po.action_type_cd = tb_ms_cmn_cd.CD
                    WHERE
                        (
                            (
                                b.procurement_number = '{$purchase_order_no}'
                            )
                            OR (
                                b.online_purchase_order_number = '{$purchase_order_no}'
                            )
                        )
                    AND (
                        `order_status` = 'N001320300'
                    )");
    }
}