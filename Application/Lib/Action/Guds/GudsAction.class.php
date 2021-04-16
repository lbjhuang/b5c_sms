<?php
/**
 * Created by PhpStorm.
 * User: afanti
 * Date: 2017/7/24
 * Time: 14:08
 */

class GudsAction extends BaseAction
{

    #语言
    static $_LANG_VALS = array(
        'N000920100' => array('cn' => '中文', 'kr' => '중국어', 'en' => 'chinese', 'jp' => '中国語', 'need' => 1),
        'N000920400' => array('cn' => '韩语', 'kr' => '한국어', 'en' => 'korean', 'jp' => '韓国語', 'need' => 0),
        'N000920200' => array('cn' => '英语', 'kr' => '영어', 'en' => 'english', 'jp' => '英語', 'need' => 1),
        'N000920300' => array('cn' => '日语', 'kr' => '일본어', 'en' => 'Japanese', 'jp' => '日本語', 'need' => 0),
    );
    #状态
    static $_CHK_STATUS_VALS = array(
        'N000420100' => array('cn' => '审核中', 'kr' => '', 'en' => '', 'jp' => ''),
        'N000420200' => array('cn' => '草稿', 'kr' => '', 'en' => '', 'jp' => ''),
        'N000420400' => array('cn' => '审核成功', 'kr' => '', 'en' => '', 'jp' => ''),
        'N000420300' => array('cn' => '审核驳回', 'kr' => '', 'en' => 'Japaese', 'jp' => ''),
    );
    #按扭权限
    static $_RIGHTS_VALS = array(
        'Guds/Guds/dochkguds'
    );

    private $GudsUnit;

    /**
     * @var DictionaryModel 字典模型
     */
    private $Dictionary;


    public function _initialize()
    {
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
        $this->Dictionary = D('Dictionary');
        $this->GudsUnit = $this->Dictionary->getGudsUnitVals('CD', 'CD_VAL');//获取商品单位
        parent::_initialize();
    }

    public function index()
    {
        $this->display('listMain');
    }

    #商品列表接口
    #@see http://wiki.b5msoft.com/index.php/guds/guds/showList
    public function showList()
    {
        $chkRights = false;
        $page_num = I('get.pageNum', C('PAGE_NUM'));
        $page = I('get.page', 1);
        $getData = file_get_contents("php://input");
        $params = json_decode($getData, true);
        if (!empty($params['dateVal'])) {
            list($startDate, $endDate) = explode(' - ', $params['dateVal']);//注意这里 连接线 前后的空格
            $params['startDate'] = date('Y-m-d H:i:s', strtotime($startDate) + 24 * 3600) ;//开始
            $params['endDate'] = date('Y-m-d H:i:s', strtotime($endDate) + 24 * 3600 - 1 );
        }
        $Guds = new GudsModel();
        $count = $Guds->getGudsList($params, ['type'=>'count']);
        $totalPage = ceil($count[0]['num'] / $page_num);
        $options['start'] = ($page - 1) * $page_num;
        $options['limit'] = $page_num;
        $gudsData = $Guds->getGudsList($params, $options);
        $dict = $this->Dictionary->getDictByType([
            DictionaryModel::CURRENCY_PREFIX,
            DictionaryModel::GUDS_SALE_CHANNEL_PREFIX,
            DictionaryModel::GUDS_SALE_STATUS_PREFIX,
        ], true);

        if (in_array(self::$_RIGHTS_VALS[0], $_SESSION['actlist']) || in_array(1,$_SESSION['role_id'])) {
            $chkRights = true;
        }

        foreach ((array)$gudsData as $key => $guds) {
            $gudsData[$key]['unitName'] = $this->GudsUnit[$guds['unit']];//单位
            $gudsData[$key]['langName'] = self::$_LANG_VALS[$guds['lang']]['cn'];
            $gudsData[$key]['chkStatusName'] = self::$_CHK_STATUS_VALS[$guds['chkStatus']]['cn'];
            $gudsData[$key]['price'] = sprintf('%0.3f', $guds['price']);
            $gudsData[$key]['priceType'] = $dict[DictionaryModel::CURRENCY_PREFIX][$guds['priceType']];
            $gudsData[$key]['isFrontProduct'] = $this->isFrontProduct($guds['publishType']);
        }

        $result = array(
            'code' => 2000,
            'msg' => 'success',
            'data' => array(
                'pageNum' => $page_num,
                'page' => $page,
                'totalPage' => $totalPage,
                'totalNum' => $count[0]['num'],
                'chkRights' => $chkRights,
                'saleChannel' => $dict[DictionaryModel::GUDS_SALE_CHANNEL_PREFIX],
                'saleStatus' => $dict[DictionaryModel::GUDS_SALE_STATUS_PREFIX],
                'list' => $gudsData
            )
        );
        $this->jsonOut($result);
    }

