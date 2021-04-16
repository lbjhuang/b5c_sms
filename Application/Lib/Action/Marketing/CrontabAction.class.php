<?php
/**
 * 渠道管理相关定时任务
 * Class CrontabOmsAction
 */

class CrontabAction extends Action
{

    // 每小时-GP推广库存预警
    public function gpStockWarningWxMsg()
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
            $promotionTaskService = new PromotionTaskService();
            $promotionTaskService->gpStockWarning();

            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start' => $start_time, 'end' => $end_time, 'expend' => ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
        exit;
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