<?php

/**
 * 商品模块
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/7/25
 * Time: 17:32
 */
class GudsModel extends RelationModel
{
    protected $trueTableName = "tb_ms_guds";
    protected $gudsOptionsTable = 'tb_ms_guds_opt';
    protected $brandCateTable = 'tb_ms_sllr_cat';
    protected $commonCateTable = 'tb_ms_cmn_cat';
    protected $gudsChkTable = 'tb_ms_guds_chk';
    protected $commonCdTable = 'tb_ms_cmn_cd';

    protected $_map = [
        'gudsId' => 'GUDS_ID',
        'brndId' => 'BRND_ID',
        'brandId' => 'SLLR_ID',
        'gudsCode' => 'GUDS_CODE',
        'mainId' => 'MAIN_GUDS_ID',
        'catId' => 'CAT_CD',
        'gudsCat' => 'GUDS_CAT',
        'gudsName' => 'GUDS_NM',
        'gudsCnName' => 'GUDS_CNS_NM',
        'gudsSubName' => 'GUDS_VICE_NM',
        'gudsSubCnName' => 'GUDS_VICE_CNS_NM',
        'brandName' => 'BRND_NM',
        'unit' => 'VALUATION_UNIT',
        'lang' => 'LANGUAGE',
        'shelfLife' => 'SHELF_LIFE',
        'gudsOrgp' => 'GUDS_ORGP_CD',
        'priceType' => 'STD_XCHR_KIND_CD',
        'isShelflife' => 'IS_SHELF_LIFE',
        'saleStatus' => 'GUDS_SALE_STAT_CD',
        'chkStatus' => 'GUDS_REG_STAT_CD',
        'saleChannel' => 'SALE_CHANNEL',
        'productType' => 'GUDS_STAT_CD',
        'overYn' => 'OVER_YN',
        'isShelflife' => 'IS_SHELF_LIFE',
        'refundTaxRate' => 'RETURN_RATE',
        'isRefundTax' => 'GUDS_VAT_RFND_YN',
        'minBuyNum' => 'MIN_BUY_NUM',
        'maxBuyNum' => 'MAX_BUY_NUM',
        'expressCat' => 'BELONG_SEND_CAT',
        'expressType' => 'LOGISTICS_TYPE',
        'overseasTax' => 'ADDED_TAX',
        'PCS' => 'GUDS_DLVC_DESN_VAL_5',
        'productFlag' => 'GUDS_FLAG',
        'publishType' => 'PUBLISH_TYPE',
    ];

    #语言对应的数据字典
    static $_LANG_VALS = array(
        'cn' => 'N000920100',
        'kr' => 'N000920400',
        'en' => 'N000920200',
        'jp' => 'N000920300',
    );

    /**
     * 读取商品列表
     * @param array $cond 查询条件
     * @param array $options 分页条件
     * @return array
     */
    public function getGudsList($cond, $options){
        $field = '  g.MAIN_GUDS_ID as mainId,
                    g.SLLR_ID as brandId,
                    g.GUDS_CODE as gudsCode,
                    g.GUDS_ID as gudsId,
                    g.CAT_CD as catId,
                    g.GUDS_NM as gudsName,
                    g.GUDS_CNS_NM as gudsCnName,
                    g.GUDS_VICE_NM as gudsSubName,
                    g.GUDS_VICE_CNS_NM as gudsSubCnName,
                    g.BRND_NM as brandName,
                    g.LANGUAGE as lang,
                    g.VALUATION_UNIT as unit,
                    g.GUDS_REG_STAT_CD as chkStatus,
                    g.STD_XCHR_KIND_CD as priceType,
                    g.GUDS_FLAG as productFlag,
                    g.PUBLISH_TYPE as publishType,
                    MIN(go.GUDS_OPT_ORG_PRC) as price,
                    COUNT(go.GUDS_OPT_ID) as skuNum,
                    gc.CHK_CONTENT as remark';

        $subQuery = $this->table('tb_ms_guds g')
            ->field($field)
            ->join($this->gudsOptionsTable . ' go  on g.MAIN_GUDS_ID = go.GUDS_ID')
            ->join($this->gudsChkTable . ' gc on gc.MAIN_GUDS_ID = g.MAIN_GUDS_ID and gc.GUDS_ID = g.GUDS_ID')
            ->where($this->getQueryCond($cond))
            ->order(array('g.MAIN_GUDS_ID' => 'DESC'))
            ->group('go.GUDS_ID,g.LANGUAGE')
            ->limit($options['start'], $options['limit'])
            ->buildSql();

        if ($options['type'] == 'count') {
            $sql = 'SELECT COUNT(*) as num FROM (' . $subQuery . ') AS T';
            return $this->query($sql);
        }

        $result = $this->query($subQuery);
        return $result;
    }

