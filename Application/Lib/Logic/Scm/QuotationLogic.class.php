<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/7
 * Time: 11:15
 */
require_once APP_PATH . 'Lib/Logic/Scm/ScmBaseLogic.class.php';
require_once 'ScmTrait.php';

class QuotationLogic extends ScmBaseLogic
{
    use ScmTrait;

    private $action_step_check = [
        'quotation_save' => [
            //'阶段'=＞［［销售进度］，［采购进度］，［中标状态］］
            'purchase_claim' => [['no_need_to_treat'], ['draft'], null],
            'seller_choose' => [['untreated'], ['draft'], null],
        ],
        'quotation_update' => [//采购确认前编辑
            'purchase_confirm' => [['no_need_to_treat'], ['untreated'], ['chosen']],
        ],
        'quotation_submit' => [
            'purchase_claim' => [['no_need_to_treat'], ['draft'], null],
            'seller_choose' => [['untreated'], ['draft', 'no_need_to_treat'], null],
        ],
        'quotation_discard' => [
            'seller_choose' => [['untreated'], ['draft'], ['to_choose']],//没有放弃报价了。。2018.4.24
        ],
        'quotation_confirm' => [
            'purchase_confirm' => [['no_need_to_treat'], ['untreated'], ['chosen', 'not_chosen']],
        ],
        'quotation_upload_po' => [
            'upload_po' => [null, ['untreated'], ['chosen']],
        ],
        'quotation_justice_approve' => [
            'justice_approve' => [['untreated', 'pass_through'], ['untreated'], ['chosen']],
        ],
        'quotation_reupload_po' => [
            'justice_approve' => [null, ['untreated','not_pass_through'], ['chosen']],
        ],
//        'quotation_justice_stamp' => [
//            'justice_stamp' => [null, ['untreated'], ['chosen']],
//        ],
        'quotation_po_archive' => [
            'justice_approve' => [null, ['untreated'], ['chosen']],
            'success' => [null, null, ['chosen']],
        ],
        'quotation_po_rearchive' => [
            'create_order' => [['untreated', 'pass_through'], ['untreated'], ['chosen']],
        ],
        'quotation_create_order' => [
            'create_order' => [['untreated', 'pass_through'], ['untreated'], ['chosen']],
        ],
        'quotation_delete' => [
            'purchase_claim' => [null, ['draft'], null],
            'seller_choose' => [null, ['draft'], null],
            'purchase_confirm' => [null, ['draft'], null],
        ],
        'quotation_apply_edit' => [
            'purchase_confirm' => [['no_need_to_treat'], ['untreated'], ['chosen', 'not_chosen']],
//            'leader_approve' => [['not_pass_through'], ['no_need_to_treat'], ['chosen']],
//            'ceo_approve' => [['not_pass_through'], ['no_need_to_treat'], ['chosen']],
            'upload_po' => [null, ['untreated'], ['chosen']],
            'justice_approve' => [null, ['not_pass_through'], ['chosen']],
//            'justice_stamp' => [null, ['not_pass_through'], ['chosen']],
//            'po_archive' => [null, ['not_pass_through'], ['chosen']],
            'create_order' => [null, ['untreated', 'pass_through'], ['chosen']],
        ],
        'quotation_apply_discard' => [
            'purchase_confirm' => [['no_need_to_treat'], ['untreated'], ['chosen', 'not_chosen']],
//            'leader_approve' => [['not_pass_through'], ['no_need_to_treat'], ['chosen']],
//            'ceo_approve' => [['not_pass_through'], ['no_need_to_treat'], ['chosen']],
            'upload_po' => [null, ['untreated'], ['chosen']],
            'justice_approve' => [null, ['not_pass_through'], ['chosen']],
//            'justice_stamp' => [null, ['not_pass_through'], ['chosen']],
//            'po_archive' => [null, ['not_pass_through'], ['chosen']],
            'create_order' => [null, ['untreated', 'pass_through'], ['chosen']],
        ],
        'quotation_return_stamp' => [
            'justice_stamp' => [null, ['not_pass_through'], ['chosen']],
        ],
        'request_advance' => [
            'upload_po' => [['pass_through','untreated','no_need_to_treat','not_pass_through'], ['pass_through'], ['chosen']],
        ],
        'allow_advance' => [
            'upload_po' => [['pass_through','untreated','no_need_to_treat','not_pass_through'], ['pass_through'], ['chosen']],
        ],
        'cancel_advance' => [
            'upload_po' => [null, ['pass_through'], ['chosen']],
        ],
    ];

