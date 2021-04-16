<?php

/**
 * 公共控制器
 * User: Muzhitao
 * Date: 2016/1/15 0015
 * Time: 18:09
 * Email：muzhitao@vchangyi.com
 */
class PublicAction extends BaseAction
{

    private function isHttps()
    {
        if (true == I('is_https')) {
            return true;
        }
        return false;
    }


    /**
     * 登录界面
     */
    public function login()
    {
        if (!$this->isHttps() && 'online' === $_ENV["NOW_STATUS"] && 1 == RedisModel::get_key('erp_switch_update_https_request')) {
            $supply = get_supply_info();
            header('HTTP/1.1 301 Moved Permanently');
            header("Location: https://{$_SERVER['HTTP_HOST']}/index.php?m=public&a=login&type=first&is_https=true{$supply}");
            die();
        }
        $this->display();
    }

    /**
     * 验证码
     */
    public function verify()
    {

        ob_clean();
        import('ORG.Util.Image');
        Image::buildImageVerify($length = 4, $mode = 1, $type = 'png', $width = 58, $height = 38);
    }

    public function error()
    {

    }

    /**
     * 用户登录检测
     */
    public function checkLogin()
    {
        import('ORG.Util.RBAC');
        //include_once 'IP.class.php';
        $map = [];
        $map['M_NAME'] = trim(I("post.username"));
        $map['IS_USE'] = 0;
        $map['M_STATUS'] = ['neq', 2];
        C('USER_AUTH_MODEL', 'Admin');
        $md5_pwd = md5(I("post.password"));
        // 微信消息自动登录
        if (I('post.wechat')) {
            $md5_pwd = I("post.password");
        }
        if (! $map['M_NAME']) list($map['M_NAME'], $md5_pwd, $request_type, $lang_type) = $this->getAppRequest();
        $authInfo = RBAC::authenticate($map);
        Logs([$map, $authInfo], __FUNCTION__, 'fm');
        if (empty($authInfo)) {
            $this->ajaxReturn(0, 'Account does not exist or is disabled', 0);
        } else {
            if (strtoupper($authInfo['M_PASSWORD']) !== strtoupper($md5_pwd) && 0 != $authInfo['empl_id']) {
                $this->ajaxReturn(0, 'OA Account Or Password error', 0);
            } elseif ($authInfo['M_PASSWORD'] != md5(I("post.password") . C('PASSKEY')) && 0 == $authInfo['empl_id']) {
                $this->ajaxReturn(0, 'Account Or Password error', 0);
            } else {
                $authInfo['ROLE_ID'] = D('admin_role')->where(['M_ID' => $authInfo['M_ID']])->getField('ROLE_ID', true);
                $_SESSION[C('USER_AUTH_KEY')] = $authInfo['M_ID'];
                $_SESSION['role_id'] = $authInfo['ROLE_ID'];
                $_SESSION['user_id'] = $authInfo['M_ID'];
                $_SESSION['m_loginip'] = get_client_ip();
                //$country = IP::find(get_client_ip());
                //                if($country[0] == '美国'){
                //                    $lurl = 'en-us';
                //                }elseif($country[0] == '韩国'){
                //                    $lurl = 'ko-kr';
                //                }elseif($country[0] == '日本'){
                //                    $lurl = 'ja-jp';
                //                }else{
                //                    $lurl = 'zh-cn';
                //                }
                $lurl = null;
                $_SESSION['m_logintime'] = $authInfo['M_LOGINTIME'];
                $_SESSION['m_loginname'] = $authInfo['M_NAME'];
                $_SESSION['m_login_huaming'] = $authInfo['huaming'];
                $_SESSION['m_email'] = $authInfo['M_EMAIL'];
                $_SESSION['emp_sc_nm'] = $authInfo['EMP_SC_NM'];
                $role = M('role')->where(['ROLE_ID' => ['in', $authInfo['ROLE_ID']]])->getField('ROLE_ACTLIST', true);
                $role = implode(',', $role);
                $_SESSION['actlist'] = $this->getRoleInfo($role);
                $_SESSION['actlist_value'] = $this->getRoleInfo($role, true);
                $_SESSION['actlist_value_lower'] = array_map(function ($value) {
                    return strtolower($value);
                }, $_SESSION['actlist']);
                $_SESSION['actlist_value_lower'] = array_unique(array_values($_SESSION['actlist_value_lower']));
                if (in_array(1, $authInfo['ROLE_ID'])) {
                    $_SESSION[C('ADMIN_AUTH_KEY')] = true;
                }
                /* 更新登录数据 */
                $data['M_LOGINIP'] = get_client_ip();
                $data['M_LOGINTIME'] = time();
                $data['M_LOGINNUMS'] = $authInfo['M_LOGINNUMS'] + 1;

                M(C('USER_AUTH_MODEL'))->where('M_ID = ' . $authInfo['M_ID'])->save($data);
                // to mark in cookie field
                if (I("post.is_remember")) {
                    $tmpCookie = $_SESSION;
                    unset($tmpCookie['actlist']);
                    unset($tmpCookie['actlist_value']);
                    $tmpCookie = base64_encode(base64_encode(serialize($tmpCookie)));
                    cookie('_identity_sms', $tmpCookie, ['expire' => 3600 * 24]);
                    cookie('_identity_key', md5(C('PASSKEY') . $tmpCookie), ['expire' => 3600 * 24]);
                }
                
                $this->syncSession();
                # 更新本地session
                $this->saveSession();
                if ('gapp_zh' == $request_type) $lurl = $this->responseAppData($lang_type, $authInfo['EMP_SC_NM']);
                if (I('post.wechat')) return ['code' => 2000, 'msg' => 'success', 'data' => []];
                $this->ajaxReturn($lurl, 'Login success！', 1);
            }
        }

    }

