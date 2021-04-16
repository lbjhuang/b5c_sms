<?php
/**
 * 基础控制器
 * User: muzhitao
 * Date: 2016/7/28
 * Time: 14:22
 */
load('@.otherbase');
header('Access-Control-Allow-Origin:*');

class BaseAction extends Action
{

    /**
     * 初始化入口
     */
    public $access;
    public $serviceName;
    public $params;
    public $error_message;
    public $return_success = [
        'code' => 200000,
        'msg' => '成功',
        'data' => ''
    ];
    public $return_error = [
        'code' => 400000,
        'msg' => '失败',
        'data' => ''
    ];

    public static $rule_menu;

    protected $module = 'BaseAction';   //存储所有url
    protected $whiteList = [];
    protected $error_data;
    protected $success_code;
    /**
     * @var string
     */
    protected $service = stdClass::class;

    public function _initialize()
    {
       
        if (!class_exists('ButtonAction')) {
            include_once APP_PATH . 'Lib/Action/Home/ButtonAction.class.php';
        }
        #login页面仅仅载入html  不加载其他  return,java监控项目请求跳过登录验证
        if((strtolower(MODULE_NAME) == 'public' && strtolower(ACTION_NAME) == 'login') ||
            (strtolower(MODULE_NAME) == 'index' && strtolower(ACTION_NAME) == 'status')) {
            return true;
        }
        if (!empty($this->whiteList)) {
            foreach ($this->whiteList as $key => &$value) {
                $value = strtolower($value);
                unset($value);
            }
            if (in_array(strtolower(ACTION_NAME), $this->whiteList)) {
                return true;
            }
        }
        
        //$this->_catchMe();
        $this->generateMethod();
        $this->setParams();
        $this->access = $_SESSION['actlist_value'];
        // 用户权限检查
       
        if (C('USER_AUTH_ON') && !in_array(MODULE_NAME, explode(',', C('NOT_AUTH_MODULE')))) {
           
            //check and login by cookie
            /*            if (!$_SESSION [C('USER_AUTH_KEY')]) {
                            $_identity_sms = cookie('_identity_sms');
                            $_identity_key = cookie('_identity_key');
                            if($_identity_sms and $_identity_key){
                                if(md5(C('PASSKEY').$_identity_sms)==$_identity_key){
                                    $tmpIdentity = unserialize(base64_decode(base64_decode($_identity_sms)));
                                    $role = M('role')->field('ROLE_ACTLIST')->find($tmpIdentity['role_id']);
                                    $tmpIdentity['actlist'] = $role['ROLE_ACTLIST']== 0? 0:A('Public')->getRoleInfo($role['ROLE_ACTLIST']);
                                    $tmpIdentity['actlist_value'] = $role['ROLE_ACTLIST']== 0? 0:A('Public')->getRoleInfo($role['ROLE_ACTLIST'], true);
                                    foreach($tmpIdentity as $k=>$v){
                                        $_SESSION[$k] = $v;
                                    }
                                    $this->access = $_SESSION['actlist_value'];
                                    cookie('_identity_sms',$_identity_sms,array('expire'=>3600*24));
                                    cookie('_identity_key',$_identity_key,array('expire'=>3600*24));
                                    // var_dump($_SESSION); die();
                                }
                            }
                        }*/
            if (isLocalEnv()) {
                //本地关闭session同步后，session为空，固定一个session信息
                if (empty($_SESSION) && is_file(LOCAL_SESSION)) {
                    $user_json = @file_get_contents(LOCAL_SESSION);
                    $_SESSION = json_decode($user_json, true);
                }
            }
            //检查认证识别号
            if (!$_SESSION [C('USER_AUTH_KEY')]) {
                //跳转到认证网关
                $supply = get_supply_info();
                js_redirect(PHP_FILE . C('USER_AUTH_GATEWAY') . $supply);
                //js_redirect(PHP_FILE . C('USER_AUTH_GATEWAY'));
                // redirect(PHP_FILE . C('USER_AUTH_GATEWAY'), 2,'请先登陆');
                //redirect(U('Public/login'));
            }
            #将用户和session_id绑定  用户无操作7天后erp登录信息才会失效  必须在此处才能与erp登录失效时间同步
            #定时任务一天扫描一次redis hash 将失效的session_id del  避免Hash对象过大导致速度慢
           
            $isFirstBind = RedisModel::client()->hexists('uid_session_id_' . $_SESSION['userId'], session_id());
            RedisModel::client()->hset('uid_session_id_' . $_SESSION['userId'], session_id(), 14 * 24 * 60 * 60);
            #判断是否需要刷新权限  是的话 改为真实role_id;刷新权限
            $isRefresh = RedisModel::client()->hget('refresh_role_session_id_'.$_SESSION['userId'], session_id());
            if(!$isFirstBind || $isRefresh){
                $_SESSION['role_id'] = M('admin_role')->where(['M_ID' => $_SESSION['userId']])->getField('ROLE_ID', true);
                $role = M('role')->where(['ROLE_ID' => ['in', $_SESSION['role_id']]])->getField('ROLE_ACTLIST', true);
                $role = implode(',', $role);
                $_SESSION['actlist'] = $this->getRoleInfo($role);
                RedisModel::client()->hdel('refresh_role_session_id_'.$_SESSION['userId'], session_id());
            }


            if (!$this->_get_access_list($_SESSION['role_id'])) {
                //redirect(U('Public/error'));

//                echo "没有权限";
//				exit;
                if (IS_AJAX || false != strstr($_SERVER['CONTENT_TYPE'],'application/json')) {
                    $data['msg'] = L('没有权限');
                    $data['info'] = L('没有权限');
                    $data['data'] = L('没有权限');
                    $data['status'] = 3000;
                    $data['code'] = 3000;
                    $this->ajaxReturn($data, 'JSON');
                    exit;
                } else {
                    header('Content-Type:text/html; charset=utf-8');
                    echo L("没有权限");
                    exit;
                }
            }
        }
        $module = $this->_get_menu();
       
        //echo "<pre>"; print_r($this->_get_menu_all());exit();
        //echo "<pre>"; print_r($_SESSION);exit();

        // lang by self check
        if (empty($_GET[C('VAR_LANGUAGE')])) {
            $langSet = cookie('think_language');
            if ($langSet) {
                $_GET[C('VAR_LANGUAGE')] = $langSet;
                cookie('think_language', $langSet, 3600 * 24 * 365);
            }
        }
        B('SYSOperationLog');
        $rm = array_column(self::$rule_menu, 'NAME', 'CTL');
        $cm = array_column(self::$rule_menu, 'NAME', 'ACT');

       
        $this->assign('rule_menu', $rm);
        $this->assign('c_name', $cm);
        $this->assign('module', $module);

        $version = RedisModel::client()->get('static-resource-version');
        if (!empty($version)) {
            C('VER_NUM', $version);
            define ( 'V', $version);
        } else {
            define ( 'V', date('YmdHi') );
        }
        #翻译
      
        if (empty(Cookie('think_language')) && LANG_SET && LANG_SET != 'LANG_SET')  Cookie('think_language', LANG_SET, 365 * 24 * 3600);
        if (empty(Cookie('think_language')) && (!LANG_SET || LANG_SET == 'LANG_SET'))  Cookie('think_language', 'zh-cn', 365 * 24 * 3600);
        #避免其他地方设置了cookie  公共方法重置cookie时间
        if(!empty(Cookie('think_language'))) Cookie('think_language', Cookie('think_language'), 365 * 24 * 3600);
      

    }

