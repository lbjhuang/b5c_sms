<?php
/**
 * erp 整站日志记录， app_end 结束后记录
 * 1、系统崩溃的情况下也能记录
 * 2、可分级别记录
 * 接口需要记录日志的使用方法：B('SYSOperationLog')
 * Created by PhpStorm.
 * User: b5m
 * Date: 2017/12/25
 * Time: 18:33
 */

defined('THINK_PATH') or exit();

class SYSOperationLogBehavior extends Behavior
{
    protected $options = [

    ];

    private $_filePath;
    private $_fileName;
    private $_groupName = GROUP_NAME;
    private $_actionName = ACTION_NAME;
    private $_moduleName = MODULE_NAME;
    private $_actList;
    private $_acceptType;

    /**
     * 执行行为 run方法是Behavior唯一的接口
     * @access public
     * @param mixed $params 行为参数
     * @return void
     *
     *
     */
    public function run(&$params)
    {
        $this->options ['test'] = C('rule_white_list');
        $this->generateFilePath();
        $this->setActList();
        $this->save();
        // TODO: Implement run() method.
    }

    public function record()
    {
        $data ['uId']           = create_guid();
        $data ['noteType']      = 'N001940200';
        $data ['source']        = 'N001950500';
        $data ['ip']            = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']);
        $data ['space']         = null;
        $data ['cTime']         = date('Y-m-d H:i:s');
        $data ['cTimeStamp']    = time();
        $data ['action']        = $this->_actionName;
        $data ['model']         = $this->_moduleName;
        $data ['nodeId']        = $this->getNodeId();
        $data ['group']         = $this->_groupName;
        if (IS_POST && !$_POST) {
            $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
            if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['HTTP_ACCEPT'], 'application/json') !== false or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
                $json_str = file_get_contents('php://input');
                $json_str = stripslashes($json_str);
                $_POST = json_decode($json_str, true);
                $_REQUEST = array_merge($_POST, $_GET);
            }
        }
        $data ['msg']           = [
            'model' => MODULE_NAME,
            'msg'   => [
                'ContentType' => $_SERVER['CONTENT_TYPE'],
                'Method' => $_SERVER["REQUEST_METHOD"],
                'URI' => $_SERVER["REQUEST_URI"],
                'GET' => $_GET,
                'POST'=> $_POST
            ]
        ];
        $data ['user'] = $_SESSION['m_loginname'];

        return $data;
    }

    public function save()
    {
        $pattern = explode(',', C('SYS_OPERATION_LOG_SAVE_PATTERN'));
        if ($pattern) {
            foreach ($pattern as $k => $v) {
                if (strtolower(trim($v)) == 'db') {
                    $this->saveToDb();
                }
                if (strtolower(trim($v)) == 'file') {
                    $this->saveToFile();
                }
            }
        } else {
            if (C('SYS_OPERATION_LOG_SAVE_PATTERN') == 'db')
                $this->saveToDb();
            elseif (C('SYS_OPERATION_LOG_SAVE_PATTERN') == 'file')
                $this->saveToFile();
        }
    }

    /**
     * 日志写入数据库
     *
     */
    public function saveToDb()
    {
        $model = D('TbMsUserOperationLog');
        $data = $this->record();
        $data ['msg'] = json_encode($data ['msg']);
        $model->add($data);
    }

    /**
     * 日志写入文件
     *
     */
    public function saveToFile()
    {
        $data = $this->record();
        $txt = json_encode($data);
        $file = $this->_filePath . $this->_fileName;
        fclose(fopen($file, 'a+'));
        $_fo = fopen($file, 'rb');
        fclose($_fo);
        file_put_contents($file, $txt . "\n", FILE_APPEND);
    }

    private function generateFilePath()
    {
        $basePath = '/opt/logs/logstash/';
        $time = date('Ymd');
        $fileName = 'logstash_' . $time . '_erp_json.log';
        $this->_filePath = $basePath;
        $this->_fileName = $fileName;
    }

    private function getOperation()
    {
        if ($this->_groupName == $this->getDefaultGroup())
            $acName = $this->_moduleName . '/' . $this->_actionName;
        else
            $acName = $this->_groupName . '/' . $this->_moduleName . '/' . $this->_actionName;

        return $acName;
    }

    /**
     * 获取当前请求的Accept头信息
     * @return string
     */
    protected function getAcceptType(){
        $type = [
            'html'  =>  'text/html,application/xhtml+xml,*/*',
            'xml'   =>  'application/xml,text/xml,application/x-xml',
            'json'  =>  'application/json,text/x-json,application/jsonrequest,text/json',
            'js'    =>  'text/javascript,application/javascript,application/x-javascript',
            'css'   =>  'text/css',
            'rss'   =>  'application/rss+xml',
            'yaml'  =>  'application/x-yaml,text/yaml',
            'atom'  =>  'application/atom+xml',
            'pdf'   =>  'application/pdf',
            'text'  =>  'text/plain',
            'png'   =>  'image/png',
            'jpg'   =>  'image/jpg,image/jpeg,image/pjpeg',
            'gif'   =>  'image/gif',
            'csv'   =>  'text/csv'
        ];
        foreach($type as $key=>$val){
            $array   =  explode(',',$val);
            foreach($array as $k=>$v){
                if(stristr($_SERVER['HTTP_ACCEPT'], $v)) {
                    if ($key == 'json')
                        $_POST = json_decode(file_get_contents('php://input'), true);
                }
                if(stristr($_SERVER['HTTP_ACCEPT'], $v)) {
                    $this->_acceptType = $key;
                }
            }
        }

        return false;
    }

    protected function getDefaultGroup()
    {
        return C("DEFAULT_GROUP");
    }

    public function setActList()
    {
        $this->_actList = $_SESSION ['actlist'];
    }

    public function getNodeId()
    {
        $currentOperation = $this->getOperation();
        foreach ($this->_actList as $key => $value) {
            if (strtolower($currentOperation) == strtolower($value)) {
                return $key;
            }
        }
    }
}

