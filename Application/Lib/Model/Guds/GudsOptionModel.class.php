<?php

/**
 * 品牌模型类
 * User: afanti
 * Date: 2017/7/25
 * Time: 13:40
 */
class GudsOptionModel extends Model
{
    protected $trueTableName = "tb_ms_guds_opt";

    protected $_map = [
        'sellerId'          => 'SLLR_ID',
        'mainGudsId'        => 'GUDS_ID',
        'optionId'          => 'GUDS_OPT_ID',
        'selfCode'          => 'GUDS_OPT_CODE',
        'optionMap'         => 'GUDS_OPT_VAL_MPNG',
        'upcCode'           => 'GUDS_OPT_UPC_ID',
        'registerState'     => 'GUDS_OPT_REG_STAT_CD',
        'saleState'         => 'GUDS_OPT_SALE_STAT_CD',
        'minOrderCount'     => 'GUDS_OPT_MIN_ORD_QTY',
        'expire'            => 'GUDS_OPT_EXP_DT',
        'purchasePrice'     => 'GUDS_OPT_ORG_PRC',
        'marketPrice'       => 'GUDS_OPT_SALE_PRC',
        'salePrice'         => 'GUDS_OPT_BELOW_SALE_PRC',
        'length'            => 'GUDS_OPT_LENGTH',
        'width'             => 'GUDS_OPT_WIDTH',
        'height'            => 'GUDS_OPT_HEIGHT',
        'weight'            => 'GUDS_OPT_WEIGHT',
        'CR'                => 'GUDS_HS_CODE',
        'HS'                => 'GUDS_HS_CODE2',
        'publishType'       => 'PUBLISH_TYPE',
        'hsUpcCode'         => 'GUDS_HS_UPC',
        'isEnable'          => 'IS_ENABLE',
        'customsLogistics'  => 'CUSTOMS_LOGISTICS'
    ];

    /**
     * 读取单条SKU数据。
     * @param $gudsOptionId
     * @param array $lang
     * @return array|bool
     */
    public function getGudsOptionById($gudsOptionId, $lang = ['N000920100', 'N000920200', 'N000920300', 'N000920400'])
    {
        if (empty($gudsOptionId)) {
            return false;
        }

        $where = " GUDS_OPT_ID ='{$gudsOptionId}' ";

        //指定语言类型的情况下
        if (!empty($lang)) {
            $lang = implode("','", $lang);
            $where .= " AND LANGUAGE IN ('{$lang}')";
        }

        //是否过滤字段列表
        $sql = "
          SELECT
            O.SLLR_ID AS sellerId,
            O.GUDS_ID AS mainGudsId,
            G.GUDS_NM AS gudsName,
            G.LANGUAGE AS lang,
            O.GUDS_OPT_ID AS skuId,
            O.GUDS_OPT_CODE AS selfCode,
            O.GUDS_OPT_VAL_MPNG AS skuMap,
            O.GUDS_OPT_UPC_ID AS upcCode,
            O.GUDS_OPT_REG_STAT_CD AS registerState,
            O.GUDS_OPT_SALE_STAT_CD AS saleState,
            O.GUDS_OPT_MIN_ORD_QTY AS minOrderCount,
            O.GUDS_OPT_EXP_DT AS expire,
            O.GUDS_OPT_ORG_PRC AS originalPrice,
            O.GUDS_OPT_SALE_PRC AS marketPrice,
            O.GUDS_OPT_BELOW_SALE_PRC AS salePrice,
            O.GUDS_OPT_LENGTH AS length,
            O.GUDS_OPT_WIDTH AS width,
            O.GUDS_OPT_HEIGHT AS height,
            O.GUDS_OPT_WEIGHT AS weight,
            O.GUDS_HS_CODE AS crCode,
            O.GUDS_HS_CODE2 AS hsCode,
            O.PUBLISH_TYPE AS publishType,
            O.GUDS_HS_UPC AS hsUpcCode,
            O.IS_ENABLE AS isEnable,
            O.CUSTOMS_PRICE AS customsPrice,
            O.CUSTOMS_LOGISTICS AS customsLogistics
          FROM tb_ms_guds_opt AS O
          LEFT JOIN tb_ms_guds AS G ON O.GUDS_ID = G.MAIN_GUDS_ID
          WHERE {$where}";

        $res = $this->query($sql);
        //echo $this->getLastSql();
        return $res;
    }

