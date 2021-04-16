<?php
/**
 * User: yangsu
 * Date: 19/3/1
 * Time: 10:05
 */

class SentinelService extends Service
{

    /**
     * @var $SentinelRepository
     */
    private $SentinelRepository;
    private $notices = [
        'default' => ['yangsu', 'baisui', 'leshan', 'feisong'],
        'b2b_notice' => ['yangsu', 'baisui', 'leshan', 'feisong'],
        'oms_notice' => ['yangsu', 'leshan', 'fuming'],
        'transfer_by' => ['yangsu', 'leshan'],
        'scm_operation_notice' => ['tianrui'], // 监控采购各种触发操作生成抵扣/应付记录失败
        'oms_patch_notice' => ['fuming'],
        'pur_notice' => ['fuming', 'tianrui'],
        'warehouse_notice' => ['fuming', 'leshan'],
        'fin_notice' => ['fuming', 'yangsu', 'leshan', 'tianrui'],
        'gp_refund_notice' => ['fuming', 'jifan','rui.xu'],
        'kyriba_notice' => ['fuming', 'shenmo', 'tianrui'],
        'customs_notice' => ['tianrui'],
        'question_notice' => ['tianrui'],
        'contract_email_remind' => ['tianrui'],
        'inve_notice' => ['tianrui'],
        'contract_audit_notice' => ['tianrui'],
        'proxy_group_test' => ['yangsu'],
        'proxy_group' => [ 'feisong', 'zhuoping', 'yangsu', 'leshan', 'jifan', 'baisui', 'yuanxi', 'baifeng','zhanfei'],
        'mongodb_group' => ['fuming', 'tianrui'],
        'general' => ['fuming'],
        'fin_flow_notice' => ['tianrui'],
        ];
    private $notices_dev = [
        'default' => ['yangsu', 'baisui', 'leshan', 'feisong'],
        'b2b_notice' => ['yangsu', 'baisui', 'leshan', 'feisong'],
        'oms_notice' => ['yangsu', 'leshan', 'fuming'],
        'transfer_by' => ['yangsu', 'leshan'],
        'scm_operation_notice' => ['tianrui'], // 监控采购各种触发操作生成抵扣/应付记录失败
        'oms_patch_notice' => ['fuming'],
        'pur_notice' => ['fuming', 'tianrui'],
        'warehouse_notice' => ['fuming', 'leshan'],
        'fin_notice' => ['fuming'],
        'gp_refund_notice' => ['fuming', 'yezi'],
        'customs_notice' => ['tianrui'],
        'inve_notice' => ['tianrui'],
        'contract_audit_notice' => ['tianrui'],
        'question_notice' => ['tianrui'],
        'proxy_group' => [ 'feisong', 'zhuoping', 'yangsu', 'leshan', 'jifan', 'baisui'],
        'contract_email_remind' => ['tianrui'],
        'fin_flow_notice' => ['tianrui'],
    ];

    /**
     * @var array
     */
    private $processing_status = [
        'N000000001' => '待处理',
        'N000000002' => '已处理',
        'N000000003' => '已丢弃',
    ];

    /**
     * SentinelService constructor.
     */
    public function __construct()
    {
//        $this->SentinelRepository = new SentinelRepository();
    }

    /**
     * @param $key
     * @param $msg
     * @param $content
     * @param null $noticed_by
     * @param string $notice_type
     */
    public function addAbnormal($key, $msg, $content = null, $noticed_by = null, $notice_type = 'ERP')
    {
        $TbSystemAbnormalSentinel = new TbSystemAbnormalSentinel();
        $TbSystemAbnormalSentinel->key = $key;
        $TbSystemAbnormalSentinel->msg = $msg;
        $TbSystemAbnormalSentinel->content_json = $this->returnContentArray($content);
        $TbSystemAbnormalSentinel->processing_status = 'N000000001';
        $TbSystemAbnormalSentinel->updated_by = $TbSystemAbnormalSentinel->created_by = DataModel::userNamePinyin();
        if ('online' == $_ENV["NOW_STATUS"]) {
            $notices = $this->notices;
        } else {
            $notices = $this->notices_dev;
        }
        $user_ids = $notices[$noticed_by] ? $notices[$noticed_by] : $noticed_by;
        if ($user_ids) {
            $message_string = "[{$_ENV["NOW_STATUS"]}] " . $key . ' : ' . $msg;
            if (is_array($user_ids)) {

                $user_ids = str_replace("'", '', WhereModel::arrayToInString($user_ids));
            }
            # 根据消息类型 发送指定消息
            if ($notice_type == 'MonitoringAlarm')
            {
                $res = ApiModel::WorkWxSendWarnMarkdownMessage($user_ids, $msg);
            }else
            {
                $res = ApiModel::WorkWxSendMessage($user_ids, $message_string);
            }
            if (200000 == $res['code']) {
                $TbSystemAbnormalSentinel->processing_status = 'N000000002';
                $TbSystemAbnormalSentinel->noticed_by = $user_ids;
                $TbSystemAbnormalSentinel->noticed_msg_json = $message_string;
            }
        }
        $TbSystemAbnormalSentinel->save();
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    private function returnContentArray($data)
    {
        if (DataModel::isJson($data)) {
            return json_decode($data, true);
        }
        return $data;
    }


}