    /**
     * 构造查询条件
     * @param $cond
     * @return string
     */
    public function getQueryCond($cond){
        $where = '1';
        !empty($cond['brandId']) && $where .= " AND g.SLLR_ID='{$cond['brandId']}'";
        //搜索条件
        switch ($cond['type']){
            case 'gudsName':
                !empty($cond['typeVal']) && $where .= " AND g.GUDS_CNS_NM LIKE '%{$cond['typeVal']}%'";
                !empty($cond['typeVal']) && $where .= " OR g.GUDS_NM LIKE '%{$cond['typeVal']}%'";
                break;
            case 'Upc':
                !empty($cond['typeVal']) && $where .= " AND FIND_IN_SET('{$cond['typeVal']}',go.GUDS_OPT_UPC_ID)";
                break;
            case 'mainId':
                !empty($cond['typeVal']) && $where .= " AND g.MAIN_GUDS_ID='{$cond['typeVal']}'";
                break;
            case 'gudsId':
                !empty($cond['typeVal']) && $where .= " AND g.GUDS_ID='{$cond['typeVal']}'";
                break;
            default://默认什么也不做
        }

        //处理语言条件
        if ($cond['lang']){
            $langSet = array_intersect(self::$_LANG_VALS, $cond['lang']);
            $languageList = implode("','", $langSet);
            $where .= " AND g.LANGUAGE IN ('{$languageList}')";
        }

        $reviewStatus = implode("','", $cond['status']);
        !empty($cond['status']) && $where .= " AND g.GUDS_REG_STAT_CD IN ('{$reviewStatus}')";
        if (isset($cond['publishType']) && $cond['publishType'] !== '')//发布类型，前后端发布
        {
            $where .= " AND g.PUBLISH_TYPE = {$cond['publishType']}";
        }

        //日期条件：
        $timeCond = ($cond['datetype'] == 'ud') ? 'g.updated_time' : 'g.SYS_REG_DTTM';
        !empty($cond['startDate']) && $where .= " AND {$timeCond} >= '{$cond['startDate']}'";
        !empty($cond['endDate']) && $where .= " AND  {$timeCond} <= '{$cond['endDate']}'";

        return $where;
    }

    /**
     * 获取商品数据
     * @param array $where 查询条件数组
     * @return mixed
     */
    public function getGudsData($where = array())
    {
        $fields = 'GUDS_ID,SLLR_ID,GUDS_CODE,MAIN_GUDS_ID,CAT_CD,GUDS_NM,GUDS_CNS_NM,GUDS_VICE_NM,GUDS_VICE_CNS_NM,
        BRND_NM,VALUATION_UNIT,LANGUAGE,SHELF_LIFE,GUDS_ORGP_CD,STD_XCHR_KIND_CD,IS_SHELF_LIFE,GUDS_SALE_STAT_CD,
        GUDS_REG_STAT_CD,SALE_CHANNEL,GUDS_STAT_CD,OVER_YN,IS_SHELF_LIFE,RETURN_RATE,GUDS_VAT_RFND_YN,MIN_BUY_NUM,
        MAX_BUY_NUM,BELONG_SEND_CAT,LOGISTICS_TYPE,ADDED_TAX,GUDS_DLVC_DESN_VAL_5,GUDS_FLAG,GUDS_REG_STAT_CD,PUBLISH_TYPE';

        $res =   $this->where($where)->field($fields)->select();
        foreach ($res as $key => $guds){
            $res[$key] = $this->parseFieldsMap($guds);
        }
        return $res;
    }

