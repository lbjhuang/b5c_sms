<?php

/**
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/11/20
 * Time: 18:52
 */
class  CouponAction extends BaseAction
{
    /**
     * @var CouponModel
     */
    private $_couponModel;
    /**
     * @var DictionaryModel
     */
    private $_dicModel;

    private $_dicData;

    private $_commData;

    private $_filePath = '/opt/b5c-disk/coupon/';/*'Application/Tpl/Home/Public/images/coupon/';*/

    private $_OPT_DATA = ['mod' => ['CD' => 'mod', 'CD_VAL' => '修改'], 'start' => ['CD' => 'start', 'CD_VAL' => '启用'], 'stop' => ['CD' => 'stop', 'CD_VAL' => '停用']];

    private $_result = ['code' => '400000', 'msg' => 'failed', 'data' => null];

    private $_API_URL = '';

    public function _initialize()
    {
        import('ORG.Util.Page');// 导入分页类
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
        $this->_couponModel = D('@Model/Coupon/Coupon');
        $this->_dicModel = D('Dictionary');
        $this->_dicData = $this->_dicModel->getDictByType([
            DictionaryModel::COUPON_ONCE_SEND_OBJECT, #一次性发放对象
            DictionaryModel::COUPON_CONTINUED_SEND_OBJECT,#持续发放对象
            DictionaryModel::COUPON_TYPE, #优惠券类型
        ]);
        $this->_commData = [
            'status' => [0 => ['CD' => 0, 'CD_VAL' => '停用'], 1 => ['CD' => 1, 'CD_VAL' => '启用']],
            'sendWay' => [1 => ['CD' => 1, 'CD_VAL' => '一次发放'], 2 => ['CD' => 2, 'CD_VAL' => '持续发放']],
            'threshold' => [0 => ['CD' => 0, 'CD_VAL' => '无门槛'], 1 => ['CD' => 1, 'CD_VAL' => '有门槛']],
            'usedTimeType' => [1 => ['CD' => 1, 'CD_VAL' => '绝对时间'], 2 => ['CD' => 2, 'CD_VAL' => '相对时间']],
            'superpositionRule' => [0 => ['CD' => 0, 'CD_VAL' => '不叠加'], 1 => ['CD' => 1, 'CD_VAL' => '叠加']],
            'useRange' => [1 => ['CD' => 1, 'CD_VAL' => '全部商品'], 2 => ['CD' => 2, 'CD_VAL' => '部分商品']],
        ];
        $this->_API_URL = C('openApiUrl') . "/dataB5C/publishCoupon.json";

        parent::_initialize();
    }

    /**
     * 优惠券列表
     */
    public function index()
    {
        $params['status'] = $_REQUEST['status'] !== '' && isset($_REQUEST['status']) ? (int)$_REQUEST['status'] : '-1';
        $params['id'] = !empty($_REQUEST['couponId']) ? $_REQUEST['couponId'] : '';
        $params['title'] = !empty($_REQUEST['title']) ? $_REQUEST['title'] : '';
        $params['type'] = !empty($_REQUEST['type']) ? $_REQUEST['type'] : '';
        $params['name'] = !empty($_REQUEST['name']) ? $_REQUEST['name'] : '';
        $params['sendWay'] = !empty($_REQUEST['sendWay']) ? $_REQUEST['sendWay'] : '';
        $page_num = empty($_REQUEST['pageNum']) ? 10 : $_REQUEST['pageNum'];
        #var_dump($params['status'],$_REQUEST['status']);die;
        $count = (int)$this->_couponModel->getConpouList($params, 'count');
        $Page = new Page($count, $page_num);
        $show = $Page->show();
        $couponData = $this->_couponModel->getConpouList($params, 'list', $Page->firstRow, $Page->listRows);
        $ids = [];
        foreach ($couponData as $val) {
            $ids[] = $val['id'];
        }
        $usedNumData = $this->_couponModel->getUsedCouponCountByIds($ids);
        foreach ($couponData as $key => $val) {
            $couponData[$key]['usedNum'] = isset($usedNumData[$val['id']]) ? $usedNumData[$val['id']] : 0;
        }
        $linkParams = http_build_query($params);
        $this->assign('page', $show);
        $this->assign('total', $count);
        $this->assign('couponData', $couponData);
        $this->assign('status', $params['status']);
        $this->assign('couponId', $params['id']);
        $this->assign('title', $params['title']);
        $this->assign('type', $params['type']);
        $this->assign('name', $params['name']);
        $this->assign('sendWay', $params['sendWay']);
        $this->assign('typeArr', $this->_dicData[DictionaryModel::COUPON_TYPE]);
        $this->assign('commData', $this->_commData);
        $this->assign('linkParams', $linkParams);
        $this->display('list');
    }

