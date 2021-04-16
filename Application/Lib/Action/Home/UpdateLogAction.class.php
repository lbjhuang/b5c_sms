<?php
/**
 * 用户中心
 * User: b5m
 * Date: 2020/07/02
 * Time: 15:22
 */
class UpdateLogAction extends BaseAction
{
    public function _initialize()
    {
        $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
        if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['HTTP_ACCEPT'], 'application/json') !== false or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
            $json_str = file_get_contents('php://input');
            $json_str = stripslashes($json_str);
            $_POST = json_decode($json_str, true);
            $_REQUEST = array_merge($_POST, $_GET);
        }
        header('Access-Control-Allow-Origin: *'); 
        parent::_initialize();
    }

    /**
     * 更新日志-列表
     */
    public function updateLogList()
    {
       
        $this->assign('OPEN_HOST', OPEN_HOST);
        $this->assign('user_name', session('m_loginname'));
        $this->display('update-log-list');
    }

    /**
     * 更新日志-新增
     */
    public function updateLogNew()
    {
        $this->assign('OPEN_HOST', OPEN_HOST);
        $this->assign('user_name', session('m_loginname'));
        $this->display('update-log-new');
    }

    /**
     * 更新日志-编辑
     */
    public function updateLogEdit()
    {
        $this->assign('OPEN_HOST', OPEN_HOST);
        $this->assign('user_name', session('m_loginname'));
        $this->display('update-log-edit');
    }

    /**
     * 更新日志-详情
     */
    public function updateLogView()
    {
        $this->assign('OPEN_HOST', OPEN_HOST);
        $this->assign('user_name', session('m_loginname'));
        $this->display('update-log-view');
    }
}