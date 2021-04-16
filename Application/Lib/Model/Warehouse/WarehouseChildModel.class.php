<?php
/**
 * 入库
 * User: b5m
 * Date: 2018/6/27
 * Time: 17:02
 */
class WarehouseChildModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_warehouse_child';

    public function isInRule($warehouse_cd) {
        $real_in_storage = $this->where(['cd'=>$warehouse_cd])->getField('real_in_storage');
        if($real_in_storage == 1) return true;
        return false;
    }
}