<?php
/**
 * User: yangsu
 * Date: 19/07/31
 * Time: 10:31
 */

class LocationService extends Service
{
    /**
     * @var string
     */
    public $warehouse_key = 'warehouse_cd';
    /**
     * @var string
     */
    public $sku_key = 'sku_id';
    /**
     * @var array
     */
    private $location_key_array = ['location_code', 'defective_location_code', 'location_code_back'];

    public $model;
    public $user_name;
    private $_location_table;

    /**
     * FinanceService constructor.
     */
    public function __construct()
    {
        $this->repository      = new LocationRepository();
        $this->model           = empty($model) ? new Model() : $model;
        $this->_location_table = M('location_sku', 'tb_wms_');
        $this->user_name       = DataModel::userNamePinyin();
    }

    /**
     * @param $warehouse
     * @param $skus
     * @param array $intrusion_data
     *
     * @return null
     */
    public function obtain($warehouse, $skus, &$intrusion_data = [])
    {
        if (empty($skus)) {
            return null;
        }
        if (!is_string($warehouse)) {
            return null;
        }
        if (is_string($skus)) {
            $skus = (array)$skus;
        }
        $location_db = $this->repository->getLocaltionSku($warehouse, $skus);
        if (empty($location_db)) {
            return null;
        }
        $locations = array_combine(array_column($location_db, 'sku'), array_values($location_db));
        if (!empty($intrusion_data)) {
            foreach ($intrusion_data as &$datum) {
                foreach ($this->location_key_array as $item) {
                    $datum[$item] = $locations[$datum[$this->sku_key]][$item];
                }
            }
        } else {
            return $locations;
        }
    }

    /**
     * @param $intrusion_data
     *
     * @return null
     */
    public function obtainEnumerate(&$intrusion_data)
    {
        if (!is_array($intrusion_data)) {
            return null;
        }
        $warehouses = array_column($intrusion_data, $this->warehouse_key);
        $skus = array_column($intrusion_data, $this->sku_key);
        $location_db = $this->repository->getLocaltionEnumerateSku($warehouses, $skus);
        $locations = array_combine(array_column($location_db, 'location_key'), array_values($location_db));
        foreach ($intrusion_data as &$datum) {
            foreach ($this->location_key_array as $item) {
                $datum[$item] = $locations[$datum[$this->warehouse_key] . $datum[$this->sku_key]][$item];
            }
        }

    }

    /**
     * 批量添加/更新货位
     * @param $data
     * @return bool
     */
    public function recordLocationBatch($data)
    {
        $this->model->startTrans();
        foreach ($data as $v) {
            if(!$v['warehouse_code'] || !$v['sku']) {
                Logs(json_encode($v), __FUNCTION__.'----fix--1', 'tr');
                $this->model()->rollback();
                return false;
            }
            $warehouse_id = D('Warehouse')->cache(10)->where(['CD'=>$v['warehouse_code']])->getField('id');
            if (!$warehouse_id) {
                Logs(json_encode($v), __FUNCTION__.'----fix--2', 'tr');
                $this->model->rollback();
                return false;
            }
            $where = ['warehouse_id'=>$warehouse_id, 'sku'=>$v['sku']];
            if (!$v['location_code'] && !$v['defective_location_code']) {
                $this->_location_table->where($where)->delete();
                continue;
            }
            $location_info = $this->obtain($v['warehouse_code'], $v['sku']);
            $v['update_user'] = $this->user_name;
            unset($v['warehouse_code']);
            if (empty($location_info)) {
                $v['warehouse_id'] = $warehouse_id;
                if (!$this->_location_table->add($v)) {
                    Logs(json_encode($v), __FUNCTION__.'----fix--3', 'tr');
                    $this->model()->rollback();
                    return false;
                }
            } else {
                $res = $this->_location_table->where($where)->save($v);
                if (false === $res) {
                    $log_data['where'] = $where;
                    $log_data['data'] = $v;
                    Logs(json_encode($log_data), __FUNCTION__.'----fix--4', 'tr');
                    $this->model()->rollback();
                    return false;
                }
            }
        }
        $this->model->commit();
        return true;
    }
}