    private function getAppRequest()
    {
        $user = $pwd = null;
        $data = file_get_contents("php://input", 'r');
        $res = json_decode($data, true);
        if ('gapp_zh' == $res['requesttype']) {
            $user = $res['username'];
            $pwd = $res['password'];
        }
        Logs($res, '$res');
        return [$user, $pwd, $res['requesttype'], $res['langtype']];
    }

    /**
     * @param string $lang_type
     * @param        $EMP_SC_NM
     *
     * @return mixed
     */
    private function responseAppData($lang_type = 'zh-cn', $EMP_SC_NM)
    {
        $sess_data = $_SESSION;
        $info = $this->getInfoData($sess_data, $EMP_SC_NM);
        $res['user_info'] = $info;
        $res['m_img'] = $this->getUserImg($info['m_loginname']);
        $res['user_author'] = null;
        $res['cookie'] = session_name() . '=' . session_id() . '; think_language=' . $lang_type;
        unset($sess_data);
        return $res;
    }

    /**
     * @param $user
     *
     * @return null|string
     */
    private function getUserImg($user)
    {
        $M = M();
        $PIC = $M->table('tb_hr_card')->where('ERP_ACT = \'' . $user . '\'')->getField('PIC');
        $str = null;
        if ($PIC) {
            $str = 'index.php?m=Api&a=show&filename=' . $PIC;
        }
        return $str;
    }

    /**
     * 退出登录操作
     */
    public function logout()
    {

        if (! empty($_SESSION[C('USER_AUTH_KEY')])) {
            //            $this->login_log($_SESSION['real_name'], '退出成功');
            $del_state = RedisModel::del_key(session_id());
            Logs($del_state, '$del_state');
            unset($_SESSION[C('USER_AUTH_KEY')]);
            unset($_SESSION);
            session_destroy();
            cookie('_identity_sms', null);
            cookie('_identity_key', null);
            $this->assign('jumpUrl', U("Public/login"));
            $this->success('退出成功');
        } else {
            $this->error('已经登出了');
            js_redirect(PHP_FILE . C('USER_AUTH_GATEWAY'));
        }
    }

    /**
     * 后台用户登录日志
     *
     * @param string $username
     * @param string $option
     */
    protected function login_log($username = '', $option = '登录失败')
    {

        $System = M('SystemLog');
        $data['ip'] = get_client_ip();
        $data['time'] = time();
        $data['username'] = $username;
        $data['options'] = $option;

        // 插入数据
        $System->add($data);
    }

    //权限
    public function getRoleInfo($role, $flag = false)
    {
        if (empty($role)) {
            return false;
        }
        $node = M('Node');
        $role_ids = explode(',', $role);
        $where_node['ID'] = ['IN', $role];
        $temp_arr = $node->cache(true, 10)->where($where_node)->getField('ID,CTL,ACT', true);
        $role_val = [];
        foreach ($role_ids as $v) {
            $temp = $temp_arr[$v];
            if ($flag) {
                if (! empty($temp['CTL'])) {
                    $value = $temp['CTL'] . '/' . $temp['ACT'];
                    $role_val[$value] = $value;
                }
            } else {
                $role_val[$v] = ! empty($temp['CTL']) ? $temp['CTL'] . '/' . $temp['ACT'] : null;
            }
        }
        return $role_val;
    }


    private function syncSession()
    {
        $SyncSessionPush = new SyncSessionPushBehavior();
        $SyncSessionPush->run();
    }

    /**
     * @param $sess_data
     *
     * @return mixed
     */
    private function getInfoData($sess_data, $EMP_SC_NM)
    {
        $info['userId'] = $sess_data['userId'];
        $info['role_id'] = $sess_data['role_id'];
        $info['user_id'] = $sess_data['user_id'];
        $info['m_loginip'] = $sess_data['m_loginip'];
        $info['m_logintime'] = $sess_data['m_logintime'];
        $info['m_loginname'] = $sess_data['m_loginname'];
        $info['m_email'] = $sess_data['m_email'];
        $info['role_nm'] = $EMP_SC_NM;
        return $info;
    }

    /**
     * 保存session 到本地
     * @author Redbo He
     * @date 2021/1/20 15:47
     */
    public function saveSession()
    {
        if(isLocalEnv()) {
            file_put_contents(LOCAL_SESSION, json_encode(array_filter($_SESSION),JSON_PRETTY_PRINT));
        }
    }
}
