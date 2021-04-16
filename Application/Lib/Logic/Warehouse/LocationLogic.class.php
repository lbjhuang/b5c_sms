<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/22
 * Time: 13:21
 */

require_once APP_PATH.'Lib/Logic/BaseLogic.class.php';

class LocationLogic extends BaseLogic
{
    private static $model;
    public function __construct()
    {
        parent::__construct();
    }

    public function model() {
        if(self::$model)
            return self::$model;
        return self::$model = D('Location');
    }

    /**
     * @param $warehouse_id
     * @param $sku
     * @param $location_code
     * @param $defective_location_code
     * @return bool|mixed
     */
    public function recordLocation($warehouse_id,$sku,$location_code,$defective_location_code) {
        $this->model()->startTrans();
        $location_code_old = $this->model()->lock(true)->where(['warehouse_id'=>$warehouse_id,'sku'=>$sku])->getField('id');
        if($location_code_old) {
            if($location_code != '' && $location_code != $location_code_old) {
                $save = [
                    'location_code' => $location_code,
                    'update_user' => session('m_loginname')
                ];
                $res = $this->model()->where(['warehouse_id' => $warehouse_id, 'sku' => $sku])->save($save);
            }elseif($location_code == '') {
                $res = $this->model()->where(['warehouse_id' => $warehouse_id, 'sku' => $sku])->delete();
            }else {
                $res = true;
            }
        }else {
            if($location_code != '') {
                $add = [
                    'warehouse_id'  => $warehouse_id,
                    'sku'           => $sku,
                    'location_code' => $location_code,
                    'update_user'   => session('m_loginname'),
                ];
                $res = $this->model()->create($add) && $this->model()->add();
            }else {
                $res = true;
            }
        }
        if($res === false) {
            $this->model()->rollback();
            $this->error = $sku.'货位保存失败'.$this->model()->getError() ? '：'.$this->model()->getError() : '';
            return false;
        }
        $this->model()->commit();
        return $res;
    }

    /**
     * @param $arr [['warehouse_code','sku','location_code'],['']]
     * @return bool
     * 批量保存批次
     */
    public function recordLocationBatch($arr) {
        $this->model()->startTrans();
        foreach ($arr as $v) {
            if(!$v['warehouse_code'] || !$v['sku']) {
                $this->error = '参数异常';
                $this->model()->rollback();
                return false;
            }
            $warehouse_id   = D('Warehouse')->cache(10)->where(['CD'=>$v['warehouse_code']])->getField('id');
            $res            = $this->recordLocation($warehouse_id,$v['sku'],$v['location_code'],$v['defective_location_code']);
            if($res === false) {
                $this->model()->rollback();
                return false;
            }
        }
        $this->model()->commit();
        return true;

    }

}