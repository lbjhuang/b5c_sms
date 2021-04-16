<?php

//日记账改造后，历史数据生成
class HistoryAction extends BaseAction
{
    private $accountLog;
    private $mail;
    protected $whiteList = [
        'thrTurnOver'
    ];

    public function _initialize()
    {
        $_SERVER ['CONTENT_TYPE'] = strtolower($_SERVER ['CONTENT_TYPE']);
        if ($_SERVER ['CONTENT_TYPE'] == 'application/json' or stripos($_SERVER ['HTTP_ACCEPT'], 'application/json') !== false or stripos($_SERVER ['CONTENT_TYPE'], 'application/json') !== false) {
            $json_str = file_get_contents('php://input');
            $json_str = stripslashes($json_str);
            $_POST    = json_decode($json_str, true);
            $_REQUEST = array_merge($_POST, $_GET);
        }
        $this->accountLog = new TbWmsAccountBankLogModel();
        $this->mail       = new ExtendSMSEmail();
        import('ORG.Util.Page');// 导入分页类
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:text/html;charset=utf-8');
        parent::_initialize();
        B('SYSOperationLog');
    }

    //日记账历史数据流水号生成
    public function his_number_create()
    {
        $model = M('fin_account_turnover', 'tb_');
        $model->startTrans();
        $transfer_no = $model->field(['account_transfer_no,create_time,transfer_time,id'])->order('id desc')->select();
        foreach ($transfer_no as $v) {
            if ($v['id'] > 6430) {
                continue;
            }
            if (!empty($v['account_transfer_no'])) {
                continue;
            }
            if (empty($v['create_time'])) {
                if (empty($v['transfer_time'])) {
                    $time = time();
                } else {
                    $time = strtotime($v['transfer_time']);
                }
            } else {
                $time = strtotime($v['create_time']);
            }
            $date = date('Ymd', $time);
            $len  = strlen($v['id']);
            if ($len > 4) {
                $model->rollback();
                throw new Exception(L('超出当日序号可生成的最大限制(9999)'));
            }
            $str = '';
            for ($i = $len; $i < 4; $i++) {
                $str .= '0';
            }
            $data['account_transfer_no'] = 'LS' . $date . $str . $v['id'];//生成流水号
            if (!$model->where(['id' => ['eq', $v['id']]])->save($data)) {
                $model->rollback();
                throw new Exception(L('历史数据添加流水ID失败'));
            } else {
                $ids1[] = $v['id'];
            }
        }
        Logs(json_encode($ids1), '历史数据添加流水ID', 'history');
        $model->commit();
        die('历史数据添加流水ID成功');
    }

    //日记账历史数据我方公司相关信息生成
    public function my_compnay_create()
    {
        $model      = M('fin_account_turnover', 'tb_');
        $bank_model = M('fin_account_bank', 'tb_');
        $model->startTrans();
        $transfer_no = $model->order('id desc')->select();
        //我方公司信息
        foreach ($transfer_no as $v) {
            if ($v['id'] > 6430) {
                continue;
            }
            if (!empty($v['swift_code']) || !empty($v['open_bank'])) {
                continue;
            }
            if (empty($v['account_bank'])) {
                continue;
            }
            $bank = $bank_model->where(['account_bank' => $v['account_bank']])->find();
            if (!$bank) {
                continue;
            }
            if (!$bank['swift_code'] && !$bank['open_bank']) {
                continue;
            }
            $data = [
                'swift_code' => $bank['swift_code'],
                'open_bank'  => $bank['open_bank'],
            ];
            if (!$model->where(['id' => ['eq', $v['id']]])->save($data)) {
                $model->rollback();
                throw new Exception(L('历史数据添加我方swift_code失败'));
            } else {
                $ids1[] = $v['id'];
            }
        }
        Logs(json_encode($ids1), '历史数据添加我方swift_code', 'history');
        $model->commit();
        die('历史数据添加我方swift_code成功');
    }

    //日记账历史数据对方公司相关信息生成
    public function opp_compnay_create()
    {
        $model          = M('fin_account_turnover', 'tb_');
        $bank_model     = M('fin_account_bank', 'tb_');
        $transfer_model = M('fin_account_transfer', 'tb_');
        $pur_model      = M('pur_order_detail', 'tb_');

        $model->startTrans();
        $transfer_no = $model->order('id desc')->select();
        //转入
        foreach ($transfer_no as $v) {
            if ($v['id'] > 6430) {
                continue;
            }
            if ($v['opp_swift_code'] || $v['opp_open_bank'] || $v['opp_account_bank'] || $v['opp_company_name']) {
                continue;
            }
            $type = $v['transfer_type'];
            if ($type == 'N001950400') {
                //转入
                $transfer_info = $transfer_model->where(['transfer_no' => $v['transfer_no']])->find();
                if (!$transfer_info) {
                    continue;
                }
                $pay_bank_info = $bank_model->find($transfer_info['pay_account_bank_id']);
                $data          = [
                    'opp_company_name' => $transfer_info['pay_company_name'],
                    'opp_open_bank'    => $transfer_info['pay_open_bank'],
                    'opp_account_bank' => $transfer_info['pay_account_bank'],
                    'opp_swift_code'   => $pay_bank_info['swift_code'],
                    'remark'           => $transfer_info['rec_reason'],
                ];

                if (!$model->where(['id' => ['eq', $v['id']]])->save($data)) {
                    $model->rollback();
                    throw new Exception(L('历史数据生成对方银行信息失败-转入'));
                } else {
                    $ids1[] = $v['id'];
                }
            }

        }

        //转出
        foreach ($transfer_no as $v) {
            if ($v['id'] > 6430) {
                continue;
            }
            if ($v['opp_swift_code'] || $v['opp_open_bank'] || $v['opp_account_bank'] || $v['opp_company_name']) {
                continue;
            }
            $type = $v['transfer_type'];
            if ($type == 'N001950300') {
                //转出
                $transfer_info = $transfer_model->where(['transfer_no' => $v['transfer_no']])->find();
                if (!$transfer_info) {
                    continue;
                }
                $rec_bank_info = $bank_model->find($transfer_info['rec_account_bank_id']);
                $data          = [
                    'opp_company_name' => $transfer_info['rec_company_name'],
                    'opp_open_bank'    => $transfer_info['rec_open_bank'],
                    'opp_account_bank' => $transfer_info['rec_account_bank'],
                    'opp_swift_code'   => $rec_bank_info['swift_code'],
                    'remark'           => $transfer_info['pay_reason'],
                ];

                if (!$model->where(['id' => ['eq', $v['id']]])->save($data)) {
                    $model->rollback();
                    throw new Exception(L('历史数据生成对方银行信息失败-转出'));
                } else {
                    $ids2[] = $v['id'];
                }
            }
        }

        //采购贷款/手续费
        foreach ($transfer_no as $v) {
            if ($v['id'] > 6430) {
                continue;
            }
            if ($v['opp_swift_code'] || $v['opp_open_bank'] || $v['opp_account_bank'] || $v['opp_company_name']) {
                continue;
            }
            $type = $v['transfer_type'];
            if ($type == 'N001950100' || $type == 'N001950500') {
                $transfer_info = $pur_model->where(['procurement_number' => $v['transfer_no']])->find();
                if (!$transfer_info) {
                    continue;
                }
                if ($transfer_info['supplier_collection_account']) {
                    $data['opp_company_name'] = $transfer_info['supplier_collection_account'];
                }
                if ($transfer_info['supplier_collection_account']) {
                    $data['opp_open_bank'] = $transfer_info['supplier_opening_bank'];
                }
                if ($transfer_info['supplier_collection_account']) {
                    $data['opp_account_bank'] = $transfer_info['supplier_card_number'];
                }
                if ($transfer_info['supplier_collection_account']) {
                    $data['opp_swift_code'] = $transfer_info['supplier_swift_code'];
                }
                if (empty($data)) {
                    continue;
                }
                if (!$model->where(['id' => ['eq', $v['id']]])->save($data)) {
                    $model->rollback();
                    throw new Exception(L('历史数据生成对方银行信息失败-采购贷款'));
                } else {
                    $ids3[] = $v['id'];
                }
            }
        }
        Logs(json_encode($ids1), '历史数据生成对方银行信息-转入', 'history');
        Logs(json_encode($ids2), '历史数据生成对方银行信息-转出', 'history');
        Logs(json_encode($ids3), '历史数据生成对方银行信息-采购贷款', 'history');
        $model->commit();
        die('历史数据生成对方银行信息成功');
    }

    // 日记账历史数据，财务划转金额币种生成
    public function finance_money_create()
    {
        $model          = M('fin_account_turnover', 'tb_');
        $bank_model     = M('fin_account_bank', 'tb_');
        $transfer_model = M('fin_account_transfer', 'tb_');
        $model->startTrans();
        $transfer_no = $model->order('id desc')->select();

        //划转转入
        foreach ($transfer_no as $v) {
            if ($v['id'] > 6430) {
                continue;
            }
            if (!empty($v['original_currency']) || !empty($v['original_amount'])) {
                continue;
            }
            $type = $v['transfer_type'];
            if ($type == 'N001950400') {
                //转入
                $transfer_info = $transfer_model->where(['transfer_no' => $v['transfer_no']])->find();
                if (!$transfer_info) {
                    continue;
                }
                $rec_bank_info = $bank_model->find($transfer_info['rec_account_bank_id']);

                if (!$rec_bank_info) {
                    continue;
                }
                $original_amount   = $transfer_info['rec_actual_money'];
                $original_currency = $rec_bank_info['currency_code'];
                if (!$original_currency) {
                    continue;
                }
                $data = [
                    'currency_code'     => $original_currency,//原来生成的币种要改
                    'original_currency' => $original_currency,
                    'original_amount'   => $original_amount,
                    'other_currency'    => $original_currency,
                    'remitter_currency' => $original_currency,
                ];

                if (!$model->where(['id' => ['eq', $v['id']]])->save($data)) {
                    $model->rollback();
                    throw new Exception(L('历史数据生成划转转入金额币种失败'));
                } else {
                    $currency1[$v['id']] = $v['currency_code'];
                    $ids1[]              = $v['id'];
                }
            }
        }

        //划转转出
        foreach ($transfer_no as $v) {
            if ($v['id'] > 6430) {
                continue;
            }
            if (!empty($v['original_currency']) || !empty($v['original_amount'])) {
                continue;
            }
            $type = $v['transfer_type'];
            if ($type == 'N001950300') {
                //转出
                $transfer_info = $transfer_model->where(['transfer_no' => $v['transfer_no']])->find();
                if (!$transfer_info) {
                    continue;
                }
                $pay_bank_info = $bank_model->find($transfer_info['pay_account_bank_id']);

                if (!$pay_bank_info) {
                    continue;
                }
                $original_amount   = $transfer_info['pay_actual_money'];
                $original_currency = $pay_bank_info['currency_code'];
                if (!$original_currency) {
                    continue;
                }
                $data = [
                    'currency_code'     => $original_currency,//原来生成的币种要改
                    'original_currency' => $original_currency,
                    'original_amount'   => $original_amount,
                    'other_currency'    => $original_currency,
                    'remitter_currency' => $original_currency,
                ];

                if (!$model->where(['id' => ['eq', $v['id']]])->save($data)) {
                    $model->rollback();
                    throw new Exception(L('历史数据生成划转转出金额币种失败'));
                } else {
                    $currency2[$v['id']] = $v['currency_code'];
                    $ids2[]              = $v['id'];
                }
            }
        }
        Logs(json_encode($ids1), '历史数据生成划转转入金额币种', 'history');
        Logs(json_encode($ids2), '历史数据生成划转转出金额币种', 'history');
        Logs(json_encode($currency1), '划转入原本币种', 'finance-backup');
        Logs(json_encode($currency2), '划转出原本币种', 'finance-backup');
        //采购带贷款

        $model->commit();
        die('历史数据生成金额币种成功');
    }


    // 日记账历史数据，采购应付金额币种生成
    public function pur_money_create()
    {
        $model = M('fin_account_turnover', 'tb_');
        $model->startTrans();
        $transfer_no = $model->order('id desc')->select();

        //采购贷款
        foreach ($transfer_no as $v) {
            if ($v['id'] > 6430) {
                continue;
            }
            if ($v['original_currency'] || $v['original_amount']) {
                continue;
            }
            $type = $v['transfer_type'];
            if ($type == 'N001950100') {
                if (!$v['currency_code']) {
                    continue;
                }
                $data = [
                    'original_currency' => $v['currency_code'],
                    'original_amount'   => $v['amount_money'],
                    'other_currency'    => $v['currency_code'],
                    'remitter_currency' => $v['currency_code'],
//                   'other_cost' => 0,
//                   'remitter_cost' => 0,
                ];
                if (!$model->where(['id' => ['eq', $v['id']]])->save($data)) {
                    $model->rollback();
                    throw new Exception(L('日记账历史数据采购贷款金额币种生成失败'));
                } else {
                    $ids1[] = $v['id'];
                }
            }

            //手续费
            if ($type == 'N001950500') {
                $data = [
                    'original_currency' => $v['currency_code'],
                    'original_amount'   => $v['amount_money'],
                    'other_currency'    => $v['currency_code'],
                    'remitter_currency' => $v['currency_code'],
                    'other_cost'        => $v['amount_money'],
//                   'remitter_cost' => 0,
                ];
                if (!$model->where(['id' => ['eq', $v['id']]])->save($data)) {
                    $model->rollback();
                    throw new Exception(L('日记账历史数据采购手续费金额币种生成失败'));
                } else {
                    $ids2[] = $v['id'];
                }
            }
        }

        Logs(json_encode($ids1), '日记账历史数据采购贷款金额币种生成', 'history');
        Logs(json_encode($ids2), '日记账历史数据采购手续费金额币种生成', 'history');
        $model->commit();
        die('历史数据生成采购应付金额币种成功');
    }


