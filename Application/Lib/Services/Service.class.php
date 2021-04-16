<?php

/**
 * User: yangsu
 * Date: 18/10/18
 * Time: 10:31
 */

class Service
{
    /**
     * @var
     */
    public $exp_title;
    /**
     * @var
     */
    public $exp_cell_name;
    /**
     * @var
     */
    public $exp_table_data;

    /**
     * @var string
     */
    protected $repository = stdClass::class;

    protected $doc_download_url = '/index.php?m=order_detail&a=download&path=doc&file=';

    /**
     * @param $exp_title
     * @param array $exp_cell_name
     * @param array $exp_table_data
     * @param $file_name
     *
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function outputExcel($exp_title, array $exp_cell_name, array $exp_table_data, $file_name = null)
    {
        
        $excel = new \ExcelModel();
        $excel->export($exp_title, $exp_cell_name, $exp_table_data, $file_name);
       
    }

    /**
     * @param $exp_title
     * @param array $exp_cell_name
     * @param array $exp_table_data
     */
    public function outputCsv($exp_title, array $exp_cell_name, array $exp_table_data)
    {
        $map = [];
        foreach ($exp_cell_name as $key => $value) {
            $map[] = ['field_name' => $key, 'name' => $value];
        }
        $this->exportCsv($exp_table_data, $map, $exp_title);
    }

    /**
     * @param $data
     * @param $map
     * @param $excel_name
     */
    private function exportCsv(&$data, $map, $excel_name)
    {
        $filename = '' . $excel_name . '' . date('Ymd') . '.csv'; //设置文件名
        header('Content-Type: text/csv');
        header("Content-type:text/csv;charset=gb2312");
        header("Content-Disposition: attachment;filename={$filename}");
        echo chr(0xEF) . chr(0xBB) . chr(0xBF);
        $out = fopen('php://output', 'w');
        fputcsv($out, array_column($map, 'name'));
        $fields = array_column($map, 'field_name');
        foreach (DataModel::toYield($data) as $row) {
            $line = array_map(function ($field) use ($row) {
                return (string)$row[$field] . "\t";
            }, $fields);
            fputcsv($out, $line);
        }
        fclose($out);
    }

    /**
     * @param $request_data
     * @param $where_arr
     * @param $temp_time
     *
     * @return mixed
     */
    protected function timeToOrmTime($request_data, $where_arr, $temp_time, $temp_table)
    {
        $request_data['time_type'] = $temp_time;
        switch ($where_arr["{$temp_table}.{$temp_time}"][0]) {
            case 'EGT':
                $request_data['time_begin'] = $where_arr["{$temp_table}.{$temp_time}"][1];
                break;
            case 'ELT':
                $request_data['time_end'] = $where_arr["{$temp_table}.{$temp_time}"][1];
                break;
            case 'BETWEEN':
                $request_data['time_begin'] = $where_arr["{$temp_table}.{$temp_time}"][1][0];
                $request_data['time_end'] = $where_arr["{$temp_table}.{$temp_time}"][1][1];
                break;
        }
        return $request_data;
    }

    /**
     * @param array $datas
     * @param       $time_key
     *
     * @return array
     */
    protected function toBeyondNumber(array $datas, $time_key)
    {
        $now_date = new DateTime();
        $res_arr = array_map(function ($value) use ($now_date, $time_key) {
            if ($value[$time_key]) {
                $temp_date = new DateTime($value[$time_key]);
                $value['beyond_number'] = $now_date->diff($temp_date)->days;
            }
            return $value;
        }, $datas);
        return $res_arr;
    }

    /**
     * 获取kyriba推送文件下载路径
     * @param $payment_audit_no
     */
    public function getKyribaPushFileDownloadPath($payment_audit_no)
    {
        $file1 = "GSHOPPER.NC4.IMPORT.{$payment_audit_no}.PY_TRANSFER.Z_IMP_PY.null.null.xml";
        $file2 = "GSHOPPER.NC4.IMPORT.{$payment_audit_no}.PY_TRANSFER.Z_IMP_PY_T.null.null.xml";
        $file3 = "GSHOPPER.NC4.IMPORT.{$payment_audit_no}.PY_TRANSFER.Z_IMP_PY_R.null.null.xml";
        if (is_file(ATTACHMENT_DIR_DOC. $file1)) {
            return $this->doc_download_url. $file1;
        }
        if (is_file(ATTACHMENT_DIR_DOC. $file2)) {
            return $this->doc_download_url. $file2;
        }
        if (is_file(ATTACHMENT_DIR_DOC. $file3)) {
            return $this->doc_download_url. $file3;
        }
        return '';
    }

    /**
     * 验证是否是可操作订单
     * @param $condition   条件
     * @param bool $sale_order  代销售订单  true : 验证   false: 不验证
     * @param bool $shopnc_order  shopnc平台订单  true : 验证   false: 不验证
     */
    public function isMayOperationOrders($condition,$sale_order = true,$shopnc_order = true)
    {
        $isOperation = true;
        $sale_condition = $shopnc_condition = $condition;
        if ($condition) {
            if ($sale_order) {
                $sale_condition_new = array();
                foreach ($sale_condition as $key => $value) {
                    $sale_condition_new['tb_op_order.' . $key] = $value;
                }
                $sale_condition_new['btc_order_type_cd'] = 'N003720003';   //   B2C订单类型::::代销售订单
                $ord_info = M('order', 'tb_op_')
                    ->join('INNER JOIN tb_op_order_extend ON tb_op_order.ORDER_ID = tb_op_order_extend.order_id 
                        AND tb_op_order.PLAT_CD = tb_op_order_extend.plat_cd ')
                    ->where($sale_condition_new)->field('btc_order_type_cd')->find();
                if ($ord_info) $isOperation = false;
            }
//            if ($isOperation) {
//                if ($shopnc_order) {
//                    $ord_info = M('order', 'tb_op_')->where($shopnc_condition)->field('STORE_ID')->find();
//                    if ($ord_info) {
//                        $store_info = M('store', 'tb_ms_')->field('BEAN_CD')->where(array('ID' => $ord_info['STORE_ID']))->find();
//                        if ($store_info && strtolower($store_info['BEAN_CD']) == 'shopnc') $isOperation = false;
//                    }
//                }
//            }
        }
        return $isOperation;
    }
}