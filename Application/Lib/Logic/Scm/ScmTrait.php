<?php
/**
 * Created by PhpStorm.
 * User: due
 * Date: 2018/3/28
 * Time: 14:48
 */

@import('@.Model.Scm.QuotationModel');

trait ScmTrait {

    protected $legal_man = '';
    private $step_list = [
        'sell' => [//销售需求
            //'总进度'     => ['字典code', '销售初始进度', '采购初始进度'],
            'demand_submit'     => ['N002120100', '', ''],
//            'demand_approve'    => ['N002120200', 'untreated', ''],
            'purchase_claim'    => ['N002120300', 'no_need_to_treat', ''],
            'seller_choose'     => ['N002120400', 'untreated', ''],
//            'purchase_confirm'  => ['N002120500', 'no_need_to_treat', 'untreated'],
//            'seller_submit'     => ['N002120600', 'untreated', ''],
            'leader_approve'    => ['N002120700', 'untreated', 'no_need_to_treat'],
            'ceo_approve'       => ['N002120800', 'untreated', ''],
            'upload_po'         => ['N002120900', 'untreated', 'untreated'],
            'justice_approve'   => ['N002121000', 'untreated', 'untreated'],
            'create_order'      => ['N002121300', 'untreated', 'untreated'],
            'success'           => ['N002121400', '', '']
        ],
        'store' => [//热销品囤货
            //'总进度'     => ['字典code', '销售初始进度', '采购初始进度'],
            'demand_submit'     => ['N002120100', '', ''],
//            'demand_approve'    => ['N002120200', 'untreated', ''],
            'purchase_claim'    => ['N002120300', 'no_need_to_treat', ''],
            'seller_choose'     => ['N002120400', 'untreated', ''],
//            'purchase_confirm'  => ['N002120500', 'no_need_to_treat', 'untreated'],
//            'seller_submit'     => ['N002120600', 'untreated', ''],
            'leader_approve'    => ['N002120700', 'untreated', 'no_need_to_treat'],
            'ceo_approve'       => ['N002120800', 'untreated', ''],
            'upload_po'         => ['N002120900', 'pass_through', 'untreated'],
            'justice_approve'   => ['N002121000', 'pass_through', 'untreated'],
            'create_order'      => ['N002121300', 'untreated', 'untreated'],
            'success'           => ['N002121400', '', '']
        ],
        'stock' => [//全现货
            //'总进度'     => ['字典code', '销售初始进度'],
            'demand_submit'     => ['N002120100', ''],
            'leader_approve'    => ['N002120700', 'untreated'],
            'ceo_approve'       => ['N002120800', 'untreated'],
            'upload_po'         => ['N002120900', 'untreated'],
            'justice_approve'   => ['N002121000', 'untreated'],
            'create_order'      => ['N002121300', 'untreated'],
            'success'           => ['N002121400', '']
        ]
    ];