    //日记账历史数据采购应付备注信息生成
    public function pur_remark_create()
    {
        $model     = M('fin_account_turnover', 'tb_');
        $rel_model = new Model();
        $model->startTrans();
        $transfer_no = $model->order('id desc')->select();
        //采购应付备注
        foreach ($transfer_no as $v) {
            if ($v['id'] > 6430) {
                continue;
            }
            if ($v['remark']) {
                continue;
            }
            $type = $v['transfer_type'];
            if ($type == 'N001950100' || $type == 'N001950500') {
                $transfer_info = $rel_model->table('tb_pur_order_detail d')
                    ->field(['p.confirm_remark'])
                    ->join('left join tb_pur_relevance_order o on d.order_id = o.order_id')
                    ->join('left join tb_pur_payment p on o.relevance_id = p.relevance_id')
                    ->where(['d.procurement_number' => $v['transfer_no']])
                    ->find();
//                $transfer_info = $pur_model->where(['procurement_number' => $v['transfer_no']])->find();
                if (!$transfer_info) {
                    continue;
                }
                if (!$transfer_info['confirm_remark']) {
                    continue;
                }
                $data['remark'] = $transfer_info['confirm_remark'];

                if (!$model->where(['id' => ['eq', $v['id']]])->save($data)) {
                    $model->rollback();
                    throw new Exception(L('历史数据采购应付备注生成失败'));
                } else {
                    $ids1[] = $v['id'];
                }
            }
        }
        Logs(json_encode($ids1), '历史数据采购应付备注生成', 'history');
        $model->commit();
        die('历史数据采购应付备注生成成功');
    }

    //日记账关联历史数据划转信息生成
    public function rel_finance()
    {
        $model      = M('fin_account_turnover', 'tb_');
        $claimModel = M('fin_claim', 'tb_');
        $adminModel = M('admin', 'bbm_');
        $claimModel->startTrans();
        $transfer_no = $model->order('id desc')->select();
        foreach ($transfer_no as $v) {
            if ($v['id'] > 6430) {
                continue;
            }
            if ($v['transfer_type'] == 'N001950400' || $v['transfer_type'] == 'N001950300') {
                $claim = $claimModel->where(['account_turnover_id' => $v['id']])->find();
                if ($claim) {
                    continue;
                }
                //划转转入/出写入日记账关联
                $name = '';
                if ($v['create_user']) {
                    $admin_info = $adminModel->field(['EMP_SC_NM'])->find($v['create_user']);
                    $name       = $admin_info['EMP_SC_NM'];
                }
                $data[] = [
                    'account_turnover_id' => $v['id'],
                    'order_type'          => $v['transfer_type'],
                    'order_no'            => $v['transfer_no'],
                    'claim_amount'        => $v['amount_money'],
                    'created_at'          => $v['create_time'],
                    'created_by'          => $name,
                ];
            }
        }
        if (empty($data)) {
            return;
        }
        if (!$claimModel->addAll($data)) {
            $claimModel->rollback();
            throw new Exception(L('日记账关联历史数据划转信息生成失败'));
        }
        Logs(json_encode($data), '日记账关联历史数据划转信息生成', 'history');
        $claimModel->commit();
        die('日记账关联历史数据划转信息生成成功');
    }

    //日记账关联历史数据采购应付信息生成
    public function rel_pur()
    {
        $model      = M('fin_account_turnover', 'tb_');
        $claimModel = M('fin_claim', 'tb_');
        $adminModel = M('admin', 'bbm_');
        $claimModel->startTrans();
        $relModel    = new Model();
        $transfer_no = $model->order('id desc')->select();
        foreach ($transfer_no as $v) {
            if ($v['id'] > 6430) {
                continue;
            }
            if ($v['transfer_type'] == 'N001950100' || $v['transfer_type'] == 'N001950500') {
                $claim = $claimModel->where(['account_turnover_id' => $v['id']])->find();
                if ($claim) {
                    continue;
                }
                //采购应付贷款/手续费写入日记账关联
                $name = '';
                if ($v['create_user']) {
                    $admin_info = $adminModel->field(['EMP_SC_NM'])->find($v['create_user']);
                    $name       = $admin_info['EMP_SC_NM'];
                }

                $order = $relModel->table('tb_pur_order_detail d')
                    ->field('d.order_id,i.sell_team')
                    ->join('left join tb_pur_relevance_order o on d.order_id=o.order_id')
                    ->join('left join tb_pur_sell_information i on o.sell_id=i.sell_id')
                    ->where(['d.procurement_number' => $v['transfer_no']])
                    ->find();

                $data[] = [
                    'account_turnover_id' => $v['id'],
                    'order_type'          => $v['transfer_type'],
                    'order_id'            => $order['order_id'],
                    'order_no'            => $v['transfer_no'],
                    'child_order_no'      => $v['child_transfer_no'],
                    'sale_teams'          => $order['sell_team'],
                    'claim_amount'        => $v['amount_money'],
                    'created_at'          => $v['create_time'],
                    'created_by'          => $name,
                ];
            }
        }
        if (empty($data)) {
            return;
        }
        if (!$claimModel->addAll($data)) {
            $claimModel->rollback();
            throw new Exception(L('日记账关联历史数据采购应付信息生成失败'));
        }
        Logs(json_encode($data), '日记账关联历史数据采购应付信息生成', 'history');
        $claimModel->commit();
        die('日记账关联历史数据采购应付信息成功');
    }

    //新生成的数据采购贷款-汇款人费用币种缺失修复
    public function pur_remitter()
    {
        $model = M('fin_account_turnover', 'tb_');
        $model->startTrans();
        $transfer_no = $model->order('id desc')->select();
        foreach ($transfer_no as $v) {
            if ($v['id'] < 6430) {
                continue;
            }
            if ($v['remitter_currency']) {
                continue;
            }
            if (!$v['other_currency']) {
                continue;
            }
            if ($v['transfer_type'] != 'N001950100') {
                continue;
            }
            $data = [
                'remitter_currency' => $v['other_currency'],
            ];
            if (!$model->where(['id' => ['eq', $v['id']]])->save($data)) {
                $model->rollback();
                throw new Exception(L('采购贷款-汇款人费用币种缺失修复失败'));
            } else {
                $ids1[] = $v['id'];
            }
        }
        Logs(json_encode($ids1), '采购贷款-汇款人费用币种缺失修复', 'history');
        $model->commit();
        die('采购贷款-汇款人费用币种缺失修复成功');
    }

    //对方信息制表符数据修复
    public function opp_company_name()
    {
        $model = M('fin_account_turnover', 'tb_');
        $model->startTrans();
        $where       = [
            'account_transfer_no' => ['in', ['LS201901030056', 'LS201901030057']],
            'transfer_type'       => 'N001950200'
        ];
        $transfer_no = $model->where($where)->select();
        foreach ($transfer_no as $v) {
            $data = [
                'opp_company_name' => substr($v['opp_company_name'], 1)
            ];
            if (!$model->where(['id' => $v['id']])->save($data)) {
                $model->rollback();
                throw new Exception(L('付款方名称修复失败'));
            }
            $ids1[] = $v;
        }
        Logs(json_encode($ids1), 'B2B收款-付款方名称修复', 'history');
        $model->commit();
        die('B2B收款-付款方名称修复成功');
    }

    //删除历史数据多生成的采购应付手续费日记账记录和日记账关联记录
    public function delete_over_data()
    {
        $model = M('fin_account_turnover', 'tb_');
        $model->startTrans();
        $where       = [
            'account_transfer_no' => 'LS201803020224',
            'transfer_type'       => 'N001950500'
        ];
        $transfer_no = $model->where($where)->find();
        $claim       = M('fin_claim', 'tb_')->where(['account_turnover_id' => $transfer_no['id']])->find();
        if (empty($transfer_no)) {
            die('未找到日记账记录');
        }
        if (empty($claim)) {
            die('未找到日记账关联记录');
        }
        if (!$model->where($where)->delete()) {
            $model->rollback();
            throw new Exception(L('删除日记账失败'));
        } else {
            if (!M('fin_claim', 'tb_')->where(['account_turnover_id' => $transfer_no['id']])->delete()) {
                $model->rollback();
                throw new Exception(L('删除日记账关联失败'));
            }
        }
        Logs(json_encode($transfer_no), '删除的日记账', 'history');
        Logs(json_encode($claim), '删除的日记账关联', 'history');
        $model->commit();
        die('删除成功');
    }

    //修复b2b没有销售团队数据
    public function b2b_sales_team()
    {
        $model = M('fin_claim', 'tb_');
        $model->startTrans();
        $where = [
            'order_type' => 'N001950200',
            'sale_teams' => ['exp', 'is null'],
            'id'         => ['gt', 8567]
        ];
        $claim = $model->where($where)->select();
        foreach ($claim as $v) {
            $info = M('b2b_info', 'tb_')->where(['ORDER_ID' => $v['order_id']])->find();
            if (!$info['SALES_TEAM']) {
                continue;
            }
            if ($v['sale_teams']) {
                continue;
            }
            $res = $model->where(['id' => $v['id']])->save(['sale_teams' => $info['SALES_TEAM']]);
            if (!$res) {
                $model->rollback();
                throw new Exception(L('更新失败'));
            }
            $ids1[] = $v['id'];
        }
        Logs(json_encode($ids1), 'B2B收款-销售团队名称修复', 'history');
        $model->commit();
        die('成功');
    }

    //采购付款单生成
    public function payment_audit_create()
    {
        $model = new Model();
        $model->startTrans();
        $payment_m = M('payment','tb_pur_');
        $audit_m = M('payment_audit','tb_pur_');
        $list  = $model->table('tb_pur_payment pp')
            ->field('pp.*,od.supplier_opening_bank,od.supplier_collection_account,od.supplier_card_number,od.supplier_swift_code,od.our_company as company')
            ->join('left join tb_pur_relevance_order rel on pp.relevance_id = rel.relevance_id')
            ->join('left join tb_pur_order_detail od on rel.order_id = od.order_id')
            ->where(['pp.status' => ['neq', 0]])->select();
        if (empty($list)) {
            die('数据为空');
        }
        foreach ($list as $item) {
            $payment_voucher = $billing_voucher = '';
            $voucher = $receipt = [];
            if ($item['voucher']) {
                $imgs = explode(',',$item['voucher']);
                foreach ($imgs as $v) {
                    $voucher[] = ['original_name' => $v, 'save_name'=> $v];
                }
                $payment_voucher = json_encode($voucher, JSON_UNESCAPED_UNICODE);
            }
            if ($item['bank_receipt']) {
                $imgs = explode(',',$item['bank_receipt']);
                foreach ($imgs as $v) {
                    $receipt[] = ['original_name' => $v, 'save_name'=> $v];
                }
                $billing_voucher = json_encode($receipt, JSON_UNESCAPED_UNICODE);
            }
            $data = [
                'payment_audit_no'            => 'FK' . date('Ymd') . TbWmsNmIncrementModel::generateCustomNo('hbfk',5),
                'our_company_cd'              => $item['company'] ? : '',
                'status'                      => $item['status'],
                'payable_amount_before'       => $item['amount_payable'] ? : '',
                'payable_amount_after'        => $item['amount_confirm'] ? : '',
                'payable_date_after'          => $item['payable_date'] ? : '',
                'supplier_opening_bank'       => $item['supplier_opening_bank'] ? : '',
                'supplier_collection_account' => $item['supplier_collection_account'] ? : '',
                'supplier_card_number'        => $item['supplier_card_number'] ? : '',
                'supplier_swift_code'         => $item['supplier_swift_code'] ? : '',
                'payment_our_bank_account'    => $item['our_company_bank_account'] ? : '',
                'payment_voucher'             => $payment_voucher ? : '',
                'payment_amount'              => $item['amount_paid'] ? : '',
                'payment_currency_cd'         => $item['currency_paid'],
                'billing_amount'              => $item['amount_account'] ? : '',
                'billing_date'                => $item['account_date'] ? : '',
                'billing_voucher'             => $billing_voucher ? : '',
                'billing_fee'                 => $item['expense'] ? : '',
                'billing_exchange_rate'       => $item['exchange_tax_account'] ? : '',
                'payment_at'                  => $item['payment_submit_time'] ? : '',
                'payment_by'                  => $item['payment_submit_user'] ? : '',
                'billing_at'                  => $item['account_submit_time'] ? : '',
                'billing_by'                  => $item['account_submit_user'] ? : '',
                'created_by'                  => $item['confirm_user'] ? : '',
                'created_at'                  => $item['confirm_time'] ? : '',
                'updated_by'                  => $item['confirm_user'] ? : '',
            ];
            $res = $audit_m->add($data);
            if (!$res) {
                $model->rollback();
                die('生成失败');
            }
            if(!$payment_m->where(['id'=>$item['id']])->save(['payment_audit_id'=>$res])) {
                $model->rollback();
                die('更新失败');
            }
        }
        $model->commit();
        die('成功');
    }

