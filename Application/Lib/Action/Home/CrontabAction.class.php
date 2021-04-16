<?php
/**
 * User: yangsu
 * Date: 18/11/6
 * Time: 18:01
 */


class CrontabAction extends Action
{
    /**
     * @var bool
     */
    private $is_open = true;

    /**
     * 请求路由必须绕过登录
     * 直接收 3s 内响应结果，超过 3s 直接放弃接收
     * 响应参数不应过长{简洁状态即可}
     *
     * expression crontab 语法
     * call_function 暂不支持
     * host 请求域
     * request_url 请求地址
     * is_async 异步请求
     * waiting_seconds 等待时间（必须 3 以内）
     * description 说明
     *
     * @var array
     */
    private $lists = [
        [
            'expression' => '30 10 * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=LogisticTo&a=sendAggregationMail&api=b08a8be1abd25efd858141757dbfc5c5',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '物流跟进提醒',
        ],
        [
            'expression' => '0 */12 * * *', // 每半天跑一次
            //'expression' => '*/1 * * * *', // 每一分钟跑一次
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=server&a=contractUpdateAuditStatus',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '法务合同手动归档填充已完成审核节点状态',
        ],
        [
            'expression' => '* * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=Console&a=allo_send_mail',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '调拨发送邮件',
        ],
        [
            'expression' => '0 0 * * 7 *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=Server&a=clean',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '清理构建文件',
        ],
        [
            'expression' => '*/5 * * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=Timing&a=hairnetDelivery',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '发网定时任务',
        ],
        [
            'expression' => '0 0 * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=Report&a=updateFromToday&api=b08a8be1abd25efd858141757dbfc5c5',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => 'B2B-更新应收报表',
        ],
        [
            'expression' => '30 0 1 */1 *',
//            'expression' => '*/5 * * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=Report&a=earlyMonthB2bReceivableExport&api=b08a8be1abd25efd858141757dbfc5c5',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => 'B2B-历史定时导出',
        ],
        [
            'expression' => '* * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=pur_send_mail',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '采购付款-批量核销异步发送邮件',
        ],
        [
            'expression' => '* * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=executeTask',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '待派单-获取运单监控',
        ],
        [
            'expression' => '0 9 * * 1-5', // 每个工作日9点 发送企业微信消息给各个相关的当前工单应处理人
            //'expression' => '*/1 * * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=server&a=allo_send_wx_msg',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '工作日早上九点工单通知',
        ],
        [
            'expression' => '*/5 * * * *', // 每隔5分种
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=server&a=after_sale_refund_wx_msg',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '订单退款审核通知',
        ], 
        [
            'expression' => '0 10 * * 4', // 每周四上午10点
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=server&a=video_score_wx_msg',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '高管视频评分通知',
        ], 
        [
            'expression' => '*/5 * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=triggerGcFaceOrderGet',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '谷仓获取单号失败后，主动触发获取单号',
        ],
        [
            'expression' => '0 0 1 * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=Report&a=earlyMonthExistingStockExport&api=b08a8be1abd25efd858141757dbfc5c5',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '仓储-现存量导出历史',
        ],
        [
            'expression' => '* * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=CrontabRun&a=updateFailedToMarkShipment',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '待派单-标记发货失败更新',
        ],
        [
            'expression' => '0 15 * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?g=OMS&m=Crontab&a=gpOrderRemind',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => 'GP订单派单后12小时未出库邮件提醒-每个自然天的15:00',
        ],
        /*[
            'expression' => '0 10 * * 1',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=server&a=contractExpireRemind',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '合同临期提醒邮件-每周一上午的10:00',
        ],*/
        [
            'expression' => '0 10 * * 1',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=server&a=contractExpireRemindNew',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '合同临期提醒合同负责人邮件-每周一上午的10:00',
        ],
        [
            'expression' => '*/30 9-21 * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=CrontabRun&a=checkPullOrderError',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '拉单-异常日志监控',
        ],
        [
            'expression' => '10 2 * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=PullOrderQueue&a=makeUpYesterday',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '店铺-补拉前一天订单',
        ],
        [
            'expression' => '0 9 * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=PullOrderQueue&a=makeUpYesterdayNineAmEbay',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => 'Ebay店铺[东一区至东八区] 每天早上9点补拉前一天订单',
        ],
        [
            'expression' => '*/5 * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?g=OMS&m=Crontab&a=checkOrderAddressValid',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '待派单订单地址校验-每5分钟',
        ],
        [
            'expression' => '*/30 * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?g=OMS&m=Crontab&a=testTask',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => 'erp运营派单走单流程_邮件通知-每半个小时',
        ],
        [
            'expression' => '20,50 8-22 * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=Proxy&a=check',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '拉单任务代理监控',
        ],
        [
            'expression' => '0 */1 * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=kyribaMailReceive',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '定期读取kyriba邮件',
        ],
        [
            'expression' => '0 */1 * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=kyribaSftpReceive',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '定期读取kyriba sftp回单',
        ],
        [
            'expression' => '30 * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=kyribaSftpReceiveAndSynchronize',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '定期读取kyriba sftp回单，银行回单同步',
        ],
        [
            'expression' => '15 * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=kyribaReceiveFailedMail',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '定期读取kyriba接收失败邮件',
        ],
        [
            'expression' => '30 07 * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=deleteAscFile',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '定期删除Kyriba的.asc文件',
        ],
        [
            'expression' => '* * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?g=report&m=ReportCrontab&a=team_inventory_list_data',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '定时生成团队库存汇总报表',
        ],
        [
            'expression' => '0 */2 * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?g=report&m=ReportCrontab&a=cost_list_data',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '定时预热一个半月内的成本报表',
        ],
        [
            'expression' => '* * * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?g=report&m=ReportCrontab&a=get_stock_count',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '缓存出入库列表的初始条数',
        ],
//        [
//            'expression' => '*/10 * * * *', // 每隔10分种
//            'call_function' => '',
//            'host' => 'ERP_CRON_TASK_URL',
//            'request_url' => '/index.php?m=server&a=get_order_origin_amount',
//            'is_async' => 'true',
//            'waiting_seconds' => 1,
//            'description' => '缓存订单列表无筛选条件下初始金额',
//        ],
//        [
//            'expression' => '*/1 * * * *',
//            'call_function' => '',
//            'host' => 'ERP_CRON_TASK_URL',
//            'request_url' => '/index.php?g=OMS&m=Crontab&a=checkOrderChargeOffStatus',
//            'is_async' => 'true',
//            'waiting_seconds' => 1,
//            'description' => '检查更新订单收入成本冲销状态状态-每1分钟',
//        ],
//        [
//            'expression' => '*/5 * * * *',
//            'call_function' => '',
//            'host' => 'ERP_CRON_TASK_URL',
//            'request_url' => '/index.php?m=crontabHandle&a=kyribaReceiveConsume',
//            'is_async' => 'true',
//            'waiting_seconds' => 1,
//            'description' => 'kyriba 回单内容消费',
//        ],

//        [
//            'expression' => '0 */1 * * *',
//            'call_function' => '',
//            'host' => 'ERP_CRON_TASK_URL',
//            'request_url' => '/index.php?g=Marketing&m=Crontab&a=gpStockWarningWxMsg',
//            'is_async' => 'true',
//            'waiting_seconds' => 1,
//            'description' => 'GP推广库存预警',
//        ],
        [
            'expression' => '*/5 * 13-15 1 *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=translatedata',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '11134框架外翻译优化历史数据处理-1月13-15号每5分钟',
        ],
        [
            'expression' => '0 1 * * *',#每天凌晨清除一次失效redis hash key
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=clearRedisHashByUid',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '刷新菜单权限清除失效redis key',
        ],

        [
            'expression' => '0 */1 * * *',
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=makeAllocationEffective',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '调拨时效列表数据生成',
        ],
        [
            'expression' => '*/2 * * * *',#每两分钟一次自动获取回邮单
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=autoReOrderApply',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '11245 OTTO自动获取回邮单',
        ],
        [
            'expression' => '*/10 * * * *',#每十分钟一次万邑通自动出库
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=send_out_goods_winit',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '11283 万邑通-作废出库-自动出库',
        ],
//        [
//            'expression' => '*/2 * * * *',#每2分钟一次万邑通自动退回到待派单
//            'call_function' => '',
//            'host' => 'ERP_CRON_TASK_URL',
//            'request_url' => '/index.php?m=crontabHandle&a=return_to_patch',
//            'is_async' => 'true',
//            'waiting_seconds' => 1,
//            'description' => '11283 万邑通-作废出库-自动退回到待派单',
//        ]
        [
            'expression' => '*/2 * * * *',#每2分钟一次同步订单售后状态
            'call_function' => '',
            'host' => 'ERP_CRON_TASK_URL',
            'request_url' => '/index.php?m=crontabHandle&a=change_order_after_sale&api=b08a8be1abd25efd858141757dbfc5c5',
            'is_async' => 'true',
            'waiting_seconds' => 1,
            'description' => '11306 GP新后台对接退款流程(未发货状态前)',
        ]
    ];

