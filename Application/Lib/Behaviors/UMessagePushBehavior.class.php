<?php
/**
 * Created by PhpStorm.
 * User: b5m
 * Date: 2018/3/29
 * Time: 10:23
 */
class UMessagePushBehavior extends Behavior
{
    private $_appKey = '5ab4bf5ff29d98270b000266';
    private $_masterSecret = 'cifsdm37giady7xyze7kubebwbptuov1';

    protected $template = [
        'ticker' => ''
    ];

    private function _setAppKey()
    {
        $this->_appKey = C('app_push_key');
    }

    private function _setMasterSecret()
    {
        $this->_masterSecret = C('app_push_secret');
    }

    /**
     * @param mixed $params
     * extract
     */
    public function run(&$params)
    {
        $this->_setAppKey();
        $this->_setMasterSecret();
        vendor('umeng_php.php.src.Demo');
        $demo = new ByMe($this->_appKey, $this->_masterSecret);
        $unicast = $demo->getAndroidUnicast();
        $unicast->setAppMasterSecret($this->_masterSecret);
        $unicast->setPredefinedKeyValue("expire_time", date('Y-m-d H:i:s', 3600 * 24 * 3 + time()));
        $unicast->setPredefinedKeyValue("description", $params ['description']);
        $unicast->setPredefinedKeyValue("production_mode", true);
        $unicast->setPredefinedKeyValue("appkey", $this->_appKey);
        $unicast->setPredefinedKeyValue("title", $params ['title']);
        $unicast->setPredefinedKeyValue("ticker", $params ['ticker']);
        $unicast->setPredefinedKeyValue("text", $params ['text']);
        $unicast->setPredefinedKeyValue("after_open", 'go_app');
        $unicast->setPredefinedKeyValue("play_vibrate", true);
        $unicast->setPredefinedKeyValue("play_lights", false);
        $unicast->setPredefinedKeyValue("play_sound", true);
        $unicast->setPredefinedKeyValue("display_type", 'notification');
        $unicast->setExtraField('idVal', $params ['idVal']);
        $unicast->setExtraField('transferNo', $params ['transferNo']);
        $unicast->setExtraField('fromMsgPush', '1');
        $unicast->setPredefinedKeyValue("alias", $params ['alias']);
        $unicast->setPredefinedKeyValue("alias_type", 'GshopperErp');
        $unicast->setPredefinedKeyValue("type", 'customizedcast');
        $unicast->setPredefinedKeyValue("timestamp", time());
        $_POST = $unicast;
        B('SYSOperationLog');
        $response = $unicast->send();
        $_POST = $response;
        B('SYSOperationLog', $response);
        // TODO: Implement run() method.
    }
}