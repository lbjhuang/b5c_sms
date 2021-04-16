<?php

/**
 * Alone API class
 * 2017
 * author: huaxin
 */
class ApiAction extends Action
{

    public function __call($function_name, $args)
    {
        // Summary some api
        $function_name = strtolower($function_name);
        if (substr($function_name, 0, 8) == 'hr_dept_') {
            $HrDeptObj = A('HrDept', 'Aapi', true);
            $do_func = substr($function_name, 3);
            if (method_exists($HrDeptObj, $do_func)) {
                $this->format_output($HrDeptObj->$do_func());
            }
        }
        if (substr($function_name, 0, 6) == 'store_') {
            $TbStoreObj = A('Store', 'Aapi', true);
            $do_func = substr($function_name, 6);
            if (method_exists($TbStoreObj, $do_func)) {
                $this->format_output($TbStoreObj->$do_func());
            }
        }
        if (substr($function_name, 0, 8) == 'meeting_') {
            $TbStoreObj = A('HrMeeting', 'Aapi', true);
            $do_func = substr($function_name, 8);
            if (method_exists($TbStoreObj, $do_func)) {
                $this->format_output($TbStoreObj->$do_func());
            }
        }
        if (substr($function_name, 0, 8) == 'hr_jobs_') {
            $TbStoreObj = A('HrJobs', 'Aapi', true);
            $do_func = substr($function_name, 3);
            if (method_exists($TbStoreObj, $do_func)) {
                $this->format_output($TbStoreObj->$do_func());
            }
        }

    }

    public function check_access_authority()
    {
        $obj_base = A('Base', '', true);
        //通过类名MyClass进行反射
        $ref_class = new ReflectionClass('BaseAction');
        //通过反射类进行实例化
        $instance = $ref_class->newInstance();
        //通过方法名myFun获取指定方法
        $method = $ref_class->getmethod('_get_access_list');

        //设置可访问性
        $method->setAccessible(true);
        //执行方法
        $status = $method->invoke($instance, $_SESSION['role_id']);
        if (!$status) {
            if (IS_AJAX) {
                $this->ajaxReturn(0, L('没有权限'), 0);
                exit;
            } else {
                header('Content-Type:text/html; charset=utf-8');
                echo L("没有权限");
                exit();
            }
        }
    }

    /**
     *  格式化输出
     */
    public function format_output($data, $code = 200, $msg = '')
    {
        $ret = array();
        if (isset($data['code'])) {
            $ret = $data;
        } else {
            $ret['code'] = $code;
            $ret['msg'] = '';
            $ret['data'] = $data;
        }
        $ret['code'] = isset($ret['code']) ? $ret['code'] : '';
        $ret['msg'] = isset($ret['msg']) ? $ret['msg'] : '';
        $ret['data'] = isset($ret['data']) ? $ret['data'] : '';
        $this->output_json($ret);
    }

    private function output_json($ret)
    {
        echo ZWebHttp::CallbackBegin(1) .
            json_encode($ret) .
            ZWebHttp::CallbackEnd(1);
    }

    private function getJson()
    {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        return $data;
    }

    /**
     *  clear app cache files
     *  e.g. :  index.php?m=api&a=clean_application_cache
     *  usage:  Manual cleaning app cron ;
     */
    public function clean_application_cache()
    {
        $outputs = array();
        $path = RUNTIME_PATH . 'Temp/';
        $outputs[] = Mainfunc::cleanDirFiles($path);
        $path = RUNTIME_PATH . 'Cache/';
        $outputs[] = Mainfunc::cleanDirFiles($path);
        $this->format_output($outputs);
    }

    /**
     *  clear cron config cache files
     *  e.g. :  index.php?m=api&a=clean_cron_cache
     *  usage:  Manual cleaning cron config cache ; debug cron ;
     */
    public function clean_cron_cache()
    {
        $outputs = array();
        $clearfile = RUNTIME_PATH . 'cron.lock';
        $outputs[] = @unlink($clearfile);
        $clearfile = RUNTIME_PATH . '~crons.php';
        $outputs[] = @unlink($clearfile);
        $this->format_output($outputs);
    }

