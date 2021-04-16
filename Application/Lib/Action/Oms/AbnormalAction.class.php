<?php

/**
 * User: yangsu
 * Date: 18/11/5
 * Time: 11:03
 */
class AbnormalAction extends BasisAction
{
    public function excel_delete()
    {
        $this->display();
    }

    public function uploadDeleteExcel()
    {
        try {
            set_time_limit(120);
            if (empty($_FILES['expe']['tmp_name'])) {
                throw new Exception('文件加载异常');
            }
            $receive_map = [
                'order_id' => 'A',
                'plat_cd' => 'B',
                'msg' => 'C'
            ];
            $afterSaleService = new OmsAfterSaleService();
            $cancel_status = $afterSaleService->getCancelStatus();//获取售后取消相关状态（包括补发、退货、退款）
            list($excel_arr, $file_path) = FileModel::excelToArray($_FILES['expe']['tmp_name'], $receive_map);
            $this->validateExcel($excel_arr);
            $this->checkOrderRepeat($excel_arr);
            $this->checkOrderDbHas($excel_arr, $cancel_status);
            $request_data = $this->assemblyDeleteOrderData($excel_arr, $file_path);

            $afterSaleService->cancelReissueByOrder(array_column($request_data['data'], 'orderId'));
            
            $res = ApiModel::deleteB2b2cOrders($request_data);
        } catch (Exception $exception) {
            $res = DataModel::$error_return;
            $res['msg'] = $exception->getMessage();
            if ($exception->getCode()) {
                $res['code'] = $exception->getCode();
            }
        }
        RedisLock::unlock();
        $this->assign('res', json_encode($res));
        $this->display('orderDelresult');
        //$this->ajaxReturn($res);
    }

    private function validateExcel($data)
    {
        foreach ($data as $k => $v) {
            if (empty($v['order_id']) || empty($v['plat_cd'])) {
                throw new Exception('第 ' . ($k + 2) . ' 行订单 ID 和平台 CD 必填');
            }
        }
    }

    private function assemblyDeleteOrderData($data, $file_path)
    {
        $request_data = array(
            'user' => DataModel::userNamePinyin(),
            'requestAt' => time() * 1000,
            'reason' => $data[0]['msg'],
            'fileName' => $_FILES['expe']['name'],
            'filePath' => $file_path,
            'data' => []
        );
        $request_data['data'] = array_map(function ($temp_value) {
            $temp_res['orderId'] = $temp_value['order_id'];
            $temp_res['platCd'] = $temp_value['plat_cd'];
            return $temp_res;
        }, $data);
        return $request_data;
    }

    private function checkOrderRepeat($data)
    {
        if (empty($data) || !is_array($data)) {
            throw new Exception('Excel 数据为空');
        }
        if (empty($data[0]['msg'])) {
            throw new Exception('删除原因未填写');
        }
        $array_column = array_column($data, 'order_id');
        $array_unique = array_unique($array_column);
        if (count($array_column) != count($array_unique)) {
            $array_column = array_map(function ($value) {
                return (string)$value;
            }, $array_column);
            $count_arrs = array_count_values($array_column);
            $error_msg = '';
            foreach ($count_arrs as $key => $value) {
                if ($value > 1) {
                    $error_msg .= $key . ' 订单 ID 重复；';
                }
            }
            throw new Exception($error_msg);
        }
    }

    private function checkOrderDbHas($data, $after_sale_cancel_status)
    {
        $Model = new Model();
        $where_string = ' 1 != 1';
        foreach ($data as $value) {
            $where_string .= " OR tb_op_order.ORDER_ID = '{$value['order_id']}' AND tb_op_order.PLAT_CD = '{$value['plat_cd']}'";
        }
        $db_res = $Model->table('tb_op_order')
            ->join('LEFT JOIN tb_op_order_extend ON tb_op_order.ORDER_ID = tb_op_order_extend.order_id 
	            AND tb_op_order.PLAT_CD = tb_op_order_extend.plat_cd')
            ->field('tb_op_order.ORDER_ID,tb_op_order.PLAT_CD,tb_op_order_extend.btc_order_type_cd,
            tb_op_order_extend.reissue_status_cd,tb_op_order_extend.return_status_cd,tb_op_order_extend.refund_status_cd')
            ->where($where_string, null, true)
            ->select();
        if (empty($db_res)) {
            throw new \Exception('ERP 中查询不到对应订单信息');
        }
        foreach ($db_res as $v) {
            if ($v['btc_order_type_cd'] == 'N003720003'){
                throw new Exception(L('代销售订单禁止删除操作'));
            }
            if (!RedisLock::lock($v['ORDER_ID'] . '_' . $v['PLAT_CD'], 30)) {
                throw new Exception(L('订单锁获取失败'));
            }

            //已取消的售后订单可以被删除
            if (!in_array($v['reissue_status_cd'], $after_sale_cancel_status)
                || !in_array($v['return_status_cd'], $after_sale_cancel_status)
                || !in_array($v['refund_status_cd'], $after_sale_cancel_status)) {
                throw new Exception(L('订单已申请了售后，禁止删除操作'));
            }
            
        }
        $error_msg = '';
//        $error_msg = $this->checkGroupSku($Model, $where_string, $error_msg);
        if (count($db_res) != count($data)) {
            $db_order_arr = array_column($db_res, 'ORDER_ID');
            $array_column = array_column($data, 'order_id');
            $diff_arr = array_diff($array_column, $db_order_arr);
            foreach ($diff_arr as $temp_value) {
                $error_msg .= $temp_value . ' 订单不存在；' . PHP_EOL;
            }
            throw new \Exception($error_msg);
        }
    }

    /**
     * @param $Model
     * @param $where_string
     *
     * @return string
     * @throws Exception
     */
    private function checkGroupSku($Model, $where_string,$error_msg)
    {
        $sku_arr = $Model->table('tb_op_order_guds')
            ->field('ORDER_ID,B5C_SKU_ID')
            ->where($where_string, null, true)
            ->group('ORDER_ID,B5C_SKU_ID')
            ->select();
        $all_group_sku = SkuModel::getGroupSku();
        $all_group_sku = array_column($all_group_sku, 'sku_id');
        foreach ($sku_arr as $key => $value) {
            if (in_array($value['B5C_SKU_ID'], $all_group_sku)) {
                $error_msg .= $value['ORDER_ID'] . ' 存在组合商品；';
            }
        }
        if (!empty($error_msg)) {
            throw new \Exception($error_msg);
        }
        return $error_msg;
}
}
