<?php

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class ExportInveLogic extends BaseLogic
{
    protected $sheet                        = ['盘点单信息SKU维度'];
    protected $sheet_en                     = ['invesku'];
    protected $cell_invesku                   = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
    protected $cell_name_invesku              = ['货位号', 'SKU编码','条形码','商品名称','属性','数量（=在库库存）', '商品类型'];
    protected $cell_value_key_invesku         = ['locationNo', 'skuId','upcId','gudsNm','attr','amountTotalNum', 'productType'];
    protected $cell_invebatch                 = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH'];


    public function exportInveGoods($where) {
        set_time_limit(0);
        vendor('PHPExcel.PHPExcel');
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        $cacheSettings = array( 'memoryCacheSize' => '512MB');
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod,$cacheSettings);
        $fileName = 'invegoods'.time().'.xls';
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $objExcel = new PHPExcel();

        foreach ($this->sheet as $k => $v) {
            if($k != 0)
                $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($k)->setTitle($v);
        }

        foreach ($this->sheet_en as $k => $v) {
            $cell_name_key = 'cell_name_'.$v;
            foreach ($this->$cell_name_key as $key => $val) {
                $objExcel->setActiveSheetIndex($k)->setCellValue($this->cell_invebatch[$key] . '1', $val);
            }
        }


        $current_row_invesku      = 0;


        $invesku = $this->getInveSku($where);

        //插入表格
        foreach ($this->cell_value_key_invesku as $k => $v) {
            $current_row_invesku = 0;
            foreach ($invesku as $ik => $iv) {
                $current_row_invesku++;
                $objExcel->setActiveSheetIndex(0)->setCellValue($this->cell_invesku[$k] . ($current_row_invesku+1), $iv[$v]."\t");
            }
        }
        foreach ($this->cell_value_key_invebatch as $k => $v) {
            $current_row_invebatch = 0;
            foreach ($invebatch as $ik => $iv) {
            $current_row_invebatch++;
                $objExcel->setActiveSheetIndex(1)->setCellValue($this->cell_invebatch[$k] . ($current_row_invebatch+1), $iv[$v]."\t");
            }
        }

        
        $objExcel->setActiveSheetIndex(0);
        $objWriter = \PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
        $objWriter->save('php://output');
    }



    protected function getInveSku($where) {
        $model = new StandingExistingModel();
        $data = $model->getInveData($where, true);
        $tempData = [];
        $warehouse_id = WarehouseModel::getWarehouseIdByCode($where['warehouse'][0]);
        $locationRepository = new LocationRepository();
        $inventoryService = new InventoryService();
        foreach ($data as $key => $value) {
            $tempData[$key]['skuId'] = $value['skuId'];
            $tempData[$key]['upcId'] = $value['upcId'];
            $tempData[$key]['gudsNm'] = $value['gudsName'];
            $tempData[$key]['attr'] = $value['optAttr'];
            $tempData[$key]['amountTotalNum'] += $value['amountTotalNum'];
            $tempData[$key]['locationNo'] = $inventoryService->getInveGoodsLocationCodeByWarehouse($warehouse_id, $value['skuId'], $locationRepository, $value['productType']);
            $tempData[$key]['productType'] = $value['productType'];
        }
        return $tempData;
    }
}