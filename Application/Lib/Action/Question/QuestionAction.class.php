<?php

/**
 * Created by PhpStorm.
 * User: lscm
 * Date: 2017/10/20
 * Time: 10:57
 */
class QuestionAction extends BaseAction
{
    private $_questionModel, $_questionDetailModel, $_questionLogModel;
    /**
     * @var DictionaryModel
     */
    private $_dictionary;
    Private $_filePath = '/opt/b5c-disk/question/';/*'Application/Tpl/Home/Public/images/question/';*/
    private $_PROJECT_TYPE = ['1' => '禅道需求编号', '2' => '禅道BUG编号'];

    private $_CONFIG = [
        "N001760100" => ['title'=>'已收到来自@@user@@，@@emailtitle@@的反馈','email' => '您的意见已成功反馈，我们将尽快安排处理。感谢您的反馈，您的支持是我们最大的动力!', 'page' => '您的意见已成功受理，我们正安排处理中。 感谢您的反馈，您的支持是我们最大的动力!', 'name' => '', 'time' => '', 'style' => 'process-state','name'=>''],
        "N001760200" => ['title'=>'已处理来自@@user@@，@@emailtitle@@的反馈','email' => '您的意见已成功受理，我们正安排处理中。 感谢您的反馈，您的支持是我们最大的动力!', 'page' => '您反馈的问题已解决！感谢您的反馈，您的支持是我们最大的动力!', 'name' => '处理人', 'time' => '受理时间', 'style' => 'process-state','name'=>'受理'],
        "N001760300" => ['title'=>'已完成来自@@user@@，@@emailtitle@@的反馈','email' => '您反馈的问题已解决！感谢您的反馈，您的支持是我们最大的动力!', 'page' => '', 'name' => '结案人', 'time' => '完成时间', 'style' => 'complete-state','name'=>'结案'],
        "N001760400" => ['title'=>'已完成来自@@user@@，@@emailtitle@@的反馈','email' => '', 'page' => '', 'name' => '结案人', 'time' => '完成时间', 'style' => 'pending-state','name'=>'驳回']];
    private $_EMAILTEMPLATE = '';
    /*private $_MODULE_USER = [
        'pay' => ['module' => '支付管理', 'useName' => 'yuwei',],
        'orders' => ['module' => '订单管理', 'useName' => 'muxia',],
        'data' => ['module' => '基础资料', 'useName' => 'huali',],
        'stock' => ['module' => '库存管理', 'useName' => 'feisong',],
        'customer' => ['module' => '客户管理', 'useName' => 'huali',],
        'coupon' => ['module' => '营销管理', 'useName' => 'huali',],
        'orderdetail' => ['module' => '采购模块', 'useName' => 'huali',],
        'supplier' => ['module' => '供应商管理', 'useName' => 'huali',],
        'apprecord' => ['module' => 'app模块', 'useName' => 'huali',],
        'dynamic' => ['module' => '动销分析', 'useName' => 'huali',],
        'b2b' => ['module' => 'B2B订单管理', 'useName' => 'huali',],
        'btbcustomermanagement' => ['module' => 'B2B客户管理', 'useName' => 'huali',],
        'contract' => ['module' => '合同台账', 'useName' => 'huali',],
        'guds_guds' => ['module' => '商品管理', 'useName' => 'yuwei',],
        'hr' => ['module' => '人事管理', 'useName' => 'huali',],
        'store' => ['module' => '店铺管理', 'useName' => 'huali',],
        'logistics' => ['module' => '物流管理', 'useName' => 'huali',],];*/
    private $_EMAIL_ADDRESS = [
        "pm@gshopper.com",
    ];

    public function saveConfig()
    {
        try {
            $request_data = DataModel::getDataNoBlankToArr();
            if ($request_data) {
                $this->validateConfigData($request_data);
                $res = DataModel::$success_return;
                $res['code'] = 200;
                $questionService = new QuestionService();
                $questionService->saveQuestionConfig($request_data);   
            }
        } catch (Exception $exception) {
            $res = $this->catchException($exception);
        }
        $this->ajaxReturn($res);
    }

    private function validateConfigData($data)
    {
        $rules = [];
        foreach ($data as $key => $value) {
            $rules["{$key}.type"] = 'required|numeric';
            $rules["{$key}.user_id"] = 'required|numeric';
        }
        
        $attributes = [
            'type' => '岗位类型',
            'user_id' => '用户id',
        ];
        $this->validate($rules, $data, $attributes);
    }