    #商口添加页面接口
    #@see http://wiki.b5msoft.com/index.php/guds/guds/addPage
    public function addPage()
    {
        $dict = $this->Dictionary->getDictByType([
            DictionaryModel::GUDS_SALE_CHANNEL_PREFIX,
            DictionaryModel::GUDS_PRODUCT_TYPE_PREFIX,
            DictionaryModel::GUDS_PRODUCT_FLAG_PREFIX,
            DictionaryModel::GUDS_PRODUCT_DESCRIPTION_PREFIX,
        ], true);

        $brandModel = new BrandModel();
        $brandData = $brandModel->getBrandList(array(), 0);
        $result = array(
            'code' => 2000,
            'msg' => 'success',
            'data' => array(
                'brandList' => $brandData,
                'unit' => $this->GudsUnit,
                'lang' => self::$_LANG_VALS,
                'saleChannel' => $dict[DictionaryModel::GUDS_SALE_CHANNEL_PREFIX],
                'productType' => $dict[DictionaryModel::GUDS_PRODUCT_TYPE_PREFIX],
                'productFlag' => $dict[DictionaryModel::GUDS_PRODUCT_FLAG_PREFIX] + array('0' => 'none'),
                'productDesc' => $dict[DictionaryModel::GUDS_PRODUCT_DESCRIPTION_PREFIX],
            ));
        $this->jsonOut($result);
    }

    /**
     * 创建后端商品页面的输出。
     */
    public function create()
    {
        $this->display('addGoods');
    }

    /**
     * 创建前端商品页面的输出。
     */
    public function createFrontend()
    {
        $this->display('addGoodsFE');
    }

    #添加保存商口接口
    #@see http://wiki.b5msoft.com/index.php/guds/guds/doAdd
    public function doAdd()
    {
        $getData = file_get_contents("php://input");
        $params = json_decode($getData, true);
        $params['brandId'] = addslashes($params['brandId']);

        if (empty($params['cateId']) || empty($params['brandId'])) {
            $result = array('code' => '40000', 'msg' => '参数不正确', 'data' => null);
            $this->jsonOut($result);
        }

        if (empty($params['brandName'])) {
            $result = array('code' => '400001', 'msg' => '商品品牌名不空', 'data' => null);
            $this->jsonOut($result);
        }

        if (empty($params['unit'])) {
            $result = array('code' => '400002', 'msg' => '商品单位不能为空', 'data' => null);
            $this->jsonOut($result);
        }

        if (empty($params['originCountry']) || !is_numeric($params['originCountry'])) {
            $this->jsonOut(array('code' => 400701, 'msg' => L('GUDS_NEED_ORIGIN'), 'data' => $params));
        }

        if (empty($params['currency'])) {
            $this->jsonOut(array('code' => 400702, 'msg' => L('GUDS_NEED_CURRENCY'), 'data' => $params));
        }

        //没有输入中文和英文内容
        if (empty($params['langData']['N000920100']['gudsName']) || empty($params['langData']['N000920100']['imgData']))
        {
            $result = array('code' => '400703', 'msg' => L('NEED_CHINESE_CONTENT'), 'data' => '');
            $this->jsonOut($result);
        }

        if (empty($params['langData']['N000920200']['gudsName']) || empty($params['langData']['N000920200']['imgData']))
        {
            $result = array('code' => '400703', 'msg' => L('NEED_ENGLISH_CONTENT'), 'data' => '');
            $this->jsonOut($result);
        }

        //新增加Guds的品牌Id，数字版本ID属性，现在暂时冗余后续再慢慢替换。
        $brandModel = new BrandModel();
        $brandData = $brandModel->getBrand($params['brandId']);
        $params['BRND_ID'] = $brandData['BRND_ID'];
        //保存商品信息
        $Guds = new GudsModel();
        $saveGudsRes = $Guds->createGuds($params);
        if (empty($saveGudsRes)) {
            $result = array('code' => '40000100', 'msg' => 'failed', 'data' => 'guds basic info error');
            $this->jsonOut($result);
        }

        //改为一次性批量插入商品图片，一次SQL，表有语言字段，可以支持扩展。
        $imgModel = new GudsImgModel();
        $saveImgRes = $imgModel->saveData($params, $saveGudsRes);

        //改为批量一次性保存商品描述数据，表有语言字段，可以支持扩展。
        $descModel = new GudsDescModel();
        $saveDescRes = $descModel->saveData($params, $saveGudsRes);

        //改为批量一次性保存商品详情数据，表有语言字段，可以支持扩展。
        $detailModel = new GudsDtlModel();
        $saveDetailRes = $detailModel->saveData($params, $saveGudsRes);

        $result = array('code' => '2000', 'msg' => 'success', 'data' => $saveGudsRes);
        $this->jsonOut($result);
    }