    /**权限检查
     * @param string $step N021******
     * @param string $demand_status N021******
     * @param string $quotation_status N021******
     * @param string $quotation_chosen N021******
     * @return bool
     */
    public function stepCheck($quotation)
    {
        $demand = D('Scm/Demand')->lock(true)->field('step,status')->where(['id' => $quotation['demand_id']])->find();
        //获取别名
        $step = array_flip(DemandModel::$step)[$quotation['step']];
        $demand_status = array_flip(DemandModel::$status)[$demand['status']];
        $quotation_status = array_flip(QuotationModel::$status)[$quotation['status']];
        $quotation_chosen = array_flip(QuotationModel::$chosen)[$quotation['chosen']];
        //检查
        if (!$this->action_step_check[ACTION_NAME]) {
            return true;
        }
        $this->error = '流程状态已更新，请刷新页面';
        if (empty($this->action_step_check[ACTION_NAME][$step])) {
            return false;
        }
        list($d_sta_arr, $q_sta_arr, $q_ch_arr) = $this->action_step_check[ACTION_NAME][$step];
        if($quotation['request_advance'] != 2) {
            if ($d_sta_arr && !in_array($demand_status, $d_sta_arr))
                return false;
        }
        if ($q_sta_arr && !in_array($quotation_status, $q_sta_arr)) {
            return false;
        }

        if ($q_ch_arr && $quotation_chosen && !in_array($quotation_chosen, $q_ch_arr)) {
            return false;
        }
        $this->error = '';
        return true;
    }

    /**销售认领保存
     * @param $quotation
     * @return bool
     */
    public function saveQuotation($quotation)
    {
        $goods = $quotation['goods'];
        $quotation_m = D('Scm/Quotation');
        $quotation_goods_m = D('Scm/QuotationGoods');
        $is_submit = $quotation['is_submit'];
        unset($quotation['is_submit']);
        M()->startTrans();
        $demand                     = D('Scm/Demand')->lock(true)->where(['id' => $quotation['demand_id']])->find();
        $quotation['payment_time']  = is_array($quotation['payment_time']) && !empty($quotation['payment_time']) ? json_encode($quotation['payment_time'], JSON_UNESCAPED_UNICODE) : '';
        $quotation['attachment']    = is_array($quotation['attachment']) && !empty($quotation['attachment']) ? json_encode($quotation['attachment'], JSON_UNESCAPED_UNICODE) : '';
        $quotation['status']        = QuotationModel::$status['draft'];
        // 销售订单的情况下，采购认领时，采购类型只能选择【普通采购】，禁止选择线上采购。
        if ($demand['demand_type'] == DemandModel::$demand_type_sell && $quotation['purchase_type'] == QuotationModel::$type_online) {
            $this->error = '销售订单的情况下，采购类型只能选择【普通采购】';
            return false;
        }
        //保存报价主表
        if ($quotation['id']) {
            if (!$is_submit && !$this->stepCheck($quotation_m->field('demand_id,status,chosen,request_advance,step')->where(['id' => $quotation['id']])->find())) {
                return false;
            }
            //有demand_id为编辑需求
            $res_quotation = $quotation_m->updateQuotation($quotation);
        } else {
            //没有demand_id为创建需求
            $quotation['step']      = $demand['step'];
            $quotation['chosen']    = QuotationModel::$chosen['to_choose'];
            $res_quotation          = $quotation_m->addQuotation($quotation);
            $quotation['id'] = $res_quotation;
        }
        if ($res_quotation === false) {
            M()->rollback();
            $this->error = '创建报价失败：' . ($quotation_m->getError() ?: '保存出错');
            return false;
        }
        //需求商品保存
        //删除原有商品
        $quotation_goods_m->where(['quotation_id' => $quotation['id']])->delete();
        foreach ($goods as &$v) {
            //处理删除产品
            unset($v['id']);
            unset($v['create_time']);
            $v['quotation_id'] = $quotation['id'];
            $v['create_user'] = $_SESSION['m_loginname'];

            //  销售小团队-验证销售小团队数据 【同一个SKU只能允许单个销售小团队，且各个小团队的供货数量=销售团队的可供数量】
            if (isset($v['sell_small_team_json']) && !empty($v['sell_small_team_json'])){
                foreach ($v['sell_small_team_json'] as $itme){
                    if (!isset($itme['number']) || empty($itme['number'])){
                        $this->error = '销售小团队供货数量有误';
                        return false;
                    }
                    if (!isset($itme['small_team_name']) || empty($itme['small_team_name'])){
                        //$this->error = '销售小团队不能为空';
                        //return false;
                    }
                }
                $sell_small_number = array_sum(array_column($v['sell_small_team_json'],'number'));
                if ($sell_small_number != $v['supply_number']){
                    M()->rollback();
                    $this->error = '销售小团队供货数量有误';
                    return false;
                }
                $sell_small_code = array_column($v['sell_small_team_json'],'small_team_code');
                if (count($sell_small_code) != count(array_unique($sell_small_code))){
                    M()->rollback();
                    $this->error = '同一个SKU不允许重复销售小团队供货';
                    return false;
                }
            }

            $v['sell_small_team_json'] = json_encode($v['sell_small_team_json']);

            $good = $quotation_goods_m->create($v);
            $res_goods = $quotation_goods_m->add($good);
            if ($res_goods === false) break;
        }
        unset($v);
        if ($res_goods === false) {
            M()->rollback();
            $this->error = '报价商品保存失败';
            return false;
        }
        $quotation['goods'] = $goods;
        $amt_data = $quotation_m->calcAmt($quotation);
        $amt_data['sell_currency'] = $demand['sell_currency'];
        if ($quotation_m->where(['id' => $quotation['id']])->save($amt_data) === false) {
            M()->rollback();
            $this->error = $quotation_m->getError() ?: '报价金额计算失败';
            return false;
        }
        //报价校验用到当前步骤
        $quotation['step'] = $demand['step'];
        if ($is_submit && !$this->quotationSubmit(['quotation' => $quotation, 'goods' => $goods])) {
            M()->rollback();
            return false;
        }

        // 预付款和尾款信息条款保存
        if (!$this->saveClause($quotation)) {
            M()->rollback();
            return false;
        }
        M()->commit();
        $this->code = 2000;
        $this->data['quotation_id'] = $quotation['id'];
        return true;
    }