    /**处理po上传、归档
     * @param $data
     * @return bool
     */
    public function handlePO($data)
    {
       
        $model = D(ucfirst($data['model']));
        $model->startTrans();
        $model_data = $model->lock(true)->where(['id' => $data['id']])->find();
        //检查操作权限
        if ($data['model'] == 'demand') {
            $permit = $this->stepCheck($model_data['step'], $model_data['status'], D('Scm/Quotation')->getStatusArr($model_data['id']));
            if ($data['step'] == 'upload_po') {
                if (empty($data['po_date']) || ($data['has_3rd_po'] == 1 && empty($data['third_po_no']))) {
                    $this->error = 'po时间和第三方po单号必填';
                    return false;
                }
                $model_data['has_3rd_po'] = $data['has_3rd_po'];
                $model_data['third_po_no'] = $data['third_po_no'];
                $model_data['po_date'] = $data['po_date'];
            }
            $status = DemandModel::$status;
        } else {
            $permit = $this->stepCheck($model_data);
            if ($data['step'] == 'upload_po') {
                if ($model_data['purchase_type'] == QuotationModel::$type_online && empty($data['pur_account'])){
                    $this->error = '下单账号必填';
                    return false;
                }
                if (empty($data['pur_order_no'])) {
                    $this->error = '第三方PO单号必填';
                    return false;
                }
                $model_data['pur_account'] = $data['pur_account'];
                $model_data['pur_order_no'] = $data['pur_order_no'];
            }
            $status = QuotationModel::$status;
        }
        if (!$permit) {
            return false;
        }
        $data['po'] = json_encode($data['po'], JSON_UNESCAPED_UNICODE);
        $check_flg = false;
     
        switch ($data['step']) {
            case 'upload_po':
                $model_data['status'] = $status['pass_through'];
                $model_data['po'] = $data['po'];
               
                $data['model'] == 'demand' ? ScmEmailModel::L1($model_data) : '';
              
                $check_flg = true;
                break;
            case 'reupload_po':
                $model_data['status'] = $status['untreated'];
                $model_data['po'] = $data['po'];
                //邮件
                
                $data['model'] == 'demand' ? ScmEmailModel::L1($model_data) : ScmEmailModel::L2($model_data);
                break;
            case 'po_archive':
//                $model_data['status'] = $data['status'] ? $status['pass_through'] : $status['not_pass_through'];
//                $model_data['reason'] = $data['status'] ? '' : $data['reason'];
//                $check_flg = $data['status'] == 1;
                $model_data['po_archive'] = $data['po'];
//                //邮件
//                if (!$check_flg) {
//                    $data['model'] == 'demand' ? ScmEmailModel::Q1($model_data) : ScmEmailModel::Q2($model_data);
//                }
                break;
            case 'po_rearchive':
                $model_data['po_archive'] = $data['po'];
                break;
        }
       
        //检查当阶段是否全部完成，修改主状态
        if ($model->save($model_data) !== false) {
            if (!$check_flg || $this->checkAllOver(['step' => $data['step'], 'type' => $data['model'], 'id' => $data['id']])) {
                $model->commit();
                $this->code = 2000;
                return true;
            } else {
                $model->rollback();
                return false;
            }
        } else {
            $this->error = $model->getError() ?: '提交失败';
            $model->rollback();
            return false;
        }

    }

    /**检查当前step是否全部完成
     * @param $data [type, id, step] exp. ['quotation', 299, 'leader_approve']
     * @return bool
     */
    public function checkAllOver($data) {
        $step = QuotationModel::$step[$data['step']];
        $d_quota = D('Scm/Quotation');
        if ($data['type'] == 'quotation') {
            if ($data['step'] != 'purchase_confirm') {//采购确认申请修改推到下一步处理，无需清空
                $d_quota->where(['id'=>$data['id']])->save(['reason'=>'']);
            }
            $quotation = $d_quota->field('demand_id,id,request_advance,status')->where(['id'=>$data['id']])->find();
            $demand_id = $quotation['demand_id'];
        } else {
            D('Scm/Demand')->where(['id'=>$data['id']])->save(['reason'=>'']);
            $demand_id = $data['id'];
        }
        //保存法务审批人
        if ($this->legal_man) {
            D(ucfirst($data['type']))->where(['id' => $data['id']])->save(['legal_man' => $this->legal_man]);
        }
        $d_s_list = DemandModel::$status;
        $q_s_list = QuotationModel::$status;
        $q_c_list = QuotationModel::$chosen;
        $check_list = [
            //主需求进度 => [主需求状态, 采购状态, 中标状态]
            'demand_submit' => [$d_s_list['pass_through'], null, null],
            'demand_approve' => [$d_s_list['pass_through'], null, null],
            'purchase_claim' => [$d_s_list['no_need_to_treat'], $q_s_list['pass_through'], $q_c_list['to_chosen']],
            'seller_choose' => [
                $d_s_list['pass_through'],
                $q_s_list['no_need_to_treat'],
                [$q_c_list['chosen'], $q_c_list['not_chosen']]
            ],
            'purchase_confirm' => [
                $d_s_list['no_need_to_treat'],
                [$q_s_list['pass_through'], $q_s_list['apply_for_modification'], $q_s_list['discarded']],
                [$q_c_list['chosen']]//已落选不影响——2018.05.07 due
            ],
            'seller_submit' => [$d_s_list['pass_through'], $q_s_list['pass_through'], $q_c_list['chosen']],
            'leader_approve' => [$d_s_list['pass_through'], $q_s_list['no_need_to_treat'], $q_c_list['chosen']],
            'ceo_approve' => [$d_s_list['pass_through'], $q_s_list['no_need_to_treat'], $q_c_list['chosen']],
            'upload_po' => [$d_s_list['pass_through'], $q_s_list['pass_through'], $q_c_list['chosen']],
            'justice_approve' => [$d_s_list['pass_through'], $q_s_list['pass_through'], $q_c_list['chosen']],
            'create_order' => [$d_s_list['pass_through'], $q_s_list['pass_through'], $q_c_list['chosen']],
        ];
        list($d_status, $q_status, $q_chosen) = $check_list[$data['step']];
        $this->data[$data['type']] = $data['id'];
        $this->data['check_list.' . $data['step']] = $check_list[$data['step']];
        //如果报价申请提前
        if($data['type'] == 'quotation' && $quotation['request_advance'] == 2) {
            if(is_array($q_status) ? !in_array($quotation['status'],$q_status) : ($quotation['status'] != $q_status)) return true;
            if(!$this->quotationDone($data['id'], $data['step'])) return false;
            $demand_step = D('Scm/Demand')->where(['id'=>$demand_id])->getField('step');
            if($demand_step != $step) {
                $this->addStepLog();
                return true;
            }
        }
        //任一不为完成状态，则不推进
        $exists = D('Scm/Demand')->field('id')
            ->lock(true)
            ->where([
                'id'        => $demand_id,
                'step'      => $step,
                'status'    => (is_array($d_status) ? ['not in', $d_status] : ['neq', $d_status])])
            ->find();
        $this->data['untreated_sql1'] = D()->getLastSql();
        $exists = $exists || D('Scm/Quotation')->field('id')
            ->lock(true)
            ->where([
                'demand_id' => $demand_id,
                'chosen'    => (is_array($q_chosen) ? ['in', $q_chosen] : $q_chosen),
                'step'      => $step,
                'invalid'   => 0,
                'status'    => (is_array($q_status) ? ['not in', $q_status] : ['neq', $q_status])
            ])
            ->find();
        $this->data['untreated_sql2'] = D()->getLastSql();
        $this->data['untreated'] = $exists;
        if (!$exists) {
            return $this->stepDone($demand_id, $data['step']);
        }
        $this->addStepLog();
        return true;
    }