    //采购日记账主订单号改成付款单号
    public function transfer_no_update()
    {
        $model = new Model();
        $model->startTrans();
        $turnover_m = M('account_turnover','tb_fin_');
        $list  = $model->query("SELECT
            tur.*, pa.payment_audit_no
        FROM
            tb_fin_account_turnover tur
        LEFT JOIN tb_pur_payment pp ON tur.child_transfer_no = pp.payment_no
        LEFT JOIN tb_pur_payment_audit pa ON pp.payment_audit_id = pa.id
        WHERE
            (
                tur.transfer_type IN ('N001950500', 'N001950100')
                AND pa.`status` = 3
                AND tur.transfer_no NOT LIKE 'FK%'
            )");
        if (empty($list)) {
            die('数据为空');
        }
        foreach ($list as $item) {
            if (!$item['payment_audit_no']) {
                continue;
            }
            $res = $turnover_m->where(['id'=>$item['id']])->save(['transfer_no'=>$item['payment_audit_no']]);
            if (false === $res) {
                $model->rollback();
                die('生成失败');
            }
        }
        $model->commit();
        die('成功');
    }

    public function log_create() {
        $list = M('payment','tb_pur_')->select();
        foreach ($list as $item) {
            $data[] = [
                'payment_id' => $item['id'],
                'created_by' => '',
                'created_at' => $item['create_time'],
                'operation_info' => '创建应付单',
            ];
            if (in_array($item['status'], [1,2,3])) {
                $data[] = [
                    'payment_id' => $item['id'],
                    'created_by' => $item['confirm_user'],
                    'created_at' => $item['confirm_time'],
                    'operation_info' => '确认应付',
                ];
            }
            if (in_array($item['status'], [2,3])) {
                $data[] = [
                    'payment_id' => $item['id'],
                    'created_by' => $item['payment_submit_user'] ? : $item['account_submit_user'],
                    'created_at' => $item['payment_submit_time'] ? : $item['account_submit_time'],
                    'operation_info' => '确认付款',
                ];
            }
            if ($item['status'] == 3) {
                $data[] = [
                    'payment_id' => $item['id'],
                    'created_by' => $item['account_submit_user'],
                    'created_at' => $item['account_submit_time'],
                    'operation_info' => '确认出账',
                ];
            }
        }
        $model = M('payment_log','tb_pur_');
        $model->startTrans();
        $res = M('payment_log','tb_pur_')->addAll($data);
        if (!$res) {
            $model->rollback();
            die('fail');
        }
        $model->commit();
        die('success');
    }

    //付款币种修复
//    public function payment_audit_update()
//    {
//        $model = new Model();
//        $model->startTrans();
//        $audit_m = M('payment_audit', 'tb_pur_');
//        $list    = $audit_m->where(['status' => ['gt', 1]])->select();
//        foreach ($list as $value) {
//            if ($value['payment_amount']<=0 && !$value['payment_voucher'] && $value['payment_currency_cd']) {
//                $ids[] = $value['id'];
//            }
//        }
//        if (empty($ids)) {
//            die('no');
//        }
//        $res = $audit_m->where(['id'=>['in',$ids]])->save(['payment_currency_cd'=>'']);
//        if (!$res) {
//            $model->rollback();
//            die('fail');
//        }
//        $model->commit();
//        die('success');
//    }

    //日记账币种修复
//    public function turnover_update()
//    {
//        $model = new Model();
//        $model->startTrans();
//        $audit_m = M('account_turnover', 'tb_fin_');
//        $list    = $model->table('tb_fin_account_turnover at')
//            ->field('at.*,ab.currency_code as billing_currency_code')
//            ->join('left join tb_fin_account_bank ab on at.opp_account_bank = ab.account_bank')
//            ->where(['at.transfer_type'=>['in',['N001950100','N001950500']]])
//            ->select();
//        foreach ($list as $value) {
//            if (!$value['billing_currency_code']) {
//                continue;
//            }
//            if ($value['billing_currency_code'] != $value['currency_code']
//                || $value['billing_currency_code'] != $value['original_currency']
//                || $value['billing_currency_code'] != $value['remitter_currency']
//                || $value['billing_currency_code'] != $value['other_currency']) {
//                $res = $audit_m->where(['id'=>$value['id']])->save([
//                    'currency_code'     => $value['billing_currency_code'],
//                    'original_currency' => $value['billing_currency_code'],
//                    'remitter_currency' => $value['billing_currency_code'],
//                    'other_currency'    => $value['billing_currency_code'],
//                ]);
//                if (!$res) {
//                    $model->rollback();
//                    die('fail');
//                }
////                $temp[] = $value['account_transfer_no'];
//            }
//        }
//        $model->commit();
//        die('success');
//    }

    //还原日记账4个币种
    public function revert()
    {
        $json = '[{"id":645,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":646,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":647,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":648,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":649,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":650,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":651,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":652,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":653,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":985,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":992,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":993,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":994,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3477,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3478,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3479,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3480,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3481,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3482,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3484,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3642,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3643,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3644,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3645,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3646,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3647,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":3648,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4127,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4128,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4129,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4130,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4131,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4132,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4133,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4134,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4135,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4136,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4913,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4914,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4915,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4916,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4917,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4918,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4919,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":4920,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5160,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5161,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5162,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5698,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5699,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5834,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5835,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5836,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5837,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5838,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5839,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5840,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5841,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5842,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5843,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5844,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5845,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5846,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5847,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5848,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5849,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5850,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5851,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5852,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":5853,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"},
{"id":6418,"currency_code":"N000590300","original_currency":"N000590300","remitter_currency":"N000590300","other_currency":"N000590300"}]';
        $list = json_decode($json, true);
//        v($list);
        $model = new Model();
        $model->startTrans();
        $audit_m = M('account_turnover', 'tb_fin_');
        foreach ($list as $v) {
            $res = $audit_m->where(['id'=>$v['id']])->save([
                'currency_code'     => $v['currency_code'],
                'original_currency' => $v['original_currency'],
                'remitter_currency' => $v['remitter_currency'],
                'other_currency'    => $v['other_currency'],
            ]);
            if (false === $res) {
                $model->rollback();
                die('还原失败');
            }
            $ids[] = $v['id'];
        }
        $model->commit();
        echo 'success';
        v($ids);
    }

//    //修复采购应付出账币种、汇率、日记账4币种
//    public function repair_payment_currency()
//    {
//        $model = new Model();
//        $model->startTrans();
//        $payment_model = M('payment','tb_pur_');
//        $payment_audit_model = M('payment_audit','tb_pur_');
//        $tur_model = M('account_turnover','tb_fin_');
//        $sql = "SELECT
//            pp.id,
//            pp.payment_no,
//            pp.currency_account,
//            pp.exchange_tax_account,
//            ab.update_time,
//            ab.account_bank,
//            ab.currency_code AS billing_currency_code,
//            tur.currency_code AS tur_currency_code,
//            tur.original_currency,
//            tur.remitter_currency,
//            tur.other_currency,
//            tur.account_transfer_no,
//            pa.payment_our_bank_account,
//            pa.billing_at,
//            pa.id as payment_audit_id,
//            od.amount_currency,
//            rel.sou_time
//        FROM
//            tb_pur_payment pp
//        LEFT JOIN tb_fin_account_turnover tur ON pp.payment_no = tur.child_transfer_no
//        LEFT JOIN tb_pur_payment_audit pa ON pp.payment_audit_id = pa.id
//        LEFT JOIN tb_fin_account_bank ab ON pa.payment_our_bank_account = ab.account_bank
//        LEFT JOIN tb_pur_relevance_order rel on pp.relevance_id = rel.relevance_id
//        LEFT JOIN tb_pur_order_detail od on rel.order_id = od.order_id
//        WHERE
//            pp.`status` = 3
//        AND ab.update_time < pa.billing_at
//        AND ab.currency_code != pp.currency_account";
//        $list = $model->query($sql);
//        foreach ($list as $item) {
//            $count = $payment_model->where(['payment_audit_id'=>$item['payment_audit_id']])->count();
//            if ($count > 1) {
//                $merge[] = $item['payment_no'];
////                continue;
//            }
//            $ex = exchangeRateConversion(cdVal($item['billing_currency_code']), cdVal($item['amount_currency']), str_replace('-','',$item['sou_time']));
//            if (empty($ex)) {
//                $ex = ExchangeRateModel::conversion($item['billing_currency_code'], $item['amount_currency'], str_replace('-','',$item['sou_time']));
//            }
//            if (!$ex) {
//                $rate[] = $item['payment_no'];
//            }
//            $save = [
//                'currency_account' => $item['billing_currency_code'],
//                'exchange_tax_account' => $ex,
//            ];
//            $res = $payment_model->where(['id'=>$item['id']])->save($save);
//            if (false === $res) {
//                $model->rollback();
//                die('fail1'.$item['payment_no']);
//            }
//            $r = $payment_audit_model->where(['id'=>$item['payment_audit_id']])->save(['billing_exchange_rate'=>$ex]);
//            if (false === $r) {
//                $model->rollback();
//                die('fail2'.$item['payment_no']);
//            }
//            if ($item['account_transfer_no']) {
//                $rr = $tur_model->where(['account_transfer_no'=>$item['account_transfer_no']])->save([
//                    'currency_code'     => $item['billing_currency_code'],
//                    'original_currency' => $item['billing_currency_code'],
//                    'remitter_currency' => $item['billing_currency_code'],
//                    'other_currency'    => $item['billing_currency_code'],
//                ]);
//                if (false === $rr) {
//                    $model->rollback();
//                    die('fail3'.$item['payment_no']);
//                }
//            } else {
//                $payment_audit_info = $payment_audit_model->find($item['payment_audit_id']);
//                $tr = $tur_model->where(['transfer_no'=>$payment_audit_info['payment_audit_no']])->save([
//                    'currency_code'     => $item['billing_currency_code'],
//                    'original_currency' => $item['billing_currency_code'],
//                    'remitter_currency' => $item['billing_currency_code'],
//                    'other_currency'    => $item['billing_currency_code'],
//                ]);
//                if (false === $tr) {
//                    $model->rollback();
//                    die('fail4'.$item['payment_no']);
//                }
//            }
//        }
//        $model->commit();
//        echo 'success';
//        echo '汇率为空';
//        echo '<pre>';var_dump($rate);
//        echo '***********************************';
//        v($merge);
//    }

    //修复付款单出账币种
    public function repair_billing_currency()
    {
        $model = new Model();
        $model->startTrans();
        $payment_model       = M('payment', 'tb_pur_');
        $payment_audit_model = M('payment_audit', 'tb_pur_');
        $list = $payment_audit_model->where(['status'=>3])->select();
        foreach ($list as $v) {
            $payment_info = $payment_model->where(['payment_audit_id'=>$v['id']])->find();
            if (!$payment_info['currency_account']) {
                $empty[] = $v['payment_audit_no'];
                continue;
            }
            if ($v['billing_currency_cd']) {
                continue;
            }
            $flag[] = $v['payment_audit_no'];
            $res = $payment_audit_model->where(['id'=>$v['id']])->save(['billing_currency_cd'=>$payment_info['currency_account']]);
            if (!$res) {
                $model->rollback();
                die('fail'.$v['payment_audit_no']);
            }
        }
        $model->commit();
        echo 'success';
        echo '<pre>';var_dump($flag);
        echo '************币种为空***************';
        v($empty);

    }

    //还原采购应付出账币种、汇率、日记账4币种
    public function revert_payment_currency()
    {
        $model = new Model();
        $model->startTrans();
        $payment_model = M('payment','tb_pur_');
        $payment_audit_model = M('payment_audit','tb_pur_');
        $tur_model = M('account_turnover','tb_fin_');
        $sql = "SELECT
            pp.id,
            pp.payment_no,
            od.amount_currency,
            ab.currency_code AS billing_currency_code,
            pp.exchange_tax_account,
            ab.update_time,
            ab.account_bank,
            tur.currency_code AS tur_currency_code,
            tur.original_currency,
            tur.remitter_currency,
            tur.other_currency,
            tur.account_transfer_no,
            pa.payment_our_bank_account,
            pa.billing_at,
            pa.id AS payment_audit_id,
            rel.sou_time,
            pa.billing_exchange_rate
        FROM
            tb_pur_payment pp
        LEFT JOIN tb_fin_account_turnover tur ON pp.payment_no = tur.child_transfer_no
        LEFT JOIN tb_pur_payment_audit pa ON pp.payment_audit_id = pa.id
        LEFT JOIN tb_fin_account_bank ab ON pa.payment_our_bank_account = ab.account_bank
        LEFT JOIN tb_pur_relevance_order rel ON pp.relevance_id = rel.relevance_id
        LEFT JOIN tb_pur_order_detail od ON rel.order_id = od.order_id
        WHERE
            pp.`status` = 3
        AND od.amount_currency != pp.currency_account
        AND pp.payment_no IN (
            'YF20180307003',
            'YF20180307003',
            'YF20180319026',
            'YF20180319026',
            'YF20180319020',
            'YF20180319020',
            'YF20180320002',
            'YF20180320002',
            'YF20180320003',
            'YF20180320003',
            'YF20180321005',
            'YF20180321005',
            'YF20180329007',
            'YF20180409023',
            'YF20180409024',
            'YF20180413011',
            'YF20180417052',
            'YF20180417054',
            'YF20180423032',
            'YF20180404002',
            'YF20180419012',
            'YF20180322024',
            'YF20180424007',
            'YF20180412005',
            'YF20180420002',
            'YF20180425006',
            'YF20180424003',
            'YF20180503019',
            'YF20180416002',
            'YF20180503009',
            'YF20180404004',
            'YF20180502004',
            'YF20180425007',
            'YF20180515007',
            'YF20180511013',
            'YF20180511011',
            'YF20180524011',
            'YF20180524008',
            'YF20180608008',
            'YF20180614008',
            'YF20180621001',
            'YF20180627006',
            'YF20180627006',
            'YF20180706010',
            'YF20180606005',
            'YF20180531004',
            'YF20180706004',
            'YF20180706006',
            'YF20180813006',
            'YF20180904019',
            'YF20180907012',
            'YF20180911015',
            'YF20180911014',
            'YF20180912033',
            'YF20180913001',
            'YF20180914001',
            'YF20180920043',
            'YF20180814006',
            'YF20180925006',
            'YF20180925002',
            'YF20180925004',
            'YF20180927017',
            'YF20180928018',
            'YF20180929004',
            'YF20180912031',
            'YF20180929003',
            'YF20181009005',
            'YF20181009018',
            'YF20181009016',
            'YF20181009015',
            'YF20181010027',
            'YF20181015008',
            'YF20180906023',
            'YF20180906024',
            'YF20181017014',
            'YF20181018004',
            'YF20181025004',
            'YF20181026015',
            'YF20181029018',
            'YF20181101007',
            'YF20181101010',
            'YF20181009017',
            'YF20180925003',
            'YF20180930006',
            'YF20181010011',
            'YF20181026017',
            'YF20180928006',
            'YF20181106031',
            'YF20180928019',
            'YF20181108016',
            'YF20181108015',
            'YF20181109005',
            'YF20181107013',
            'YF20181113009',
            'YF20181113010',
            'YF20181116009',
            'YF20181120044',
            'YF20181120008',
            'YF20181120007',
            'YF20180724014',
            'YF20181120039',
            'YF20181120037',
            'YF20181120041',
            'YF20181120043',
            'YF20181121016',
            'YF20181119005',
            'YF20181122001',
            'YF20181128008',
            'YF20181130022',
            'YF20181130006',
            'YF20181210001',
            'YF20181211008',
            'YF20181211017',
            'YF20181210006',
            'YF20181210008',
            'YF20181210007',
            'YF20181218021',
            'YF20181218026',
            'YF20181218018',
            'YF20181218022',
            'YF20181218024',
            'YF20181217016',
            'YF20181221026',
            'YF20181226019',
            'YF20181225008',
            'YF20181221023',
            'YF20181224030',
            'YF20190102005',
            'YF20181229028',
            'YF20181229035',
            'YF20181229033',
            'YF20190103025',
            'YF20181229013',
            'YF20190103002',
            'YF20190103001',
            'YF20190103003',
            'YF20181229029',
            'YF20181229031',
            'YF20181229026',
            'YF20181229007',
            'YF20181213022',
            'YF20181213028',
            'YF20181229009',
            'YF20190102028',
            'YF20190102029',
            'YF20190102030',
            'YF20181212010',
            'YF20190108033',
            'YF20190108032',
            'YF20190108026',
            'YF20190108017',
            'YF20190109007',
            'YF20190109008',
            'YF20190109012',
            'YF20190109013',
            'YF20190109027',
            'YF20190111013',
            'YF20190111014',
            'YF20190115006',
            'YF20190827003',
            'YF20190827009',
            'YF20190827010'
        )";
        $list = $model->query($sql);
        foreach ($list as $item) {
            $ex = 1;
            $save = [
                'currency_account' => $item['amount_currency'],
                'exchange_tax_account' => $ex,
            ];
            $res = $payment_model->where(['id'=>$item['id']])->save($save);
            if (false === $res) {
                $model->rollback();
                die('fail1'.$item['payment_no']);
            }
            $r = $payment_audit_model->where(['id'=>$item['payment_audit_id']])->save([
                'billing_exchange_rate'=>$ex,
                'billing_currency_cd' => $item['amount_currency']
            ]);
            if (false === $r) {
                $model->rollback();
                die('fail2'.$item['payment_no']);
            }
            if ($item['account_transfer_no']) {
                $rr = $tur_model->where(['account_transfer_no'=>$item['account_transfer_no']])->save([
                    'currency_code'     => $item['amount_currency'],
                    'original_currency' => $item['amount_currency'],
                    'remitter_currency' => $item['amount_currency'],
                    'other_currency'    => $item['amount_currency'],
                ]);
                if (false === $rr) {
                    $model->rollback();
                    die('fail3'.$item['payment_no']);
                }
            } else {
                $payment_audit_info = $payment_audit_model->find($item['payment_audit_id']);
                $tr = $tur_model->where(['transfer_no'=>$payment_audit_info['payment_audit_no']])->save([
                    'currency_code'     => $item['amount_currency'],
                    'original_currency' => $item['amount_currency'],
                    'remitter_currency' => $item['amount_currency'],
                    'other_currency'    => $item['amount_currency'],
                ]);
                if (false === $tr) {
                    $model->rollback();
                    die('fail4'.$item['payment_no']);
                }
            }
        }
        $model->commit();
        echo 'success';
    }

