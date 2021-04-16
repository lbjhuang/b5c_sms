<?php

class SupplierExport
{
    /**
     * 需要导出的工作簿字典
     * @var array
     */
    protected $sheets = [
        0 => "供应商列表",
    ];

    protected $sheets_styles = [
        0 => [
            'SP_NAME' => [ "width" => 50, ],
            'SP_RES_NAME' => [ "width" => 30, ],
            'SP_NAME_EN' => [ "width" => 30, ],
            'SP_RES_NAME_EN' => [ "width" => 15, ],
            'SP_TEAM_CD_val' => [ "width" => 20, ],
            'registered_address'  => [ "width" => 20,],
            'work_address'  => [ "width" => 20,],
            'CREATE_USER_NAME'  => [ "width" => 15,],
            'EST_TIME'  => [ "width" => 15,],
            'SUB_CAPITAL_val'  => [ "width" => 15,],
            'LG_REP'  => [ "width" => 15,],
            'SHARE_NAME'  => [ "width" => 30,],
            'CREDIT_SCORE'  => [ "width" => 10,],
            'CREDIT_GRADE_val'  => [ "width" => 10,],
        ]
    ];

    protected $sheets_header_map = [
        [
            'SP_NAME' => '供应商名称',
            'SP_RES_NAME' => '供应商简称',
            'SP_NAME_EN' => '英文名称',
            'SP_RES_NAME_EN' => '英文简称',
            'SP_TEAM_CD_val' => '采购团队',
            'registered_address' => '注册地址',
            'work_address' => '办公地址',
            'CREATE_USER_NAME' => '创建人',
            'EST_TIME' => '成立时间',
            'SUB_CAPITAL_val'      => '认缴资本',
            'LG_REP'   => '法人代表',
            'SHARE_NAME' => '股东名称',
            'CREDIT_SCORE' => '信用评分',
            'CREDIT_GRADE_val' => '信用评级',
        ],
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
            if($sheet_data)
            {
                foreach ($sheet_data as $data_index  => $item)
                {
                    foreach ($sheet_headers_keys as $kf =>  $filed)
                    {
                        $col = $sheet_header_col_map[$filed];
                        $line = $data_index + 2;
                        $cell_line = $col. $line;
                        $val = isset( $item[$filed]) ?  $item[$filed] : '';
                        $activeSheet->setCellValue($cell_line, $val);
                        $activeSheet->getStyle($cell_line)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $activeSheet->getStyle($cell_line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    }
                }
            }
        }
        # 设置打开的工作簿
        $this->objPHPExcel->setActiveSheetIndex(0);
        $file_name = $file_name ? $file_name :  "供应商列表_" .date("YmdHis");
        //*生成xlsx文件
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$file_name.'.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter=PHPExcel_IOFactory::createWriter($this->objPHPExcel,'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}
