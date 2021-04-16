<?php
/**
 * User: yangsu
 * Date: 18/10/17
 * Time: 17:02
 */


class ExcelModel
{
    /**
     * @var array
     */
    public $column_width = [];
    /**
     * @var PHPExcel
     */
    private $objPHPExcel;

    /**
     * ExcelModel constructor.
     */
    public function __construct()
    {
        vendor("PHPExcel.PHPExcel");
        $this->objPHPExcel = new PHPExcel();
    }

    /**
     * @param $exp_title
     * @param $exp_cell_name
     * @param $exp_table_data
     * @param null $file_name
     *
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function export($exp_title, $exp_cell_name, $exp_table_data, $file_name = null)
    {
        $this->assemblySheet($exp_table_data, $exp_cell_name);
        $fileName = $exp_title . date('_YmdHis');
        if (empty($file_name)) {
            $file_name = 'php://output';
            header('pragma:public');
            header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $exp_title . '.xls"');
            header("Content-Disposition:attachment;filename=$fileName.xls"); //attachment新窗口打印inline本窗口打印
        }
        $objWriter = \PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel5');
        $objWriter->save($file_name);
        // exit;
    }
    
    /**
     * @param $exp_title
     * @param array $exp_cell_name_arr
     * @param array $exp_table_data_arr
     *
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function manySheetExport($exp_title, array $exp_cell_names, array $exp_table_datas, array $sheet_name_maps = [])
    {
        $sheet_index = 0;
        foreach ($exp_cell_names as $key => $exp_cell_name) {
            if ($sheet_index > 0) {
                $this->objPHPExcel->createSheet();
            }
            $exp_table_data = $exp_table_datas[$key];
            $this->assemblySheet($exp_table_data, $exp_cell_name, $sheet_index);
            if ($sheet_name_maps[$key]) {
                $this->objPHPExcel->getActiveSheet($key)->setTitle($sheet_name_maps[$key]);
            }
            $sheet_index++;
        }
        $fileName = $exp_title . date('_YmdHis');
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $exp_title . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls"); //attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * @param array $exp_table_data
     * @param $exp_cell_name
     * @param $objPHPExcel
     * @param int $sheet_index
     * @param null $width
     *
     * @return PHPExcel
     * @throws PHPExcel_Exception
     */
    private function assemblySheet(array $exp_table_data, $exp_cell_name, $sheet_index = 0, $width = null)
    {
        $cellNum = count($exp_cell_name);
        $dataNum = count($exp_table_data);

        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S',
            'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL',
            'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        for ($i = 0; $i < $cellNum; $i++) {
            $this->objPHPExcel
                ->setActiveSheetIndex($sheet_index)
                ->setCellValue($cellName[$i] . '1', $exp_cell_name[$i][1]);
            if (!empty($width)) {
                if ($this->column_width['type'] == 'auto_size') {
                    $this->objPHPExcel->getActiveSheet($sheet_index)
                        ->getColumnDimension($cellName[$i])
                        ->setAutoSize(true);
                }
                if ($this->column_width['size']) {
                    $this->objPHPExcel->getActiveSheet($sheet_index)
                        ->getColumnDimension($cellName[$i])
                        ->setWidth($width['size'][$cellName[$i]]);
                }
            }
        }
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                if (!empty($exp_table_data[$i][$exp_cell_name[$j][0]]) || null !== $exp_table_data[$i][$exp_cell_name[$j][0]]) {
                    $val_data = (string)$exp_table_data[$i][$exp_cell_name[$j][0]];
                    $this->objPHPExcel
                        ->getActiveSheet($sheet_index)
                        ->setCellValueExplicit($cellName[$j] . ($i + 2), $val_data, PHPExcel_Cell_DataType::TYPE_STRING);

                }
            }
        }
        return $this->objPHPExcel;
    }


}