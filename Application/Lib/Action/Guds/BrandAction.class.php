<?php

/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/7/25
 * Time: 13:51
 */
class BrandAction extends BaseAction
{
    static $lanArr = array('cn', 'kr', 'en', 'jp');
    /**
     * @var DictionaryModel
     */
    public $Dictionary;

    public function _initialize()
    {
        parent::_initialize();
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
        $this->Dictionary = D('Dictionary');
    }

    public function index()
    {
        $this->display('brandListMain');
    }

    public function create()
    {
        $this->display('addBrand');
    }

    /**
     * 显示品牌列表
     * @see http://wiki.b5msoft.com/index.php/guds/brand/showList
     * @param int $limit
     * @return array
     */
    public function showBrandList($limit = 0)
    {
        $isAjax = I('get.isAjax', 0);
        $limit = empty($limit) ? I('get.limit', 0) : $limit;
        $data = array();
        $Brand = new BrandModel();
        $brandData = (array)$Brand->getBrandList(array(), $limit);
        foreach ($brandData as $val) {
            $data[$val['brandId']] = $val;
        }
        if ($isAjax == 0) {
            return $data;
        }
        $result = array('code' => 2000, 'msg' => 'success', 'data' => $data);
        $this->jsonOut($result);
    }

    /**
     * 获取品牌所有列表
     */
    public function getBrandList()
    {
        $Brand = new BrandModel();
        $brandData = (array)$Brand->getBrandList(array(), 0);
        $result = array('code' => 2000, 'msg' => 'success', 'data' => $brandData);
        $this->jsonOut($result);
    }

    /**
     * 显示品牌类目列表
     * @deprecated TODO 已经废弃 不在使用了， 将移除掉。
     * @see http://wiki.b5msoft.com/index.php/guds/brand/showBrandCateList
     * @param int $sllrId
     * @return array
     */
    public function showBrandCateList($sllrId = 0)
    {
        $this->jsonOut(['code'=> 500, 'msg' => 'Api has been canceled !','data' => null]);
        $data = array();
        $BrandCate = D('@Model/Guds/BrandCate');
        $MultiCode = D('@Model/Guds/MultiCode');
        $brandId = empty(I('get.brandId', 0)) ? $sllrId : I('get.brandId', 0,'urldecode');
        $isAjax = I('get.isAjax', 0);
        $cateData = (array)$BrandCate->getBrandCateList($brandId);
        foreach ($cateData as $val) {
            $valData = $MultiCode->getBrandCateDataBySllrIdAndCode($val['brandId'], $val['catId'], 'ALL_VAL');
            $val['allVal'] = empty($valData[0]['ALL_VAL']) ? '' : $valData[0]['ALL_VAL'];
            $data['list'][$val['catId']] = $val;

            $cd = $val['catId'];
            $level1 = substr($cd, 0,3);
            $level2 = substr($cd, 3,3);
            $level3 = substr($cd, 6,4);
            $keyL1 = $level1 . '0000000';
            if ($level1 && $level2 == '000' && $level3 == '0000'){
                $data['cateStru'][$keyL1]['val'] = $val;
            } elseif ($level1 && $level2 != '000' && $level3 == '0000'){
                $data['cateStru'][$keyL1]['sec'][$cd]['val'] = $val;
            } elseif($level1 && $level2 != '000' && $level3 != '0000'){
                $keyL2 = $level1 . $level2 . '0000';
                $data['cateStru'][$keyL1]['sec'][$keyL2]['thr'][$cd]['val'] = $val;
            }
        }
        if ($isAjax == 0) {
            return $data;
        }
        $result = array('code' => 2000, 'msg' => 'success', "data" => $data);
        $this->jsonOut($result);
    }