    /**
     *  clear logs
     *  e.g. :  index.php?m=api&a=clean_log_file
     *  usage:
     */
    public function clean_log_file()
    {
        $dir = RUNTIME_PATH . 'Logs';
        $tmp = Mainfunc::rmDirExpiredFile($dir);
        var_dump($tmp);
    }

    public function index()
    {
    }

    public function pushmsg()
    {
        $tmp = A('Msg', 'Aapi', true);
        echo $tmp->apppushresult();
    }

    public function codevalue()
    {
        $tmp = A('Code', 'Aapi', true);
        echo $tmp->codeall();
    }

    /**
     *  make and write a post registrant
     */
    public function merchant_apply_register()
    {
        $this->output_json(A('Merchant', 'Aapi', true)->register());
    }

    /**
     *  findUserArea
     */
    public function find_area()
    {
        $this->format_output(A('Code', 'Aapi', true)->findDataOfArea());
    }

    /**
     * 查询人员
     *
     * @return [type] [description]
     */
    public function search()
    {

        $data = $this->getJson();
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->showList($data);
        Response::push($ret);
    }

    /**
     * 新增人员
     *
     * @param string $value [description]
     */
    public function AddPersonnel()
    {
        $data = $this->getJson();
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->addCustomer($data);
        Response::push($ret);
    }

    /**
     * 上传文件接口
     */

    public function hrUpload()
    {
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->upload($_FILES);
        Response::push($ret);

    }

    public function show()
    {
        $filename = $_REQUEST['filename'];
        $str = ATTACHMENT_DIR . $filename;
        //check img doc - skip
        $read_data = file_get_contents($str);
        echo $read_data;
    }


    /**
     * 新增跟踪信息
     *
     * @param string $value [description]
     */
    public function addTrack()
    {

        $data = $this->getJson();
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->editTrack($data);
        Response::push($ret);
    }

    /**
     *批量修改
     */
    public function batchChange()
    {
        $this->check_access_authority();
        $data = $this->getJson();
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->statusChange($data);
        Response::push($ret);
    }

    /**
     * 下拉选项接口
     */
    public function choice()
    {
        $data = $this->getJson();
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->acquireData($data);
        Response::push($ret);
    }

    /**
     * 编辑名片接口
     */
    public function editCard()
    {
        $data = $this->getJson();
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->changeCard($data);
        Response::push($ret);
    }

    /**
     * 编辑追踪接口
     */
    public function changeTrack()
    {
        $data = $this->getJson();
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->changeTrack($data);
        Response::push($ret);
    }


    /**
     * 名片信息展示
     *
     * @return [type] [description]
     */
    public function card()
    {
        $data = $this->getJson();
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->Mycard($data);
        Response::push($ret);
    }

    /**
     * 业务名片信息
     *
     * @return [type] [description]
     */

    public function business_card()
    {
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->person_business_card($data);
        Response::push($ret);
    }

    /**
     * excel导入员信息
     *
     * @return [type] [description]
     */
    public function import_emp()
    {
        $this->check_access_authority();
        $im = new ImportEmpModel();
        $ret = $im->import();
        $data = json_encode($ret);
        echo $data;
        die;
    }

    /**
     * 导出excel
     *
     * @return [type][description]
     */
    public function export_emp()
    {
        $this->check_access_authority();
        $id = $_REQUEST['EMPL_ID'];
        $tmp = A('Hr', 'Aapi', true);
        // $ret = $tmp->export($id);
        $ret = $tmp->exportHrByManual();
        Response::push($ret);
    }

    /**
     * 省市县三级联动
     */
    public function address()
    {
        $data = $this->getJson();
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->address($data);
        Response::push($ret);
    }

    public function download()
    {
        $file = $_GET['file'];
        import('ORG.Net.Http');
        $filename = APP_PATH . 'Tpl/Home/Hr/empl.xlsx';
        if ($file == 'rec') {
            $filename = APP_PATH . 'Tpl/Home/Hr/recruit.xlsx';
            Http::download($filename, 'import_recruit.xlsx');
            exit();
        } elseif ($file == 'resume') {
            $filename1 = $_GET['filename'];
            $filename = ATTACHMENT_DIR . $filename1;
            Http::download($filename, $filename1);
            exit();
        }
        Http::download($filename, 'empl.xlsx');
    }

