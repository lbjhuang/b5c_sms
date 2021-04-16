<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2017/12/12
 * Time: 13:33
 */

class

TbWmsWarehouseParamsModel extends BaseModel
{
    protected $trueTableName = 'tb_wms_warehouse_params';

    protected $_auto = [
        ['is_disabled', 0],
        ['create_time', 'getTime', 1, 'callback'],
        ['update_time', 'getTime', 2, 'callback'],
        ['create_user_id', 'getName', 1, 'callback'],
        ['update_user_id', 'getName', 2, 'callback']
    ];

    /**
     * 根据仓库 id 获取这个仓库锁支持的国家 id 集
     * @param $warehouseId
     * @return array $countries 某个仓库所能发货的所有国家 id
     */
    public function getExistingCountry($warehouseId)
    {
        $ret = $this->where('warehouse_id = %d', [$warehouseId])->find();

        if ($ret) {
            $countries = array_column(json_decode($ret ['area'], true), 'id');
        } else {
            $countries = null;
        }

        return $countries;
    }
}