    /**
     * 品牌搜索列表
     */
    public function showList()
    {
        $getData = file_get_contents('php://input');
        $params = json_decode($getData, true);
        $authType = $this->Dictionary->getAuthType('CD', 'CD_VAL');
        $brandStatus = $this->Dictionary->getBrandStatus('CD', 'CD_VAL');
        $page_num = empty($params['pageNum']) ? C('PAGE_NUM') : $params['pageNum'];

        if (!empty($params['dateVal'])) {
            list($startDate, $endDate) = explode(' - ', $params['dateVal']);//注意这里 连接线 前后的空格
            $params['startDate'] = date('Y-m-d H:i:s', strtotime($startDate)) ;//开始
            $params['endDate'] = date('Y-m-d H:i:s', strtotime($endDate) + 24 * 3600 - 1 );
        }
        $Brand = new BrandModel();
        $condition = $Brand->searchCondition($params);
        $count = $Brand->getTotalCount($condition);

        $page = empty($params['page']) ? 1 : $params['page'];
        $firstRow = ($page - 1) * $page_num;
        $totalPage = ceil($count[0]['num'] / $page_num);
        $option = ['offset' => $firstRow, 'limit' => $page_num,'orderBy' => 'SYS_REG_DTTM DESC'];
        $brandListData = $Brand->searchBrandList($condition, $option);
        #echo $Brand->getLastSql();die;
        foreach ($brandListData as $key => $val) {
            $brandListData[$key]['brandStatusName'] = $brandStatus[$val['brandStatus']];
            $brandListData[$key]['authTypeName'] = $authType[$val['authType']];
        }

        $result = array(
            'code' => 2000,
            'msg' => 'success',
            'data' => array(
                'pageNum' => $page_num,
                'page' => $page,
                'totalPage' => $totalPage,
                'totalNum' => $count,
                'list' => $brandListData,
                'authType' => $authType,
                'brandStatus' => $brandStatus)
        );
        $this->jsonOut($result);
    }

    /**
     * 品牌添加页面
     * @see http://wiki.b5msoft.com/index.php/guds/brand/addPage
     */
    public function addPage()
    {
        $categoryModel = new CategoryModel();
        $cateDate = $categoryModel->getCateGoryByLevel(1,0,50, 'Y');
        $dict = $this->Dictionary->getDictByType([
            DictionaryModel::GUDS_SALE_CHANNEL_PREFIX,
            DictionaryModel::GUDS_AUTH_TYPE_PREFIX,
            DictionaryModel::GUDS_BRAND_COUNTRY_PREFIX,
            DictionaryModel::GUDS_BANK_ACOUNT_PREFIX,
            DictionaryModel::GUDS_COMPANY_TYPE_PREFIX,
            DictionaryModel::GUDS_BRAND_STATUS_PREFIX,
        ],true);

        $result = array(
            'code' => 2000,
            'msg' => 'success',
            'data' => array(
                'cateData' => $cateDate,
                'saleChannelData' => $dict[DictionaryModel::GUDS_SALE_CHANNEL_PREFIX],
                'authTypeData' => $dict[DictionaryModel::GUDS_AUTH_TYPE_PREFIX],
                'brandCountryData' => $dict[DictionaryModel::GUDS_BRAND_COUNTRY_PREFIX],
                'bankAcountData' => $dict[DictionaryModel::GUDS_BANK_ACOUNT_PREFIX],
                'companyTypeData' => $dict[DictionaryModel::GUDS_COMPANY_TYPE_PREFIX],
                'brandStatusData' => $dict[DictionaryModel::GUDS_BRAND_STATUS_PREFIX]
            ));
        $this->jsonOut($result);
    }