    /**
     *  API:人员删除
     *  PS:此处不建议使用驼峰写名称(存在issue)
     */
    public function emplDelele()
    {
        $this->check_access_authority();
        $data = $this->getJson();
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->delete($data);
        Response::push($ret);
    }

    /**
     * 查看附件信息
     *
     * @param  string $value [description]
     *
     * @return [type]        [description]
     */
    public function checkAttach()
    {
        $data = $_REQUEST;
        import('ORG.Net.Http');
        if ($data['perCardPic']) {
            $fileName = $data['perCardPic'];
        } elseif ($data['resume']) {
            $fileName = $data['resume'];
        } elseif ($data['degCert']) {
            $fileName = $data['degCert'];
        } elseif ($data['graCert']) {
            $fileName = $data['graCert'];
        } elseif ($data['learnProve']) {
            $fileName = $data['learnProve'];
        }
        $filename = $fileName;
        $direct_down_filename = ATTACHMENT_DIR . $filename; // For test: 'C:\Users\b5m\Downloads/huaxintestpic.jpg';
        $type = mime_content_type($direct_down_filename);
        $showname = basename($direct_down_filename);
        if ($type == 'application/octet-stream') {
            header("Content-Disposition: attachment; filename=" . $showname);    //word  下载
        }
        header("Content-type: " . $type);
        echo file_get_contents($direct_down_filename);
        exit();    //其他格式直接打开
    }

    //修改密码(my card)
    public function changePwd()
    {
        $erpPwd = $_REQUEST['erpPwd'];
        $erpAct = $_REQUEST['erpAct'];
        $erpid = $_REQUEST['erpid'];
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->person_changePwd($erpPwd, $erpAct, $erpid);
        Response::push($ret);
    }

    //重置密码

    public function emailResetPwd()
    {
        $erpAct = $_REQUEST['erpAct'];
        $erpid = $_REQUEST['erpid'];
        $EmpScNm = $_REQUEST['EmpScNm'];
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->person_emailResetPwd($erpAct, $erpid, $EmpScNm);
        Response::push($ret);
    }

    /**
     * 组织部门展示
     *
     * @return [type] [description]
     */
    public function deptlist()
    {

        $data = $this->getJson();
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->deptData();
        Response::push($ret);
    }

    public function deptdetail()
    {
        $data = $this->getJson();
        $tmp = A('Hr', 'Aapi', ture);
        $ret = $tmp->detailData();
        Response::push($ret);
    }

    /**
     * 新建简历、编辑简历
     *
     * @return [type] [description]
     */
    public function addrecruit()
    {
        $data = $this->getJson();
        $data = $data['params'];
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->addRec($data);
        Response::push($ret);
    }

    /**
     * 招聘管理列表
     *
     * @param  string $value [description]
     */
    public function recruitlist()
    {
        $data = $this->getJson();
        $data = $data['params'];
        $tmp = A('Hr', 'Aapi', true);
        $ret = $tmp->recruitData($data);
        Response::push($ret);
    }

    /**
     * export recruit
     *
     * @return [type] [description]
     */
    public function exportrecruit()
    {
        $this->check_access_authority();
        $data = $_GET['param'];
        $tmp = A('HrRecruit', 'Aapi', true);
        $ret = $tmp->recexport($data);
        Response::push($ret);
    }

    //import
    public function import_recruit()
    {
        $data = $this->getJson();
        $tmp = new ImportRecModel();
        $ret = $tmp->import();
        Response::push($ret);
    }


    public function recDetail()
    {
        $data = $this->getJson();
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->detailData($data);
    }


    //del
    public function batchDel()
    {
        $this->check_access_authority();
        $data = $this->getJson();
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->delData($data);
    }

    /**
     * change
     */
    public function batchrecruit()
    {
        $data = $this->getJson();
        $data = $data['params'];
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->change($data);
    }


    public function getResData()
    {
        $data = $this->getJson();
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->ResData($data);
    }

    //批量推送
    public function batchPull()
    {
        $data = $this->getJson();
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->pullEmail($data);
    }

    //getLeader
    public function getLeader()
    {
        $data = $_GET['param'];
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->getLeaderNM($data);
    }