    /**
     * #商品详情接口
     * #@see http://wiki.b5msoft.com/index.php/guds/guds/showGuds
     */
    public function showGuds()
    {
        $gudsId = I('get.gudsId', 0);
        $dict = $this->Dictionary->getDictByType([
            DictionaryModel::GUDS_SALE_CHANNEL_PREFIX,
            DictionaryModel::GUDS_PRODUCT_TYPE_PREFIX,
            DictionaryModel::GUDS_PRODUCT_FLAG_PREFIX,
            DictionaryModel::GUDS_PRODUCT_DESCRIPTION_PREFIX,
            DictionaryModel::LANGUAGES
        ], true);

        $gudsImgsData = $gudsData = array();
        if (empty($gudsId)) {
            $this->jsonOut(array('code' => '40000020', 'msg' => 'params is error', 'data' => null));
        }

        $Guds = new GudsModel();
        $GudsImg = new GudsImgModel();
        $GudsChk = new GudsChkModel();
        $GudsDesc = new GudsDescModel();
        $GudsDtl = new GudsDtlModel();
//        $gudsData = $Guds->getGudsData(['GUDS_ID' => $gudsId]);
        $gudsList = $Guds->getGudsListById($gudsId);
        $mainGudsData = $gudsList[$gudsId];
        $gudsImgsDataArr = $GudsImg->getGudsImgData(['MAIN_GUDS_ID'=>$mainGudsData['mainId']]);//商品图片列表
        $gudsDescDataArr = $GudsDesc->getDescData(['MAIN_GUDS_ID' => $mainGudsData['mainId']]);#商品说明
        $gudsDtlDataArr = $GudsDtl->getDtlData(['MAIN_GUDS_ID' => $mainGudsData['mainId']]);#商品详情
        foreach ($gudsImgsDataArr as $gudsImgs) {
            $gudsImgsData[$gudsImgs['LANGUAGE']] = $gudsImgs['GUDS_IMG_CDN_ADDR'];
        }

        foreach ($gudsDtlDataArr as $gudsDtl) {
            $gudsDtlData[$gudsDtl['LANGUAGE']] = $gudsDtl['GUDS_DTL_CONT'];
        }

        $gudsDescData = [];
        foreach ($gudsDescDataArr as $gudsDescs) {
            $gudsDesc = json_decode($gudsDescs['GUDS_DESCRIBE'], true);
            foreach ((array)$gudsDesc as $val) {
                $gudsDescData[$val['gudsInfo']][$gudsDescs['LANGUAGE']] = $val['productDetail'];
            }
        }

        //添加前后端状态
        if (!empty($mainGudsData)) {
            $mainGudsData['isFrontProduct'] = $this->isFrontProduct($mainGudsData['publishType']);
            $leverArr = $Guds->dealWithCateLever($mainGudsData['gudsCat']);
            $gudsData['common'] = array_merge($mainGudsData, $leverArr);
        }

        //先覆盖原来的语言CODE码，改成数组,添加每种语言的商品内容
        $gudsData['common']['lang'] = [];
        foreach ((array)$gudsList as $key => $guds) {
            $gudsData['common']['lang'][$guds['lang']] = array(
                'gudsId' => $guds['gudsId'],
                'gudsName' => $guds['gudsName'],
                'gudsCnName' => $guds['gudsCnName'],
                'gudsSubName' => $guds['gudsSubName'],
                'gudsSubCnName' => $guds['gudsSubCnName'],
                'langName' => $dict[DictionaryModel::LANGUAGES][$guds['lang']],
                'img' => empty($gudsImgsData) ? '' : $gudsImgsData[$guds['lang']],
                'detail' => empty($gudsDtlData) ? '' : html_entity_decode($gudsDtlData[$guds['lang']]),
            );
        }

        //添加审核状态信息，审核不通过的才读取数据库，其他全部是空
        if (GudsChkModel::REJECT == $mainGudsData['chkStatus']) {
            $chkData = $GudsChk->getChkContent($mainGudsData['mainId'], $gudsId);
        }
        $gudsData['common']['remark'] = !empty($chkData['CHK_CONTENT']) ? $chkData['CHK_CONTENT'] : '';

        //类目数据处理。
        $cateModel = new CategoryModel();
        $cateList = $cateModel->getRelateCateByCode($mainGudsData['gudsCat']);
        $cateTree = $cateModel->buildCateByLevel($cateList, $mainGudsData['gudsCat']);
        $result = array(
            'code' => 2000,
            'msg' => 'success',
            'data' => array(
                'guds' => $gudsData,
                'brandList' => $cateTree,
                'gudsDescData' => $gudsDescData,
                'saleChannel' => $dict[DictionaryModel::GUDS_SALE_CHANNEL_PREFIX],
                'productType' => $dict[DictionaryModel::GUDS_PRODUCT_TYPE_PREFIX],
                'productFlag' => $dict[DictionaryModel::GUDS_PRODUCT_FLAG_PREFIX] + array('0' => 'none'),
                'productDesc' => $dict[DictionaryModel::GUDS_PRODUCT_DESCRIPTION_PREFIX],
            ));
        $this->jsonOut($result);
    }


