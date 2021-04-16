<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/7/24
 * Time: 14:45
 */

class GudsOptionsAction extends BaseAction
{
    //在用销售状态
    const ON_SALE = "N000100100";
    const PENDING_SALE = "N000100200";
    const STOP_SALE = "N000100300";
    const SOLD_OUT = "N000100600";
    
    private $allowedPlatform = [
        'N000830100' => 1,//B5C
        'N000831400' => 1,//G-APP-KR
        'N000834100' => 1,//G-APP-CN
        'N000834200' => 1,//G-APP-EN
        'N000834300' => 1//G-APP-JP
    ];
    

    /**
     * 为了避免权限验证导致的前段调试麻烦，暂时覆盖掉父类的登录验证
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * SKU 列表，一个商品的SKU列表。
     * @see http://erp.stage.com/index.php?g=guds&m=gudsOptions&a=getOptionList&gudsId=80006184&mainGudsId=80006184&sellerId=ahc
     */
    public function getOptionList()
    {
        $gudsId = I('gudsId');
        $mainGudsId = I('mainGudsId');//独立语言之外的
        if (empty($gudsId) ) {//|| empty($sellerId)
            $this->jsonOut(array('code' => 400, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        $tempSkuIdsArr = $optionMaps = $allOptions = array();
        $GudsOptionModel = new GudsOptionModel();
        $OptionMap = new OptionMapModel();
        $skuRelation = new SkuRelationsModel();
        //$params = ['sellerId' => $sellerId, 'mainGudsId'=> $mainGudsId];
        $params = ['mainGudsId'=> $mainGudsId];
        $optionGroup = $GudsOptionModel->getGudsOptions($params);
        $skuMaps = $OptionMap->getOptionMaps($optionGroup);//SKU属性映射关系表。
        $allOptions = $OptionMap->getOptionByCodeMap($skuMaps, LANG_CODE);//所有SKU属性名和属性值, 按当前页面语言读取

        //解析和映射出SKU列表数据。
        foreach ($optionGroup as $key => $opt) {
            //分离Option和OptionValue对组合(8001:800171;8002:800242)，转为逗号分隔的Code字符串，查询所有属性信息.
            $optMap = $GudsOptionModel->parseOptionMap($opt['GUDS_OPT_VAL_MPNG']);
            $optionGroup[$key]['optMaps'] = $optMap;
            $optionGroup[$key]['optText'] = $GudsOptionModel->parseOptionText($optMap, $allOptions);
            $optionGroup[$key]['optionMaps'] = $OptionMap->optionMapTranslate($opt['GUDS_OPT_VAL_MPNG'], $allOptions);
            $optionGroup[$key]['GUDS_OPT_ORG_PRC'] = sprintf('%0.2f', $opt['GUDS_OPT_ORG_PRC']);//格式化2位小数

            //解析SKU扩展信息，海关、物流相关。
            $extension = explode(',', $opt['CUSTOMS_LOGISTICS']);
            $customs = [];
            foreach ($extension as $extra){
                list($code, $status) = explode('-', $extra);
                $customs[$code] = $status;
            }
            $optionGroup[$key]['CUSTOMS_LOGISTICS'] = $customs;

            //ID数组，用于查询SKU绑定的第三方平台的SKU 相关信息。
            $tempSkuIdsArr[] = $opt['GUDS_OPT_ID'];;
        }

        $data = $skuRelation->getDataBySkuIds($tempSkuIdsArr);
        //SKU配置工具栏信息，选中的属性名列表，和属性值列表；
        $list = $OptionMap->optionMapParse($allOptions);
        $returnData = array('optionList' => $optionGroup, 'selector' => $list, 'thirdSkuData' => $data);
        $result = array('code' => 200, 'msg' => 'success', 'data' => $returnData);
        $this->jsonOut($result);
    }

    /**
     * 添加SKU页面，读取基本的SKU属性信息，所有的基本属性值。
     * 包括：币种，产地，Option属性，Option属性值
     * @see http://erp.stage.com/index.php?g=guds&m=gudsOptions&a=getBasicOptions
     *
     */
    public function getBasicOptions()
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
            DictionaryModel::SKU_FEATURE
        ]);
        
        //$OptionMap = D('OptionMap');
        $optionMap = new OptionMapModel();
        $options = $optionMap->getOptions(LANG_CODE);
        
        //筛选平台数据
        $platform = [];
        foreach ($dict[DictionaryModel::PLATFORM_PREFIX] as $code => $dicItem){
            if (isset($this->allowedPlatform[$code]) && 1 == $this->allowedPlatform[$code]){
                $platform[$code] = $dicItem;
            }
        }
        
        $data = array(
            'currency' => $dict[DictionaryModel::CURRENCY_PREFIX],
            'origin' =>   $areaModel->getCountiesBySort(),
            'platform' => $platform,
            'refundRate' => $dict[DictionaryModel::REFUND_RATE],
            'expressCat' => $dict[DictionaryModel::EXPRESS_CAT],
            'expressType' => $dict[DictionaryModel::EXPRESS_TYPE],
            'crossBoardRate' => $dict[DictionaryModel::CROSS_BOARD_RATE],
            'saleState' => $dict[DictionaryModel::GUDS_SALE_STATUS_PREFIX],
            'warehouse' => $dict[DictionaryModel::WAREHOUSE_PREFIX],
            'skuFeature' => $dict[DictionaryModel::SKU_FEATURE],
            'options' => $options
        );

        $result = array('code' => 200, 'msg' => 'success', 'data' => $data);
        $this->jsonOut($result);
    }