    // 保存预付款和尾款条款信息
    public function saveClause($quotation)
    {
        $clause_m = D('Scm/PurClause');
        $addInfo = [];
        if (!empty($quotation['pre_pay'])) {
            foreach ($quotation['pre_pay'] as $key => $value) {
                $addInfo[$key] = [
                    'action_type_cd' => $value['action_type_cd'],
                    'days' => $value['days'],
                    'pre_paid_date' => $value['pre_paid_date'],
                    'percent' => $value['percent'],
                    'clause_type' => '1',
                    'quotation_id' => $quotation['id'],
                ];
            }
        }

        if (!empty($quotation['end_pay'])) {
            $quotation['end_pay']['percent'] = '0';
            $quotation['end_pay']['clause_type'] = '2';
            $quotation['end_pay']['quotation_id'] = $quotation['id'];
            $addInfo[] = $quotation['end_pay'];
        }

        $res = true;
        if (count($addInfo) !== 0) {
            // 删除原来该报价下面的付款规则
            $clause_m->where(['quotation_id' => $quotation['id']])->delete();
            $res = $clause_m->addClause($addInfo, $id);
        }

        return $res;
    }

    public function updateQuotation($quotation)
    {
        $quotation_m = D('Scm/Quotation');
        if (!$quotation_m->validate2($quotation)) {
            $this->error = $quotation_m->getError() ?: '数据验证未通过';
            return false;
        }
        M()->startTrans();
        $quotation['payment_time'] = is_array($quotation['payment_time']) && !empty($quotation['payment_time']) ? json_encode($quotation['payment_time']) : '';
        $quotation['attachment'] = is_array($quotation['attachment']) && !empty($quotation['attachment']) ? json_encode($quotation['attachment']) : '';
        $res_quotation = $quotation_m->where(['id'=>$quotation['id']])->save($quotation_m->create($quotation));
        if ($res_quotation === false) {
            M()->rollback();
            $this->error = '报价更新失败：' . ($quotation_m->getError() ?: '保存出错');
            return false;
        }
        $quotation['goods'] = D('Scm/QuotationGoods')->where(['quotation_id' => $quotation['id']])->select();;
        $amt_data = $quotation_m->calcAmt($quotation);
        $amt_data['sell_currency'] = D('Scm/Demand')->where(['id' => $quotation['demand_id']])->getField('sell_currency');
        if ($quotation_m->where(['id' => $quotation['id']])->save($amt_data) === false) {
            M()->rollback();
            $this->error = $quotation_m->getError() ?: '报价金额计算失败';
            return false;
        }
        M()->commit();
        $this->code = 2000;
        $this->data['quotation_id'] = $quotation['id'];
        return true;
    }