    public function getDefaultUser()
    {
        try {
            $questionService = new QuestionService();
            $res = $questionService->getDefaultUserInfo();
            $response = $this->formatOutput('2000', 'success', $res);
        }  catch (Exception $e) {
            $info = $e->getMessage();
            $response = $this->formatOutput('3003', $info);
        }
        return $this->ajaxReturn($response, 'json');
    }

    // 获取下拉数据（产品/技术/测试）
    public function getUserType()
    {
        try {
            $get = I('get.');
            if (!$get['type']) {
                throw new Exception("缺少必传参数type");
            }
            $questionService = new QuestionService();
            $res = $questionService->getUserByWx($get['type']);
            $response = $this->formatOutput('2000', 'success', $res);
        }  catch (Exception $e) {
            $info = $e->getMessage();
            $response = $this->formatOutput('3003', $info);
        }
        return $this->ajaxReturn($response, 'json');
    }

    // 工单详情中，底部各种按钮点击提交的统一接口
    public function buttonSave()
    {
        // 权限校验（前端处理）
        try {
            $post = $this->jsonParams();
            if (!$post['id'] || !$post['type']) {
                throw new Exception("缺少必传参数id或type");
            }
            $questionService = new QuestionService();

            $post['info'] = $this->_questionModel->getQuestionDetail($post['id']);// 获取该工单的详细信息
            # 问题已关闭 无法继续操作
            if($post['info']['status'] == $questionService::$STATUS_CLOSED) {
                return $this->ajaxError([],"问题已被关闭，无法继续操作");
            }
            $model_type = $questionService::$_MODULE_INFO[$post['type']]['module']; // 根据按钮获取对应的model
            $strategy = "\\Question". $model_type;
            $strategy = new $strategy;

            $res = $strategy->dealWithButton($post);

            $response = $this->formatOutput('2000', 'success', $res);
        }  catch (Exception $e) {
            $info = $e->getMessage();
            $response = $this->formatOutput('3003', $info);
        }
        return $this->ajaxReturn($response, 'json');


    }

    public function _initialize()
    {
        import('ORG.Util.Page');// 导入分页类
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
        $this->_dictionary = new DictionaryModel();
        $this->_questionModel = D('@Model/Question/Question');
        $this->_questionDetailModel = D('@Model/Question/QuestionDetail');
        $this->_questionLogModel = D('@Model/Question/QuestionLog');
        //parent::_initialize();
        $this->_EMAILTEMPLATE = "<table cellspacing='0' cellpadding='0' style='width: 100%;max-width:1000px;margin-top:20px;border:1px solid #cccccc;'>                          
                                <tr>
                                <td style='padding-left:12px;border-bottom:1px solid #cccccc;border-right:1px solid #cccccc; text-align: left;height: 50px;color: #333333;font-size:14px;background: #F2F2F2;width:100px;'>" . L('编号') . "</td>
                                <td style=' padding-left:12px;border-bottom:1px solid #cccccc; text-align: left;height: 50px;font-size: 13px;text-align: left;'>@@id@@</td>                    
                                </tr>
                                <tr>
                                <td style='padding-left:12px;border-bottom:1px solid #cccccc;border-right:1px solid #cccccc; text-align: left;height: 50px;color: #333333;font-size:14px;background: #F2F2F2;width:100px;'>" . L('标题') . "</td>
                                <td style='padding-left:12px;border-bottom:1px solid #cccccc;text-align: left;height: 50px;font-size: 13px;text-align: left;'>@@title@@</td>                    
                                </tr>
                                <tr>
                                <td style='padding-left:12px;border-bottom:1px solid #cccccc;border-right:1px solid #cccccc;text-align: left;height: 50px;color: #333333;font-size:14px;background: #F2F2F2;width:100px;'>" . L('所在模块') . "</td>
                                 <td style='padding-left:12px;border-bottom:1px solid #cccccc;text-align: left;height: 50px;font-size: 13px;text-align: left;'>@@module@@</td>                    
                                </tr>
                                <tr>
                                <td style='padding-left:12px;border-bottom:1px solid #cccccc;border-right:1px solid #cccccc;text-align: left;height: 50px;color: #333333;font-size:14px;background: #F2F2F2;width:100px;'>" . L('用户') . "</td>
                                 <td style='padding-left:12px;border-bottom:1px solid #cccccc;text-align: left;height: 50px;font-size: 13px;text-align: left;'>@@user@@</td>                    
                                </tr>
                                <tr>
                                <td style='padding-left:12px;border-bottom:1px solid #cccccc;border-right:1px solid #cccccc;text-align: left;height: 50px;color: #333333;font-size:14px;background: #F2F2F2;width:100px;'>" . L('处理人') . "</td>
                                <td style='padding-left:12px;border-bottom:1px solid #cccccc;text-align: left;height: 50px;font-size: 13px;text-align: left;'>@@optUser@@</td>                    
                                </tr>
                                <tr>
                                <td style='padding-left:12px;border-bottom:1px solid #cccccc;border-right:1px solid #cccccc;text-align: left;height: 50px;color: #333333;font-size:14px;background: #F2F2F2;width:100px;'>" . L('有效性') . "</td>
                                 <td style='padding-left:12px;border-bottom:1px solid #cccccc;text-align: left;height: 50px;font-size: 13px;text-align: left;'>@@validity@@</td>                    
                                </tr>
                                <tr>
                                 <td style='padding-left:12px;border-bottom:1px solid #cccccc;border-right:1px solid #cccccc;text-align: left;height: 50px;color: #333333;font-size:14px;background: #F2F2F2;width:100px;'>" . L('状态') . "</td>
                                <td style='padding-left:12px;border-bottom:1px solid #cccccc;text-align: left;height: 50px;font-size: 13px;text-align: left;'>@@status@@</td>                    
                                </tr>
                                <tr>
                                <td style='padding-left:12px;border-bottom:1px solid #cccccc;border-right:1px solid #cccccc;text-align: left;height: 50px;color: #333333;font-size:14px;background: #F2F2F2;width:100px;'>@@tmeName@@</td>
                                <td style='padding-left:12px;border-bottom:1px solid #cccccc;text-align: left;height: 50px;font-size: 13px;text-align: left;'>@@time@@</td>                    
                                </tr>
                                @@reply@@
                                </table>";
        if (empty($_SESSION['m_loginname']) || empty($_SESSION['user_id'])) {
            redirect('index.php?m=public&a=login');
        }
    }