    protected function assignJson($name, $value = '')
    {
        $this->view->assign($name, json_encode($value, JSON_UNESCAPED_UNICODE));
        return $this;
    }

    public function getParams()
    {
        return $_REQUEST;
    }

    public function params()
    {
        $params = [];
        foreach ($_REQUEST as $k => $v) {
            if (is_string($v)) $v = trim($v);
            if ($v !== '') $params[$k] = $v;
        }
        return $params;
    }

    public function jsonParams($assoc = true)
    {
        $data = file_get_contents("php://input", 'r');
        return json_decode($data, $assoc);
    }

    /**
     * 权限判断
     *
     * @param $role
     *
     * @return bool
     */
    private function _get_access_list($role)
    {

        /* 公共或者系统控制器则开放所有权限 */
        /*if(MODULE_NAME == 'System' || MODULE_NAME == 'Public' || MODULE_NAME == 'Admin') {
            return true;
        }*/
        $temp = $this->_get_menu_all();
        $action_name = strtolower(ACTION_NAME);
        if (!in_array(MODULE_NAME . '/' . $action_name, $temp) && !in_array(GROUP_NAME . '/' . MODULE_NAME . '/' . $action_name, $temp)) {
            return true;
        }

        if (MODULE_NAME == 'Index') {
            if ($action_name == 'index' || $action_name == 'welcome') {
                return true;
            }
        }

        /* 如果是超级管理员则开放所有权限  */
        if ($role == 1) {
            return true;
        }

        $Node = D("Node");
        $Role = D("Role");

        $where = array(
            'CTL' => !empty(GROUP_NAME) && GROUP_NAME == "Home" ? MODULE_NAME : GROUP_NAME . '/' . MODULE_NAME,
            'ACT' => $action_name
        );
        // todo 限制多重复权限配置 ->order('ID DESC')
        $node_detail = $Node->where($where)->find();
        $role = $Role->where(['ROLE_ID' => ['in', $role]])->getField('ROLE_ACTLIST', true);
        if ($role == null) {
            return false;
        }
        $access = explode(",", implode(',', $role));
        if (in_array($node_detail['ID'], $access)) {
            return true;
        }

        return false;
    }

