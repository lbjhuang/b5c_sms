<?php

/**
 * huanzhu
 * excel try  2017 11/17
 */
class ExcelOperation
{
    public $title = '';   //活动页
    public $filename = '';  //导出文件名
    public $table_name = '';  //导出表名
    public $obj = null;  //导出表名
    public $objectPHPExcel;
    public $matchKeyTitle = array();

    public function __construct()
    {
        vendor("PHPExcel.PHPExcel");
        ini_set('memory_limit', '512M');
        $this->objectPHPExcel = new PHPExcel();
        $tableObj = D($this->table_name);
        $this->obj = $tableObj;
    }

    private function findExcelLable($lable)
    {
        $arr = array();
        $use_ArrData = $this->matchKeyTitle;
        $valArr = array_column($use_ArrData, 'label', 'field');
        $ret = isset($valArr[$lable]) ? $valArr[$lable] : null;
        return $ret;
    }

    //导出
    public function export($ids)
    {
        $excelinfo = $this->matchKeyTitle;
        $excelinfo = array_column($excelinfo, 'field');
        $arrId = explode(',', $ids);
        foreach ($arrId as $v) {
            $dataAllData = D($this->table_name)
                ->where('ID=' . $v)
                ->find();
            $dataAll[] = $dataAllData;
        }


        $this->objectPHPExcel = new PHPExcel();
        $this->objectPHPExcel->setActiveSheetIndex(0);
        $this->objectPHPExcel->getActiveSheet()->setTitle($this->title);
        $objSheet = $this->objectPHPExcel->getActiveSheet(); //获取当前活动sheet

        if (empty($dataAll)) {
            header('Content-Type:application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $this->filename . '"');//告诉浏览器将输出文件的名称
            $objWriter = PHPExcel_IOFactory::createWriter($this->objectPHPExcel, 'Excel5');
            $objWriter->save('php://output');
            return null;
        }

        $line = 1;
        $i = 0;
        foreach ($excelinfo as $key => $val) {
            //将序列转化为A、B、C
            $col_cell = PHPExcel_Cell::stringFromColumnIndex($i);    //超过26行返回AA、AB...
            $objSheet->getStyle($col_cell)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);   //设置每个字段文本格式
            $use_cell = PHPExcel_Cell::stringFromColumnIndex($i) . $line;
            $use_val = $this->findExcelLable($val);
            $objSheet->setCellValue($use_cell, $use_val);   //写数据
            ++$i;
        }
        $line = 2;
        foreach ($dataAll as $k_data => $v_data) {
            $i = 0;
            foreach ($excelinfo as $key => $val) {
                $use_cell = PHPExcel_Cell::stringFromColumnIndex($i) . $line;
                $objSheet->setCellValueExplicit($use_cell, $v_data[$val], PHPExcel_Cell_DataType::TYPE_STRING);
                ++$i;
            }
            ++$line;

        }
        header('Content-Type:application/vnd.ms-excel');
        header('Cache-Control: max-age=0');//禁止缓存
        header('Content-Disposition: attachment;filename="' . $this->filename . '"');//告诉浏览器将输出文件的名称
        $objWriter = PHPExcel_IOFactory::createWriter($this->objectPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }


    /* 导入数据的处理成数组
  /* @access public
  /* @param $file_path 文件路径，可以是字符串逗号分隔多个地址的字符串，也可以是单个地址字符串
  /* @param $time_item 需要转换的格式的列序号
  /* @param $start_row  第几行开始读取数据
  /* @param $is_filter_empty 是否过滤空值
  /* @param $want_key_format  是否格式化成列名和数字组成的键

 */

    public function getImportData($file_path, $sheet_index, $time_item = array(), $start_row = 3, $is_filter_empty = true, $want_key_format=false)
    {
        // 包含PHPExcel 文件
        $objPHPExcelReader = PHPExcel_IOFactory::load($file_path);
        $reader = $objPHPExcelReader->getWorksheetIterator();
        //循环读取sheet
        $res_arr = array();
        foreach ($reader as $k => $sheet) {
            if ($k <= $sheet_index) {
                //读取表内容
                $content = $sheet->getRowIterator();
                //逐行处理
                foreach ($content as $items) {
                    $rows = $items->getRowIndex();       //行
                    $columns = $items->getCellIterator();  //列
                    /*遇空单元格不跳过*/
                    $columns->setIterateOnlyExistingCells($is_filter_empty);
                    $row_arr = array();
                    //确定从哪一行开始读取
                    if ($rows < $start_row) {
                        continue;
                    }
                    //逐列读取
                    foreach ($columns as $head => $cell) {
                        //获取cell中数据
                        if (in_array($head, $time_item)) {
                            $data = gmdate("Y-m-d H:i:s", PHPExcel_Shared_Date::ExcelToPHP($cell->getValue()));
                        } else {
                            $data = trim($cell->getValue());
                        }

                        //是否格式化成列名和数字组成的键
                        if($want_key_format){
                            $cell_char = PHPExcel_Cell::stringFromColumnIndex($head);
                            $item['cell_name'] =  $cell_char.$rows;
                            $item['cell_value'] = $data;
                            $row_arr[] = $item;
                        }else{
                            $row_arr[] = $data;
                        }

                    }
                    !empty($row_arr) && $res_arr[$rows] = $row_arr;
                }
            }

        }
        return $res_arr;
    }


    //获取导入的列名字
    public function getImportHeader($file_path, $sheet_index, $header_row = 1)
    {
        // 包含PHPExcel 文件
        $objPHPExcelReader = PHPExcel_IOFactory::load($file_path);
        $reader = $objPHPExcelReader->getWorksheetIterator();
        //循环读取sheet
        $row_name = [];
        foreach ($reader as $k => $sheet) {
            if ($k <= $sheet_index) {
                $row_data = [];
                //读取表内容
                $content = $sheet->getRowIterator();

                //逐行处理
                foreach ($content as $items) {
                    $rows = $items->getRowIndex();       //行
                    $columns = $items->getCellIterator();  //列
                    //确定从哪一行开始读取
                    if ($rows > $header_row) {
                        continue;
                    }
                    //逐列读取
                    foreach ($columns as $head => $cell) {
                        //获取cell中数据
                        $data = trim($cell->getValue());
                        if (empty($data)){
                            continue;
                        }
                        $row_data[] = $data;
                    }
                    !empty($row_data) && $row_name[$sheet_index] = $row_data;
                }
            }

        }
        return $row_name;
    }

}


?>