    public function getEnJob()
    {
        $znJob = $_GET['param'];
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->enJob($znJob);
    }

    //log
    public function getResLogData()
    {
        $data = $this->getJson();
        $resId = $data['params'];
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->getlogData($resId);
    }

    public function addJob()
    {
        $data = $this->getJson();
        $jobData = $data['params'];
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->setJob($jobData);
    }

    public function changeJob()
    {
        $data = $this->getJson();
        $jobData = $data['params'];
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->editJob($jobData);
    }

    //待跟进候选人
    public function waitFollow()
    {
        $data = $this->getJson();
        $data = $data['param'];
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->waitData($data);
    }

    //已沟通候选人
    public function hasCommun()
    {
        $data = $this->getJson();
        $data = $data['param'];
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->CommunData($data);
    }

    public function setUse()
    {
        $data = $this->getJson();
        $data = $data['params'];
        $tmp = A('HrRecruit', 'Aapi', true);
        echo $tmp->setUse($data);
    }

    //重复提醒
    public function repeatNotice()
    {
        $id = $_GET['id'];
        $telPhone = $_GET['telPhone'];
        if (!empty($telPhone)) {
            $telNow = D("TbHrResume")->where("ID='{$id}'")->find();
            if (!empty($id)) {
                if ($telPhone != $telNow['TEL']) {
                    if ($telRes = D("TbHrResume")->where("TEL='{$telPhone}'")->find()) {
                        echo json_encode(array("code" => 200, "msg" => L("号码重复"), "data" => $telRes['ID']));
                    }
                }
            } else {
                if ($telRes = D("TbHrResume")->where("TEL='{$telPhone}'")->find()) {
                    echo json_encode(array("code" => 200, "msg" => L("号码重复"), "data" => $telRes['ID']));
                }
            }
        }

    }

    public function checkTest()
    {
        echo "<pre>";
        var_dump($_SESSION);
    }


    public function es()
    {
        $es = new ESClientModel();
        $grand = $es->search();
        echo "<pre>";
        var_dump($grand);
        die;
    }

    public function esPost()
    {
        $params = [
            'index' => 'currencyexchange',
            'type' => 'currencyexchange',
            'id' => 'N000590500',
            'body' => [
                "currencyCD" => "N000590500",
                "currencyCDVal" => "EUR",
                "currencyName" => "欧元",
                "currencySign" => "€",
                "currencyRate" => "0.880361351600",
                "opType" => "update"
            ]
        ];
        $params = [
            'index' => 'currencyexchange',
            'type' => 'currencyexchange',
            'id' => 'N000590500',
            'body' => [
                "currencyCD" => "N000590500",
                "currencyCDVal" => "EUR",
                "currencyName" => "欧元",
                "currencySign" => "€",
                "currencyRate" => "0.880361351600",
                "opType" => "update"
            ]
        ];
        $es = new ESClientModel();
        var_dump($es->post($params));
    }

    /**
     * scm模块ceo邮件审批
     */
    public function scm_ceo_approve()
    {
        spl_autoload_register('autoLoad');
//        C('USER_AUTH_ON', false);
        header('Content-Type: text/html; charset=utf-8');
        $key = I('key');
        !empty($key) or die('<h1>授权码异常</h1>');
        $now = date('Y-m-d H:i:s');
        $auth = M('auth_key', 'tb_')->where(['key' => $key, 'enable' => 1, 'endtime' => ['egt', $now]])->find();
        !empty($auth) or die('<h1>授权码错误或已失效</h1>');
        $_SESSION['m_loginname'] = $auth['user'];
        $auth['header'] = json_encode(getallheaders());
        $auth['request'] = json_encode($_REQUEST);
        $auth['use_time'] = $now;
        M('auth_key', 'tb_')->save($auth);
        $action = new DemandAction();
        $action->ceo_approve();
    }