    /**
     * 根据ID读取同属一个MainGudsId的商品列表
     * @param $gudsId
     * @return mixed
     */
    public function getGudsListById($gudsId)
    {
        $fields = 'G1.GUDS_ID,G1.BRND_ID,G1.SLLR_ID,G1.GUDS_CODE,G1.MAIN_GUDS_ID,G1.GUDS_CAT,G1.CAT_CD,G1.GUDS_NM,
        G1.GUDS_CNS_NM,G1.GUDS_VICE_NM,G1.GUDS_VICE_CNS_NM,G1.BRND_NM,G1.VALUATION_UNIT,G1.LANGUAGE,G1.SHELF_LIFE,
        G1.GUDS_ORGP_CD,G1.STD_XCHR_KIND_CD,G1.IS_SHELF_LIFE,G1.GUDS_SALE_STAT_CD,G1.GUDS_REG_STAT_CD,G1.SALE_CHANNEL,
        G1.GUDS_STAT_CD,G1.OVER_YN,G1.IS_SHELF_LIFE,G1.RETURN_RATE,G1.GUDS_VAT_RFND_YN,G1.MIN_BUY_NUM,G1.MAX_BUY_NUM,
        G1.BELONG_SEND_CAT,G1.LOGISTICS_TYPE,G1.ADDED_TAX,G1.GUDS_DLVC_DESN_VAL_5,G1.GUDS_FLAG,G1.GUDS_REG_STAT_CD,
        G1.PUBLISH_TYPE';

        $res = $this->table($this->trueTableName . ' AS G1')
            ->join($this->trueTableName . ' AS G2 ON G1.MAIN_GUDS_ID=G2.MAIN_GUDS_ID')
            ->where("G2.GUDS_ID='{$gudsId}'")
            ->getField($fields);

        foreach ($res as $key => $guds){
            $res[$key] = $this->parseFieldsMap($guds);
        }

        return $res;
    }

    /**
     * 通过gudsId获取商品详情
     * @param number $GUDS_ID 商品id
     * @return  array
     */
    public function getGudsDetailByGudsId($GUDS_ID)
    {
        $where = array('GUDS_ID', $GUDS_ID);
        return $this->getGudsData($where);
    }

    /**
     * 通过MainIdAndSllrId获取商品详情
     * @param $MAIN_GUDS_ID 商品主Id
     * @param $SLLR_ID      品牌
     * @return mixed
     */
    public function getGudsDetailByMainIdAndSllrId($MAIN_GUDS_ID, $SLLR_ID)
    {
        $where = empty($MAIN_GUDS_ID) ? array() : array('MAIN_GUDS_ID' => array('eq', $MAIN_GUDS_ID));
        $where = empty($where) ? array() : (empty($SLLR_ID) ? $where : array_merge($where, array('SLLR_ID' => array('eq', $SLLR_ID))));
        return $this->getGudsData($where);
    }

    /**
     * 通过MAIN_GUDS_ID||/&& LANGUAGE 获取商品详情
     * @param $MAIN_GUDS_ID 主商品id
     * @param $LANGUAGE   语言
     * @return mixed
     */
    public function getGudsDetailByMgudsIdAndLang($MAIN_GUDS_ID, $LANGUAGE)
    {
        $where = empty($MAIN_GUDS_ID) ? array() : array('MAIN_GUDS_ID' => array('eq', $MAIN_GUDS_ID));
        $where = empty($where) ? array() : (empty($LANGUAGE) ? $where : array_merge($where, array('LANGUAGE' => array('eq', $LANGUAGE))));
        return $this->getGudsData($where);
    }