    /**
     * 品牌保存接口
     * @see http://wiki.b5msoft.com/index.php/guds/brand/doAdd
     *
     */
    public function doAdd()
    {
        $Brand = D('@Model/Guds/Brand');
        $BrandSllr = D('@Model/Guds/BrandSllr');
        $BrandSllrAddr = D('@Model/Guds/BrandSllrAddr');
        $BrandStrRepCat = D('@Model/Guds/BrandStrRepCat');
        $BrandImg = D('@Model/Guds/BrandImg');
        $brandParams = $sllrParams = $sllrAddrParams = $catParams = $videoParams = $imgParams = array();

        $getData = file_get_contents('php://input');
        $params = json_decode($getData, true);
        $params['sllrId'] = trim($params['sllrId']);
        if (empty($params['sllrId'])) {
            $result = array('code' => '40000100', 'msg' => '参数不能为空', 'data' => null);
            $this->jsonOut($result);
        }
        if (!preg_match('/((?!^[0-9]*$)(?!^[a-zA-Z]*$)^([a-zA-Z0-9]{2,}))|([[a-zA-Z]{2,})$/', $params['sllrId'])) {
            $result = array('code' => '40000105', 'msg' => 'sllrId只能是字母或字母和数字组合', 'data' => null);
            $this->jsonOut($result);
        }
        $result = $BrandSllr->getSllrIdIsExist($params['sllrId']);
        if (!empty($result)) {
            $result = array('code' => '40000102', 'msg' => '品牌名已经存在！', 'data' => null);
            $this->jsonOut($result);
        }
        if (!empty($params['BZOP_CONM'])) {
            $result = $BrandSllr->getCompanyNameIsExist($params['companyName']);
            if (!empty($result)) {
                $result = array('code' => '40000103', 'msg' => '品牌公司名称已经存在！', 'data' => null);
                $this->jsonOut($result);
            }
        }
        //------------------------------------品牌sllr信息保存------------------------------------------------
        $sllrParams['SLLR_ID'] = $params['sllrId'];
        $sllrParams['SLLR_PWD'] = md5('123231312');
        $sllrParams['BZOP_CONM'] = $params['companyName'];
        $sllrParams['BZOP_REPR_NM'] = $params['bzNm'];
        $sllrParams['BZOP_REP_TEL_NO'] = $params['bzopTelNo'];
        $sllrParams['CRN'] = $params['crn'];
        $sllrParams['CRP_REG_NO'] = $params['cRegNo'];
        $sllrParams['BZTP_NM'] = $params['bztNm'];
        $sllrParams['ITEM_NM'] = $params['itNm'];
        $sllrParams['COMM_RTL_ANC_NO'] = $params['commRtlNo'];
        $sllrParams['CALP_DPST_BANK_CD'] = 'N000030100';/*$params['calpDpstBankCd']*/;
        $sllrParams['CALP_DPSR_NM'] = $params['calpDpsrNm'];
        $sllrParams['CALP_ACNT_NO'] = $params['calpAcntNo'];
        $sllrParams['BZOP_DIV_CD'] = 'N000050100';/*$params['bzopDivCd']*/;
        $result = $BrandSllr->addSllrData($sllrParams);
        #echo $BrandSllr->getLastSql();die;
        if ($result === false) {
            $result = array('code' => '40000110', 'msg' => '品牌管理人添加失败', 'data' => null);
            $this->jsonOut($result);
        }
        //------------------------------------品牌信息保存------------------------------------------------
        $brandParams['SLLR_ID'] = $params['sllrId'];
        $brandParams['BRND_STR_NM'] = $params['brandName'];
        $brandParams['BRND_STR_KR_NM'] = $params['brandKrName'];
        $brandParams['BRND_STR_JPA_NM'] = $params['brandJpName'];
        $brandParams['BRND_STR_ENG_NM'] = $params['brandEnName'];
        $brandParams['BRND_STR_STAT_CD'] = 'N000040100';
        $brandParams['SALE_CHANNEL'] = !empty($params['saleChannel']) ? implode(',', $params['saleChannel']) : '';
        $brandParams['BRND_INTD_CONT'] = $params['brandContent'];
        $brandParams['VEST_WAY'] = $params['vestWay'];
        $brandParams['BRND_ORGP_CD'] = $params['brandOrgCd'];
        $brandParams['BRND_STR_OP_OPR_NM'] = $params['brandOpNm'];
        $brandParams['BRND_STR_OP_OPR_CMP_TEL_NO'] = $params['brandCmpTelNo'];
        $brandParams['BRND_STR_OP_OPR_CP_NO'] = $params['brandOprCpNo'];
        $brandParams['BRND_STR_OP_OPR_EML'] = 'gshopper@gshopper.com'/*$params['brandOpOprEml']*/;
        $brandParams['BRND_STR_BACT_OPR_NM'] = $params['brandBactOprNm'];
        $brandParams['BRND_STR_BACT_OPR_CMP_TEL_NO'] = $params['brandBactOprCmpTelNo'];
        $brandParams['BRND_STR_BACT_OPR_CP_NO'] = $params['brandBactOprCpNo'];
        $brandParams['BRND_STR_BACT_OPR_EML'] = $params['brandBactOprEml'];
        $result = $Brand->addBrandData($brandParams);
        if ($result === false) {
            $result = array('code' => '40000111', 'msg' => '品牌信息添加失败', 'data' => null);
            $this->jsonOut($result);
        }
        //------------------------------------品牌sllr地址信息保存------------------------------------------------
        $sllrAddrParams['SLLR_ID'] = $params['sllrId'];
        $sllrAddrParams['SLLR_ZPNO'] = '021000';
        $sllrAddrParams['SLLR_ADDR'] = $params['sllrAddr'];
        $sllrAddrParams['SLLR_DTL_ADDR'] = $params['sllrDtlAddr'];
        $sllrAddrParams['SLLR_ADDR_DIV_CD'] = 1;
        $result = $BrandSllrAddr->addSllrAddrData($sllrAddrParams);
        /*if ($result === false) {
            $result = array('code' => '40000111', 'msg' => '公司地址信息添加失败', 'data' => null);
            $this->jsonOut($result);
        }*/
        //------------------------------------品牌sllr分类信息保存------------------------------------------------
        $catParams['SLLR_ID'] = $params['sllrId'];
        foreach ($params['catList'] as $val) {
            if (empty($val)) {
                continue;
            }
            $catParams['CAT_CD'] = $val;
            $BrandStrRepCat->addBrandStrRepCatData($catParams);
        }
        //------------------------------------品牌视频信息保存------------------------------------------------
        /*$videoParams['SLLR_ID'] = $params['sllrId'];
        foreach ($params['videoList'] as $val) {
            if (empty($val['videoCdnAddr'])) {
                continue;
            }
            $videoParams['BRND_STR_VIDEO_TYPE'] = $val['videoType'];
            $videoParams['BRND_STR_VIDEO_STAT_CD'] = $val['videoCd'];
            $videoParams['BRND_STR_VIDEO_ORGT_FILE_NM'] = $val['videoOrgtFileNm'];
            $videoParams['BRND_STR_VIDEO_CDN_ADDR'] = $val['videoCdnAddr'];
            $BrandVideo->addBrandVideoData($videoParams);
        }*/
        //------------------------------------品牌图片信息保存------------------------------------------------
        $imgParams['SLLR_ID'] = $params['sllrId'];
        foreach ($params['imgList'] as $val) {
            if (empty($val['imgContent']['cdnAddr'])) {
                continue;
            }
            $imgParams['BRND_STR_IMG_CD'] = $val['brandImgCd'];
            $imgParams['BRND_STR_IMG_STAT_CD'] = $val['brandImgStatCd'];
            $imgParams['BRND_STR_IMG_ORGT_FILE_NM'] = $val['imgContent']['orgtName'];
            $imgParams['BRND_STR_IMG_SYS_FILE_NM'] = $val['imgContent']['newName'];
            $imgParams['BRND_STR_IMG_CDN_ADDR'] = $val['imgContent']['cdnAddr'];
            $BrandImg->addBrandImgData($imgParams);
        }
        $result = array('code' => 2000, 'msg' => 'success', 'data' => true);
        $this->jsonOut($result);

    }