    /**总进度推进
     * @param $demand_id
     * @param string $step
     * @param string $step_type 步骤类型，stock全现货，sell销售订单，store热销品囤货
     * @return bool
     */
    public function stepDone($demand_id, $step = '', $step_type = 'sell')
    {
        $step_code = QuotationModel::$step[$step];
        if (!$step) return false;
        //判断全是现货
        $demand = D('Scm/Demand')->field('id,demand_code,customer_charter_no,demand_type,is_spot,seller,sell_team,step,b2b_order_id,create_user')->where(['id' => $demand_id])->find();
        $q_list = D('Scm/Quotation')->alias('t')
            ->where(['demand_id' => $demand_id, 'chosen' => QuotationModel::$chosen['chosen'], 'invalid' => 0,'step' => $step_code])
            ->field('id,quotation_code,purchase_type,purchaser,purchase_id,purchase_team,c.ETC3 as legal_man,t.create_user')
            ->join('left join tb_ms_cmn_cd c on c.CD=t.purchase_team')
            ->select();
        if ($demand['is_spot'] == 1) {
            $step_type = 'stock';
        } elseif ($demand['demand_type'] == DemandModel::$demand_type_store) {
            $step_type = 'store';
        }
        $this->data['step_type'] = $step_type;
        $step_list = &$this->step_list[$step_type];
        //美国团队销售领导自动通过
        if($demand['sell_team'] == 'N001283100') {
            unset($step_list['leader_approve']);
        }
        foreach ($step_list as $k => $v) {
            if ($k == $step) {
                list($next_step, $d_status, $q_status) = current($step_list);
                break;
            }
        }
        if (!$next_step) return false;
        $next_step_name = DemandModel::getStepNm($next_step);
        $d_data = ['id' => $demand_id, 'step' => $next_step];
        if ($d_status) {
            $d_data['last_status'] = '';
            $d_data['status'] = DemandModel::$status[$d_status];
        }
        $this->data['d_data'] = $d_data;
        $d_flg = D('Scm/Demand')->save($d_data);
        $q_data['step'] = $next_step;
        $q_where = ['demand_id' => $demand_id, 'invalid' => 0,'step'=>$step_code];
        if ($q_status) {
            $q_data['status'] = QuotationModel::$status[$q_status];
        }
        //FIXDUE 特别：采购确认与销售领导审批环节重置已落选报价状态
        switch ($next_step_name) {
            case 'leader_approve':
                $q_where['chosen'] = ['in', [QuotationModel::$chosen['chosen'], QuotationModel::$chosen['not_chosen']]];
                break;
            case 'seller_choose':
                break;
            default:
                $q_where['chosen'] = QuotationModel::$chosen['chosen'];
                break;
        }
        $q_flg = D('Scm/Quotation')->where($q_where)->save($q_data);
        $this->data['q_status'] = $q_status;
        $this->data['d_push']   = $d_flg;
        $this->data['q_push']   = $q_flg;
        $q_flg = $q_flg !== false;
        //邮件
        if ($d_flg && $q_flg) {
            $this->data['q_email_count'] = count($q_list);
            //高ce(退税后)&全线上采购ceo自动通过
            if ($next_step_name == 'ceo_approve') {
                $test_arr = array_filter($q_list, function($v) {
                    return $v['purchase_type'] != QuotationModel::$type_online;
                });
                $ce = M('demand_profit','tb_sell_')->field('commodity_cost,ce_level_after_tax_refund as ce,net_profit,net_profit_total,net_profit_spot,average_receivables_efficiency as avg_days')->where(['demand_id'=>$demand_id])->find();
                $this->data['ceo_autopass']['ce'] = $ce;
                //美国团队自动通过
                $profit_map = ['net_profit_total', 'net_profit_spot', 'net_profit'];
                $usa_flg = $demand['sell_team'] == 'N001283100' && $ce['commodity_cost'] < 50000 && $ce[$profit_map[$demand['is_spot']]] >= 0.005;//美国团队，采购金额<50000，对应净利润>0
                $this->data['ceo_autopass']['usa_flg'] = $usa_flg;
                //有现货，现货&在途部分 净利润 + 采购部分 净利润 > 0，或为部分sku  2019.3.13 8880 更新
                $spot_flg = $demand['is_spot'] == 2 || $ce['net_profit_spot'] + $ce['net_profit'] > 0;
                if ($demand['is_spot'] == 1 && $demand['demand_type'] == DemandModel::$demand_type_sell) {
                    $pass_skus = M('config','tb_sell_')->where(['name' => 'ceo_pass_sku'])->getField('value');
                    $demand_skus = D('Scm/DemandGoods')->where(['demand_id' => $demand_id])->getField('sku_id', true);
                    $sku_flg = !empty($demand_skus);
                    foreach ($demand_skus as $v) {
                        if (strpos($pass_skus, trim($v)) === false) {
                            $sku_flg = false;
                            break;
                        }
                    }
                    $this->data['ceo_autopass']['sku_flg'] = $sku_flg;
                    $spot_flg = $spot_flg || $sku_flg;
                }
                $this->data['ceo_autopass']['spot_flg'] = $spot_flg;
                //有采购，必须全线上采购或ce为S,A,B
                $pur_flg = $demand['is_spot'] == 1 || in_array(trim($ce['ce']), ['S', 'A', 'B']) || empty($test_arr);
                $this->data['ceo_autopass']['pur_flg'] = $pur_flg;
                //销售部分，客户三月平均收款天数A，B，C，D或无记录或为限定公司
                $sell_flg = !$ce['avg_days'] || in_array(trim($ce['avg_days']), ['A', 'B', 'C', 'D']) || M('config','tb_sell_')->where("find_in_set('{$demand['customer_charter_no']}',value)")->find();
                $this->data['ceo_autopass']['sell_flg'] = $sell_flg;
                $this->data['ceo_autopass']['sell_flg_sql'] = M()->_sql();
                $pass_flg = $usa_flg || ($spot_flg && $pur_flg && $sell_flg);
                $this->data['ceo_autopass']['pass_flg'] = $pass_flg;
                if ($pass_flg && $ce['commodity_cost'] < 100000)  {
                    D('Scm/Demand')->save(['id' => $demand_id, 'status' => DemandModel::$status['pass_through']]);
                    $this->data['ceo_online_auto_pass'] = 1;
                    if(!$this->checkAllOver(['step'=>$next_step_name,'type'=>'demand','id'=>$demand_id])) return false;
                    $next_step_name = '';
                }
            }
            //线上采购自动通过
            if (in_array($next_step_name, ['justice_approve', 'justice_stamp', 'po_archive'])) {
                $pass_num = D('Scm/Quotation')->where([
                        'demand_id' => $demand_id,
                        'chosen' => QuotationModel::$chosen['chosen'],
                        'invalid' => 0,
                        'step' => $next_step,
                        'purchase_type' => QuotationModel::$type_online
                    ])
                    ->save(['status' => QuotationModel::$status['pass_through']]);
                $this->data['q_online_auto_pass'] = $pass_num;
                if ($pass_num > 0) {
                    if ($pass_num == count($q_list)) {
                        if(!$this->checkAllOver(['step'=>$next_step_name,'type'=>'demand','id'=>$demand_id])) return false;
                        $q_list = [];
                    }
                    $q_list = array_filter($q_list, function($v) {
                        return $v['purchase_type'] != QuotationModel::$type_online;
                    });
                }
            }
            switch ($next_step_name) {
                case 'purchase_claim':
                    ScmEmailModel::C($demand);
                    break;
                case 'purchase_confirm':
                    ScmEmailModel::E($q_list);
                    break;
                case 'seller_submit':
                    ScmEmailModel::F($demand);
                    break;
                case 'leader_approve':
                    ScmEmailModel::G($demand);
                    break;
                case 'ceo_approve':
                    ScmEmailModel::I($demand);
                    break;
                case 'upload_po':
                    ScmEmailModel::J($demand, $q_list);
                    break;
                case 'justice_approve':
                    ScmEmailModel::L($demand, $q_list);
                    break;
                case 'justice_stamp'://#7897 删除邮件N 2018-07-10
                    break;
                case 'po_archive':
                    ScmEmailModel::P($demand, $q_list);
                    break;
//                case 'create_order':
//                    ScmEmailModel::R($demand);
//                    break;
                case 'success':
                    ScmEmailModel::S($demand, $q_list);
                    break;
                default:
                    break;
            }
        }
        //记录日志
        $this->addStepLog();
        if($next_step_name == 'create_order') {
            if(!D('Scm/Demand','Logic')->saveDealDetail($demand_id)) {
                $this->error = D('Scm/Demand','Logic')->getError();
                return false;
            }
            if(!D('Scm/Demand','Logic')->createOrders($demand_id)) {
                $this->error = D('Scm/Demand','Logic')->getError();
                return false;
            }
        }
        return $d_flg && $q_flg;
    }