    /**处理审核状态
     * #@see http://wiki.b5msoft.com/index.php/guds/guds/doChkGuds
     */
    public function doChkGuds()
    {
        $GudsChk = new GudsChkModel();
        $Guds = new GudsModel();
        $getData = file_get_contents("php://input");
        $getParams = json_decode($getData, true);
        $params['MAIN_GUDS_ID'] = $getParams['mainId'];
        $params['GUDS_ID'] = $getParams['gudsId'];
        $params['CHK_STATUS'] = $getParams['status'];
        $params['CHK_CONTENT'] = $getParams['content'];
        if (empty($params['MAIN_GUDS_ID']) || empty($params['CHK_STATUS']) || empty($params['GUDS_ID'])) {
            $this->jsonOut(array('code' => '4001', 'msg' => 'params is  error', 'data' => null));
        }
        $gudsData = array_pop($Guds->getGudsData(['GUDS_ID' => $params['GUDS_ID']]));
        $data = $GudsChk->getChkData($params['MAIN_GUDS_ID']);
        //没有审核数据，一般是新建的商品或者没有提交过申请审核的；此时申请审核
        if (empty($data)) {
            //需要添加审核记录数据，并更新SPU审核状态，允许改为草稿和待审核。
            $chResult = $GudsChk->saveData($params);
            $result = ['code' => 2000, 'msg' => 'success', 'data' => $chResult];
        } elseif(GudsChkModel::DRAFT == $gudsData['chkStatus']){
            //有审核数据但是草稿状态的，是审核通过了又修改了商品，这时请求审核的允许，其他不允许。
            if ($params['CHK_STATUS'] == GudsChkModel::PENDING){
                $chResult = $GudsChk->updateData($params);
                $result = ['code' => 2000, 'msg' => 'success', 'data' => $chResult];
            } else {
                //草稿状态只能改为待审核
                $result = ['code' => '4003', 'msg' => L('REVIEW_ONLY_TOBE_PENDING'), 'data' => false];
            }
        } elseif(GudsChkModel::PENDING == $gudsData['chkStatus']){
            if ($params['CHK_STATUS'] == GudsChkModel::APPROVED ||$params['CHK_STATUS'] == GudsChkModel::REJECT){
                $chResult = $GudsChk->updateData($params);
                $result = ['code' => 2000, 'msg' => 'success', 'data' => $chResult];
            } else{
                $result = ['code' => '4004', 'msg' => L('REVIEW_ONLY_TOBE_DONE')];
            }
        } elseif(GudsChkModel::APPROVED == $gudsData['chkStatus'] || GudsChkModel::REJECT == $gudsData['chkStatus']){
            //审核通过或驳回，需要验证审核状态该，如果已经审核过，否则提示 已经处理了，去查看状态进行确认。
            //如确实需要修改审核状态的，需要修改商品后重新提交 审核申请。
            $result = ['code' => '4005', 'msg' => L('REVIEW_PROCESSED'), 'data' => false];
        } else {
            //其他所有情况操作，均视为非法请求。
            $result = ['code' => '4006', 'msg' => 'update failed', 'data' => null];
        }

        if ($result['data'] === false) {
            $this->jsonOut($result);
        } else {
            if ($params['CHK_STATUS'] == GudsChkModel::APPROVED)#审核通过发送消息到队列
            {
                $this->sendMessageToMq($params['MAIN_GUDS_ID'], 'N000920100');
            }
            $this->jsonOut($result);
        }
    }

    #上传图片
    #@see http://wiki.b5msoft.com/index.php/guds/guds/uploadGudsImage
    public function uploadGudsImage()
    {
        $uploadFile = D('@Model/Guds/FileUpload');
        $result = $uploadFile->fileUploadExtend();
        $this->jsonOut(array('code' => 2000, 'msg' => 'success', 'data' => $result));
    }