    public function index()
    {
        $moduleName = '';
        $questionType = $this->_dictionary->getDictionary(DictionaryModel::QUESTION_TYPE_PREFIX);
        foreach ($questionType as $k => $v) {
            $questionType[$k]['CD_VAL'] = L($v['CD_VAL']);
        }
        $pageUrl = $_COOKIE['questionUrl'];
        if (!empty($pageUrl)) {
            $strArr = parse_url($pageUrl);
            parse_str($strArr['query'], $strArr);
            $moduleName = empty($strArr['g']) ? $strArr['m'] : $strArr['g'] . '_' . $strArr['m'];
        }
        //$questionStatus = $this->_directory->getQuestionStatus('CD', '');
        //$tempArr = $this->getQuestionRoleId();
        //$rightsUser = $this->_questionModel->getQuestionDealWithUser($tempArr);
        $moduleList = $this->dealWithModule();
        $this->assign('pageUrl', $pageUrl);
        $this->assign('questionType', $questionType);
        //$this->assign('questionStatus', $questionStatus);
        $this->assign('moduleList', $moduleList);
        //$this->assign('rightsUser', $rightsUser);
        $this->assign('moduleName', $moduleName);
        $this->display('index');
    }

    // 工单列表
    public function questionList()
    {
        $questionStatus = $this->_dictionary->dealWithDateToKeyVal($this->_dictionary->getDictionary(DictionaryModel::QUESTION_DEALWITH_STATUS_PREFIX), 'CD', 'CD_VAL');
        $moduleList = $this->dealWithModule();
        $validityData = $this->_dictionary->getDictionary(DictionaryModel::QUESTION_VALIDITY_PREFIX);
        $status = !empty($_REQUEST['status']) ? $_REQUEST['status'] : '';

        #管理员查看all
        $bool = $this->isQuestionDealWithUser();

        $params['id'] = !empty($_REQUEST['id']) ? $_REQUEST['id'] : ''; //id
        if (strpos($params['id'], 'Q ') !== false) {
            //问题编号有Q前缀
            $params['id'] = substr($params['id'], 2);
        }
        $params['title'] = !empty($_REQUEST['title']) ? $_REQUEST['title'] : ''; //标题
        $params['module'] = !empty($_REQUEST['module']) ? $_REQUEST['module'] : '';
        $params['startTime'] = !empty($_REQUEST['startTime']) ? $_REQUEST['startTime'] : '';
        $params['endTime'] = !empty($_REQUEST['endTime']) ? $_REQUEST['endTime'] : '';
        //默认当前用户 初始化没有userName参数
        if (!isset($_REQUEST['userName'])) {
            $params['userName'] = !empty($_REQUEST['userName']) ? $_REQUEST['userName'] : $_SESSION['user_id'];
        } else {
            $params['userName'] = !empty($_REQUEST['userName']) ? $_REQUEST['userName'] : '';
        }
        $params['handler'] = !empty($_REQUEST['handler']) ? $_REQUEST['handler'] : '';
        $params['status'] = $status;
        $params['hist_handler'] = !empty($_REQUEST['hist_handler']) ? $_REQUEST['hist_handler'] : '';
        $page_num = empty($params['pageNum']) ? 20 : $params['pageNum'];
        $count = $this->_questionModel->getQuestionListByPage($params, 'count');
        $Page = new Page($count, $page_num);
        $show = $Page->show();
        $questionData = $this->_questionModel->getQuestionListByPage($params, 'list', $Page->firstRow, $Page->listRows);
        $question_ids = array_column($questionData, 'id');
        $detailData = $this->_questionLogModel->getQuestionLogByQuestionIds($question_ids);
        $remarks = array_column($detailData, 'remark', 'question_id');
        foreach ($questionData as $key => &$item) {
            $item['last_notes'] = isset($remarks[$item['id']]) ? $remarks[$item['id']] : '';
        }

        $linkParams = http_build_query($params);
        //var_dump($linkParams);die;
        $this->assign('page', $show);
        $this->assign('total', $count);
        $this->assign('questionData', $questionData);
        $this->assign('id', $_REQUEST['id'] ? $_REQUEST['id'] : '' );
        $this->assign('title', $params['title']);
        $this->assign('startTime', $params['startTime']);
        $this->assign('endTime', $params['endTime']);
        $this->assign('moduleName', $params['module']);
        $this->assign('userName', $params['userName']);
        $this->assign('handler', $params['handler']);
        $this->assign('hist_handler', $params['hist_handler']);
        $this->assign('statusData', $questionStatus);
        $this->assign('status', $status);
        $this->assign('moduleList', $moduleList);
        $this->assign('linkParams', $linkParams);
        $this->assign('config', $this->_CONFIG);
        $this->assign('isQuestionDealWithUser', $bool);
        $this->assign('validityData', $validityData);
        $this->display('list');
    }

