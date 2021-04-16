<?php

/**
 * 偏远地区配置
 */
class AreaConfigAction extends BaseAction
{
    public $omsService;
    public $remoteAreaRepository;

    public function _initialize()
    {
        parent::_initialize();
        $this->omsService = new OmsService();
        $this->remoteAreaRepository = new RemoteAreaRepository();
    }

    public function area_config()
    {
        $this->display();
    }

    public function saveConfig()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();

            $this->validateConfigData($request_data);

            $res = DataModel::$success_return;
            $res['code'] = 200;
            $this->omsService->saveRemoteAreaConfig($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateConfigData($data)
    {
        $rules = [];
        if (isset($data['config_id'])) {
            $rules = [
                'country_id' => 'required|numeric',
                'prefix_postal_code' => 'required|string|min:1',
                'config_id' => 'required|numeric'
            ];
        } else {
            foreach ($data as $key => $value) {
                $rules["{$key}.country_id"] = 'required|numeric';
                $rules["{$key}.prefix_postal_code"] = 'required|string|min:1';
            }
        }
        $attributes = [
            'country_id' => '国家id',
            'prefix_postal_code' => '邮编',
            'config_id' => '配置id',
        ];
        $this->validate($rules, $data, $attributes);
    }

    public function configInfo()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();

            $res = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = $this->omsService->getConfigInfo($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function configList()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();

            $res = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = $this->omsService->remoteAreaConfigList($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function deleteConfig()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();

            $res = DataModel::$success_return;
            $res['code'] = 200;
            $this->omsService->deleteRemoteAreaConfig($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function import()
    {
        $res = $this->remoteAreaRepository->import();
        if($res['status']) {
            return $this->ajaxSuccess($res['msg']);
        } else  {
            return $this->ajaxError($res['data'], $res['msg']);
        }
    }

}