    public function quotationDone($id, $step, $step_type = 'sell') {
        if (!$step) return false;
        $quotation = D('Scm/Quotation')->alias('t')->where(['id' => $id])->find();
        //判断全是现货
        $demand = D('Scm/Demand')->field('id,demand_code,customer_charter_no,demand_type,is_spot,seller,sell_team,step,b2b_order_id,create_user')->where(['id' => $quotation['demand_id']])->find();
        if ($demand['is_spot'] == 1) {
            $step_type = 'stock';
        } elseif ($demand['demand_type'] == DemandModel::$demand_type_store) {
            $step_type = 'store';
        }

        $this->data['step_type'] = $step_type;
        $step_list = &$this->step_list[$step_type];
        foreach ($step_list as $k => $v) {
            if ($k == $step) {
                list($next_step, $d_status, $q_status) = current($step_list);
                break;
            }
        }
        if (!$next_step) return false;
        $next_step_name = DemandModel::getStepNm($next_step);
        $q_save = ['step'=>$next_step];
        if ($q_status) {
            $q_save['status'] = QuotationModel::$status[$q_status];
        }
        if (in_array($next_step_name, ['justice_approve', 'justice_stamp', 'po_archive']) && $quotation['purchase_type'] == QuotationModel::$type_online) {
            $q_save['status'] = QuotationModel::$status['pass_through'];
        }
        $q_flg = D('Scm/Quotation')
            ->where(['id' => $id])
            ->save($q_save);
        $this->data['q_status'] = $q_status;
        $this->data['q_push']   = $q_flg;
        $this->data['q_email_count'] = 1;
        if($q_flg === false) return false;
        switch ($next_step_name) {
            case 'justice_approve':
                ScmEmailModel::L2($quotation);
                break;
            case 'po_archive':
                ScmEmailModel::P2($quotation);
                break;
//            case 'create_order':
//                ScmEmailModel::R($demand);
//                break;
            case 'success':
                ScmEmailModel::S2($quotation);
                break;
            default:
                break;
        }
        if($next_step_name == 'create_order') {
            if(!D('Scm/Quotation')->createOrder($quotation)) {
                $this->error = D('Scm/Quotation')->getError();
                return false;
            }
        }
        if(!$this->checkAllOver(['step' =>$next_step_name, 'type' => 'quotation', 'id' => $id])) return false;
        return true;
    }