    // 工单配置页面
    public function config()
    {
        $this->display('config');
    }

    // 工单详情
    public function questionDetail()
    {
        $viewStr = 'detail';
        $question['question_desc'] = '';
        #管理员查看
        $bool = $this->isQuestionDealWithUser();
        $id = (int)I('get.id', 0);
        $data = $this->_questionModel->getQuestionDetail($id, 0);
        $detailData = $this->_questionDetailModel->getQuestionDetailList($id);
        $moduleList = $this->dealWithModule();
        $detailData['module_name'] = $moduleList[$detailData['module_name']];

        $validityData = $this->_dictionary->getDictionary(DictionaryModel::QUESTION_VALIDITY_PREFIX);
        $questionType = $this->_dictionary->getDictionary(DictionaryModel::QUESTION_TYPE_PREFIX);

        foreach ($questionType as $k => $v) {
            $questionType[$k]['CD_VAL'] = L($v['CD_VAL']);
        }
        $moduleUser = $_SESSION['m_loginname'];


        // 获取该登录用户的权限（工单创建者，当前工单应处理人，其他人）
        $authorizationType = [];
        if ($moduleUser === $data['question_user_name']) { // 工单创建者
            $authorizationType['asker'] = true;
        }
        if (strstr($data['opt_user_name'], $moduleUser)) { // 当前工单应处理人
            $authorizationType['dealer'] = true;
        }

        $data['authorizationType'] = $authorizationType;
        
        $params['question_id'] = $id;
        $logList = $this->_questionLogModel->getLogListData($params);
        $logList = $this->_questionLogModel->getLogImgListData($logList);

        $data['loglist'] = $logList;
        $data = CodeModel::autoCodeOneVal($data, ['status']);
        $data['addremark_info'] = $this->_questionLogModel->getAddRemark($logList); // 补充说明

        $this->assign('moduleList', $moduleList);
        $this->assign('data', $data);
        $this->assign('detailData', $detailData);
        //$this->assign('adminData', $adminData);
        $this->assign('validityData', $validityData);
        $this->assign('projectType', $this->_PROJECT_TYPE);
        $this->assign('isQuestionDealWithUser', $bool);
        $this->assign('questionType', $questionType);
        $this->assign('moduleUser', $moduleUser);
        $this->assign('message', $this->_CONFIG);
        $this->display($viewStr);
    }