    /**
     * 显示品牌数据
     * @see http://wiki.b5msoft.com/index.php/guds/brand/showBrandData
     */
    public function showBrandData()
    {
        $B5caiCate = D('@Model/Guds/B5caiCate');
        $Brand = D('@Model/Guds/Brand');
        $BrandSllr = D('@Model/Guds/BrandSllr');
        $BrandSllrAddr = D('@Model/Guds/BrandSllrAddr');
        $BrandStrRepCat = D('@Model/Guds/BrandStrRepCat');
        $BrandImg = D('@Model/Guds/BrandImg');
        $sllrId = I('get.brandId','','urldecode');
        $brandData = $Brand->getBrand($sllrId);
        #echo $sllrId,$Brand->getLastSql();die;
        $sllrData = $BrandSllr->getSllrIdDataBySllrId($sllrId);
        $sllrAddrData = $BrandSllrAddr->getSllrAddrDataBySllrId($sllrId);
        $brandStrRepCateData = $BrandStrRepCat->getBrandStrRepCatBySllrId($sllrId);
        $brandImgData = $BrandImg->getBrandImgBySllrId($sllrId);
        $b5caiCateData = $B5caiCate->getB5caiCateList(1, 0);
        $saleChannelData = $this->Dictionary->getSaleChannel('CD', 'CD_VAL');
        $authTypeData = $this->Dictionary->getAuthType('CD', 'CD_VAL');
        $brandCountryData = $this->Dictionary->getBrandCountry('CD', 'CD_VAL');
        $bankAcountData = $this->Dictionary->getBankAcount('CD', 'CD_VAL');
        $companyTypeData = $this->Dictionary->getCompanyType('CD', 'CD_VAL');
        $brandStatusData = $this->Dictionary->getBrandStatus('CD', 'CD_VAL');
        $result = array(
            'code' => 2000,
            'msg' => 'success',
            'data' => array(
                'cateData' => $b5caiCateData,
                'saleChannelData' => $saleChannelData,
                'authTypeData' => $authTypeData,
                'brandCountryData' => $brandCountryData,
                'bankAcountData' => $bankAcountData,
                'companyTypeData' => $companyTypeData,
                'brandStatusData' => $brandStatusData,
                'basicData' => array(
                    'sllrData' => $sllrData,
                    'SllrAddrData' => $sllrAddrData,
                    'brandStrRepCateData' => $brandStrRepCateData,
                    'brandImgData' => $brandImgData,
                    'brandData' => $brandData,
                )
            ));
        $this->jsonOut($result);
    }

