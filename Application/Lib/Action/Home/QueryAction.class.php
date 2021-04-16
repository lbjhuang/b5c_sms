<?php

class ProxyAction extends Action
{
    public function check()
    {
        $store_proxy_ips = $this->getQueryLists();
        foreach ($store_proxy_ips as $proxy_ip => $group_store) {
            $temp_ping = $this->pingBaidu($proxy_ip);
            if (0 == $temp_ping['succeed_times']) {
                $msg = "监控到队列：{} 异常,影响 {$group_store}";
                @SentinelModel::addAbnormal('代理异常', $msg, null, 'proxy_group');
            }
        }
    }

    private function getQueryLists()
    {
        $Model = new Model();
        $sql = "SELECT
                    GROUP_CONCAT(STORE_NAME) AS group_store,
                    QUEUE_INFO
                FROM
                    `tb_ms_store`
                WHERE
                    DELETE_FLAG = 0
                AND STORE_STATUS = 0
                AND APPKES != '{}'
                AND QUEUE_INFO != ''
                AND ORDER_SWITCH = 1                
                GROUP BY
                    QUEUE_INFO";
        $res = $Model->query($sql);
        $query_lists = array_column($res, 'QUEUE_INFO');
        $temp_queue_placeholders = [];
        foreach ($query_lists as $query_list) {
            $temp_queue_placeholders[] = json_decode($query_list, true)['queuePlaceholder'];
        }
        return $temp_queue_placeholders;
    }


}