    // 创建工单
    public function doQuestionAdd()
    {
        //$moduleList = $this->dealWithModule();
        $questionStatus = $this->_dictionary->dealWithDateToKeyVal($this->_dictionary->getDictionary(DictionaryModel::QUESTION_DEALWITH_STATUS_PREFIX), 'CD', 'CD_VAL');
        $fileName = $str = '';
        $params['title'] = $_REQUEST['suggestTitle'];
        $params['desc'] = $_REQUEST['questionDesc'];
        $params['type'] = $_REQUEST['questionType'];
        $params['validity'] = '';
        $params['project_type'] = '';
        $params['project_no'] = 0;
        $params['moduleName'] = $_REQUEST['inWhichModule'];
        $params['pageUrl'] = $_REQUEST['questionPage'];
        $params['status'] = "N001760100";
        $params['questionEmail'] = $_SESSION['m_loginname'] . "@gshopper.com";
        $params['time'] = time();
        if (!empty($_FILES)) {
            $uploadModel = new FileUploadModel();
            $uploadModel->filePath = $this->_filePath;
            $uploadModel->fileExts = ['jpg', 'gif', 'png', 'jpeg', 'pdf', 'doc', 'docx', 'csv', 'xls', 'xlsx'];
            $fileName = $uploadModel->fileUploadExtend();
        }
        $params['fileName'] = $fileName;
        $questionService = new QuestionService(); // 默认当前应处理人为默认实施人员
        $params['opt_user_id'] = $questionService->getDefaultUser($questionService::$DEFAULT_CRY)['user_id'];
        $params['opt_user_name'] = $questionService->getDefaultUser($questionService::$DEFAULT_CRY)['name']; 
        $id = $this->_questionModel->saveQuestionData($params);
        if (!empty($id)) {
            $detail_add = [];
            $detail_add['questionId'] = $id;
            if (!empty($_REQUEST['imgArray'])) {
                $detail_add['img_json'] = $_REQUEST['imgArray'];
            }
            if (!empty($_REQUEST['demo_remark'])) { // 举例说明
                $detail_add['demo_remark'] = $_REQUEST['demo_remark'];
            }
            $this->_questionDetailModel->saveQuestionDetailData($detail_add);
            // 用单2.0需求中，移除邮件通知 from FS #9643
            /*$reply = "<tr>
                          <td style='padding-left:12px;border-right:1px solid #cccccc;text-align: left;height: 50px;color: #333333;font-size:14px;background: #F2F2F2;width:100px;'>" . L('问题描述') . "</td>
                          <td style='padding:12px;text-align: left;height: 50px;font-size: 13px;text-align: left;'>".$params['desc']."</td>                    
                     </tr>";*/
            // header('Content-Type:text/html;charset=utf-8 ');
            // $moduleList = $this->dealWithModule();
            // $questionStatus = $this->_dictionary->dealWithDateToKeyVal($this->_dictionary->getDictionary(DictionaryModel::QUESTION_DEALWITH_STATUS_PREFIX), 'CD', 'CD_VAL');
            // $title = $params['title'];/*$this->dealWithStr($params['title'],30);*/
            // $idstr ="Q" . ($id < 10 ? sprintf('%02s', $id) : $id);
            // $replaceStr = array($idstr,$title,$moduleList[$params['moduleName']]['TITLE'],$_SESSION['m_loginname'],'','',L($questionStatus[$params['status']]),L('创建时间'),date('Y-m-d H:i:s', $params['time']),$reply);
            // $searchStr = array('@@id@@','@@title@@','@@module@@','@@user@@','@@optUser@@','@@validity@@','@@status@@','@@tmeName@@','@@time@@','@@reply@@');
            // $emailMessage = $this->_CONFIG[$params['status']]['email'] . "<br/>" . str_replace($searchStr, $replaceStr, $this->_EMAILTEMPLATE);
            // $address = array_merge($this->_EMAIL_ADDRESS, [$params['questionEmail']]);
            // $result = $this->sendMail($address, str_replace(array('@@user@@','@@emailtitle@@'),array($_SESSION['m_loginname'],$title),$this->_CONFIG[$params['status']]['title']), $emailMessage);

            // 创建工单-企业微信通知
            $insertData = [];
            $question_trancry = new QuestionCreate();
            $insertData['type'] = '14';
            $insertData['id'] = $id;
            $insertData['info'] = $this->_questionModel->getQuestionDetail($id); // 获取该工单的详细信息
            $wx_res = $question_trancry->dealWithButton($insertData);
            $this->jsonOut(['code' => 2000, 'msg' => 'success', 'data' => null]);
        }
    }


