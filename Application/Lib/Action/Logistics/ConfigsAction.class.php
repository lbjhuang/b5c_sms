<?php
@import("@.Model.Orm.TbMsLogisticsAccountInfo");
@import("@.Model.Orm.TbMsLogisticsCompany");

/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/10/13
 * Time: 09:40
 */
class ConfigsAction extends BaseAction
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 基础信息配置页面
     */
    public function logistics_basis()
    {
        $this->display();
    }

    /**
     * 物流派单规则配置页面
     */
    public function logistics_dispatch()
    {
        $this->display();
    }

    /**
     * 物流绑定关系配置页面
     */
    public function logistics_relation()
    {
        $this->display();
    }

    /**
     * 搜索物流规则
     */
    public function searchRules()
    {
        $startTime = trim(I('startTime', ''));//date('Y-m-d',time())
        $endTime = trim(I('endTime', ''));//date('Y-m-d', time() + 86400)
        $ruleName = trim(I('ruleName', ''));
        $saleChannel = trim(I('saleChannel'));

        $page = trim(I('page', 1));
        $rows = trim(I('rows', '20'));
        $start = ($page - 1) * $rows;

        $condition = [
            'startTime' => $startTime,
            'endTime' => $endTime,
            'ruleName' => $ruleName,
            'saleChannel' => $saleChannel,
            'start' => $start,
            'rows' => $rows
        ];
        $RuleModel = new LogisticRulesModel();
        $data = $RuleModel->getRules($condition);
        $total = $RuleModel->getRulesCount($condition);

        //读取目的国家，城市，销售渠道，仓库，物流公司，物流模式
        $dictionary = new DictionaryModel();
        $dictList = $dictionary->getDictByType([
            DictionaryModel::LOGISTICS_COMPANY,
            DictionaryModel::GUDS_SALE_CHANNEL_PREFIX,
            DictionaryModel::WAREHOUSE_PREFIX,
            DictionaryModel::ORIGIN_PREFIX
        ]);

        //转义字段名称，并添加字典码对应的 名称
        foreach ($data as $key => $datum) {
            $item = $RuleModel->parseFieldsMap($datum);
            $item['destnCountryName'] = $dictList[DictionaryModel::ORIGIN_PREFIX][$item['destnCountry']]['CD_VAL'];
            $item['warehouseName'] = $dictList[DictionaryModel::WAREHOUSE_PREFIX][$item['warehouse']]['CD_VAL'];
            $item['logisticsCompany'] = $dictList[DictionaryModel::LOGISTICS_COMPANY][$item['logisticsCode']]['CD_VAL'];

            $saleChannel = explode(',', $item['saleChannel']);
            foreach ($saleChannel as $channel) {
                $item['channelName'] .= $dictList[DictionaryModel::GUDS_SALE_CHANNEL_PREFIX][$channel]['CD_VAL'] . ',';
            }
            $item['channelName'] = rtrim($item['channelName'], ',');
            $data[$key] = $item;
        }

        $lastData = ['list' => $data, 'total' => $total, 'page' => $page, 'rows' => $rows];
        $result = ['code' => 200, 'msg' => 'SUCCESS', 'data' => $lastData];
        $this->jsonOut($result);
    }

    /**
     * 创建一条物流规则
     */
    public function createRules()
    {
        $ruleName = trim(I('ruleName', ''));
        $saleChannel = trim(I('platforms', ''));
        $warehouse = trim(I('warehouse', ''));
        $logisticsCode = trim(I('logisticsCode', ''));
        $destnCountry = trim(I('destnCountry', ''));
        $destnCity = trim(I('destnCity', ''));
        $logisticsMode = trim(I('logisticsMode', ''));
        $isEnable = trim(I('isEnable', 1));
        $remark = trim(I('remark', ''));

        if (empty($ruleName) || empty($saleChannel) || empty($warehouse) || empty($destnCountry)) {
            $this->jsonOut(array('code' => 40002, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        if (empty($logisticsCode) || empty($logisticsMode)) {
            $this->jsonOut(array('code' => 40003, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        $RuleModel = new LogisticRulesModel();
        $res = $RuleModel->addRule([
            'ruleName' => $ruleName,
            'destnCountry' => $destnCountry,
            'destnCity' => $destnCity,
            'saleChannel' => rtrim($saleChannel, ','),
            'warehouse' => $warehouse,
            'logisticsCode' => $logisticsCode,
            'logisticsMode' => $logisticsMode,
            'isEnable' => $isEnable,
            'remark' => $remark
        ]);

        if ($res) {
            $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => null));
        } else {
            $this->jsonOut(array('code' => 500, 'msg' => L('SYSTEM_ERROR'), 'data' => null));
        }
    }

    /**
     * 更新物流规则
     */
    public function updateRule()
    {
        $id = trim(I('id'));
        $ruleName = trim(I('ruleName', ''));
        $saleChannel = trim(I('platforms', ''));
        $warehouse = trim(I('warehouse', ''));
        $logisticsCode = trim(I('logisticsCode', ''));
        $destnCountry = trim(I('destnCountry', ''));
        $destnCity = trim(I('destnCity', ''));
        $logisticsMode = trim(I('logisticsMode', ''));
        $isEnable = trim(I('isEnable', 1));
        $remark = trim(I('remark', ''));


        if (empty($id) || !is_numeric($id)) {
            $this->jsonOut(array('code' => 40004, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        $RuleModel = new LogisticRulesModel();
        $updateData = [
            'ruleName' => $ruleName,
            'destnCountry' => $destnCountry,
            'destnCity' => $destnCity,
            'saleChannel' => $saleChannel,
            'warehouse' => $warehouse,
            'logisticsCode' => $logisticsCode,
            'logisticsMode' => $logisticsMode,
            'isEnable' => $isEnable,
            'remark' => $remark
        ];
        $condition = ['id' => $id];
        $res = $RuleModel->updateRule($updateData, $condition);

        $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => $res));
    }

    /**
     * 删除 物流规则数据，这里是逻辑删除。
     *
     * 限制删除数量最多是：30
     */
    public function deleteRules()
    {
        $id = trim(I('id'));
        #$ruleName = I('ruleName');
        if (empty($id)) {
            $this->jsonOut(array('code' => 40005, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        //批量删除,最大允许删除个数 30个，超过了报错。
        if (false !== strpos($id, ',')) {
            $idArr = explode(',', $id);
            if (count($idArr) >= 30) {
                $this->jsonOut(array('code' => 40006, 'msg' => L('INVALID_PARAMS'), 'data' => null));
            }
        }

        $condition['id'] = $id;
        !empty($ruleName) && $condition['ruleName'] = $ruleName;

        $RuleModel = new LogisticRulesModel();
        $res = $RuleModel->deleteRule($condition);

        if ($res) {
            $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => $res));
        } else {
            $this->jsonOut(array('code' => 40500, 'msg' => L('NOT_EXIST'), 'data' => $res));
        }
    }

    /**
     * 创建新的物流关系
     */
    public function createRelations()
    {
        $data['ownCode'] = trim(I('ownCode'));//物流公司在码表中的CODE码
        $data['thirdCode'] = trim(I('thirdCode'));//第三方销售平台发货需要提供的物流公司名称
        $data['platCode'] = trim(I('platCode'));//销售平台，销售渠道
        $data['logisticName'] = trim(I('logisticName'));//物流公司中文名称
        $data['partnerId'] = trim(I('partnerId'));//物流公司电子面单账号或月结账号
        $data['partnerKey'] = trim(I('partnerKey'));//物流公司电子面单账号或月结账号密码

        if (empty($data['ownCode']) || false === strpos($data['ownCode'], DictionaryModel::LOGISTICS_COMPANY)) {
            $this->jsonOut(array('code' => 40007, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        if (empty($data['platCode']) || false === strpos($data['platCode'], DictionaryModel::PLATFORM_PREFIX)) {
            $this->jsonOut(array('code' => 40007, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        if (empty($data['thirdCode']) || empty($data['logisticName'])) {
            $this->jsonOut(array('code' => 40008, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        $relationModel = new LogisticsRelationModel();

        #验证是否已经有了，唯一关系是：物流公司CODE码和第三方平台CODE
        $exist = $relationModel->getRelations(['ownCode' => $data['ownCode'], 'platCode' => $data['platCode']]);
        if (!empty($exist)) {
            $this->jsonOut(array('code' => 40008, 'msg' => L('HAS_EXIST'), 'data' => null));
        }

        #添加新关系
        $res = $relationModel->addRelation($data);
        if ($res) {
            $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => null));
        } else {
            $this->jsonOut(array('code' => 500, 'msg' => L('SYSTEM_ERROR'), 'data' => null));
        }
    }

    /**
     * 更新物流关系
     */
    public function updateRelation()
    {
        $id = trim(I('id'));
        $data['ownCode'] = trim(I('ownCode'));//物流公司在码表中的CODE码
        $data['thirdCode'] = trim(I('thirdCode'));//第三方销售平台发货需要提供的物流公司名称
        $data['platCode'] = trim(I('platCode'));//销售平台，销售渠道
        $data['logisticName'] = trim(I('logisticName'));//物流公司中文名称
        $data['partnerId'] = trim(I('partnerId'));//物流公司电子面单账号或月结账号
        $data['partnerKey'] = trim(I('partnerKey'));//物流公司电子面单账号或月结账号密码

        if (empty($id)) {
            $this->jsonOut(array('code' => 40009, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        //参数不能等都是空的
        if (empty($data['ownCode']) && empty($data['thirdCode']) && empty($data['platCode'])
            && empty($data['logisticName']) && empty($data['partnerId']) && empty($data['partnerKey'])) {
            $this->jsonOut(array('code' => 40010, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        $condition = ['id' => $id];//更新条件
        $relationModel = new LogisticsRelationModel();
        $res = $relationModel->updateRelation($data, $condition);
        if (false === $res) {//如果是false基本上就是参数不合法导致，参数没传
            $this->jsonOut(array('code' => 40011, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }
        $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => $res));
    }

    /**
     * 根据条件搜索物流关联数据
     */
    public function searchRelations()
    {
        $startTime = trim(I('startTime'));
        $endTime = trim(I('endTime'));
        $platCode = trim(I('platCode'));//快递公司 CODE
        $ownCode = trim(I('ownCode'));//自己的物流表示CODE码
        $thirdCode = trim(I('thirdCode'));
        $logisticName = trim(I('logisticName'));

        $page = trim(I('page', 1));
        $rows = trim(I('rows', '20'));
        $start = ($page - 1) * $rows;

        $relationModel = new LogisticsRelationModel();
        $condition = [
            'startTime' => $startTime,
            'endTime' => $endTime,
            'ownCode' => $ownCode,
            'platCode' => $platCode,
            'thirdCode' => $thirdCode,
            'logisticName' => $logisticName,
            'start' => $start,
            'rows' => $rows
        ];
        $data = $relationModel->getRelations($condition);
        $total = $relationModel->getRelationCount($condition);


        //结果中添加物流公司名称，和平台名称
        //读取目的国家，城市，销售渠道，仓库，物流公司，物流模式
        $dictionary = new DictionaryModel();
        $dictList = $dictionary->getDictByType([
            DictionaryModel::LOGISTICS_COMPANY,
            DictionaryModel::PLATFORM_PREFIX,
        ]);
        foreach ($data as $key => &$item) {
            $item['logistics_company'] = $dictList[DictionaryModel::LOGISTICS_COMPANY][$item['b5c_logistics_cd']]['CD_VAL'];
            $item['platform_name'] = $dictList[DictionaryModel::PLATFORM_PREFIX][$item['plat_cd']]['CD_VAL'];
        }

        $lastData = ['list' => $data, 'total' => $total, 'page' => $page, 'rows' => $rows];

        $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => $lastData));
    }

    /**
     * 删除 物流关系
     * 允许两种途径：
     *  1、主键ID直接删除。
     *  2、根据字典表CODE码和销售渠道平台CODE码来删除。
     */
    public function deleteRelation()
    {
        $id = trim(I('id'));
        $platCode = trim(I('platCode', ''));//快递公司 CODE
        $ownCode = trim(I('ownCode', ''));//自己的物流表示CODE

        if (empty($id)) {
            $this->jsonOut(array('code' => 40011, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        $condition = ['id' => $id, 'ownCode' => $ownCode, 'platCode' => $platCode];
        $relationModel = new LogisticsRelationModel();
        $res = $relationModel->deleteRelation($condition);

        if ($res) {
            $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => null));
        } else {
            $this->jsonOut(array('code' => 500, 'msg' => L('SYSTEM_ERROR'), 'data' => null));
        }
    }

    /**
     * 搜索物理模式列表
     */
    public function searchMode()
    {
        $rows = $_GET['pageSize'];
        $condition['moreSearch'] = trim(I('moreSearch'));
        $condition['logisticsCompanyCode'] = trim(I('logisticsCompanyCode'));
        $condition['logisticsCode'] = trim(I('logisticsCode')); //物流公司CODE码
        $condition['logisticsMode'] = trim(I('logisticsMode'));//物流模式，物流方式
        $condition['serviceCode'] = trim(I('serviceCode'));//物流模式服务代码
        $condition['startTime'] = trim(I('startTime'));
        $condition['endTime'] = trim(I('endTime'));
        $condition['surface'] = trim(I('surface'));


        $page = trim(I('page', 1));
        if (!$rows) {
            $rows = trim(I('rows', 20));
        }
        $start = ($page - 1) * $rows;
        $condition['start'] = $start;
        $condition['rows'] = $rows;

        $modeModel = new LogisticsModeModel();
        $data = $modeModel->searchMode($condition);
        $total = $modeModel->getTotalModel($condition);

        //获取实际物流公司名称
        $realCompany = new RealLgtCompanyService();
        $realComInfo = $realCompany->getComNameKeyValue();

        //添加物流公司名称
        $dictionary = new DictionaryModel();
        $dictList = $dictionary->getDictByType([DictionaryModel::LOGISTICS_COMPANY]);
        $all_logistics_account_arr = TbMsLogisticsAccountInfo::all()->toArray();
        $all_logistics_account_arr = array_column($all_logistics_account_arr, 'account_name', 'logistics_account_info_id');
        $Model = new Model();
        $tb_lgt_postage_models = $Model->table('tb_lgt_postage_model')
            ->select();
        foreach ($tb_lgt_postage_models as $val) {
            $temp_val[$val['LOGISTICS_MODEL_ID']][] = $val['MODEL_NM'];
        }
        $tb_lgt_postage_models_key_val = $temp_val;
        foreach ($data as $key => &$item) {
            $item = $modeModel->parseFieldsMap($item);
            $item['logistics_company'] = $dictList[DictionaryModel::LOGISTICS_COMPANY][$item['logistics_code']]['CD_VAL'];
            $item['account_name'] = $all_logistics_account_arr[$item['logistics_account_info_id']];
            /*if ($item['POSTAGE_ID']) {
                if (strstr($item['POSTAGE_ID'], ',') !== false) {
                    $temp_arr = explode(',', $item['POSTAGE_ID']);
                } else {
                    $temp_arr = (array)$item['POSTAGE_ID'];
                }
                $temp_postage_id = '';
                foreach ($temp_arr as $val) {
                    if ($tb_lgt_postage_models_key_val[$val]) {
                        $temp_postage_id .= $tb_lgt_postage_models_key_val[$val] . ',';
                    }
                }
                $temp_postage_id = trim($temp_postage_id, ',');
                $item['POSTAGE_ID'] = $temp_postage_id;
            }*/
            if ($tb_lgt_postage_models_key_val[$item['id']]) {
                $temp_postage_id = '';
                foreach ($tb_lgt_postage_models_key_val[$item['id']] as $val) {
                    $temp_postage_id .= $val . ',';
                }
                $item['POSTAGE_ID'] = trim($temp_postage_id, ',');
            } else {
                $item['POSTAGE_ID'] = null;
            }

            $item['real_logistics_company_name'] = $realComInfo[$item['real_logistics_company_id']] ? $realComInfo[$item['real_logistics_company_id']] : '';
        }

        $result = ['total' => $total, 'page' => $page, 'rows' => $rows, 'list' => $data];
        $this->jsonOut(['code' => 200, 'msg' => 'success', 'data' => $result]);
    }

    /**
     * 物流模式数据导出
     */
    public function logisticsModeExport()
    {
        ini_set('max_execution_time', 1800);
        ini_set('memory_limit', '1024M');
        $condition['moreSearch'] = trim(I('moreSearch'));
        $condition['logisticsCompanyCode'] = trim(I('logisticsCompanyCode'));
        $condition['logisticsCode'] = trim(I('logisticsCode')); //物流公司CODE码
        $condition['logisticsMode'] = trim(I('logisticsMode'));//物流模式，物流方式
        $condition['serviceCode'] = trim(I('serviceCode'));//物流模式服务代码
        $condition['startTime'] = trim(I('startTime'));
        $condition['endTime'] = trim(I('endTime'));
        $condition['surface'] = trim(I('surface'));
        /*if (empty($condition['logisticsCompanyCode']) && empty($condition['logisticsCode']) && empty($condition['startTime']) && empty($condition['endTime'])) {
            $this->jsonOut(['code' => 300, 'msg' => '请先选择查询参数再导出', 'data' => []]);
        }*/
        $modeModel = new LogisticsModeModel();
        $where = $modeModel->searchWhere($condition, true);
        $logMode = new LgtPostageModel();
        $data = $logMode->getModel_DetailDataList($where);
        /*if (count($data) > 10000) {
            $this->jsonOut(['code' => 300, 'msg' => '导出数量超过10000条', 'data' => []]);
        }*/
        //$this->jsonOut(['code' => 200, 'msg' => 'success', 'data' => $data]);exit;
        $expTitle = "运费模板导出";
        /*$expCellName = array(
            array('logCompany', '物流公司'),
            array('LOGISTICS_MODE', '物流方式'),
            array('MODEL_NM', '运费模板'),
            array('STATE_CODE', '状态'),
            array('OUT_AREAS_DATA', '出发仓库'),
            array('DAY1', '时效1'),
            array('DAY2', '时效2'),
            array('DENOMINATED_TYPE', '计价方式'),
            array('COEFFICIENT', '计泡系数'),
            array('MAX_WEIGHT', '最大重量'),
            array('POSTTAGE_DISCOUNT', '运费折扣（%）'),
            array('POSTTAGE_DISCOUNT_DATE_START', '运费折扣有效期1'),
            array('POSTTAGE_DISCOUNT_DATE_END', '运费折扣有效期2'),
            array('PROCESS_DISCOUNT', '处理费折扣（%）'),
            array('PROCESS_DISCOUNT_DATE_START', '处理费有效期1'),
            array('PROCESS_DISCOUNT_DATE_END', '处理费有效期2'),
            array('WEIGHT1', '区间开始(kg)'),
            array('WEIGHT2', '区间结束(kg)'),
            array('COST', '固定费用（元）'),
            array('PROCESS_WEIGHT', '每X千克'),
            array('PROCESS_COST', '每X千克费用（元）'),
            array('BAN_ITEM_CAT', '不支持类型（CM）'),
            array('LENGTH1_START', '最长边开始'),
            array('LENGTH1_END', '最长边结束'),
            array('LENGTH2_START', '第二长边开始'),
            array('LENGTH2_END', '第二长边结束'),
            array('LENGTH3_MAX', '长宽高之和(≦)'),
            array('VOLUME_MAX', '体积(≦)'),
            array('country', '支持国家'),
            array('area', '支持区域'),
        );
        //join exp excel
        $excel = new \ExcelModel();
        $excel->export($expTitle, $expCellName, $data);*/
        $this->export_csv($data, $expTitle);
    }

    public function export_csv($data, $expTitle) {
        $map = [
            ['field_name' => 'logCompany', 'name' => '物流公司'],
            ['field_name' => 'LOGISTICS_MODE', 'name' => '物流方式'],
            ['field_name' => 'MODEL_NM', 'name' => '运费模板'],
            ['field_name' => 'STATE_CODE', 'name' => '状态'],
            ['field_name' => 'OUT_AREAS_DATA', 'name' => '出发仓库'],
            ['field_name' => 'DAY1', 'name' => '时效1'],
            ['field_name' => 'DAY2', 'name' => '时效2'],
            ['field_name' => 'DENOMINATED_TYPE', 'name' => '计价方式'],
            ['field_name' => 'COEFFICIENT', 'name' => '计泡系数'],
            ['field_name' => 'MAX_WEIGHT', 'name' => '最大重量'],
            ['field_name' => 'POSTTAGE_DISCOUNT', 'name' => '运费折扣（%）'],
            ['field_name' => 'POSTTAGE_DISCOUNT_DATE_START', 'name' => '运费折扣有效期1'],
            ['field_name' => 'POSTTAGE_DISCOUNT_DATE_END', 'name' => '运费折扣有效期2'],
            ['field_name' => 'PROCESS_DISCOUNT', 'name' => '处理费折扣（%）'],
            ['field_name' => 'PROCESS_DISCOUNT_DATE_START', 'name' => '处理费有效期1'],
            ['field_name' => 'PROCESS_DISCOUNT_DATE_END', 'name' => '处理费有效期2'],
            ['field_name' => 'WEIGHT1', 'name' => '区间开始(kg)'],
            ['field_name' => 'WEIGHT2', 'name' => '区间结束(kg)'],
            ['field_name' => 'COST', 'name' => '固定费用（元）'],
            ['field_name' => 'PROCESS_WEIGHT', 'name' => '每X千克'],
            ['field_name' => 'PROCESS_COST', 'name' => '每X千克费用（元）'],
            ['field_name' => 'BAN_ITEM_CAT', 'name' => '不支持类型（CM）'],
            ['field_name' => 'LENGTH1_START', 'name' => '最长边开始'],
            ['field_name' => 'LENGTH1_END', 'name' => '最长边结束'],
            ['field_name' => 'LENGTH2_START', 'name' => '第二长边开始'],
            ['field_name' => 'LENGTH2_END', 'name' => '第二长边结束'],
            ['field_name' => 'LENGTH3_MAX', 'name' => '长宽高之和(≦)'],
            ['field_name' => 'VOLUME_MAX', 'name' => '体积(≦)'],
            ['field_name' => 'country', 'name' => '支持国家'],
            ['field_name' => 'area', 'name' => '支持区域'],
        ];
        $this->exportCsv($data, $map, $expTitle, true);
    }

    /**
     * 读取全部的列表，现在没有条件，以后按照条件来处理。
     */
    public function getLogisticsModeList()
    {
        $logisticsCode = trim(I('logisticsCode'));
        if (empty($logisticsCode)) {
            $this->jsonOut(array('code' => 40012, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        $modeModel = new LogisticsModeModel();
        $modeList = $modeModel->searchMode(['logisticsCode' => $logisticsCode]);//条件为空时读取所有内容

        //添加物流公司名称
        $dictionary = new DictionaryModel();
        $dictList = $dictionary->getDictByType([DictionaryModel::LOGISTICS_COMPANY]);
        foreach ($modeList as $key => &$item) {
            $item = $modeModel->parseFieldsMap($item);
            $item['logistics_company'] = $dictList[DictionaryModel::LOGISTICS_COMPANY][$item['logistics_code']]['CD_VAL'];
        }
        $result = ['code' => 200, 'msg' => 'success', 'data' => $modeList, 'tips' => L('LOGISTICS_RULE_MODE')];
        $this->jsonOut($result);
    }

    /**
     * 创建物流模式
     */
    public function createLogisticsMode()
    {
        $logisticsCode = trim(I('logisticsCode')); //物理公司CODE码
        $logisticsMode = trim(I('logisticsMode'));//物理模式，物理方式
        $serviceCode = trim(I('serviceCode'));//物理模式服务代码
        $isEnable = trim(I('is_enable'));  //物流方式启用情况
        $real_logistics_company_id = trim(I('real_logistics_company_id')); // 实际物流公司id
        $remark = trim(I('remark'));
        $surfaceWay_chose = trim(I('surfaceWay_chose'));
        $need_gift = trim(I('need_gift'));
        $logistics_account_info_id = trim(I('logistics_account_info_id'));
        if (empty($logisticsCode) || empty($logisticsMode) || empty($serviceCode)) {
            $this->jsonOut(array('code' => 40013, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        $newMode['logisticsCode'] = $logisticsCode;
        $newMode['logisticsMode'] = $logisticsMode;
        $newMode['serviceCode'] = $serviceCode;
        $newMode['is_enable'] = $isEnable;
        $newMode['remark'] = $remark;
        $newMode['surfaceWay_chose'] = $surfaceWay_chose;
        $newMode['need_gift'] = $need_gift;
        $newMode['logistics_account_info_id'] = $logistics_account_info_id;
        $newMode['real_logistics_company_id'] = $real_logistics_company_id;

        $modeModel = new LogisticsModeModel();
        $res = $modeModel->createMode($newMode);
        if ($res) {
            $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => ['id' => $res]));
        } else {
            $this->jsonOut(array('code' => 500, 'msg' => L('物流方式重复'), 'data' => $modeModel->getDbError()));
        }
    }

    /**
     * 更新物流方式
     */
    public function updateLogisticsMode()
    {
        $modeId = trim(I('id'));
        $logisticsCode = trim(I('logisticsCode')); //物理公司CODE码
        $logisticsMode = trim(I('logisticsMode'));//物理模式，物理方式
        $serviceCode = trim(I('serviceCode'));//物理模式服务代码
        $isEnable = trim(I('is_enable'));  //物流方式启用情况
        $surfaceWay_chose = trim(I('surfaceWay_chose'));
        $need_gift = trim(I('need_gift'));
        $logistics_account_info_id = trim(I('logistics_account_info_id'));
        $real_logistics_company_id = trim(I('real_logistics_company_id'));
        

        //var_dump($modeId);die;
        if (empty($modeId) || empty($logisticsCode) || empty($logisticsMode)) {
            $this->jsonOut(array('code' => 40014, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }
        if (empty($logistics_account_info_id)) {
            $this->jsonOut(array('code' => 40014, 'msg' => L('帐密信息不能为空'), 'data' => null));
        }

        $newMode['logisticsCode'] = $logisticsCode;
        $newMode['logisticsMode'] = $logisticsMode;
        $newMode['serviceCode'] = $serviceCode;
        $newMode['is_enable'] = $isEnable;
        $newMode['surfaceWay_chose'] = $surfaceWay_chose;
        $newMode['need_gift'] = $need_gift;
        $newMode['logistics_account_info_id'] = $logistics_account_info_id;
        $newMode['real_logistics_company_id'] = $real_logistics_company_id;
        $condition = ['id' => $modeId, 'logisticsCode' => $logisticsCode, 'logisticsMode' => $logisticsMode, 'serviceCode' => $serviceCode, 'is_enable' => $isEnable];
        $modeModel = new LogisticsModeModel();
        $res = $modeModel->updateMode($newMode, $condition);

        $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => $res));
    }

    /**
     * 删除物流模式
     */
    public function deleteLogisticsMode()
    {
        $modeId = trim(I('id'));
        if (empty($modeId)) {
            $this->jsonOut(array('code' => 40012, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        $modeModel = new LogisticsModeModel();
        $res = $modeModel->deleteMode(['id' => $modeId]);
        if ($res) {
            $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => $res));
        } else {
            $this->jsonOut(array('code' => 50001, 'msg' => L('NOT_EXIST'), 'data' => $res));
        }
    }

    /**
     * 导入物流绑定关系数据。
     */
    public function exportRelations()
    {
        header('Content-Type: text/html;charset=utf-8');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=LogisticsRelations.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $startTime = trim(I('startTime'));
        $endTime = trim(I('endTime'));
        $platCode = trim(I('platCode'));//快递公司 CODE
        $ownCode = trim(I('ownCode'));//自己的物流表示CODE码
        $thirdCode = trim(I('thirdCode'));
        $logisticName = trim(I('logisticName'));

        $relationModel = new LogisticsRelationModel();
        $condition = [
            'startTime' => $startTime,
            'endTime' => $endTime,
            'ownCode' => $ownCode,
            'platCode' => $platCode,
            'thirdCode' => $thirdCode,
            'logisticName' => $logisticName,
        ];
        $data = $relationModel->getRelations($condition);

        $title = array(
            '编号',
            '物流CODE码',
            '销售平台要求名称',
            '销售平台CODE码',
            '创建用户',
            '添加时间',
            '更新时间',
            '物流公司名称',
            '面单账号或月结账号',
            '面单账号或账号密码',
            '是否已删除'
        );
        $this->outputCvs($title, $data);

    }

    public function exportRules()
    {
        header('Content-Type: text/html;charset=utf-8');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=LogisticsRules.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $startTime = trim(I('startTime', ''));//date('Y-m-d',time())
        $endTime = trim(I('endTime', ''));//date('Y-m-d', time() + 86400)
        $ruleName = trim(I('ruleName', ''));
        $saleChannel = trim(I('saleChannel'));

        $condition = [
            'startTime' => $startTime,
            'endTime' => $endTime,
            'ruleName' => $ruleName,
            'saleChannel' => $saleChannel,
        ];
        $RuleModel = new LogisticRulesModel();
        $data = $RuleModel->getRules($condition);
        //读取目的国家，城市，销售渠道，仓库，物流公司，物流模式
        $dictionary = new DictionaryModel();
        $dictList = $dictionary->getDictByType([
            DictionaryModel::LOGISTICS_COMPANY,
            DictionaryModel::GUDS_SALE_CHANNEL_PREFIX,
            DictionaryModel::WAREHOUSE_PREFIX,
            DictionaryModel::ORIGIN_PREFIX
        ]);

        //转义字段名称，并添加字典码对应的 名称
        foreach ($data as $key => $datum) {
            $item = $RuleModel->parseFieldsMap($datum);
            $item['destnCountryName'] = $dictList[DictionaryModel::ORIGIN_PREFIX][$item['destnCountry']]['CD_VAL'];
            $item['warehouseName'] = $dictList[DictionaryModel::WAREHOUSE_PREFIX][$item['warehouse']]['CD_VAL'];
            $item['logisticsCompany'] = $dictList[DictionaryModel::LOGISTICS_COMPANY][$item['logisticsCode']]['CD_VAL'];

            $saleChannel = explode(',', $item['saleChannel']);
            foreach ($saleChannel as $channel) {
                $item['channelName'] .= $dictList[DictionaryModel::GUDS_SALE_CHANNEL_PREFIX][$channel]['CD_VAL'] . ',';
            }

            $data[$key] = $item;
        }

        $title = [
            '编号',
            '规则名称',
            '目的地国家',
            '目的地城市',
            '销售平台',
            '出货仓库',
            '物流公司',
            '物流方式编码',
            '是否启用',
            '是否删除',
            '创建人',
            '创建时间',
            '最后更新时间',
            '备注',
            '物流方式名称',
            '目的地国家名称',
            '出货仓库名称',
            '物流公司名称',
            '销售平台名称'
        ];

        $this->outputCvs($title, $data);

    }

    /**
     * 导入出物流模式数据
     */
    public function exportMode()
    {
        header('Content-Type: text/html;charset=utf-8');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=LogisticsRules.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        // $condition['logisticsCode'] = trim(I('logisticsCode')); //物理公司CODE码
        // $condition['logisticsMode'] = trim(I('logisticsMode'));//物理模式，物理方式
        // $condition['serviceCode'] = trim(I('serviceCode'));//物理模式服务代码
        // $condition['startTime'] = trim(I('startTime'));
        // $condition['endTime'] = trim(I('endTime'));

        $condition['moreSearch'] = trim(I('moreSearch'));
        $condition['logisticsCompanyCode'] = trim(I('logisticsCompanyCode'));
        $condition['logisticsCode'] = trim(I('logisticsCode')); //物流公司CODE码
        $condition['logisticsMode'] = trim(I('logisticsMode'));//物流模式，物流方式
        $condition['serviceCode'] = trim(I('serviceCode'));//物流模式服务代码
        $condition['startTime'] = trim(I('startTime'));
        $condition['endTime'] = trim(I('endTime'));
        $condition['surface'] = trim(I('surface'));

        $modeModel = new LogisticsModeModel();
        $data = $modeModel->searchMode($condition, 'export');


        //添加物流公司名称
        $dictionary = new DictionaryModel();
        $dictList = $dictionary->getDictByType([DictionaryModel::LOGISTICS_COMPANY]);

        foreach ($data as $key => &$item) {
            $item = $modeModel->parseFieldsMap($item);
            $item['logistics_company'] = $dictList[DictionaryModel::LOGISTICS_COMPANY][$item['logistics_code']]['CD_VAL'];
        }
        $list = [];
        foreach ($data as $k => $v) {
//            unset($data[$k]['logistics_code']);
//            unset($data[$k]['need_gift']);
//            unset($data[$k]['logistics_account_info_id']);
            $list[$k]['id'] = $v['id'];
            $list[$k]['logistics_mode'] = $v['logistics_mode'];
            $list[$k]['service_code'] = $v['service_code'];
            $list[$k]['creator'] = $v['creator'];
            $list[$k]['create_time'] = $v['create_time'];
            $list[$k]['update_time'] = $v['update_time'];
            $list[$k]['is_enable'] = $v['is_enable'];
            $list[$k]['logistics_company'] = $v['logistics_company'];
        }
        $data = $list;
        $title = [
            '编号',
            //'物流公司CODE',
            '物流方式',
            '服务代码',
            '创建人',
            '创建日期',
            '更新日期',
            '是否启用',
            //'是否删除',
            //'备注',
            '物流公司'
        ];

        $this->outputCvs($title, $data);
    }

    private function outputCvs($title, $data)
    {
        //打开PHP文件句柄,php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');
        //输出Excel列名信息
        foreach ($title as $key => $value) {
            //CSV的Excel支持GBK编码，一定要转换，否则乱码
            $headlist[$key] = iconv('utf-8', 'gbk', $value);
        }

        //将数据通过fputcsv写到文件句柄
        fputcsv($fp, $headlist);
        $num = 0;
        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 1000;

        //逐行取出数据，不浪费内存
        $count = count($data);
        for ($i = 0; $i < $count; $i++) {
            $num++;
            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }

            $row = $data[$i];
            foreach ($row as $key => $value) {
                //$value = str_replace([',', '，',"\r\n", "\r", "\n"], '-', $value);
                if (false !== strpos($value, ',')) {
                    $value = "\"{$value}\"";
                }

                if (is_numeric($value)) {
                    $value = "\t" . $value;
                }
                $row[$key] = iconv('utf-8', 'gbk', $value);
            }

            fputcsv($fp, $row);
        }
    }

    /**
     * 查询条件
     */
    private function getCondition()
    {
        $pageRows = trim($_REQUEST['pageRows']);
        $pageCurrent = trim($_REQUEST['pageCurrent']);
        $startTime = trim($_REQUEST['startTime']);
        $endTime = trim($_REQUEST['endTime']);
        $apiType = trim($_REQUEST['apiType']);//接口类型
        $providerSystem = trim($_REQUEST['providerSystem']);//来源系统
        $condition = [
            'startTime' => $startTime,
            'endTime' => $endTime,
            'apiType' => $apiType,
            'providerSystem' => $providerSystem,
            'pageRows' => $pageRows,
            'pageCurrent' => $pageCurrent,
        ];
        return $condition;
    }


    /**
     * 物流api接口日志列表
     */
    public function tracking_api_list()
    {

        $condition = $this->getCondition();
        $model = D("ElecsheetLog");

        if ($trackData = $model->getTrackData($condition)) {
            $data = [
                'code' => 200,
                'msg' => 'success',
                'data' => $trackData,
            ];
        } else {
            $data = [
                'code' => 500,
                'msg' => 'error no data',
                'data' => [],
            ];
        }
        $this->jsonOut($data);

    }

    /**
     * 导出物流api接口日志
     */
    public function exportApiLog()
    {
        $condition = $this->getCondition();

        $export = new ExportExcelModel();
        $export->attributes = [
            'A' => ['name' => '编号', 'field_name' => 'sort'],
            'B' => ['name' => 'B5C单号', 'field_name' => 'ord_id'],
            'C' => ['name' => '接口类型', 'field_name' => 'api_type'],
            'D' => ['name' => '来源系统', 'field_name' => 'provider'],
            'E' => ['name' => '目的系统', 'field_name' => 'client'],
            'F' => ['name' => '运单号', 'field_name' => 'tracking_no'],
            'G' => ['name' => 'b5c物流公司编码', 'field_name' => 'b5c_logistics_cd'],
            'H' => ['name' => '物流服务方式代码', 'field_name' => 'servie_code'],
            'I' => ['name' => 'B5C同步时间', 'field_name' => 'update_time'],
            //'J' => ['name' => '状态', 'field_name' => 'call_health'],
        ];
        $model = new ElecsheetLogModel();
        //$export->title = L('会议记录' );
        $export->fileName = L('物流API接口日志');

        $data = $model->getTrackData($condition);
        $export->data = $data['trackResData'];

        if ($export->getError()) {
            $this->error($export->getError());
        }
        $export->export();

    }

    /**
     * 物流详情
     */

    public function tracking_detail()
    {

        $model = new ElecsheetLogModel();
        $detailData = $model->getTrackDetail();

        $detailData = $detailData ? $detailData : [];
        $data = [
            'code' => 200,
            'msg' => 'success',
            'data' => $detailData,
        ];
        $this->jsonOut($data, $msg, $res);
    }


    public function getInterface()
    {
        $model = new ElecsheetLogModel();
        $interfaceData = $model->getInterfacesData();
        if ($interfaceData) {
            $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => $interfaceData));
        } else {
            $this->jsonOut(array('code' => 50001, 'msg' => L('NOT_EXIST'), 'data' => $interfaceData));
        }
    }

    public function logisSelectData()
    {
        $moreSearch = $this->getParams();
        $delModeRepeat = [];
        $delCodeRepeat = [];
        $model = new LogisticsModeModel();
        $condition = [];
        $logModeData = $model->searchMode($condition, 'export');  //所有物流方式数据
        switch ($moreSearch['moreSearch']) {
            case 'mode':
                foreach ($logModeData as $k => $v) {
                    if (!in_array($v['LOGISTICS_MODE'], $delModeRepeat)) {
                        $logModeResData[$k]['code'] = $v['LOGISTICS_MODE'];
                        $logModeResData[$k]['val'] = $v['LOGISTICS_MODE'];
                        $delModeRepeat[] = $v['LOGISTICS_MODE'];
                    }
                }
                break;
            case 'serviceCode':
                foreach ($logModeData as $k => $v) {
                    if ($v['SERVICE_CODE'] != '' && !in_array($v['SERVICE_CODE'], $delCodeRepeat)) {
                        $logModeResData[$k]['code'] = $v['SERVICE_CODE'];
                        $logModeResData[$k]['val'] = $v['SERVICE_CODE'];
                        $delCodeRepeat[] = $v['SERVICE_CODE'];
                    }
                }
                break;
            default:
                break;
        }
        if ($logModeResData) {
            $this->jsonOut(array('code' => 200, 'msg' => 'SUCCESS', 'data' => $logModeResData));
        } else {
            $this->jsonOut(array('code' => 50001, 'msg' => L('NOT_EXIST'), 'data' => $logModeResData));
        }
    }

    public function showLogisticsAccountInfo()
    {
        $require_data = DataModel::getDataToArr()['logistios_company_cd'];
        if ($require_data) {
            $logistics_db = TbMsLogisticsCompany::where('logistios_company_cd', $require_data)
                ->first();
            $logistics_arr = [];
            if ($logistics_db) {
                $logistics_arr = $logistics_db->toArray();
            }

            $all_logistics_account_arr = TbMsLogisticsAccountInfo::where('logistics_company_id', $logistics_arr['id'])
                ->where('is_enable', 1)
                ->get()
                ->toArray();
            $res = array_column($all_logistics_account_arr, 'login_username', 'id');
        } else {
            $all_logistics_account_arr = TbMsLogisticsAccountInfo::all()->toArray();
            $res = array_column($all_logistics_account_arr, 'login_username', 'id');
        }
        $this->ajaxReturnCheck($res);
    }

    public function getAllLogisticsCompany($temp_require_data = null)
    {
        if (empty($temp_require_data)) {
            $require_data = DataModel::getData(true);
        } else {
            $require_data = $temp_require_data;
        }
        $require_data = ZUtils::filterBlank($require_data);
        $LogisticsCompany = new TbMsLogisticsCompanyModel();
        $LogisticsCompany->page_count = 500;
        if (!empty($require_data['data'])) {
            $LogisticsCompany->where = $this->logisticsCompanyWhereJoin($require_data['data']);
            $allList = $LogisticsCompany->getAllList();
            if (!$allList) {
                $allList = [];
            }
            $res['page']['count'] = count($allList);
            $LogisticsCompany->this_page = $require_data['page']['this_page'] - 1;
            if ($LogisticsCompany->this_page < 0) {
                $LogisticsCompany->this_page = 0;
            }
            $LogisticsCompany->this_page = $LogisticsCompany->this_page * $require_data['page']['page_count'];
            $LogisticsCompany->page_count = $require_data['page']['page_count'];
        }
        $res['page']['this_page'] = $require_data['page']['this_page'];
        $res['page']['page_count'] = $require_data['page']['page_count'];
        $res['data'] = $LogisticsCompany->getAllList();
        $res_info_arr = TbMsLogisticsAccountInfo::all();
        if ($res_info_arr) {
            $res_info_arr = $res_info_arr->toArray();
        }
        foreach ($res_info_arr as $temp_val) {
            $res_info_key_arr[$temp_val['logistics_company_id']][] = $temp_val['login_username'];
        }

        $res['data'] = $this->updateCdValRes($res['data'], ['N00239', 'N00070', 'N00240', 'N00068']);

        foreach ($res['data'] as &$res_val) {
            $res_val['logistics_account_account_name_arr'] = (array)$res_info_key_arr[$res_val['id']];
            $res_val = $this->cdTypeToArray($res_val);
        }
        Logs($res['data']);
        if (!empty($temp_require_data)) {
            return $res['data'];
        }
        $this->ajaxReturnCheck($res);
    }

    public function logisticsCompanyWhereJoin($require_data)
    {
        $temp_where = [];
        if ($require_data['company_value']) {
            /*$company_cd_arr = CodeModel::getCodeValKeyArr(['N00070', 'N00124']);
            $company_value_cd = $company_cd_arr[$require_data['company_value']];*/
            $Model = M();
            $where_cd['CD_VAL'] = ['like', "%{$require_data['company_value']}%"];
            switch ($require_data['company_type']) {
                case 'logistios_company_cd':
                    $where_cd['CD'] = ['like', 'N00070%'];
                    $company_like_cd_arr = $Model->table('tb_ms_cmn_cd')->where($where_cd)->select();

                    $temp_where['logistios_company_cd'] = ['in', array_column($company_like_cd_arr, 'CD')];
                    break;
                case 'forwarding_company_cd':
                    $where_cd['CD'] = ['like', 'N00239%'];
                    $company_like_cd_arr = $Model->table('tb_ms_cmn_cd')->where($where_cd)->select();

                    $temp_where['forwarding_company_cd'] = ['like', array_column($company_like_cd_arr, 'CD')];
                    break;
            }
        }
        if ($require_data['time_act'] || $require_data['time_end']) {
            switch ($require_data['time_type']) {
                case 'created_dt':
                    $temp_where = $this->joinBweetTime($require_data, $temp_where, 'tb_ms_logistics_company.created_at');
                    break;
                case 'updated_dt':
                    $temp_where = $this->joinBweetTime($require_data, $temp_where, 'tb_ms_logistics_company.updated_at');
                    break;
            }
        }
        if (!empty($require_data['butt_item_cd_arr']) && is_array($require_data['butt_item_cd_arr'])) {
            $str_where = " = 'null data') ";
            foreach ($require_data['butt_item_cd_arr'] as $temp_val) {
                $str_where .= "OR ( `butt_item_cd_arr` like '%{$temp_val}%')";
            }
            $str_where = trim($str_where, ')');
            $temp_where['butt_item_cd_arr'] = array('exp', $str_where);
        }
        return $temp_where;
    }

    public function updateLogisticsCompany()
    {
        try {
            $require_data = DataModel::getDataToArr('data');
            $this->checkUpdateLogisticsCompany($require_data);
            Logs($require_data, '$require_data', 'updateLogisticsCompany');
            $update_or_create_data = $this->joinAssignementCompany($require_data);
            $db_res = TbMsLogisticsCompany::updateOrCreate(
                ['id' => $require_data['id']],
                $update_or_create_data
            );
            if (!$db_res) {
                throw new Exception(L('更新物流公司信息失败'));
            }
            $res = DataModel::$success_return;
            $res['msg'] = L('成功');
        } catch (Exception $exception) {
            $res = DataModel::$error_return;
            $res['data'] = $this->error_message;
            $res['code'] = $exception->getCode();
            $res['msg'] = $exception->getMessage();
            if ($res['code'] == 23000) {
                $res['data'] = $res['msg'];
                $res['msg'] = '物流信息已存在无法新增';
            }
        }
        unset($res['info']);
        $this->ajaxReturn($res);
    }

    private function checkUpdateLogisticsCompany($data)
    {
        $rules = [
            'logistios_company_cd' => 'required|string|min:10|max:10',
            'butt_item_cd_arr' => 'required|array',
        ];
        $customAttributes = [
            'logistios_company_cd' => '物流公司',
            'forwarding_company_cd' => '货代公司',
            'self_warehouse_cd_arr' => '自有仓库',
            'butt_item_cd_arr' => '对接项',
        ];
        //'forwarding_company_cd' => 'N00124',
        //             'butt_item_cd' => 'N00201',
        $check_code_arr = [
            'logistios_company_cd' => 'N00070',
        ];
        $this->validate($rules, $data, $customAttributes);
        $this->checkCodeRight($data, $customAttributes, $check_code_arr);
    }

    /**
     * @param $res
     */
    private function ajaxReturnCheck($res)
    {
        if (empty($res)) {
            $this->ajaxError($res);
        }
        Logs($res, '$res');
        $this->ajaxSuccess($res);
    }

    /**
     * @param $require_data
     *
     * @return mixed
     */
    private function joinAssignementCompany($require_data)
    {
        $require_data['self_warehouse_cd_arr'] = implode(',', $require_data['self_warehouse_cd_arr']);
        $require_data['butt_item_cd_arr'] = implode(',', $require_data['butt_item_cd_arr']);
        $require_data['updated_user'] = session('m_loginname');
        return $require_data;
    }

    /**
     * @param $require_data
     * @param $LogisticsCompany
     */
    private function joinBweetTime($require_data, $temp_where, $type)
    {
        if ($require_data['time_act'] && $require_data['time_end']) {
            $temp_where[$type] = array('between', [$require_data['time_act'] . ' 00:00:00', $require_data['time_end'] . ' 23:59:59']);
        } elseif ($require_data['time_act']) {
            $temp_where[$type] = array('egt', $require_data['time_act']);
        } elseif ($require_data['time_end']) {
            $temp_where[$type] = array('elt', $require_data['time_end']);
        }
        return $temp_where;
    }

    public function getLogisticsAccountInfo()
    {
        try {
            $logistics_company_id = DataModel::getDataToArr()['logistics_company_id'];
            $this->checkRequireData($logistics_company_id, L('查询物流公司不能为空'));
            $res = DataModel::$success_return;
            $res['data'] = TbMsLogisticsAccountInfo::where('logistics_company_id', $logistics_company_id)
                ->get()
                ->toArray();
            $res['data'] = $this->updateDateComma($res['data']);
            $res['data'] = $this->updateCdValRes($res['data'], ['N00124']);
        } catch (Exception $exception) {
            $res = DataModel::$error_return;
            $res['data'] = $this->error_message;
            $res['code'] = $exception->getCode();
            $res['msg'] = $exception->getMessage();
        }
        unset($res['info']);
        $this->ajaxReturn($res);
    }

    private function updateDateComma($data)
    {
        foreach ($data as &$temp) {
            $temp['contract_validity_act_at'] = date('Y.m.d', strtotime($temp['contract_validity_act_at']));
            $temp['contract_validity_end_at'] = date('Y.m.d', strtotime($temp['contract_validity_end_at']));
        }
        return $data;
    }

    /**
     * @param $data
     * @param $lang_tips
     *
     * @throws Exception
     */
    private function checkRequireData($data, $lang_tips)
    {
        if (empty($data)) {
            throw new  Exception($lang_tips);
        }
    }

    /**
     * @param $res
     *
     * @param $cd_arr
     *
     * @return mixed
     */
    private function updateCdValRes($res, $cd_arr)
    {
        $cd_key_val_arr = CodeModel::getCodeKeyValArr($cd_arr);
        $cd_key_arr = array_keys($cd_key_val_arr);
        foreach ($res as $key => $value) {
            foreach ($value as $k => $v) {
                if (in_array($v, $cd_key_arr)) {
                    $res[$key][$k . '_val'] = $cd_key_val_arr[$v];
                }
                if ('butt_item_cd_arr' == $k && strstr($v, ',') !== false) {
                    $res[$key]['butt_item_cd_arr'] = $explode = explode(',', $v);
                    foreach ($explode as $e_v) {
                        if ($cd_key_val_arr[$e_v]) {
                            $res[$key]['butt_item_cd_arr_val'][] = $cd_key_val_arr[$e_v];
                        }
                    }
                    unset($explode);
                }
                if ('self_warehouse_cd_arr' == $k && strstr($v, ',') !== false) {
                    $res[$key]['self_warehouse_cd_arr'] = $explode = explode(',', $v);
                    foreach ($explode as $e_v) {
                        if ($cd_key_val_arr[$e_v]) {
                            $res[$key]['self_warehouse_cd_arr_val'][] = $cd_key_val_arr[$e_v];
                        }
                    }
                    unset($explode);
                }
                if (isset($res[$key]['self_warehouse_cd_arr']) && empty($res[$key]['self_warehouse_cd_arr'])) {
                    $res[$key]['self_warehouse_cd_arr'] = (array)null;
                }
                if (isset($res[$key]['self_warehouse_cd_arr_val'])) {
                    $res[$key]['self_warehouse_cd_arr_val'] = (array)$res[$key]['self_warehouse_cd_arr_val'];
                }
                if (isset($res[$key]['self_warehouse_cd_arr']) && empty($res[$key]['self_warehouse_cd_arr_val'])) {
                    $res[$key]['self_warehouse_cd_arr_val'] = (array)null;
                }
                if (isset($res[$key]['forwarding_company_cd']) && empty($res[$key]['forwarding_company_cd_val'])) {
                    $res[$key]['forwarding_company_cd_val'] = null;
                }
                unset($k, $v, $explode, $e_v);
            }
            //  *** 交换值 ***
            list($res[$key]['login_username'], $res[$key]['account_name']) = [$res[$key]['account_name'], $res[$key]['login_username']];
            list($res[$key]['login_password'], $res[$key]['account_password']) = [$res[$key]['account_password'], $res[$key]['login_password']];
        }
        return $res;
    }

    public function updateLogisticsAccountInfo()
    {
        try {
            $require_data = DataModel::getDataToArr('data');
            $require_data = $this->dataMenuUpdate($require_data);
            $this->checkUpdateLogisticsAccountInfo($require_data);
            Logs($require_data, '$require_data', 'updateLogisticsAccountInfo');
            $this->checkCompanyPolymerizationOnly($require_data);
            if (empty($require_data['account_password'])) {
                unset($require_data['account_password']);
            }
            $require_data['updated_user'] = session('m_loginname');
            //  *** 交换值 ***
            list($require_data['login_username'], $require_data['account_name']) = [$require_data['account_name'], $require_data['login_username']];
            list($require_data['login_password'], $require_data['account_password']) = [$require_data['account_password'], $require_data['login_password']];
            if (empty($require_data['account_name'])) {
                unset($require_data['account_name']);
            }
            if (empty($require_data['account_password'])) {
                unset($require_data['account_password']);
            }
            $db_res = TbMsLogisticsAccountInfo::updateOrCreate(
                ['id' => $require_data['id']],
                $require_data
            );
            if (!$db_res) {
                throw new Exception(L('更新帐密信息失败'));
            }
            if (empty($require_data['id']) && !empty($db_res['original']['id']) && !empty($require_data['logistics_company_id'])) {
                $where_logistics['logistics_company_id'] = $db_res['original']['logistics_company_id'];
                $count_db = TbMsLogisticsAccountInfo::where($where_logistics)
                    ->count();
                if ($count_db == 1) {
                    $logistios_company_cd = TbMsLogisticsCompany::find($require_data['logistics_company_id'], ['logistios_company_cd']);
                    if ($logistios_company_cd) {
                        $Model = new Model();
                        $save['logistics_account_info_id'] = $db_res['original']['id'];
                        $where_logistics_mode['LOGISTICS_CODE'] = $logistios_company_cd->toArray()['logistios_company_cd'];
                        $tb_ms_logistics_mode_res = $Model->table('tb_ms_logistics_mode')
                            ->where($where_logistics_mode)
                            ->save($save);
                        Logs($tb_ms_logistics_mode_res, '$tb_ms_logistics_mode_res', 'updateLogisticsAccountInfo');
                    }
                }
            }
            $res = DataModel::$success_return;
            $res['msg'] = L('成功');
        } catch (Exception $exception) {
            $res = DataModel::$error_return;
            $res['data'] = $this->error_message;
            $res['code'] = $exception->getCode();
            $res['msg'] = $exception->getMessage();
            if ($res['code'] == 23000) {
                $res['data'] = $res['msg'];
                $res['msg'] = '帐密信息已存在新增';
            }
        }
        unset($res['info']);
        $this->ajaxReturn($res);
    }

    private function checkUpdateLogisticsAccountInfo($data)
    {
        $rules = [
            'logistics_company_id' => 'required|numeric',
            'account_password' => 'filled|string|max:255',
            'our_sigin_company_cd' => 'required|string|min:10|max:10',
            'contract_validity_act_at' => 'required|date',
            'contract_validity_end_at' => 'required|date',
            'is_enable' => 'required|numeric|min:0|max:1',
        ];
        $customAttributes = [
            'logistics_company_id' => '对应物流公司',
            'account_name' => '帐号',
            'token' => 'token',
            'our_sigin_company_cd' => '我方签约公司',
            'contract_validity_act_at' => '签约有效期开始',
            'contract_validity_end_at' => '签约有效期结束',
            'is_enable' => '是否启用',
        ];
        $this->validate($rules, $data, $customAttributes);
        $this->checkHasLogisticsCompanyId($data['logistics_company_id']);
        $this->checkUnion($data);

    }

    private function checkHasLogisticsCompanyId($logistics_company_id)
    {
        if (!TbMsLogisticsCompany::where('id', $logistics_company_id)->count()) {
            throw new Exception(L('物流公司错误'), 40030);
        }
    }

    private function checkUnion($data)
    {
        if (empty($data['token']) && empty($data['login_username']) && empty($data['login_password'])) {
            throw new Exception(L('登录信息和token必填一个'), 40030);
        }
    }

    public function deleteLogisticsAccountInfo()
    {
        try {
            $info_id = DataModel::getDataToArr()['id'];
            $this->checkLogisticsAccountInfo($info_id);
            $res = DataModel::$success_return;
            $res['data'] = TbMsLogisticsAccountInfo::destroy($info_id);
            if (!$res['data']) {
                throw new Exception(L('删除帐密失败'));
            }
        } catch (Exception $exception) {
            $res = DataModel::$error_return;
            $res['data'] = $this->error_message;
            $res['code'] = $exception->getCode();
            $res['msg'] = $exception->getMessage();
        }
        unset($res['info']);
        $this->ajaxReturn($res);;
    }

    private function checkLogisticsAccountInfo($info_id)
    {
        $this->checkRequireData($info_id, L('删除帐密不能为空'));
        if (!TbMsLogisticsAccountInfo::where('id', $info_id)->count()) {
            throw new Exception(L('请求删除帐密不存在'), 40030);
        }
    }

    /**
     * @param $require_data
     *
     * @return mixed
     */
    private function checkCompanyPolymerizationOnly($require_data)
    {
        if (empty($require_data['id'])) {
            $count = TbMsLogisticsAccountInfo::where('logistics_company_id', $require_data['logistics_company_id'])
                ->where('account_name', $require_data['account_name'])
                ->where('account_password', $require_data['account_password'])
                ->where('token', $require_data['token'])
                ->count();
            if ($count) {
                throw new Exception(L('同物流公司，帐密，token 已存在'), 40440);
            }
        }
    }

    public function exportExcel()
    {
        $post_data = json_decode(htmlspecialchars_decode(I('post_data')), true);
        $xls_data_arr = $this->getAllLogisticsCompany($post_data);
        foreach ($xls_data_arr as &$temp_value) {
            if (is_array($temp_value['self_warehouse_cd_arr_val'])) {
                $temp_value['self_warehouse_cd_arr_val'] = implode(',', $temp_value['self_warehouse_cd_arr_val']);
            }
            if (is_array($temp_value['butt_item_cd_arr_val'])) {
                $temp_value['butt_item_cd_arr_val'] = implode(',', $temp_value['butt_item_cd_arr_val']);
            }
            if (is_array($temp_value['logistics_account_account_name_arr'])) {
                $temp_value['logistics_account_account_name_arr'] = implode(',', $temp_value['logistics_account_account_name_arr']);
            }

        }
        $xlsData = $xls_data_arr;
        $xlsName = "物流公司";
        $xlsCell = array(
            array('id', '编号'),
            array('logistios_company_cd_val', '物流公司'),
            array('forwarding_company_cd_val', '代理公司'),
            array('self_warehouse_cd_arr_val', '自有仓库'),
            array('butt_item_cd_arr_val', '对接项'),
            array('logistics_account_account_name_arr', '账号信息'),
            array('updated_at', '修改时间'),
            array('updated_user', '修改人'),
        );
        $Orders = A('Home/Orders');
        $width = ['type' => 'auto_size'];
        $Orders->exportExcel_self($xlsName, $xlsCell, $xlsData, $width);
    }

    public function test()
    {
        $logistics_account_infos = TbMsLogisticsAccountInfo::with('tbMsLogisticsCompany')->get();
        foreach ($logistics_account_infos as $logistics_account_info) {
            var_dump($logistics_account_info->tbMsLogisticsCompany->logistios_company_cd);
        }
        die();
    }

    /**
     * @param $require_data
     *
     * @return mixed
     */
    private function dataMenuUpdate($require_data)
    {
        $require_data['contract_validity_act_at'] = str_replace('.', '-', $require_data['contract_validity_act_at']);
        $require_data['contract_validity_end_at'] = str_replace('.', '-', $require_data['contract_validity_end_at']);
        return $require_data;
    }

    /**
     * @param $res
     * @param $v
     * @param $key
     * @param $cd_key_val_arr
     *
     * @param $temp_key_name
     *
     * @return array
     */
    private function updateCodeArrVal($res, $k, $v, $key, $cd_key_val_arr, $temp_key_name)
    {
        if ($k = $temp_key_name && strstr($v, ',')) {
            $explode = explode(',', $v);
            $res[$key][$temp_key_name] = $explode;
            foreach ($res[$key][$temp_key_name] as $e_v) {
                $res[$key][$temp_key_name . '_val'][] = $cd_key_val_arr[$e_v];
            }
        }
        return $res;
    }

    /**
     * @param $res_val
     *
     * @return mixed
     */
    private function cdTypeToArray($res_val)
    {
        $res_val['self_warehouse_cd_arr'] = (array)$res_val['self_warehouse_cd_arr'];
        $res_val['butt_item_cd_arr'] = (array)$res_val['butt_item_cd_arr'];
        $res_val['butt_item_cd_arr_val'] = (array)$res_val['butt_item_cd_arr_val'];
        return $res_val;
    }


    //重置物流轨迹查询次数
    public function resetLogisticsSearchCount()
    {
        try {
            $logistics_mode_id = DataModel::getDataNoBlankToArr()['logistics_mode_id'];
            $model = new Model();
            $model->startTrans();
            if (empty($logistics_mode_id)) {
                throw new Exception('请求为空');
            }
            $rClineVal = RedisModel::lock('logistics_mode_id' . $logistics_mode_id, 3);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res          = DataModel::$success_return;
            $res['code']  = 200;
            $res['data']  = (new LogisticsService($model))->resetLogisticsSearchCount($logistics_mode_id);
            $model->commit();
            RedisModel::unlock('logistics_mode_id' .$logistics_mode_id);
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }
}