    /**
     * 获取指定语言版本的，指定SKU属性的 属性值列表。
     * @see http://erp.stage.com/index.php?g=guds&m=gudsOptions&a=getOptionValues&selectedOptId=8007
     *
     * @params number selectedOptId 请求参数为，添加SKU属性时选取的SKU 属性 ID (CODE).
     * @throws Exception
     */
    public function getOptionValues()
    {
        $optionId = I('selectedOptId');
        if (empty($optionId)) {
            $this->jsonOut(array('code' => 400, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        $OptionMap = new OptionMapModel();
        $optionValues = $OptionMap->getOptionValues($optionId, LANG_CODE);

        $result = array('code' => 200, 'msg' => 'success', 'data' => $optionValues);
        $this->jsonOut($result);
    }

    /**
     * 根据选择的SKU属性名称组和SKU属性值组，构建SKU组合，生成列表用于填写其他信息。
     * @see http://erp.stage.com/index.php?g=guds&m=gudsOptions&a=getOptionGroup
     *
     * 请求方式为：JSON格式参数， 异步请求。
     * @params json 参数格式为：[{'8001':'800126,800127,800130','8002':'800352,800353'}]
     */
    public function getOptionGroup()
    {
        //[{'8001':'800126,800127,800130','8002':'800352,800353'}]
        $optionList = file_get_contents('php://input');
        $selected = json_decode($optionList, true);

        //$selected = array('8001'=>'800126,800127,800130','8002'=>'800352,800353');
        if (empty($selected) || !is_array($selected)) {
            $this->jsonOut(array('code' => 4001, 'msg' => '参数错误', 'data' => $optionList));
        }

        foreach ($selected as $key => $value){
            if (empty($key) || empty($value)){
                $this->jsonOut(array('code' => 4001, 'msg' => L('INVALID_PARAMS'), 'data' => ''));
            }
        }

        $OptionMap = new OptionMapModel();
        //组合所有属性名的CODE码和属性值的CODE码，语言默认中文。
        $optionData = $OptionMap->getOptionByCodeMap($selected, LANG_CODE);
        
        //分离SKU属性名称，并根据不同的SKU属性名称 组合成单独的属性值数组。
        $optionNames = $optionValues = $optionGroup = [];
        foreach ($optionData as $code => $option) {
            if (empty($option['PAR_CODE'])) //Option name 数组分离。
            {
                $optionNames[$code] = $option;
            }
            else // 分离属性值，并且用属性名的CODE作为数组索引，拆分出不同属性名对应的属性值到单独数组。
            {
                $optionValues[$option['PAR_CODE']][$option['CODE']] = $option; //@important 这里要注意索引，不可默认
            }
        }
        //根据属性值构造SKU属性组。
        $optionGroup = $OptionMap->cartesian($optionValues);
        $data = array('optionNames' => $optionNames, 'optionGroup' => $optionGroup);
        $result = array('code' => 200, 'msg' => 'success', 'data' => $data);
        $this->jsonOut($result);
    }

    /**
     * 根据选定的Option属性，和关键词搜索，该属性下的属性值。
     * @see http://erp.stage.com/index.php?g=guds&m=gudsOptions&a=searchOptionValue&optNameCode=8001&keyword=110
     */
    public function searchOptionValue()
    {
        $optionNameCode = I('optNameCode');
        $keyword = I('keyword');

        if (empty($optionNameCode)) {
            $this->jsonOut(array('code' => 4002, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        $OptionMap = D('OptionMap');
        $optionValues = $OptionMap->searchOptionValues($optionNameCode, $keyword);
        $result = array('code' => 200, 'msg' => 'success', 'data' => $optionValues);
        $this->jsonOut($result);
    }


    /**
     * 为指定的SKU选项名称：添加新的SKU值数据，支持多组同时添加。
     * @see http://erp.stage.com/index.php?g=guds&m=gudsOptions&a=addNewOptionValue
     */
    public function addNewOptionValue()
    {
        $params = file_get_contents('php://input');
        $params = json_decode($params, true);
        if (empty($params) || empty($params['optNameCode']) || empty($params['optValues'])) {
            $this->jsonOut(array('code' => 4003, 'msg' => L('INVALID_PARAMS'), 'data' => $params));
        }

        //验证重复性，只要有任意语言在当前指定Name下面有相同的值，就认为重复存在。
        $optValueModel = new OptionValueModel();
        $isExist = $optValueModel->checkExist($params['optNameCode'], $params['optValues']);
        if ($isExist){
            $this->jsonOut(array('code' => 4003, 'msg' => L('HAS_EXIST'), 'data' => $params));
        }

        //开始添加逻辑
        list($result, $lastValues) = $optValueModel->createOptionValues($params['optNameCode'], $params['optValues']);
        if ($result <= 0 || $result == false) {
            $this->jsonOut(array('code' => 4004, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        //$multipleLang = $optValKr . '/' . $optValCn . '/' . $optValEn . '/' . $optValJp;
        $optionMapModel = new OptionMapModel();
        $result = $optionMapModel->createNewValues($params['optNameCode'], $lastValues);
        if ($result <= 0 || $result == false) {
            $this->jsonOut(array('code' => 4005, 'msg' => L('SYSTEM_ERROR'), 'data' => null));
        }

        //读取并返回最近新添加的Value的信息。
        $newValueMap = $optionMapModel->getOptValues(array_keys($lastValues));
        $this->jsonOut(array('code' => 200, 'msg' => 'Success', 'data' => $newValueMap));
    }

    /**
     * 验证SKU属性是否存在相同属性的SKU，如果存在了就不允许再添加了。
     */
    public function checkExist()
    {
        $params = file_get_contents('php://input');
        $options = json_decode($params, true);
        if (empty($options) || empty($options['mainGudsId']) || empty($options['optionGroup'])){
            $this->jsonOut(array('code' => 4006, 'msg' => L('INVALID_PARAMS'), 'data' => 'CE-001'));
        }

        $optModel = new GudsOptionModel();
        $res = $optModel->checkOptionExist($options['mainGudsId'], $options['optionGroup']);
        $res = ['code' => '200', 'msg' => 'success', 'data' => $res];
        $this->jsonOut($res);
    }

    /**
     * 构建新的SKU信息，根据选择的SKU属性情况，会是多条生成。
     * @see http://erp.stage.com/index.php?g=guds&m=gudsOptions&a=create
     */
    public function create()
    {
        $params = file_get_contents('php://input');
        $options = json_decode($params, true);
        if (empty($options) || empty($options['optionGroup'])) {
            $this->jsonOut(array('code' => 4006, 'msg' => L('INVALID_PARAMS'), 'data' => $params));
        }

        if (empty($options['gudsId']) || empty($options['mainGudsId'])) {
            $this->jsonOut(array('code' => 4007, 'msg' => L('INVALID_PARAMS'), 'data' => $params));
        }

        //验证属性 必填项
        $GudsOptionModel = new GudsOptionModel();
        foreach ($options['optionGroup'] as $key => $values) {
        
            //前端发布需要更多必填项。
            if (1 == $options['publishType']) {
                if (empty($values['attributes']['saleState'])) {
                    $this->jsonOut(array('code' => 4009, 'msg' => L('INVALID_PARAMS'), 'data' => null));
                }
            } elseif (empty($values['attributes']['PRICE'])) {
                $this->jsonOut(array('code' => 4008, 'msg' => L('NEED_PURCHASE_PRICE'), 'data' => $params));
            }

            if (1 == $options['publishType'] && empty($values['attributes']['WEIGHT'])){
                $this->jsonOut(array('code' => 400801, 'msg' => L('NEED_WEIGHT'), 'data' => $params));
            }

            //条形码长度超过 100 提示错误。
            if (strlen($values['attributes']['UPC']) >= 100){
                $this->jsonOut(array('code' => 400802, 'msg' => L('INVALID_PARAMS'), 'data' => $params));
            }

            //条形码一个的时候修改条码同时更新海关条码，当多个时修改，海关条码不改，空就留空。
            $upcList = explode(',', $values['attributes']['UPC']);
            foreach ($upcList as $item){
                if (!empty($item) && (strlen($item) < 8 || !is_numeric($item))){
                    $this->jsonOut(array('code' => 401203, 'msg' => L('BAR_CODE_NEED_NUMBER'), 'data' => ''));
                }
            }

            //一个条形码时海关条码相同，多个时海关条码需要手动填写，这里留空。
            $upcList = explode(',', ($values['attributes']['UPC']));
            if (count($upcList) == 1){
                $options['optionGroup'][$key]['attributes']['hsUpcCode'] = $upcList[0];
            }

            //验证物流信息配置必填项，JSON对象CODE码以及值
            if (empty($values['extension'])){
                $res = [
                    'code' => 401203,
                    'msg' => L('NEED_CUSTOMS_INFO'),
                    'data' => 'Customs and Logistics are needed!'
                ];
                $this->jsonOut($res);
            }

            $res = $GudsOptionModel->checkOptionExist($options['mainGudsId'], $values);
            if (true == $res){
                $this->jsonOut(['code'=>401204, 'msg' => L('HAS_EXIST'), 'data' => null]);
            }
        }

        //更新SPU的产地和币种信息
        $res = $this->updateGuds($options);//更新所有 与该SKU列表有关联的商品信息，即 MainGudsID一样的
        $result = $GudsOptionModel->saveGudsOptions($options);//save SKU
        if ($result === false || empty($result)) {
            $this->jsonOut(array('code' => 500, 'msg' => L('SYSTEM_ERROR'), 'data' => null));
        }

        $this->jsonOut(array('code' => 200, 'msg' => 'success', 'data' => ['save'=> $result,'updateSpu' => $res]));
    }

    /**
     * 修改SKU信息
     * 前端发布状态或者SKU为On SALE状态，必须填写完整信息。
     *
     * @see http://erp.stage.com/index.php?g=guds&m=gudsOptions&a=modify
     */
    public function modify()
    {
        $params = file_get_contents("php://input");
        $options = json_decode($params, true);

        if (empty($options) || empty($options['optionGroup'])) {
            $this->jsonOut(array('code' => 4010, 'msg' => L('INVALID_PARAMS'), 'data' => $params));
        }

        if ( empty($options['gudsId']) || empty($options['mainGudsId'])) {
            $this->jsonOut(array('code' => 4011, 'msg' => L('INVALID_PARAMS'), 'data' => $params));
        }

        //验证SKU信息完整性，如果是前端发布或者SKU的销售状态选择了 在售 必须填写全部信息，否则报错。
        $GudsOption = new GudsOptionModel();
        foreach ($options['optionGroup'] as &$attr)
        {
            if (1 == $options['publishType'] && empty($attr['attributes']['saleState'])){
                $this->jsonOut(array('code' => 4012, 'msg' => L('LESS_FRONTEND_PUSH'), 'data' => $params));
            }

            if(0 == $options['publishType'] && empty($attr['attributes']['PRICE'])){
                $this->jsonOut(array('code' => 401201, 'msg' => L('NEED_PURCHASE_PRICE'), 'data' => $params));
            }

            if (1 == $options['publishType'] && empty($attr['attributes']['WEIGHT'])){
                $this->jsonOut(array('code' => 401201, 'msg' => L('NEED_WEIGHT'), 'data' => $params));
            }

            //条形码长度超过 100 提示错误。
            if (strlen($attr['attributes']['UPC']) >= 100){
                $this->jsonOut(array('code' => 401202, 'msg' => L('INVALID_PARAMS'), 'data' => $params));
            }

            //条形码一个的时候修改条码同时更新海关条码，当多个时修改，海关条码不改，空就留空。
            $upcList = explode(',', $attr['attributes']['UPC']);
            foreach ($upcList as $item){
                if (!empty($item) && (strlen($item) < 8 || !is_numeric($item))){
                    $this->jsonOut(array('code' => 401203, 'msg' => L('BAR_CODE_NEED_NUMBER'), 'data' => ''));
                }
            }

            if (count($upcList) == 1 || empty($upcList)){
                $attr['attributes']['hsUpcCode'] = $upcList[0];
            }

            //验证物流信息配置必填项，JSON对象CODE码以及值
            if (empty($attr['extension'])){
                $res = [
                    'code' => 401203,
                    'msg' => L('NEED_CUSTOMS_INFO'),
                    'data' => 'Customs and Logistics are needed!'
                ];
                $this->jsonOut($res);
            }
        }
        //更新所有 与该SKU列表有关联的商品信息，即 MainGudsID一样的
        $res = $this->updateGuds($options);
        //更新SKU属性
        $mainGudsId = $options['mainGudsId'];
        try {
            foreach ($options['optionGroup'] as $key => $item) {
                //SKU选了ON SALE 那么就必须是前端发布
                $publishType = (self::ON_SALE == $item['saleState']) ? 1 : $options['publishType'];
                $item['attributes']['minBuyNum'] = !empty($options['minBuyNum']) ? $options['minBuyNum'] : 1;
                $item['attributes']['optionCode'] = $item['optionCode'];
                $item['attributes']['publishType'] = $publishType;
                $item['attributes']['extension'] = $item['extension'];
                //$condition = ['sellerId' => $sellerId, 'mainGudsId'=> $mainGudsId, 'optionId' => $item['optionId']];
                $res += $GudsOption->updateOptions($item['attributes'], $mainGudsId, $item['optionId']);
            }
        } catch (Exception $e) {
            $this->jsonOut(array('code' => 500, 'msg' => L('SYSTEM_ERROR'), 'data' => $e));
        }
        $gudsAction = A('Guds/Guds');
        $gudsAction->sendMessageToMq($mainGudsId, 'N000920100',['optType'=>0,'saleStatus' => 'N000100100']);
        $this->jsonOut(array('code' => 200, 'msg' => 'success', 'data' => $res));
    }

    /**
     * 更新SKU的启用状态
     */
    public function setEnable()
    {
        $mainGudsId = I('get.mainGudsId');
        $optionId = I('get.optionId');
        $enable = I('get.enable');

        if (empty($mainGudsId) || empty($optionId) || !isset($enable) || $enable == ''){
            $this->jsonOut(array('code' => 4011, 'msg' => L('INVALID_PARAMS'), 'data' => ''));
        }

        $optionModel = new GudsOptionModel();
        try {
            $res = $optionModel->updateOptions(['isEnable' => $enable], $mainGudsId, $optionId);
        } catch (Exception $e) {
            $this->jsonOut(array('code' => 500, 'msg' => L('SYSTEM_ERROR'), 'data' => $e));
        }

        $this->jsonOut(array('code' => 200, 'msg' => 'success', 'data' => $res));
    }

    /**
     * 更新商品信息
     * @param array $options 商品数据参数
     * @return mixed
     */
    private function updateGuds($options)
    {
        //这些属性将废弃。
        $attr = $options['optionGroup'][0]['attributes'];
        !empty($attr['LENGTH']) && $gudsUpdate['GUDS_DLVC_DESN_VAL_1'] = $attr['LENGTH'];
        !empty($attr['WIDTH']) && $gudsUpdate['GUDS_DLVC_DESN_VAL_2'] = $attr['WIDTH'];
        !empty($attr['HEIGHT']) && $gudsUpdate['GUDS_DLVC_DESN_VAL_3'] = $attr['HEIGHT'];
        !empty($attr['WEIGHT']) && $gudsUpdate['GUDS_DLVC_DESN_VAL_4'] = $attr['WEIGHT'];
        !empty($attr['saleState']) && $gudsUpdate['GUDS_SALE_STAT_CD'] = $attr['saleState'];
        !empty($options['origin']) && $gudsUpdate['GUDS_ORGP_CD'] = $options['origin'];
        $gudsUpdate['GUDS_REG_STAT_CD'] = 'N000420200'; #商品修改，就要重新审核，改为草稿状态
        !empty($options['currency']) && $gudsUpdate['STD_XCHR_KIND_CD'] = $options['currency'];
        !empty($options['platform']) && $gudsUpdate['SALE_CHANNEL'] = $options['platform'];
        !empty($options['isRefundTax']) && $gudsUpdate['GUDS_VAT_RFND_YN'] = $options['isRefundTax'];
        !empty($options['refundTaxRate']) && $gudsUpdate['RETURN_RATE'] = $options['refundTaxRate'];
        !empty($options['overseasTax']) && $gudsUpdate['ADDED_TAX'] = $options['overseasTax'];  //FIXME 这里要改成跨境综合税
        !empty($options['PCS']) && $gudsUpdate['GUDS_DLVC_DESN_VAL_5'] = $options['PCS'];
        !empty($options['minBuyNum']) && $gudsUpdate['MIN_BUY_NUM'] = $options['minBuyNum'];
        !empty($options['maxBuyNum']) && $gudsUpdate['MAX_BUY_NUM'] = $options['maxBuyNum'];
        !empty($options['expressCat']) && $gudsUpdate['BELONG_SEND_CAT'] = $options['expressCat'];
        !empty($options['expressType']) && $gudsUpdate['LOGISTICS_TYPE'] = $options['expressType'];
        isset($options['publishType']) && $options['PUBLISH_TYPE'] = $options['publishType'];

        //update guds info, 这里一定是更新 GUDS_ID的商品，就是特定语言的商品的产地和币种。
        $gudsModel = new GudsModel();
        $res = $gudsModel->updateGudsByMainGudsId($gudsUpdate, $options['mainGudsId']);
        return $res;
    }
    
    /**
     * 读取SKU价格列表，按照MainGudsId读取
     */
    public function getOptionPrice()
    {
        $mainGudsId = I('mainGudsId','');//80006184
        $optionId = I('optionId','');//8000758102
        $languageType = I('lang', LANG_CODE);//N000920100
        if (empty($mainGudsId))
        {
            $this->jsonOut(array('code' => 4013, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }
        
        $priceModel = new OptionPriceModel();
        $gudsOptionModel = new GudsOptionModel();
        $price = $priceModel->getPriceByMainGudsId($mainGudsId);

        //转义输出
        $priceGroup = [];
        foreach ((array)$price as $item){
            $priceGroup[$item['GUDS_OPT_ID']][] = $priceModel->parseFieldsMap($item);
        }
        
        $OptionMap = new OptionMapModel();
        $optionGroup = $gudsOptionModel->getGudsOptions(['mainGudsId' => $mainGudsId]);
        $skuMaps = $OptionMap->getOptionMaps($optionGroup);
        $allOptions = $OptionMap->getOptionByCodeMap($skuMaps, $languageType);

        //解析和映射出SKU列表数据。
        $optionPairs = [];
        foreach ($optionGroup as $key => $option) {
            $optMaps = $gudsOptionModel->parseOptionMap($option['GUDS_OPT_VAL_MPNG']);
            $optionPairs[$option['GUDS_OPT_ID']] = $gudsOptionModel->parseOptionText($optMaps, $allOptions);
        }
        
        $result = array('priceGroup' => $priceGroup, 'skuInfo' => $optionPairs);
        $this->jsonOut(array('code' => 200, 'msg' => 'success', 'data' => $result));
    }
    
    /**
     * 为SKU添加价格信息
     */
    public function addPrice()
    {
        $params = file_get_contents("php://input");
        $options = json_decode($params, true);
        if (empty($options) || empty($options['mainGudsId']) || empty($options['priceGroup']))
        {
            $this->jsonOut(array('code' => 4013, 'msg' => L('INVALID_PARAMS'), 'data' => 'GOA-4013'));
        }
    
        $optionModel = new GudsOptionModel();
        $updateOptionRes = 0;
        foreach ($options['priceGroup'] as $key => $item){
            if (empty($item['optionId']) || empty($item['warehouse']) || empty($item['purchasePrice'])){
                $this->jsonOut(array('code' => 4014, 'msg' => L('INVALID_PARAMS'), 'data' => 'GOA-4014'));
            }
            
            //$item['grossProfitMargin']做可选，如果没有提供，Model层有默认值 0.00
            if (empty($item['marketPrice']) || empty($item['realPrice'])){
                $this->jsonOut(array('code' => 4015, 'msg' => L('INVALID_PARAMS'), 'data' => 'GOA-4015'));
            }
            
            //验证完毕，同步更新SKU表的采购价：影响ERP商品列表页展示的价格；更新：一件代发价格和是否显示一件代发价格，
            $asyncPrice = ['PRICE' => $item['purchasePrice'],'realPrice' => $item['realPrice']];
            $updateOptionRes += $optionModel->updateOptions($asyncPrice, $options['mainGudsId'], $item['optionId']);
        }
        
        $priceModel = new OptionPriceModel();
        $saveRes = $priceModel->saveSkuPrice($options);
        
        if ($saveRes){
            $this->jsonOut(array('code' => 200, 'msg' => 'success', 'data' => $saveRes));
        } else {
            $this->jsonOut(array('code' => 500, 'msg' => L('SYSTEM_ERROR'), 'data' => $saveRes));
        }
    }
    
    /**
     * 更新价格信息
     * 更新接口里面同时支持，添加新价格，为了给前端减少工作量
     */
    public function updatePrice()
    {
        $params = file_get_contents("php://input");
        $options = json_decode($params, true);
        if (empty($options) || empty($options['mainGudsId']) || empty($options['priceGroup']))
        {
            $this->jsonOut(array('code' => 4013, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }
    
        $optionModel = new GudsOptionModel();
        $priceModel = new OptionPriceModel();
        
        //分离新添加和更新的数据，以是否有id字段或id字段是否为空为依据
        $newPrice = [];
        $updatePriceRes = $createRes = $updateOptionRes = 0;
        foreach ($options['priceGroup'] as $id => $item){
            #添加参数验证
            if (empty($item['optionId']) || empty($item['warehouse']) || empty($item['purchasePrice'])){
                $this->jsonOut(array('code' => 4014, 'msg' => L('INVALID_PARAMS'), 'data' => 'GOA-4014'));
            }
            
            #添加参数验证
            if (empty($item['marketPrice']) || empty($item['realPrice']) ){
                $this->jsonOut(array('code' => 4015, 'msg' => L('INVALID_PARAMS'), 'data' => 'GOA-4015'));
            }
            
            #开始分离新添加的价格数据
            if (empty($item['id'])){
                $newPrice[] = $item;
                unset($options['priceGroup'][$id]);
            }
            
            //开始处理更新操作，首先更新价格表，然后更新SKU表的采购价，同步更新列表页上的采购价
            $updatePriceRes += $priceModel->updatePrice($item, $options['mainGudsId']);
            $asyncPrice = ['PRICE' => $item['purchasePrice'], 'realPrice' => $item['realPrice']];
            $updateOptionRes += $optionModel->updateOptions($asyncPrice, $options['mainGudsId'], $item['optionId']);
        }
        
        //如果包含新添加的价格，直接插入数据库
        if (!empty($newPrice)){
            $options['priceGroup'] = $newPrice;
            $createRes = $priceModel->saveSkuPrice($options);
        }
        $gudsAction = A('Guds/Guds');
        $gudsAction->sendMessageToMq($options['mainGudsId'], 'N000920100',['optType'=>0,'saleStatus' => 'N000100100']);
        if ($updatePriceRes || $createRes || $updateOptionRes){
            $res = array('code' => 200, 'msg' => 'success', 'data' => [ $updatePriceRes, $updateOptionRes]);
            $this->jsonOut($res);
        } else {
            $this->jsonOut(array('code' => 500, 'msg' => L('SYSTEM_ERROR'), 'data' => $updatePriceRes));
        }
    }
    
    /**
     * 删除价格数据
     */
    public function deletePrice()
    {
        $priceId = I('id');
        $mainGudsId = I('mainGudsId');
        $optionId = I('optionId');
        
        if(empty($priceId) || empty($mainGudsId) || empty($optionId)){
            $this->jsonOut(array('code' => 4014, 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }
        
        $priceModel = new OptionPriceModel();
        $condition = ['id' => $priceId, 'mainGudsId' => $mainGudsId];
        $res = $priceModel->deletePrice($condition);
        $priceList = $priceModel->getPriceByOptionId($mainGudsId, $optionId);
        
        #没有价格信息了,设置为停售状态，删除后提示：价格已删除，该SKU在前端不再展示，等同于下架。
        if ($res && empty($priceList)){
            $optionModel = new GudsOptionModel();
            $setOffline = $optionModel->updateOptions(['saleState' => self::STOP_SALE], $mainGudsId, $optionId);
            $this->jsonOut(array('code' => 200, 'msg' => L('DELETE_LAST_PRICE'), 'data' => $setOffline));
        }
        
        if ($res){
            $this->jsonOut(array('code' => 200, 'msg' => 'success', 'data' => $res));
        } else {
            $this->jsonOut(array('code' => 500, 'msg' => L('NOT_EXIST'), 'data' => $res));
        }
    }
    
    /**
     * 下载导入SKU的模板Excel表
     */
    public function download()
    {

    }
    
    /**
     * 批量导入SKU信息
     */
    public function import()
    {
    
    }

    /**
     * 借助空操作，处理错误请求和扫描请求（扫描请求往往是暴力猜测请求，造成很大压力）。
     */
    public function _empty()
    {
        //错误请求，可能是输错了也可能是而已的扫描请求，进行404处理。
        die("Illegal Request, Please make sure you send a correct request! ");
    }

}