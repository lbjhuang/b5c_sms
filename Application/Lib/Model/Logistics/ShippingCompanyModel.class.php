<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/11/8
 * Time: 10:07
 */
class ShippingCompanyModel extends Model{
    protected $trueTableName = "tb_ms_shipper";
    
    /**
     * 读取单个发货公司的信息
     * @param $id
     * @return bool
     */
    public function getShipperById($id)
    {
        if (empty(trim($id))){
            return false;
        }
        
        return $this->where("id={$id}")->find();
    }
    
    /**
     * 读取列表
     * @return mixed
     */
    public function getShipperList()
    {
        return $this->select();
    }
}