    //历史账号处理
    public function account() {
        $model = M('account_bank', 'tb_fin_');
        $model->startTrans();
        $list = $model->where(['open_bank' => ['in',['支付宝','Alipay','PAYPAL']]])->select();
        foreach ($list as $item) {
            if (trim($item['open_bank']) == '支付宝') {
                $res = $model->where(['id'=>$item['id']])->save(['open_bank'=>'国内支付宝','payment_channel_cd'=>'N001000200']);
                if (!$res) {
                    $model->rollback();
                    die('fail');
                }
            } else if (trim($item['open_bank']) == 'Alipay') {
                $res = $model->where(['id'=>$item['id']])->save(['open_bank'=>'国际支付宝','payment_channel_cd'=>'N001000303']);
                if (!$res) {
                    $model->rollback();
                    die('fail');
                }
            } else if (trim($item['open_bank']) == 'PAYPAL') {
                $res = $model->where(['id'=>$item['id']])->save(['open_bank'=>'Paypal','payment_channel_cd'=>'N001000306']);
                if (!$res) {
                    $model->rollback();
                    die('fail');
                }
            }
        }
        $ids = $model->where(['open_bank' => ['not in',['支付宝','国内支付宝','国际支付宝','PAYPAL','Alipay','Paypal']]])->getField('id',true);
        $res = $model->where(['id'=>['in',$ids]])->save(['payment_channel_cd'=>'N001000301']);
        if (!$res) {
            $model->rollback();
            die('fail');
        }
        $model->commit();
        die('success');
    }

    //售后历史数据处理
    public function afterSale()
    {
        $model = M('op_order_after_sale_relevance', 'tb_');
        $where = [
            'return_id' => 0,
            '_logic' => 'or',
            'reissue_id' => 0,
        ];
        $list1 = $model->where($where)->select();
        $model->startTrans();
        foreach ($list1 as $item) {
            if (!$item['return_id'] && !$item['reissue_id']) {
                $model->rollback();
                die('two fail');
            }
            if (!$item['return_id']) {
                $save1 = [
                    'type' => 2,
                    'after_sale_id' => $item['reissue_id']
                ];
                if (!$model->where(['id'=>$item['id']])->save($save1)) {
                    $model->rollback();
                    die('save1 fail');
                }
            }
            if (!$item['reissue_id']) {
                $save2 = [
                    'type' => 1,
                    'after_sale_id' => $item['return_id']
                ];
                if (!$model->where(['id'=>$item['id']])->save($save2)) {
                    $model->rollback();
                    die('save2 fail');
                }
            }
        }
        $map = [
            'return_id' => ['neq', 0],
            '_logic' => 'and',
            'reissue_id' => ['neq', 0],
        ];
        $list2 = $model->where($map)->select();
        foreach ($list2 as $item) {
            if (!$item['return_id'] || !$item['reissue_id']) {
                $model->rollback();
                die('two or fail');
            }
            $add = $save = $item;
            $save['type'] = 1;
            $save['after_sale_id'] = $item['return_id'];
            if (!$model->where(['id'=>$item['id']])->save($save)) {
                $model->rollback();
                die('save or fail');
            }

            unset($add['id']);
            $add['type'] = 2;
            $add['after_sale_id'] = $item['reissue_id'];
            if (!$model->add($add)) {
                $model->rollback();
                die('add or fail');
            }
        }
        $model->commit();
        die('success');
    }

    private function get_user_map()
    {
        $json = '[{"old_name":"huangfeihong","new_name":"Yeogirl.Yun"},{"old_name":"cuihua","new_name":"Philo.Liang"},{"old_name":"ayue","new_name":"Wendy.Chen"},{"old_name":"ajiu","new_name":"Nakisha.Zhang"},{"old_name":"chuliuxiang","new_name":"Lysa.Lan"},{"old_name":"xiaoshami","new_name":"Qingqing.Peng"},{"old_name":"miaoyu","new_name":"Helen.Yuan"},{"old_name":"tiezhu","new_name":"Olivia.Yan"},{"old_name":"qinqiong","new_name":"Jerry.Huang"},{"old_name":"shuanger","new_name":"Sherry.Huang"},{"old_name":"wenji","new_name":"Cara.Cai"},{"old_name":"baoqin","new_name":"Kathy.Tang"},{"old_name":"xiexun","new_name":"Recally .Xu"},{"old_name":"bihen","new_name":"Claire.Zhang"},{"old_name":"shangyang","new_name":"Bryan.Shang"},{"old_name":"dugufang","new_name":"Jojo.Zhu"},{"old_name":"limo","new_name":"Vera.Zhai"},{"old_name":"xinfan","new_name":"Liang.Lyu"},{"old_name":"lixiaolong","new_name":"Albert.Lee"},{"old_name":"qingshui","new_name":"Stephy.Yan"},{"old_name":"zhanchun","new_name":"Jenny.Zhan"},{"old_name":"qianyi","new_name":"Peien.Li"},{"old_name":"baifeng","new_name":"Neo.Du"},{"old_name":"moyan","new_name":"Danica.Li"},{"old_name":"zhanfei","new_name":"Navy.Zhang"},{"old_name":"sunbin","new_name":"Jayden.Lee"},{"old_name":"wangchao","new_name":"Mingyuan.Kang"},{"old_name":"muyu","new_name":"Owen.Ouyang"},{"old_name":"muziyou","new_name":"Lily.Ji"},{"old_name":"lingxuan","new_name":"Lico.Xie"},{"old_name":"daixuan","new_name":"Lucky.Li"},{"old_name":"yuanxi","new_name":"Catherine.Xu"},{"old_name":"nansong","new_name":"Song.Ye"},{"old_name":"yangsu","new_name":"Ben.Huang"},{"old_name":"qingying","new_name":"Alice.Wu"},{"old_name":"hongye","new_name":"Ella.Tian"},{"old_name":"zhaomu","new_name":"ABen.Wang"},{"old_name":"qingzhu","new_name":"Jay.Chen"},{"old_name":"puzhe","new_name":"Aiden.Jin"},{"old_name":"lvdai","new_name":"Robin.Wei"},{"old_name":"wugui","new_name":"Winner.Wu"},{"old_name":"xiangxian","new_name":"Shawn.Sun"},{"old_name":"xuanyuan","new_name":"Neeya.Dong"},{"old_name":"suqin","new_name":"Yingying.Su"},{"old_name":"yufu","new_name":"Xuan.Liu"},{"old_name":"yushu","new_name":"Yuuka.Ezaki"},{"old_name":"qingsha","new_name":"Daisy.Shen"},{"old_name":"feisong","new_name":"Adams.Tan"},{"old_name":"taiji","new_name":"Sam.Yang"},{"old_name":"yuzhen","new_name":"Tina.Kim"},{"old_name":"gonglao","new_name":"Alexey.Kim"},{"old_name":"duhan","new_name":"Karen.Li"},{"old_name":"shangfengzi","new_name":"Mons.Gao"},{"old_name":"nianyun","new_name":"Mike.Wu"},{"old_name":"liangyu","new_name":"Vava.Xia"},{"old_name":"baisui","new_name":"Jeli.Fang"},{"old_name":"fangfei","new_name":"Minjung.Kang"},{"old_name":"yanzi","new_name":"Celiane.Yan"},{"old_name":"tonggu","new_name":"Jason.Chen"},{"old_name":"leshan","new_name":"Blake.Jiang"},{"old_name":"shiqun","new_name":"Yan.Hao"},{"old_name":"shutong","new_name":"Frida.Gong"},{"old_name":"yiyao","new_name":"Aimee.Xu"},{"old_name":"liuche","new_name":"Luke.Liu"},{"old_name":"leyang","new_name":"Yan.Le"},{"old_name":"maobeiyu","new_name":"Beiyu.Mao"},{"old_name":"xun彧","new_name":"Dongho.Han"},{"old_name":"quanhai","new_name":"James.Zhao"},{"old_name":"baohe","new_name":"Regina.Li"},{"old_name":"zhengxiu","new_name":"Amy .Li"},{"old_name":"leiwulong","new_name":"Timur.Valiev"},{"old_name":"lengan","new_name":"Astor.Zhang"},{"old_name":"miaosong","new_name":"Evan.Wu"},{"old_name":"qiyu","new_name":"Tbag.Sun"},{"old_name":"jingfu","new_name":"Fiona.Ma"},{"old_name":"moge","new_name":"Moge.Yao"},{"old_name":"yuanze","new_name":"Zara.Zhang"},{"old_name":"zijian","new_name":"Albert.Tian"},{"old_name":"liuxia","new_name":"Shekwah.Chi"},{"old_name":"sunguan","new_name":"Sane.Lai"},{"old_name":"ding珰","new_name":"Susy.Tang"},{"old_name":"muer","new_name":"Dany.Dang"},{"old_name":"qiushuang","new_name":"Doris.Zhang"},{"old_name":"xiangxue","new_name":"Sophia.Jin"},{"old_name":"shuiying","new_name":"May.Zhang"},{"old_name":"zifeng","new_name":"Feng.Hou"},{"old_name":"jiangzhi","new_name":"Shea.Zhou"},{"old_name":"guishan","new_name":"Calvin.Chen"},{"old_name":"nanqing","new_name":"Addy.Wang"},{"old_name":"yinuo","new_name":"Lynn.Wei"},{"old_name":"songwan","new_name":"Frank.Xu"},{"old_name":"qianqin","new_name":"Stella.Xu"},{"old_name":"tingbai","new_name":"Mikasa.Guo"},{"old_name":"juannian","new_name":"Habit.Wang"},{"old_name":"mengyi","new_name":"Kavin.Li"},{"old_name":"ningtian","new_name":"Haven.He"},{"old_name":"chilian","new_name":"Carry.Huang"},{"old_name":"fuming","new_name":"Mark.Zhong"},{"old_name":"baer","new_name":"Wheat.Wei"},{"old_name":"luofu","new_name":"Terry.Xu"},{"old_name":"tianrui","new_name":"Weslie.Li"},{"old_name":"zhenghe","new_name":"Yuri.Ban"},{"old_name":"lean","new_name":"Bonnie.Yang"},{"old_name":"yuanxiao","new_name":"Ys.Noh"},{"old_name":"liumingqiu","new_name":"Matilda.Wang"},{"old_name":"muzimo","new_name":"Yonghong.Shi"},{"old_name":"yunzhi","new_name":"Summer.Hu"},{"old_name":"meishi","new_name":"Hailey.Yi"},{"old_name":"likexiu","new_name":"Show.Xu"},{"old_name":"jingzhi","new_name":"Carol.Tan"},{"old_name":"gailuojiao","new_name":"Nadia.Deng"},{"old_name":"butianzhi","new_name":"Ben.Bu"},{"old_name":"yiping","new_name":"Liping.Zhou"},{"old_name":"johncooper","new_name":"Egor.Ermolaev"},{"old_name":"davidbeckham","new_name":"Vitaly.Malygin"},{"old_name":"youyan","new_name":"Shirley.Cao"},{"old_name":"shaoqing","new_name":"Blue.Jiang"},{"old_name":"zouyan","new_name":"Kevin.Yu"},{"old_name":"hanshan","new_name":"He.Jiang"},{"old_name":"liuchuanluo","new_name":"Ulysses.Kuang"},{"old_name":"taoyao","new_name":"Lin.Tao"},{"old_name":"qingtan","new_name":"Eloise.Zang"},{"old_name":"lingqing","new_name":"Ezer .Kang"},{"old_name":"ahua","new_name":"Wallace.Wu"},{"old_name":"xiaodai","new_name":"Yu.Dai"},{"old_name":"chunxia","new_name":"Saiya.Jiang"},{"old_name":"shihe","new_name":"JianXiong.Liu"},{"old_name":"yueya","new_name":"Jo.Diao"},{"old_name":"yefan","new_name":"Bill.Mi"},{"old_name":"manyi","new_name":"Joy.Huang"},{"old_name":"manman","new_name":"Tino.Ji"},{"old_name":"shuyi","new_name":"Teresa.Wang"},{"old_name":"waner","new_name":"Tiffany.Cheng"},{"old_name":"anle","new_name":"Joy.Xiao"},{"old_name":"mochen","new_name":"Jinhua. Zhou"},{"old_name":"ziling","new_name":"Lay.Zhou"},{"old_name":"yuanzong","new_name":"Cyrus.Chen"},{"old_name":"meishengxue","new_name":"Sherry.Li"},{"old_name":"baisankong","new_name":"kallen.Liu"},{"old_name":"diqing","new_name":"Michelle.Xiao"},{"old_name":"xiaoyu","new_name":"Jingtao.Fu"},{"old_name":"baiqing","new_name":"Sharon.Zhang"},{"old_name":"hanyu","new_name":"Soyou.Wu"},{"old_name":"shenmo","new_name":"Sellers.Shen"},{"old_name":"zetianwen","new_name":"Lucile.Ji"},{"old_name":"zaiquan","new_name":"Zhao.Ding"},{"old_name":"guoxiang","new_name":"Noel.Kwok"},{"old_name":"changjingzhi","new_name":"Jian.Gong"},{"old_name":"zhouting","new_name":"Jinhui.Liu"},{"old_name":"feifeng","new_name":"Fei.Lam"},{"old_name":"yezi","new_name":"Bowie.Heng"},{"old_name":"wuqinglie","new_name":"Alex.Wu"},{"old_name":"jinwande","new_name":"Sophie.Han"},{"old_name":"zhizhi","new_name":"Dorothy.Zhu"},{"old_name":"xueting","new_name":"Pat.Hui"},{"old_name":"zunbao","new_name":"Derek.Jiang"},{"old_name":"shuiyao","new_name":"Lynne.Li"},{"old_name":"tinghe","new_name":"Carry.Liu"},{"old_name":"jifan","new_name":"Kiven.Li"},{"old_name":"zheyan","new_name":"Sherry.Wang"},{"old_name":"zhouxin","new_name":"Shirley.Zhao"},{"old_name":"zhuoping","new_name":"Roddy.Yu"},{"old_name":"fangyuxiang","new_name":"Sophie.Zhuang"},{"old_name":"shoucheng","new_name":"Guijun.Lyu"},{"old_name":"huaian","new_name":"Dexiang.Chen"},{"old_name":"banfan ","new_name":"Alan.Li"},{"old_name":"yinan","new_name":"Iris.Zhang"},{"old_name":"jinyuan","new_name":"Ryan.Wu"},{"old_name":"guoleyuan","new_name":"Hanbi.Kim"},{"old_name":"zuoyi","new_name":"Summer.Su"},{"old_name":"yibai","new_name":"Victoria.Wang"},{"old_name":"feiyang","new_name":"Cuncheng.Xiao"},{"old_name":"ningxiang","new_name":"HaiLin.Wang"},{"old_name":"ningzhu","new_name":"Ruby.Hong"},{"old_name":"mengman","new_name":"Holiday.He"},{"old_name":"wanjun","new_name":"koko.Ma"},{"old_name":"yishan","new_name":"Hao.Zheng"},{"old_name":"shangguanhai","new_name":"Amanda.Zhang"},{"old_name":"chuyao","new_name":"Mandy.Cui"},{"old_name":"","new_name":"Joe.Chan"},{"old_name":"","new_name":"Rui.Xu"},{"old_name":"","new_name":"Xuejun.Zou"},{"old_name":"","new_name":"Lillian.Yang"}]';
        $json = json_decode($json, true);
        $old_name = array_column($json, 'old_name');
        $new_name = array_column($json, 'new_name');
        return array_combine($old_name, $new_name);
    }