    /**
     * 通过GUDS_ID||/&& SLLR_ID 获取商品详情
     * @param $GUDS_ID  商品id
     * @param $SLLR_ID  品牌id
     * @return mixed
     */
    public function getGudsDetailByGudsIdAndSllrId($GUDS_ID, $SLLR_ID = null)
    {
        $where = empty($GUDS_ID) ? array() : array('GUDS_ID' => array('eq', $GUDS_ID));
        $where = empty($SLLR_ID) ? $where : array_merge($where, array('SLLR_ID' => array('eq', $SLLR_ID)));
        return $this->getGudsData($where);
    }

    /**
     * 添加商品信息
     * @param $data
     * @return array|bool
     */
    public function createGuds($data)
    {
        if (empty($data))   return  false;
        $this->startTrans();
        $selectSql = "select max(GUDS_ID) as maxId from " . $this->trueTableName . " for update";
        $result = $this->query($selectSql);
        //添加商品的其他语言内容时，MainGudsId是指定的，不是新生成的。
        $mainId = empty($data['mainId']) ? ($result[0]['maxId'] + 1) : $data['mainId'];
        $maxGudsId = $result[0]['maxId'] + 1;
        $i = 0;
        $valuesArr = $new = $commData = [];

        foreach ($data['langData'] as $key => $guds){
            if (empty($guds['gudsName']) && empty($guds['gudsSubName'])) continue; //缺少语言内容，跳过。

            //重复提交导致尝试添加已有的语言类型数据，直接跳过不处理，但是要验证SQL数据部分是否齐全。
            $checkExist = $this->where("MAIN_GUDS_ID={$mainId} AND LANGUAGE = '{$key}'")->find();
            if (!empty($checkExist)){
                $commData['tips'][$key] = L('HAS_EXIST');
                continue;
            }

            $gudsId = $maxGudsId + $i;
            $i = $i + 1;
            $new[$gudsId]['GUDS_ID'] = $gudsId;
            $new[$gudsId]['BRND_ID'] = $data['BRND_ID'];
            $new[$gudsId]['SLLR_ID'] = $data['brandId'];
            $new[$gudsId]['MAIN_GUDS_ID'] = $mainId;
            $new[$gudsId]['CAT_CD'] = '';//品牌类目将取消了，现在暂时留空
            $new[$gudsId]['GUDS_CAT'] = $data['cateId'];//换成通用类目
            $new[$gudsId]['GUDS_NM'] = addslashes($guds['gudsName']);
            $new[$gudsId]['GUDS_CNS_NM'] = addslashes($guds['gudsName']);
            $new[$gudsId]['GUDS_VICE_NM'] = addslashes($guds['gudsSubName']);
            $new[$gudsId]['GUDS_VICE_CNS_NM'] = addslashes($guds['gudsSubName']);
            $new[$gudsId]['BRND_NM'] = addslashes($data['brandName']);
            $new[$gudsId]['GUDS_PVDR_NM'] = addslashes($data['brandId']);
            $new[$gudsId]['GUDS_ORGP_CD'] = $data['originCountry'];
            $new[$gudsId]['GUDS_REG_STAT_CD'] = GudsChkModel::DRAFT;
            $new[$gudsId]['GUDS_SALE_STAT_CD'] = 'N000100200';
            $new[$gudsId]['GUDS_VAT_RFND_YN'] = !empty($data['isRefundTax']) ? $data['isRefundTax'] : 'N';
            $new[$gudsId]['GUDS_DLVC_DESN_VAL_5'] = !empty($data['PCS']) ? $data['PCS'] : 1;//装箱数
            $new[$gudsId]['GUDS_OPT_YN'] = 'Y';
            $new[$gudsId]['GUDS_XPSR_CNT_USE_YN'] = 'N';
            $new[$gudsId]['SYS_REGR_ID'] = $_SESSION['m_loginname'];
            $new[$gudsId]['SYS_REG_DTTM'] = date('Y-m-d H:i:s');
            $new[$gudsId]['SYS_CHGR_ID'] = $_SESSION['m_loginname'];
            $new[$gudsId]['SYS_REG_DTTM'] = date('Y-m-d H:i:s');
            $new[$gudsId]['GUDS_FLAG'] = !isset($data['productFlag']) ? null : $data['productFlag'];
            $new[$gudsId]['DELIVERY_WAREHOUSE'] = 'N000680100';
            $new[$gudsId]['VALUATION_UNIT'] = $data['unit'];
            $new[$gudsId]['MIN_BUY_NUM'] = !empty($data['minBuyNum'])? $data['minBuyNum']: 1;
            $new[$gudsId]['MAX_BUY_NUM'] = !empty($data['maxBuyNum'])? $data['maxBuyNum']: 9999;
            $new[$gudsId]['BELONG_SEND_CAT'] = !empty($data['expressCat'])? $data['expressCat']: "";
            $new[$gudsId]['LOGISTICS_TYPE'] = !empty($data['expressType'])? $data['expressType']: "";
            $new[$gudsId]['IS_NOPOSTAGE'] = 'N';
            $new[$gudsId]['LANGUAGE'] = $key;
            $new[$gudsId]['OVER_YN'] = 'N';
            $new[$gudsId]['PROCUREMENT_SOURCE'] = 'N001030200';
            $new[$gudsId]['STD_XCHR_KIND_CD'] = $data['currency'];
            $new[$gudsId]['SALE_CHANNEL'] = empty($data['saleChannel']) ? '' : implode(',', $data['saleChannel']);
            $new[$gudsId]['RETURN_RATE'] = isset($data['refundTaxRate']) ? $data['refundTaxRate'] :'';;
            $new[$gudsId]['ADDED_TAX'] = isset($data['overseasTax']) ? $data['overseasTax'] :'N001020100';;
            //FIXME 这里要改成跨境综合税
            $new[$gudsId]['OVERSEAS_RATE'] = isset($data['overseasTax']) ? $data['overseasTax'] :'N001020100';
            $new[$gudsId]['PUSH_STATUS'] = 'N000890100';
            $new[$gudsId]['PUSH_TIME'] = date('Y-m-d H:i:s') ;
            $new[$gudsId]['PUBLISH_TYPE'] = isset($data['publishType']) ? $data['publishType'] : 0;//是否前后端发布
            $new[$gudsId]['IS_SHELF_LIFE'] = empty($data['isLifetime']) ? 'N' : $data['isLifetime'];
            $commData['langData'][$key] = array('mainId' => $mainId, 'sllrId' => $data['brandId'], 'gudsId' => $gudsId);
            //$params['GUDS_STAT_CD'] = 'N001180100'; //废弃，使用仓库来判定
            $valuesArr[] = "('" . implode("','", $new[$gudsId]) . "')";
        }

        $fields = implode(',', array_keys(reset($new)));
        $valueStr = implode(',', $valuesArr);
        $dataSql = "INSERT INTO {$this->trueTableName} ({$fields}) VALUES {$valueStr};";
        $saveRes = !empty($valueStr) ? $this->execute($dataSql) : false;//执行数据保存
        $commData['mainId'] = $mainId;//臭屁的结构

        if ($saveRes==false){
            $this->rollback();
            if (empty($commData['tips'])){
                $commData['tips']['save'] = L('SYSTEM_ERROR');
            }
        }else {
            $this->commit();
            $commData['tips']['save'] = 'Add new language success!';
        }
        return $commData;
    }