    /**
     * 查询指定品牌的指定商品的，指定的语言类型的SKU列表。
     * @param array $params 查询条件,[sellerId, mainGudsId, optionId] 等
     * @return array | bool
     */
    public function getGudsOptions($params = [])
    {

        $where = "1";
        //!empty($params['sellerId']) && $where .= " AND SLLR_ID = '{$params['sellerId']}'";
        !empty($params['mainGudsId']) && $where .= " AND GUDS_ID = {$params['mainGudsId']}";
        !empty($params['optionId']) && $where .= " AND GUDS_OPT_ID ='{$params['optionId']}'";

        $optionGroup = $this->where($where)
            ->field('
            SLLR_ID,
            GUDS_ID,
            GUDS_OPT_ID,
            GUDS_OPT_CODE,
            GUDS_OPT_VAL_MPNG,
            GUDS_OPT_UPC_ID,
            GUDS_OPT_REG_STAT_CD,
            GUDS_OPT_SALE_STAT_CD,
            GUDS_OPT_MIN_ORD_QTY,
            GUDS_OPT_EXP_DT,
            GUDS_OPT_ORG_PRC,
            GUDS_OPT_SALE_PRC,
            GUDS_OPT_BELOW_SALE_PRC,
            GUDS_OPT_LENGTH,
            GUDS_OPT_WIDTH,
            GUDS_OPT_HEIGHT,
            GUDS_OPT_WEIGHT,
            GUDS_HS_CODE,
            GUDS_HS_CODE2,
            GUDS_OPT_MIN_ORD_QTY,
            PUBLISH_TYPE,
            GUDS_HS_UPC,
            IS_ENABLE,
            CUSTOMS_PRICE,
            CUSTOMS_LOGISTICS')
            ->select();
        //echo $this->getLastSql();
        return !empty($optionGroup) ? $optionGroup : false;
    }

    /**
     * 验证指定的品牌，指定mainGudsId 下面是否有SKU数据了，如果有了就不允许修改和添加。
     * @param $sellerId
     * @param $mainGudsId
     * @return bool
     */
    public function checkOptions($sellerId, $mainGudsId)
    {
        $maxOptId = $this->where("SLLR_ID = '{$sellerId}' AND GUDS_ID='{$mainGudsId}' ")->getField("MAX(GUDS_OPT_ID)");
        return !empty($maxOptId) ? $maxOptId : false;
    }

    /**
     * 构建商品的SKU组数据，按照选择的Option和OptionValue和语言版本构建SKU属性组。
     *
     * `SLLR_ID` varchar(50)  销售者ID，实际是商家ID；需要商品页面传过来，保存商品后才会有这个属性。
     * `GUDS_ID` varchar(20) 商品ID，关联商品主表；SPU的主ID，
     * `GUDS_OPT_ID` varchar(20) NOT NULL COMMENT '상품옵션아이디 | 商品SKU ID + 01，+02，+03...+99
     * `GUDS_OPT_CODE` varchar(14) DEFAULT NULL COMMENT '商品SKU自编码（同仓不能相同 不同仓可以相同）',
     *
     * @param array $data SKU属性集合
     * @return false|int
     */
    public function saveGudsOptions($data)
    {

        if (empty($data)) return false;

        //开启事务，执行SQL。
        $this->startTrans();
        $newOptIdSql = "SELECT MAX(GUDS_OPT_ID) id FROM tb_ms_guds_opt WHERE GUDS_ID='{$data['mainGudsId']}' FOR UPDATE";
        $newId = $this->query($newOptIdSql);
        $optionGroup = $this->buildOptionGroup($data, $newId[0]['id']);
        //SKU 的字段属性
        $fields = implode(',', array_keys(reset($optionGroup)));
        list($skuValArr, $bindArr) = $this->buildSqlValues($optionGroup);

        //所有SKU的值字符串，拼接起来批量执行插入
        $valueStr = implode(',', $skuValArr);
        $skuSql = "INSERT INTO {$this->trueTableName} ({$fields}) VALUES {$valueStr};";

        //构建SKU海关和物流相关数据SQL
        $valueStr = implode(',', $bindArr);
        $bindSql = "INSERT INTO `tb_ms_params` VALUES {$valueStr}";

        //执行数据保存
        $skuRes = $this->execute($skuSql);
        $bindRes = !empty($bindArr) ? $this->execute($bindSql) : false;

        if ($skuRes == false || $bindRes == false){
            $this->rollback();
        }else {
            $this->commit();
        }

        return  $skuRes && $bindRes;
    }

    /**
     * 构造SKU的属性和属性值对，然后处理映射关系。
     * @param array $data SKU属性参数，来自前段传来
     * @param number $newOptId 新的OPTION ID。
     * @return array
     */
    private function buildOptionGroup($data, $newOptId = null)
    {
        //$sellerId = $data['sellerId'];
        $mainGudsId = $data['mainGudsId'];
        $group = array();
        $checkDuplicate = [];
        foreach ($data['optionGroup'] as $key => $option) {
            $attr = $option['attributes'];
            $extension = $option['extension'];
            unset($option['attributes'], $option['extension']);

            //根据SKU属性组合，验证重复性，重复的会去掉。
            $optionsMapStr = $this->buildOptionMap($option);
            if (isset($checkDuplicate[$optionsMapStr])){
                continue;//如果有相同SKU组合了，就不添加直接过滤掉
            }
            $optId = $this->buildOptionId($mainGudsId, $key, $newOptId); // SKU ID 跟着MainGudsId走的，因为所有语言公用SKU。
            $checkDuplicate[$optionsMapStr] = 1;//不重复添加到重复验证表
            $group[$optId]['GUDS_OPT_VAL_MPNG'] = $optionsMapStr;
            //$group[$optId]['SLLR_ID'] = $sellerId;
            $group[$optId]['GUDS_ID'] = $mainGudsId;//SKU表中的 GUDS_ID实际上是 MAIN_GUDS_ID。
            $group[$optId]['GUDS_OPT_ID'] = $optId;
            //$group[$optId]['GUDS_OPT_CODE'] = $this->buildCustomCode($data['CAT_CD_ALP'], $data['BRND_ID'], $optId);//自编码去掉
            $group[$optId]['GUDS_OPT_UPC_ID'] = $attr['UPC'];
            //默认的SKU审核状态，默认为【草稿】，TODO 后面backend停用后就删除SKU的状态，只保留SPU上的就可以
            $group[$optId]['GUDS_OPT_REG_STAT_CD'] = 'N000420200';

            //销售状态，默认【销售准备】状态。'N000100200';
            $group[$optId]['GUDS_OPT_SALE_STAT_CD'] = !empty($attr['saleState']) ? $attr['saleState'] : "N000100200";
            $group[$optId]['GUDS_OPT_ORG_PRC'] = $attr['PRICE'];
            $group[$optId]['GUDS_OPT_SALE_PRC'] = $attr['PRICE'] * (1 + 0.5);
            $group[$optId]['GUDS_OPT_LENGTH'] = $attr['LENGTH'];
            $group[$optId]['GUDS_OPT_WIDTH'] = $attr['WIDTH'];
            $group[$optId]['GUDS_OPT_HEIGHT'] = $attr['HEIGHT'];
            $group[$optId]['GUDS_OPT_WEIGHT'] = !empty($attr['WEIGHT']) ? $attr['WEIGHT'] : 0;
            $group[$optId]['GUDS_OPT_USE_YN'] = 'Y';
            $group[$optId]['SYS_REGR_ID'] = $_SESSION['m_loginname'];
            $group[$optId]['SYS_CHGR_ID'] = $_SESSION['m_loginname'];
            $group[$optId]['SYS_REG_DTTM'] = date('Y-m-d H:i:s', time());
            $group[$optId]['SYS_CHG_DTTM'] = date('Y-m-d H:i:s', time());
            $group[$optId]['GUDS_HS_CODE'] = $attr['CR'];
            $group[$optId]['GUDS_HS_CODE2'] = $attr['HS'];
            $group[$optId]['GUDS_OPT_MIN_ORD_QTY'] = isset($attr['minBuyNum']) ? $attr['minBuyNum'] : 1;

            //发布类型和海关条码以及启用禁用。
            $group[$optId]['PUBLISH_TYPE'] = isset($data['publishType']) ? $data['publishType'] : 0;
            $group[$optId]['GUDS_HS_UPC'] = isset($attr['hsUpcCode']) ? $attr['hsUpcCode'] : '';
            $group[$optId]['IS_ENABLE'] = isset($attr['isEnable']) ? $attr['isEnable'] : 1;//1启用，0禁用
            //海关物流信息，[CODE-VALUE,CODE_VALUE,...]，Value 0否,1是,2未知;
            $group[$optId]['CUSTOMS_LOGISTICS'] = !empty($extension) ? $extension : array();

            //海关报关价格
            $group[$optId]['CUSTOMS_PRICE'] = isset($attr['customsPrice']) ? $attr['customsPrice'] : '0.0000';
        }

        return $group;
    }

    /**
     * 自编码生成
     * @deprecated 自编码废弃
     * @param string $catFlag 通用类目大类编号：A-Z
     * @param $brandId
     * @param $optId
     * @return string
     */
    private function buildCustomCode($catFlag, $brandId, $optId)
    {
        $brandId = sprintf('%04d', $brandId);
        $optId = sprintf("%'X-11s", $optId);
        return $catFlag . $brandId . $optId;
    }

    /**
     * SKU id生成
     * @param $mainGudsId
     * @param $key
     * @param $newOptId
     * @return string
     */
    private function buildOptionId($mainGudsId, $key, $newOptId = null)
    {
        //因为数组索引为 0开始，所以 +1；两位数的前面补 0，三位数直接串起来。
        $key = $key + 1;
        if (!empty($newOptId)){
            $maxOptId = substr($newOptId, strlen($mainGudsId));
            $key = $maxOptId + $key;
        }

        //不足两位的填 0 补充到2位数
        if ($key <= 99) {
            $key = sprintf('%02d', $key);
        }

        return  $mainGudsId . $key;
    }

    /**
     * 构建Option和OptionValue关系
     * @param $option
     * @return string
     */
    private function buildOptionMap($option)
    {
        $optionMap = '';
        //映射属性名和属性值，这里排序很重要，用于后续验证SKU属性和属性值组合是否会有重复。
        ksort($option,SORT_NUMERIC);
        foreach ($option as $optCode => $optVal) {
            //这里传过来的option一定要把 attributes 元素干掉，这里为了避免出错进行过滤
            if (isset($optVal['PAR_CODE']) && isset($optVal['CODE'])) {
                $optionMap .= $optVal['PAR_CODE'] . ':' . $optVal['CODE'] . ';';
            }
        }
        return trim($optionMap, ';');
    }

    /**
     * 海关和物流信息数据结构构建
     *
     * @param number $optId SKU ID
     * @param array $extension SKU的物流和海关信息属性列表
     * @param array $existParams
     * @return array|bool
     */
    private function buildExtension($optId, $extension, $existParams = [])
    {
        if (empty($extension) || !is_array($extension))   return  false;

        $skuExtra = $tbMsParams = [];
        $time = date("Y-m-d H:i:s", $_SERVER['REQUEST_TIME']);
        foreach ($extension as $code => $status) {
            //SKU自身数据构建，组成"CODE-状态值"数组，这种格式比JSON的好处是搜索方便。
            $skuExtra[] = "{$code}-{$status}";

            //派单系统使用的扩展属性表数据构建，每个属性就是一行记录，数据行数会非常多。
            $paramsLine = ['', $optId, $code, 2, $time, $_SESSION['m_loginname'], $status];

            //当$existParams 数据存在时表示在更新tb_ms_params表数据，添加主键ID进去用于Replace。
            if (!empty($existParams) && isset($existParams[$code])){
                $paramsLine[0] = $existParams[$code]['ID'];
            }

            //所有数据拼接成SQL的Value列表，用于一次性执行SQL。
            $tbMsParams[] = "('" . implode("','", $paramsLine) . "')";
        }

        return [$skuExtra, $tbMsParams];
    }

    /**
     * 根据给定的SKU列表，构建SKU的SQLValue值字符串。
     * @param $optionList
     * @return array [SKU值字符串数组, 扩展属性表值字符串数组]
     */
    private function buildSqlValues($optionList)
    {
        //组合并保存SKU的数据
        $skuValArr = $extParamsArr = [];
        foreach ($optionList as $optId => $opt){
            //所有SKU属性，转成SQL的VALUES部分，组成数组,海关扩展信息部分构成字符串逗号分隔放到一个字段。
            list($skuExtra, $tbMsParams) = $this->buildExtension($optId, $opt['CUSTOMS_LOGISTICS']);
            $opt['CUSTOMS_LOGISTICS'] = implode(',', $skuExtra);
            $skuValArr[] = "('" . implode("','", $opt) . "')";

            //组合关联扩展表的数据，所有SKU的所有扩展信息放到一起，一次性插入。
            $extParamsArr = $extParamsArr + $tbMsParams;
        }

        return [$skuValArr, $extParamsArr];
    }

    /**
     * 根据自编码读取SKU列表信息。
     *
     * @param array $optionCode 自编码
     * @param number $optId SKU id 排除自己
     * @return bool | array
     */
    public function getOptionsByOptCode($optionCode, $optId)
    {
        if (empty($optionCode)) return false;

        $sql = "SELECT
                    G.MAIN_GUDS_ID,
                    G.SLLR_ID,
                    G.CAT_CD,
                    G.GUDS_NM,
                    O.GUDS_OPT_ID,
                    O.GUDS_OPT_CODE,
                    O.GUDS_OPT_VAL_MPNG,
                    P.REAL_PRICE
                FROM
                    tb_ms_guds AS G
                    LEFT JOIN tb_ms_guds_opt AS O ON G.MAIN_GUDS_ID = O.GUDS_ID
                    LEFT JOIN tb_ms_guds_opt_price AS P ON O.GUDS_OPT_ID = P.GUDS_OPT_ID 
                WHERE
                    O.GUDS_OPT_ID = '{$optId}' OR O.GUDS_OPT_CODE = '{$optionCode}'
                GROUP BY G.MAIN_GUDS_ID;";

        $optionList = $this->query($sql);

        $res = [];
        foreach ($optionList as $opt) {
            $res[$opt['GUDS_OPT_ID']] = $opt;
        }

        return $res;
    }

    /**
     * 更新SKU信息
     * @param array $data 更新数据
     * @param number $mainGudsId 商品id
     * @param int $optionId SKU ID
     * @return mixed
     */
    public function updateOptions($data, $mainGudsId, $optionId)
    {
        //!empty($data['optionCode']) && $update['GUDS_OPT_CODE'] = $data['optionCode']; 自编码废弃
        !empty($data['PRICE']) && $update['GUDS_OPT_ORG_PRC'] = $data['PRICE'];
        !empty($data['CR']) && $update['GUDS_HS_CODE'] = $data['CR'];
        !empty($data['HS']) && $update['GUDS_HS_CODE2'] = $data['HS'];
        isset($data['LENGTH']) && $update['GUDS_OPT_LENGTH'] = intval($data['LENGTH']);
        isset($data['WIDTH']) && $update['GUDS_OPT_WIDTH'] = intval($data['WIDTH']);
        isset($data['HEIGHT']) && $update['GUDS_OPT_HEIGHT'] = intval($data['HEIGHT']);
        isset($data['WEIGHT']) && $update['GUDS_OPT_WEIGHT'] = intval($data['WEIGHT']);
        !empty($data['saleState']) && $update['GUDS_OPT_SALE_STAT_CD'] = $data['saleState'];
        !empty($data['minBuyNum']) && $update['GUDS_OPT_MIN_ORD_QTY'] = intval($data['minBuyNum']);

        //!important 当指定了一件代发价格时，要设定展示一件代发价格，这是为了兼容Backend系统和B5C页面展示价格
        if (!empty($data['realPrice'])) {
            $update['GUDS_OPT_BELOW_SALE_PRC'] = $data['realPrice'];
            $update['GUDS_OPT_BELOW_SALE_PRC_YN'] = 'Y';
        }
        //这里用isset是因为，排除 null 和 0 等，可能转为 false的情况。
        isset($data['isContainsBattery']) && $update['IS_CONTAINS_BATTERY'] = $data['isContainsBattery'];
        isset($data['isOnlyBattery']) && $update['IS_ONLY_BATTERY'] = $data['isOnlyBattery'];
        isset($data['isAneroidMarkup']) && $update['IS_ANEROID_MARKUP'] = $data['isAneroidMarkup'];
        isset($data['isBreakable']) && $update['IS_BREAKABLE'] = $data['isBreakable'];

        !empty($data['publishType']) && $update['PUBLISH_TYPE'] = $data['publishType'];
        !empty($data['hsUpcCode']) && $update['GUDS_HS_UPC'] = $data['hsUpcCode'];
        isset($data['isEnable']) && $update['IS_ENABLE'] = $data['isEnable'];
        isset($data['isEnable']) && $update['GUDS_OPT_USE_YN'] = $data['isEnable'] == 1 ? 'Y' : 'N';
        isset($data['UPC']) && $update['GUDS_OPT_UPC_ID'] = $data['UPC'];

        $time = date('Y-m-d H:i:s', time());
        $update['SYS_CHGR_ID'] = $_SESSION['userId'];
        $update['SYS_CHG_DTTM'] = $time;

        //组建扩展信息
        $paramsData = null;
        list($skuExtra, $tbMsParams) = $this->buildExtension($optionId, $data['extension'], $paramsData);
        !empty($skuExtra) && $update['CUSTOMS_LOGISTICS'] = implode(',', $skuExtra);

        //MySql特有的ON DUPLICATE KEY UPDATE 语法简化操作，批量插入同时主键或者唯一索引冲突的就会进行更新。
        $paramStr = implode(',', $tbMsParams);
        $paramSql = "INSERT INTO `tb_ms_params` VALUES {$paramStr} ON DUPLICATE KEY UPDATE `STATUS` = VALUES(`STATUS`)";
        $paramRes = !empty($tbMsParams) ? $this->execute($paramSql) : false;

        $where = "GUDS_ID={$mainGudsId} AND GUDS_OPT_ID={$optionId}";
        $res = $this->where($where)->save($update);

        if ((!empty($tbMsParams) && $paramRes === false) || $res === false){
            return false;
        }

        //echo $this->getLastSql();
        return $res + $paramRes;
    }

    /**
     * 解析SKU属性CODE映射字符串为数组形式，格式为：[NameCODE=>ValueCode,...]
     * 用于继续后续解析为明文的[属性名=>属性值数据]，用来页面展示SKU的属性列表。
     *
     * @param string $optionMap SKU的属性值，GUDS_OPT_VAL_MPNG
     * @return mixed 参数为Null或者空将会返回，空数组。
     */
    public function parseOptionMap($optionMap)
    {
        //SKU 属性名和属性值对拆解为数组。
        eval('$optionMap = ['.str_replace([':',';'],['=>',','], $optionMap).'];');
        return $optionMap;
    }

    /**
     * 解析SKU属性CODE码表，为属性名属性值明文字符串。
     *
     * @param array $optMap SKU属性CODE码表。
     * @param array $attributeData SPU的所有SKU属性和属性值数据表。
     *
     * @return array SKU属性名属性值字符串。
     */
    public function parseOptionText($optMap, $attributeData)
    {
        $optNameStr = $optValueStr = '';
        foreach ($optMap as $nameCode => $valueCode){
            $optNameStr .= $attributeData[$nameCode]['ALL_VAL'] . '<BR/>';
            $optValueStr .= $attributeData[$valueCode]['ALL_VAL'] . ';<BR/>';
        }

        return ['optNames' => $optNameStr,'optValues'=>$optValueStr];
    }

    /**
     * 添加SKU的时候验证是否存在相同的SKU。
     * 验证条件是：所有SKU属性名和属性值对，都有相同。
     *
     * @param number $mainGudsId 新添加的商品主ID
     * @param array $optionData 当前商品的所有SKU数据表。
     *
     * @return bool
     */
    public function checkOptionExist($mainGudsId,$optionData)
    {
        if (empty($optionData) || empty($optionData)){
            return false;
        }

        $newSkuMap = [];
        foreach ($optionData as $newOpt){
            if (is_numeric($newOpt['PAR_CODE']) && is_numeric($newOpt['CODE'])){
                $newSkuMap[$newOpt['PAR_CODE']] = $newOpt['CODE'];
            }
        }

        //组合SKU属性数组，为比较做准备
        $optList = $this->getGudsOptions(['mainGudsId' => $mainGudsId]);
        foreach ($optList as $opt){
            $optArr = $this->parseOptionMap($opt['GUDS_OPT_VAL_MPNG']);
            //数组相同，即SKU OPT_Name_CODE 和 OPT_VALUE_CODE都相同，只要有一个匹配的就返回。
            if ($newSkuMap == $optArr){
                return true;
            }
        }

        //匹配不到属性相同的返回 false，即没有存在同样的属性的SKU。
        return false;
    }
}