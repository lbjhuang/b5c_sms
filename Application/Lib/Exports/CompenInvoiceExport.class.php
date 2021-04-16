<?php

class CompenInvoiceExport
{
    /**
     * 需要导出的工作簿字典
     * @var array
     */
    protected $sheets = [
        0 => "sheet1",
    ];
    protected $sheets_header_map = [
        "data" => [
            'spu_name_en' => '产品名称（英文名称）',
            'opt_name_value_str' => '属性',
            'item_count' => '数量',
            'communication' => '用途',
        ],
    ];

    //临时生成excel目录
    protected $save_excel_path = ATTACHMENT_DIR_EXCEL;
    protected $save_excel_dir = 'invoice-tmp/';//禁止修改目录
    //临时生成images目录
    protected $save_image_path = ATTACHMENT_DIR_IMG;
    protected $save_image_dir = 'image-tmp/';//禁止修改目录
    //临时生成压缩包目录
    protected $save_package_path = ATTACHMENT_DIR_DOC;
    protected $save_package_dir = 'package-tmp/';//禁止修改目录
    protected $save_package_name = 'invoice.zip';//指定压缩包生成名称

    //针对发票模板每一行的样式都有所不同，如下逐步按行进行单独配置。
    protected $invoice_excel_config = [
        [
            'merge_cells_range' => ['A1:I1'],//合并单元格坐标
            'values'            => ['Handelsrechnung'],//标题
            'values_pos'        => ['A1'],//标题写入坐标
            'row_height'        => '21',//行高
            'vertical'          => 'center',//垂直对齐方式
            'horizontal'        => 'center',//水平对齐方式
        ],
        [
            'merge_cells_range' => ['A2:E2', 'F2:I2'],
            'values'            => ['Sendungsnumme:', 'Rechnungsdatum：'],
            'values_pos'        => ['A2', 'F2'],
            'row_height'        => '32.25',
            'vertical'          => 'center',
        ],
        [
            'merge_cells_range' => ['A3:E3', 'F3:I3'],
            'values'            => ['waybill_number', 'export_date'],
            'values_pos'        => ['A3', 'F3'],
            'row_height'        => '32.25',
            'vertical'          => 'center',
            'horizontal'        => 'left',
            'text_colors'       => ['FF000000', 'FF000000'],//字体颜色-红色
        ],
        [
            'merge_cells_range' => ['A4:E4', 'F4:I4'],
            'values'            => ['Shipper/Absender(Anschrift)', 'Empfänger(Anschrift)'],
            'values_pos'        => ['A4', 'F4'],
            'row_height'        => '32.25',
            'vertical'          => 'center',
        ],
        [
            'merge_cells_range' => ['A5:E5', 'F5:I5'],
            'values'            => ['Absendernamen：Worldtech Logistics GmbH c/o GINDA GmbH', 'Empfängernamen：'],
            'values_pos'        => ['A5', 'F5'],
            'row_height'        => '32.25',
            'vertical'          => 'center',
            'variables'         => [null, 'address_user_name'],//变量（和values[i]组成一个单元格数据）
        ],
        [
            'merge_cells_range' => ['A6:E6', 'F6:I6'],
            'values'            => ["Absenderanschrift：\n Fiduciastr. 10C 76227 Karlsruhe", "Empfängeranschrift：\n "],
            'values_pos'        => ['A6', 'F6'],
            'row_height'        => '78',
            'vertical'          => 'center',
            'variables'         => [null, 'address']
        ],
        [
            'merge_cells_range' => ['A7:E7', 'F7:I7'],
            'values'            => ['Tel.:', 'Tel.:'],
            'values_pos'        => ['A7', 'F7'],
            'row_height'        => '21.75',
            'vertical'          => 'center',
        ],
        [
            'merge_cells_range' => ['B8:C8'],
            'values'            => ['Po.', 'Produktsname', 'Menge(st.)', 'Gewicht（KG', 'Einzelpreis(EUR)', 'Gesamtpreis (Netto)（EUR)', 'Mwst.（EUR)', 'Brutto-Gesamtpreis （EUR)'],
            'values_pos'        => ['A8', 'B8', 'D8', 'E8', 'F8', 'G8', 'H8', 'I8'],
            'row_height'        => '54',
            'vertical'          => 'center',
            'horizontal'        => 'center',
        ],
    ];