    /**
     * 功能导航菜单的构建，这里要进行接口和页面区分。
     *
     * @return array
     */
    public function _get_menu()
    {

        $Node = D("Node");
        $modules = $rolemenu = array();
        //Changed By Afanti @2017.08.07, 添加类型筛选，这里之筛选页面类型。
        if (isLocalEnv()) {
            $menu = $Node->where('LEVEL != 3 AND TYPE =0')->order('SORT DESC')->select();
        } else {
            $erp_node_cache = RedisModel::get_key('erp_node_cache');
            if ($erp_node_cache) {
                $menu = json_decode($erp_node_cache, true);
            } else {
                $menu = $Node->where('LEVEL != 3 AND TYPE =0')->order('SORT DESC')->select();
                RedisModel::set_key('erp_node_cache', json_encode($menu), null, 1800);
            }
            unset($erp_node_cache);
        }
        if (empty($menu)) {
            return $modules;
        }
        foreach ($menu as $key => $s) {
            if ($s['PID'] == 0) {
                $modules[$s["ID"]] = $s;
            } else {
                $rolemenu[] = $s;
            }
        }
        self::$rule_menu = $rolemenu;
        if (!empty($rolemenu)) {
            foreach ($rolemenu as $key => $s) {
                $rolemenu[$key]['url'] = U($s['CTL'] . '/' . $s['ACT']);
            }
            foreach ($rolemenu as $vs) {
                if (isset($modules[$vs['PID']])) {
                    $modules[$vs['PID']]['child'][] = $vs;
                } else {
                    //$modules[]['child'] = array();
                }
            }
        }
        return $modules;
    }

    //获得全部信息
    public function _get_menu_all()
    {

        $Node = D("Node");
        $modules = $rolemenu = array();
        $menu = $Node->order('SORT DESC')->select();

        if (empty($menu)) {
            return $modules;
        }

        foreach ($menu as $key => $s) {
            $modules[$s['ID']] = $s['CTL'] . '/' . strtolower($s['ACT']);
        }
        /*if ($s['ID'] == 0) {
            $modules[$s["ID"]] = $s;
        } else {
            $rolemenu[] = $s;
        }
    }

    if (!empty($rolemenu)) {
        foreach($rolemenu as $key => $s) {
            $rolemenu[$key]['url'] = U($s['CTL'].'/'.$s['ACT']);
        }
        foreach($rolemenu as $vs) {
            if (isset($modules[$vs['PID']])) {
                $modules[$vs['PID']]['child'][] = $vs;
            } else {
                $modules[]['child'] = array();
            }
        }
    }*/
        return $modules;
    }

    private function getLogPath($time = '')
    {
        $logFilePath = __DIR__ . '../../../Runtime/Logs/';
        if (!$time) $time = date('Y-m-d');
        if (I('get.type') == 'sendout') {    //生成的日志记录格式,做什么操作记录什么名字
            $logName = $time . 'sendout.log';
        } else if (I('get.type') == 'refund') {
            $logName = $time . 'refund.log';
        } else {
            $logName = $time . I('get.type') . '.log';
        }
        return $logFilePath . $logName;
    }

    /**
     * 显示日志
     */
    public function show_log()
    {
        $time = I('get.time');
        $file = $this->getLogPath($time);

        $content = file_get_contents($file);
        var_dump($content);
        if (!$content) $content = 'Not and more!';
        //header("Content-type: text/html; charset=utf-8");
        $this->assign('content', $content);
        $this->display('Log/show_log');
    }

    /**
     * 显示日志
     */
    public function showSendLog()
    {
        $fileName = '_sendGuds';
        $time = I('get.time');
        if ($time) {
            $time = date('Ymd', strtotime($time));
        } else {
            $time = date('Ymd', time());
        }

        $fname = I('get.fname');
        if ($fname)
            $fileName = $fname;

        $destination = '/opt/logs/logstash/' . $time . $fileName . '.log';
        $clean = I('get.clean');
        if ($clean == true or $clean == 1 or $clean == 'down') {
            if (fclose(fopen($destination, 'w'))) {
                exit('clean down');
            } else {
                exit('clean fail');
            }
        }


        $content = file_get_contents($destination);
        if (!$content) $content = 'Not any more!';
        header("Content-type: text/html; charset=utf-8");
        $this->assign('content', $content);
        $this->display('Log/show_log');
    }

    /**
     * 清理日志
     */
    public function clean()
    {
        $file = $this->getLogPath();
        fclose(fopen($file, 'a+'));
        $_fo = fopen($file, 'rb');
        $old = fread($_fo, 1024 * 1024);
        fclose($_fo);
        file_put_contents($file, '');

        $content = file_get_contents($file);
        if (!$content) $content = 'clean done';
        else $content =
            'clean fail';
        $this->display('Log/show_log', 'utf-8', 'html', $content, $prefix = '');
    }

