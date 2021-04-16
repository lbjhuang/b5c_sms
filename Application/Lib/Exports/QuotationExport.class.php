<?php

class QuotationExport
{
    /**
     * 需要导出的工作簿字典
     * @var array
     */
    protected $sheets = [
        0 => "报价单信息",
    ];

    protected $sheets_styles = [
        0 => [
            'quote_no' => [ "width" => 15, ],
            'quotation_create_at' => [ "width" => 12, ],
            'small_team_cd_val' => [ "width" => 20, ],
            'quotation_created_by' => [ "width" => 12, ],
            'allo_no'  => [ "width" => 16,],
            'total_volume'  => [ "width" => 14,],
            'total_box_num'  => [ "width" => 14,],
            'total_weight'  => [ "width" => 14,],
            'allo_out_warehouse_val'  => [ "width" => 20,],
            'allo_in_warehouse_val'  => [ "width" => 20,],
            'sku_id'  => [ "width" => 12,],
            'good_name'  => [ "width" => 60,],
            'good_number'  => [ "width" => 10,],
            'billing_weight'  => [ "width" => 15,],
            'declare_type_cd_val'  => [ "width" => 12,],
            'planned_transportation_channel_cd_val'  => [ "width" => 15,],
            'is_electric_cd_val'  => [ "width" => 10,],
        ]
    ];

    protected $sheets_header_map = [
        [
            'quote_no' => '报价单号',
            'quotation_create_at' => '询价日期',
            'small_team_cd_val' => '销售小团队',
            'quotation_created_by' => '运营',
            'allo_no' => '调拨单号',
            'allo_out_warehouse_val' => '调出仓库地址',
            'allo_in_warehouse_val' => '调入仓库地址',
            'sku_id'      => 'SKU条码',
            'good_name'   => '商品名称',
            'good_number' => '数量',
            'total_box_num' => '总箱数（箱）',
            'total_weight' => '总重量（KG）',
            'total_volume' => '体积（CBM）',
            'billing_weight' => '计费重（kg）',
            'declare_type_cd_val' => '报关',
            'planned_transportation_channel_cd_val' => '计划运输渠道',
            'is_electric_cd_val' => '带电',
        ],
    ];


    # 合并单元格
    protected $header_merge_map = [
      [
          [
              "merge_fields" => [
                  "total_volume","total_weight","total_box_num", "small_team_cd_val","quotation_created_by","allo_no","allo_out_warehouse_val","allo_in_warehouse_val","declare_type_cd_val","is_electric_cd_val",
              ],
              'merge_index'  => "quot_allo_data",
              "row_num_index" => "quote_allo_row_num",
              'merge_type' => 'row',
          ],
          [
              "merge_fields" => [
                  "quote_no","quotation_create_at",
                  "total_volume_sum","total_box_num_sum","total_weight_sum","billing_weight","planned_transportation_channel_cd_val",
              ],
              'merge_index'  => "",
              "row_num_index" => "merge_num",
              'merge_type' => 'row',
          ],
          [
              "merge_fields" => [
                  "sku_id","good_name","good_number",
              ],
              'merge_index'  => "quot_allo_data.goods",
              "row_num_index" => "",
              'merge_type' => 'row',
          ]
      ]
    ];
    protected $objPHPExcel = null;
    public function __construct()
    {
        vendor("PHPExcel.PHPExcel");
        $this->objPHPExcel  =new PHPExcel();
    }

    /**
     * 需要导出的数据
     * @var array
     */
    protected $data = [];

    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    /**
     * 获取合并字段对照字典
     * @param $sheet_map_name
     * @author Redbo He
     * @date 2021/3/11 19:16
     */
    protected function getMergeMapData($merge_map_data)
    {
        $filed_map = [];
        if($merge_map_data)
        {
            $merge_fields     = array_column($merge_map_data,'merge_fields');
            foreach ($merge_fields as  $index => $fields) {
                foreach ($fields as $field) {
                    $filed_map[$field] = $index;
                }
            }
        }
        return $filed_map;
    }


