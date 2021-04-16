<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2017/12/27
 * Time: 17:20
 */

class LocationModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_location_sku';

    protected $_validate = [
        ['warehouse_id','require','仓库ID必须'],
        ['sku','require','SKU必须'],
        ['location_code','require','货位编号必须'],
    ];

    /**
     * 返回查询条件
     * @param array $params
     * @return array
     */
    public function search($params)
    {
        $conditions = [];
        if ($params ['sku']) {
            $conditions ['t1.sku'] = ['like', '%' . $params ['sku'] . '%'];
        }
        if ($params ['warehouse_code']) {
            $conditions ['t1.warehouse_id'] = ['eq', $params ['warehouse_code']];
        }
        if ($params ['guds_nm']) {
            $conditions ['t6.spu_name'] = ['like', '%'. $params ['guds_nm'] . '%'];
        }
        if ($params ['location_code']) {
            $conditions ['t1.location_code'] = ['eq', $params ['location_code']];
        }
        if ($params ['location_code_back']) {
            $conditions ['t1.location_code_back'] = ['eq', $params ['location_code_back']];
        }
        if ($params ['defective_location_code']) {
            $conditions ['t1.defective_location_code'] = ['eq', $params ['defective_location_code']];
        }
        if ($params ['p']) {
            $_GET ['p'] = $_POST ['p'] = $params ['p'];
        }

        return $conditions;
    }
}