    /**
     * 多语言更新，同时兼容添加操作。
     */
    public function updateGudsMultiple()
    {
        $getData = file_get_contents("php://input");
        $params = json_decode($getData, true);
        if (empty($params['mainId']) || empty($params['brandId']) || empty($params['langData'])) {
            $this->jsonOut(array('code' => '40020', 'msg' => L('INVALID_PARAMS'), 'data' => null));
        }

        if (empty($params['unit'])) {
            $result = array('code' => '40019', 'msg' => '商品单位不能为空', 'data' => null);
            $this->jsonOut($result);
        }

        //组合和判定是修改还是新添加。
        $gudsModel = new GudsModel();
        $gudsImg = new GudsImgModel();
        $gudsDesc = new GudsDescModel();
        $gudsDetail = new GudsDtlModel();

        if (empty($params['originCountry']) || !is_numeric($params['originCountry'])) {
            $this->jsonOut(array('code' => 400701, 'msg' => L('GUDS_NEED_ORIGIN'), 'data' => $params));
        }

        if (empty($params['priceType'])) {
            $this->jsonOut(array('code' => 400702, 'msg' => L('GUDS_NEED_CURRENCY'), 'data' => $params));
        }

        //没有输入中文和英文内容
        if (empty($params['langData']['N000920100']['gudsName']) || empty($params['langData']['N000920100']['imgData']))
        {
            $result = array('code' => '400703', 'msg' => L('NEED_CHINESE_CONTENT'), 'data' => '');
            $this->jsonOut($result);
        }

        if (empty($params['langData']['N000920200']['gudsName']) || empty($params['langData']['N000920200']['imgData']))
        {
            $result = array('code' => '400703', 'msg' => L('NEED_ENGLISH_CONTENT'), 'data' => '');
            $this->jsonOut($result);
        }

        //新增加Guds的品牌Id，数字版本ID属性，现在暂时冗余后续再慢慢替换。
        $brandModel = new BrandModel();
        $brandData = $brandModel->getBrand($params['brandId']);
        $params['BRND_ID'] = $brandData['BRND_ID'];

        //准备数据，前任挖坑后人 不想填了
        $params['GUDS_VAT_RFND_YN'] = $params['isRefundTax'];//是否返税
        $params['RETURN_RATE'] = $params['refundTaxRate'];//返税比率
        $params['ADDED_TAX'] = $params['overseasTax'];  //FIXME 这里要改成跨境综合税
        $params['OVERSEAS_RATE'] = $params['overseasTax'];
        $params['MIN_BUY_NUM'] = $params['minBuyNum'];
        $params['MAX_BUY_NUM'] = $params['maxBuyNum'];
        $params['BELONG_SEND_CAT'] = $params['expressCat'];
        $params['LOGISTICS_TYPE'] = $params['expressType'];

        //验证Guds的分类是否修改，如果修改需要更新 SKU 的自编码，重新自动生成。
        $resData = [];
        //处理完更新的，删除更新的，剩下的就是新加的
        foreach ($params['langData'] as $langCode => $langData) {
            //验证数据
            if (empty($langData['gudsName'])) {
                $this->jsonOut(array('code' => '40021', 'msg' => L('INVALID_PARAMS'), 'data' => null));
            }

            if (empty($langData['imgData'])) {
                $this->jsonOut(array('code' => '40022', 'msg' => L('MUST_FULFILL_IMAGE'), 'data' => null));
            }

            //前端发布，必须有Detail信息。
            if (1 == $params['publishType'] && empty($langData['detail'])) {
                $this->jsonOut(array('code' => '40022', 'msg' => L('FRONTEND_NEED_DETAIL'), 'data' => null));
            }

            //先处理更新的数据，没有GudsId是新加的，因为GUDS_ID 是唯一索引，所以修改品牌没关系，不会导致主键冲突
            if (!empty($langData['gudsId'])) {
                $data['SLLR_ID'] = !empty($params['brandId']) ? $params['brandId'] : '';//品牌id
                $data['BRND_ID'] = !empty($params['BRND_ID'] ) ?$params['BRND_ID']  : '';//品牌新的数字格式
                $data['BRND_NM'] = !empty($params['brandName']) ? $params['brandName'] : '';//品牌名称
                $data['GUDS_CAT'] = !empty($params['cateId']) ? $params['cateId'] : "";//类目修改，后端类目，即品牌类目
                $data['GUDS_NM'] = $langData['gudsName'];
                $data['GUDS_CNS_NM'] = $langData['gudsName'];
                $data['GUDS_VICE_NM'] = $langData['gudsSubName'];
                $data['GUDS_VICE_CNS_NM'] = $langData['gudsSubName'];//没太大意义，这里很不好获取 中文的，而且有可能直接就不填
                $data['STD_XCHR_KIND_CD'] = empty($params['priceType']) ? '' : $params['priceType'];//币种
                $data['GUDS_ORGP_CD'] = empty($params['originCountry']) ? '' : $params['originCountry'];//产地
                $params['GUDS_DLVC_DESN_VAL_5'] = $params['PCS'];//装箱数
                $data['VALUATION_UNIT'] = $params['unit'];//计量单位
                $data['SALE_CHANNEL'] = empty($params['saleChannel']) ? '' : implode(',', $params['saleChannel']);
                $data['OVER_YN'] = empty($params['overYn']) ? 'N' : $params['overYn'];//默认不允许超卖
                $data['GUDS_FLAG'] = empty($params['productFlag']) ? null : $params['productFlag']; //商品标记
                $data['PUBLISH_TYPE'] = isset($params['publishType']) ? $params['publishType'] : 0;
                $data['IS_SHELF_LIFE'] = empty($params['isLifetime']) ? '' : $params['isLifetime'];//是否有有效期
                $data['GUDS_REG_STAT_CD'] = 'N000420200'; #商品修改，就要重新审核，改为草稿状态
                $data = array_merge($data, $params);

                $res = $gudsModel->updateGuds($data, $langData['gudsId']);
                $resData['update'][$langData['gudsId']]['updateGuds'] = $res;//更新结果列表

                //更新商品图片
                if (!empty($langData['imgData'])) {
                    $data['GUDS_ID'] = $langData['gudsId'];
                    $data['MAIN_GUDS_ID'] = $params['mainId'];
                    $data['GUDS_IMG_ORGT_FILE_NM'] = $langData['imgData']['orgtName'];
                    $data['GUDS_IMG_SYS_FILE_NM'] = $langData['imgData']['newName'];
                    $data['GUDS_IMG_CDN_ADDR'] = $langData['imgData']['cdnAddr'];
                    $data['LANGUAGE'] = $langCode;
                    $resImages = $gudsImg->updateData($data);
                    $resData['update'][$langData['gudsId']]['images'] = $resImages;
                }

                //更新描述信息
                $descParams['gudsId'] = $langData['gudsId'];
                $descParams['mainId'] = $params['mainId'];
                $descParams['lang'] = $langCode;
                $descArr = [];
                foreach ($langData['desc'] as $type => $desc) {
                    $descArr[] = ['gudsInfo' => $type, 'productDetail' => $desc];
                }
                $descParams['desc'] = json_encode($descArr);
                $resDesc = $gudsDesc->updateData($descParams);
                $resData['update'][$langData['gudsId']]['desc'] = $resDesc;

                //更新商品详情
                $dtlParams['GUDS_ID'] = $langData['gudsId'];
                $dtlParams['lang'] = $langCode;
                $dtlParams['MAIN_GUDS_ID'] = $params['mainId'];
                $dtlParams['GUDS_DTL_CONT'] = $dtlParams['GUDS_DTL_CONT_WEB'] = $langData['detail'];
                $resDetail = $gudsDetail->updateData($dtlParams);
                $resData['update'][$langData['gudsId']]['detail'] = $resDetail;
                //清除更新完成的数据，为添加做准备
                unset($params['langData'][$langCode]);
            }
        }

        //处理添加
        $addRes = [];
        if (!empty($params['langData'])) {
            $addRes = $this->createWhenUpdate($params);
        } else {
            $addRes['save']['tips'] = 'Have no add require';
        }

        //通知MQ修改，进行ES搜索更新
        $this->sendMessageToMq($params['mainId'], 'N000920100', ['optType' => 0, 'saleStatus' => 'N000100100']);

        $resData = array_merge($resData, $addRes);
        $this->sendMessageToMq($params['mainId'], $params['lang'], ['optType' => 0, 'saleStatus' => 'N000100100']);
        $result = ['code' => 2000, 'msg' => 'success', 'data' => $resData];
        $this->jsonOut($result);
    }