    public function sendGitReportEmail()//ss
    {
        $debug = I('d');
        $email = I('email');
        $data = GitReportModel::getData();
        if (empty($data['report'])) {
            $data['report'] = GitReportModel::genReport($data['bi_report']);
        }
        $data['report'] = GitReportModel::handleReport($data['report']);
        $this->data = $data;
        $content = 'Dear 飞鸿，<br/><br/>请查收员工GIT代码提交次数 & 员工日报缺失汇总<br/><br/>';
        $content = $content . $this->fetch('Home@Hr:git_report_email');
        $to = $email;
        $title = '员工GIT代码提交次数 & 员工日报缺失汇总';
        $mail = new SMSEmail();
        if ($debug) {
            header('Content-Type: text/html; charset=utf-8');
            $to = 'due@gshopper.com';
            $mail->sendEmail($to, $title, $content);
            echo '<pre>';
            print_r($this->data);
            echo '</pre>';
            die($content);
        }
        $mail->sendEmail($to, $title, $content);
        die('OK');
    }

    /**
     * 微信授权回调地址
     */
    public function wechat_approve()
    {
        if (IS_GET) {
            $params = ZUtils::filterBlank($_GET);
            Logs($params, __FUNCTION__, 'wechat');
            list($detail, $review, $callback_function) = ReviewMsg::getReview($params);
            $detail = $this->detailPush($detail, $callback_function);
            $this->detail = json_encode($detail);
            $this->order_id = $review['order_id'];
            $this->review_status = $review['review_status'];
            Logs($this->detail, __FUNCTION__, __CLASS__);
            switch ($callback_function) {
                case 'sales_leadership_approval':
                case 'ceo_approval':
                    $tpl = 'WorkWeChat/scm-tpl';
                    break;
                case 'transfer_approval':
                    $tpl = 'WorkWeChat/fin-tpl';
                    break;
                case 'product_transfer':
                    $tpl = 'WorkWeChat/product_transfer';
                    break;
                case 'new_transfer_approval':
                    $tpl = 'WorkWeChat/new_transfer_approval';
                    break;
                case 'attribution_transfer_approval':
                    $tpl = 'WorkWeChat/attribution_transfer_approval';
                    break;
                case 'payment_message_notice':
                    $tpl = 'WorkWeChat/payment-message-notice';
                    break;
                case 'after_sales_audit':
                    $tpl = 'WorkWeChat/after_sales_audit';
                    break;
                case 'return_warehouse_notice':
                    $tpl = 'WorkWeChat/return_warehouse_notice';
                    break;

                default:
                    $tpl = 'WorkWeChat/template';
            }
            if (!file_exists(T($tpl))) {
                $tpl = 'WorkWeChat/template';
            }
            Logs(['success'], __FUNCTION__. '-result', 'wechat');
            $this->display($tpl);
        } else {
            $params = json_decode(file_get_contents("php://input", 'r'), true);
            try {
                $res = ReviewMsg::handleReview($params);
            } catch (Exception $e) {
                $res = ['code' => 3000, 'msg' => $e->getMessage(), 'data' => ''];
            }
            $this->ajaxReturn($res);
        }

    }

