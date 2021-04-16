<?php
/**
 * Created by PhpStorm.
 * User: due
 * Date: 2019/3/11
 * Time: 14:25
 */

use App\Models\TbSysMessageWechat;

@import('@.Model.Orm.TbSysMessageWechat');

class WechatMsg
{
    public $model;

    /**发送消息返回数据
     *
     * @var
     */
    public $err_msg;
    const CORP_ID = 'ww696f35fe6e43781c';
    public static $access_token_redis_key = [
        'ERP' => 'wx-token-ERP'
    ];

    /**获取app的access_token
     *
     * @param $app_name
     *
     * @return mixed
     */
    public static function getAccessToken($app_name = 'ERP')
    {
        $key = self::$access_token_redis_key[$app_name];
        return RedisModel::client()->get($key);
    }

    /**根据企业微信用户code获取用户信息（花名拼音和邮箱）
     *
     * @param $code
     * @param string $app_name
     *
     * @return mixed
     */
    public static function getUserInfo($code, $app_name = 'ERP')
    {
        $access_token = self::getAccessToken($app_name);
        Logs($access_token, __FUNCTION__. '-access-token', 'wechat');
        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token={$access_token}&code={$code}";
        $ret = json_decode(curl_get_json_get($url), true);
        Logs($ret, __FUNCTION__. '-wx-user-info', 'wechat');
        $debug_user = I('debug_user');
        if ($debug_user && in_array($_SESSION['m_login_huaming'], ['fuming','yangsu'])) {
            $ret['UserId'] = I('debug_user');
        }
        if (isset($ret['UserId'])) {
            $info = M('user_source', 'tb_wework_')->alias('us')
                ->field('M_NAME as name,us.email,M_PASSWORD as password,card.WORK_PALCE as work_place')
                ->join('left join bbm_admin ba on ba.M_EMAIL=us.email')
                ->join('left join tb_hr_card card on card.EMPL_ID=ba.empl_id')
                ->where(['us.userid' => $ret['UserId']])
                ->find();
            Logs([$info, $_SESSION['m_login_huaming']], __FUNCTION__. '-user-info', 'wechat');
        }
        return $info;
    }

    /**获取用户信息并且自动登录
     *
     * @see PublicAction::checkLogin() 登录接口
     *
     * @param $params
     *
     * @return mixed
     */
    public static function autoLogin($params)
    {
        // 代理重定向
        if ($params['redirect']) {
            $redirect = $params['redirect'];
            unset($params['redirect']);
            $url = 'http://' . $redirect . U('api/wechat_approve', $params);
            header('Location: ' . $url);
            exit;
        }
        $user_info = WechatMsg::getUserInfo($params['code']);
        // 自动登录
        $_POST['username'] = $user_info['name'];
        $_POST['password'] = $user_info['password'];
        $_POST['wechat'] = 1;
        // debug
        if ('stage' == $_ENV['NOW_STATUS'] && $params['debug_user']) {

        }
        A('Home/Public')->checkLogin();
        return $user_info;
    }

    /**获取发送消息接口
     *
     * @return string
     */
    public function getSendApiUrl()
    {
        return WECHAT_API . '/weixin/out/sendMessage';
    }

    public function delete($id)
    {
        TbSysMessageWechat::find($id)->delete();
    }

    /**创建消息
     *
     * @param $msg array 支持一条消息
     *
     * @return int
     */
    public function create($msg)
    {
        $data = [
            'wechat_application_name' => $msg['appName'],
            'wechat_msg_type' => $msg['msgtype'],
            'receiver_by_json' => $msg['tousers'],
            'api_request_url' => $this->getSendApiUrl(),
            'api_request_body_json' => $msg,
            'created_by' => $_SESSION['m_loginname'],
            'updated_by' => $_SESSION['m_loginname'],
        ];
        $this->model = new TbSysMessageWechat();
        $this->model->fill($data);
        $this->model->save();
        return $this->model->id;
    }

    /**发送消息
     *
     * @return bool
     */
    public function send()
    {
        $data = [
            'data' => [
                $this->model->api_request_body_json,
            ],
            'processId' => guid()
        ];

        $json_encode = json_encode($data);

        $ret = curl_get_json($this->getSendApiUrl(), $json_encode);
        $ret = json_decode($ret, true);
        $ret['data'][0]['data'] = [];
        $this->model->update([
            'api_response_json' => $ret,
            'api_request_body_json' => $data
        ]);
        $this->err_msg = $ret;
        return $ret['code'] == 2000;
    }

    public function sendText($user_email, $content)
    {
        if (empty($user_email)) {
            return false;
        }
        if (is_string($user_email)) {
            $tousers = [$user_email];
        }
        if (is_array($user_email)) {
            $tousers = $user_email;
        }
        $this->create([
            'tousers' => $tousers,
            'msgtype' => 'text',
            'appName' => 'ERP',
            'text' => ['content' => L($content)],
        ]);
        return $this->send();
    }

    /**
     *  防止冲突 重写
     */
    public function sendTextNew($user_email, $content)
    {
        if (empty($user_email)) {
            return false;
        }
        if (is_string($user_email)) {
            $tousers = [$user_email];
        }
        if (is_array($user_email)) {
            $tousers = $user_email;
        }
        $this->create([
            'tousers' => $tousers,
            'msgtype' => 'text',
            'appName' => 'ERP',
            'text' => ['content' => $content],
        ]);
        return $this->send();
    }
}