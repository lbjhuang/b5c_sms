<?php

class RealLgtCompanyAction extends BaseAction
{

    public $realLgtService;

    public function _initialize()
    {
        parent::_initialize();
        $this->realLgtService = new RealLgtCompanyService();
    }

    // 获取实际物流公司列表（key-value）
    public function getComNameKeyValue()
    {
        try {
            $res = DataModel::$success_return;
            unset($res['info']);
            $res['code'] = 200;
            $res['data'] = $this->realLgtService->getComNameKeyValue();
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res); 
    }
    // 获取实际物流公司列表含详情
    public function getComInfoList()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();

            $res = DataModel::$success_return;
            unset($res['info']);
            $res['code'] = 200;
            $res['data'] = $this->realLgtService->realCompanyInfoList($request_data);
            $res['data']['page']['this_page'] = $request_data['page']['this_page'];

        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);        
    }
    /**
     * 创建/编辑实际物流公司
     */
    public function saveComInfo()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $this->validateConfigData($request_data);

            $res = DataModel::$success_return;
            unset($res['info']);
            $this->realLgtService->saveLgtRealCompany($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);

    }

    private function validateConfigData($data)
    {

        $rules = [];

        $rules = [
            'logistics_name' => 'required',
            //'com_en_name' => 'required|string',
            // 'com_sort_name' => 'required|string',
            // 'service_code' => 'required',
            'lgt_track_platform_cd' => 'required|string|min:10',
            'level' => 'required|numeric|min:1',
            

        ];
        $attributes = [
            'logistics_name' => '实际物流公司名称',
            //'com_en_name' => '实际物流公司英文名称',
            // 'com_sort_name' => '物流公司拼音代码',
            // 'service_code' => '服务代码',
            'lgt_track_platform_cd' => '物流轨迹平台CD',
            'level' => '优先级',
           
        ];
        if(empty($data['rcd_id'])){
            //为新增须验证optional
            $rules['optional'] = 'required|numeric|min:1';
            $attributes['optional'] = '是否选择备用物流';
        }
        // $this->validate($rules, $data, $attributes);
        ValidatorModel::validate($rules, $data, $attributes);
        $message = ValidatorModel::getMessage();
        if ($message && strlen($message) > 0) {
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            foreach ($this->error_message as $key => $value) {
                throw new Exception($value[0], 40001);
            }
        }
    }

    public function realComInfo()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();

            $res = DataModel::$success_return;
            unset($res['info']);
            $res['code'] = 200;
            $res['data'] = $this->realLgtService->getRealComInfo($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function deleteRealCompanyInfo()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();

            $res = DataModel::$success_return;
            unset($res['info']);
            $res['code'] = 200;
            $this->realLgtService->deleteRealCompanyInfo($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }
}