    /**
     * 优惠券详情
     */
    public function detail()
    {
        $optModel = D('@Model/Coupon/CouponOpt');
        $id = I('get.id', 0);
        $shopData = '';
        $couponData = $this->_couponModel->getCouponDetail($id);
        $couponOptData = $optModel->getCouponOptData($id);
        if (!empty($couponData['shop'])) {
            #$shopDataArr = explode(',',$couponData['shop']);
            $shopinfo = $this->_couponModel->getShopNameById($couponData['shop']);
            foreach ($shopinfo as $value) {
                $shopData[] = $value['STORE_NAME'];
            }
            $shopData = implode(',', $shopData);
        }
        if ($couponData['used_time_type'] == '1') {
            $timeArr = explode('_', $couponData['used_time_value']);
            $startTime = strtotime($timeArr[0]);
            $endTime = strtotime($timeArr[1]);
            $timeValue = date('Y', $startTime) . L('年') . date('m', $startTime) . L('月') . date('d', $startTime) . L('日') . date(' H:i:s', $startTime) . '-' . date('Y', $endTime) . L('年') . date('m', $endTime) . L('月') . date('d', $endTime) . L('日') . date(' H:i:s', $endTime);
        } else {
            $timeValueArr = explode('-', $couponData['used_time_value']);
            $timeValue = L('收到后') . (int)$timeValueArr[0] . L('天') . (int)$timeValueArr[1] . L('小时') . (int)$timeValueArr[2] . L('分内');
        }
        $thresholdVal = L($this->_commData['threshold'][$couponData['threshold']]['CD_VAL']);
        if ($couponData['threshold'] == 1) {
            $thresholdVal = L('满') . sprintf('%0.2f', $couponData['threshold_condition']) . L('金额') . '(USD)';
        }
        if ($couponData['coupon_type'] == 'N001870100') {
            $couponTypeVal = '【' . L($this->_dicData[DictionaryModel::COUPON_TYPE][$couponData['coupon_type']]['CD_VAL']) . '】 ' . L('抵扣金额') . sprintf('%0.2f', $couponData['max_amount']) . 'USD';
        } else {
            $couponTypeVal = '【' . L($this->_dicData[DictionaryModel::COUPON_TYPE][$couponData['coupon_type']]['CD_VAL']) . '】 ' . L('折扣比例') . sprintf('%0.2f', $couponData['proportion'] / 100) . ' ' . L('最高优惠金额') . sprintf('%0.2f', $couponData['max_amount']) . 'USD';
        }
        $this->assign('couponTypeVal', $couponTypeVal);
        $this->assign('thresholdVal', $thresholdVal);
        $this->assign('timeValue', $timeValue);
        $this->assign('onceSendObject', $this->_dicData[DictionaryModel::COUPON_ONCE_SEND_OBJECT]);
        $this->assign('continuedSendObject', $this->_dicData[DictionaryModel::COUPON_CONTINUED_SEND_OBJECT]);
        $this->assign('couponType', $this->_dicData[DictionaryModel::COUPON_TYPE]);
        $this->assign('shopData', $shopData);
        $this->assign('commData', $this->_commData);
        $this->assign('couponData', $couponData);
        $this->assign('shopData', $shopData);
        $this->assign('couponOptData', $couponOptData);
        $this->assign('optTypeData', $this->_OPT_DATA);
        $this->display('detail');
    }

    /**
     * 优惠券添加详情
     */
    public function addPage()
    {
        $shopData = $this->_couponModel->getShopData();
        $this->assign('onceSendObject', $this->_dicData[DictionaryModel::COUPON_ONCE_SEND_OBJECT]);
        $this->assign('continuedSendObject', $this->_dicData[DictionaryModel::COUPON_CONTINUED_SEND_OBJECT]);
        $this->assign('couponType', $this->_dicData[DictionaryModel::COUPON_TYPE]);
        $this->assign('commData', $this->_commData);
        $this->assign('shopData', $shopData);
        $this->display('add');
    }

