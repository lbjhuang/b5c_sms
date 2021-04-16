<?php

/**
 * User: due
 * Date: 18/4/13
 * Time: 16:33
 */
class ScmBaseAction extends BaseAction
{
    protected $cache_ret;
    private $log_data;

    /**
     * ScmBaseAction constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->logBegin();
    }

    /**
     * 日记记录开始方法
     */
    public function logBegin($key = null, $request_id = null)
    {
        $action_map = [
            //'action_name' => ['操作简介', 是否记录详情],
            'demand_save' => ['提交需求', true],
            'demand_submit' => ['提交需求', true],
            'return_to_draft' => ['撤回到需求草稿', false],
            'return_to_seller_choose' => ['重选方案', false],
            'process_application' => ['处理申请', false],
            'wechat_approve' => ['微信审批', false],
            'demand_approve' => ['需求审批', false],
            'seller_leader_approve' => ['销售领导审批', false],
            'ceo_approve' => ['现金效率审批', false],
            'scm_ceo_approve' => ['现金效率审批', false],//邮件审批使用2018-08-08 due
            'choose_quotation' => ['选择报价', false],
            'demand_quotation_submit' => ['提交方案', false],
            'demand_discard' => ['放弃需求', false],
            'demand_upload_po' => ['需求侧-上传po', true],
            'demand_forensic_audit_proposal' => ['需求侧-保存法务审批意见', true],
            'demand_watermark_to_po' => ['需求侧-生成带水印PO', true],
            'demand_audit_email' => ['需求侧-法务审批通知邮件', true],
            'demand_justice_approve' => ['需求侧-法务审批', false],
            'demand_reupload_po' => ['需求侧-上传po', true],
            'demand_justice_stamp' => ['需求侧-法务盖章', false],
            'demand_po_archive' => ['需求侧-归档PO', true],
            'demand_po_rearchive' => ['需求侧-归档PO', true],
            'create_order' => ['创建订单', false],
            'resend_ceo_email' => ['重发审批提醒邮件', false],
            'quotation_save' => ['提交报价', true],
            'quotation_submit' => ['提交报价', true],
            'quotation_confirm' => ['采购确认', false],
            'quotation_upload_po' => ['报价侧-上传po', true],
            'quotation_justice_approve' => ['报价侧-法务审批', false],
            'quotation_reupload_po' => ['报价侧-上传po', true],
            'quotation_forensic_audit_proposal' => ['报价侧-保存法务审批意见', true],
            'quotation_watermark_to_po' => ['报价侧-生成带水印PO', true],
            'quotation_audit_email' => ['报价侧-法务审批通知邮件', true],
            'quotation_po_archive' => ['报价测-归档PO', true],
            'quotation_po_rearchive' => ['报价测-归档PO', true],
            'quotation_create_order' => ['创建采购订单', false],
            'quotation_discard' => ['放弃报价', false],
            'quotation_delete' => ['删除报价', false],
            'request_advance' => ['报价申请提前', false],
            'allow_advance' => ['同意报价提前申请', false],
            'cancel_advance' => ['取消报价提前申请', false],
        ];
        $action_map2 = [//系统不显示
            'quotation_apply_edit' => ['报价申请修改', false],
            'quotation_apply_discard' => ['报价申请弃单', false],
            'demand_delete' => ['删除需求', false],//不需要
            'quotation_return_stamp' => ['法务盖章失败，报价直接提交', false],
            'demand_return_stamp' => ['法务盖章失败，需求直接提交', false],
        ];
        $draft_flg = false;
        $action_name = ACTION_NAME;
        if (!empty($key)) {
            $action_name = $key;
        }
        if (isset($action_map[$action_name])) {
            $info = $action_map[$action_name][0];
            $detail_flg = $action_map[$action_name][1];;//是否记录详情
            //存草稿不记日志
            if ($action_name == 'demand_save' || $action_name == 'quotation_save') {
                if (empty($_POST['is_submit']) && empty($_POST['is_update'])) {
                    return;
                }
            }
            if ($action_name == 'process_application') {
                $process_application_method = [
                    'approve' => 'N002340100', //同意
                    'discard' => 'N002340200', //直接弃单
                    'not_approve' => 'N002340300', //不同意
                ];
                $process_application_approve_to = [
                    //'edit_demand'       => 'N002320100', //修改需求
                    //'choose_quotation'  => 'N002320200', //重新选择报价
                    'edit_quotation' => 'N002320200', //允许所有采购修改报价
                ];
                switch ($_POST['process_method']) {
                    case $process_application_method['approve']:
                        switch ($_POST['approve_to_or_reason']) {
                            case $process_application_approve_to['edit_quotation']:
                                $info = '处理采购申请-允许所有采购修改报价';
                                break;
                            default:
                                break;
                        }
                        break;
                    /*case $process_application_method['discard']:
                        break;*/
                    case $process_application_method['not_approve']:
                        $info = '处理采购申请-不同意';
                        break;
                }
            }
        } elseif (isset($action_map2[$action_name])) {
            $info = $action_map2[$action_name][0];
            $detail_flg = $action_map2[$action_name][1];//是否记录详情
            $this->log_data['show'] = 0;
        } else {
            return;
        }
        $demand_id = $_REQUEST['id'];
        if (empty($demand_id)) {
            $demand_id = $request_id;
        }
        $detail_type = 'demand';
        if (MODULE_NAME == 'Quotation') {
            $detail_type = 'quotation';
            $this->log_data['quotation_id'] = $_REQUEST['id'];
            $this->log_data['old_quotation'] = D('Quotation')->field('id,demand_id,status')->where(['id' => $_REQUEST['id']])->find();
            if ($this->log_data['old_quotation']) {
                $demand_id = $this->log_data['old_quotation']['demand_id'];
                $this->log_data['old_quotation_status'] = $this->log_data['old_quotation']['status'];
            } else {
                $demand_id = $_REQUEST['demand_id'];
            }
        }
        $this->log_data['old_demand'] = D('Demand')->field('id,step,status')->where(['id' => $demand_id])->find();
        $this->log_data['old_step'] = $this->log_data['old_demand']['step'];
        $this->log_data['old_demand_status'] = $this->log_data['old_demand']['status'];
        $this->log_data['demand_id'] = $demand_id;
        $this->log_data['detail_type'] = $detail_flg ? $detail_type : '';
        $this->log_data['detail_flg'] = $detail_flg;
        $this->log_data['info'] = $info;
        $this->log_data['request'] = gzdeflate(json_encode($_REQUEST, JSON_UNESCAPED_UNICODE), 9);
    }