    public function quotationDetail($id)
    {
        $quotation = D('Scm/Quotation')->where(['id' => $id])->find();
        if (!$quotation) {
            $this->error = '报价不存在';
            return false;
        }
        $quotation['goods'] = D('Scm/QuotationGoods')
            ->alias('t')
            ->field('t.*,a.sku_id,a.search_id,a.purchase_number,a.sell_price,a.sell_price_not_contain_tax')
            ->join('left join tb_sell_demand_goods a on a.id=t.demand_goods_id')
            ->where(['quotation_id' => $id])
            ->select();
        foreach ($quotation['goods']  as &$itme){
            $itme['sell_small_team_json'] = json_decode($itme['sell_small_team_json'], true);
        }
        unset($itme);
        $quotation['goods'] = $this->formatDemandGoodsUpcMore($quotation['goods'], 'bar_code');

        $quotation['payment_time'] = json_decode($quotation['payment_time'], true);
        $quotation['attachment'] = json_decode($quotation['attachment'], true);
        $quotation['po'] = json_decode($quotation['po'], true);
        //判断是否有水印，有则取水印pdf
        foreach ($quotation['po'] as &$po_image) {
            $save_name_arr = explode('.', $po_image['save_name']);
            if (is_file(ATTACHMENT_DIR_IMG. $save_name_arr[0]. '-sy.pdf')) {
                $po_image['save_name'] = $save_name_arr[0]. '-sy.pdf';
            }
        }
        unset($po_image);
        $quotation['po_archive'] = json_decode($quotation['po_archive'], true);
        foreach ($quotation['po_archive'] as &$poa_image) {
            $save_name_arr = explode('.', $poa_image['save_name']);
            if (is_file(ATTACHMENT_DIR_IMG. $save_name_arr[0]. '-sy.pdf')) {
                $poa_image['save_name'] = $save_name_arr[0]. '-sy.pdf';
            }
        }
        unset($poa_image);
        $quotation['po_with_watermark'] = json_decode($quotation['po_with_watermark'], true);
        $quotation['legal_man'] = $quotation['legal_man'] ?: TbMsCmnCdModel::getLegalMan($quotation['purchase_team']);
        $demand = D('Scm/Demand')->where(['id' => $quotation['demand_id']])->find();
        $demand['collection_time'] = json_decode($demand['collection_time'], true);
        $demand['attachment'] = json_decode($demand['attachment'], true);
        $demand['po'] = json_decode($demand['po'], true);
        $demand['po_archive'] = json_decode($demand['po_archive'], true);
        $demand['po_with_watermark'] = json_decode($demand['po_with_watermark'], true);
        $demand['legal_man'] = $demand['legal_man'] ?: TbMsCmnCdModel::getLegalMan($demand['sell_team']);
        if (!$demand) {
            $this->error = '主需求不存在';
            return false;
        }
        $demand['goods'] = D('Scm/DemandGoods')->where(['demand_id' => $quotation['demand_id']])->select();
        $demand['goods'] = $this->formatDemandGoodsUpcMore($demand['goods'], 'bar_code');
        // 预付款&&尾款信息
        $demand['pre_pay'] = D('Scm/PurClause')->where(['quotation_id' => $id,'clause_type' => '1'])->select();
        $demand['end_pay'] = D('Scm/PurClause')->where(['quotation_id' => $id,'clause_type' => '2'])->find();

        if ($demand['pre_pay']) { // 百分比格式优化，保留两位小数
            foreach ($demand['pre_pay'] as $key => &$va) {
                $va['percent'] = sprintf("%.2f",$va['percent']);
            }
        }


        //cd转换
        $this->cd2Val($quotation, 'quotation');
        $this->cd2Val($quotation['goods'], 'quotation_goods');
        $this->cd2Val($demand, 'demand');
        $this->cd2Val($demand['goods'], 'demand_goods');
        $process_sync = D('Scm/Quotation')->where(['demand_id'=>$quotation['demand_id'],'invalid'=>0,'step'=>['neq',$demand['step']],'chosen'=>QuotationModel::$chosen['chosen']])->getField('id') ? false : true;
        if (!empty($_REQUEST['type']) && $_REQUEST['type'] == 'edit') {
            if (($_SESSION['m_loginname'] != $quotation['create_user']) && $_SESSION['m_login_huaming'] != $quotation['create_user']) {
                $this->error = '没有编辑权限';
                return false;
            }
            foreach ($demand['goods'] as &$v) {
                $v["is_checked"] = false;
                foreach ($quotation['goods'] as $vv) {
                    if ($v['id'] == $vv['demand_goods_id']) {
                        $v["is_checked"] = true;
                        $v["supply_number"] = $vv["supply_number"];
                        $v["choose_number"] = $vv["choose_number"];
                        $v["purchase_price"] = $vv["purchase_price"];
                        $v["purchase_price_not_contain_tax"] = $vv["purchase_price_not_contain_tax"];
                        $v["drawback_percent"] = $vv["drawback_percent"];
                        $v["auth_and_link"] = $vv["auth_and_link"];
                        $v['sell_small_team_json'] = $vv['sell_small_team_json'];
                    }
                }
            }
            unset($v);
            $this->data['goods'] = $demand['goods'];
        }

        $status_list = [
            'step'              => $quotation['step'],
            'demand_status'     => $demand['status'],
            'quotation_chosen'  => $quotation['chosen'],
            'quotation_status'  => $quotation['status'],
            'process_sync'      => $process_sync,
        ];

        //客户法务审核信息
        $this->data['audit_info'] = D('BTBCustomerManagement')->getCustomerAuditInfo($demand['customer_charter_no']);

        $this->code = 2000;
        $this->data['quotation'] = $quotation;
        $this->data['demand'] = $demand;
        $this->data['status_list'] = $status_list;
        $ce = M('QuotationProfit', 'tb_sell_')->where(['quotation_id' => $id])->find();
        $this->data['ce'] = $ce ?: D('Scm/Quotation')->calcCE($quotation, $demand);
        $this->data['login_name'] = $_SESSION['m_loginname'];
        return true;
    }

