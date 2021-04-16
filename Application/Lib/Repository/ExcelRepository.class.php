<?php
/**
 * User: yangsu
 * Date: 19/08/06
 * Time: 14:38
 */

class ExcelRepository extends Repository
{
    public function getAllSkuAmount($sku = null)
    {
        $field = [
            'tb_wms_batch.SKU_ID',
            'SUM(tb_wms_batch.total_inventory * (tb_wms_stream.unit_price / (1+ifnull(tb_wms_stream.pur_invoice_tax_rate,0)))) AS sum_amount',
            'SUM(tb_wms_batch.total_inventory) AS sum_total_inventory',
        ];
        $where = [];
        if (!empty($sku)) {
            $where['tb_wms_batch.SKU_ID'] = $sku;
        }
        $where_string = 'tb_wms_batch.stream_id = tb_wms_stream.id AND tb_wms_batch.vir_type IN (\'N002440100\',\'N002440400\')';
        $sql = $this->model->table('tb_wms_batch,tb_wms_stream')
            ->field($field)
            ->where($where)
            ->where($where_string, null, true)
            ->group('tb_wms_batch.SKU_ID')
            ->select();
        return $sql;
    }

    public function getDesignationDateBill($start_date, $end_date)
    {
        $field = [
            'tb_wms_bill.type',
            'tb_wms_stream.GSKU AS sku_id',
            'tb_wms_stream.send_num',
            'tb_wms_stream.unit_price',
            'tb_wms_stream.unit_price / (1+ifnull(tb_wms_stream.pur_invoice_tax_rate,0)) AS unit_price_no_rate'
        ];
        $where['tb_wms_bill.zd_date'] = ['GT', $start_date . ' 00:00:00'];
        $where_string = "tb_wms_bill.id =  tb_wms_stream.bill_id AND tb_wms_bill.vir_type  IN ('N002440100','N002440400')";
        return $this->model->table('tb_wms_bill,tb_wms_stream')
            ->field($field)
            ->where($where)
            ->where($where_string, null, true)
            ->select();
    }

    public function getInterval($start_date, $end_date)
    {
        $field = [
            'GSKU AS sku_id',
            'SUM(send_num) AS sum_send_num',
        ];
        $where['tb_wms_bill.bill_type'] = 'N000950100';
        $where = WhereModel::getBetweenDate(
            $start_date,
            $end_date,
            $where,
            'tb_wms_bill.zd_date'
        );
        $where_string = "tb_wms_bill.id =  tb_wms_stream.bill_id";
        return $this->model->table('tb_wms_bill,tb_wms_stream')
            ->field($field)
            ->where($where)
            ->where($where_string, null, true)
            ->group('tb_wms_stream.GSKU')
            ->select();
    }

    public function transferOut()
    {
        $sql = "SELECT
                    tb_wms_batch_order.SKU_ID,
                    SUM(
                        tb_wms_batch_order.occupy_num
                    ) AS 'sum_occupy_num',
                    SUM(
                        tb_wms_batch_order.occupy_num * tb_wms_stream.unit_price / (
                            1 + ifnull(
                                tb_wms_stream.pur_invoice_tax_rate,
                                0
                            )
                        )
                    ) AS 'sum_amount'
                FROM
                    `tb_wms_batch_order`,
                    tb_wms_batch,
                    tb_wms_stream
                WHERE
                    tb_wms_batch_order.use_type = '10'
                AND tb_wms_batch_order.batch_id = tb_wms_batch.id
                AND tb_wms_batch.stream_id = tb_wms_stream.id
                GROUP BY
                    tb_wms_batch_order.SKU_ID";
        $data_db = $this->model->query($sql);
        foreach ($data_db as $value) {
            $return_data[$value['SKU_ID']] = $value;
        }
        return $return_data;
    }

    public function getTppCustData($start_date, $end_date)
    {
        $field = [
            'tb_ms_receiver_cust.RES_NAME as RES_NAME',
            'tb_ms_receiver_cust.REL_TEL_NUM as REL_TEL', //手机号需要解密
            'tb_ms_receiver_cust.RES_EMAIL as RES_EMAIL',
            'tb_ms_receiver_cust.UPDATE_TIME as UPDATE_TIME',
            'tb_ms_receiver_cust.ALL_ORDER_COUNT as ALL_ORDER_COUNT',
        ];
        $where = [];
        $where = WhereModel::getBetweenDate(
            $start_date,
            $end_date,
            $where,
            'tb_ms_receiver_cust.CREATE_TIME'
        );
        $where_string = "";
        return $this->model->table('tb_ms_receiver_cust')
            ->field($field)
            ->where($where)
            ->where($where_string, null, true)
            ->select();
    }

    public function getGpCustData($start_date = '', $end_date = '')
    {
        //总记录7000+
        $field = [
            'tb_ms_thrd_cust.CUST_NICK_NM',
            'tb_ms_thrd_cust.CUST_EML',
            'tb_ms_thrd_cust.CUST_CP_NO',
            'tb_ms_thrd_cust.PLAT_FORM',
            'tb_ms_thrd_cust.SYS_REG_DTTM',
        ];
        $where = [];
        $where = WhereModel::getBetweenDate(
            $start_date,
            $end_date,
            $where,
            'tb_ms_thrd_cust.created_at'
        );
        $where_string = "";
        return $this->model->table('tb_ms_thrd_cust')
            ->field($field)
            ->where($where)
            ->where($where_string, null, true)
            ->group('tb_ms_thrd_cust.CUST_ID')
            ->select();
    }

    public function getOpOrderData($start_date, $end_date)
    {
        $field = [
            'ID',
            'USER_EMAIL',
            'ADDRESS_USER_NAME',
            'ADDRESS_USER_PHONE',
            'ADDRESS_USER_COUNTRY',
        ];
        $where = [];
        $where = WhereModel::getBetweenDate(
            $start_date,
            $end_date,
            $where,
            'tb_op_order.ORDER_CREATE_TIME'
        );
        $where_string = "";
        return $this->model->table('tb_op_order')
            ->field($field)
            ->where($where)
            ->where($where_string, null, true)
            ->select();
    }
}