    //发票商品excel标题配置
    protected $invoice_goods_config = [
        'spu_name_en',
        'item_count',
        'weight',
        'trading_price',
        'netto_price',
        'mwst_price',
        'total_price',
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
     * 一键生成发票
     * @author Redbo He
     * @date 2021/2/18 10:50
     */
    public function generateInvoice($file_name = '')
    {
        $sheets_headers = array_keys($this->sheets_header_map);
        foreach ($sheets_headers as $index => $sheet_map_name) {
            //$sheet_map_name:data
            $sheet_title = isset($this->sheets[$index]) ? $this->sheets[$index] : '';
            # 创建多个工作簿
            if ($index > 0) {
                $this->objPHPExcel->createSheet($index);
            }
            $this->objPHPExcel->setActiveSheetIndex($index);
            $activeSheet = $this->objPHPExcel->getActiveSheet();
            $activeSheet->getDefaultStyle()->getFont()->setName('宋体');
            $activeSheet->getDefaultStyle()->getFont()->setSize(11);
            if ($sheet_title) {
                //$sheet_title:sheet1
                $activeSheet->setTitle($sheet_title);
            }

            $sheetColor = new PHPExcel_Style_Color();
            $activeSheet->getStyle('A1:I20')->getFont()->setBold(true);
//            $activeSheet->getColumnDimension('L')->setWidth(50.88);//设置L列宽
            $activeSheet->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $compensate_data = $this->data[0];
            foreach ($this->invoice_excel_config as $row => $excel_map) {
                if ($row == 0) {
                    $activeSheet->getStyle($excel_map['merge_cells_range'][0])->getFill()->getStartColor()->setARGB(PHPExcel_Style_Color::COLOR_BLACK);
                }
                //按配置合并单元格
                if (!empty($excel_map['merge_cells_range'])) {
                    $merge_cells_count = count($excel_map['merge_cells_range']);
                    for ($i = 0; $i < $merge_cells_count; $i++) {
                        $activeSheet->mergeCells($excel_map['merge_cells_range'][$i]);
                    }
                }
                if (!empty($excel_map['values'])) {
                    $values_count = count($excel_map['values']);
                    for ($i = 0; $i < $values_count; $i++) {
                        if (isset($compensate_data[$excel_map['values'][$i]])) {
                            //设置变量值
                            $activeSheet->setCellValue($excel_map['values_pos'][$i], $compensate_data[$excel_map['values'][$i]]);
                        } else {
                            if (isset($excel_map['variables']) && $excel_map['variables'][$i]) {
                                //一个单元格文字存在不同样式，进行特殊处理
                                $objRichText = new PHPExcel_RichText();
                                $objRichText->createText($excel_map['values'][$i]);
                                $objPayable = $objRichText->createTextRun($compensate_data[$excel_map['variables'][$i]]);
                                $objPayable->getFont()->setName('宋体');
                                $sheetColor->setRGB('FF000000');
                                $objPayable->getFont()->setColor($sheetColor);
                                $activeSheet->getCell($excel_map['values_pos'][$i])->setValue($objRichText);
                            } else {
                                //设置标题值
                                $activeSheet->setCellValue($excel_map['values_pos'][$i], $excel_map['values'][$i]);
                            }
                        }
                        if (isset($excel_map['vertical'])) {
                            //设置垂直对齐方式
                            $activeSheet->getStyle($excel_map['values_pos'][$i])->getAlignment()->setVertical($excel_map['vertical']);
                        }
                        if (isset($excel_map['horizontal'])) {
                            //设置水平对齐方式
                            $activeSheet->getStyle($excel_map['values_pos'][$i])->getAlignment()->setHorizontal($excel_map['horizontal']);
                        }
                        if (isset($excel_map['text_colors'])) {
                            if ($excel_map['text_colors'][$i]) {
                                //设置字体颜色
                                $sheetColor->setRGB($excel_map['text_colors'][$i]);
                                $activeSheet->getStyle($excel_map['values_pos'][$i])->getFont()->setColor($sheetColor);
                            }
                        }
                        //设置自动换行
                        $activeSheet->getStyle($excel_map['values_pos'][$i])->getAlignment()->setWrapText(TRUE);
                    }
                }
                if (!empty($excel_map['row_height'])) {
                    //设置行高
                    $activeSheet->getRowDimension($row + 1)->setRowHeight($excel_map['row_height']);
                }
            }
            $total_weight = $total_netto_price = $total_mwst_price = $all_total_price = '';
            $start_row    = 9;
            foreach ($this->data as $key => $item) {
                //汇总数据计算
                $total_weight      += $item['item_count'] * $item['weight'];
                $total_netto_price += $item['netto_price'];
                $total_mwst_price  += $item['mwst_price'];
                $all_total_price   += $item['total_price'];

                //商品数据单独动态配置
                $start_column = ord('A');
                $activeSheet->mergeCells('B' . $start_row . ':C' . $start_row);
                $activeSheet->getRowDimension($start_row)->setRowHeight(168.95);
                $activeSheet->getStyle(chr($start_column) . $start_row)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $activeSheet->getStyle(chr($start_column) . $start_row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $activeSheet->setCellValue(chr($start_column) . $start_row, $key + 1);//商品序号
                $sheetColor->setRGB('FF000000');
                $activeSheet->getStyle(chr($start_column) . $start_row)->getFont()->setColor($sheetColor);

                $start_column++;
                foreach ($this->invoice_goods_config as $goods_field) {
                    if ($goods_field == 'item_count') {
                        $start_column++;
                    }
                    $column_str = chr($start_column) . $start_row;
                    $activeSheet->getStyle($column_str)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $activeSheet->getStyle($column_str)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $activeSheet->setCellValue($column_str, $item[$goods_field]);
                    $activeSheet->getStyle($column_str)->getAlignment()->setWrapText(TRUE);
                    $sheetColor->setRGB('FF000000');
                    $activeSheet->getStyle($column_str)->getFont()->setColor($sheetColor);
                    $start_column++;
                }
                $start_row++;
            }
        }
        //设置汇总数据
        $activeSheet->mergeCells('A' . $start_row . ':C' . $start_row);
        $activeSheet->setCellValue('A' . $start_row, 'Summe');
        $activeSheet->setCellValue('D' . $start_row, '0');
        $activeSheet->setCellValue('E' . $start_row, $total_weight);
        $activeSheet->setCellValue('G' . $start_row, sprintf("%.2f",$total_netto_price));
        $activeSheet->setCellValue('H' . $start_row, sprintf("%.2f",$total_mwst_price));
        $activeSheet->setCellValue('I' . $start_row, sprintf("%.2f",$all_total_price));
        $activeSheet->getStyle('A' . $start_row . ':I' . $start_row)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $activeSheet->getStyle('A' . $start_row . ':I' . $start_row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $activeSheet->getRowDimension($start_row)->setRowHeight(21);
        //设置全部边框
        $style_array = [
            'borders' => [
                'allborders' => [
                    'style' => \PHPExcel_Style_Border::BORDER_THIN
                ],
            ],
        ];
        $activeSheet->getStyle('A1:I'.$start_row)->applyFromArray($style_array);

        //保存发票excel文件
        $this->saveInvoiceExcelFile($file_name);
    }

    /**
     * 发票商品excel文件生成
     * @param string $file_name
     */
    public function generateInvoiceGoods($file_name = '')
    {
        $sheets_headers = array_keys($this->sheets_header_map);
        foreach ($sheets_headers as $index => $sheet_map_name) {
            //$sheet_map_name:data
            $sheet_title = isset($this->sheets[$index]) ? $this->sheets[$index] : '';
            if ($index > 0) {
                $this->objPHPExcel->createSheet($index);
            }
            $this->objPHPExcel->setActiveSheetIndex($index);
            $activeSheet = $this->objPHPExcel->getActiveSheet();
            $activeSheet->getDefaultStyle()->getFont()->setName('宋体');
            $activeSheet->getDefaultStyle()->getFont()->setSize(11);
            if ($sheet_title) {
                //$sheet_title:sheet1
                $activeSheet->setTitle($sheet_title);
            }

            $activeSheet->getStyle('A1:G10')->getFont()->setBold(true);
            $activeSheet->getColumnDimension('A')->setWidth(50);//设置列宽
            $activeSheet->getColumnDimension('C')->setWidth(25);//设置列宽
            $activeSheet->getColumnDimension('G')->setWidth(17.13);//设置列宽
            //设置标题
            $start_column         = ord("A");
            $start_row            = 1;
            $sheet_headers        = $this->sheets_header_map[$sheet_map_name];
            $sheet_headers_values = array_values($sheet_headers);
            $sheet_headers_keys   = array_keys($sheet_headers);
            foreach ($sheet_headers_values as $header_name) {
                $pCoordinate = chr($start_column). $start_row;
                $activeSheet->setCellValue($pCoordinate, $header_name);
                $start_column = $start_column + 2;
            }
            $start_row++;
            //按单元格设置商品数据
            foreach ($this->data as $row => $item) {
                $start_column = ord("A");
                foreach ($sheet_headers_keys as $field) {
                    $pCoordinate = chr($start_column). $start_row;
                    $activeSheet->setCellValue($pCoordinate, $item[$field]);
                    $start_column = $start_column + 2;
                }
                $activeSheet->setCellValue('G'. $start_row, 'communication');
                $start_row ++;
            }

            //插入图片（多个）
            foreach ($this->data as $row => $item) {
                $objDrawing = new PHPExcel_Worksheet_Drawing();
                if (empty($item['image_url'])) {
                    continue;
                }
                //下载并保持cdn图片url
                $image_save_path = getImage($item['image_url'], $this->save_image_path. $this->save_image_dir);
                if (empty($image_save_path)) {
                    continue;
                }
                $objDrawing->setPath($image_save_path);
                $objDrawing->setHeight(300);//设置图片高度
                $objDrawing->setCoordinates('A'. $start_row);
                $objDrawing->setWorksheet($activeSheet);
                $start_row += 18;//每个图片的高度间隔设置，防止图片重叠
            }
        }
        $this->saveInvoiceExcelFile($file_name);
    }

    /**
     * 保存发票excel文件
     * @param $file_name
     */
    private function saveInvoiceExcelFile($file_name)
    {
        # 设置打开的工作簿
        $file_name = $file_name ? $file_name : "invoice_" . date("YmdHis");
        $file_name = iconv('GBK', 'UTF-8', $file_name);
        //*生成xlsx文件
        $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel2007');
//        $objWriter->save('php://output');
        $save_path = $this->save_excel_path. $this->save_excel_dir;
        if (!is_dir($save_path)) {
            if (!mkdir($save_path)) {
                throw new Exception(L("无权限创建目录，创建{$this->save_excel_dir}目录失败"));
            }
        }
        $objWriter->save($save_path. $file_name. '.xlsx');
    }

    /**
     * 压缩excel文件
     * @return bool
     */
    public function packInvoiceExcelFile()
    {
        if (!is_dir($this->save_package_path.$this->save_package_dir)) {
            if (!mkdir($this->save_package_path.$this->save_package_dir)) {
                throw new Exception(L("无权限创建目录，创建{$this->save_package_dir}目录失败"));
            }
        }
        $zip = new \ZipArchive;
        $open_res = $zip->open($this->save_package_path.$this->save_package_dir. 'invoice.zip', \ZipArchive::CREATE);
        if (!$open_res) {
            return false;
        }
        $excel_path = $this->save_excel_path. $this->save_excel_dir;
        if ($handle = opendir($excel_path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $zip->addFile($excel_path. $file, $file);
            }
        }
        closedir($handle);
        $zip->close();
    }

    /**
     * 压缩包下载
     * @param $file_name
     */
    public function downloadPackage($file_name)
    {
        $file_name = $file_name ? $file_name : "invoice" . date("YmdHis"). '.zip';
        $package_name = $this->save_package_path. $this->save_package_dir. $this->save_package_name;
        $fp=fopen($package_name,"r");
        $file_size=filesize($package_name);//获取文件的字节
        //下载文件需要用到的头
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length:".$file_size);
        header("Content-Disposition: attachment; filename=$file_name");
        $buffer=1024; //设置一次读取的字节数，每读取一次，就输出数据（即返回给浏览器）
        $file_count=0; //读取的总字节数
        //向浏览器返回数据 如果下载完成就停止输出，如果未下载完成就一直在输出。根据文件的字节大小判断是否下载完成
        while(!feof($fp) && $file_count<$file_size) {
            $file_con   = fread($fp, $buffer);
            $file_count += $buffer;
            echo $file_con;
        }
        fclose($fp);
        exit;
    }

    /**
     * 删除临时文件
     */
    public function deleteTmpFiles()
    {
        $package_path = $this->save_package_path. $this->save_package_dir. $this->save_package_name;
        $excel_path = $this->save_excel_path. $this->save_excel_dir;
        $image_path = $this->save_image_path. $this->save_image_dir;
        if (!is_dir($this->save_package_path. $this->save_package_dir)) {
            return ;
        }
        if (!is_dir($excel_path)) {
            return ;
        }
        if (!is_dir($image_path)) {
            return ;
        }

        //上次生成的压缩包删除
        if (is_file($package_path)) {
            unlink($package_path);
        }
        //上次生成的excel文件删除
        if ($handle = opendir($excel_path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                if (is_file($excel_path. $file)) {
                    unlink($excel_path. $file);
                }
            }
        }
        closedir($handle);
        //上次生成的图片删除
        if ($handle = opendir($image_path)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                if (is_file($image_path. $file)) {
                    unlink($image_path. $file);
                }
            }
        }
        closedir($handle);
    }
}
