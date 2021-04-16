<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/5/4
 * Time: 13:31
 */

class ExportModel extends ExportExcelModel
{
    public $exportStyle = false;
    public function packData()
    {
        foreach ($this->data as $key => &$value) {
            if ($value ['deliveryStatus'] == 0)
                $value ['deliveryStatus'] = '未对接';
            else
                $value ['deliveryStatus'] = '已对接';
        }
    }

    public function export($savePath = 'php://output')
    {
        if (count($this->data) < $this->threshold) {
            try {
                $this->setMainTitle();
                $this->setColumnTitle();
                $this->packData();
                $this->parseData();

                //是否需要样式
                if ($this->exportStyle) {
                    $this->setExportStyle();
                }

                $this->setFileName();
                // send header
                $this->sendHeaders();
                // send content
                $objWriter = PHPExcel_IOFactory::createWriter($this->phpExcel, 'Excel5');
                $objWriter->save($savePath);
            } catch (PHPExcel_Writer_Exception $e) {
                exit($e->getMessage());
            }
        } else {
            $this->mosaicData();
            $this->save($savePath);
        }
    }

    public function setExportStyle()
    {
        try {
            if ($this->attributes) {
                $column_map = array_keys($this->attributes);
            } else {
                $column_map = range('A', 'Z');
            }
            //冻结首行
            $this->phpExcel->setActiveSheetIndex(0);
            $this->phpExcel->getActiveSheet()->freezePane('A2');
            foreach ($column_map as $item) {
                $width = ($item == 'A') ? 30 : 20;
                $this->phpExcel->getActiveSheet()->getColumnDimension($item)->setWidth($width);
                ////$this->phpExcel->getActiveSheet()->getColumnDimension($item)->setAutoSize(true);
                $this->phpExcel->getActiveSheet()->getStyle($item . 1)->getFont()->setName('微软雅黑')->setBold(true); //字体加粗
            }
        } catch (PHPExcel_Writer_Exception $e) {
            exit($e->getMessage());
        }
    }
}