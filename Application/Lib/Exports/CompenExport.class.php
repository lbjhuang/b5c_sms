<?php

class CompenExport
{
    /**
     * 需要导出的工作簿字典
     * @var array
     */
    protected $sheets = [
        0 => "赔付单",
    ];
    protected $sheets_header_map = [
        "data" => [
            'compensate_no' => '赔付单号',
            'b5c_order_no' => '订单号',
            'order_id' => '平台订单ID',
            'b5c_sku_id' => 'SKU 编号',
            'sku_id' => '平台SKU编号',
            'spu_name_cn' => '中文名称',
            'spu_name_en' => '英文名称',
            'upc_id' => '条形码',
            'opt_name_value_str' => '属性（值）',
            'specification' => '规格',
            'weight' => '重量',
            'item_count' => '数量',
            'cost_usd_price' => '成本价格（USD）',
            'trading_price' => '交易价格',
            'delivery_warehouse_cd_val' => '发货仓库',
            'sendout_time' => '出库时间',
            'shipping_methods' => '物流方式',
            'country' => '收货人国家',
            'plat_cd_val' => '站点',
            'waybill_number' => '运单号',
            'created_by' => '申请人 ',
            'created_at' => '申请时间',
            'status_cd_val' => '异常处理状态',
            'reason_cd_val' => '申请原因',
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
            foreach ($sheet_headers_keys as $kk => $header_name) {
                $sheet_header_col_map[$header_name] = chr($a_ord + $kk);
            }

            // 标题填充
            // 填充提一行数据 （标题）
            $sheet_styles = isset($this->sheets_styles[$index]) ? $this->sheets_styles[$index] : [];

            foreach ($sheet_headers as $col_name => $col_val) {
                $col = $sheet_header_col_map[$col_name];
                $col_line = $col . "1";
                $activeSheet->setCellValue($col . "1", $col_val);

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
                        if($filed == 'trading_price' && $val){
                            $val = $item['trading_currency'].' '.$val;
                        }
                        if ($filed == 'waybill_number') {
                            $val = $val. ' ';
                        }
                        $activeSheet->setCellValue($cell_line, $val);
                    }
                }
            }
        }
        # 设置打开的工作簿
        $this->objPHPExcel->setActiveSheetIndex(0);
        $file_name = $file_name ? $file_name : "赔付单_" . date("YmdHis");
        //*生成xlsx文件
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $file_name . '.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}
