<?php
/**
 * User: yangsu
 * Date: 19/2/28
 * Time: 13:32
 */

class WorkWeChatAction extends BaseAction
{
    public function _initialize()
    {
    }

    public function index()
    {
        echo 'test';
    }

    public function approval()
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=sdXyQjz7YJoO52DusSMDDvCFugzvRt-eGBDZHJJoSwf583bjaS2uiWg7XzD0cJHCHu6lRW0w-YASWtvb77Id4ZhiIu-fligp2nIjE3XgpHpo_DErx3B0-yJ2ZYW7k_Cj8jLDgV6u0y3qlfjOSIWYCcp57WGJ1aZq2up6CBS3G0pyYhSPQCCvGfarTBaZmMm9kuUnaVoBMEcxoh6tpOn2KA';
        $qyapi_res = file_get_contents($url);
        $this->assign('qyapi_res', $qyapi_res);
        $this->display();
    }

    public function getThirdPartyOpenPage()
    {
        Logs($_REQUEST);
        echo json_encode($_REQUEST);
    }
}