    public function updateQuestionData()
    {
        $params = file_get_contents('php://input');
        $this->_questionModel->updatQuestionData();
    }

    // 原来工单详情提交接口，在2.0中已弃用
    public function addQuestionDetailData()
    {
        $bool = $this->isQuestionDealWithUser();
        if (empty($bool)) {
            $this->jsonOut(['code' => 4000, 'msg' => 'failed', 'data' => 'you do not have rights']);
        }
        $requestData = file_get_contents("php://input");
        $requestData = json_decode($requestData, true);
        if (empty($requestData['id']) || !in_array($requestData['status'], array_keys($this->_CONFIG))) {
            $this->jsonOut(['code' => 4000, 'msg' => 'failed', 'data' => 'params is not exist']);
        }
        $params = '';
        $params['questionId'] = $requestData['id'];
        $params['optUserId'] = $_SESSION['user_id'];
        $params['optUserName'] = $_SESSION['m_loginname'];
        $params['questionDesc'] = empty($requestData['questionDesc']) ? '' : trim($requestData['questionDesc']);
        $params['status'] = $requestData['status'];
        $params['add_time'] = time();
        $result = $this->_questionDetailModel->saveQuestionDetailData($params);
        if ($result !== false) {
            $data['id'] = $requestData['id'];
            $data['status'] = $requestData['status'];
            $data['optTime'] = time();
            $data['validity'] = $requestData['validity'];
            $data['project_type'] = $requestData['projectType'];
            $data['project_no'] = $requestData['projectNo'];
            $data['opt_user_id'] = $_SESSION['user_id'];
            $data['opt_user_name'] = $params['optUserName'];
            if ($params['status'] == 'N001760200') {
                $time = $data['opt_time'] = $params['add_time'];
            } else {
                $time = $data['finish_time'] = $params['add_time'];
            }
            $result = $this->_questionModel->updatQuestionData($data);
            if ($result !== false) {
                $moduleList = $this->dealWithModule();
                $questionStatus = $this->_dictionary->dealWithDateToKeyVal($this->_dictionary->getDictionary(DictionaryModel::QUESTION_DEALWITH_STATUS_PREFIX), 'CD', 'CD_VAL');
                $validityData = $this->_dictionary->getDictionary(DictionaryModel::QUESTION_VALIDITY_PREFIX);
                $id = $requestData['id'];
                $detail = $this->_questionModel->getQuestionDetail($id, 0);
                $opt_user_name = empty($detail['opt_user_name']) ? $params['optUserName'] : $detail['opt_user_name'];
                $validity = empty($detail['validity']) ? $params['optUserName'] : $detail['validity'];
                $title = $detail['title'];/*$this->dealWithStr($detail['title']);*/
                $idstr ="Q" . ($id < 10 ? sprintf('%02s', $id) : $id);
                $userNameData = $this->_questionModel->getQuestionDetail($requestData['id'], 0, 'question_user_name');
                $address = array_merge($this->_EMAIL_ADDRESS, [$userNameData['question_user_name'] . '@gshopper.com']);
                $message = $requestData['status'] == 'N001760400' ? $requestData['questionDesc'] : $this->_CONFIG[$params['status']]['email'];
                $timeName = $requestData['status'] == 'N001760200' ? '处理时间':'完成时间';
                $reply = "<tr>
                          <td style='padding-left:12px;border-right:1px solid #cccccc;text-align: left;height: 50px;color: #333333;font-size:14px;background: #F2F2F2;width:100px;'>" . L('问题描述') . "</td>
                          <td style='padding:12px;text-align: left;height: 50px;font-size: 13px;text-align: left;'>".$detail['desc']."</td>                    
                         </tr>";
                if ($params['status'] == 'N001760400') {
                    $reply = "
                              <tr>
                              <td style='padding-left:12px;border-bottom:1px solid #cccccc;border-right:1px solid #cccccc;text-align: left;height: 50px;color: #333333;font-size:14px;background: #F2F2F2;width:100px;'>" . L('问题描述') . "</td>
                              <td style='padding-left:12px;border-bottom:1px solid #cccccc;text-align: left;height: 50px;font-size: 13px;text-align: left;'>".$detail['desc']."</td>                    
                              </tr>
                              <tr>
                              <td style='padding-left:12px;border-right:1px solid #cccccc;text-align: left;height: 50px;color: #333333;font-size:14px;background: #F2F2F2;width:100px;'>" . L('意见回复') . "</td>
                              <td style='padding:12px;text-align: left;height: 50px;font-size: 13px;text-align: left;'>".$params['questionDesc']."</td>                    
                              </tr>";
                }
                $replaceStr = array($idstr,$title,$moduleList[$detail['module_name']]['TITLE'],$detail['question_user_name'],$opt_user_name,$validityData[$validity]['CD_VAL'],L($questionStatus[$requestData['status']]),L($timeName),date('Y-m-d H:i:s',$time),$reply);
                $searchStr = array('@@id@@','@@title@@','@@module@@','@@user@@','@@optUser@@','@@validity@@','@@status@@','@@tmeName@@','@@time@@','@@reply@@');
                $emailMessage = $this->_CONFIG[$params['status']]['email'] . "<br/>" . str_replace($searchStr, $replaceStr, $this->_EMAILTEMPLATE);
                $this->sendMail($address, str_replace(array('@@user@@','@@emailtitle@@'),array($detail['question_user_name'],$title),$this->_CONFIG[$params['status']]['title']),$emailMessage);
                $this->jsonOut(['code' => 2000, 'msg' => 'success', 'data' => null]);
            }

        }
    }