    /**
     * 日志记录结束
     */
    private function logEnd()
    {
        if ($this->cache_ret['code'] === 2000 && $this->log_data) {
            if (MODULE_NAME == 'Quotation') {
                $quotation_id = $_POST['id'];
                if (ACTION_NAME == 'quotation_save' && empty($_POST['id'])) {
                    $quotation_id = $this->cache_ret['data']['quotation_id'];
                }
                $quotation = D('Quotation')->field('id,create_time,create_user', true)->where(['id' => $quotation_id])->find();
                $this->log_data['quotation_id'] = $quotation_id;
                $this->log_data['quotation_status'] = $quotation['status'];
            }
            //日志数据
            if (ACTION_NAME == 'demand_save' && empty($_POST['id'])) {
                $this->log_data['demand_id'] = $this->cache_ret['data']['demand_id'];
            }
            $demand = D('Demand')->field('id,create_time,create_user', true)->where(['id' => $this->log_data['demand_id']])->find();
            $this->log_data['reason'] = !empty($_POST['reason']) ? $_POST['reason'] : '';
            $this->log_data['user'] = $_SESSION['m_loginname'];
            $this->log_data['demand_status'] = $demand['status'];
            $this->log_data['step'] = $demand['step'];
            $this->log_data['action_name'] = ACTION_NAME;
            $log_m = M('action_log', 'tb_sell_');
            $log_m->create($this->log_data);
            if (($log_id = $log_m->add()) && $this->log_data['detail_flg']) {
                $this->log_data['id'] = $log_id;
                $detail_type = $this->log_data['detail_type'];
                $this->logDetailSave($$detail_type);
            }
        } elseif ($this->log_data && in_array(ACTION_NAME,['wechat_approve','testscmlog', 'scm_ceo_approve'])) {
            $log_m = M('action_log', 'tb_sell_');
            $this->log_data['user'] = DataModel::userNamePinyin();
            $log_m->create($this->log_data);
            $log_m->add();
        }
    }

    /**保存日志详情
     * @param $detail
     */
    private function logDetailSave(&$detail)
    {
        $type = $this->log_data['detail_type'];
        $model = $type == 'demand' ? M('DemandHistory', 'tb_sell_') : M('QuotationHistory', 'tb_sell_');
        $goods_model = $type == 'demand' ? M('DemandGoodsHistory', 'tb_sell_') : M('QuotationGoodsHistory', 'tb_sell_');
        $detail['create_user'] = $_SESSION['m_loginname'];
        $detail[$type . '_id'] = $this->log_data[$type . '_id'];
        $detail['log_id'] = $this->log_data['id'];
        $model->create($detail);
        $history_id = $model->add();
        $detail['goods'] = D(ucfirst($type))->getGoods($detail[$type . '_id']);
        foreach ($detail['goods'] as &$v) {
            unset($v['id']);
            unset($v['create_time']);
            $v[$type . '_history_id'] = $history_id;
            $v['create_user'] = $_SESSION['m_loginname'];
        }
        unset($v);
        $goods_model->addAll($detail['goods']);
    }

    /**重写ajax返回方法，缓存返回结果，记录日志
     * @param mixed $data
     * @param string $type
     */
    protected function ajaxReturn($data, $type = '')
    {
        $this->cache_ret = $data;
        parent::ajaxReturn($data, $type = '');
    }

    /**
     * 日志记录
     */
    public function __destruct()
    {
        $this->logEnd();
        parent::__destruct();
    }
}