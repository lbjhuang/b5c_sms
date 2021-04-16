<?php
/**
 * 区域和收货地址接口处理
 * 
 * User: afanti
 * Date: 2017/11/1
 * Time: 15:42
 */
class AreaAction extends BaseAction{
    public function _initialize()
    {
    }

    public function getAreaById()
    {
        $params = file_get_contents("php://input");
        $params = json_decode($params, true);
    
        if (empty($params['id'])){
            $result = ['code' => 40001, 'msg' => L('INVALID_PARAMS'), 'data' => null];
            $this->jsonOut($result);
        }
    
        $areaMode = new AreaModel();
        $cities = $areaMode->getAreaById($params['id']);
        $this->jsonOut(['coe' => 200, 'msg' => 'success', 'data' => $cities]);
    }
    
    public function getAreaByNo()
    {
        $params = file_get_contents("php://input");
        $params = json_decode($params, true);
    
        if (empty($params['no'])){
            $result = ['code' => 40001, 'msg' => L('INVALID_PARAMS'), 'data' => null];
            $this->jsonOut($result);
        }
    
        $areaMode = new AreaModel();
        $cities = $areaMode->getAreaByNo($params['no']);
        $this->jsonOut(['coe' => 200, 'msg' => 'success', 'data' => $cities]);
    }
    
    /**
     * 读取所有国家列表
     */
    public function getCountries(){
        $areaMode = new AreaModel();
        $countries = $areaMode->getAreaByType(AreaModel::COUNTRY);
        $this->jsonOut(['coe' => 200, 'msg' => 'success', 'data' => $countries]);
    }
    
    /**
     * 根据国家区域编码读取省、州列表
     */
    public function getProvince()
    {
        $params = file_get_contents("php://input");
        $params = json_decode($params, true);
    
        if (empty($params['countryNo'])){
            $result = ['code' => 40001, 'msg' => L('INVALID_PARAMS'), 'data' => null];
            $this->jsonOut($result);
        }
    
        $areaMode = new AreaModel();
        $cities = $areaMode->getStateAndProvince($params);
        $this->jsonOut(['coe' => 200, 'msg' => 'success', 'data' => $cities]);
        
    }
    
    /**
     * 根据省份区域编码读取城市列表
     */
    public function getCities()
    {
        $params = file_get_contents("php://input");
        $params = json_decode($params, true);
        
        if (empty($params['provinceNo'])){
            $result = ['code' => 40001, 'msg' => L('INVALID_PARAMS'), 'data' => null];
            $this->jsonOut($result);
        }
        
        $areaMode = new AreaModel();
        $cities = $areaMode->getCities($params);
        $this->jsonOut(['coe' => 200, 'msg' => 'success', 'data' => $cities]);
    }

    /**
     * 读取所有国家列表(含洲际)
     */
    public function getInterCounty()
    {
        $areaMode = new AreaModel();
        $countries = $areaMode->getInterAreaByType(AreaModel::COUNTRY);
        $this->jsonOut(['coe' => 200, 'msg' => 'success', 'data' => $countries]);
    }
    
}