    /**
     * 翻译类容导入
     */
    public function import_translation()
    {
        $params = $this->getParams();
        $language_map = [
            'zh-cn',
            'zh-tw',
            'zh-hk',
            'en-us',
            'ja-jp',
            'ko-kr',
            'de-ch',
            'es-us',
            'es-es',
            'fr-ca',
            'fr-ch',
            'fr-fr',
            'pt-pt',
            'de-de',
            'de-ch',
            'tr-tr',
        ];
        $groups = C("APP_GROUP_LIST");
        $groups = explode(",", $groups);
        if ($_FILES ['file']['size'] > 0) {
            $languageFileNmae = $_POST ['group_name'];
            //$lang_set = 'en-us';
            $lang_set = $this->getParams()['language-set'];
            if (empty($lang_set) or $lang_set == "") {
                $error = L('请设置语言包');
                $this->assign('error', $error);
                $this->display('Log/import_translation');
            }
            if (!in_array($lang_set, $language_map)) {
                $error = L('所选语言包不存在');
                $this->assign('error', $error);
                $this->display('Log/import_translation');
            }
            if (!is_dir(LANG_PATH . '/' . $lang_set)) {
                $isok = mkdir(LANG_PATH . '/' . $lang_set);
                if (!$isok) {
                    $error = L('无法创建语言翻译文件夹，请联系管理员');
                    $this->assign('error', $error);
                    $this->display('Log/import_translation');
                }
            }
            $fname = LANG_PATH . '/' . $lang_set . '/' . $languageFileNmae . '.php';
            if (!file_exists($fname)) {
                fclose(fopen($fname, 'a+'));
            }
            if ($_FILES['file']['tmp_name'] == "" or !empty($_FIELS['file']['tmp_name'])) {
                $error = L('请上传语言包');
                $this->assign('error', $error);
                $this->display('Log/import_translation');
            }
            header("content-type:text/html;charset=utf-8");
            $filePath = $_FILES['file']['tmp_name'];
            vendor("PHPExcel.PHPExcel");
            $objPHPExcel = new PHPExcel();
            //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
            $PHPReader = new PHPExcel_Reader_Excel2007();
            if (!$PHPReader->canRead($filePath)) {
                $PHPReader = new PHPExcel_Reader_Excel5();
                if (!$PHPReader->canRead($filePath)) {
                    echo 'no Excel';
                    return;
                }
            }
            //读取Excel文件
            $PHPExcel = $PHPReader->load($filePath);
            //读取excel文件中的第一个工作表
            $sheet = $PHPExcel->getSheet(0);
            //取得最大的列号
            $allColumn = $sheet->getHighestColumn();
            //取得最大的行号
            $allRow = $sheet->getHighestRow();
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $en = trim((string)$PHPExcel->getActiveSheet()->getCell("A" . $currentRow)->getValue());
                $ch = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());
                if (empty($ch)) continue;
                $temp [$en] = $ch;
            }
            $common = $this->getCommonFileContent(include LANG_PATH . $lang_set . '/' . $languageFileNmae . '.php');
            is_array($common) or $common = [];
            $ret = array_merge($common, $temp);
            $newAdded = [];
            foreach ($ret as $k => $v) {
                if (array_key_exists($k, $common)) {
                    if ($common [$k] == $ret [$k]) {
                        continue;
                    } else {
                        $newAdded [$k] = $v;
                    }
                } else {
                    $newAdded [$k] = $v;
                }
            }
            if ($newAdded) {
                // 备份
                $this->backCommonLanguagePackage(LANG_PATH . $lang_set . '/' . $languageFileNmae . '.php', LANG_PATH . $lang_set . '/' . $languageFileNmae . '_back' . date('Y-m-d-H-i-s') . '.php');
                $save_text = '<?php return ' . var_export($ret, true) . ';';
                $isok = file_put_contents(LANG_PATH . $lang_set . '/' . $languageFileNmae . '.php', $save_text);
            }
            $this->assign('is_translation', true);
            $this->assign('newAddedLength', count($newAdded));
            $this->assignJson('newAdded', $newAdded);
        }
        $languages = [
            'zh-cn' => '中文',
            'en-us' => '英文',
            'ja-jp' => '日文',
            'ko-kr' => '韩文'
        ];
        isset($params ['language-set']) or $params ['language-set'] = 'en-us';
        isset($params ['group_name']) or $params ['group_name'] = 'Home';
        isset($newAdded) or $newAdded = [];
        $this->assign('language', $params ['language-set']);
        $this->assign('module', $params ['group_name']);
        $this->assignJson('groups', $groups);
        $this->assignJson('newAdded', $newAdded);
        $this->assignJson('languages', $languages);
        $this->display('Log/import_translation');
    }

    /**
     * 手动发送邮件
     */
    public function send_mail()
    {
        $action = A('AlloSendEmail', 'Console', true);
        $action->run();
    }

    /**
     * 供应商客户数据导入
     */
    public function import_supplier_customer()
    {
        if ($_FILES) {
            $lang_set = 'en-us';
            header("content-type:text/html;charset=utf-8");
            $filePath = $_FILES['file']['tmp_name'];
            vendor("PHPExcel.PHPExcel");
            $objPHPExcel = new PHPExcel();
            //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
            $PHPReader = new PHPExcel_Reader_Excel2007();
            if (!$PHPReader->canRead($filePath)) {
                $PHPReader = new PHPExcel_Reader_Excel5();
                if (!$PHPReader->canRead($filePath)) {
                    echo 'no Excel';
                    return;
                }
            }
            //读取Excel文件
            $PHPExcel = $PHPReader->load($filePath);
            //读取excel文件中的第一个工作表
            $sheet = $PHPExcel->getSheet(0);
            //取得最大的列号
            $allColumn = $sheet->getHighestColumn();
            //取得最大的行号
            $allRow = $sheet->getHighestRow();
            $temp = [];
            //Excel导入的供应商客户数据
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $flag = false;
                $data = [];
                $name = trim((string)$PHPExcel->getActiveSheet()->getCell("C" . $currentRow)->getValue());//供应商名称
                $type = trim((string)$PHPExcel->getActiveSheet()->getCell("D" . $currentRow)->getValue());//类型（供应商&客户）需要做拆分处理
                $exists = trim((string)$PHPExcel->getActiveSheet()->getCell("F" . $currentRow)->getValue());//是否已存在（中文类型，是或者否）
                $address = trim((string)$PHPExcel->getActiveSheet()->getCell("I" . $currentRow)->getValue());//详细办公地址
                if ($exists == '是') continue;
                if ($type == '供应商') {
                    $type = 0;
                } elseif ($type == '客户') {
                    $type = 1;
                } elseif ($type == '供应商&客户') {
                    $type = 3;
                }
                $data ['SP_NAME'] = $name;
                $data ['CREATE_TIME'] = date('Y-m-d H:i:s');
                $data ['CREATE_USER_ID'] = $_SESSION['userId'];
                $data ['DATA_MARKING'] = $type;
                $data ['COMPANY_ADDR_INFO'] = $address;
                if ($type == 0) {
                    $temp ['supplier'][md5($name)] = $data;
                } elseif ($type == 1) {
                    $temp ['customer'][md5($name)] = $data;
                } else if ($type == 3) {
                    $data ['DATA_MARKING'] = 0;
                    $temp ['supplier'][md5($name)] = $data;
                    $data ['DATA_MARKING'] = 1;
                    $temp ['customer'][md5($name)] = $data;
                }
            }
            //ERP中已有数据
            $model = M('_crm_sp_supplier', 'tb_');
            $ret = $model->field('SP_NAME, DATA_MARKING')->select();
            if ($ret) {
                $existsData = [];
                foreach ($ret as $key => $value) {
                    if ($value ['DATA_MARKING'] == 0) {
                        $existsData ['supplier'][] = md5($value ['SP_NAME']);
                    } else {
                        $existsData ['customer'][] = md5($value ['SP_NAME']);
                    }
                }
            }
            //再次筛选是否在ERP中存在数据
            if ($temp) {
                foreach ($temp as $key => &$value) {
                    foreach ($value as $k => $v) {
                        if (in_array($k, $existsData [$key])) {
                            unset($value [$k]);
                        }
                    }
                }

                foreach ($temp ['supplier'] as $k => $v) {
                    $stemp [] = $v;
                }

                foreach ($temp ['customer'] as $k => $v) {
                    $ctemp [] = $v;
                }
            }
            $ret_supplier = $model->addAll($stemp);
            $ret_customer = $model->addAll($ctemp);
            $this->assign('show', true);
            $this->assign('ret_supplier', count($stemp));
            $this->assign('ret_customer', count($ctemp));
        }

        $this->display('Log/import_supplier_customer');
    }

    /**
     * 获取已翻译的语言包
     */
    public function getCommonFileContent($name = null)
    {
        return $name;
    }

    /**
     * 备份已翻译的语言包
     */
    public function backCommonLanguagePackage($oname, $nname)
    {
        copy($oname, $nname);
    }

    /**
     * 获取基础配置数据
     */
    public function getDataDirectory()
    {
        $this->assign('isAutoRenew', BaseModel::isAutoRenew());
        $this->assign('spTeamCd', BaseModel::spTeamCdExtend());
        $this->assign('spJsTeamCd', BaseModel::spJsTeamCdExtend());
        $this->assign('copanyTypeCd', BaseModel::conType());
        $this->assign('spYearScaleCd', BaseModel::spYearScaleCd());
        $this->assign('country', BaseModel::getCountry());
        $this->assign('cmnCat', BaseModel::getCmnCat());
        $this->assign('saleTeamCd', BaseModel::saleTeamCdCur());
        $this->assign('contractState', BaseModel::contractState());
    }

    public function __get($name)
    {
        if (isset($this->access[$name])) {
            return true;
        }

        return false;
    }

    public function generateMethod()
    {
        $this->serviceName = $this->getParams()['serviceName'];
    }

    public function setParams()
    {
        $this->params = $this->getParams();
    }

    public function request_do()
    {
        $class = new ReflectionClass($this->module);
        if ($class->hasMethod($this->module . $this->serviceName)) {
            $method = new ReflectionMethod($this->module, $this->module . $this->serviceName);
            if ($method->isPublic()) {
                $back = $method->invoke($this, $this->params);
            } else {
                $back = ['code' => 400, 'message' => 'Bad Request'];
            }
        } else {
            $back = ['code' => 405, 'message' => 'Method Not Allowed'];
        }

        $this->ajaxReturn($back);
    }

    public $requestUrl;

    public function setRequestUrl($requestUrl)
    {
        $this->requestUrl = $requestUrl;
    }

    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * 记录接口日志
     */
    public function _catchMe($requestData = null, $responseData = null)
    {
        $filePath = '/opt/logs/logstash/';
        $fileName = 'logstash_' . date('Ymd') . '_erp_json.log';
        $a = parse_url($_SERVER["REQUEST_URI"]);
        parse_str($a["query"], $s);
        $a = $s['a'];
        $m1 = $s['m'];
        $m = M('');
//获取操作日志
        $res = $m->query("SELECT CONCAT(bbm_node.TITLE,bbm_node.NAME) as opt from bbm_node where lower(bbm_node.CTL)='$m1' AND lower(bbm_node.ACT)='$a' ");
        $action = $s ['a'];
        $tlog = D('TbMsUserOperationLog');
        $data ['uId'] = create_guid();
        $data ['noteType'] = 'N001940200';
        $data ['source'] = 'N001950500';
        $data ['ip'] = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
        $data ['space'] = null;
        $data ['cTime'] = date('Y-m-d H:i:s');
        $data ['cTimeStamp'] = time();
        $data ['action'] = $s ['a'];
        $data ['model'] = $s ['m'];
        $data ['msg'] = json_encode([
            'model' => MODULE_NAME,
            'msg' => [
                'GET' => $_GET,
                'POST' => $_POST,
                'action' => $s ['m'],
                'operation' => $res[0]['opt'],
                'uri' => $this->getRequestUrl() ? $this->getRequestUrl() : $_SERVER["REQUEST_URI"],
                'requestData' => $requestData,
                'responseData' => $responseData
            ]
        ]);
        $data ['user'] = $_SESSION['m_loginname'];
//        $tlog->add($data);
        //echo $tlog->_sql();
        //var_dump($tlog->getError());
//        $logFilePath = __DIR__ . '/../../Runtime/Logs/';
//        // 日志对象
//        $log = new \stdClass();
//        $trace = debug_backtrace(0);
//        $logName = date('Y-m-d') . @$trace[2]['function'] . '.log';
//        $txt = "\n------------------------------------------------------------------";
//        $txt .= "\n@@@用户：".$log->datetime = $_SESSION['m_loginname'];
//        $txt .= "\n@@@时间：".$log->datetime = date('Y-m-d H:i:s');
//        $txt .= "\n@@@来源：".$log->ip = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']);
//        $txt .= "\n@@@方法：".$log->method = $_SERVER["REQUEST_METHOD"];
//        $txt .= "\n@@@目标：".$log->url = $_SERVER["REQUEST_URI"];
//        $txt .= "\n@@@调用：".$log->callback = sprintf('%s::%s (line:%s)', @$trace[2]['class'], @$trace[2]['function'], @$trace[1]['line']);
//        $txt .= "\n@@@成功：".$log->apiIsok = 'SUCCESS';
//        $txt .= "\n@@@页面变量(GET)：\n".$log->varGet = print_r($_GET, true);
//        $txt .= "\n@@@页面变量(POST)：\n".$log->varPost = print_r($_POST, true);
//        $txt .= "\n------------------------------------------------------------------";
//        // 保存到日志文件

        $data ['id'] = $tlog->getLastInsID();
        $data ['msg'] = json_decode($data['msg']);
        $txt = json_encode($data);
        $file = $filePath . $fileName;
        fclose(fopen($file, 'a+'));
        $_fo = fopen($file, 'rb');
        //$old = fread($_fo, 1024 * 1024);
        fclose($_fo);
        //file_put_contents($file, $txt.$old);
        file_put_contents($file, $txt . "\n", FILE_APPEND);
    }

    /**
     * Json格式输出，同时判定是否为JSON-P请求，如果是JSON-P请求则会输出JSON-P格式数据。
     *
     * @param $result
     */
    protected function jsonOut($result)
    {
        $callback = I('jsonCallBack');
        if (!empty($callback)) {
            echo $callback . '(' . json_encode($result) . ')';
        } else {
            echo json_encode($result);
        }
        exit;
    }


    /**
     * 格式化输出
     *
     * @param int $code 状态码
     * @param string $info 提示信息
     * @param array $data 返回数据
     *
     * @return array $response 返回信息
     */
    public function formatOutput($code, $info, $data)
    {
        $response = [
            'code' => $code,
            'msg' => $info,
            'data' => $data
        ];

        return $response;
    }

    public function ajaxSuccess($data = [], $msg = 'success', $code = 2000)
    {
        if (empty($code) && $this->success_code) {
            $code = $this->success_code;
        }
        $this->ajaxReturn(['data' => $data, 'msg' => L($msg), 'code' => $code]);
    }

    public function ajaxError($data = [], $msg = 'error', $code = 3000)
    {
        if (!$code) {
            $code = 3000;
        }
        $this->ajaxReturn(['data' => $data, 'msg' => L($msg), 'code' => $code]);
    }

    /**
     * @param $rules
     * @param $data
     * @param $custom_attributes
     *
     * @throws Exception
     */
    public function validate($rules, $data, $custom_attributes)
    {
       
        ValidatorModel::validate($rules, $data, $custom_attributes);
        $message = ValidatorModel::getMessage();
       
        if ($message && strlen($message) > 0) {
            $this->error_message = json_decode($message, JSON_UNESCAPED_UNICODE);
            foreach ($this->error_message as $value) {
                throw new Exception(L($value[0]), 40001);
            }
        }
    }

    /**
     * 临时导入Excel获取相关数据
     */
    public function tempImportExcel()
    {
        if ($_FILES) {
            $model = new TempImportExcelModel();
            $model->import();
        } else {
            $this->display('Log/TempImportExcel');
        }
    }


    /**
     * @param array $data
     * @param array $customAttributes
     * @param array $check_code_arr
     *
     * @throws Exception
     */
    protected function checkCodeRight(array $data, array $customAttributes, array $check_code_arr)
    {
        $code_key_val_arr = CodeModel::getCodeKeyValArr(array_values($check_code_arr));
        $code_key_arr = array_keys($check_code_arr);
        foreach ($data as $key => $value) {
            if (in_array($key, $code_key_arr) && substr($value, 0, 6) != $check_code_arr[$key]
                && empty($code_key_val_arr[$value])) {
                throw new Exception($customAttributes[$key] . L('数据异常'));
            }
        }
    }

    /**
     * @param $exception
     *
     * @return array
     */
    protected function assemblyCatchRes($exception, $error_data = null)
    {
        $res = DataModel::$error_return;
        unset($res['info']);
        $code = $exception->getCode();
        $code ? $res['code'] = $code : null;
        $message = $exception->getMessage();
        $message ? $res['msg'] = $message : null;
        $error_data ? $res['data'] = $error_data : null;
        return $res;
    }

    /**
     * @param $data
     *
     * @return string|被过滤参数可为字符串、数组
     */
    protected function filterData($data)
    {
        return DataModel::filterBlank($data);
    }

    /**
     * @param $data
     * @param $rules
     * @param $attributes
     * @param $return_message
     *
     * @throws Exception
     */
    protected function validateInCapture($data, $rules, $attributes = [], $return_message = '请求数据错误')
    {
        if (!ValidatorModel::validate($rules, $data, $attributes)) {
            $this->error_data = json_decode(ValidatorModel::getMessage(), JSON_UNESCAPED_UNICODE);
            throw new Exception($return_message);
        }
    }

    public function exportCsv(&$data, $map, $excel_name, $bool = false)
    {
        $filename = '' . $excel_name . '' . date('YmdHis') . '.csv'; //设置文件名
        header('Content-Type: text/csv');
        header("Content-type:text/csv;charset=gb2312");
        header("Content-Disposition: attachment;filename=".L($filename));
        echo chr(0xEF) . chr(0xBB) . chr(0xBF);
        $out = fopen('php://output', 'w');
        $title_name = array_column($map, 'name');
        if (empty($title_name)) {
            $title_name = array_column($map, 0);//兼容格式
        }
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));   //wps都没问题，加这句兼容所有版本office，不乱码。
        fputcsv($out, $title_name);
        $fields = array_column($map, 'field_name');
        if (empty($fields)) {
            $fields = array_column($map, 1);
        }
        foreach ($data as $row) {
            $line = array_map(function ($field) use ($row, $bool) {
                //导出CSV文件去除双引号
                if (is_numeric($row[$field]) || $bool == true) {
                    return (string)$row[$field];
                }
                return (string)$row[$field] . "\t";
            }, $fields);
            fputcsv($out, $line);
        }
        fclose($out);
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

    //过滤参数
    public function trimParmas($data)
    {
        if (!is_array($data)) return trim($data);
        $params = [];
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                $params[$key] = $this->trimParmas($item);
            } else {
                $params[$key] = trim($item);
            }
        }
        return $params;
    }

    public function saveCsv(&$data, $map, $file_name)
    {
        $file_name = '' . $file_name . '_' . date('Ymd') . '.csv'; //设置文件名
        $out       = fopen(ATTACHMENT_DIR_EXCEL . $file_name, 'w');
        fputcsv($out, array_column($map, 'name'));
        $fields = array_column($map, 'field_name');
        foreach ($data as $row) {
            $line = array_map(function ($field) use ($row) {
                return (string)$row[$field] . "\t";
            }, $fields);
            fputcsv($out, $line);
        }
        fclose($out);
        return $file_name;
    }

    public function exportExcel($expTitle, $expCellName, $expTableData, $width = [], $number_fields = [])
    {
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        vendor("PHPExcel.PHPExcel");
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->freezePane('A2');
        $objPHPExcel->getDefaultStyle()->getFont()->setName('微软雅黑');
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ');
        for ($i = 0; $i < $cellNum; $i++) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '1', $expCellName[$i][1]);
            if (!empty($width)) {
                if ($width['type'] == 'auto_size') {
                    $objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i])->setAutoSize(true);
                }
                if ($width['size']) {
                    $objPHPExcel->getActiveSheet(0)->getColumnDimension($cellName[$i])->setWidth($width['size']);
                }
            }
        }
        for ($i = 0; $i < $dataNum; $i++) {
            for ($j = 0; $j < $cellNum; $j++) {
                $field = $expCellName[$j][0];
                $val_data = $expTableData[$i][$field];
                if (in_array($field, $number_fields)) {
                    if (!$val_data) $val_data = '0.00';//默认赋值指定数字字段
                    $val_data = sprintf("%.2f", $val_data);//保留两位小数
                }
                if (in_array($field, $number_fields)) {
                    //判断是数字，改变值的类型，方便excel计算
                    $objPHPExcel->getActiveSheet(0)->setCellValueExplicit($cellName[$j] . ($i + 2), $val_data, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                } else {
                    $objPHPExcel->getActiveSheet(0)->setCellValueExplicit($cellName[$j] . ($i + 2), $val_data, PHPExcel_Cell_DataType::TYPE_STRING);
                }
            }
        }
        $xlsTitle = $expTitle;
        $fileName = $expTitle . date('_YmdHis');
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls"); //attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 验证是否是可操作订单
     * @param $condition   条件
     * @param bool $sale_order  代销售订单  true : 验证   false: 不验证
     * @param bool $shopnc_order  shopnc平台订单  true : 验证   false: 不验证
     */
    public function isMayOperationOrders($condition,$sale_order = true,$shopnc_order = true)
    {
        $isOperation = true;
        $sale_condition = $shopnc_condition = $condition;
        if ($condition) {
            if ($sale_order) {
                $sale_condition_new = array();
                foreach ($sale_condition as $key => $value) {
                    $sale_condition_new['tb_op_order.' . $key] = $value;
                }
                $sale_condition_new['btc_order_type_cd'] = 'N003720003';   //   B2C订单类型::::代销售订单
                $ord_info = M('order', 'tb_op_')
                    ->join('INNER JOIN tb_op_order_extend ON tb_op_order.ORDER_ID = tb_op_order_extend.order_id 
                        AND tb_op_order.PLAT_CD = tb_op_order_extend.plat_cd ')
                    ->where($sale_condition_new)->field('btc_order_type_cd')->find();
                if ($ord_info) $isOperation = false;
            }
//            if ($isOperation) {
//                if ($shopnc_order) {
//                    $ord_info = M('order', 'tb_op_')->where($shopnc_condition)->field('STORE_ID')->find();
//                    if ($ord_info) {
//                        $store_info = M('store', 'tb_ms_')->field('BEAN_CD')->where(array('ID' => $ord_info['STORE_ID']))->find();
//                        if ($store_info && strtolower($store_info['BEAN_CD']) == 'shopnc') $isOperation = false;
//                    }
//                }
//            }
        }
        return $isOperation;
    }
    /*
     * 获取毫秒
     * @return float
     */
    public function getMsectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }

    public function getRoleInfo($role, $flag = false)
    {
        if (empty($role)) {
            return false;
        }
        $node = M('Node');
        $role_ids =
        is_array($role) ? $role : explode(',', $role);
        $where_node['ID'] = ['IN', $role];
        $temp_arr = $node->cache(true, 10)->where($where_node)->getField('ID,CTL,ACT', true);
       
        $role_val = [];
        foreach ($role_ids as $v) {
            $temp = $temp_arr[$v];
            if ($flag) {
                if (!empty($temp['CTL'])) {
                    $value = $temp['CTL'] . '/' . $temp['ACT'];
                    $role_val[$value] = $value;
                }
            } else {
                $role_val[$v] = !empty($temp['CTL']) ? $temp['CTL'] . '/' . $temp['ACT'] : null;
            }
        }
        return $role_val;
    }
}