    /**
     * 更新商品品牌数据
     * @see  http://wiki.b5msoft.com/index.php/guds/brand/updateBrandData
     */
    public function updateBrandData()
    {
        $Brand = D('@Model/Guds/Brand');
        $BrandSllr = D('@Model/Guds/BrandSllr');
        $BrandSllrAddr = D('@Model/Guds/BrandSllrAddr');
        $BrandStrRepCat = D('@Model/Guds/BrandStrRepCat');
        $BrandImg = D('@Model/Guds/BrandImg');
        $brandParams = $sllrParams = $sllrAddrParams = $catParams = $videoParams = $imgParams = array();
        $getData = file_get_contents('php://input');
        $params = json_decode($getData, true);
        if (empty($params['sllrId'])) {
            $result = array('code' => '40000100', 'msg' => '参数不正确', 'data' => null);
            $this->jsonOut($result);
        }
        if (!empty($params['companyName']) && $params['isChange'] == 1) {
            $result = $BrandSllr->getCompanyNameIsExist($params['BZOP_CONM']);
            if (!empty($result)) {
                $result = array('code' => '40000103', 'msg' => '品牌公司名称已经存在！', 'data' => null);
                $this->jsonOut($result);
            }
        }
        //------------------------------------品牌sllr信息保存------------------------------------------------
        $sllrParams['SLLR_ID'] = $params['sllrId'];
        $sllrParams['BZOP_CONM'] = $params['companyName'];
        $sllrParams['BZOP_REPR_NM'] = $params['bzNm'];
        $sllrParams['BZOP_REP_TEL_NO'] = $params['bzopTelNo'];
        $sllrParams['CRN'] = $params['crn'];
        $sllrParams['CRP_REG_NO'] = $params['cRegNo'];
        $sllrParams['BZTP_NM'] = $params['bztNm'];
        $sllrParams['ITEM_NM'] = $params['itNm'];
        $sllrParams['COMM_RTL_ANC_NO'] = $params['commRtlNo'];
        $sllrParams['CALP_DPST_BANK_CD'] = $params['calpDpstBankCd'];
        $sllrParams['CALP_DPSR_NM'] = $params['calpDpsrNm'];
        $sllrParams['CALP_ACNT_NO'] = $params['calpAcntNo'];
        $sllrParams['BZOP_DIV_CD'] = $params['bzopDivCd'];
        $result = $BrandSllr->updataSllrData($sllrParams);
        if ($result === false) {
            $result = array('code' => '40000110', 'msg' => '品牌管理人更新失败', 'data' => null);
            $this->jsonOut($result);
        }
        //------------------------------------品牌信息保存------------------------------------------------
        $brandParams['SLLR_ID'] = $params['sllrId'];
        $brandParams['BRND_STR_NM'] = $params['brandName'];
        $brandParams['BRND_STR_KR_NM'] = $params['brandKrName'];
        $brandParams['BRND_STR_JPA_NM'] = $params['brandJpName'];
        $brandParams['BRND_STR_ENG_NM'] = $params['brandEnName'];
        $brandParams['BRND_STR_STAT_CD'] = $params['brandStatus'];
        $brandParams['SALE_CHANNEL'] = !empty($params['saleChannel']) ? implode(',', $params['saleChannel']) : '';
        $brandParams['BRND_INTD_CONT'] = $params['brandContent'];
        $brandParams['VEST_WAY'] = $params['vestWay'];
        $brandParams['BRND_ORGP_CD'] = $params['brandOrgCd'];
        $brandParams['BRND_STR_OP_OPR_NM'] = $params['brandOpNm'];
        $brandParams['BRND_STR_OP_OPR_CMP_TEL_NO'] = $params['brandCmpTelNo'];
        $brandParams['BRND_STR_OP_OPR_CP_NO'] = $params['brandOprCpNo'];
        $brandParams['BRND_STR_OP_OPR_EML'] = $params['brandOpOprEml'];
        $brandParams['BRND_STR_BACT_OPR_NM'] = $params['brandBactOprNm'];
        $brandParams['BRND_STR_BACT_OPR_CMP_TEL_NO'] = $params['brandBactOprCmpTelNo'];
        $brandParams['BRND_STR_BACT_OPR_CP_NO'] = $params['brandBactOprCpNo'];
        $brandParams['BRND_STR_BACT_OPR_EML'] = $params['brandBactOprEml'];
        $brandParams['ENST_APRV_DNY_RSN_CONT'] = $params['remark'];
        $result = $Brand->updateBrandData($brandParams);
        if ($result === false) {
            $result = array('code' => '40000111', 'msg' => '品牌信息添加失败', 'data' => null);
            $this->jsonOut($result);
        }
        //------------------------------------品牌sllr地址信息保存------------------------------------------------
        $sllrAddrParams['SLLR_ID'] = $params['sllrId'];
        $sllrAddrParams['SLLR_ZPNO'] = $params['sllrZpNo'];
        $sllrAddrParams['SLLR_ADDR'] = $params['sllrAddr'];
        $sllrAddrParams['SLLR_DTL_ADDR'] = $params['sllrDtlAddr'];
        $sllrAddrParams['SLLR_ADDR_DIV_CD'] = 1;
        $result = $BrandSllrAddr->updateSllrAddrData($sllrAddrParams);
        //------------------------------------品牌sllr分类信息保存------------------------------------------------
        $catParams['SLLR_ID'] = $params['sllrId'];
        /*if (!empty($params['catList'])) {*/
        $BrandStrRepCat->delBrandStrRepCatBySllrId($params['sllrId']);
        /*}*/
        foreach ($params['catList'] as $val) {
            if (empty($val)) {
                continue;
            }
            $catParams['CAT_CD'] = $val;
            $BrandStrRepCat->addBrandStrRepCatData($catParams);
        }
        //------------------------------------品牌图片信息保存------------------------------------------------
        $imgParams['SLLR_ID'] = $params['sllrId'];
        foreach ($params['imgList'] as $val) {
            if (empty($val['imgContent']['cdnAddr'])) {
                continue;
            }
            $imgParams['BRND_STR_IMG_CD'] = $val['brandImgCd'];
            $imgParams['BRND_STR_IMG_STAT_CD'] = $val['brandImgStatCd'];
            $imgParams['BRND_STR_IMG_ORGT_FILE_NM'] = $val['imgContent']['orgtName'];
            $imgParams['BRND_STR_IMG_SYS_FILE_NM'] = $val['imgContent']['newName'];
            $imgParams['BRND_STR_IMG_CDN_ADDR'] = $val['imgContent']['cdnAddr'];
            $result = $BrandImg->updateBrandImgData($imgParams);
        }
        $result = array('code' => 2000, 'msg' => 'success', 'data' => true);
        $this->jsonOut($result);
    }