    /**
     * 上传文件和图片
     */
    public function uploadFile()
    {
        $uploadModel = new FileUploadModel();
        $uploadModel->filePath = $this->_filePath;
        $uploadModel->fileExts = ['jpg', 'gif', 'png', 'jpeg'];
        $fileName = $uploadModel->fileUploadExtend();
        $image = "http://".$_SERVER['HTTP_HOST'].U('question/question/showImage','fileName='.$fileName);
        $result = ['errno'=>0,'data'=>[$image]];
        $this->jsonOut($result);
    }
    /**
     * 显示文件和图片
     */
    public function showFile()
    {
        $filename = $_REQUEST['filename'];
        $str = $this->_filePath . $filename;
        $read_data = file_get_contents($str);
        echo $read_data;
        die;
    }

    /**
     *导出excel表
     */
    public function doExcel()
    {
        $questionStatus = $this->_dictionary->dealWithDateToKeyVal($this->_dictionary->getDictionary(DictionaryModel::QUESTION_DEALWITH_STATUS_PREFIX), 'CD', 'CD_VAL');;
        $validityData = $this->_dictionary->getDictionary(DictionaryModel::QUESTION_VALIDITY_PREFIX);
        $moduleList = $this->dealWithModule();
        $params['module'] = I('get.module', '');
        $params['startTime'] = I('get.startTime', '');
        $params['endTime'] = I('get.endTime', '');
        $params['userName'] = I('get.userName', '');
        $params['handler'] = I('get.handler', '');
        $params['status'] = I('get.status', '');
        $questionData = $this->_questionModel->getQuestionList($params);
        vendor("PHPExcel.PHPExcel");
        vendor("PHPExcel.PHPExcel.Writer.Excel2007");
        $objectPHPExcel = new PHPExcel ();
        $objectPHPExcel->getProperties()->setCreator(L("在线反馈"))
            ->setLastModifiedBy(L("在线反馈"))
            ->setTitle(L("在线反馈"))
            ->setSubject(L("在线反馈"))
            ->setDescription(L("在线反馈"))
            ->setKeywords("excel")
            ->setCategory("result file");
        $objectPHPExcel->setActiveSheetIndex(0);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
        $objectPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
        // $objectPHPExcel->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
        $objectPHPExcel->getActiveSheet()->setCellValue('A1', L('编号'));
        $objectPHPExcel->getActiveSheet()->setCellValue('B1', L('标题'));
        $objectPHPExcel->getActiveSheet()->setCellValue('C1', L('所在板块'));
        $objectPHPExcel->getActiveSheet()->setCellValue('D1', L('问题描述'));
        $objectPHPExcel->getActiveSheet()->setCellValue('E1', L('用户'));
        $objectPHPExcel->getActiveSheet()->setCellValue('F1', L('处理人'));
        $objectPHPExcel->getActiveSheet()->setCellValue('G1', L('状态'));
        $objectPHPExcel->getActiveSheet()->setCellValue('H1', L('创建时间'));
        // $objectPHPExcel->getActiveSheet()->setCellValue('I1', L('问题描述'));
        foreach ($questionData as $k => $v) {
            $id = ($v['id']) < 10 ? "Q0" . $v['id'] : "Q" . $v['id'];
            $num = $k + 2;
            $objectPHPExcel->getActiveSheet()->setCellValue('A' . $num, $id);
            $objectPHPExcel->getActiveSheet()->setCellValue('B' . $num, $v['title']);
            $objectPHPExcel->getActiveSheet()->setCellValue('C' . $num, L($moduleList[$v['module_name']]['NAME']));
            $objectPHPExcel->getActiveSheet()->setCellValue('D' . $num, $v['desc']);
            $objectPHPExcel->getActiveSheet()->setCellValue('E' . $num, $v['question_user_name']);
            $objectPHPExcel->getActiveSheet()->setCellValue('F' . $num, $v['opt_user_name']);
            $objectPHPExcel->getActiveSheet()->setCellValue('G' . $num, L($questionStatus[$v['status']]));
            $objectPHPExcel->getActiveSheet()->setCellValue('H' . $num, date('Y-m-d H:i:s', $v['add_time']));
            // $objectPHPExcel->getActiveSheet()->setCellValue('I' . $num, $v['desc']);
        }
        $objectPHPExcel->getActiveSheet()->setTitle('反馈问题列表');
        $name = '反馈问题列表_' . date("YmdHis");
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $name . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = new PHPExcel_Writer_Excel2007($objectPHPExcel);
        $objWriter->save(str_replace('.php', '.xlsx', __FILE__));;
        $objWriter->save('php://output');
        exit;
    }

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
     * 判断在线反馈请求指定链接
     */
    public function getReturnUrl()
    {
        $bool = $this->isQuestionDealWithUser();
        $page = $bool ? 'questionList' : 'index';
        $this->jsonOut(['code' => '2000', 'msg' => 'success', 'data' => $page]);
    }