    /**
     * 优惠券修改页面
     */
    public function changePage()
    {
        $couponGudsModel = D('@Model/Coupon/CouponGuds');
        $couponUserModel = D('@Model/Coupon/CouponUser');
        $shopData = $this->_couponModel->getShopData();
        $id = I('get.id', 0);
        $users = $products = '';
        $couponData = $this->_couponModel->getCouponDetail($id);
        $key_name = ["id", "title", "couponType", "status", "creatorName", "creatorId", "sendWay", "sendObject",
            "orderNum",
            "shop",
            "threshold",
            "threshold_condition",
            "proportion",
            "maxAmount",
            "usedTimeType",
            "usedTimeValue",
            "uperpositionRule",
            "useRange",
            "userTotal",
            "gudsTotal",
            "addTime",
            "startTime",
            "updateUime"];
        $jsonData = json_encode(array_combine($key_name, $couponData));
        if (empty($couponData)) {
            die('优惠券不存在');
        }
        if ($couponData['send_way'] == 1 && $couponData['send_object'] != 'N001850100') {
            $userDatas = $couponUserModel->getUserDataByCouponId($couponData['id']);
            if (!empty($userDatas)) {
                foreach ($userDatas as $value) {
                    $users .= $value['user_email'] . ',';
                }
                $users = trim($users, ',');
            }
        }
        if ($couponData['use_range'] == 2) {
            $productDatas = $couponGudsModel->getProductIds($couponData['id']);
            if (!empty($productDatas)) {
                foreach ($productDatas as $value) {
                    $products .= $value['guds_id'] . ',';
                }
                $products = trim($products, ',');
            }
        }
        $this->assign('onceSendObject', $this->_dicData[DictionaryModel::COUPON_ONCE_SEND_OBJECT]);
        $this->assign('continuedSendObject', $this->_dicData[DictionaryModel::COUPON_CONTINUED_SEND_OBJECT]);
        $this->assign('couponType', $this->_dicData[DictionaryModel::COUPON_TYPE]);
        $this->assign('commData', $this->_commData);
        $this->assign('shopData', $shopData);
        $this->assign('couponData', $couponData);
        $this->assign('products', $products);
        $this->assign('users', $users);
        $this->assign('jsonData', $jsonData);
        $this->display('change');
    }

    /**
     * 优惠券添加数据
     */
    public function doCouponAdd()
    {
        $couponGudsModel = D('@Model/Coupon/CouponGuds');
        $couponUserModel = D('@Model/Coupon/CouponUser');
        //$params = file_get_contents('php://input');
        $data = $params = $_REQUEST;//json_decode($params,true);
        $result = $this->_result;
        if (empty($params['title']) || empty($params['shop']) || empty($params['sendObject']) || empty($params['sendWay'])) {
            $result['code'] = '400000080';
            $result['msg'] = L('非法操作');
            $this->jsonOut($result);
        }
        if (($params['sendObject'] == 'N001850200' || $params['sendObject'] == 'N001850300') && empty($params['users'])) {
            $result['code'] = '400000000';
            $result['msg'] = L('没有选择发放用户');
            $this->jsonOut($result);
        }
        if ($params['useRange'] == '2' && empty($params['products'])) {
            $result['code'] = '400000001';
            $result['msg'] = L('没有选择对应商品');
            $this->jsonOut($result);
        }
        $data['status'] = 0;#添加优惠券默认是关闭的
        $data['creatorName'] = $_SESSION['m_loginname'];
        $data['creatorId'] = $_SESSION['user_id'];
        $data['title'] = $params['title'];
        $data['timeType'] = $params['timeType'];
        $data['timeValue'] = $params['timeType'] == 1 ? $params['start_time'] . '_' . $params['end_time'] : $params['timeValue'] . '-' . (int)$params['timeHourValue'] . "-" . (int)$params['timeMinuteValue'];
        $data['orderNum'] = empty($params['orderNum']) ? 0 : $params['orderNum'];
        $data['proportion'] = empty($params['proportion']) ? 0 : $params['proportion'];
        $data['thresholdCondition'] = empty($params['thresholdCondition']) ? 0 : $params['thresholdCondition'];
        $data['addTime'] = time();
        $data['userTotal'] = $data['gudsTotal'] = 0;
        //一次性发放,全部用户
        if ($params['sendWay'] == 1 && $params['sendObject'] == 'N001850100') {
            $data['userTotal'] = (int)$params['users'];
        }
        //全部商品
        if ($params['useRange'] == 1) {
            $data['gudsTotal'] = (int)$params['products'];
        }
        $couponId = $this->_couponModel->addCouponData($data);
        if (empty($couponId)) {
            $result['code'] = '400000002';
            $result['msg'] = L('添加失败');
            $this->jsonOut($result);
        }
        if ($params['sendObject'] == 'N001850200' || $params['sendObject'] == 'N001850300') {
            $userParams = explode(',', $params['users']);
            $couponUserModel->batchAddCouponUserData($userParams, $couponId);
        }
        if ($params['useRange'] == '2') {
            $gudsParams = explode(',', $params['products']);
            $couponGudsModel->batchAddCouponGudsData($gudsParams, $couponId);
        }
        $result['code'] = '2000';
        $result['msg'] = L('成功');
        $result['data'] = $couponId;
        $this->jsonOut($result);


    }