    protected function merge_get_index_data($data, $index)
    {

        $index_map = explode('.', $index);
        if(count($index_map) == 1)
            return array_get($data, $index);
        else {
            $result = [];
            foreach ($index_map as $i) {
                if(isset($data[$i])) {
                    $data = array_get($data, $i);
                }
                else {
                    $data = array_column($data, $i);
                }
            }

            foreach ($data as $val) {
                $result = array_merge( $result, $val);
            }
        }
        return $result;
    }

    /**
     * 下载
     * @author Redbo He
     * @date 2021/2/18 10:50
     */
    public function download ($file_name = '')
    {
        $sheets_headers = array_keys($this->sheets_header_map);
        $a_ord = ord("A");
        foreach ($sheets_headers as  $index => $sheet_map_name)
        {
            $sheet_title = isset($this->sheets[$index]) ? $this->sheets[$index] : '';
            # 创建多个工作簿
            if($index > 0) {
                $this->objPHPExcel->createSheet($index);
            }
            $this->objPHPExcel->setActiveSheetIndex($index);
            $activeSheet = $this->objPHPExcel->getActiveSheet();
            if($sheet_title) {
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
            $phpColor = new PHPExcel_Style_Color();
            $phpColor->setRGB('FFC000');
            foreach ($sheet_headers as $col_name => $col_val) {
                $col = $sheet_header_col_map[$col_name];
                $col_line = $col. "1";
                $activeSheet->setCellValue($col. "1", $col_val);
                if($index == 0) {
                    # 背景色
                    $activeSheet->getStyle($col_line)
                        ->getFill()
                        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB('FFC000');
                    //设置第一行的高度
                    $activeSheet->getRowDimension(1)->setRowHeight(30);
                    // 设置列对齐
                    $activeSheet->getStyle($col_line)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                    //Set column borders 设置列边框
                    $activeSheet->getStyle($col_line)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $activeSheet->getStyle($col_line)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $activeSheet->getStyle($col_line)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
                    $activeSheet->getStyle($col_line)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

                    //Set border colors 设置边框颜色
                    $activeSheet->getStyle($col_line)->getBorders()->getLeft()->getColor()->setARGB('000000');
                    $activeSheet->getStyle($col_line)->getBorders()->getTop()->getColor()->setARGB('000000');
                    $activeSheet->getStyle($col_line)->getBorders()->getBottom()->getColor()->setARGB('000000');
                    $activeSheet->getStyle($col_line)->getBorders()->getRight()->getColor()->setARGB('000000');

                    # excel 头部居中垂直对齐
                    $activeSheet->getStyle($col_line)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $activeSheet->getStyle($col_line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                }
                # 字体加粗
                $activeSheet
                    ->getStyle($col_line)
                    ->getFont()
                    ->setName('宋体')
                    ->setBold(true); //字体加粗
                # 动态设置列宽度
                #   'total_volume'  => [ "width" => 20,],
                if($sheet_styles) {
                    $col_styles  = isset($sheet_styles[$col_name]) ? $sheet_styles[$col_name] : [];
                    if($col_styles) 
                    {
                        foreach ($col_styles as $style => $style_val)
                        {
                            if($style == 'width') {
                                $activeSheet->getColumnDimension($col)->setWidth($style_val);
                            }
                        }
                    }
                }

            }
            //  填充第n(n>=2, n∈N*)行数据
            $sheet_data = isset($this->data[$sheet_map_name]) ? $this->data[$sheet_map_name] : [];
            # 合并请求
            $merge_map_data   =  isset($this->header_merge_map[$sheet_map_name]) ? $this->header_merge_map[$sheet_map_name] : [];
            $field_merge_map  = $this->getMergeMapData($merge_map_data);
            if($sheet_data)
            {
                $row_step_start_num = 1;
                $row_y_end =  0;
                foreach ($sheet_data as $data_index  => $item)
                {
                    foreach ($sheet_headers_keys as $kf =>  $filed)
                    {
                        $merge_index = ($field_merge_map && isset($field_merge_map[$filed])) ? $field_merge_map[$filed] : false;
                        $merge_info = [];
                        if($merge_index !== false) {
                            $merge_info = $merge_map_data[$merge_index];
                        }
                        $col = $sheet_header_col_map[$filed];
                        if($merge_info)
                        {
                            $merge_index   = $merge_info['merge_index'];
                            $row_num_index = $merge_info['row_num_index'];
                            if($merge_index)
                            {
                                # 需要合并的数据 子数据
                                $merge_data = $this->merge_get_index_data($item, $merge_index);
                                # 循环遍历数据
                                foreach ($merge_data as $mk => $m_data)
                                {
                                    $merge_line_num = 1; # 合并数默认1  即 不合并
                                    if($row_num_index) {
                                        $merge_line_num = array_get($m_data, $row_num_index);
                                    }
                                    $merge_line_incr_num = 0;
                                    if($merge_line_num > 1) {
                                        $merge_line_incr_num = $merge_line_num - 1;
                                    }

                                    # 坐标计算修改 | 要区分 一个报价单是多个调拨单 内部还有多个合并 合并表格的起始位置记录 计算规则不一致
                                    if($mk == 0) {
                                        $row_y_start   = $row_step_start_num + 1;
                                    }
                                    else
                                    {
                                        $row_y_start = $row_y_end + 1;
                                    }

                                    $row_y_end      = $row_y_start + ($merge_line_incr_num);

                                    $val       = isset( $m_data[$filed]) ?  $m_data[$filed] : '';
                                    $cell_line = $col. $row_y_start;
                                    $activeSheet->setCellValue($cell_line, $val);
                                    $activeSheet->getStyle($cell_line)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                                    $activeSheet->getStyle($cell_line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                                    if($row_y_start != $row_y_end) {
                                        $merge_line   = $cell_line . ':'. $col . ($row_y_end);
                                        $activeSheet->mergeCells($merge_line);
                                        $activeSheet->getStyle($merge_line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                                        $activeSheet->getStyle($merge_line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                                    }

                                }
                            }
                            else {
                                $merge_line_num = 1; # 合并数默认1  即 不合并
                                if($row_num_index) {
                                    $merge_line_num = array_get($item, $row_num_index);
                                }
                                $merge_line_incr_num =0;
                                if($merge_line_num > 1) {
                                    $merge_line_incr_num = $merge_line_num - 1;
                                }

                                $row_y_start    = $row_step_start_num + 1;
                                $row_y_end      = $row_y_start + $merge_line_incr_num;
                                $cell_line = $col. $row_y_start;
                                $val = isset( $item[$filed]) ?  $item[$filed] : '';
                                $activeSheet->setCellValue($cell_line, $val);
                                $activeSheet->getStyle($cell_line)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                                $activeSheet->getStyle($cell_line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                                if($row_y_start != $row_y_end) {
                                    $merge_line   = $cell_line . ':'. $col . ($row_y_end);
                                    $activeSheet->mergeCells($merge_line);
                                    $activeSheet->getStyle($merge_line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                                    $activeSheet->getStyle($merge_line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                                }

                            }
                        }
                        else
                        {
                            $col = $sheet_header_col_map[$filed];
                            $line = $data_index + 2;
                            $cell_line = $col. $line;
                            $val = isset( $item[$filed]) ?  $item[$filed] : '';
                            $activeSheet->setCellValue($cell_line, $val);
                        }
                    }
                    # 每次循环结束 记录最后一次操作的行数记录数
                    $row_step_start_num = $row_y_end;
                }
            }
        }
        # 设置打开的工作簿
        $this->objPHPExcel->setActiveSheetIndex(0);
        $file_name = $file_name ? $file_name :  "报价单列表_" .date("YmdHis");
        //*生成xlsx文件
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$file_name.'.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter=PHPExcel_IOFactory::createWriter($this->objPHPExcel,'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}