    /**检查法务审批人权限
     *
     * 销售团队的法务负责人是N00128开头的Code码，对应的Comment3
     * 采购团队的法务负责人是N00129开头的Code码，对应的Comment3
     * @param $cd //采购团队code或销售团队code
     * @return bool
     */
    public function justiceCheck($cd)
    {
        $actions_list = [
            'demand_justice_approve',
            'demand_justice_stamp',
            'demand_po_archive',
            'demand_po_rearchive',
            'quotation_justice_approve',
            'quotation_justice_stamp',
            'quotation_po_archive',
            'quotation_po_rearchive'
        ];
        if (!in_array(ACTION_NAME, $actions_list)) return true;
        $allow_user = TbMsCmnCdModel::getLegalMan($cd);
        $check_ret = $allow_user == $_SESSION['m_loginname'];
        $this->legal_man = $allow_user;
        return true;//#7897 操作权限全部改为权限管理角色配置项，不再通过数据字典配置判断
        $this->error = $check_ret ? '' : '没有法务权限';
        return $check_ret;
    }

    /**详情中的cd转换为val
     * @param array $data
     * @param string $type
     */
    public function cd2Val(&$data = [], $type = '')
    {

        //收货城市
        $site_m = D('TbCrmSite');
        switch ($type) {
            case 'quotation':
                $data['currency_val'] = cdVal($data['currency']);
                $data['purchase_type_val'] = cdVal($data['purchase_type']);
                $data['pur_website_val'] = cdVal($data['pur_website']);
                $data['delivery_type_val'] = cdVal($data['delivery_type']);
                $data['our_company_val'] = cdVal($data['our_company']);
                $data['invoice_type_val'] = cdVal($data['invoice_type']);
                $data['tax_rate_val'] = cdVal($data['tax_rate']);
                $data['payment_cycle_type_val'] = cdVal($data['payment_cycle_type']);
                $data['payment_cycle_val'] = cdVal($data['payment_cycle']);
                $data['expense_currency_val'] = cdVal($data['expense_currency']);
                $data['purchase_team_val'] = cdVal($data['purchase_team']);
                $data['ship_type_val'] = cdVal($data['ship_type']);
                $data['warehouse_val'] = cdVal($data['warehouse']);
                $data['chosen_val'] = cdVal($data['chosen']);
                $data['status_val'] = cdVal($data['status']);
                $data['reason_val'] = cdVal($data['reason']);
                $data['has_contract_val'] = $data['has_contract'] ? L('有') : L('无');
                $data['payment_time_val'] = [];
                $data['source_country_val']    = $site_m->siteName($data['source_country']);
                $data['ship_time'] = $data['ship_time'] == '0000-00-00' ? '' : $data['ship_time'];
                $data['arrive_time'] = $data['arrive_time'] == '0000-00-00' ? '' : $data['arrive_time'];
                $data['drawback_time'] = $data['drawback_time'] == '0000-00-00' ? '' : $data['drawback_time'];
                $data['step_val'] = cdVal($data['step']);
                if ($payment_time = $data['payment_time']) {
                    foreach ($payment_time as $v) {
                        $val = '';
                        $val .= $v['node'] ? L(cdVal($v['node'])) . '，' : '';
                        $val .= $v['days'] ? cdVal($v['days']) . L(cdVal($v['day_type'])) . '，' : '';
                        $val .= $v['percent'] ? $v['percent'] . '%，': '';
                        $val .= $v['date'] ? L('预计') . $v['date'] : '';
                        $data['payment_time_val'][] = $val;
                    }
                }
                break;
            case 'quotation_goods':
                $data = SkuModel::getInfo($data,'sku_id',['spu_name','image_url','attributes'],['spu_name'=>'goods_name','image_url'=>'guds_img_cdn_addr','attributes'=>'sku_attribute']);
                foreach ($data as &$v) {
                    $v['drawback_percent_val'] = cdVal($v['drawback_percent']);
                    $v['auth_and_link_val'] = cdVal($v['auth_and_link']);
                    if ($v['auth_and_link_val']) {
                        L($v['auth_and_link_val']);
                    }
                }
                break;
            case 'demand':
                $data['receive_country_val']    = $site_m->siteName($data['receive_country']);
                $data['receive_province_val']   = $site_m->siteName($data['receive_province']);
                $data['receive_city_val']       = $site_m->siteName($data['receive_city']);
                $data['demand_type_val'] = cdVal($data['demand_type']);
                $data['our_company_val'] = cdVal($data['our_company']);
                $data['business_mode_val'] = cdVal($data['business_mode']);
                $data['receive_mode_val'] = cdVal($data['receive_mode']);
                $data['need_warehouse_val'] = $data['need_warehouse'] == 2 ? '' : ($data['need_warehouse'] == 0 ? L('直接发给客户') : L('先入我方仓库，再发给客户'));
                $data['sell_currency_val'] = cdVal($data['sell_currency']);
                $data['collection_cycle_val'] = cdVal($data['collection_cycle']);
                $data['invoice_type_val'] = cdVal($data['invoice_type']);
                $data['tax_rate_val'] = cdVal($data['tax_rate']);
                $data['expense_currency_val'] = cdVal($data['expense_currency']);
                $data['expense_currency_spot_val'] = cdVal($data['expense_currency_spot']);
                $data['tax_currency_val'] = cdVal($data['tax_currency']);
                $data['tax_currency_spot_val'] = cdVal($data['tax_currency_spot']);
                $data['other_income_currency_val'] = cdVal($data['other_income_currency']);
                $data['sell_team_val'] = cdVal($data['sell_team']);
                $data['order_source_val'] = cdVal($data['order_source']);
                $data['step_val'] = cdVal($data['step']);
                $data['status_val'] = cdVal($data['status']);
                $data['reason_val'] = cdVal($data['reason']);
                $data['collection_time_val'] = [];
                $data['ship_date'] = $data['ship_date'] == '0000-00-00' ? '' : $data['ship_date'];
                $data['deadline'] = $data['deadline'] == '0000-00-00' ? '' : $data['deadline'];
                if ($collection_time = $data['collection_time']) {
                    if ($data['demand_type'] == DemandModel::$demand_type_store) {
                        $data['collection_time_val'][] = $collection_time[0]['date'] ?: '';
                    } else {
                        foreach ($collection_time as $v) {
                            $val = '';
                            $val .= $v['node'] ? L(cdVal($v['node'])) . '，' : '';
                            $val .= $v['days'] ? cdVal($v['days']) . L(cdVal($v['day_type'])) . '，' : '';
                            $val .= $v['percent'] ? $v['percent'] . '%，': '';
                            $val .= $v['date'] ? L('预计') . $v['date'] : '';
                            $data['collection_time_val'][] = $val;
                        }
                    }

                }
                break;
            case 'demand_goods':
                $data = SkuModel::getInfo($data,'sku_id',['spu_name','image_url','attributes'],['spu_name'=>'goods_name','image_url'=>'guds_img_cdn_addr','attributes'=>'sku_attribute']);
                foreach ($data as &$v) {
                    $v['spot_batch']        = json_decode($v['spot_batch']);
                    $v['spot_warehouse']    = json_decode($v['spot_warehouse']);
                    $v['on_way_batch']      = json_decode($v['on_way_batch']);
                    $v['on_way_warehouse']  = json_decode($v['on_way_warehouse']);
                    $v['auth_and_link_val'] = cdVal($v['auth_and_link']);
                    if ($v['auth_and_link_val']) {
                        L($v['auth_and_link_val']);
                    }
                }
                break;
            default:
                break;
        }
    }