    public function quotationSubmit($data)
    {
        $quotation_m = D('Scm/Quotation');
        $quotation_goods_m = D('Scm/QuotationGoods');
        $demand_m = D('Scm/Demand');
        if (!empty($data['id'])) {
            $id = $data['id'];
            $quotation = $quotation_m->where(['id' => $id, 'create_user' => $_SESSION['m_loginname']])
                ->find();
            $goods = $quotation_goods_m->where(['quotation_id' => $id])
                ->select();
        } else {
            $id = $data['quotation']['id'];
            $quotation = $data['quotation'];
            $goods = $data['goods'];
        }
        if (!$quotation) {
            $this->error = '报价不存在或没有权限';
            return false;
        }
        //权限检查
        if (!$this->stepCheck($quotation)) {
            return false;
        }
        $quotation['payment_time'] = json_decode($quotation['payment_time'], true);
        $trans_flg = ACTION_NAME === 'quotation_submit';
        if ($trans_flg) M()->startTrans();
        if (!$quotation_m->validate($quotation)) {
            $this->error = $quotation_m->getError() ?: '数据验证未通过';
            return false;
        }
        $goods_fail = false;
        foreach ($goods as $v) {
            if (!$quotation_goods_m->create($v, 4)) {
                $goods_fail = true;
                $this->error = $quotation_goods_m->getError() ?: '商品数据验证未通过';
                break;
            }
        }
        if ($goods_fail) return false;
        $res_q = $quotation_m->where(['id' => $id])->save(['status' => QuotationModel::$status['no_need_to_treat'], 'chosen' => QuotationModel::$chosen['to_choose']]);
        if ($res_q === false) {
            $this->error = '提交失败';
            return false;
        }
        //销售进度推进 有人认领了即推进
        $demand = $demand_m->field('id,demand_code,status,step,seller,create_user')->where(['id' => $quotation['demand_id']])->find();
        if (($demand['step'] == DemandModel::$step['purchase_claim'] && !$this->stepDone($demand['id'], 'purchase_claim'))
            || !$this->quotationDone($quotation['id'],'purchase_claim')) {
            $this->error = '状态更新失败';
            return false;
        }
        if ($trans_flg) M()->commit();
        $this->code = 2000;
        //发送邮件
        ScmEmailModel::D($demand);
        return true;
    }

    public function quotationConfirm($data)
    {
        $id = $data['id'];
        $quotation_m = D('Scm/Quotation');
        $quotation_m->startTrans();
        $quotation = $quotation_m->lock(true)->where(['id' => $id])->find();
        $quotation['goods'] = D('Scm/QuotationGoods')->lock(true)->field('choose_number,supply_number,purchase_price,sell_price,drawback_percent')->where(['quotation_id' => $id])->select();
        $demand = D('Scm/Demand')->lock(true)->where(['id' => $quotation['demand_id']])->find();
        //cd转换
        //权限检查
        if (!$this->stepCheck($quotation)) {
            return false;
        }
        isset($data['service_expense']) ? $quotation['service_expense'] = $data['service_expense'] : null;
        isset($data['expense']) ? $quotation['expense'] = $data['expense'] : null;
        isset($data['expense_currency']) ? $quotation['expense_currency'] = $data['expense_currency'] : null;
        $data = ['status' => QuotationModel::$status['pass_through'], 'service_expense' => $quotation['service_expense'], 'expense' => $quotation['expense'], 'expense_currency' => $quotation['expense_currency']];
        //计算采购金额与退税金额
        if ($quotation['chosen'] == QuotationModel::$chosen['chosen']) {
            $amt_data = $quotation_m->calcAmt($quotation, 'choose');
            $quotation['amount'] = $data['amount'] = $amt_data['amount'];
            $quotation['drawback_amount'] = $data['drawback_amount'] = $amt_data['drawback_amount'];
            $quotation['po_amount'] = $data['po_amount'] = $amt_data['po_amount'];
        }
        $ce_data = $quotation_m->calcCE($quotation, $demand);
        $ce_data['quotation_id'] = $id;
        M('QuotationProfit', 'tb_sell_')->where(['quotation_id' => $id])->delete();//fixme
        if (!M('QuotationProfit', 'tb_sell_')->add($ce_data)) {
            $this->error = 'CE保存失败';
            M()->rollback();
            return false;
        }
        //计算ce---over
        $res = $quotation_m->where(['id' => $id])->save($data);
        if ($res === false) {
            $this->error = '保存失败';
            M()->rollback();
            return false;
        }
        //状态推进
        if (!$this->checkAllOver(['step' => 'purchase_confirm', 'type' => 'quotation', 'id' => $id])) {
            M()->rollback();
            return false;
        }
        $this->code = 2000;
        M()->commit();
        //发送邮件
        ScmEmailModel::Z($quotation);
        return true;
    }