    /**
     * 优惠券修改数据
     */
    public function updateCoupon()
    {
        $couponOptModel = D('@Model/Coupon/CouponOpt');
        $couponGudsModel = D('@Model/Coupon/CouponGuds');
        $couponUserModel = D('@Model/Coupon/CouponUser');
        $data = $params = $_REQUEST;
        $couponId = $params['id'];
        $couponData = $this->_couponModel->getCouponDetail($couponId);
        $result = $this->_result;
        if (empty($couponData) || !empty($couponData['start_time'])) {
            $result['code'] = '400000080';
            $result['msg'] = L('非法操作');
            $this->jsonOut($result);
        }
        if (($params['sendObject'] == 'N001850200' || $params['sendObject'] == 'N001850300') && empty($params['users'])) {
            $result = $this->_result;
            $result['code'] = '400000000';
            $result['msg'] = L('没有选择发放用户');
            $this->jsonOut($result);
        }
        if ($params['useRange'] == '2' && empty($params['products'])) {
            $result = $this->_result;
            $result['code'] = '400000001';
            $result['msg'] = L('没有选择对应商品');
            $this->jsonOut($result);
        }
        //一次性发放,全部用户
        if ($params['sendWay'] == 1 && $params['sendObject'] == 'N001850100') {
            $data['userTotal'] = (int)$params['users'];
        }
        //全部商品
        if ($params['useRange'] == 1) {
            $data['gudsTotal'] = (int)$params['products'];
        }
        $data['timeValue'] = $params['timeType'] == 1 ? $params['start_time'] . '_' . $params['end_time'] : $params['timeValue'] . '-' . (int)$params['timeHourValue'] . "-" . (int)$params['timeMinuteValue'];
        $data['updateTime'] = time();
        $data['couponId'] = $couponId;
        $updateResult = $this->_couponModel->updateCouponData($data);
        if ($updateResult === false) {
            $result['code'] = '400000080';
            $result['msg'] = L('操作失败');
            $this->jsonOut($result);
        }
        #修改数据去除旧数据，生成新数据
        if ($params['sendObject'] == 'N001850200' || $params['sendObject'] == 'N001850300') {
            $couponUserModel->delUserData($couponId);
            $userParams = explode(',', $params['users']);
            $couponUserModel->batchAddCouponUserData($userParams, $couponId);
        }
        #修改数据去除旧数据，生成新数据
        if ($params['useRange'] == '2') {
            $couponGudsModel->delGudsData($couponId);
            $gudsParams = explode(',', $params['products']);
            $couponGudsModel->batchAddCouponGudsData($gudsParams, $couponId);
        }
        $optParams['optType'] = 'mod';
        $optParams['couponId'] = $params['id'];
        $optParams['optUserName'] = $_SESSION['m_loginname'];
        $optParams['optUserId'] = $_SESSION['user_id'];
        $couponOptModel->addCouponOptData($optParams);
        $result['code'] = '2000';
        $result['msg'] = L('成功');
        $result['data'] = $updateResult;
        $this->jsonOut($result);

    }