    /**操作日志详情
     * @param $param
     * @return bool
     */
    public function logDetail($param)
    {
        if ($param['detail_type'] == 'demand') {
            $demand_id = D('ActionLog')->where(['id' => $param['id']])->getField('demand_id');

            $detail_list = M('DemandHistory', 'tb_sell_')
                ->where(['log_id' => ['elt', $param['id']], 'demand_id' => $demand_id])
                ->order('id desc')
                ->limit(2)
                ->select();
            if (!$detail_list) {
                $this->error = '日志详情不存在';
                return false;
            }
            foreach ($detail_list as &$v) {
                $v['goods'] = M('DemandGoodsHistory', 'tb_sell_')->where(['demand_history_id' => $v['id']])->select();
                $v['collection_time'] = json_decode($v['collection_time'], true);
                $v['contract'] = $v['is_purchase_only'] ? L('单采合作无合同') : $v['contract'];
                $v['attachment'] = json_decode($v['attachment'], true);
                $v['po'] = json_decode($v['po'], true);
                $v['po_with_watermark'] = json_decode($v['po_with_watermark'], true);
                $v['po_archive'] = json_decode($v['po_archive'], true);
                $v['legal_man'] = $v['legal_man'] ?: TbMsCmnCdModel::getLegalMan($v['sell_team']);
                $this->cd2Val($v, 'demand');
                $this->cd2Val($v['goods'], 'demand_goods');
                $v['goods_val'] = [];
                foreach ($v['goods'] as &$vv) {
                    $v['goods_val'][] = "{$vv['search_id']} - {$vv['require_number']} - {$vv['sell_price']} - {$vv['purchase_number']} - {$vv['auth_and_link_val']} - {$vv['spot_number']}";
                }
            }
            unset($v);
        } else {
            $quotation_id = D('ActionLog')->where(['id' => $param['id']])->getField('quotation_id');
            $detail_list = M('QuotationHistory', 'tb_sell_')
                ->where(['log_id' => ['elt', $param['id']], 'quotation_id' => $quotation_id])
                ->order('id desc')
                ->limit(2)
                ->select();
            if (!$detail_list) {
                $this->error = '日志详情不存在';
                return false;
            }
            foreach ($detail_list as &$v) {
                $v['goods'] = M('QuotationGoodsHistory', 'tb_sell_')->where(['quotation_history_id' => $v['id']])->select();
                $v['payment_time'] = json_decode($v['payment_time'], true);
                $v['attachment'] = json_decode($v['attachment'], true);
                $v['po'] = json_decode($v['po'], true);
                $v['po_with_watermark'] = json_decode($v['po_with_watermark'], true);
                $v['po_archive'] = json_decode($v['po_archive'], true);
                $v['legal_man'] = $v['legal_man'] ?: TbMsCmnCdModel::getLegalMan($v['purchase_team']);
                $this->cd2Val($v, 'quotation');
                $this->cd2Val($v['goods'], 'quotation_goods');
                $v['goods_val'] = [];
                foreach ($v['goods'] as &$vv) {
                    $v['goods_val'][] = "{$vv['search_id']} - {$vv['sell_price']} - {$vv['purchase_number']} - {$vv['supply_number']} - {$vv['purchase_price']} - {$vv['drawback_percent_val']} - {$vv['auth_and_link_val']}";
                }
            }
            unset($v);
        }
        $this->code = 2000;
        $this->data = ['now' => array_shift($detail_list), 'old' => array_shift($detail_list)];
        return true;
    }

    private function addStepLog()
    {
        $filePath = '/opt/logs/logstash/scm/';
        if (!is_dir($filePath)) {
            mkdir($filePath, 0777, true);
        }
        chmod($filePath, 0777);
        $fileName = 'checkover_' . date('Ymd') . '.log';
        $logContent = '------------Log start(Step Log)------------' . date('Y-m-d H:i:s') . PHP_EOL;
        $logContent .= json_encode($this->data, JSON_UNESCAPED_UNICODE);
        $logContent .= PHP_EOL . '------------------Log end------------------' . PHP_EOL . PHP_EOL;
        file_put_contents($filePath . $fileName, $logContent, FILE_APPEND);
    }
}