    /**
     * 更新商品数据
     * @param array $data 待更新数据
     * @param int $GUDS_ID 商品SPU编号
     * @return mixed
     */
    public function updateGuds($data, $GUDS_ID)
    {
        if (empty($data)  || empty($GUDS_ID)) {
            return false;
        }

        !empty($data['CAT_CD']) && $update['CAT_CD'] = $data['CAT_CD'];//原品牌类目，将取消了
        !empty($data['GUDS_CAT']) && $update['GUDS_CAT'] = $data['GUDS_CAT'];//通用类目
        !empty($data['GUDS_NM']) && $update['GUDS_NM'] = $data['GUDS_NM'];//商品名称
        !empty($data['GUDS_CNS_NM']) && $update['GUDS_CNS_NM'] = $data['GUDS_CNS_NM'];//商品中文名称
        !empty($data['GUDS_VICE_NM']) && $update['GUDS_VICE_NM'] = $data['GUDS_VICE_NM'];//副标题
        !empty($data['GUDS_VICE_CNS_NM']) && $update['GUDS_VICE_CNS_NM'] = $data['GUDS_VICE_CNS_NM'];//中文副标题
        !empty($data['SLLR_ID']) && $update['SLLR_ID'] = $data['SLLR_ID'];
        !empty($data['BRND_NM']) && $update['BRND_NM'] = $data['BRND_NM'];//品牌名称
        !empty($data['GUDS_PVDR_NM']) && $update['GUDS_PVDR_NM'] = $data['GUDS_PVDR_NM'];//供货商名称
        !empty($data['GUDS_ORGP_CD']) && $update['GUDS_ORGP_CD'] = $data['GUDS_ORGP_CD'];//原产国
        !empty($data['GUDS_REG_STAT_CD']) && $update['GUDS_REG_STAT_CD'] = $data['GUDS_REG_STAT_CD'];//审核状态
        !empty($data['GUDS_SALE_STAT_CD']) && $update['GUDS_SALE_STAT_CD'] = $data['GUDS_SALE_STAT_CD'];//销售状态
        !empty($data['GUDS_AUTH_YN']) && $update['GUDS_AUTH_YN'] = $data['GUDS_AUTH_YN'];//是否有品牌授权
        !empty($data['GUDS_VAT_RFND_YN']) && $update['GUDS_VAT_RFND_YN'] = $data['GUDS_VAT_RFND_YN'];//是否退税

        !empty($data['GUDS_DLVC_DESN_VAL_5']) && $update['GUDS_DLVC_DESN_VAL_5'] = $data['GUDS_DLVC_DESN_VAL_5'];//装箱数据
        !empty($data['GUDS_OPT_YN']) && $update['GUDS_OPT_YN'] = $data['GUDS_OPT_YN'];//SKU是否启用，这里移植到SKU了，后续取消
        !empty($data['GUDS_XPSR_CNT_USE_YN']) && $update['GUDS_XPSR_CNT_USE_YN'] = $data['GUDS_XPSR_CNT_USE_YN'];//是否显示库存

        !empty($data['GUDS_SALE_QTY']) && $update['GUDS_SALE_QTY'] = $data['GUDS_SALE_QTY'];//销量
        !empty($data['SYS_CHGR_ID']) && $update['SYS_CHGR_ID'] = $data['SYS_CHGR_ID'];//修改者名
        !empty($data['SYS_CHG_DTTM']) && $update['SYS_CHG_DTTM'] = $data['SYS_CHG_DTTM'];//修改时间
        !is_null($data['GUDS_FLAG']) && $update['GUDS_FLAG'] = $data['GUDS_FLAG'];//商品标签
        !empty($data['GUDS_DLPY_YN']) && $update['GUDS_DLPY_YN'] = $data['GUDS_DLPY_YN'];//是否一件代发
        !empty($data['DELIVERY_WAREHOUSE']) && $update['DELIVERY_WAREHOUSE'] = $data['DELIVERY_WAREHOUSE'];//发货仓库
        !empty($data['VALUATION_UNIT']) && $update['VALUATION_UNIT'] = $data['VALUATION_UNIT'];//装箱单位
        !empty($data['MIN_BUY_NUM']) && $update['MIN_BUY_NUM'] = $data['MIN_BUY_NUM'];//最小购买数
        !empty($data['MAX_BUY_NUM']) && $update['MAX_BUY_NUM'] = $data['MAX_BUY_NUM'];//最大购买数
        !empty($data['BELONG_SEND_CAT']) && $update['BELONG_SEND_CAT'] = $data['BELONG_SEND_CAT'];
        !empty($data['LOGISTICS_TYPE']) && $update['LOGISTICS_TYPE'] = $data['LOGISTICS_TYPE'];
        !empty($data['LOGUSTICS_FEE']) && $update['LOGUSTICS_FEE'] = $data['LOGUSTICS_FEE'];
        //!empty($data['IS_NOPOSTAGE']) && $update['IS_NOPOSTAGE'] = $data['IS_NOPOSTAGE'];

        !empty($data['OVER_YN']) && $update['OVER_YN'] = $data['OVER_YN'];
        !empty($data['PROCUREMENT_SOURCE']) && $update['PROCUREMENT_SOURCE'] = $data['PROCUREMENT_SOURCE'];
        !empty($data['SHELF_LIFE']) && $update['SHELF_LIFE'] = $data['SHELF_LIFE'];
        !empty($data['STD_XCHR_KIND_CD']) && $update['STD_XCHR_KIND_CD'] = $data['STD_XCHR_KIND_CD'];
        !empty($data['SALE_CHANNEL']) && $update['SALE_CHANNEL'] = $data['SALE_CHANNEL'];
        !empty($data['RETURN_RATE']) && $update['RETURN_RATE'] = $data['RETURN_RATE'];
        !empty($data['ADDED_TAX']) && $update['ADDED_TAX'] = $data['ADDED_TAX'];
        !empty($data['OVERSEAS_RATE']) && $update['OVERSEAS_RATE'] = $data['OVERSEAS_RATE'];
        !empty($data['PUSH_STATUS']) && $update['PUSH_STATUS'] = $data['PUSH_STATUS'];
        !empty($data['PUSH_TIME']) && $update['PUSH_TIME'] = $data['PUSH_TIME'];
        isset($data['PUBLISH_TYPE']) && $update['PUBLISH_TYPE'] = $data['PUBLISH_TYPE'];
        !empty($data['IS_SHELF_LIFE'])&& $update['IS_SHELF_LIFE'] = $data['IS_SHELF_LIFE'];
        $update['updated_time'] = date('Y-m-d H:i:s');

        return $this->where("GUDS_ID={$GUDS_ID} ")->save($update);
    }