    private function detailPush($detail, $callback_function)
    {
        switch ($callback_function) {
            case 'sales_leadership_approval':
            case 'ceo_approval':
                $Model = new Model();
                $demand = $Model->table('tb_sell_demand')
                    ->field('step,status')
                    ->where(['id' => $detail['demand']['id']])
                    ->find();
                $detail['demand']['sales_leadership_approval_by'] = $Model->table('tb_sell_demand')
                    ->where(['id' => $detail['demand']['id']])
                    ->getField('sales_leadership_approval_by');
                $expected_sales_country = $Model->table('tb_sell_demand')
                    ->where(['id' => $detail['demand']['id']])
                    ->getField('expected_sales_country');
                $expected_sales_platform_cd = $Model->table('tb_sell_demand')
                    ->where(['id' => $detail['demand']['id']])
                    ->getField('expected_sales_platform_cd');
                $expected_sales_country = D('Area')->getCountryNameByIds($expected_sales_country);
                $expected_sales_platform_cd = D('Scm/Demand','Logic')->getPlatformCd($expected_sales_platform_cd);
                $detail['demand']['expected_sales_country'] = $expected_sales_country;
                $detail['demand']['expected_sales_platform_cd'] = $expected_sales_platform_cd;
                $detail['demand']['sell_demand_remark'] = $detail['demand']['remark']; //需求-销售信息的订单备注 
                $detail['demand']['remark'] = $Model->table('tb_sell_demand_profit')
                    ->where(['demand_id' => $detail['demand']['id']])
                    ->getField('remark');
                if ('N002120700' == $demand['step'] && 'N002130300' == $demand['status']) {
                    $detail['demand']['sales_leadership_approval_by'] = null;
                    $detail['demand']['remark'] = null;
                }

                // 根据接收人办公地点显示语言
                // 商品名称、属性读PMS的语言
                // 获取不到翻译信息时，默认翻译为英文，即显示中文
                $data_arr = $detail['goods'];
                $res = SkuModel::getInfo(
                    $data_arr,
                    'sku_id',
                    ['spu_name', 'attributes'],
                    null);
                foreach ($res as $key => &$value) {
                    $value['goods_name'] = $value['spu_name'] ? $value['spu_name'] : $value['goods_name'];
                    $value['sku_attribute'] = $value['attributes'] ? $value['attributes'] : $value['sku_attribute'];
                    $value['purchase_number'] = intval($value['purchase_number']) + intval($value['spot_number']) + intval($value['on_way_number']);
                }
                $detail['goods'] = $res;

                break;
            case 'transfer_approval':
                $Model = new Model();
                $msgs = $Model->table('tb_fin_account_audit_msg')
                    ->field('msg,auditor_nm')
                    ->where(['transfer_no' => $detail['base_info']['transfer_no']])
                    ->select();
                $audit_reason = [];
                array_map(function ($value) use (&$audit_reason) {
                    $audit_reason[] = ['name' => $value['auditor_nm'], 'reason' => $value['msg']];
                }, $msgs);
                $detail['base_info']['audit_reason'] = $audit_reason;
                break;
            //售后申请审核
            case 'after_sales_audit':
                $Model = new Model();
                $order_refund = $Model->table('tb_op_order_refund')
                    ->field('status_code,audit_status_cd')
                    ->where(['id' => $detail['refund_info']['id']])
                    ->find();
                $detail['refund_info']['status_code'] = $order_refund['status_code'] ? $order_refund['status_code'] : $detail['refund_info']['status_code'];
                $detail['refund_info']['audit_status_cd'] = $order_refund['audit_status_cd'] ? $order_refund['audit_status_cd'] : $detail['refund_info']['audit_status_cd'];
                break;
            //退货入库
            case 'return_warehouse_notice':
        }
        return $detail;
    }

    public function updateStaticResourceVersion()
    {
        $version = date('YmdHi');
        RedisModel::client()->set('static-resource-version',$version);
    }

    public function getCodeTypes()
    {
       $result = M('cmn_cd_type', 'tb_ms_')->select();
       echo json_encode($result);exit();
    }

    //一般付款详情根据review_no
    public function paymentDetailByReviewNo()
    {
        $params = $this->getJson();
        //根据审核表查询付款单表
        $review = ReviewModel::findByNo($params['params']['review_no'])->toArray();
        $payment_info = M('payment_audit', 'tb_pur_')->where(['id'=>['in',$review['detail_json']['base_info']['id']]])->find();
        if ($payment_info['status'] == TbPurPaymentAuditModel::$status_accounting_audit) {
            $audit_status = 1;
        } else {
            $audit_status = 4;  // 审核完成
        }
        $review['detail_json']['base_info']['audit_status'] = $audit_status;
        switch ($payment_info['source_cd']){
            case TbPurPaymentAuditModel::$source_general_payable  :
                $res = $this->checkReviewStatus($payment_info, $params['params']['user_name']);
                $review['detail_json']['base_info']['audit_status'] = $res;
                ELog::add(['msg'=>'根据review_no查询一般付款详情','request'=>$params,'res'=>$review['detail_json'],'payment_info'=>$payment_info],ELog::INFO);
                break;
            case TbPurPaymentAuditModel::$source_payable  :
                ELog::add(['msg'=>'根据review_no查询采购应付详情','request'=>$params,'res'=>$review['detail_json'],'payment_info'=>$payment_info],ELog::INFO);
                break;
            case TbPurPaymentAuditModel::$source_transfer_payable  :
                ELog::add(['msg'=>'根据review_no查询关联交易详情','request'=>$params,'res'=>$review['detail_json'],'payment_info'=>$payment_info],ELog::INFO);
                break;
            case TbPurPaymentAuditModel::$source_transfer_payable_indirect  :
                ELog::add(['msg'=>'根据review_no查询关联交易（间接）','request'=>$params,'res'=>$review['detail_json'],'payment_info'=>$payment_info],ELog::INFO);
                break;
            default:
                echo json_encode([]);
        }
        echo json_encode($review['detail_json']);


    }