    /**
     * 操作优惠券开启和关闭
     */
    public function operateCoupon()
    {
        $couponOptModel = D('@Model/Coupon/CouponOpt');
        $couponGudsModel = D('@Model/Coupon/CouponGuds');
        $couponUserModel = D('@Model/Coupon/CouponUser');
        $couponProcessRecordModel = D('@Model/Coupon/CouponProcessRecord');
        $couponId = I('get.id');
        $type = I('get.type');
        $couponData = $this->_couponModel->getCouponDetail($couponId);
        $result = $this->_result;
        if (empty($couponData)) {
            $result['code'] = '400000050';
            $result['msg'] = L('数据错误');
            $this->jsonOut($result);
        }
        $params['startTime'] = time();
        if ($type == 'start') {
            $params['status'] = 1;
            $params['optType'] = 'start';
        }
        if ($type == 'stop') {
            $params['status'] = 0;
            $params['optType'] = 'stop';
        }
        $params['couponId'] = $couponId;
        $optResult = $this->_couponModel->updateCouponData($params);
        if ($optResult === false) {
            $result['code'] = '400000060';
            $result['msg'] = L('操作失败');
            $this->jsonOut($result);
        }
        $params['optUserName'] = $_SESSION['m_loginname'];
        $params['optUserId'] = $_SESSION['user_id'];
        $couponOptModel->addCouponOptData($params);
        $products = $users = [];
        $pushData = $couponData;
        if ($couponData['send_way'] == 1 && $couponData['send_object'] != 'N001850100') {
            $userDatas = $couponUserModel->getUserDataByCouponId($couponData['id']);
            if (!empty($userDatas)) {
                foreach ($userDatas as $value) {
                    $users[] = $value['user_email'];
                }
            }
        } else {
            $users[] = empty($couponData['user_total']) ? [] : $couponData['user_total'];
        }
        if ($couponData['use_range'] == 2) {
            $productDatas = $couponGudsModel->getProductIds($couponData['id']);
            if (!empty($productDatas)) {
                foreach ($productDatas as $value) {
                    $products[] = $value['guds_id'];
                }
            }
        } else {
            $products[] = empty($couponData['guds_total']) ? [] : $couponData['guds_total'];
        }
        $pushData['status'] = $params['status'];
        $pushData['users'] = $users;
        $pushData['products'] = $products;
        $platArr = $this->_couponModel->getPlatCdByStore($couponData['shop']);
        #$pushData['plat'] = array_keys($platArr);
        foreach ($platArr as $plat => $value) {
            $recordData = $couponProcessRecordModel->getCouponProcessRecordByCondition($couponData['id'], $plat);
            !empty($recordData['thr_id']) ? $pushData['thrId'] = $recordData['thr_id'] : 0;
            $data = $this->assembleSendData($pushData, $plat);
            $postData = json_encode($data);
            $url = $this->_API_URL;
            $responseData = curl_get_json($url, $postData);
            $this->doLog($postData, $responseData);
            $responseData = json_decode($responseData, true);
            $recordParams['thrdId'] = $responseData['data']['coupons'][0]['thrdId'];
            $recordParams['couponId'] = $couponData['id'];/*$responseData['data']['coupons'][0]['id']*/
            $recordParams['platCode'] = $plat;
            if ($responseData['code'] == 2000 && !empty($responseData['data']['coupons'][0]['thrdId'])) {
                if (empty($recordData['coupon_id'])) {
                    $couponProcessRecordModel->addCouponProcessRecordData($recordParams);
                } else {
                    $couponProcessRecordModel->updateCouponProcessRecordData($recordParams);
                }
            }

        }

        $result['code'] = '2000';
        $result['msg'] = L('成功');
        $result['data'] = $optResult;
        $this->jsonOut($result);
    }