    /**
     * 发送邮件
     * @param $address
     * @param $title
     * @param $content
     */
    private function sendMail($address, $title, $content)
    {
        return '';
        //$emailModel = new SMSEmail();
        //return $emailModel->sendEmail($address, $title, $content);
    }

    /**
     * 是否拥有处理问题的权限
     * @return bool
     */
    private function isQuestionDealWithUser()
    {
        $isRights = false;
        $roleId = $_SESSION['role_id'];
        $tempArr = $this->getQuestionRoleId($roleId);
        $nodeId = $this->_questionModel->getNodeId();
        if (in_array($nodeId['ID'], $tempArr)) {
            $isRights = true;
        }
        return $isRights;
    }

    /**
     * 获取拥有处理问题的权限
     * @return array
     */
    private function getQuestionRoleId($roleId)
    {
        $tempArr = [];
        if (!empty($roleId)) {
            $rights = $this->_questionModel->getQuestionRights($roleId);
            $tempArr = explode(',', $rights['ROLE_ACTLIST']);
        }
        return $tempArr;
    }


    /**
     * 处理module数据
     * @return mixed
     */
    private function dealWithModule()
    {
    
        $moduleList = $this->_questionModel->getNodeList(); // 获取所有一级菜单节点
        return $moduleList;
    }

    /**
     * 处理邮件标题太多，自动换行
     * @param $str
     * @param int $n
     * @return string
     */
    private function dealWithStr($str, $n = 15)
    {
        $titleStr = '';
        $titleCount = $n;
        $titleNum = ceil(mb_strlen($str, 'utf-8') / $titleCount);
        for ($i = 0; $i < $titleNum; $i++) {
            $start = $i * $titleCount;
            $titleStr .= mb_substr($str, $start, $titleCount, 'utf-8') . "<br/>";
        }
        $title = rtrim($titleStr, '<br/>');
        return $title;
    }

    /**
     * 显示图片
     */
    public function showImage()
    {
        $fileName = I('get.fileName');
        $image = $this->_filePath.$fileName;
        echo file_get_contents($image);
    }
}