    //检验当前用户审核状态
    public function checkReviewStatus($data, $user_name)
    {
        //$user_name = 'shenmo';
        //$data = ['status' => 4, 'current_audit_user' => 'test1', 'accounting_audit_user' => 'test1>shenmo>test2'];
        $current_audit_user = $data['current_audit_user'];
        $accounting_audit_user = $data['accounting_audit_user'];
        //审核状态 1:待审核 2:已同意 3:已拒绝 4:审核完成
        if ($data['status'] == TbPurPaymentAuditModel::$status_no_confirmed) { //待确认
            $audit_status = 3;
        } elseif ($data['status'] == TbPurPaymentAuditModel::$status_accounting_audit) { //待审核
            //付款单状态为待审核
            //是当前用户审核则待按钮状态：未审核
            if ($current_audit_user == $user_name) {
                $audit_status = 1;
            } else {
                if (strpos($accounting_audit_user, '->') !== false) {
                    $audit_user = explode('->', $accounting_audit_user);
                } else if (strpos($accounting_audit_user, ',') !== false) {
                    $audit_user = explode(',', $accounting_audit_user);
                } else {
                    $audit_user = (array)$accounting_audit_user;
                }
                if (count($audit_user) > 1) {
                    //当前审核用户在访问用户的后面审核
                    if (array_search($current_audit_user, $audit_user) > array_search($user_name, $audit_user)) {
                        $audit_status = 2;
                    }
                }
            }
        } else {
            //非待审核状态直接显示审核完成文案
            $audit_status = 4;
        }
        return $audit_status;
    }

