<?php

class AlloExport
{
    /**
     * 需要导出的工作簿字典
     * @var array
     */
    protected $sheets = [
        0 => "空运、快递",
        1 => "铁运、陆运、海运",
    ];

    // protected $sheets_styles = [
    //     0 => [
    //         'quote_no' => [ "width" => 18, ],
    //         'allo_no'  => [ "width" => 20,],
    //         'allo_out_warehouse_address'  => [ "width" => 60,],
    //         'allo_in_warehouse_address'  => [ "width" => 60,],
    //         'total_volume'  => [ "width" => 15,],
    //         'total_box_num'  => [ "width" => 15,],
    //         'total_weight'  => [ "width" => 15,],
    //         'billing_weight'  => [ "width" => 15,],
    //         'declare_type_cd_val'  => [ "width" => 20,],
    //         'planned_transportation_channel_cd_val'  => [ "width" => 15,],
    //     ],
    //     1 => [
    //         'quote_no' => [ "width" => 18, ],
    //         'allo_no'      => [ "width" => 20,],
    //         'sku_id'       => [ "width" => 18,],
    //         'good_name'    => [ "width" => 50,],
    //         'good_number'  => [ "width" => 10,],
    //     ]
    // ];

    protected $sheets_header_map = [
        #空运、快递
        "list_data1" => [
            'allo_no' => '调拨单号',
            'allo_in_team_val' => '销售团队',
            'small_team_cd_val' => '销售小团队',
            'allo_in_warehouse_val' => '调入仓库',
            'allo_out_warehouse_val' => '调出仓库',
            'create_user_nm' => '发起人',
            'info_id' => '出库记录',
            'spu_name' => '商品名称',
            'sku_id' => 'sku条码',
            'attributes' => '商品属性',
            'demand_allo_num' => '调拨数量',
            'out_num' => '出库数量',
            'in_num' => '已入库数量',
            'tb_crm_sp_supplier_value' => '运输公司',
            'planned_transportation_channel_cd_val' => '运输渠道',
            'third_party_warehouse_entry_number' => '入仓单号/SO号',
            'oversea_in_storage_no' => '海外仓入库单号',
            'cabinet_type_val' => '柜型',
            'out_plate_number_val' => '出库板数',
            'customs_clear_val' => '清关方式',
            'send_warehouse_way_val' => '送仓方式',
            'tracking_number' => '快递单号',
            'cube_feet_val' => '计费重/材积',
            'logistics_state' => '货物运输轨迹',
            'nodePlanTime1' => '下单报价时间',
            'nodeSystemPlan1' => '下单预计时间',
            'nodeTime1' => '下单完成时间',
            'nodePlanTime2' => '出库报价时间',
            'nodeSystemPlan2' => '出库预计时间',
            'nodeTime2' => '出库完成时间',
            'nodePlanTime3' => '离港报价时间',
            'nodeSystemPlan3' => '离港预计时间',
            'nodeTime3' => '离港完成时间',
            'nodePlanTime4' => '到港报价时间',
            'nodeSystemPlan4' => '到港预计时间',
            'nodeTime4' => '到港完成时间',
            'nodePlanTime5' => '清关报价时间',
            'nodeSystemPlan5' => '清关预计时间',
            'nodeTime5' => '清关完成时间',
            'nodePlanTime6' => '送仓报价时间',
            'nodeSystemPlan6' => '送仓预计时间',
            'nodeTime6' => '送仓完成时间',
            'nodePlanTime7' => '开始上架报价时间',
            'nodeSystemPlan7' => '开始上架预计时间',
            'nodeTime7' => '开始上架完成时间',
            'nodePlanTime8' => '上架完成报价时间',
            'nodeSystemPlan8' => '上架完成预计时间',
            'nodeTime8' => '上架完成完成时间',
        ],
        #铁运、陆运、海运
        "list_data2" => [
            'allo_no' => '调拨单号',
            'allo_in_team_val' => '销售团队',
            'small_team_cd_val' => '销售小团队',
            'allo_in_warehouse_val' => '调入仓库',
            'allo_out_warehouse_val' => '调出仓库',
            'create_user_nm' => '发起人',
            'info_id' => '出库记录',
            'spu_name' => '商品名称',
            'sku_id' => 'sku条码',
            'attributes' => '商品属性',
            'demand_allo_num' => '调拨数量',
            'out_num' => '出库数量',
            'in_num' => '已入库数量',
            'tb_crm_sp_supplier_value' => '运输公司',
            'planned_transportation_channel_cd_val' => '运输渠道',
            'third_party_warehouse_entry_number' => '入仓单号/SO号',
            'oversea_in_storage_no' => '海外仓入库单号',
            'cabinet_type_val' => '柜型',
            'cabinet_number' => '柜号',
            'strip_p_seal' => '封条',
            'shipping_company_name' => '船公司',
            'out_plate_number_val' => '出库板数',
            'customs_clear_val' => '清关方式',
            'send_warehouse_way_val' => '送仓方式',
            'tracking_number' => '快递单号',
            'cube_feet_val' => '计费重/材积',
            'logistics_state' => '货物运输轨迹',
            'nodePlanTime1' => '下单报价时间',
            'nodeSystemPlan1' => '下单预计时间',
            'nodeTime1' => '下单完成时间',
            'nodePlanTime2' => '出库报价时间',
            'nodeSystemPlan2' => '出库预计时间',
            'nodeTime2' => '出库完成时间',
            'nodePlanTime3' => '离港报价时间',
            'nodeSystemPlan3' => '离港预计时间',
            'nodeTime3' => '离港完成时间',
            'nodePlanTime4' => '到港报价时间',
            'nodeSystemPlan4' => '到港预计时间',
            'nodeTime4' => '到港完成时间',
            'nodePlanTime5' => '清关报价时间',
            'nodeSystemPlan5' => '清关预计时间',
            'nodeTime5' => '清关完成时间',
            'nodePlanTime6' => '送仓报价时间',
            'nodeSystemPlan6' => '送仓预计时间',
            'nodeTime6' => '送仓完成时间',
            'nodePlanTime7' => '开始上架报价时间',
            'nodeSystemPlan7' => '开始上架预计时间',
            'nodeTime7' => '开始上架完成时间',
            'nodePlanTime8' => '上架完成报价时间',
            'nodeSystemPlan8' => '上架完成预计时间',
            'nodeTime8' => '上架完成完成时间',
        ],
    ];