    /**
     * 更新商品的时候，同时进行添加操作的处理
     * @param array $params 更新的新数据
     * @return mixed
     */
    private function createWhenUpdate($params)
    {
        $gudsModel = new GudsModel();
        $params['SALE_CHANNEL'] = empty($params['saleChannel']) ? '' : implode(',', $params['saleChannel']);
        $params['OVER_YN'] = empty($params['overYn']) ? 'N' : $params['overYn'];
        $params['IS_SHELF_LIFE'] = empty($params['isLifetime']) ? 'N' : $params['isLifetime'];
        $params['PUBLISH_TYPE'] = isset($params['publishType']) ? $params['publishType'] : 0;
        $item['GUDS_FLAG'] = empty($params['productFlag']) ? null : $params['productFlag'];

        //读取MainGudsId的商品信息，同步为同样的币种、产地、运输信息等。
        $mainGudsInfo = $gudsModel->getGudsData(array('GUDS_ID'=>$params['mainId']));
        $mainGuds = array_pop($mainGudsInfo);
        foreach ($params['langData'] as &$item){
            $item['GUDS_VAT_RFND_YN'] = $mainGuds['GUDS_VAT_RFND_YN'];
            $item['RETURN_RATE'] = $mainGuds['RETURN_RATE'];
            $item['ADDED_TAX'] = $mainGuds['ADDED_TAX'];
            $item['OVERSEAS_RATE'] = $mainGuds['ADDED_TAX'];//$mainGuds['OVERSEAS_RATE']; //FIXME 后面这里要改成跨境综合税
            $item['GUDS_DLVC_DESN_VAL_5'] = $mainGuds['GUDS_DLVC_DESN_VAL_5'];
            $item['MIN_BUY_NUM'] = $mainGuds['MIN_BUY_NUM'];
            $item['MAX_BUY_NUM'] = $mainGuds['MAX_BUY_NUM'];
            $item['BELONG_SEND_CAT'] = $mainGuds['BELONG_SEND_CAT'];
            $item['LOGISTICS_TYPE'] = $mainGuds['LOGISTICS_TYPE'];
        }
        $data = $gudsModel->createGuds($params);
        $resData['save']['guds'] = $data['tips'];
        if (!empty($data['langData'])) {
            //改为一次性批量插入商品图片，一次SQL，表有语言字段，可以支持扩展。
            $imgModel = new GudsImgModel();
            $saveImgRes = $imgModel->saveData($params, $data);
            $resData['save']['saveImage'] = $saveImgRes;

            //改为批量一次性保存商品描述数据，表有语言字段，可以支持扩展。
            $descModel = new GudsDescModel();
            $saveDescRes = $descModel->saveData($params, $data);
            $resData['save']['saveDesc'] = $saveDescRes;

            //改为批量一次性保存商品详情数据，表有语言字段，可以支持扩展。
            $detailModel = new GudsDtlModel();
            $saveDetailRes = $detailModel->saveData($params, $data);
            $resData['save']['saveDetail'] = $saveDetailRes;
        }

        return $resData;
    }

