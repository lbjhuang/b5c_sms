<?php
/**
 * User: yangsu
 * Date: 19/07/31
 * Time: 10:49
 */

/**
 * Class B2bRepository
 */
class LocationRepository extends Repository
{
    public function getLocationCodeByWarehouse($warehouse, $sku)
    {
        if(empty($sku) || empty($warehouse)){
            return [];
        }
        return $this->model->table('tb_wms_location_sku')->field('location_code,defective_location_code')->where(['warehouse_id' => $warehouse, 'sku' => $sku])->find();
    }

    public function getLocaltionSku($warehouse, $skus)
    {
        if(empty($skus)){
            return [];
        }
        $field = [
            'tb_wms_location_sku.id',
            'tb_wms_location_sku.warehouse_id',
            'tb_wms_warehouse.CD AS warehouse_cd',
            'tb_wms_location_sku.sku',
            'CONCAT(tb_wms_warehouse.CD,tb_wms_location_sku.sku) AS location_key',
            'tb_wms_location_sku.location_code',
            'tb_wms_location_sku.defective_location_code',
            'tb_wms_location_sku.location_code_back',
        ];
        $where['tb_wms_location_sku.sku'] = ['IN', $skus];
        $where_sting = "tb_wms_warehouse.id = tb_wms_location_sku.warehouse_id AND (tb_wms_location_sku.warehouse_id = '{$warehouse}' OR tb_wms_warehouse.CD = '{$warehouse}')";
        return $this->model->table('(tb_wms_location_sku,tb_wms_warehouse)')
            ->field($field)
            ->where($where)
            ->where($where_sting, null, true)
            ->cache(true, 3)
            ->select();
    }

    public function getLocaltionEnumerateSku($warehouse_cds, $skus)
    {
        $field = [
            'tb_wms_location_sku.id',
            'tb_wms_location_sku.warehouse_id',
            'tb_wms_warehouse.CD AS warehouse_cd',
            'tb_wms_location_sku.sku',
            'CONCAT(tb_wms_warehouse.CD,tb_wms_location_sku.sku) AS location_key',
            'tb_wms_location_sku.location_code',
            'tb_wms_location_sku.defective_location_code',
            'tb_wms_location_sku.location_code_back',
        ];
        $where['tb_wms_warehouse.CD'] = ['IN', $warehouse_cds];
        $where['tb_wms_location_sku.sku'] = ['IN', $skus];
        $where_sting = "tb_wms_warehouse.id = tb_wms_location_sku.warehouse_id";
        return $this->model->table('(tb_wms_location_sku,tb_wms_warehouse)')
            ->field($field)
            ->where($where)
            ->where($where_sting, null, true)
            ->cache(true, 3)
            ->select();
    }
}