    /*
     * 采购侧法务审批
     */
    public function justiceApprove($data)
    {
        $d_quotation = new QuotationModel();
        $d_quotation->startTrans();
        $q_status = QuotationModel::$status;
        $q_data = $d_quotation->lock(true)->where(['id' => $data['id']])->find();
        //检查法务权限
        if (!$this->stepCheck($q_data) || !$this->justiceCheck($q_data['purchase_team'])) {
            return false;
        }
        if ($q_data) {
            $q_data['status'] = $data['status'] ? $q_status['pass_through'] : $q_status['not_pass_through'];
            $q_data['reason'] = $data['status'] ? '' : $data['reason'];
//            if ($data['status']) {
//                if (!empty($data['po'] && is_array($data['po']))) {
//                    $q_data['po'] = json_encode($data['po'], JSON_UNESCAPED_UNICODE);
//                }
//            }
            if ($d_quotation->save($q_data) === false) {
                $d_quotation->rollback();
                $this->error = '提交失败';
                return false;
            }
            if (!$this->checkAllOver(['step' => 'justice_approve', 'type' => 'quotation', 'id' => $data['id']])) {//检查当阶段是否全部完成，修改主状态
                $this->error = '步骤推进异常';
                return false;
            }
        } else {
            $this->error = '订单状态异常';
            return false;
        }
        $d_quotation->commit();
        $this->code = 2000;
        //发送邮件
        $data['status'] ? null : ScmEmailModel::M2($q_data);
        return true;
    }

    /**法务盖章
     * @param $data
     * @return bool
     */
    public function justiceStamp($data)
    {
        $d_quota = new QuotationModel();
        $d_quota->startTrans();
        $q_data = $d_quota->lock(true)->where(['id' => $data['id']])->find();
        //检查法务权限
        if (!$this->stepCheck($q_data) || !$this->justiceCheck($q_data['purchase_team'])) {
            return false;
        }
        if ($q_data) {
            //$data['status'] 1通过，0退回
            $q_data['status'] = $data['status'] ? QuotationModel::$status['pass_through'] : QuotationModel::$status['not_pass_through'];
            $q_data['reason'] = $data['status'] ? '' : $data['reason'];//日志记录
            if ($d_quota->save($q_data) === false) {
                $d_quota->rollback();
                $this->error = '提交失败';
                return false;
            }
            if (!$this->checkAllOver(['step' => 'justice_stamp', 'type' => 'quotation', 'id' => $data['id']])) {//检查当阶段是否全部完成，修改主状态
                $this->error = '主需求状态推进错误';
                return false;
            }
        } else {
            $this->error = '订单状态异常';
            return false;
        }
        $d_quota->commit();
        $this->code = 2000;
        //发送邮件
        $data['status'] ? null : ScmEmailModel::O2($q_data);
        return true;
    }

    /**法务盖章失败，直接提交按钮
     * @param $data
     * @return bool
     */
    public function returnStamp($data)
    {
        $d_quotation = new QuotationModel();
        $d_quotation->startTrans();
        $q_status = QuotationModel::$status;
        $q_data = $d_quotation->lock(true)->where(['id' => $data['id']])->find();
        //检查法务权限
        if (!$this->stepCheck($q_data)) {
            return false;
        }
        if ($q_data) {
            $q_data['status'] = $q_status['untreated'];
            if ($d_quotation->save($q_data) === false) {
                $d_quotation->rollback();
                $this->error = '提交失败';
                return false;
            }
        } else {
            $this->error = '订单状态异常';
            return false;
        }
        $d_quotation->commit();
        $this->code = 2000;
        //发送邮件
        return true;
    }

    /**
     * @param $data
     * @return bool
     * 保存法务审批意见
     */
    public function forensicAuditProposal($data) {
        $quotation_m = D('Scm/Quotation');
        $quotation_m->startTrans();
        $quotation = $quotation_m->lock(true)->where(['id'=>$data['id']])->find();
        if(!$this->stepCheck($quotation)) {
            return false;
        }
        $res = $quotation_m->where(['id'=>$data['id']])->save(['forensic_audit_proposal'=>$data['forensic_audit_proposal']]);
        if($res === false) {
            $quotation_m->rollback();
            $this->error = '保存失败';
            return false;
        }else {
            $quotation_m->commit();
            return true;
        }
    }