    public function delete()
    {
        $this->_empty();
    }

    /**
     * 处理商品发送消息的Data
     * @param $mainId
     * @param $arr
     * @return bool
     */
    public function dealwithGudsDataForEsSearch($mainId, $arr)
    {
        $searchData = [];
        $mainGudsId = $mainId/*'80007842'*/;
        $operType = $arr['optType'];#1代表在售/售完,0代表待销售/下架
        $gudsModel = new GudsModel();
        $result = $gudsModel->getProductAllInfo($mainGudsId);
        if (empty($result)) return false;

        $data = array();
        foreach ($result as $value) {
            if ($value['publishType'] == 0) { //后端发布的直接跳过
                continue;
            }

            $data[$value['language1']] = $value;
        }
        $detailMode = new GudsDtlModel();
        $dtlData = $detailMode->getDtlData(['MAIN_GUDS_ID' => $mainGudsId]);
        foreach ($dtlData as $value) {
            $data[$value['LANGUAGE']]['dtlPicture'] = $value['GUDS_DTL_CONT'];
        }
        $priceData = [];
        $optionPriceModel = new OptionPriceModel();
        $priceArr = $optionPriceModel->getPriceByMainGudsId($mainGudsId);/*$client->get($key)*/;

        if (!empty($priceArr)) {
            foreach ($priceArr as $value) {
                $priceData[] = [
                    "id" => $value['ID'],
                    "gudsOptId" => $value['GUDS_OPT_ID'],
                    "marketPrice" => $value['MARKET_PRICE'],
                    "realPrice" => $value['REAL_PRICE'],
                    "deliveryWarehouse" => $value['WAREHOUSE_CODE'],
                ];
            }
        }

        foreach ($data as $key => $v) {
            $searchData[$key] = [
                "Belongsendcat" => $v['Belongsendcat'],
                "Brand" => $v['Brand'],
                "BrndSaleChannel" => empty($v['BrndSaleChannel']) ? [] : explode(',', $v['BrndSaleChannel']),
                "CreateTime" => $v['CreateTime'],
                "DOCID" => $v['DOCID'],
                "DeliveryMethod" => $v['DeliveryMethod'],
                "GappPushTime" => $v['GappPushTime'],
                "ItemSaleChannel" => empty($v['ItemSaleChannel']) ? [] : explode(',', $v['ItemSaleChannel']),
                "Picture" => $v['Picture'],
                "PriceRange" => empty($v['PriceRange']) ? '' : $v['PriceRange'],
                "ProduceSource" => $v['ProduceSource'],
                "SaleStatus" => $arr['saleStatus'], #销售状态
                "Source" => empty($v['Source']) ? [] : explode(',', $v['Source']),
                "Tags" => empty($v['Tags']) ? [] : explode(',', $v['Tags']),
                "Title" => $v['Title'],
                "UpdateTime" => $v['UpdateTime'],
                "dlvwarehouse" => $v['dlvwarehouse'],
                "dtlPicture" => empty($v['dtlPicture']) ? "" : $v['dtlPicture'],
                "firstCategory" => !empty($v['Category']) ? explode('>', $v['Category'])[0] : '',
                "gudsId" => $v['gudsId'],
                "gudsPrice" => $priceData,
                "gudsStatCd" => empty($v['gudsStatCd']) ? [] : explode(',',$v['gudsStatCd']),
                "highsaleprc" => empty($v['highsaleprc']) ? 0.00 : $v['highsaleprc'],
                "highsaleprcYN" => empty($v['highsaleprcYN']) ? "N" : $v['highsaleprcYN'],
                "language" => $key,
                "lowsaleprc" => empty($v['lowsaleprc']) ? 0.00 : $v['lowsaleprc'],
                "lowsaleprcYN" => empty($v['lowsaleprcYN']) ? "N" : $v['lowsaleprcYN'],
                "mainGudsId" => $mainGudsId,
                "maxPrice" => $v['maxPrice'],
                "midsaleprc" => empty($v['midsaleprc']) ? 0.00 : $v['midsaleprc'],
                "midsaleprcYN" => empty($v['midsaleprcYN']) ? 'N' : $v['midsaleprcYN'],
                "minPrice" => empty($v['minPrice']) ? 0.00 : $v['minPrice'],
                "minorderNum" => empty($v['minorderNum']) ? 0.00 : $v['minorderNum'],
                "onesalePrice" => empty($v['onesalePrice']) ? 0.00 : $v['onesalePrice'],
                "onesaleYN" => empty($v['onesaleYN']) ? 'Y' : $v['onesaleYN'],
                "operType" => $operType,#1代表在售/售完,0代表等销售/下架
                "postageYN" => empty($v['postageYN']) ? '' : $v['postageYN'],
                "price" => empty($v['price']) ? 0.00 : $v['price'],
                "secondCategory" => !empty($v['Category']) ? explode('>', $v['Category'])[1] : '',
                "sllrId" => empty($v['sllrId']) ? '' : $v['sllrId'],
                "xchrKindCd" => empty($v['xchrKindCd']) ? '' : $v['xchrKindCd'],
            ];
        }
        $esData['data']['guds'] = array_values($searchData);
        $esData['processId'] = create_guid();
        $rbmq = new GudsRmqModel();
        $rbmq->getConfig();
        $rbmq->setData($esData);
        $result = $rbmq->submit();
        return $result;
    }

