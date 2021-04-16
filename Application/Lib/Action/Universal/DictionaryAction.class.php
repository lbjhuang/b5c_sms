<?php
/**
 * 通用配置管理接口，这里以后主要负责字典码的管理。
 *
 *
* User: afanti
* Date: 2017/10/12
* Time: 18:35
*/
class DictionaryAction extends BaseAction {

    public $CodeService;

    public function _initialize()
    {
        $this->CodeService = new CodeService();
    }

    public function getCodeList()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = $this->CodeService->codeSearchList($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    public function exportCodeList(){
        session_write_close();
        set_time_limit(0);
        $request_data = DataModel::getDataNoBlankToArr();
        $list = $this->CodeService->codeSearchList($request_data,true);
        $data = $list['data'];
        $this->CodeService->exportXls($data);
    }
    
    /**
     * 根据指定的类型，字典码前缀读取字典列表，支持多个获取。
     * -如果多个读取前缀提供多个以逗号分隔
     * -返回JSON数据列表
     */
    public function getDictionaryList()
    {
        $prefix = I('prefix');
        $need_count = I('need_count'); //是否需要返回统计数量
        if (empty($prefix)){
            $resData['prefix'] = $prefix;
            $resData['need_count'] = $need_count;
            $result = array('code' => 4001, 'msg' =>  L('INVALID_PARAMS'), 'data' => $resData);
            $this->jsonOut($result);
        }
        
        $prefixList = explode(',', $prefix);
        
        $directoryModel = new DictionaryModel();
        $dict = $directoryModel->getDictByType($prefixList);
        if (!empty($need_count)) {
            $dict['count'] = $this->CodeService->getCodeCountsInfo($dict, $prefix);
        }

        $result = array('code' => 200, 'msg' => 'success', 'data' => $dict);
        $this->jsonOut($result);
    }
    
    /**
     * 读取指定的单个CODE
     */
    public function getDictionaryByCd()
    {
        $code = I('code');
        if (empty($code) || strlen($code) < 10){
            $result = array('code' => 4001, 'msg' => L('INVALID_PARAMS'), 'data' => null);
            $this->jsonOut($result);
        }
    
        $directoryModel = new DictionaryModel();
        $dict = $directoryModel->getDictionaryByCd($code);
        
        $result = array('code' => 200, 'msg' => 'success', 'data' => $dict);
        $this->jsonOut($result);
    }

    /**
     * 读取CODE TYPE下拉列表
     */
    public function getCdTypeKeyVal()
    {
        $status = I('status');

        try {
            if (!isset($status)) {
                throw new Exception(L('INVALID_PARAMS'));
            }
            $res = DataModel::$success_return;
            unset($res['info']);
            $res['data'] = $this->CodeService->getCodeTypeKeyVal($status);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->jsonOut($res);
    }

    // 获取某个code type 或全部 的详细信息
    public function getCdTypeList()
    {
        try {
            $res = DataModel::$success_return;
            unset($res['info']);
            $res['data'] = $this->CodeService->getCdTypeList(I('cd_type'));
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->jsonOut($res);
    }
    
    // 创建code type
    public function createCdType()
    {
        try {
            $request_data = I('post.');

            if ($request_data) {
                $request_data = DataModel::filterBlank($request_data);
                $this->checkCreateCdTypeRequest($request_data);
            }

            $res = DataModel::$success_return;
            unset($res['info']);
            $res['data'] = $this->CodeService->createCdType($request_data);

        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->jsonOut($res);
    }

    // code type 状态更改，开启关闭
    public function changeCdTypeStatus()
    {
        try {
            $request_data = I('post.');

            if ($request_data) {
                $this->checkUpdateCdTypeStatusRequest($request_data);
            }

            $res = DataModel::$success_return;
            unset($res['info']);
            $res['data'] = $this->CodeService->changeCdTypeStatus($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->jsonOut($res);
    }

    private function checkCreateCdTypeRequest($data)
    {
        $rules = [
            "cd_type_name" => "required|string|max:80",
        ];
        $custom_attributes = [
            "cd_type_name" => "CODE 类型名称",
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    private function checkUpdateCdTypeStatusRequest($data)
    {
        $rules = [
            "cd_type" => "required|string|size:6",
            "status" => "required|numeric"
        ];
        $custom_attributes = [
            "cd_type" => "CODE TYPE ID",
            "status" => "更改状态"
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    private function checkDeleteCdRequest($data)
    {
        $rules = [
            "CD" => "required|string|size:10",
        ];
        $custom_attributes = [
            "CD" => "CODE ID",
        ];
        $this->validate($rules, $data, $custom_attributes);
    }

    private function checkSaveCdRequest($data)
    {
        foreach ($data['data'] as $key => $value) {
            $rules = [
                'data.'.$key.'.CD_NM' => "required|string|max:80",
                'data.'.$key.'.CD_VAL' => "required|string|max:255",
                'data.'.$key.'.USE_YN' => "required|string|size:1",
                'data.'.$key.'.SORT_NO' => "numeric|max:9999",
            ];
            $custom_attributes = [
                "data.".$key.".CD_NM" => "CODE类型名称",
                "data.".$key.".CD_VAL" => "CODE名称",
                "data.".$key.".USE_YN" => "CODE开关状态",  
                "data.".$key.".SORT_NO" => "CODE排序",  
            ];

        }
        $rules['cd_type'] =  "required|string|size:6";
        $custom_attributes['cd_type'] = "CODE类型";    

        $this->validate($rules, $data, $custom_attributes);
    }


    // 新建/修改某个code type 的code信息
    public function saveDictionaryByCdType($sendData = [])
    {
        try {
            $request_data = $_POST; //I方法拿不到数组什么鬼？？
            if ($sendData) {
                $request_data = $sendData;
            }
            if ($request_data) {
                $request_data = DataModel::filterBlank($request_data);
                $this->checkSaveCdRequest($request_data);
            }
            $res = DataModel::$success_return;
            unset($res['info']);
            $res['data'] = $this->CodeService->saveDictionaryByCdType($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        if ($sendData) {
            return $res;
        }
        $this->jsonOut($res); 
    }

    // 删除某个code
    public function deleteDictionary() //暂时不需要
    {
        try {
            $request_data = I('post.');

            if ($request_data) {
                $this->checkDeleteCdRequest($request_data);
            }

            $res = DataModel::$success_return;
            unset($res['info']);
            $res['data'] = $this->CodeService->deleteCd($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->jsonOut($res);     
    }
    
    public function createDictionary()
    {
        //TODO 迁移字典处理到这里
    }
    
    public function updateDictionary()
    {
        //TODO 迁移字典处理到这里
    }

    public function InitializationCodeTypeData()
    {
        try {
         $res = $this->CodeService->getCodeTypeByGroup();   
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->jsonOut($res);
    }

    // 查看日志
    public function getCodeLog()
    {
        $cd = I('cd');
        try {
            $list = M()->table('gs_dp.dp_tb_ms_cmn_cd_log')->where(['table_id' => $cd])
            ->order('update_time desc')
            ->select();
            foreach ($list as $key => &$value) {
                $value['content'] = json_decode($value['content'], true);
            }
        } catch (Exception $exception) {
           $res = $this->catchException($exception); 
        }
        $this->jsonOut($list);
    }

    /**
     * 添加SKU页面，读取基本的SKU属性信息，所有的基本属性值。
     * 包括：币种，产地，Option属性，Option属性值
     * @see git s
     *
     */
    public function commonGroup()
    {
        $Dictionary = new DictionaryModel();
        $areaModel = new AreaModel();
        $dict = $Dictionary->getDictByType([
            DictionaryModel::CURRENCY_PREFIX,
            DictionaryModel::PLATFORM_PREFIX,
            DictionaryModel::REFUND_RATE,
            DictionaryModel::CROSS_BOARD_RATE,
            DictionaryModel::EXPRESS_CAT,
            DictionaryModel::EXPRESS_TYPE,
            DictionaryModel::GUDS_SALE_STATUS_PREFIX,
            DictionaryModel::WAREHOUSE_PREFIX,
            DictionaryModel::SKU_FEATURE,
            DictionaryModel::GUDS_AUTH_TYPE_PREFIX,
            DictionaryModel::GUDS_VALUATION_UNIT_PREFIX
        ]);

        $data = array(
            'currency' => $dict[DictionaryModel::CURRENCY_PREFIX],
            'country' =>   $areaModel->getCountiesBySort(),
            'platform' => $dict[DictionaryModel::PLATFORM_PREFIX],
            'refundRate' => $dict[DictionaryModel::REFUND_RATE],
            'expressCat' => $dict[DictionaryModel::EXPRESS_CAT],
            'expressType' => $dict[DictionaryModel::EXPRESS_TYPE],
            'crossBoardRate' => $dict[DictionaryModel::CROSS_BOARD_RATE],
            'saleState' => $dict[DictionaryModel::GUDS_SALE_STATUS_PREFIX],
            'warehouse' => $dict[DictionaryModel::WAREHOUSE_PREFIX],
            'skuFeature' => $dict[DictionaryModel::SKU_FEATURE],
            'chargeUnit' => $dict[DictionaryModel::GUDS_VALUATION_UNIT_PREFIX],
            'authType' => $dict[DictionaryModel::GUDS_AUTH_TYPE_PREFIX],          
        );

        $result = array('code' => 200, 'msg' => 'success', 'data' => $data);
        $this->jsonOut($result);
    }

    // 根据某个字段比如comment1，获取对应的CD列表（默认只获取开启状态，是否需要默认值无）
    public function getListByField()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $res = DataModel::$success_return;
            $res['code'] = 200;
            $res['data'] = $this->CodeService->getListByField($request_data);
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }
}