    /**
     * @param $id
     * @param $to
     * @param $cc
     * @return bool
     * 法务审批盖章通知
     */
    public function forensicAuditEmail($id, $to, $cc) {
        $quotation_m = D('Scm/Quotation');
        $quotation_m->startTrans();
        $quotation = $quotation_m->lock(true)->where(['id'=>$id])->find();
        if(!$this->stepCheck($quotation)) {
            return false;
        }
        $title = "法务审核{$quotation['quotation_code']}";
        $content = <<<HHH
您的订单{$quotation['quotation_code']}上传的PO/合同已经通过法务的审核，请尽快打印邮件附件中的PO/合同，双方盖章后，提交给法务归档。<br/>
The PO/contract uploaded by your order {$quotation['quotation_code']} has been approved by legal. Please print the PO/contract in the email attachment as soon as possible. After the two sides stamp, it will be submitted to the legal for archive.<br/><br/>
法务审核意见：<br/>
Legal approval comments:<br/><br/>
{$quotation['forensic_audit_proposal']}
HHH;
        $email = new SMSEmail();
        if(!$email->sendEmail($to, $title, $content, $cc)) {
            $this->error = $email->getError();
            return false;
        }
        if(!empty($cc)) {
            $to = array_merge($to,$cc);
        }
        $receiver = implode(',',$to);
        if($quotation_m->where(['id'=>$id])->save(['forensic_audit_email_receiver'=>$receiver]) === false) {
            $quotation_m->rollback();
            return false;
        }
        $quotation_m->commit();
        return true;
    }

    public function watermarkToPo($id) {
        $quotation_m = D('Scm/Quotation');
        $quotation_m->startTrans();
        $quotation = $quotation_m->lock(true)->where(['id'=>$id])->find();
        if(!$this->stepCheck($quotation['step'],$quotation['status'])) {
            $this->error = '状态异常';
            return false;
        }
        $po = json_decode($quotation['po'],true);
        //pic_watermark
        $res_watermark = $this->waterMarkToFile($po);
        if(!$res_watermark) {
            $quotation_m->rollback();
            return false;
        }
        $res = $quotation_m->where(['id'=>$id])->save(['po_with_watermark'=>json_encode($res_watermark)]);
        if($res === false) {
            $quotation_m->rollback();
            $this->error = '水印保存失败';
            return false;
        }
        $quotation_m->commit();
        return $res_watermark;
    }

    /**放弃报价
     * @param $data
     * @return bool
     */
    public function discard($data)
    {
        $d_quotation = new QuotationModel();
        $d_quotation->startTrans();
        $q_data = $d_quotation->lock(true)->where(['id' => $data['id']])->find();
        //权限检查
        if (!$this->stepCheck($q_data)) {
            return false;
        }
        if ($q_data) {
            $q_data['status'] = QuotationModel::$status['discarded'];
            $q_data['reason'] = $data['reason'];
            $q_data['chosen'] = '';
            if (!$d_quotation->save($q_data)) {
                $d_quotation->rollback();
                $this->error = '弃单失败';
                return false;
            }
        } else {
            $this->error = '订单状态异常';
            return false;
        }
        $d_quotation->commit();
        $this->code = 2000;
        return true;
    }

    /**删除报价
     * @param $data
     * @return bool
     */
    public function quotationDelete($data)
    {
        $d_quotation = new QuotationModel();
        $d_quotation->startTrans();
        $q_data = $d_quotation->lock(true)->where(['id' => $data['id']])->find();
        //权限检查
        if (!$this->stepCheck($q_data)) {
            return false;
        }
        if ($q_data) {
            if (!$d_quotation->where(['id' => $data['id']])->delete()) {
                $d_quotation->rollback();
                $this->error = '删除失败';
                return false;
            }
        } else {
            $this->error = '订单状态异常';
            return false;
        }
        $d_quotation->commit();
        $this->code = 2000;
        return true;
    }

    /**申请修改
     * @param $data
     * @return bool
     */
    public function applyEdit($data)
    {
        $d_quotation = new QuotationModel();
        $d_demand = new DemandModel();
        $d_quotation->startTrans();
        $q_data = $d_quotation->lock(true)->where(['id' => $data['id']])->find();
        //权限检查
        if (!$this->stepCheck($q_data)) {
            return false;
        }
        if ($q_data) {
            if(!$this->isStepSync($q_data['demand_id'])) return false;
            $q_data['reason'] = $data['reason'];//日志记录
            $q_data['last_status'] = $q_data['status'];//记录申请前状态
            $q_data['status'] = QuotationModel::$status['apply_for_modification'];
            $demand = $d_demand->field('id,step,status')->where(['id' => $q_data['demand_id']])->find();
            //采购确认步骤不改主进度，下一进度集中处理
            if ($demand['step'] == DemandModel::$step['purchase_confirm']) {
                $q_data['last_status'] = QuotationModel::$status['pass_through'];//申请前状态默认通过
                $demandSaveFlg = true;
            } else {
                $d_data['id'] = $q_data['demand_id'];
                $d_data['status'] = DemandModel::$status['apply_for_modification'];
                if ($demand['status'] != DemandModel::$status['apply_for_modification']) $d_data['last_status'] = $demand['status'];
                $demandSaveFlg = $d_demand->save($d_data) !== false;
            }
            if (!$d_quotation->save($q_data) || !$demandSaveFlg) {
                $d_quotation->rollback();
                $this->error = '弃单失败';
                return false;
            }
            //采购确认状态检查当阶段是否全部完成，修改主状态
            if ($demand['step'] == DemandModel::$step['purchase_confirm'] && !$this->checkAllOver(['step' => 'purchase_confirm', 'type' => 'quotation', 'id' => $q_data['id']])) {
                $this->error = '主需求状态推进错误';
                return false;
            }
        } else {
            $this->error = '订单状态异常';
            return false;
        }
        $d_quotation->commit();
        $this->code = 2000;
        //发送邮件
        $d_data = $d_demand->field('id,demand_code,sell_team,seller,create_user')->where(['id' => $q_data['demand_id']])->find();
        ScmEmailModel::V($d_data);
        return true;
    }