    /**
     * 获取用户数据
     */
    public function getUserData()
    {
        $params = file_get_contents('php://input');
        $params = json_decode($params, true);
        #$params['shop'] = [23,66];
        $platArr = $this->_couponModel->getPlatCdByStore($params['shop']);
        $params['plat'] = array_keys($platArr);
        $couponUserModel = D('@Model/Coupon/CouponUser');
        if ($params['type'] == 'all') {
            $data['total'] = $couponUserModel->getGshopperUserTotal($params);
        } else {
            $platData = $this->_couponModel->getPlatData();
            $page = (int)$params['page'];
            $limit = empty($params['pageNum']) ? 10 : $params['pageNum'];
            $page = empty($page) ? 1 : $page;
            $start = ($page - 1) * $limit;
            $data['page'] = $page;
            $data['pageNum'] = $limit;
            $data['total'] = $couponUserModel->getGshopperUserData($params, 'count');
            $data['totalPage'] = ceil($data['total'] / $limit);
            $data['list'] = $couponUserModel->getGshopperUserData($params, 'list', $start, $limit);
            foreach ($data['list'] as $key => $val) {
                $data['list'][$key]['plat'] = $platData[$val['parent_plat_cd']];
            }
        }
        $result = $this->_result;
        $result['code'] = '2000';
        $result['msg'] = L('成功');
        $result['data'] = $data;
        $this->jsonOut($result);
    }

    /**
     * 获取csv用户数据
     */
    public function getExcelData()
    {
        $email = $mobile = $fileData = [];
        if (!empty($_FILES)) {
            $uploadModel = new FileUploadModel();
            $uploadModel->filePath = $this->_filePath;
            $uploadModel->fileExts = ['csv'];
            $fileName = $uploadModel->fileUploadExtend();
        };
        #$fileName = 'demo1.csv';
        $file = $this->_filePath . $fileName;
        if (!is_file($file)) {
            $result = $this->_result;
            $result['code'] = '400000010';
            $result['msg'] = '文件不存在';
            $this->jsonOut($result);
        }
        $file = fopen($file, "r");
        while (!feof($file)) {
            $fileData[] = fgetcsv($file);
        }
        fclose($file);
        #csv存放手机号 和  email
        foreach ($fileData as $key => $value) {
            if ($key == 0 || empty($value[0])) {
                continue;
            }
            $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
            if (preg_match($pattern, $value[0])) {
                $email[] = $value[0];
            } else {
                $mobile[] = $value[0];
            }

        }
        if (empty($mobile) && empty($email)) {
            $result = $this->_result;
            $result['code'] = '400000020';
            $result['msg'] = '用户数据不存在';
            $this->jsonOut($result);
        }
        $params = $_REQUEST;
        $platArr = $this->_couponModel->getPlatCdByStore($params['shop']);
        $plat = array_keys($platArr);
        $couponUserModel = D('@Model/Coupon/CouponUser');
        $data['list'] = $couponUserModel->getGshopperUserDataByEmailOrMobile($plat, $email, $mobile);
        if (empty($data['list'])) {
            $result = $this->_result;
            $result['code'] = '400000030';
            $result['msg'] = '用户数据不产符合店铺条件';
            $this->jsonOut($result);
        }
        $data['total'] = count($data['list']);
        $result = $this->_result;
        $result['code'] = '2000';
        $result['msg'] = L('成功');
        $result['data'] = $data;
        $this->jsonOut($result);
    }

    /**
     * 获取商品数据
     */
    public function getGudsData()
    {
        $params = file_get_contents('php://input');
        $params = json_decode($params, true);
        $couponGudsModel = D('@Model/Coupon/CouponGuds');
        if ($params['type'] == 'all') {
            $data['total'] = $couponGudsModel->getGudsTotal($params);
        } else {
            $limit = empty($params['pageNum']) ? 10 : $params['pageNum'];
            $page = empty($params['page']) ? 1 : $params['page'];
            $start = ($page - 1) * $limit;
            $data['page'] = $page;
            $data['pageNum'] = $limit;
            $data['total'] = $couponGudsModel->getGudsData($params, 'count');
            $data['totalPage'] = ceil($data['total'] / $limit);
            $data['list'] = $couponGudsModel->getGudsData($params, 'list', $start, $limit);
        }
        $result = $this->_result;
        $result['code'] = '2000';
        $result['msg'] = L('成功');
        $result['data'] = $data;
        $this->jsonOut($result);
    }