    /**
     * 同步修改商品状态
     * @param $MAIN_GUDS_ID
     * @param $GUDS_REG_STAT_CD
     */
    public function updateGudsStatus($MAIN_GUDS_ID, $GUDS_REG_STAT_CD)
    {
        //, 'GUDS_ID' => $GUDS_ID 取消设定单个商品状态，设置所有MainGudsId的商品
        $this->where(array('MAIN_GUDS_ID' => $MAIN_GUDS_ID))
            ->save(array('GUDS_REG_STAT_CD' => $GUDS_REG_STAT_CD));
    }

    /**处理商品品牌分类类目层
     * @param $CD
     * @return array | bool false 如果指定cd是空，返回false
     */
    public function dealWithCateLever($CD)
    {
        if (empty($CD)) return false;

        $level1 = substr($CD, 0,3);
        $level2 = substr($CD, 3,3);
        $level3 = substr($CD, 6,4);

        $catL1 = $catL2 = $catL3 = '';
        $catL1 = $level1 . '0000000';
        if ($level1 && $level2 == '000' && $level3 == '0000'){
            $catL2 = '';
            $catL3 = '';
        } elseif ($level1 && $level2 != '000' && $level3 == '0000'){
            $catL2 = $level1 . $level2 . '0000';
            $catL3 = '';
        } elseif($level1 && $level2 != '000' && $level3 != '0000'){
            $catL2 = $level1 . $level2 . '0000';
            $catL3 = $CD;
        }

        return array('catLev1' => $catL1, 'catLev2' => $catL2, 'catLev3' =>$catL3);
    }
    