    public function repair_pur()
    {
        $user = $this->get_user_map();
        $pur = M('relevance_order','tb_pur_')->field('relevance_id,last_update_user,prepared_by')->select();//->where(['relevance_id'=>['lt',100]])
        $sql = "UPDATE tb_pur_relevance_order SET ";
        foreach ($pur as $item) {
            if (!empty($user[$item['last_update_user']])) {
                $sql .= " last_update_user = CASE relevance_id
                    WHEN {$item['relevance_id']} THEN
                    '{$user[$item['last_update_user']]}'
                    ELSE
                        last_update_user
                    END,";
            }
            if (!empty($user[$item['prepared_by']])) {
                $sql .= " prepared_by = CASE relevance_id
                    WHEN {$item['relevance_id']} THEN
                    '{$user[$item['prepared_by']]}'
                    ELSE
                        prepared_by
                    END,";
            }
        }
        $sql = trim($sql, ",");
        $Db = new Model();
        $Db->startTrans();
        if ($Db->execute($sql)) {
            $Db->commit();
        } else {
            $Db->rollback();
        }
    }
    /*
     *  ERP 名称替换
     */
    public function erp_replace(){
        $temp = $this->getParams();
        $start = 0;
        $length = 5;
        if (!empty($temp) && isset($temp['start']) && isset($temp['length'])){
            $start = $temp['start'];
            $length = $temp['length'];
        }
        $userData = $this->get_user_map();
        $parame = $this->get_erp_replace_parame();
        $parame = array_splice($parame,$start,$length);
        foreach ($parame as $vb){
            $temp = true;
            if (!empty($vb) && isset($vb['tablePrefix']) && isset($vb['property']) && isset($vb['tableName']) ){
                $objectModel = M($vb['tableName'],$vb['tablePrefix']);
                $objectModel->startTrans();
                $data = $objectModel->field($vb['property'])->group($vb['property'])->select();
                if (is_array($data) && !empty($data)){
                    foreach ($data as $k => $v) {
                        if ( !empty($v[$vb['property']]) ){
                            if (isset($userData[$v[$vb['property']]]) && !empty($userData[$v[$vb['property']]])){
                                $name = $userData[$v[$vb['property']]];
                                $temp = $objectModel->where(''.$vb['property'].' =  "'.$v[$vb['property']].'"  ')->save([ $vb['property'] => $name ]);
                                // Log::write('表名 :'.$vb['tablePrefix'].$vb['tableName']. ' 属性 :'.$vb['tableName'].'SQL :'.$objectModel->getLastSql());
                            }
                        }
                    }
                }
                if ($temp){
                    $objectModel->commit();
                    Log::write('表名 :'.$vb['tablePrefix'].$vb['tableName']. ' 属性 :'.$vb['tableName'].'update succeed');
                }else{
                    $objectModel->rollback();
                    Log::write('表名 :'.$vb['tablePrefix'].$vb['tableName']. ' 属性 :'.$vb['tableName'].'update error');
                }
            }
        }
    }
    /*
     * 获取数据
     */
    public function get_erp_replace_parame(){
        $parame = array(

            // 采购模块
            1 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'sell_information', 'property' => 'seller', 'isNull' => false),
            2 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'relevance_order', 'property' => 'prepared_by', 'isNull' => false),
            3 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'payment_log', 'property' => 'created_by', 'isNull' => false),
            4 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'ship', 'property' => 'create_user', 'isNull' => false),
            5 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'invoice', 'property' => 'create_user', 'isNull' => false),
            6 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'invoice', 'property' => 'confirm_user', 'isNull' => false),
            7 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'return', 'property' => 'created_by', 'isNull' => false),
            8 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'return', 'property' => 'out_of_stock_user', 'isNull' => false),
            9 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'return', 'property' => 'tally_by', 'isNull' => false),
            10 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'deduction_detail', 'property' => 'updated_by', 'isNull' => false),
            11 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'deduction_detail', 'property' => 'updated_by', 'isNull' => false),
            12 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'relevance_order', 'property' => 'last_update_user', 'isNull' => false),
            13 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'relevance_order', 'property' => 'prepared_by', 'isNull' => false),
            14 => array('tablePrefix' => 'tb_sell_', 'tableName' => 'demand', 'property' => 'create_user', 'isNull' => false),
            15 => array('tablePrefix' => 'tb_sell_', 'tableName' => 'action_log', 'property' => 'user', 'isNull' => false),
//
//            // 财务管理
            16 => array('tablePrefix' => 'tb_fin_', 'tableName' => 'claim', 'property' => 'updated_by', 'isNull' => false),
            17 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'payment_audit', 'property' => 'created_by', 'isNull' => false),
            18 => array('tablePrefix' => 'tb_con_', 'tableName' => 'division_our_company', 'property' => 'payment_manager_by', 'isNull' => false),
            19 => array('tablePrefix' => 'tb_pur_', 'tableName' => 'payment_audit_log', 'property' => 'created_by', 'isNull' => false),
            20 => array('tablePrefix' => 'tb_fin_', 'tableName' => 'claim', 'property' => 'created_by', 'isNull' => false),
//
//            // B2B 业务
            21 => array('tablePrefix' => 'tb_b2b_', 'tableName' => 'info ', 'property' => 'PO_USER', 'isNull' => false),
            22 => array('tablePrefix' => 'tb_b2b_', 'tableName' => 'info', 'property' => 'verification_leader_by', 'isNull' => false),
            23 => array('tablePrefix' => 'tb_b2b_', 'tableName' => 'ship_list', 'property' => 'AUTHOR', 'isNull' => false),
            24 => array('tablePrefix' => 'tb_b2b_', 'tableName' => 'claim_deduction', 'property' => 'created_by', 'isNull' => false),
            25 => array('tablePrefix' => 'tb_b2b_', 'tableName' => 'log', 'property' => 'USER_ID', 'isNull' => false),
            26 => array('tablePrefix' => 'tb_con_', 'tableName' => 'division_client', 'property' => 'sales_assistant_by', 'isNull' => false),
//            // 应收列表
            27 => array('tablePrefix' => 'tb_b2b_', 'tableName' => 'receivable', 'property' => 'submit_by', 'isNull' => false),
            28 => array('tablePrefix' => 'tb_b2b_', 'tableName' => 'receivable', 'property' => 'verification_by', 'isNull' => false),
//            // 理货列表
            29 => array('tablePrefix' => 'tb_b2b_', 'tableName' => 'warehouse_list', 'property' => 'AUTHOR', 'isNull' => false),
            30 => array('tablePrefix' => 'tb_b2b_', 'tableName' => 'warehouse_list', 'property' => 'submit_user', 'isNull' => false),
//            // 退货列表
            31 => array('tablePrefix' => 'tb_b2b_', 'tableName' => 'return', 'property' => 'created_by', 'isNull' => false),
            32 => array('tablePrefix' => 'tb_b2b_', 'tableName' => 'return', 'property' => 'warehoused_by', 'isNull' => false),
//            //  仓储管理
            33 => array('tablePrefix' => 'tb_wms_', 'tableName' => 'warehouse', 'property' => 'contacts', 'isNull' => false),
            34 => array('tablePrefix' => 'tb_wms_', 'tableName' => 'warehouse', 'property' => 'in_contacts', 'isNull' => false),
            35 => array('tablePrefix' => 'tb_wms_', 'tableName' => 'warehouse', 'property' => 'out_contacts', 'isNull' => false),
//            // 调拨管理
            36 => array('tablePrefix' => 'tb_con_', 'tableName' => 'division_warehouse', 'property' => 'task_launch_by', 'isNull' => false),
            37 => array('tablePrefix' => 'tb_con_', 'tableName' => 'division_warehouse', 'property' => 'transfer_out_library_by', 'isNull' => false),
            38 => array('tablePrefix' => 'tb_con_', 'tableName' => 'division_warehouse', 'property' => 'transfer_warehousing_by', 'isNull' => false),
            // 正次品转换
            39 => array('tablePrefix' => 'tb_scm_', 'tableName' => 'conversion', 'property' => 'need_reviewer', 'isNull' => false),
            40 => array('tablePrefix' => 'tb_scm_', 'tableName' => 'conversion', 'property' => 'created_by', 'isNull' => false),
            41 => array('tablePrefix' => 'tb_scm_', 'tableName' => 'conversion', 'property' => 'approval_by', 'isNull' => false),
            // 组合商品列表
            42 => array('tablePrefix' => 'tb_wms_', 'tableName' => 'group_bill', 'property' => 'created_by', 'isNull' => false),
            43 => array('tablePrefix' => 'tb_wms_', 'tableName' => 'group_bill', 'property' => 'audit_user', 'isNull' => false),
