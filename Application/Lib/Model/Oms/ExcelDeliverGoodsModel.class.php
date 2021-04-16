<?php

/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/3/23
 * Time: 16:08
 */


class ExcelDeliverGoodsModel extends BaseImportExcelModel
{
    public function fieldMapping()
    {
        return [
            'b5cOrderNo' => ['field_name' => L('订单号'), 'required' => true],
            'ordId' => ['field_name' => L('ERP订单号'), 'required' => false],
            'orderId' => ['field_name' => L('第三方订单号'), 'required' => false],
        ];
    }

    /**
     * @param int    $row_index    行坐标
     * @param int    $column_index 列坐标
     * @param string $value        值
     *
     * @return null
     */
    public function valid($row_index, $column_index, $value)
    {
        parent::valid($row_index, $column_index, $value);
//        $db_field = $this->title [$column_index]['db_field'];
//        if ($db_field == 'CON_NO' and !empty($value)) {
//            if (in_array($value, $this->getAllCustomerContract())) $this->errorinfo [][$row_index.$column_index] = $this->title [$column_index]['en_name'] . ': ' . $value . ' 已存在';
//        } elseif (in_array($db_field, $this->commonField())) {
//            if ($db_field == 'CON_TYPE') {
//                $cds = BaseModel::conType();
//                $k = array_search($value, $cds);
//                if ($k !== false) $this->data [$row_index][$column_index]['value'] = $k;
//                else $this->errorinfo [][$row_index.$column_index] = $this->title [$column_index]['en_name'] . ': ' . $value . ' 数据库无相应的code';
//            } elseif ($db_field == 'CONTRACT_TYPE') {
//                $cds = BaseModel::contractType();
//                $value = $value;
//                $k = array_search($value, $cds);
//                if ($k !== false) $this->data [$row_index][$column_index]['value'] = $k;
//                else $this->errorinfo [][$row_index.$column_index] = $this->title [$column_index]['en_name'] . ': ' . $value . ' 数据库无相应的code';
//            } elseif ($db_field == 'IS_RENEWAL') {
//                $cds = BaseModel::isAutoRenew();
//                $value = $value;
//                $k = array_search($value, $cds);
//                if ($k !== false) $this->data [$row_index][$column_index]['value'] = $k;
//                else $this->errorinfo [][$row_index.$column_index] = $this->title [$column_index]['en_name'] . ': ' . $value . ' 数据库无相应的code';
//            } elseif ($db_field == 'CON_COMPANY_CD') {
//                $cds = BaseModel::conCompanyCd();
//                $value = $value;
//                $k = array_search($value, $cds);
//                if ($k) $this->data [$row_index][$column_index]['value'] = $k;
//                else $this->errorinfo [][$row_index.$column_index] = $this->title [$column_index]['en_name'] . ': ' . $value . ' 数据库无相应的code';
//            }
//        }
    }

    private $b5cOrderNo;

    /**
     * 数据再组装
     * 对采购商进行组装，去重验证
     */
    public function packData()
    {
        $readLine = 'A';
        $data = [];
        foreach ($this->data as $index => $info) {
            $data [] = $info [$readLine]['value'];
            $this->b5cOrderNo [$info [$readLine]['value']] = $info ['C']['value'];
        }
        $this->data = $data;
    }

    /**
     * 导入主入口函数
     *
     */
    public function import()
    {
        ini_set('max_execution_time', 1800);
        ini_set('memory_limit', '512M');
        parent::import();
        $this->packData();
        if (!$this->errorinfo) {
            $model = new OmsOutGoingModel();
            $model->mode = 0;
            $model->b5cOrderNo = $this->b5cOrderNo;
            $data ['ordId'] = $this->data;
            $params = ZUtils::filterBlank($_REQUEST['data']['query']);

            //判断是否退款
            if (!empty($this->data)) {
                $res_refund = (new OmsAfterSaleService())->checkOrderRefund($this->data, 'b5c_order_no');
                if (true !== $res_refund) {
                    return $res_refund;
                }
            }

            if ($params['preDone'] == -1) {//检查订单状态
                $response = (new OrderBackModel())->preCheckAndDone($data);
            } else {
                $data ['preDone'] = $params['preDone'];//1  释放库存&修改派单状态
                $response = $model->mulDeliver($data);
                $this->updateWaybills($response, $this->b5cOrderNo);
            }
            (new OutGoingAction())->signSendOut($this->data,1);

        } else {
            $response ['code'] = 3000;
            $response ['msg'] = L($this->errorinfo);
            $response ['data'] = null;
        }

        return $response;
    }

    public function updateWaybills($response, $orders_arr)
    {
        @import("@.Model.Oms.OrderLog");
        Logs($response, 'response', 'updateWaybills');
        Logs($orders_arr, 'orders_arr', 'updateWaybills');
        foreach ($response['data'] as $value) {
            if (2000 == $value['code']) {
                $orders_success_arr[] = $value['ordId'];
            }
        }
        if ($orders_arr && $orders_success_arr) {
            $orders_arr = array_filter($orders_arr);
            $model = new Model();
            $where['B5C_ORDER_NO'] = array('IN', $orders_success_arr);
            $where['SURFACE_WAY_GET_CD'] = array('IN', ['N002010200', 'N002010300']);;
            $res_db = $model->table('tb_op_order')
                ->field('ORDER_ID,PLAT_CD,B5C_ORDER_NO')
                ->where($where)
                ->select();
            Logs($res_db, 'res_db', 'updateWaybills');
            foreach ($res_db as $key => $value) {
                $save_waybill['ORD_ID'] = $where_update['ORD_ID'] = $value['ORDER_ID'];
                $save_waybill['plat_cd'] = $where_update['plat_cd'] = $value['PLAT_CD'];
                $save_waybill['TRACKING_NUMBER'] = $orders_arr[$value['B5C_ORDER_NO']];
                $where_update = ['ORD_ID' => $value['ORDER_ID'], 'plat_cd' => $value['PLAT_CD']];
                $upd_res = TbMsOrdPackage::updateOrCreate($where_update, $save_waybill);
                Logs($where_update, '$where_update', 'updateWaybills');
                Logs($save_waybill, '$save_waybill', 'updateWaybills');
                Logs($upd_res, 'upd_res', 'updateWaybills');
                $log_msg = "修改运单号为：" . $orders_arr[$value['B5C_ORDER_NO']];
                OrderLogModel::addLog($value['ORDER_ID'], $value['PLAT_CD'], $log_msg);
            }
        }
    }


}