    /**
     * 按照品牌和mainGudsId 更新商品信息
     * @param array $update 要更新的新属性值数组
     * @param number $mainGudsId mainGudsId
     * @return bool
     */
    public function updateGudsByMainGudsId($update, $mainGudsId)
    {
        if (empty($update)){
            return false;
        }
        
        return $this->where("MAIN_GUDS_ID='{$mainGudsId}' ")->save($update);
    }

    public function getProductAllInfo($mainGudsId)
    {
        $sql = "
        SELECT
          G.MAIN_GUDS_ID AS mainGudsId,
          MD5(CONCAT( G.SLLR_ID, G.GUDS_ID )) AS DOCID,
          G.PUBLISH_TYPE AS publishType,
          G.SLLR_ID AS sllrId,
          G.GUDS_ID AS gudsId,
          G.STD_XCHR_KIND_CD AS xchrKindCd,
          G.GUDS_SALE_STAT_CD AS SaleStatus,
          G.GUDS_NM AS Title,
          opt_tb.GUDS_OPT_SALE_PRC AS price,
          CONCAT(MIN(opt_price.REAL_PRICE),\"-\", MAX(opt_price.REAL_PRICE)) AS PriceRange,
          G.IS_NOPOSTAGE AS postageYN,
          group_concat(DISTINCT cmn_cd.ETC2) as gudsStatCd,
          brand_tb.BRND_STR_NM AS Brand,
          cmn_cat.CAT_NM_PATH AS Category,
          brand_tb.BRND_ORGP_CD AS Source,
          cmn_cat.ALIAS AS CategoryKeyword,
          img_tb.GUDS_IMG_CDN_ADDR AS Picture,
          G.GUDS_FLAG gudsflag,
          brand_tb.BRND_AUTH_YN AS brndauthYN,
          group_concat(DISTINCT cmn_cd.ETC) AS deliveryMethod,
          opt_tb.GUDS_OPT_MIN_ORD_QTY AS minorderNum,
          G.BELONG_SEND_CAT AS Belongsendcat,
          opt_tb.GUDS_OPT_BELOW_SALE_PRC_YN AS onesaleYN,
          group_concat(DISTINCT opt_price.WAREHOUSE_CODE) AS dlvwarehouse,
          opt_price.REAL_PRICE AS onesalePrice,
          MIN(opt_price.REAL_PRICE) AS minPrice,
          MAX(opt_price.REAL_PRICE) AS maxPrice,
          group_concat( tag_tb.GUDS_TAG_NM ) AS Tags,
          G.SALE_CHANNEL AS ItemSaleChannel,
          brand_tb.SALE_CHANNEL AS BrndSaleChannel,
          UNIX_TIMESTAMP( G.updated_time ) AS UpdateTime,
          UNIX_TIMESTAMP( G.SYS_REG_DTTM ) AS CreateTime,
          G.GUDS_ORGP_CD AS ProduceSource,
          G.LANGUAGE AS language1,
          G.GAPP_PUSH_TIME AS GappPushTime,
          G.GUDS_REG_STAT_CD AS verfication
        FROM
          `tb_ms_guds` AS  G
          LEFT JOIN `tb_ms_guds_img` img_tb ON G.SLLR_ID = img_tb.SLLR_ID AND G.GUDS_ID = img_tb.GUDS_ID
          LEFT JOIN `tb_ms_guds_tag` tag_tb ON G.GUDS_ID = tag_tb.GUDS_ID
          LEFT JOIN `tb_ms_brnd_str` brand_tb ON G.SLLR_ID = brand_tb.SLLR_ID
          LEFT JOIN `tb_ms_guds_opt` opt_tb ON G.SLLR_ID = opt_tb.SLLR_ID AND G.MAIN_GUDS_ID = opt_tb.GUDS_ID
          LEFT JOIN `tb_ms_sllr_cat` cat_tb ON G.SLLR_ID = cat_tb.SLLR_ID AND G.CAT_CD = cat_tb.CAT_CD
          LEFT JOIN `tb_ms_cmn_cat` cmn_cat ON cat_tb.COMM_CAT_CD = cmn_cat.CAT_CD
          LEFT JOIN `tb_ms_guds_opt_price` opt_price ON G.MAIN_GUDS_ID = opt_price.MAIN_GUDS_ID
          LEFT JOIN `tb_ms_cmn_cd` cmn_cd ON opt_price.WAREHOUSE_CODE = cmn_cd.CD
        WHERE
          G.MAIN_GUDS_ID = '{$mainGudsId}'
        GROUP BY
          G.GUDS_ID,
          G.SLLR_ID";
        return $this->query($sql);
    }

}