//            //  库存归属变更
            44 => array('tablePrefix' => 'tb_wms_', 'tableName' => 'allo_attribution', 'property' => 'created_by', 'isNull' => false),
            45 => array('tablePrefix' => 'tb_wms_', 'tableName' => 'allo_attribution', 'property' => 'reviewer_by', 'isNull' => false),
            46 => array('tablePrefix' => 'tb_wms_', 'tableName' => 'allo_attribution', 'property' => 'review_by', 'isNull' => false),
            47 => array('tablePrefix' => 'tb_wms_', 'tableName' => 'allo_attribution', 'property' => 'cancel_by', 'isNull' => false),
        );
        return $parame;
    }


    /**
     *  修复小红书订单中的商品定金  (拆单暂时不处理)
     */
    public function updateOrderGuds(){
        $start = $_POST['start']; // 开始位置
        $end = $_POST['end'];    // 结束位置
        if (empty($start) || empty($end) ){
            var_dump('参数有误');
            die;
        }

        if ($start < 2){
            var_dump('开始位置 必须从第二行开始');
            return;
        }
        ini_set('date.timezone', 'Asia/Shanghai');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
        if ($allRow < $end){
            $end = $allRow;
        }
        $orderModel = M('order','tb_op_');
        $orderGudsModel = M('order_guds','tb_op_');
        $activeSheet = $PHPExcel->getActiveSheet();
        for ($start; $start <= $end; $start++) {
            $orderNo = trim($activeSheet->getCell('A'.$start)->getCalculatedValue());
            $skuNumber = trim($activeSheet->getCell('J'.$start)->getCalculatedValue());
            $skuQuantity = trim($activeSheet->getCell('L'.$start)->getCalculatedValue());
            $skuPrice = trim($activeSheet->getCell('M'.$start)->getCalculatedValue());  // SKU定金
            $payTotalPrice = trim($activeSheet->getCell('AB'.$start)->getCalculatedValue());  // SKU支付总价
            if (!isset($orderNo) || empty($orderNo)){
                var_dump('第 '.$start.' 行 订单号不存在 跳出');
                continue;
            }
            if (!isset($skuNumber) || empty($skuNumber)){
                var_dump('第 '.$start.' 行 SKU编码不存在 跳出');
                continue;
            }
            if (!isset($skuQuantity) || empty($skuQuantity)){
                var_dump('第 '.$start.' 行 SKU件数不存在 跳出');
                continue;
            }
            if (!isset($skuPrice) || empty($skuPrice)){
                var_dump('第 '.$start.' 行 SKU定金不存在 跳出');
                continue;
            }
            if (!isset($payTotalPrice) || empty($payTotalPrice)){
                var_dump('第 '.$start.' 行 SKU支付总价 跳出');
                continue;
            }

            $where = [
                'tb_op_order.ORDER_NO' => $orderNo,
                'tb_op_order_guds.SKU_ID' => $skuNumber,
                'tb_op_order_guds.ITEM_COUNT' => $skuQuantity
            ];

            $data = $orderModel
                ->field('tb_op_order_guds.ORDER_ID,CHILD_ORDER_ID,tb_op_order_guds.ITEM_COUNT,tb_op_order_guds.deposit_amount')

                ->join('left join tb_op_order_guds on tb_op_order_guds.ORDER_ID = tb_op_order.ORDER_ID')
                ->where($where)
                ->order( 'tb_op_order_guds.CREATE_AT' )
                ->find();

            if (!isset($data['ORDER_ID']) ||  empty($data['ORDER_ID'])){
                var_dump('第 '.$start.' 数据不存在 跳出 SQL :'.$orderModel->getLastSql());
                continue;
            }
            $model = M();
            $model->startTrans();

            // 订单商品数据
            $gudsWhere = [
                'SKU_ID' => $skuNumber,
                'ITEM_COUNT' => $skuQuantity,
                'ORDER_ID' => $data['ORDER_ID']
            ];
            $saveData = ['deposit_amount' => $skuPrice];
            $res = $orderGudsModel->where($gudsWhere)->save($saveData);

            if ($res === false){
                var_dump('第 '.$start.' 更新失败 tb_op_order_guds  SQL :'.$orderGudsModel->getLastSql());
                $model->rollback();
                continue;
            }
            //  订单数据  存在子单 修改支付总额
            $orderWhere = [
                'ORDER_ID' => $data['ORDER_ID']
            ];
            $saveData = ['PAY_TOTAL_PRICE' => $payTotalPrice];

            $res = $orderModel->where($orderWhere)->save($saveData);
            if ($res === false){
                var_dump('第 '.$start.' 更新失败 tb_op_order SQL :'.$orderModel->getLastSql());
                $model->rollback();
                continue;
            }
            // 处理子单数据
            $unit = $skuPrice / $skuQuantity;
            if (isset($data['CHILD_ORDER_ID']) && !empty($data['CHILD_ORDER_ID'])){
                $ids = explode(',',$data['CHILD_ORDER_ID']);
                foreach ($ids as $orderId){
                    $orderWhere = [
                        'ORDER_ID' => $orderId
                    ];
                    $data = $orderGudsModel->field('ITEM_COUNT')->where($orderWhere)->find();

                    if (isset($data['ITEM_COUNT']) && !empty($data['ITEM_COUNT'])){
                        $deposit_amount = $unit * $data['ITEM_COUNT'];
                        $saveData = [
                            'deposit_amount' => $deposit_amount
                        ];
                        $res = $orderGudsModel->where($orderWhere)->save($saveData);
                        if ($res === false){
                            var_dump('第 '.$start.' 更新失败 子单更新 SQL :'.$orderGudsModel->getLastSql());
                            continue;
                        }
                        $saveData = ['PAY_TOTAL_PRICE' => $payTotalPrice];
                        $res = $orderModel->where($orderWhere)->save($saveData);
                        if ($res === false){
                            var_dump('第 '.$start.' 更新失败 子单更新 SQL :'.$orderModel->getLastSql());
                            continue;
                        }
                        Logs('第 '.$start.' 更新成功 订单编号 ID:'.$orderId);
                    }
                    var_dump('第 '.$start.' 更新成功 订单编号:'.$orderNo."  子单 ：".$orderId);
                }
            }
            Logs('第 '.$start.' 更新成功 订单编号:'.$orderNo);
            $model->commit();
        }
        var_dump("UPTATE OK");
        die;
    }


    /** 导入 偏远地区
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function updateAreaConfiguration(){
        $start = $_POST['start']; // 开始位置
        $end = $_POST['end'];    // 结束位置
        if (empty($start) || empty($end) ){
            var_dump('参数有误');
            die;
        }
        if ($start < 2){
            var_dump('开始位置 必须从第二行开始');
            return;
        }
        ini_set('date.timezone', 'Asia/Shanghai');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
        if ($allRow < $end){
            $end = $allRow;
        }

        $areaConfiguration = M('area_configuration','tb_op_');
        $activeSheet = $PHPExcel->getActiveSheet();
        $data = $this->get_area(0);
        ;
        for ($start; $start <= $end; $start++) {
            $country = trim($activeSheet->getCell('A' . $start)->getCalculatedValue());
            $prefixPostalCode = trim($activeSheet->getCell('B' . $start)->getCalculatedValue());
            if (!isset($country) || empty($country)){
                var_dump('第 '.$start.' 行 国家不存在    跳出');
                continue;
            }
            if (!isset($prefixPostalCode) || empty($prefixPostalCode)){
                var_dump('第 '.$start.' 行 邮编前N位不存在 跳出');
                continue;
            }
            $country_id = $data[$country];
            if (!isset($country_id) || empty($country_id)){
                var_dump('第 '.$start.' 行 国家ID 不存在 跳出');
                continue;
            }
            $where = [
                'country_id' => $country_id,
                'prefix_postal_code' => $prefixPostalCode
            ];
            $ret = $areaConfiguration->field('id')->where($where)->find();
            if (!empty($ret)){
                var_dump('第 '.$start.' 行 数据已经存在  :'.json_encode($ret).' 跳出');
                continue;
            }
            $saveData = array(
                'country_id' => $country_id,
                'prefix_postal_code' => $prefixPostalCode,
                'description' => '',
                'created_by' => 'Habit.Wang',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_by' => 'Habit.Wang',
                'updated_at' => date('Y-m-d H:i:s'),

            );
            $ret = $areaConfiguration->add($saveData);
            if ($ret === false){
                var_dump('第 '.$start.' 行 添加失败  :'.json_encode($ret).' 跳出');
            }
            var_dump('第 '.$start.' 行 添加成功  :'.$ret);
        }
    }

    /**
     * 获取地址
     */
    public function get_area($parent_no) {
        $is_id = I('is_id');
        if (strval($is_id) === 'Y' && $parent_no) {
            // 表明parent_no传的是id,而不是area_no，需要根据id获取area_no
            $address_info = (new AreaModel())->getAreaById($parent_no);
            $parent_no = $address_info['area_no'];
        }
        $address    = (new AreaModel())->getChildrenArea($parent_no);
        $data =  [];
        foreach ($address as $key => $v){
            $data[$v['zh_name']] = $v['id'];
        }
        return $data;

        $orderModel = M('order','tb_op_');
        $activeSheet = $PHPExcel->getActiveSheet();
        for ($start; $start <= $end; $start++) {
            $orderNo = trim($activeSheet->getCell('A'.$start)->getCalculatedValue());
            if (!isset($orderNo) || empty($orderNo)){
                var_dump('第 '.$start.' 行 订单号不存在 跳出');
                continue;
            }
            $where = [
                'tb_op_order.ORDER_NO' => $orderNo,
            ];

            $ret = $orderModel->field('ID')->where($where)->find();
            if (empty($ret)){
                var_dump('第 '.$start.' 数据不存在 跳出 SQL :'.$orderModel->getLastSql());
                continue;
            }
            $updateData = array(
                'PAY_TOTAL_PRICE_DOLLAR' => null
            );
            $where = [
                'tb_op_order.ID' => $ret['ID'],
            ];

            $ret = $orderModel->field('ID')->where($where)->save($updateData);
            if ($ret === false){
                var_dump('第 '.$start.' 修改失败 跳出 SQL :'.$orderModel->getLastSql());
                continue;
            }
        }
        var_dump("UPTATE OK");
        die;
    }

    /**
     *  修复小红书订单中的支付总价美元
     */
    public function updateOrderGudsDollar(){
        $start = $_POST['start']; // 开始位置
        $end = $_POST['end'];    // 结束位置
        if (empty($start) || empty($end) ){
            var_dump('参数有误');
            die;
        }
        if ($start < 2){
            var_dump('开始位置 必须从第二行开始');
            return;
        }
        ini_set('date.timezone', 'Asia/Shanghai');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
        if ($allRow < $end){
            $end = $allRow;
        }
        $orderModel = M('order','tb_op_');
        $activeSheet = $PHPExcel->getActiveSheet();
        for ($start; $start <= $end; $start++) {
            $orderNo = trim($activeSheet->getCell('A'.$start)->getCalculatedValue());
            if (!isset($orderNo) || empty($orderNo)){
                var_dump('第 '.$start.' 行 订单号不存在 跳出');
                continue;
            }
            $where = [
                'tb_op_order.ORDER_NO' => $orderNo,
            ];

            $ret = $orderModel->field('ID')->where($where)->find();
            if (empty($ret)){
                var_dump('第 '.$start.' 数据不存在 跳出 SQL :'.$orderModel->getLastSql());
                continue;
            }
            $updateData = array(
                'PAY_TOTAL_PRICE_DOLLAR' => null
            );
            $where = [
                'tb_op_order.ID' => $ret['ID'],
            ];
            $ret = $orderModel->field('ID')->where($where)->save($updateData);
            if ($ret === false){
                var_dump('第 '.$start.' 修改失败 跳出 SQL :'.$orderModel->getLastSql());
                continue;
            }
        }
        var_dump("UPTATE OK");
        die;
    }


    /**
     *  处理小红书
     */
    public function disposeOrder(){
        $type = $_POST['type'];   // 操作
        if ($type != 'update'){
            var_dump('有误');
            die;
        }
        $where['PLAT_CD'] = 'N000833300';
        $where['STORE_ID'] = 43;
        $where['ORDER_TIME'] = array('BETWEEN',['2019-12-01 00:00:00','2019-12-13 00:00:00']);
        $where['PARENT_ORDER_ID'] = array('exp','IS NULL');
        $where['CHILD_ORDER_ID'] = array('exp','IS NOT NULL');
        $data = M('order','tb_op_')->field('ORDER_ID,CHILD_ORDER_ID,PAY_TOTAL_PRICE,PAY_TOTAL_PRICE_DOLLAR')->where($where)->select();
        if (empty($data)){
            var_dump('结束 不存在数据');
            die;
        }
        foreach ($data as $value){
            $ids = explode(',',$value['CHILD_ORDER_ID']);
            $whereIds['ORDER_ID'] = array('in',$ids);
            $save_data = array(
                'PAY_TOTAL_PRICE' => $value['PAY_TOTAL_PRICE'],
                'PAY_TOTAL_PRICE_DOLLAR' => $value['PAY_TOTAL_PRICE_DOLLAR'],
            );
            $ret =  M('order','tb_op_')->where($whereIds)->save($save_data);
            if ($ret === false){
                echo "修改失败 where :".json_encode($whereIds)." save_data :".json_encode($save_data)."\r\n";
            }
            echo "修改成功 母:".$value['ORDER_ID']. " 子 :".$value['CHILD_ORDER_ID']."\r\n";
        }
        var_dump('完成');
        die;
    }


    /** 处理店铺信息
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function updateMsStore(){
        $start = $_POST['start']; // 开始位置
        $end = $_POST['end'];    // 结束位置
        if (empty($start) || empty($end) ){
            var_dump('参数有误');
            die;
        }
        if ($start < 2){
            var_dump('开始位置 必须从第二行开始');
            return;
        }
        ini_set('date.timezone', 'Asia/Shanghai');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
        if ($allRow < $end){
            $end = $allRow;
        }
        M()->startTrans();
        $activeSheet = $PHPExcel->getActiveSheet();
        $no_data = array();
        $userId = DataModel::getUserIdByName('Adams.Tan');
        for ($start; $start <= $end; $start++) {
            $ID = trim($activeSheet->getCell('A' . $start)->getValue());
            $plat_explain = trim($activeSheet->getCell('B' . $start)->getValue());
            $up_shop_time = date('Y-m-d H:i:s',PHPExcel_Shared_Date::ExcelToPHP($activeSheet->getCell('C' . $start)->getValue()));
            $STORE_INDEX_URL = trim($activeSheet->getCell('D' . $start)->getValue());
            $STORE_BACKSTAGE_URL = trim($activeSheet->getCell('E' . $start)->getValue());
            $up_shop_num = trim($activeSheet->getCell('F' . $start)->getValue());
            $company_cd = trim($activeSheet->getCell('G' . $start)->getValue());
            $proposer_email = trim($activeSheet->getCell('H' . $start)->getValue());
            $proposer_phone = trim($activeSheet->getCell('I' . $start)->getValue());
            $proposer_by = trim($activeSheet->getCell('J' . $start)->getValue());
            $store_by = trim($activeSheet->getCell('K' . $start)->getValue());
//            $shop_manager_id = trim($activeSheet->getCell('A' . $start)->getCalculatedValue());
            $is_fee = trim($activeSheet->getCell('L' . $start)->getValue());
            $remark = trim($activeSheet->getCell('M' . $start)->getValue());
            $credit_card_explain = trim($activeSheet->getCell('N' . $start)->getValue());
            if (empty($ID)){
                var_dump('第'.$start.' 行 ID 不存在 跳出');
                continue;
            }
            $update_data = array(
                'plat_explain' => $plat_explain,
                'proposer_email' => $proposer_email,
                'up_shop_time' => $up_shop_time,
                'STORE_INDEX_URL' => $STORE_INDEX_URL,
                'STORE_BACKSTAGE_URL' => $STORE_BACKSTAGE_URL,
                'up_shop_num' => $up_shop_num,
                'company_cd' => $company_cd,
                'proposer_phone' => $proposer_phone,
                'proposer_by' => DataModel::getUserIdByName($proposer_by),
                'store_by' => $store_by,
                'is_fee' => $is_fee,
                'remark' => $remark,
                'credit_card_explain' => $credit_card_explain,
                'shop_manager_id' => DataModel::getUserIdByName($store_by),
                'UPDATE_TIME' =>date("Y-m-d H:i:s",time()),
                'UPDATE_USER_ID' => $userId
            );
            var_dump($update_data);
            $where = array(
                'ID' => $ID
            );
            $ret = M('store','tb_ms_')->where($where)->save($update_data);
            if ($ret === false){
                echo '第 '.$start.' 行更新失败 ID :'.$ID."\r\n";
                M()->rollback();
                die;
            }
            echo '第 '.$start.' 行更新成功 ID :'.$ID. " \r\n";
            array_push($no_data,$ID);
        }
        M()->commit();
        echo "结束";
        die;
    }

    /**
     *
     *
     */
    public function updateMsStoreBy(){
        $start = $_POST['start']; // 开始位置
        $end = $_POST['end'];    // 结束位置
        if (empty($start) || empty($end) ){
            var_dump('参数有误');
            die;
        }
        if ($start < 2){
            var_dump('开始位置 必须从第二行开始');
            return;
        }
        ini_set('date.timezone', 'Asia/Shanghai');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
        if ($allRow < $end){
            $end = $allRow;
        }
        $activeSheet = $PHPExcel->getActiveSheet();
        $no_data = array();
        for ($start; $start <= $end; $start++) {
            $ID = trim($activeSheet->getCell('A' . $start)->getValue());
            array_push($no_data,$ID);
        }
        if (empty($no_data)){
            echo "提价数据有误";
            die;
        }
        // 获取离职用户
        $where = [
            'STATUS' => "在职",
        ];
        $tmep_data  = M('card','tb_hr_')
            ->field('tb_ms_store.store_by')
            ->join('INNER JOIN tb_ms_store ON tb_ms_store.store_by = tb_hr_card.ERP_ACT')
            ->join('INNER JOIN bbm_admin ON tb_ms_store.store_by = bbm_admin.M_NAME')
            ->where($where)
            ->select();
        if (!empty($tmep_data)){
            $names = array_column($tmep_data,'store_by');
            $whereStore['store_by'] = array('not in',$names);

        }else{
            var_dump('没有存在ERP账号绑定的店铺用户');
        }


        $whereStore['ID'] = array('not in',$no_data);
        $userId = DataModel::getUserIdByName('Adams.Tan');
        $update_store_data  =  array(
            'store_by' => 'Kathy.Tang',
            'shop_manager_id' => DataModel::getUserIdByName('Kathy.Tang'),
            'UPDATE_TIME' =>date("Y-m-d H:i:s",time()),
            'UPDATE_USER_ID' => $userId
        );
        $ret = M('store','tb_ms_')->where($whereStore)->save($update_store_data);
        if ($ret === false){
            var_dump('修改失败');
        }
        var_dump('修改成功');
        var_dump("SQL ::::::".M('store','tb_ms_')->getLastSql());
        var_dump('结束');
        die;
    }

    /**
     *  处理仓库数据（是否为保税仓）
     */
    public function disposeWarehouse(){
        $parame = $_POST['parame'];  // 简单参数验证
        if ($parame != 'admin'){
            var_dump('参数有误');
            die;
        }
        $where['warehouse'] = array('like','%保税%');
        $warehouse_data = M('warehouse','tb_wms_')->field('id,warehouse')->where($where)->select();
        if (!empty($warehouse_data)){
            $ids = array_column($warehouse_data,'id');
            $where = array(
                'id' => ['in',$ids]
            );
            $save_data = array(
                'is_bonded' => 1
            );
            $ret = M('warehouse','tb_wms_')->where($where)->save($save_data);
            if ($ret === false){
                var_dump('处理失败');
                die;
            }
        }else{
            var_dump('不存在保税仓');
            die;
        }
        var_dump('处理成功');
        die;
    }

    /**
     *  修改售后单数据
     */
    public function repairOrderRefund(){
        $parame = $_POST['parame'];  // 简单参数验证
        if ($parame != 'admin'){
            var_dump('参数有误');
            die;
        }
        $order_id = $_POST['order_id'];
        if (empty($order_id)){
            var_dump('訂單不存在 參數有誤');
            die;
        }
        $orderData = M('order','tb_op_')->field('ORDER_ID,ORDER_NO')->where(['ORDER_ID'=>$order_id])->find();
        if (empty($orderData)){
            var_dump('訂單不存在');
            die;
        }
        var_dump($orderData);
        $orderRefundData = M('order_return','tb_op_')->field('order_id,order_no')->where(['order_id'=>$order_id])->find();
        if (empty($orderRefundData)){
            var_dump('售后单不存在');
            die;
        }
        var_dump($orderRefundData);
        $save_data = array(
            'order_no' => $orderData['ORDER_NO']
        );
        $ret = M('order_return','tb_op_')->where(['order_id'=>$order_id])->save($save_data);
        var_dump($ret);
        var_dump('修改完成');
    }

    public function deleteExtend()
    {
        $model = new Model();
        $res = $model->execute('DELETE FROM tb_op_order_extend WHERE id in (161645, 161698, 161719, 161603)');
        echo $res;
    }

    /** 处理店铺信息(收入公司)
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function updateIncomeCompany(){
        var_dump(TodoModel::the_requirements_assigned_to_me());die;

        $start = $_POST['start']; // 开始位置
        $end = $_POST['end'];    // 结束位置
        if (empty($start) || empty($end) ){
            var_dump('参数有误');
            die;
        }
        if ($start < 2){
            var_dump('开始位置 必须从第二行开始');
            return;
        }
        ini_set('date.timezone', 'Asia/Shanghai');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
        if ($allRow < $end){
            $end = $allRow;
        }
        M()->startTrans();
        $activeSheet = $PHPExcel->getActiveSheet();
        for ($start; $start <= $end; $start++) {
            $ID = trim($activeSheet->getCell('A' . $start)->getValue());
            $income_company_cd = trim($activeSheet->getCell('B' . $start)->getValue());

            if (empty($ID) || empty($income_company_cd)){
                var_dump('第'.$start.' 行 id 或 income_company_cd 不存在 跳出');
                continue;
            }
            $update_data = array(
                'income_company_cd' => $income_company_cd
            );
            var_dump($update_data);
            $where = array(
                'ID' => $ID
            );
            $ret = M('store','tb_ms_')->where($where)->save($update_data);
            if ($ret === false){
                echo '第 '.$start.' 行更新失败 ID :'.$ID."\r\n";
                M()->rollback();
                die;
            }
            echo '第 '.$start.' 行更新成功 ID :'.$ID. " \r\n";
        }
        M()->commit();
        echo "结束";
        die;
    }

    public function delCust ()
    {
        $model = new Model();
        $res = $model->execute('delete from tb_ms_thrd_cust where CUST_ID = 3325');
        var_dump($res);die;
    }

    /**
     * 历史记录需要处理(历史订单)
     */
    public function disposeOrderExtend(){
        $admin = $_POST['admin'];
        if ($admin != 'admin'){
            var_dump('参数有误');
            die;
        }
        $sql = "SELECT
                CHILD_ORDER_ID,
                other_discounted_price,
                platform_discount_price,
                seller_discount_price
            FROM
                `tb_op_order`
            LEFT JOIN tb_op_order_extend ON tb_op_order.ORDER_ID = tb_op_order_extend.order_id
            WHERE
                PARENT_ORDER_ID IS NULL
            AND CHILD_ORDER_ID IS NOT NULL
            AND ORDER_CREATE_TIME >= '2020-01-01'
            AND (
                other_discounted_price > 0
                OR platform_discount_price > 0
                OR seller_discount_price > 0
            )";
        $query = M()->query($sql);
        if (!empty($query)){
            M()->startTrans();
            foreach ($query as $key => $value){
                echo json_encode($value)."\r\n";
                $order_id_data = explode(',',$value['CHILD_ORDER_ID']);
                $sava_data = array(
                    'other_discounted_price' =>$value['other_discounted_price'],
                    'platform_discount_price' =>$value['platform_discount_price'],
                    'seller_discount_price' =>$value['seller_discount_price'],
                );
                $where['order_id'] = ['in',$order_id_data];
                $ret = M('order_extend','tb_op_')->where($where)->save($sava_data);
                if ($ret === false){
                    M()->rollback();
                    die;
                }

            }
            M()->commit();
        }else{
            echo "无数据";die;
        }
        echo "结束";
        die;

    }

    /**
     * 修复采购单我方公司
     */
    public function repairPurOurComany ()
    {
        $model = new Model();
        $res = $model->execute("UPDATE tb_pur_order_detail SET our_company = 'N001244713' WHERE our_company = 'N001242400'");
        echo $res;
        die;
    }

    /**
     *
     */
    public function repairFinOurComany ()
    {
        $model = new Model();

            $res   = $model->execute("UPDATE tb_pur_payment_audit SET our_company_cd = 'N001244713' WHERE our_company_cd = 'N001242400'");
    }

    /**
     * 付款单-b2c退款单修改支付方式
     */
    public function pay_way ()
    {
        $model = new Model();
        $res = $model->execute("UPDATE tb_pur_payment_audit set payment_way_cd = 'N003020005' where source_cd = 'N003010003'");
        echo $res;
        die;
    }
    
    public function trade_no_repair ()
    {
        $model = M('payment_audit', 'tb_pur_');
        $list = $model->where(['source_cd'=>'N003010003'])->select();
        $nos = array_column($list, 'platform_order_no');
        $order_map = M('order','tb_op_')->where(['ORDER_NO'=>['in', $nos]])->getField('ORDER_NO,PAY_TRANSACTION_ID');
        foreach ($list as $v) {
            $res = $model->where(['id'=>$v['id']])->save(['trade_no'=>$order_map[$v['platform_order_no']]]);
            if (false === $res) {
                echo 'id'. $v['id']. 'fail';die;
            }
        }
        echo 'success';
    }

    //删除重复生成日记账
    public function fin_trunover()
    {
        $sql = 'SELECT id from tb_fin_account_turnover where transfer_no IN (select transfer_no from tb_fin_account_turnover where transfer_no like "FK%" GROUP BY transfer_no, transfer_type HAVING count(id) > 1) AND id not in ( select max(id) from tb_fin_account_turnover where transfer_no like "FK%" GROUP BY transfer_no, transfer_type HAVING count(id) > 1)';
        $model = new Model();
        $model->startTrans();
        $ids = $model->query($sql);
        $ids = array_column($ids, 'id');
        Logs($ids, __FUNCTION__, __CLASS__);
        $res1 = $model->table('tb_fin_account_turnover')->where(['id'=>['in',$ids]])->delete();
        if (!$res1) {
            $model->rollback();
            die('删除日记账失败');
        }
        $res2 = $model->table('tb_fin_claim')->where(['account_turnover_id'=>['in',$ids]])->delete();
        if (!$res2) {
            $model->rollback();
            die('删除日记账关联失败');
        }
        $model->commit();
        die('success');
    }

    /**
     * 处理关联交易订单销售价格有误
     */
    public function disposeRel(){
        die;
        $list = M('rel_trans','tb_fin_')
                ->field('tb_fin_rel_trans.rel_trans_no,
                tb_fin_rel_trans.rel_price,
                tb_fin_rel_trans.sku_quantity,
                tb_fin_rel_trans.rel_currency_cd,
                tb_fin_rel_trans.create_at,
                tb_wms_stream.unit_price,
                tb_wms_stream.unit_price_origin,
                tb_wms_stream.currency_id')
                ->join('LEFT JOIN tb_wms_bill ON tb_fin_rel_trans.rel_trans_no = tb_wms_bill.link_bill_id')
                ->join('LEFT JOIN tb_wms_stream ON tb_wms_stream.bill_id = tb_wms_bill.id')
                ->whereString("	tb_wms_bill.bill_type = 'N000941004'  AND trigger_type = 'N003220002' ")
                ->select();
        $list = CodeModel::autoCodeTwoVal($list,['rel_currency_cd']);
        if (!empty($list)) {
            foreach ($list as $value) {
                $unit_price_origin = !empty($value['unit_price_origin']) ? $value['unit_price_origin'] : 0;
                $unit_price = $unit_price_origin * $value['sku_quantity'] ;
                $save_data = array(
                    'rel_price' => $unit_price,
                    'rel_currency_cd' => $value['currency_id']
                );
                $where = array(
                    'rel_trans_no'=>$value['rel_trans_no']
                );
                M('rel_trans','tb_fin_')->where($where)->save($save_data);
            }
        }
       var_dump('处理完成');
    }

    public function disposeStrom(){
        $save_data = array(
            'SEND_ORD_TYPE' => 0
        );
        M('store','tb_ms_')->where("ID > 0")->save($save_data);
        var_dump('处理完成');
    }

    public function disRelTrans()
    {
        die;
        $list = M("rel_trans", 'tb_fin_')
            ->field('
                COUNT(tb_fin_rel_trans.id) AS cmn,
                tb_fin_rel_trans.id,
                tb_fin_rel_trans.ord_id,
                tb_fin_rel_trans.trigger_type,
                tb_fin_rel_trans.rel_trans_no,
                tb_fin_rel_trans.sku_id,
                tb_wms_bill.link_bill_id,
                tb_wms_stream.GSKU')
            ->join('LEFT JOIN tb_wms_bill ON tb_fin_rel_trans.rel_trans_no = tb_wms_bill.link_bill_id')
            ->join('LEFT JOIN tb_wms_stream ON tb_wms_bill.id = tb_wms_stream.bill_id')
            ->whereString('tb_fin_rel_trans.sku_id != tb_wms_stream.GSKU')
            ->group('tb_fin_rel_trans.id')
            ->select();
        $model = M("rel_trans", 'tb_fin_');
        $model->startTrans();
        if (!empty($list)) {
            foreach ($list as $value) {
                var_dump($value['rel_trans_no']);
                $rel_trans_no = substr($value['rel_trans_no'], 0,str_len($value['rel_trans_no']) - 4);
                var_dump($rel_trans_no);
                $update_rel_trans_no = "";
                foreach ($list as $itme) {
                    if (strpos($itme['rel_trans_no'], $rel_trans_no) !== false) {
                        if ($value['sku_id'] == $itme['GSKU']) {
                            $update_rel_trans_no = $itme['rel_trans_no'];
                            continue;
                        }
                    }
                }
                if (!empty($update_rel_trans_no)) {
                    var_dump("已查到数据." . $update_rel_trans_no);
                    $where = array(
                        'id' => $value['id']
                    );
                    $save_data = array(
                        'rel_trans_no' => $update_rel_trans_no . '_new'
                    );
                    $ret = $model->where($where)->save($save_data);
                    if (!$ret) {
                        var_dump('修改失败 ：' . $model->_sql());
                        $model->rollback();
                        die;
                    }
                } else {
                    var_dump('查询不到数据 :' . $value['rel_trans_no']);
                }
            }
        }
        $model->commit();
        var_dump('处理完成');
    }

    public function disNew()
    {
        die;
        $list = M("rel_trans", 'tb_fin_')
            ->field('id,rel_trans_no')
            ->whereString(" rel_trans_no LIKE '%new' ")
            ->select();
        $model = M("rel_trans", 'tb_fin_');
        $model->startTrans();
        if (!empty($list)) {

            foreach ($list as $itme) {
                $rel_trans_no = str_replace('_new', '', $itme['rel_trans_no']);
                $save_data = array(
                    'rel_trans_no' => $rel_trans_no
                );
                $where = array(
                    'id' => $itme['id']
                );
                $ret = $model->where($where)->save($save_data);
                if (!$ret) {
                    var_dump('修改失败 ：' . $model->_sql());
                    $model->rollback();
                    die;
                }

            }
        }
        $model->commit();
        var_dump('处理完成');
    }

    public function disRelNo(){
        die;
        $rel_trans_no = $_POST['rel_trans_no'];
        $rel_id = $_POST['rel_id'];
        $save_data = array(
            'rel_trans_no' => $rel_trans_no
        );
        $where = array(
            'id' => $rel_id
        );
        $ret = M('rel_trans','tb_fin_')->where($where)->save($save_data);
        var_dump($ret);
        var_dump('处理完成');
    }


    /** 处理店铺信息(销售小团队)
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public function updateSellSmallTeam(){

        $start = $_POST['start']; // 开始位置
        $end = $_POST['end'];    // 结束位置
        if (empty($start) || empty($end) ){
            var_dump('参数有误');
            die;
        }
        if ($start < 2){
            var_dump('开始位置 必须从第二行开始');
            return;
        }
        ini_set('date.timezone', 'Asia/Shanghai');
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数

        if ($allRow < $end){
            $end = $allRow;
        }
        M()->startTrans();
        $activeSheet = $PHPExcel->getActiveSheet();
        for ($start; $start <= $end; $start++) {
            $ID = trim($activeSheet->getCell('A' . $start)->getValue());
            $sell_small_team_cd = trim($activeSheet->getCell('B' . $start)->getValue());

            if (empty($ID) || empty($sell_small_team_cd)){
                var_dump('第'.$start.' 行 id 或 sell_small_team_cd 不存在 跳出');
                continue;
            }
            $update_data = array(
                'sell_small_team_cd' => $sell_small_team_cd
            );
            $where = array(
                'ID' => $ID
            );
            $ret = M('store','tb_ms_')->where($where)->save($update_data);
            if ($ret === false){
                echo '第 '.$start.' 行更新失败 ID :'.$ID."\r\n";
                M()->rollback();
                die;
            }
            echo '第 '.$start.' 行更新成功 ID :'.$ID. " \r\n";
        }
        M()->commit();
        echo "结束";
        die;
    }


    public function disSmallTeam(){
        // 销售
        $list = M('quotation_goods','tb_sell_')->field('id,sell_small_team_json')
            ->where(['_string'=>'sell_small_team_json IS NOT NULL'])->select();
       if ($list){
           foreach ($list as $item){
                $sell_small_team_json = json_decode($item['sell_small_team_json'],true);
                foreach ($sell_small_team_json as $val){
                    if (empty($val['number']) || empty($val['small_team_code'])){
                        M('quotation_goods','tb_sell_')
                            ->where(array('id'=>$item['id']))->save(array('sell_small_team_json'=>""));
                        continue;
                    }
                }

           }
       }
        // 采购
        $list = M('goods_information','tb_pur_')->field('information_id,sell_small_team_json')
            ->where(['_string'=>'sell_small_team_json IS NOT NULL'])->select();
        if ($list){
            foreach ($list as $item){
                $sell_small_team_json = json_decode($item['sell_small_team_json'],true);
                foreach ($sell_small_team_json as $val){
                    if (empty($val['number']) || empty($val['small_team_code'])){
                        M('goods_information','tb_pur_')
                            ->where(array('information_id'=>$item['information_id']))->save(array('sell_small_team_json'=>""));
                        continue;
                    }
                }
            }
        }
       var_dump('处理完成');
       die;
    }

    /**
     *  处理渠道平台
     */
    public function disChannelPlatform(){

        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $url = INSIGHT.'/insight-backend/gpPlatform/queryPlatform';
        $channel_platform_list = (new PromotionTaskService())->requsetInsightJson($url,array());
        $channel_platform_list = json_decode($channel_platform_list,true);
        $channel_platform_data_old = array();  // 数据集
        $channel_platform_data_new = array();  // 数据集

        $channel_platform_old = array();  //  标签集
        $channel_platform_new = array();  //  标签集
        $channel_platform_new_ower = array(); // 大小写对应关系
        if ($channel_platform_list && $channel_platform_list['datas']){
            foreach ($channel_platform_list['datas'] as $itme){
                $platform_tag = $itme['platform_tag'];
                $platform_tag = strtolower($platform_tag);
                $channel_platform_data_old[$platform_tag] = $itme['id'];
                array_push($channel_platform_old,$platform_tag);
            }
            $PHPExcel = $PHPReader->load($filePath);
            $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
            $activeSheet = $PHPExcel->getActiveSheet();
            for ($start = 1; $start <= $allRow; $start++) {
                $channel_platform_stage = trim($activeSheet->getCell('A' . $start)->getValue());
                $channel_platform_stage = str_replace(' ','',$channel_platform_stage);
                $channel_platform_name = trim($activeSheet->getCell('B' . $start)->getValue());
                if ($channel_platform_stage && $channel_platform_name ){
                    $channel_platform_data_new[$channel_platform_stage] = $channel_platform_name;

                    $channel_platform_new_ower[strtolower($channel_platform_stage)] = $channel_platform_stage;
                    array_push($channel_platform_new,strtolower($channel_platform_stage));
                }
            }
        }else{
            var_dump('获取渠道平台数据失败');
            die;
        }

        $channel_platform_new = array_unique($channel_platform_new);  // 表格数据去重

        $create_data = array_diff($channel_platform_new,$channel_platform_old); // 差集 需要创建的数据
        $detele_data = array_diff($channel_platform_old,$channel_platform_new); // 差集 需要删除的数据

        foreach ($detele_data as $key => $del){
            $del_id = $channel_platform_data_old[$del];
            // 删除多余的平台渠道
            if ($del_id){
                $url = INSIGHT.'/insight-backend/gpPlatform/deleteChannelPlatform';
                $res  = (new PromotionTaskService())->requsetInsightForm($url,array('id' => $del_id));
                var_dump('删除标签：'.$del.' 返回结果'.$res);
            }
        }
        foreach ($create_data as  $key  => $create ){
            $channel_platform_stage = $channel_platform_new_ower[$create];
            $channel_platform_name = $channel_platform_data_new[$channel_platform_stage];
            // 创建渠道平台
            if ($channel_platform_stage && $channel_platform_name){
                $url = INSIGHT.'/insight-backend/gpPlatform/addChannelPlatform';
                $res  = (new PromotionTaskService())->requsetInsightForm($url,array('platform_tag'=>$channel_platform_stage,'platform_name'=>$channel_platform_name));
                var_dump('新增标签：'.$channel_platform_stage.' 名称：'.$channel_platform_name.' 返回结果'.$res);
            }
        }
        var_dump('处理完成');
        die;
    }
    /**
     *  处理渠道平台
     */
    public function disChannelMedium(){
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $url = INSIGHT.'/insight-backend/gpChannelMedium/queryChannelMediumInfo';
        $channel_platform_list = (new PromotionTaskService())->requsetInsightJson($url,array());
        $channel_medium_list = json_decode($channel_platform_list,true);
        if ($channel_medium_list && $channel_medium_list['datas']){
            foreach ($channel_medium_list['datas'] as $itme){
                $url = INSIGHT.'/insight-backend/gpChannelMedium/deleteChannelMedium';
                $res  = (new PromotionTaskService())->requsetInsightForm($url,array('id' => $itme['id']));
                var_dump('删除媒介：'.$itme['medium_name'].' 返回结果'.$res);
            }
        }else{
            var_dump('获取渠道媒介数据失败');
            die;
        }

        $PHPExcel = $PHPReader->load($filePath);
        $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
        $activeSheet = $PHPExcel->getActiveSheet();
        for ($start = 1; $start <= $allRow; $start++) {
            $channel_medium_stage = trim($activeSheet->getCell('A' . $start)->getValue());
            $channel_medium_stage = str_replace(' ','',$channel_medium_stage);
            $channel_medium_name = trim($activeSheet->getCell('B' . $start)->getValue());
            if ($channel_medium_stage && $channel_medium_name ){
                $channel_medium_stage = md5($channel_medium_stage);
                $url = INSIGHT.'/insight-backend/gpChannelMedium/addChannelMedium';
                $res  = (new PromotionTaskService())->requsetInsightForm($url,array('medium_name' => $channel_medium_name,'medium_tag'=>$channel_medium_stage));
                var_dump('新增媒介：'.$channel_medium_name.' 标签：'.$channel_medium_stage.' 返回结果'.$res);
            }
        }
        var_dump('处理完成');
        die;
    }

    public function disPromotionTask(){
        $sql = "UPDATE tb_ms_promotion_task 
                SET status_cd = 'N003600003' 
                WHERE
                    promotion_demand_no IN (
                    SELECT
                        promotion_demand_no 
                    FROM
                        `tb_ms_promotion_demand` 
                    WHERE
                        `promotion_type_cd` = 'N003610003' 
                    AND `platform_cd` != 'N002620800' 
                    )";
        M()->query($sql);
    }

    /**
     *  处理历史数据
     */
    public function disPromotionTaskPlatform(){
        $model = M();
        $sql = "SELECT
            tb_ms_promotion_demand.platform_cd,
            tb_ms_promotion_task.promotion_task_no
        FROM
            tb_ms_promotion_task
            LEFT JOIN tb_ms_promotion_demand ON tb_ms_promotion_demand.promotion_demand_no = tb_ms_promotion_task.promotion_demand_no";
       $list =  $model->query($sql);
       if ($list){
           foreach ($list as $value){
                if (!empty($value['platform_cd'])){
                    $model->table('tb_ms_promotion_task')->where(array('promotion_task_no'=>$value['promotion_task_no']))->save(array('platform_cd'=>$value['platform_cd']));
                }
           }
       }
    }

    /**
     * 重置自动派单尝试次数
     */
    public function disResetNum(){
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
        $activeSheet = $PHPExcel->getActiveSheet();
        $ms_ord_data = array();
        for ($start = 2; $start <= $allRow; $start++) {
            $ms_ord = trim($activeSheet->getCell('A' . $start)->getValue());
            if ($ms_ord){
                $res = M('ms_ord','tb_')->where(array('ORD_ID'=>$ms_ord))->save(array('reset_num'=>0));
                var_dump($res);
            }
        }
    }


    /**
     * 处理数据 订单类型 ，部分店铺拉单 B2C订单类型 异常， 修复B2C订单类型
     */
    public function disBtcOrderTypeCd(){
        header("content-type:text/html;charset=utf-8");
        $filePath = $_FILES['expe']['tmp_name'];  //导入的excel路径
        vendor("PHPExcel.PHPExcel");
        $PHPReader = new PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);
        $allRow = $PHPExcel->getSheet(0)->getHighestRow();   //取得excel总行数
        $activeSheet = $PHPExcel->getActiveSheet();
        for ($start = 2; $start <= $allRow; $start++) {
            $order_id = trim($activeSheet->getCell('A' . $start)->getValue());
            $plat_cd = trim($activeSheet->getCell('B' . $start)->getValue());
            $res = M('order_extend','tb_op_')
                ->where(array('order_id'=>$order_id, 'plat_cd'=>$plat_cd))
                ->save(array('btc_order_type_cd'=>'N003720002'));
            var_dump($res);

        }
    }
}