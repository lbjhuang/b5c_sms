<?php
/**
 * User: yangsu
 * Date: 19/1/28
 * Time: 15:01
 */


class ServerAction extends Action
{
    public function clean()
    {
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            $split_server = substr($_SERVER['SERVER_ADDR'], 0, 4);
            if ('10.8' != $split_server) {
                exec('rm -rf /opt/websrv/builds/erp/*');
            }
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
            
       
    }


    // 法务合同手动归档填充已完成审核节点状态 待手动归档入口不再使用时，此定时任务可停掉
    public function contractUpdateAuditStatus()
    {
        $start_time = $this->getMsectime();
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__.'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__.'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__.'CrontabLock', 1);
            
            $sql = "UPDATE tb_crm_contract SET audit_status_cd = 'N003660007' WHERE (audit_status_cd = '' OR audit_status_cd IS NULL)";
            $res = M()->query($sql);
            RedisModel::unlock(__FUNCTION__.'CrontabLock');
            Logs([__FUNCTION__.'Crontab run  success! '], __FUNCTION__, 'crontab_message');

        } catch (Exception $e) {
            $res = $e->getMessage();
            RedisModel::unlock(__FUNCTION__.'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');

        }
        p($res);
        $end_time =  $this->getMsectime();
        Logs(['start'=>$start_time,'end'=>$end_time,'expend'=> ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
        return $res;
    }
    // 合同到期提醒-每个周一工作日10点发送
    public function contractExpireRemind()
    {
        try {
            $contractService = new ContractService();
            $res = $contractService->sendEmail();
            p($res);
        } catch (Exception $e) {
            $res = $e->getMessage();

        }
        return $res;
    }

    // 合同到期提醒-每个周一工作日10点发送
    public function contractExpireRemindNew()
    {
        $start_time = $this->getMsectime();
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                echo __FUNCTION__.' is running! lock!（per 2H ）,已经执行过了，请2小时后再执行';
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            $contractService = new ContractService();
            $res = $contractService->sendEmailNew();

            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (\Exception $e) {
            $res = $e->getMessage();
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start'=>$start_time,'end'=>$end_time,'expend'=> ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
        p($res);
        return $res;
    }

    // 每个工作日早上九点发送发送通知
    public function allo_send_wx_msg()
    {
        $start_time = $this->getMsectime();
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            $questionService = new QuestionService();
            $res = $questionService->send_all_wx_msg();
            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (Exception $e) {
            $res = $e->getMessage();
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start'=>$start_time,'end'=>$end_time,'expend'=> ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');

        return $res;
            
       
        
    }

    // 每5分钟获取一次售后退款待审核列表,发送消息给相应负责人
    public function after_sale_refund_wx_msg()
    {
        $start_time = $this->getMsectime();
        header("Content-type: text/html; charset=utf-8");
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            header("Content-type: text/html; charset=utf-8");
            $afterSaleService = new OmsAfterSaleService();
            $res = $afterSaleService->after_sale_refund_wx_msg();
            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
            
        } catch (\Exception $e) {
            $res = $e->getMessage();
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start'=>$start_time,'end'=>$end_time,'expend'=> ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
        p($res);
        return $res;
    }

    // 每周四上午十点高管检查视频评分
    public function video_score_wx_msg()
    {
        $start_time = $this->getMsectime();
        header("Content-type: text/html; charset=utf-8");
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__.' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            header("Content-type: text/html; charset=utf-8");
            $VideoService = new VideoService();
            $res = $VideoService->wx_send_score();
            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
            
        } catch (\Exception $e) {
            $res = $e->getMessage();
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        $end_time =  $this->getMsectime();
        Logs(['start'=>$start_time,'end'=>$end_time,'expend'=> ($end_time - $start_time) / 1000], __FUNCTION__, 'expend_time');
        p($res);
        return $res;
       
    }

    public function get_order_origin_amount()
    {
        try{
            $t1 = time();
            $sql = "SELECT
    `PAY_TOTAL_PRICE_DOLLAR` 
FROM
    tb_op_order t
    LEFT JOIN tb_op_order_guds AS oog ON t.ORDER_ID = oog.ORDER_ID 
    AND t.PLAT_CD = oog.PLAT_CD
    LEFT JOIN tb_ms_ord_package AS mop ON t.ORDER_ID = mop.ORD_ID 
    AND t.PLAT_CD = mop.plat_cd
    LEFT JOIN ". PMS_DATABASE .".product_sku AS ps ON oog.B5C_SKU_ID = ps.sku_id
    LEFT JOIN tb_ms_store AS ms ON ms.ID = t.store_id
    LEFT JOIN tb_ms_ord AS mo ON t.B5C_ORDER_NO = mo.ORD_ID 
WHERE
    ( ( t.PARENT_ORDER_ID IS NULL ) ) 
GROUP BY
    t.ORDER_ID,
    t.PLAT_CD";
            $res = M()->query($sql);
            if ($res) {
                $total = array_column($res, 'PAY_TOTAL_PRICE_DOLLAR');
                $totalRes = array_sum($total);
                $redisRes = RedisModel::set_key('order_list_origin_amount', $totalRes, null, 3600);
            }
            p($totalRes);
            p($redisRes);
            ELog::add(['time_all' => time() - $t1,'totalRes'=>$totalRes, 'sql' => $sql],ELog::INFO);
        }catch(Exception $e){
            ELog::add(['msg' => $e->getMessage(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'line' => $e->getLine()], ELog::INFO);
        }
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