<?php

class ProxyAction extends Action
{
    public function check()
    {
        header('Content-Type: text/html; charset=utf-8');
        ini_set('max_execution_time', 600);
        echo 'this is check proxy';
        try {
            #为定时任务加锁
            $bl = RedisModel::lock(__FUNCTION__ . 'CrontabLock', 2 * 60 * 60);
            if (!$bl) {
                Logs([__FUNCTION__ . 'CrontabLock is lock'], __FUNCTION__, 'crontab_message');
                return __FUNCTION__ . ' is running! lock!';
            }
            RedisModel::hset('crontabLockHash', __FUNCTION__ . 'CrontabLock', 1);
            #业务start
            $store_proxy_ips = $this->getStoreProxyIps();
            $frequency = 5;
            foreach ($store_proxy_ips as $proxy_ip => $group_store) {
                $temp_ping = $this->pingBaidu($proxy_ip, $frequency);
                if (0 == $temp_ping['succeed_times']) {
                    $content = ">**%s**
><font color=#006666 > %s</font> <font color=#dd0000 > 异常</font> 
> 影响: <font color=comment >%s </font>
> 尝试 <font color=#000066 > %s</font> 次, 异常 <font color=#dd0000 >%s</font> 次";
                    $msg = vsprintf($content, ["[{$_ENV['NOW_STATUS']}]监控到代理", $proxy_ip, str_replace(['%0a'], '', $group_store), $frequency, $temp_ping['defeat_times']]);
                    @SentinelModel::addAbnormal('监控到代理', $msg, null, 'proxy_group', 'MonitoringAlarm');
                    echo $msg . PHP_EOL;
                }
            }

            #业务end
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs([__FUNCTION__ . 'Crontab run  success! '], __FUNCTION__, 'crontab_message');
        } catch (\Exception $e) {
            RedisModel::unlock(__FUNCTION__ . 'CrontabLock');
            Logs(['message' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()], __FUNCTION__, 'crontab_error');
        }
        

    }

    private function getStoreProxyIps()
    {
        $Model = new Model();
        $sql = "SELECT
                    GROUP_CONCAT(
                        PLAT_NAME,
                        ';',
                        ID,
                        ';',
                        STORE_NAME,
                        ';',
                        store_by,
                        '%0a'
                    ) AS group_store,
                    PROXY
                FROM
                    `tb_ms_store`
                WHERE
                    DELETE_FLAG = 0
                AND STORE_STATUS = 0
                AND APPKES != '{}'
                AND QUEUE_INFO != ''
                AND ORDER_SWITCH = 1
                AND PROXY != ''
                GROUP BY
                    PROXY";
        $res = $Model->query($sql);
        return array_column($res, 'group_store', 'PROXY');
    }

    private function pingBaidu($proxy_ip = false, $times = 3)
    {
        $header = array(
            "accept: application/json",
            "accept-encoding: gzip, deflate",
            "accept-language: en-US,en;q=0.8",
            "content-type: application/json",
            "user-agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106 Safari/537.36",
        );
        $url = 'http://www.baidu.com/';
        $result['succeed_times'] = 0; //成功次数
        $result['defeat_times'] = 0; //失败次数
        $result['total_spen'] = 0; //总用时
        for ($i = 0; $i < $times; $i++) {
            $s = microtime();
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url); //设置传输的url
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header); //发送http报头
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate'); // 解码压缩文件
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //不验证证SSL书
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); //不验证SSL证书

            if (@$proxy_ip != false) { //使用代理ip
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Client_Ip: ' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255),
                ));
                curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'X-Forwarded-For: ' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255),
                ));
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                curl_setopt($curl, CURLOPT_PROXY, $proxy_ip);
            }

            curl_setopt($curl, CURLOPT_TIMEOUT, 5); // 设置超时限制防止死循环
            $content = curl_exec($curl);
            if (strstr($content, '百度一下，你就知道')) {
                $result['list'][$i]['status'] = 1;
                $result['succeed_times'] += 1;
                break;
            } else {
                $result['list'][$i]['status'] = 0;
                $result['defeat_times'] += 1;
            }
            $e = microtime();
            $result['total_spen'] += abs($e - $s);
            $result['list'][$i]['spen'] = abs($e - $s);
        }
        return $result;
    }

}
