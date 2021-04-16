<?php

class WxAboutModel extends BaseModel
{

    const CORP_ID = "ww696f35fe6e43781c";
    const AGENT_ID = "1000010";
    const SECRET = "k-27hnH5h86UYwwR-YEGNLBZd2IlHu2WfGiCP7bkYqw";

    public function __construct($name = '')
    {
        parent::__construct($name);

    }

    public static function getToken(){
        $token_url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=".self::CORP_ID."&corpsecret=".self::SECRET;
        $token = curl_get_json($token_url);
        return json_decode($token,true)['access_token'];
    }

    public static function sendWxMsg($user_ids, $content)
    {
        $token = self::getToken();
        $data['touser'] = $user_ids;
        $data['msgtype'] = "text";
        $data['agentid'] = self::AGENT_ID;
        $data['text']['content'] = $content;
        $url = "https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=".$token;
        return curl_get_json($url, json_encode($data,JSON_UNESCAPED_UNICODE));
    }
}