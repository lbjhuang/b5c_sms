<?php
/**
 * Created by PhpStorm.
 * User: due
 * Date: 2019/3/12
 * Time: 16:59
 */

class ReviewMsg
{
    private $data;
    private $err;
    private $debug = false;
    private static $address_lang_map_arr = [
        '' => 'zh-cn',
        'DHDX' => 'zh-cn',
        'DXDH' => 'zh-cn',
        '上海' => 'zh-cn',
        '其他' => 'zh-cn',
        '北京' => 'zh-cn',
        '台湾' => 'zh-cn',
        '宝山' => 'zh-cn',
        '日本' => 'ja-jp',
        '智尚源' => 'zh-cn',
        '深圳' => 'zh-cn',
        '深圳动车国际' => 'zh-cn',
        '深圳航天科技' => 'zh-cn',
        '澳洲' => 'en-us',
        '美国' => 'en-us',
        '韩国' => 'ko-kr',
        '香港' => 'zh-hk',
    ];

    /**
     * @param $work_place
     *
     * @return string
     */
    public static function getPathToLang($work_place)
    {
        return self::$address_lang_map_arr[$work_place] ?: 'en-us';
    }

    /**
     * @param $user_info
     */
    private static function updateInsideLanguage($user_info)
    {
        $lang_map = self::$address_lang_map_arr;
        $lang = $lang_map[$user_info['work_place']] ?: 'en-us';
        if (cookie('think_language') != $lang) {
            cookie('think_language', $lang);
            L((new LanguageModel())->getOneTranslation($lang));
        }
    }

    public function create($review)
    {
        $review['review_no'] = ReviewModel::genReviewNo($review['review_type']);
        $this->data = $review;
        return $this;
    }

    public function send($msg)
    {
        $err = [];
        try {
            $wechat = new WechatMsg();
            empty($msg['appName']) and $msg['appName'] = 'ERP';
            empty($msg['msgtype']) and $msg['msgtype'] = 'textcard';
            empty($msg['textcard']['url']) and $msg['textcard']['url'] = ReviewModel::getBtnUrl($this->data['review_no'], $this->debug);
            Logs($msg, __FUNCTION__, 'wechat');
            if (!$msg_id = $wechat->create($msg)) {
                $err = $msg;
                throw new \Exception('创建消息失败');
            }
            $this->data['wechat_msg_id'] = $msg_id;
            if (!ReviewModel::create($this->data)) {
                $err = $this->data;
                throw new \Exception('创建审批信息失败');
            }
            if (!$wechat->send()) {
                $err = ['req' => $msg, 'res' => $wechat->err_msg];
                throw new \Exception('发送信息失败');
            }
            return true;
        } catch (\Exception $e) {
            $this->err = ['msg' => $e->getMessage(), 'error' => $err];
            ZUtils::saveLog($this->err, 'wechat_msg');
            return false;
        }
    }
    public function sendNode($msg)
    {
        $err = [];
        try {
            $wechat = new WechatMsg();
            empty($msg['appName']) and $msg['appName'] = 'ERP';
            empty($msg['msgtype']) and $msg['msgtype'] = 'textcard';
            
            empty($msg['textcard']['url']) and $msg['textcard']['url'] = ReviewModel::getStockUrl($this->data['order_id'], $this->debug);
            if (!$msg_id = $wechat->create($msg)) {
                $err = $msg;
                throw new \Exception('创建消息失败');
            }
            $this->data['wechat_msg_id'] = $msg_id;
            if (!ReviewModel::create($this->data)) {
                $err = $this->data;
                throw new \Exception('创建审批信息失败');
            }
            if (!$wechat->send()) {
                $err = ['req' => $msg, 'res' => $wechat->err_msg];
                throw new \Exception('发送信息失败');
            }
            return true;
        } catch (\Exception $e) {
            $this->err = ['msg' => $e->getMessage(), 'error' => $err];
            ZUtils::saveLog($this->err, 'wechat_msg');
            return false;
        }
    }

    public function getError()
    {
        return $this->err['msg'];
    }

    /**根据code获取用户信息，并返回审批单据数据
     *
     * @param $params
     *
     * @return \Illuminate\Database\Eloquent\Builder|mixed
     */
    public static function getReview($params)
    {
        $user_info = WechatMsg::autoLogin($params);
        self::updateInsideLanguage($user_info);
        $review = ReviewModel::findByNo($params['review_no'])->toArray();
        $detail_json = $review['detail_json'];
        $callback_function = $review['callback_function'];
        foreach ($detail_json['keys'] as &$v) {
            $v = L($v) ?: $v;
        }
        unset($v);
        /*foreach ($detail_json['data'] as &$v) {
            $v = L($v);
        }*/
        foreach ($review['allowed_man_json'] as &$item) {
            $item = strtolower($item);
        }
        if (!in_array(strtolower($user_info['name']), $review['allowed_man_json'])) {
            $detail_json['config']['agree_btn'] = 0;
            $detail_json['config']['refuse_btn'] = 0;
            $detail_json['config']['can_approve'] = 0;
        }
        if ($review['review_status']) {
            $detail_json['config']['agree_btn'] = 0;
            $detail_json['config']['refuse_btn'] = 0;
        }
        $detail_json['config']['status'] = $review['review_status'];
        unset($user_info['password']);
        $detail_json['user_info'] = $user_info;
        return [$detail_json, $review, $callback_function];
    }

    /**
     * 判断权限，回调处理审批请求
     *
     * @param $params array
     *
     * @return array|mixed
     */
    public static function handleReview($params)
    {
        if (!RedisLock::lock($params['review_no'])) {
            return ['code' => 3000, 'msg' => L('请求异常'), 'data' => ''];
        }
        $user = $_SESSION['m_loginname'];
        /*$user_info = WechatMsg::autoLogin($params);
        self::updateInsideLanguage($params,$user_info);*/
        $language = LanguageModel::getCurrent();
        Logs($language, __FUNCTION__, __CLASS__);
        $review = ReviewModel::findByNo($params['review_no']);

        $allowed_man_json = [];
        foreach ($review->allowed_man_json as $item) {
            $allowed_man_json[] = strtolower($item);
        }
        if (!in_array(strtolower($user), $allowed_man_json)) {
            $res = ['code' => 3000, 'msg' => L('无审批权限'), 'data' => ''];
        } else if ($review->review_status) {
            $res = ['code' => 3000, 'msg' => L('已审批'), 'data' => ''];
        } else {
            $cb_fun = $review->callback_function;
            $params['review'] = $review->toArray();
            $res = (new ReviewCallback)->callback($cb_fun, $params);
            $res['msg'] = L($res['msg']);
            unset($params['review']);
            $update_data = [
                'req_json' => $params,
                'res_json' => $res,
                'review_at' => date('Y-m-d H:i:s'),
                'review_by' => $user
            ];
            if ($res['code'] == 2000) {
                $update_data['review_status'] = $params['status'] ? 1 : 2;
            }
            $review->update($update_data);
            // 发送回执消息
            if (!empty($res['wechat']['receipt'])) {
                $wechat = new WechatMsg();
                $wechat->create([
                    'tousers' => [$_SESSION['m_email']],
                    'msgtype' => 'text',
                    'appName' => 'ERP',
                    'text' => ['content' => L($res['wechat']['receipt'])],
                ]);
                $wechat->send();
            }
            unset($res['wechat']);
        }
        RedisLock::unlock();
        return $res;
    }

    public function setDebug()
    {
        $this->debug = true;
        return $this;
    }
}