    //会计审核
    public function accountingAudit()
    {

        try {
            $request_data = DataModel::getDataNoBlankToArr();
            $model        = new Model();
            $model->startTrans();
            if (empty($request_data)) {
                throw new Exception('请求为空');
            }if ($request_data['status'] === '' && !isset($request_data['status'])) {
                throw new Exception('审核状态缺失');
            }
            if (empty($request_data['payment_audit_id'])) {
                throw new Exception('付款id缺失');
            }
            if (empty($request_data['source_cd'])) {
                throw new Exception('付款单来源缺失');
            }
            $rClineVal    = RedisModel::lock('payment_audit_id' . $request_data['payment_audit_id'], 10);
            if (!$rClineVal) {
                throw new Exception('获取流水锁失败');
            }
            $res         = DataModel::$success_return;
            $res['code'] = 200;

            //  采购应付 调拨应对 B2C退款
           if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable){
                // 转账换汇
                (new TransferPaymentService($model))->accountingAudit($request_data);
               $this->WorkWxSendMessageNew($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_transfer_payable_indirect){
                // 转账换汇
                (new TransferPaymentService($model))->accountingAudit($request_data);
               $this->WorkWxSendMessageNew($request_data);
            } else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_general_payable){
                // 一般付款
                $payment_info = M('payment_audit', 'tb_pur_')->where(['id'=>['in',$request_data['payment_audit_id']]])->find();
                if ($request_data['is_return'] == 1) { // 保存退回记录状态和操作人
                    (new GeneralPaymentService($model))->recordReturnInfo($request_data);
                }
                (new GeneralPaymentService($model))->accountingAudit($request_data);
                $this->WorkWxSendMessage($request_data, $payment_info);
            }else if ($request_data['source_cd'] == TbPurPaymentAuditModel::$source_payable){
                // 采购应付
                (new PurPaymentService($model))->accountingAudit($request_data);
               $this->WorkWxSendMessageNew($request_data);
            }else {
                throw new Exception('付款单来源异常');
            }
            $model->commit();

            RedisModel::unlock('payment_audit_id' . $request_data['payment_audit_id']);
        } catch (Exception $exception) {
            $model->rollback();
            $res = $this->catchException($exception);
        }
        Logs(['request_data'=>$request_data,'response_data'=>$res], __FUNCTION__, __CLASS__);
        $this->ajaxReturn($res);
    }


    public function WorkWxSendMessage($request_data, $payment_info)
    {
        $res = (new FinanceService())->WorkWxSendMessage($request_data, $payment_info);
        Logs(['request_data'=>$request_data,'response_data'=>$res], __FUNCTION__, __CLASS__);
    }

    public function WorkWxSendMessageNew($request_data)
    {
        $payment_info = M('payment_audit', 'tb_pur_')->where(['id'=>['in',$request_data['payment_audit_id']]])->find();
        $operation_user = $payment_info['current_audit_user'];
        $db_res = (new  PurPaymentService())->getPersonUser(array('id'=>$request_data['payment_audit_id']),'payment_manager_by');
        $person_user = $db_res['payment_manager_by'];
        $res = $this->generalMessageTpl($payment_info,$operation_user,$person_user);
        $request_data['operation_user'] = $operation_user;
        $request_data['person_user'] = $person_user;
        Logs(['request_data'=>$request_data,'response_data'=>$res], __FUNCTION__, __CLASS__);
    }



    /**
     * @param $request_data
     * @param $operation_user   操作人
     * @param $person_user    负责人
     * @return array
     */
    public function generalMessageTpl($payment_info,$operation_user,$person_user){

        if ($payment_info['status'] == TbPurPaymentAuditModel::$status_no_payment) { //待付款
            // 同意
            $operation_user_str = '您已同意付款单号' . $payment_info['payment_audit_no'] . '的申请。';
            $operation_user_res = (new ApiModel())->WorkWxMessage($operation_user, $operation_user_str);

            $person_user_str = '付款单' . $payment_info['payment_audit_no'] . '已经审批通过，等待付款。';
            $person_user_res =  (new ApiModel())->WorkWxMessage($person_user, $person_user_str);
        }else if ($payment_info['status'] == TbPurPaymentAuditModel::$status_deleted){
            // 退回
            $operation_user_str = '您已拒绝付款单号' . $payment_info['payment_audit_no'] . '的申请。';
            $operation_user_res =  (new ApiModel())->WorkWxMessage($operation_user, $operation_user_str);

            $person_user_str = '您的付款申请' . $payment_info['payment_audit_no'] . '已经被退回。';
            $person_user_res =  (new ApiModel())->WorkWxMessage($person_user, $person_user_str);
        }
        return array('operation_user_res'=>$operation_user_res,'person_user_res'=>$person_user_res);
    }

    /**
     * @param $exception
     * @param null $Model
     *
     * @return array
     */
    protected function catchException($exception, $Model = null)
    {
        $res = DataModel::$error_return;
        $res['msg'] = $exception->getMessage();
        if ($this->error_message) {
            $res['data'] = $this->error_message;
        }
        if ('stage' == $_ENV["NOW_STATUS"] || true === $_ENV["ENABLE_DEBUG"]) {
            $res['data']['exception_in'] = $exception->getFile() . ':' . $exception->getLine();
        }
        if ($exception->getCode()) $res['code'] = $exception->getCode();
        if ($Model) {
            $Model->rollback();
        }
        return $res;
    }



    public function imgShow()
    {
        $file_full_path = $_REQUEST['file_full_path'];
        $read_data = file_get_contents($file_full_path);
        echo $read_data;
    }

}

function autoLoad($cls)
{
    $path[] = __DIR__ . DIRECTORY_SEPARATOR . '../Scm/';
    $path[] = __DIR__ . DIRECTORY_SEPARATOR . '../../Model/Scm/';
    $path[] = __DIR__ . DIRECTORY_SEPARATOR . '../../Logic/Scm/';
    $path[] = __DIR__ . DIRECTORY_SEPARATOR . '../../Model/';
    $path[] = __DIR__ . DIRECTORY_SEPARATOR . '../../Logic/';
    $path[] = __DIR__ . DIRECTORY_SEPARATOR;
    $file_name = $cls . '.class.php';
    foreach ($path as $p) {
        if (is_file($p . $file_name)) {
            require_once $p . $file_name;
            break;
        }
    }

}