    /**申请弃单
     * @param $data
     * @return bool
     */
    public function applyDiscard($data)
    {
        $d_quotation = new QuotationModel();
        $d_demand = new DemandModel();
        $d_quotation->startTrans();
        $q_data = $d_quotation->lock(true)->where(['id' => $data['id']])->find();
        //权限检查
        if (!$this->stepCheck($q_data)) {
            return false;
        }
        if ($q_data) {
            if(!$this->isStepSync($q_data['demand_id'])) return false;
            $q_data['reason'] = $data['reason'];
            $q_data['last_status'] = $q_data['status'];//记录申请前状态
            $q_data['status'] = QuotationModel::$status['discarded'];
            $demand = $d_demand->field('id,step,status')->where(['id' => $q_data['demand_id']])->find();
            //采购确认步骤不改主进度，下一进度集中处理
            if ($demand['step'] == DemandModel::$step['purchase_confirm']) {
                $q_data['last_status'] = QuotationModel::$status['pass_through'];//申请前状态默认通过
                $demandSaveFlg = true;
            } else {
                $d_data['id'] = $q_data['demand_id'];
                $d_data['status'] = DemandModel::$status['apply_for_modification'];
                if ($demand['status'] != DemandModel::$status['apply_for_modification']) $d_data['last_status'] = $demand['status'];
                $demandSaveFlg = $d_demand->save($d_data) !== false;
            }
            if (!$d_quotation->save($q_data) || !$demandSaveFlg) {
                $d_quotation->rollback();
                $this->error = '弃单失败';
                return false;
            }
            //采购确认状态检查当阶段是否全部完成，修改主状态
            if ($demand['step'] == DemandModel::$step['purchase_confirm'] && !$this->checkAllOver(['step' => 'purchase_confirm', 'type' => 'quotation', 'id' => $q_data['id']])) {
                $this->error = '主需求状态推进错误';
                return false;
            }
        } else {
            $this->error = '订单状态异常';
            return false;
        }
        $d_quotation->commit();
        $this->code = 2000;
        //发送邮件
        $d_data = $d_demand->field('id,demand_code,sell_team,seller,create_user')->where(['id' => $q_data['demand_id']])->find();
        ScmEmailModel::W($d_data);
        return true;
    }

    public function requestAdvance($id) {
        M()->startTrans();
        $quotation  = $this->getQuotation($id);
        $demand     = $this->getDemand($quotation['demand_id']);
        if($demand['status'] == DemandModel::$status['apply_for_modification']) {
            $this->error = '需求已申请修改';
            M()->rollback();
            return false;
        }
        if(!$this->stepCheck($quotation)) {
            M()->rollback();
            return false;
        }
        $res = D('Scm/Quotation')->where(['id'=>$id])->save(['request_advance'=>1]);
        if($res) {
            M()->commit();
            $this->code = 2000;
            return true;
        }else {
            M()->rollback();
            $this->error = '保存失败';
            return false;
        }
    }

    public function allowAdvance($id) {
        M()->startTrans();
        $quotation  = $this->getQuotation($id);
        if(!$this->stepCheck($quotation)) {
            M()->rollback();
            return false;
        }
        if($quotation['request_advance'] != 1) {
            $this->error = '对应报价未申请提前';
            M()->rollback();
            return false;
        }
        $res = D('Scm/Quotation')->where(['id'=>$id])->save(['request_advance'=>2]);
        if(!$res) {
            M()->rollback();
            $this->error = '申请状态保存失败';
            return false;
        }
        $res = $this->quotationDone($id,DemandModel::getStepNm($quotation['step']));
        if($res) {
            M()->commit();
            $this->code = 2000;
            return true;
        }else {
            M()->rollback();
            $this->error = '处理失败';
            return false;
        }
    }

    public function cancelAdvance($id) {
        M()->startTrans();
        $quotation  = $this->getQuotation($id);
        if(!$this->stepCheck($quotation)) {
            M()->rollback();
            return false;
        }
        if($quotation['request_advance'] != 1) {
            $this->error = '对应报价未申请提前';
            M()->rollback();
            return false;
        }
        $res = D('Scm/Quotation')->where(['id'=>$id])->save(['request_advance'=>0]);
        if($res) {
            M()->commit();
            $this->code = 2000;
            return true;
        }else {
            M()->rollback();
            $this->error = '申请状态保存失败';
            return false;
        }
    }


}