    /**
     *
     */
    public function run()
    {
        if ($this->is_open) {
            try {
                LogsModel::initConfig('Crontab', false);
                Logs('crontab act ' . $_SERVER ['HTTP_HOST']);
                $do_lists = $this->extractDoLists($this->loadingLists());
                Logs($do_lists, 'do lists');
                $this->doLists($do_lists);
            } catch (Exception $exception) {
                Logs($exception->getMessage());
            }
        }
    }

    /**
     * @param $data
     */
    private function doLists($data)
    {
        foreach ($data as $value) {
            if ($value['is_async']) {
                if ($value['waiting_seconds'] > 3 || empty($value['waiting_seconds'])) {
                    $value['waiting_seconds'] = 1;
                }
                $request_url = constant($value['host']) . $value['request_url'];
                $temp_res = ApiModel::getRequest($request_url, $value['waiting_seconds']);
                Logs([$request_url, json_encode($temp_res)], 'run list');
            }
        }
    }

    /**
     * @return array
     */
    private function loadingLists()
    {
        $lists = array_filter($this->lists, function ($temp_value) {
            if ($temp_value['expression'] && $temp_value['host'] && $temp_value['request_url'] && $temp_value['is_async']) {
                return true;
            } else {
                Logs(json_encode($temp_value), 'list parameter has null');
            }
        });
        return $lists;
    }

    /**
     * @param $data
     *
     * @return array
     * @throws Exception
     */
    private function extractDoLists($data)
    {
        $do_lists = [];
        foreach ($data as $value) {
            $cron = Cron\CronExpression::factory($value['expression']);
            if ($cron->isDue()) {
                $do_lists[] = $value;
            }
        }
        if (empty($do_lists)) {
            throw new Exception('no having do list');
        }
        return $do_lists;
    }

}