    /**
     * 通过sku信息处理商品销售状态
     * @param $mainId
     * @return array
     */
    private function getProductStatusBySku($mainId)
    {
        $isSale = 0;
        $GudsOptionModel = new GudsOptionModel();
        $arr = ['mainGudsId' => $mainId];
        $optionArr = $GudsOptionModel->getGudsOptions($arr);
        foreach ($optionArr as $value) {
            if ($value['GUDS_OPT_SALE_STAT_CD'] == 'N000100600' || $value['GUDS_OPT_SALE_STAT_CD'] == 'N000100100') {
                $isSale++;
            }
        }
        return $isSale == 0 ? ['optType' => 0, 'saleStatus' => 'N000100100'] : ['optType' => 1, 'saleStatus' => 'N000100300'];
    }

    /**
     * 判断商品是否前端商品
     * @param $publishType
     * @return bool
     */
    private function isFrontProduct($publishType)
    {
        return empty($publishType) || $publishType == 0 ? false : true;
    }

    /**
     * 处理发送商品信息到消息队列
     * @param $mainId
     * @param string $lang
     * @param array $arr
     * @return bool
     */
    public function sendMessageToMq($mainId, $lang = 'N000920100', $arr = [])
    {
        #只推中文的商品
        if ($lang != 'N000920100') return false;
        $Guds = D('@Model/Guds/Guds');
        $gudsDataArr = $Guds->getGudsDetailByMgudsIdAndLang($mainId, $lang);
        if (empty($gudsDataArr)) {
            return false;
        }

        $isFrontProduct = $this->isFrontProduct($gudsDataArr[0]['publishType']);
        if (!empty($isFrontProduct)) {
            empty($arr) && $arr = $this->getProductStatusBySku($mainId);
            $this->dealwithGudsDataForEsSearch($mainId, $arr);
        }
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