    /**
     * 下载商品品牌数据
     * @see http://wiki.b5msoft.com/index.php/guds/brand/downLoadBrandData
     */
    public function downLoadBrandData()
    {
        $fileName = 'brand_' . date("ymdHis");
        $getData = file_get_contents('php://input');
        $params = json_decode($getData, true);
        $authType = $this->Dictionary->getAuthType('CD', 'CD_VAL');
        $brandStatus = $this->Dictionary->getBrandStatus('CD', 'CD_VAL');
        $dateVal = I('get.dateVal', '');
        $params['authType'] = I('get.authType', '');
        $params['brandStatus'] = I('get.brandStatus', '');
        $params['brandId'] = I('get.brandId', '');
        $params['datetype'] = I('get.datetype', '');
        if (!empty($dateVal)) {
            list($startDate, $endDate) = explode(' - ', $dateVal);//注意这里 连接线 前后的空格
            $params['startDate'] = date('Y-m-d H:i:s', strtotime($startDate) + 24 * 3600) ;//开始
            $params['endDate'] = date('Y-m-d H:i:s', strtotime($endDate) + 24 * 3600 - 1 );
        }
        $Brand = new BrandModel();
        $condition = $Brand->searchCondition($params);
        $brandListData = $Brand->searchBrandList($condition);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');
        header('Cache-Control: max-age=0');
        setlocale(LC_ALL, 'zh_CN');
        $fp = fopen('php://output', 'a');
        fputs($fp, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
        fputcsv($fp, array('品牌Id', '品牌名称', '品牌状态', '品牌授权类型', '创建时间', '更新时间'));
        foreach ($brandListData as $key => $val) {
            $brandListData[$key]['brandStatusName'] = $brandStatus[$val['brandStatus']];
            $brandListData[$key]['authTypeName'] = $authType[$val['authType']];
            $brandName = !empty($val['brandCnName']) ? $val['brandCnName'] : (!empty($val['brandKrName']) ? $val['brandKrName'] : (!empty($val['brandJpName']) ? $val['brandJpName'] : (!empty($val['brandEnName']) ? $val['brandEnName'] : '')));
            $headlist = array($val['brandId'], $brandName, $brandStatus[$val['brandStatus']], $authType[$val['authType']], $val['createTime'], $val['updatedTime']);
            fputcsv($fp, $headlist);
        }
        fclose($fp);
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