    /**
     * 下载文件
     */
    public function downloadFile()
    {
        $filename = $this->_filePath . I('get.fileName');
        $fileinfo = pathinfo($filename);
        header('Content-type: application/x-' . $fileinfo['extension']);
        header('Content-Disposition: attachment; filename=' . $fileinfo['basename']);
        header('Content-Length: ' . filesize($filename));
        readfile($filename);
        exit();
    }

    /**
     * 显示优惠券关联用户信息
     */
    public function showUserList()
    {
        $params = file_get_contents('php://input');
        $params = json_decode($params, true);
        //$params = $_REQUEST;
        $couponUserModel = D('@Model/Coupon/CouponUser');
        $platData = $this->_couponModel->getPlatData();
        $limit = empty($params['pageNum']) ? 10 : $params['pageNum'];
        $page = empty($params['page']) ? 1 : $params['page'];
        $start = ($page - 1) * $limit;
        $data['page'] = $page;
        $data['pageNum'] = $limit;
        $data['total'] = $couponUserModel->getUserInfo($params, 'count');
        $data['totalPage'] = ceil($data['total'] / $limit);
        $data['list'] = $couponUserModel->getUserInfo($params, 'list', $start, $limit);
        foreach ($data['list'] as $key => $val) {
            $data['list'][$key]['plat'] = $platData[$val['parent_plat_cd']];
        }
        $result = $this->_result;
        $result['code'] = '2000';
        $result['msg'] = L('成功');
        $result['data'] = $data;
        $this->jsonOut($result);
    }

    /**
     * 显示优惠券关联商品信息
     */
    public function showGudsList()
    {
        $params = file_get_contents('php://input');
        $params = json_decode($params, true);
        #$params = $_REQUEST;
        $couponGudsModel = D('@Model/Coupon/CouponGuds');
        $limit = empty($params['pageNum']) ? 10 : $params['pageNum'];
        $page = empty($params['page']) ? 1 : $params['page'];
        $start = ($page - 1) * $limit;
        $data['page'] = $page;
        $data['pageNum'] = $limit;
        $data['total'] = $couponGudsModel->getGudsInfo($params, 'count');
        $data['totalPage'] = ceil($data['total'] / $limit);
        $data['list'] = $couponGudsModel->getGudsInfo($params, 'list', $start, $limit);
        $result = $this->_result;
        $result['code'] = '2000';
        $result['msg'] = L('成功');
        $result['data'] = $data;
        $this->jsonOut($result);
    }

    /**
     * 组装推送到Mq数据
     */
    public function assembleSendData($data, $plat)
    {
        $arr['code'] = null;
        $arr['data']['coupons'][] = [
            'id' => $data['id'],
            'title' => $data['title'],
            'status' => empty($data['status']) ? false : true,
            'sendWay' => $data['send_way'],
            'sendObject' => $data['send_object'],
            'orderNum' => (int)$data['order_num'],
            'users' => $data['users'],
            'shop' => explode(',', $data['shop']),
            'threshold' => empty($data['threshold']) ? false : true,
            'thresholdCondition' => $data['threshold_condition'],
            'couponType' => $data['coupon_type'],
            'proportion' => $data['proportion'],
            'maxAmount' => $data['max_amount'],
            'usedTimeType' => $data['used_time_type'],
            'usedTimeValue' => $data['used_time_value'],
            'superpositionRule' => empty($data['superposition_rule']) ? false : true,
            'useRange' => $data['use_range'],
            'products' => $data['products'],
            'thrId' => $data['thrId']
        ];
        $arr["msg"] = null;
        $arr["platCode"] = $plat;
        $arr["processCode"] = "TB_GS_COUPON";
        $arr["processId"] = create_guid();
        return $arr;
    }

    /**
     * 记录优惠券发送日志
     */
    public function doLog($requestData = null, $responseData = null)
    {
        $logFilePath = RUNTIME_PATH . 'Logs/';
        $logName = date('Y-m-d') . '_sendCoupon.log';
        $txt = date('Y-m-d H:i:s') . ' requestData =>' . $requestData . "\n\r" . "responseData=>" . $responseData;
        $file = $logFilePath . $logName;
        fclose(fopen($file, 'a+'));
        $_fo = fopen($file, 'rb');
        fclose($_fo);
        file_put_contents($file, $txt . "\n");
    }

}