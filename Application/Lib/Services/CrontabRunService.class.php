<?php
/**
 * User: yangsu
 * Date: 19/12/9
 * Time: 15:30
 */

class CrontabRunService extends Service
{
    public $repository;

    public function __construct($use_db = true)
    {
        if (true === $use_db) {
            $this->repository = new CrontabRunRepository();
        }
    }

    public function updateFailedToMarkShipment()
    {
        $out_minutes_ago = date("Y-m-d H:i:s", strtotime("-2 minute"));
        $mothtime = date("Y-m-d H:i:s", strtotime("-3 month"));
        $failed_orders = $this->repository->getFailOrderThirdDeliverStatus($out_minutes_ago, $mothtime);
        if (empty($failed_orders)) {
            return 0;
        }
        $update_failed_orders = $this->repository->updateFailOrderThirdDeliverStatus($out_minutes_ago, $mothtime);
        //10369 标记发货日志优化:定时任务监测两分钟未成功的日志取消，不再展示
        /*foreach ($failed_orders as $failed_order) {
            OrderLogModel::addLog($failed_order['ORDER_ID'],
                $failed_order['PLAT_CD'],
                '标记发货失败（检测两分钟未标记发货成功）',
                null,
                'ERP SYSTEM'
            );
        }*/
        (new OmsService())->updateOrderFromEs($failed_orders, 'ORDER_ID', 'PLAT_CD');
        return $update_failed_orders;
    }

    public function checkPullOrderError()
    {
        $params = [
            'index' => 'api_execute_log',
            'type' => 'general',
            'body' => [
                'query' => [
                    'query_string' =>
                        [
                            'query' => 'logtype:1',
                        ],
                ],

                'sort' => [
                    'exectime' => 'desc'
                ],
            ],
            'size' => 1,
        ];
        $ESClientModle = new ESClientModel();
        $es_hits = $ESClientModle->search($params)['hits'];
        $total = $es_hits['total'];
        $difference = $total - (int)RedisModel::get_key('GENERAL_LAST_ERROR');
        if (0 < $difference) {
            $resbody = $es_hits['hits'][0]['_source']['resbody'];
            @SentinelModel::addAbnormal('general error log', '新增拉单错误记录:' . $difference . ",resbody:" . $resbody);
            RedisModel::set_key('GENERAL_LAST_ERROR', $total);
        }
        echo $total . '-' . $difference;
    }
}