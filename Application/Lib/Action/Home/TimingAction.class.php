<?php

/**
 * User: yangsu
 * Date: 17/12/1
 * Time: 16:08
 */
class TimingAction extends Action
{
    public function _initialize()
    {

    }

    public function index()
    {

    }

    public function send_out_goods()
    {
        LogsModel::$project_name = 'Timing_SendOutGoods';
        LogsModel::$time_grain = true;
        LogsModel::$act_microtime = microtime(true);
        LogsModel::$hash_key = crc32(LogsModel::$act_microtime);
        Logs('act', 'act');
        $rClineVal = RedisModel::lock('erpSendOutactlook', 300);
        if ($rClineVal) {
            //  停止自动发货
            $res = OrdersAction::send_out_goods();
            RedisModel::unlock('erpSendOutactlook');
            Logs('erpSendOutactlook');
        } else {
            echo 'no request';
            Logs($rClineVal, 'send_out_goods_lock');
            Logs('send_out_goods_cline');
            Logs(C('REDIS_SERVER'), 'client_info');
        }
        Logs('end', 'end');
    }

    public function hairnetDelivery()
    {

        $start_time = $this->getMsectime();
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__ . ' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            LogsModel::$project_name = 'hairnetDelivery';
            LogsModel::$time_grain = true;
            LogsModel::$act_microtime = microtime(true);
            LogsModel::$hash_key = crc32(LogsModel::$act_microtime);
            Logs('act', 'act');
            $rClineVal = RedisModel::lock('hairnetDelivery', 50);
            if ($rClineVal) {
                $res = OrdersAction::hairnetDelivery();
                RedisModel::unlock('hairnetDelivery');
                Logs('hairnetDelivery');
            } else {
                echo 'no request';
                Logs($rClineVal, 'hairnetDelivery');
                Logs('hairnetDelivery');
                Logs(C('REDIS_SERVER'), 'client_info');
            }
            Logs('end', 'end');
            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start' => $start_time, 'end' => $end_time, 'expend' => ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
    }

    public function showLast()
    {
        $Db = new Model();
        $ms_ord_db = $Db->query("SELECT * FROM `tb_ms_ord` WHERE `reset_num` > '0' ORDER BY `updated_time` DESC LIMIT 1");
        $this->assign('ms_ord_db', $ms_ord_db);
        $this->display('show_last');
    }
    /**
     * 获取毫秒
     * @return float
     */
    public function getMsectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }
}