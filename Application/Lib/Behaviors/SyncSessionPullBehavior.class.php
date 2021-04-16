<?php

/**
 * User: yangsu
 * Date: 18/1/8
 * Time: 10:10
 */
class SyncSessionPullBehavior extends Behavior
{

    public function run(&$params)
    {
        if (strstr($_SERVER['REQUEST_URI'], '/%')) {
            goto jumpOver;
        }
        if (!in_array(ACTION_NAME, C('FILTER_ACTIONS'))
            && !in_array(strtolower(GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME), C('FILTER_GLOBAL_MODEL_ACTIONS'))
            && ('local' != $_ENV["NOW_STATUS"] && !is_file(LOCAL_SESSION))) {
            $this->sessionGet();
            $this->addUserLog();
        }
        if(in_array(strtolower(GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME), C('FILTER_GLOBAL_MODEL_ACTIONS'))){
            session_write_close();
        }
        jumpOver:
    }

    public function addUserLog()
    {
        trace(session('m_loginname'), 'user_name', 'SYS', true);
    }

    public function sessionGet()
    {
        if (!session_id()) @ session_start();
        $r_client = RedisModel::client();
        $session_res = RedisModel::get_key(session_id(), $r_client);
        $session_get_arr = json_decode($session_res, true);
        if (1 == 1) {
            $_SESSION = $session_get_arr;
        }
    }

    private function userInfoDel()
    {
        unset($_SESSION[C('USER_AUTH_KEY')]);
        unset($_SESSION);
        session_destroy();
        cookie('_identity_key', null);
        cookie('_identity_sms', null);
        $this->assign('jumpUrl', U("Public/login"));

    }

}