    protected $objPHPExcel = null;

    public function __construct()
    {
        vendor("PHPExcel.PHPExcel");
        $this->objPHPExcel = new PHPExcel();
    }

    /**
     * 需要导出的数据
     * @var array
     */
    protected $data = [];

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }


    /**
     * 下载
     * @author Redbo He
     * @date 2021/2/18 10:50
     */
    public function download($file_name = '')
    {
        $sheets_headers = array_keys($this->sheets_header_map);
        $a_ord = ord("A");
        foreach ($sheets_headers as $index => $sheet_map_name) {
            $sheet_title = isset($this->sheets[$index]) ? $this->sheets[$index] : '';
            # 创建多个工作簿
            if ($index > 0) {
                $this->objPHPExcel->createSheet($index);
            }
            $this->objPHPExcel->setActiveSheetIndex($index);
            $activeSheet = $this->objPHPExcel->getActiveSheet();
            if ($sheet_title) {
                $activeSheet->setTitle($sheet_title);
            }
            $sheet_headers = $this->sheets_header_map[$sheet_map_name];
            $sheet_headers_keys = array_keys($sheet_headers);
            $sheet_header_col_map = [];
            $key2 = ord("@");
            foreach ($sheet_headers_keys as $kk => $header_name) {
                if (ord(chr($a_ord + $kk)) > ord("Z")) {
                    $sheet_header_col_map[$header_name] = chr(ord("A")) . chr(++$key2); //超过26个字母 AA1,AB1,AC1,AD1...BA1,BB1...
                } else {
                    $sheet_header_col_map[$header_name] = chr($a_ord + $kk);
                }
            }

            $sheet_styles = isset($this->sheets_styles[$index]) ? $this->sheets_styles[$index] : [];

            foreach ($sheet_headers as $col_name => $col_val) {
                $col = $sheet_header_col_map[$col_name];
                $activeSheet->setCellValue($col . "1", $col_val);
                if ($sheet_styles) {
                    $col_styles = isset($sheet_styles[$col_name]) ? $sheet_styles[$col_name] : [];
                    if ($col_styles) {
                        foreach ($col_styles as $style => $style_val) {
                            if ($style == 'width') {
                                $activeSheet->getColumnDimension($col)->setWidth($style_val);
                            }
                        }
                    }
                }

            }
            //  填充第n(n>=2, n∈N*)行数据
            $sheet_data = isset($this->data[$sheet_map_name]) ? $this->data[$sheet_map_name] : [];
            if ($sheet_data) {
                foreach ($sheet_data as $data_index => $item) {
                    foreach ($sheet_headers_keys as $filed) {
                        $col = $sheet_header_col_map[$filed];
                        $line = $data_index + 2;
                        $cell_line = $col . $line;
                        $val = isset($item[$filed]) ? $item[$filed] : '';
                        $activeSheet->setCellValue($cell_line, $val);
                    }
                }
            }
        }
        # 设置打开的工作簿
        $this->objPHPExcel->setActiveSheetIndex(0);
        $file_name = $file_name ? $file_name : "调拨单列表_" . date("YmdHis");
        //*生成xlsx文件
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $file_name . '.xlsx"');
        header('Cache-Control: max-age=0');


        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}
