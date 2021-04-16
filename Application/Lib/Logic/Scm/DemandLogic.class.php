<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/7
 * Time: 11:15
 */
require_once APP_PATH . 'Lib/Logic/Scm/ScmBaseLogic.class.php';
require_once 'ScmTrait.php';

@import("@.Model.Scm.DemandModel");
@import("@.Model.Scm.ScmEmailModel");

class DemandLogic extends ScmBaseLogic
{
    use ScmTrait;

    static $process_application_method = [
        'approve'     => 'N002340100', //同意
        'discard'     => 'N002340200', //直接弃单
        'not_approve' => 'N002340300', //不同意
    ];

    static $batch_type = [
        'all'         => '',
        'batch'       => 'N002440100',
        'on_way'      => 'N002440200',
        'allo_on_way' => 'N002410200',
    ];

    static $process_application_approve_to = [
        //'edit_demand'       => 'N002320100', //修改需求
        //'choose_quotation'  => 'N002320200', //重新选择报价
        'edit_quotation' => 'N002320200', //允许所有采购修改报价
    ];

    /**
     * @var array 方法是否能够调用校验数组 0.不需要校验
     */
    static $action_step_check = [
        'demand_save'             => [
            'demand_submit'  => [[0, 0]],
            'purchase_claim' => [[0, 0]],
            'seller_choose'  => [[0, 0]],
        ],
        'release_spot'            => [
            'demand_submit' => [[['draft'], 0]]
        ],
        'return_to_draft'         => [
            'demand_approve'  => [[['not_pass_through', 'discarded'], 0]],
            'purchase_claim'  => [[0, 0]],
            'seller_choose'   => [[0, 0]],
            'seller_submit'   => [[0, 0]],
            'leader_approve'  => [[0, 0]],
            'ceo_approve'     => [[0, 0]],
            'upload_po'       => [[0, 0]],
            'justice_approve' => [[['not_pass_through', 'discarded'], 0]],
            'justice_stamp'   => [[['not_pass_through', 'discarded'], 0]],
            'po_archive'      => [[['not_pass_through', 'discarded'], 0]],
            'create_order'    => [[0, 0]] //此状态需额外判断订单是否已经有实际操作
        ],
        'return_to_seller_choose' => [
            'seller_submit'   => [[0, 0]],
            'leader_approve'  => [[['not_pass_through', 'discarded'], 0]],
            'ceo_approve'     => [[['not_pass_through', 'discarded'], 0]],
            'upload_po'       => [[['untreated', 'discarded'], 0]],
            'justice_approve' => [[['not_pass_through', 'discarded'], 0]],
            'justice_stamp'   => [[['not_pass_through', 'discarded'], 0]],
            'po_archive'      => [[['not_pass_through', 'discarded'], 0]],
            'create_order'    => [[0, 0]] //此状态需额外判断订单是否已经有实际操作
        ],
        'demand_submit'           => [
            'demand_submit' => [[['draft'], 0]]
        ],
        'demand_approve'          => [
            'demand_approve' => [[['untreated'], 0]]
        ],
        'seller_leader_approve'   => [
            'leader_approve' => [[['untreated'], 0]]
        ],
        'ceo_approve'             => [
            'ceo_approve' => [[['untreated'], 0]]
        ],
        'scm_ceo_approve'         => [//邮件审批使用2018-08-08 due
            'ceo_approve' => [[['untreated'], 0]]
        ],
        'demand_discard'          => [
            'demand_approve'  => [[['not_pass_through'], 0]],
            'purchase_claim'  => [[0, 0]],
            'seller_choose'   => [[0, 0]],
            'seller_submit'   => [[0, 0]],
            'leader_approve'  => [[['not_pass_through'], 0]],
            'ceo_approve'     => [[['not_pass_through'], 0]],
            'upload_po'       => [[['untreated'], 0]],
            'justice_approve' => [[['not_pass_through'], 0]],
            'justice_stamp'   => [[['not_pass_through'], 0]],
            'po_archive'      => [[['not_pass_through'], 0]],
            'create_order'    => [[0, 0]] //此状态需额外判断订单是否已经有实际操作
        ],
        'demand_delete'           => [
            'demand_submit' => [[['draft'], 0]]
        ],
        'choose_quotation'        => [
            'seller_choose' => [[['untreated'], 0]]
        ],
        'demand_quotation_submit' => [
            'seller_submit' => [[['untreated'], 0]]
        ],
        'process_application'     => [
            'seller_submit'   => [[['apply_for_modification'], 0]],
            'leader_approve'  => [[['apply_for_modification'], 0]],
            'ceo_approve'     => [[['apply_for_modification'], 0]],
            'upload_po'       => [[['apply_for_modification'], 0]],
            'justice_approve' => [[['apply_for_modification'], 0]],
            'justice_stamp'   => [[['apply_for_modification'], 0]],
            'po_archive'      => [[['apply_for_modification'], 0]],
            'create_order'    => [[['apply_for_modification'], 0]],
        ],
        'demand_upload_po'        => [
            'upload_po' => [[['untreated'], 0]]
        ],
        'demand_justice_approve'  => [
            'justice_approve' => [[['untreated'], 0]]
        ],
        'demand_reupload_po'      => [
            'justice_approve' => [[['untreated'], 0]]
        ],
//        'demand_justice_stamp' => [
//            'justice_stamp' => [[['untreated'], 0]]
//        ],
//        'demand_return_stamp' => [
//            'justice_stamp' => [[['not_pass_through'], 0]]
//        ],
        'demand_po_archive'       => [
            'justice_approve' => [[['untreated'], 0]],
            'success'         => [[0, 0]]
        ],
        'demand_po_rearchive'     => [
            'create_order' => [[['untreated'], ['untreated', 'pass_through']]]
        ],
        'create_order'            => [
            'create_order' => [[['untreated'], ['untreated']]]
        ],
    ];

    /**检查销售领导权限
     * @param $step
     * @param $sell_team
     * @return bool
     */
    public function leaderCheck($step, $sell_team)
    {
        return true;
        if ($step == DemandModel::$step['demand_approve'] || $step == DemandModel::$step['leader_approve']) {
            $leaders     = ScmEmailModel::getLeaderEmail($sell_team);
            $check_ret   = in_array(strtolower($_SESSION['m_loginname'] . '@gshopper.com'), $leaders);
            $this->error = $check_ret ? '' : '没有审批权限';
            return $check_ret;
        }
        return true;
    }

    /**
     * 校验当前接口是否能够调用
     * @param string $step 当前步骤
     * @param string $demand_status 需求状态
     * @param array $chosen_quotation_status 被选中报价状态 [['status'=>'N...'],['status'=>'N....']]
     * @return bool
     */
    public function stepCheck($step, $demand_status = '', $chosen_quotation_status = [])
    {
        //如果配置的数组中没有当前action默认通过
        if (!$step_status_arr = self::$action_step_check[ACTION_NAME]) {
            return true;
        }
        $step_arr = array_flip(DemandModel::$step);
        $step     = $step_arr[$step];
        if ($status_arr = $step_status_arr[$step]) {
            foreach ($status_arr as $val) {
                if ($val[0] === 0) {
                    $demand_check = true;
                } else {
                    $demand_status_arr = array_flip(DemandModel::$status);
                    $demand_status     = $demand_status_arr[$demand_status];
                    $demand_check      = in_array($demand_status, $val[0]) ? true : false;
                }
                if ($val[1] === 0) {
                    $quotation_check = true;
                } else {
                    $quotation_status_arr = array_flip(QuotationModel::$status);
                    $has_fail             = false;
                    foreach ($chosen_quotation_status as $v) {
                        $quotation_status = $quotation_status_arr[$v['status']];
                        if ($v['step'] == $step && !in_array($quotation_status, $val[1])) {
                            $has_fail = true;
                            break;
                        }
                    }
                    $quotation_check = $has_fail ? false : true;
                }
                if ($demand_check && $quotation_check) break;
            }
            if ($demand_check && $quotation_check) return true;
        }
        $this->error = '流程状态已更新，请刷新页面';
        return false;
    }

    public function saveDemand($demand)
    {
        $goods                     = $demand['goods'];
        $demand_m                  = D('Scm/Demand');
        $quotation_m               = D('Scm/Quotation');
        $demand_goods_m            = D('Scm/DemandGoods');
        $demand['collection_time'] = json_encode($demand['collection_time']);
        $demand['attachment']      = json_encode($demand['attachment']);

        if (!$demand['step']) {
            if (!$this->checkOnWayBatchNum($goods)) {
                return false;
            }
        }
        M()->startTrans();
        //保存需求主表
        if ($demand['id']) {
            //有demand_id为编辑需求,需要校验
            $demand_old    = $this->getDemand($demand['id']);
            $quotation_old = $this->getQuotations($demand['id'], 'chosen');
            if (!$this->stepCheck($demand_old['step'], $demand_old['status'], $quotation_old)) return false;
            $res_demand = $demand_m->updateDemand($demand, $demand_old['step'] == DemandModel::$step['seller_choose']);
            if ($demand_old['step'] == DemandModel::$step['purchase_claim']) {
                if ($quotation_m->where(['demand_id' => $demand['id']])->save(['invalid' => 1]) === false) {
                    $this->error = '编辑失败：报价置为失效失败';
                    return false;
                }
            }
            $info = '编辑草稿';
        } else {
            //没有demand_id为创建需求
            $demand_m->create($demand);
            $demand_m->create_user = $_SESSION['m_loginname'];
            $demand_m->demand_code = $demand_code = $demand_m->createDemandCode();
            $res_demand            = $demand_m->add();
            $demand['id']          = $res_demand;
            $info                  = '新增草稿';
        }
        ELog::add(['info' => $info, 'table' => 'tb_sell_demand', 'demand' => $demand]);
        if ($res_demand === false) {
            M()->rollback();
            $this->error = $demand_m->getError() ? '：' . $demand_m->getError() : '';
            return false;
        }
        //需求商品保存
        $batches     = [];
        $batch_allos = [];
        $goods_ids   = [];
        foreach ($goods as $v) {
            $goods_supplier = (new GoodsLogic())->getGoods($v['sku_id'])['supplier_cd'];
            if ($goods_supplier != 'N002680001') {
                $this->error = $v['search_id'] . '商品供应商不为GP';
                M()->rollback();
                return false;
            }
            $orderId = $demand_old['demand_code'] ? $demand_old['demand_code'] : $demand_code;
            foreach ($v['spot_batch'] as $val) {
                if ($val['id'] && $val['num']) {
                    if (isset($val['type']) && $val['type'] == '1') {
                        $batch_allos[] = ['alloNo' => $val['purchaseOrderNo'], 'orderId' => $orderId, 'operatorId' => DataModel::userId(), 'sku' => $val['sku_id'], 'saleCode' => $demand['sell_team'], 'num' => $val['num']];
                    } else {
                        $batches[] = ['batchId' => $val['id'], 'num' => $val['num']];

                    }
                }
            }
            $v['spot_batch']              = json_encode($v['spot_batch']);
            $v['spot_warehouse']          = json_encode($v['spot_warehouse']);
            $v['on_way_batch']            = json_encode($v['on_way_batch']);
            $v['on_way_warehouse']        = json_encode($v['on_way_warehouse']);
            $v['require_number_original'] = $v['require_number'];

            $demand_goods_m->create($v, 2);
            $demand_goods_m->demand_id   = $demand['id'];
            $demand_goods_m->create_user = $_SESSION['m_loginname'];
            if ($v['id']) {
                $res_goods   = $demand_goods_m->save();
                $res_goods_q = D('Scm/QuotationGoods')->where(['demand_goods_id' => $v['id']])->save(['sell_price' => $v['sell_price']]);
                $goods_ids[] = $v['id'];
            } else {
                $res_goods   = $demand_goods_m->add();
                $res_goods_q = true;
                $goods_ids[] = $res_goods;
            }
            if ($res_goods === false || $res_goods_q === false) break;
        }
        if ($res_goods === false) {
            M()->rollback();
            $this->error = '需求商品保存失败';
            return false;
        }
        if (!empty($goods_ids) && $demand_goods_m->where(['demand_id' => $demand['id'], 'id' => ['not in', $goods_ids]])->delete() === false) {
            M()->rollback();
            $this->error = '删除商品失败';
            return false;
        }
        if ((!$demand_old || $demand_old['step'] == DemandModel::$step['demand_submit']) && $demand['is_submit']) {
            if (!$this->demandSubmit($demand['id'], $demand['profit'])) {
                M()->rollback();
                return false;
            }
        }
        if (!empty($batches)) {
            $batch['orderId'] = $demand_old['demand_code'] ? $demand_old['demand_code'] : $demand_code;
            $batch['batches'] = $batches;
            $batch['virType'] = self::$batch_type['batch'];
            $res              = ApiModel::occupyBatch($batch);
            if ($res['code'] != 2000) {
                M()->rollback();
                $this->error = '批次占用失败';
                ELog::add(['info' => '批次占用失败', 'request' => $batch, 'response' => $res]);
                return false;
            }
        } elseif ($demand_old && $demand_old['is_spot'] != DemandModel::$is_spot['purchase_only']) {
//            $order_id = $demand_old['demand_code'] ?: $demand_code;
//            $res      = $this->freeUpBatch($order_id, self::$batch_type['batch']);
//            if (!$res) {
//                M()->rollback();
//                $this->error = '批次占用失败';
//                return false;
//            }
        }
        if (!empty($batch_allos)) {
            $res = ApiModel::occupyBatch($batch_allos, 1);
            if ($res['code'] != 2000) {
                M()->rollback();
                $this->error = '调拨批次占用失败';
                ELog::add(['info' => '批次占用失败', 'request' => $batch, 'response' => $res]);
                return false;
            } elseif ($demand_old && $demand_old['is_spot'] != DemandModel::$is_spot['purchase_only']) {
                $order_id = $demand_old['demand_code'] ?: $demand_code;
                $sku_list = [];
                foreach ($batch_allos as $item) {
                    $tem['orderId'] = $item['orderId'];
                    $tem['virType'] = 'N002440200';
                    $tem['skuId']   = $item['sku'];
                    $tem['alloNo']  = $item['alloNo'];
                    $sku_list[]     = $tem;
                }
                $res = $this->freeUpBatch($order_id, '', $sku_list);
                if (!$res) {
                    M()->rollback();
                    $this->error = '调拨批次占用失败';
                    return false;
                }
            }
        }
        M()->commit();
        $this->data['demand_id'] = $demand['id'];
        return true;
    }

    public function releaseSpot($id)
    {
        $demand_m       = D('Scm/Demand');
        $demand_goods_m = D('Scm/DemandGoods');
        $demand_m->startTrans();
        $demand = $demand_m->lock(true)->where(['id' => $id])->find();
        if (!$this->stepCheck($demand['step'], $demand['status'])) return false;
        $res_goods = $demand_goods_m->where(['demand_id' => $id])->save(['spot_batch' => '[]', ['spot_number' => 0]]);
        if ($res_goods === false) {
            $demand_m->rollback();
            $this->error = '清空商品批次信息失败';
            return false;
        }
        $res_batch = $this->freeUpBatch($demand['demand_code']);
        if (!$res_batch) {
            $demand_m->rollback();
            $this->error = '解除占用失败';
            return false;
        }
        $demand_m->commit();
        return true;
    }

    public function getPlatformCd($platform)
    {
        if (empty($platform)) return '';
        $TbMsCmnCdModel = D('TbMsCmnCd');
        $site_arr = $TbMsCmnCdModel->site();
        $platform_arr = explode(",", $platform);
        $temp = [];
        foreach ($platform_arr as $key => $value) {
            $temp[] = $site_arr[$value];
        }
        return implode(",", $temp);

    }
    public function demandDetail($id)
    {
        $area_m                                             = D('Area');
        $demand['demand']                                   = D('Scm/Demand')->where(['id' => $id])->find();
        $demand['demand']['collection_time']                = json_decode($demand['demand']['collection_time'], true);
        $demand['demand']['attachment']                     = json_decode($demand['demand']['attachment'], true);
        $demand['demand']['po']                             = json_decode($demand['demand']['po'], true);
        //判断是否有水印，有则取水印pdf
        foreach ($demand['demand']['po'] as &$po_image) {
            $save_name_arr = explode('.', $po_image['save_name']);
            if (is_file(ATTACHMENT_DIR_IMG. $save_name_arr[0]. '-sy.pdf')) {
                $po_image['save_name'] = $save_name_arr[0]. '-sy.pdf';
            }
        }
        unset($po_image);
        $demand['demand']['po_archive'] = json_decode($demand['demand']['po_archive'], true);
        foreach ($demand['demand']['po_archive'] as &$poa_image) {
            $save_name_arr = explode('.', $poa_image['save_name']);
            if (is_file(ATTACHMENT_DIR_IMG. $save_name_arr[0]. '-sy.pdf')) {
                $poa_image['save_name'] = $save_name_arr[0]. '-sy.pdf';
            }
        }
        unset($poa_image);
        $demand['demand']['po_with_watermark']              = json_decode($demand['demand']['po_with_watermark'], true);
        $demand['demand']['receivables_day_avg']            = $demand['demand']['customer_charter_no'] ? M('sp_supplier', 'tb_crm_')->where(['SP_CHARTER_NO' => $demand['demand']['customer_charter_no'], 'DATA_MARKING' => 1])->getField('receivables_day_avg') : null;
        $demand_goods                                       = D('Scm/DemandGoods')->where(['demand_id' => $id])->select();
        $demand['demand']['legal_man']                      = $demand['demand']['legal_man'] ?: TbMsCmnCdModel::getLegalMan($demand['demand']['sell_team']);
        $demand['demand']['rebate_rate']                    = $demand['demand']['rebate_rate'] * 100;
        $demand['demand']['rebate_amount']                  = $demand['demand']['rebate_amount']  ? : 0.00;

        $demand['demand']['expected_sales_country_val']         = $area_m->getCountryNameByIds($demand['demand']['expected_sales_country']);
        $demand['demand']['expected_sales_platform_cd_val']     = D('Scm/Demand','Logic')->getPlatformCd($demand['demand']['expected_sales_platform_cd']); 

        foreach ($demand_goods as &$v) {
            $v['quotation_goods'] = D('Scm/QuotationGoods')
                ->alias('t')
                ->field('t.id,t.quotation_id,a.quotation_code,a.supplier,b.CD_VAL purchase_team,purchaser,ce,ce_level,c.CD_VAL currency,purchase_price,purchase_price_not_contain_tax,supply_number,e.CD_VAL drawback_percent,d.CD_VAL auth_and_link,a.status,choose_number,t.sell_small_team_json')
                ->join('left join tb_sell_quotation a on a.id = t.quotation_id')
                ->join('left join tb_ms_cmn_cd b on b.CD = a.purchase_team')
                ->join('left join tb_ms_cmn_cd c on c.CD = a.currency')
                ->join('left join tb_ms_cmn_cd d on d.CD = t.auth_and_link')
                ->join('left join tb_ms_cmn_cd e on e.CD = t.drawback_percent')
                ->where(['demand_goods_id' => $v['id'], 'choose_number' => ['egt', 0], 'invalid' => 0, 'a.status' => ['neq', QuotationModel::$status['draft']]])
                ->select();
            foreach ($v['quotation_goods'] as &$itme){
                $itme['sell_small_team_json'] = json_decode($itme['sell_small_team_json'], true);
            }
        }
        $demand['goods'] = $demand_goods;
        $demand['goods'] = $this->formatDemandGoodsUpcMore($demand_goods,'bar_code');
        $quotation       = D('Scm/Quotation')->where(['demand_id' => $id, 'invalid' => 0])->select();
        $status_list     = [
            'step'             => $demand['demand']['step'],
            'demand_status'    => $demand['demand']['status'],
            'quotation_status' => [],
            'is_spot'          => $demand['demand']['is_spot'],
        ];
        $pur_clause_m    = M('clause', 'tb_pur_');
        $process_sync    = true;
        foreach ($quotation as &$v) {
            $v['legal_man'] = $v['legal_man'] ?: TbMsCmnCdModel::getLegalMan($v['purchase_team']);
            $this->cd2Val($v, 'quotation');
            $tmp                               = [
                'id'     => $v['id'],
                'chosen' => $v['chosen'],
                'step'   => $v['step'],
                'status' => $v['status']
            ];
            $status_list['quotation_status'][] = $tmp;
            if ($v['chosen'] == QuotationModel::$chosen['chosen'] && $v['step'] != $demand['demand']['step']) $process_sync = false;
            //获取报价单对应的预付款和尾款信息
            $clause_info      = $pur_clause_m->field('pre_paid_date, percent, clause_type')->where(['quotation_id' => $v['id']])->select();
            $v['clause_info'] = '';
            if ($clause_info) {
                $advance_pay_percent = 0;
                foreach ($clause_info as $key => $value) {
                    if ($value['clause_type'] == '1') {
                        $advance_pay_percent = bcadd($advance_pay_percent, $value['percent'], 8);
                    }
                }
                foreach ($clause_info as $kk => &$vv) {
                    if ($vv['clause_type'] == '2') {
                        $vv['percent'] = bcsub(100, $advance_pay_percent, 2);
                    }
                }
                $v['clause_info'] = $clause_info;
            }
        }

        //客户法务审核信息
        $audit_info = D('BTBCustomerManagement')->getCustomerAuditInfo($demand['demand']['customer_charter_no']);

        $status_list['process_sync'] = $process_sync;
        $demand['quotation']         = $quotation;
        $demand['status_list']       = $status_list;
        $this->cd2Val($demand['demand'], 'demand');
        $this->cd2Val($demand['goods'], 'demand_goods');
        //收货城市
        $site_m                                   = D('TbCrmSite');
        $demand['demand']['receive_country_val']  = $site_m->siteName($demand['demand']['receive_country']);
        $demand['demand']['receive_province_val'] = $site_m->siteName($demand['demand']['receive_province']);
        $demand['demand']['receive_city_val']     = $site_m->siteName($demand['demand']['receive_city']);
        $demand['profit']                         = M('demand_profit', 'tb_sell_')->where(['demand_id' => $id])->find();
        $demand['login_name']                     = $_SESSION['m_loginname'];
        $demand['audit_info']                     = $audit_info;
        return $demand;
    }

    public function demandSubmit($id, $profit = [])
    {
        $demand_m        = D('Scm/Demand');
        $demand_goods_m  = D('Scm/DemandGoods');
        $demand_profit_m = D('Scm/DemandProfit');
        $demand_m->startTrans();
        $this->demand       = $demand = $demand_m->lock(true)->where(['id' => $id])->find();
//        $this->demand_goods = $demand_goods = $demand_goods_m->where(['demand_id' => $id, 'invalid' => 0])->select();//没有invalid字段
        $this->demand_goods = $demand_goods = $demand_goods_m->where(['demand_id' => $id])->select();

        if (!$this->checkOnWayBatchNum($demand_goods)) {
            return false;
        }

        if (!$this->stepCheck($demand['step'], $demand['status'])) return false;
        if (!$this->createCheck($demand, $demand_goods)) return false;
        $res = $demand_m->where(['id' => $id])->save(['status' => DemandModel::$status['pass_through']]);
        if ($demand['is_spot'] == DemandModel::$is_spot['spot_only']) {
            //保存需求CE
            $profit['demand_id'] = $id;
            ELog::add(['info' => '保存需求利润数据', 'table' => 'tb_sell_demand_profit', 'id' => $id, 'profit' => $profit]);
            if (!$demand_profit_m->create($profit) || !$demand_profit_m->add()) {
                $this->error = $demand_profit_m->getError() ?: 'CE保存失败';
                M()->rollback();
                return false;
            }
        }
        $batches     = [];
        $batch_allos = [];
        foreach ($demand_goods as $v) {
            $on_way_batch = json_decode($v['on_way_batch'], true);
            foreach ($on_way_batch as $val) {
                if ($val['id'] && $val['num']) {
                    if (isset($val['type']) && $val['type'] == '1') {
                        //$batch_allos[] = ['alloNo'=>$val['purchaseOrderNo'],'orderId'=>$demand['demand_code'],'operatorId'=>DataModel::userId(),'sku'=>$v['sku_id'],'saleCode'=>$demand['sell_team'],'num'=>$val['num']];
                        $batch_allos[] = ['alloNo' => $val['purchaseOrderNo'], 'orderId' => $demand['demand_code'], 'operatorId' => DataModel::userId(), 'batchId' => $val['id'], 'num' => $val['num']];
                    } else {
                        $batches[] = ['batchId' => $val['id'], 'num' => $val['num']];
                    }
                }
            }
        }
        if (!empty($batches)) {
            $batch['orderId'] = $demand['demand_code'];
            $batch['batches'] = $batches;
            $batch['virType'] = self::$batch_type['on_way'];
            $res              = ApiModel::occupyBatch($batch);
            if ($res['code'] != 2000) {
                M()->rollback();
                $this->error = '批次占用失败';
                ELog::add(['info' => '批次占用失败', 'request' => $batch, 'response' => $res]);
                return false;
            }
        }
        if (!empty($batch_allos)) {
            $res = ApiModel::occupyBatch($batch_allos, 1);
            if ($res['code'] != 2000) {
                M()->rollback();
                $this->error = '调拨批次占用失败';
                ELog::add(['info' => '调拨在途批次占用失败', 'request' => $batch_allos, 'response' => $res]);
                return false;
            }
        }
        if ($res === false) {
            M()->rollback();
            $this->error = '提交失败';
            return false;
        }
        $this->checkAllOver(['step' => 'demand_submit', 'type' => 'demand', 'id' => $id]);
        M()->commit();
        //发送邮件
        ScmEmailModel::AA($demand);
        return true;
    }

    /**
     * @param $demand
     * @param $demand_goods
     * @return bool
     * 需求提交校验
     */
    public function createCheck($demand, $demand_goods)
    {
        $demand_m       = D('Scm/Demand');
        $demand_goods_m = D('Scm/DemandGoods');
        if ($demand['demand_type'] == DemandModel::$demand_type_store) {
            $validate = DemandModel::$validate_store_create;
        } elseif ($demand['is_spot'] == 1) {
            $validate = DemandModel::$validate_sell_submit;
            if (!$demand['is_purchase_only'] && $demand['type'] == DemandModel::$demand_type_sell && !$demand['contract']) {
                $this->error = '合同必填';
                return false;
            }
            $collection_time = json_decode($demand['collection_time'], true);
            if (!$collection_time) {
                $this->error = '收款时间必填';
                return false;
            }
            $collection_time_fail = false;
            foreach ($collection_time as $v) {
                if ($demand['demand_type'] == DemandModel::$demand_type_sell) {
                    if (!($v['node'] && $v['days'] && $v['day_type'] && $v['percent'] && $v['date'])) {
                        $collection_time_fail = true;
                        break;
                    }
                } else {
                    if (!($v['percent'] && $v['date'])) {
                        $collection_time_fail = true;
                        break;
                    }
                }
            }
            if ($collection_time_fail) {
                $this->error = '收款时间异常';
                return false;
            }
        } else {
            $validate = DemandModel::$validate_sell_create;
        }
        if (!$demand_m->validate($validate)->create($demand)) {
            $this->error = $demand_m->getError();
            return false;
        }
        $goods_fail = false;
        foreach ($demand_goods as $v) {
            if (!$demand_goods_m->create($v, 4)) {
                $goods_fail  = true;
                $this->error = $demand_goods_m->getError();
                break;
            }
        }
        if ($goods_fail) return false;
        return true;
    }


    /**
     * @param $demand
     * @param $demand_goods
     * @return bool
     * 需求提交校验
     */
    public function submitCheck($demand, $demand_goods)
    {
        $demand_m       = D('Scm/Demand');
        $demand_goods_m = D('Scm/DemandGoods');
        if ($demand['demand_type'] == DemandModel::$demand_type_store) {
            $validate = DemandModel::$validate_store_submit;
        } else {
            $validate = DemandModel::$validate_sell_submit;
        }
        if (!$demand_m->validate($validate)->create($demand)) {
            $this->error = $demand_m->getError();
            return false;
        }
        if (!$demand['is_purchase_only'] && $demand['type'] == DemandModel::$demand_type_sell && !$demand['contract']) {
            $this->error = '合同必填';
            return false;
        }
        $collection_time = json_decode($demand['collection_time'], true);
        if (!$collection_time) {
            $this->error = '收款时间必填';
            return false;
        }
        $collection_time_fail = false;
        foreach ($collection_time as $v) {
            if ($demand['demand_type'] == DemandModel::$demand_type_sell) {
                if (!($v['node'] && $v['days'] && $v['day_type'] && $v['percent'] && $v['date'])) {
                    $collection_time_fail = true;
                    break;
                }
            } else {
                if (!($v['percent'] && $v['date'])) {
                    $collection_time_fail = true;
                    break;
                }
            }
        }
        if ($collection_time_fail) {
            $this->error = '收款时间异常';
            return false;
        }
        $goods_fail         = false;
        $total_purchase_num = 0;
        foreach ($demand_goods as $v) {
            if (!$demand_goods_m->validate(DemandGoodsModel::$_validate_submit)->create($v, 4)) {
                $goods_fail  = true;
                $this->error = $demand_goods_m->getError();
                break;
            }
            $total_purchase_num += $v['purchase_number'];
        }
        if ($goods_fail) return false;
        if ($total_purchase_num = 0) {
            $this->error = '采购总数不能为0';
            return false;
        }
        return true;
    }

    public function demandDelete($id)
    {
        $demand_m       = D('Scm/Demand');
        $demand_goods_m = D('Scm/DemandGoods');
        $demand_m->startTrans();
        $demand = $demand_m->lock(true)->where(['id' => $id])->find();
        if (!$this->stepCheck($demand['step'], $demand['status'])) return false;
        //解除占用
        if ($this->demand['is_spot'] != DemandModel::$is_spot['purchase_only']) {
            $res_batch = $this->freeUpBatch($demand['demand_code']);
            if (!$res_batch) {
                $demand_m->rollback();
                $this->error = '解除占用失败';
                return false;
            }
        }
        $res = $demand_m->where(['id' => $id])->delete();
        if ($res === false) {
            $this->error = '提交失败';
            return false;
        }
        M()->commit();
        return true;
    }

    /**
     * 需求审批、销售领导审批、ceo审批
     * @param $id
     * @param $status
     * @param $reason
     * @param $remark
     * @return bool
     */
    public function approve($id, $status, $reason = '', $remark = '')
    {
        if ($status == 0) {
            $status = DemandModel::$status['not_pass_through'];
        } elseif ($status == 1) {
            $status = DemandModel::$status['pass_through'];
        } else {
            $this->error = '参数异常';
            return false;
        }
        $demand_m = D('Scm/Demand');
        $demand_m->startTrans();
        $demand = $demand_m->lock(true)->where(['id' => $id])->find();
        if (!$this->leaderCheck($demand['step'], $demand['sell_team'])) return false;
        if (!$this->stepCheck($demand['step'], $demand['status'])) return false;
        $save                                 = ['status' => $status];
        $save['reason']                       = $reason;
        $save['sales_leadership_approval_by'] = DataModel::userNamePinyin();
        $save['sales_leadership_approval_at'] = DateModel::now();
        $res                                  = $demand_m->where(['id' => $id])->save($save);
        if ($res === false) {
            M()->rollback();
            $this->error = '保存失败';
            return false;
        }
        if ($remark) {
            $res_p = D('Scm/DemandProfit')->where(['demand_id' => $id])->save(['remark' => $remark, 'remark_user' => $_SESSION['m_loginname']]);
            if ($res_p === false) {
                M()->rollback();
                $this->error = '备注保存失败';
                return false;
            }
        }
        $step_arr = array_flip(DemandModel::$step);
        $res_over = $this->checkAllOver(['step' => $step_arr[$demand['step']], 'type' => 'demand', 'id' => $id]);
        if ($res_over === false) {
            M()->rollback();
            return false;
        }
        M()->commit();
        //发送邮件
        if ($step_arr[$demand['step']] == 'ceo_approve') {
            $status == DemandModel::$status['not_pass_through'] ? ScmEmailModel::CC($demand) : ScmEmailModel::BB($demand);
        }
        if ($status == DemandModel::$status['not_pass_through']) {
            switch ($step_arr[$demand['step']]) {
                case 'leader_approve':
                    ScmEmailModel::H($demand);
                    break;
                case 'ceo_approve':
                    ScmEmailModel::K($demand);
                    break;
            }
        }

        //已中标的报价发送企业微信提醒
        if ($step_arr[$demand['step']] == 'leader_approve') {
            $quotation = D('Scm/Quotation')->field(['id', 'quotation_code', 'purchaser', 'currency', 'po_amount'])->where(['demand_id' => $id, 'chosen' => 'N002140300'])->select();
            if (!empty($quotation)) {
                $quotation_ids = array_column($quotation, 'id');
                $pur_clause_data = D('Scm/PurClause')->where(['quotation_id' => ['in', $quotation_ids], 'clause_type' => ['in', '1,2']])->field('id,quotation_id,days,percent,pre_paid_date,clause_type,action_type_cd')->select();
                $pay_data = [];
                foreach ($pur_clause_data as $pk => $pv) {
                    $pay_data[$pv['quotation_id']][] = $pv;
                }

                foreach ($quotation as $qk => $qv) {
                    $content = "采购需求编号：{$qv['quotation_code']}已经通过销售领导审批，总金额" . cdVal($qv['currency']) . ' ' . $qv['po_amount'] . '，';
                    $pre_pay_content = "预付款：";
                    $end_pay_content = "尾款：";

                    foreach ($pay_data[$qv['id']] as $pqk => $pqv) {
                        if ($pqv['clause_type'] == '1') {
                            $percent = sprintf("%.2f", $pqv['percent']);
                            if ($pqv['action_type_cd'] == 'N002860001') {
                                $pre_pay_content .= "订单创建后{$pqv['days']}天付PO金额*{$percent}%，预计{$pqv['pre_paid_date']}，";
                            } else if ($pqv['action_type_cd'] == 'N002860002') {
                                $pre_pay_content .= "指定日期付PO金额*{$percent}%：{$pqv['pre_paid_date']}，";
                            }
                        }

                        if ($pqv['clause_type'] == '2') {
                            if ($pqv['action_type_cd'] === 'N001390200') {
                                $end_pay_content .= "每次发货后{$pqv['days']}天内付货值部分金额，平均预计：{$pqv['pre_paid_date']}，";
                            } else if ($pqv['action_type_cd'] === 'N001390400') {
                                $end_pay_content .= "每次入库后{$pqv['days']}天内付货值部分金额，平均预计：{$pqv['pre_paid_date']}，";
                            }
                        }
                    }

                    $pre_pay_content = $pre_pay_content != '预付款：' ? trim($pre_pay_content, '，') : '预付款：无';
                    $end_pay_content = $end_pay_content != '尾款：' ? trim($end_pay_content, '，') : '尾款：无';
                    $demand_detail_url =  ERP_URL ."index.php?m=index&a=index&source=email&actionType=purchase&id={$qv['id']}";
                    $see ="<a href='$demand_detail_url'>【去查看】</a>";

                    $content .= $pre_pay_content . '，' . $end_pay_content . "。".$see;
                    $dept_leaders = D('TbHrDept')->getDeptLeaderByEmpName($qv['purchaser']);
                    $dept_leaders_arr = array_values(array_unique(explode(',', $dept_leaders)));
                    $send_people = array_values(array_diff($dept_leaders_arr, array("{$qv['purchaser']}")));

                    $wx_ids = M()->table('bbm_admin a')
                        ->field('b.wid')
                        ->join('left join tb_hr_empl_wx b on a.empl_id = b.uid')
                        ->where(['a.M_NAME' => ['in', $send_people]])
                        ->select();
                    //最好去掉多余的竖线
                    $wx_ids = trim(implode(array_column($wx_ids, 'wid'), '|'),'|');
                    //给采购同事的所有上级发送企业微信
                    $res = WxAboutModel::sendWxMsg($wx_ids, $content);
                    #记录日志
                    Logs(json_encode($res), __FUNCTION__ . '----send res', 'DemandApproveSendWx');
                }
            }
        }

        return true;
    }

    public function chooseQuotation($choose)
    {
        $demand_m          = D('Scm/Demand');
        $demand_goods_m    = D('Scm/DemandGoods');
        $demand_profit_m   = D('Scm/DemandProfit');
        $quotation_m       = D('Scm/Quotation');
        $quotation_goods_m = D('Scm/QuotationGoods');
        $demand_m->startTrans();
        $demand = $demand_m->lock(true)->where(['id' => $choose['id']])->find();
        if (!$this->stepCheck($demand['step'], $demand['status'])) return false;
        $purchase_number = $demand_goods_m->where(['demand_id' => $choose['id']])->getField('id,purchase_number', true);
        $demand_goods    = $demand_goods_m->where(['demand_id' => $choose['id']])->select();
        if (!$this->submitCheck($demand, $demand_goods)) return false;
        $failure       = false;
        $choose_number = [];
        foreach ($choose['goods'] as $k => $v) {
            $save['choose_number'] = $v;
            if ($v != 0) {
                $quotation_goods                                    = $quotation_goods_m->where(['id' => $k])->find();
                $choose_number[$quotation_goods['demand_goods_id']] = ($choose_number[$quotation_goods['demand_goods_id']] ?: 0) + $v;
                //已选中报价状态修改
                $res_quotation = $quotation_m->where(['id' => $quotation_goods['quotation_id']])->save(['chosen' => QuotationModel::$chosen['chosen']]);
            }
            //报价商品数量修改
            $res = $quotation_goods_m->where(['id' => $k])->save($save);
            if ($res === false || $res_quotation === false) {
                $failure = true;
            }
        }
        foreach ($purchase_number as $k => $v) {
            if ((int)$v != $choose_number[$k]) {
                $choose_number_dif         = true;
                $choose_number_dif_arr  [] = $k;
            }
        }
        if ($failure) {
            $this->error = '商品数据保存失败';
            $demand_m->rollback();
            return false;
        }
        //选择商品数量!=需求数量-批次数量时
        if ($choose_number_dif) {
            $demand_save = ['status' => DemandModel::$status['pass_through']];
            if ($demand['demand_type'] == DemandModel::$demand_type_sell) {
                if (!$choose['sell_amount']) {
                    $demand_m->rollback();
                    $this->error = '参数缺失';
                    return false;
                }
                $demand_save['sell_amount']      = $choose['sell_amount'];
                $demand_save['expense_currency'] = $choose['expense_currency'];
                $demand_save['expense']          = $choose['expense'];
            }
            $demand_save['sell_amount']      = $choose['sell_amount'];
            $demand_save['expense_currency'] = $choose['expense_currency'];
            $demand_save['expense']          = $choose['expense'];
            //需求状态置为处理通过
            if (!$demand_m->where(['id' => $choose['id']])->save($demand_save)) {
                $demand_m->rollback();
                $this->error = '需求状态保存失败';
                return false;
            }
            //更新需求商品需求数量、采购数量
            foreach ($choose_number_dif_arr as $v) {
                $num = $choose_number[$v] ?: 0;
                $res = $demand_goods_m->where(['id' => $v])->save(['require_number' => ['exp', 'spot_number+' . $num], 'purchase_number' => $num]);
                if ($res === false) {
                    $demand_goods_error = true;
                    break;
                }
            }
            if ($demand_goods_error) {
                $this->error = '更新商品需求数量和采购数量失败';
                $demand_m->rollback();
                return false;
            }
        } else {
            //需求状态置为处理通过
            if (!$demand_m->where(['id' => $choose['id']])->save(['status' => DemandModel::$status['pass_through']])) {
                $demand_m->rollback();
                $this->error = '需求状态保存失败';
                return false;
            }
        }

        //已提交未选中的置为落选
        $res_not_chosen = $quotation_m
            ->where([
                'demand_id' => $choose['id'],
                'status'    => QuotationModel::$status['no_need_to_treat'],
                'invalid'   => 0,
                'chosen'    => QuotationModel::$chosen['to_choose']
            ])
            ->save(['chosen' => QuotationModel::$chosen['not_chosen']]);
        if ($res_not_chosen === false) {
            $this->error = '落选报价状态保存失败';
            $demand_m->rollback();
            return false;
        }
        //保存需求CE
        $profit              = $choose['profit'];
        $profit['demand_id'] = $choose['id'];

        if (!$demand_profit_m->create($profit) || !$demand_profit_m->add()) {
            $this->error = $demand_profit_m->getError() ?: 'CE保存失败';
            M()->rollback();
            return false;
        }
        //更新报价CE
        $demand_new = $demand_m->where(['id' => $demand['id']])->find();
        if (!$quotation_m->updateCE($demand_new)) {
            $this->error = $quotation_m->getError();
            return false;
        }
        if (!$this->checkAllOver(['step' => 'seller_choose', 'type' => 'demand', 'id' => $demand['id']])) {
            $this->error = '完成操作失败';
            $demand_m->rollback();
            return false;
        }
        $demand_m->commit();
        return true;
    }

    public function demandQuotationSubmit($id, $profit)
    {
        $demand_m        = D('Scm/Demand');
        $demand_goods_m  = D('Scm/DemandGoods');
        $quotation_m     = D('Scm/Quotation');
        $demand_profit_m = M('demand_profit', 'tb_sell_');
        $demand_m->startTrans();
        $demand       = $demand_m->lock(true)->where(['id' => $id])->find();
        $demand_goods = $demand_goods_m->where(['demand_id' => $id, 'invalid' => 0])->select();
        $quotation    = $quotation_m->field('status,step')->lock(true)->where(['demand_id' => $id, 'chosen' => QuotationModel::$chosen['chosen'], 'invalid' => 0])->select();
        if (!$this->stepCheck($demand['step'], $demand['status'], $quotation)) return false;
        if (!$this->submitCheck($demand, $demand_goods)) return false;
        $res = $demand_m->where(['id' => $id])->save(['status' => DemandModel::$status['pass_through']]);
        if ($res === false) {
            $this->error = '保存失败';
            M()->rollback();
            return false;
        }
        if (!$demand_profit_m->add($profit)) {
            $this->error = 'CE保存失败';
            M()->rollback();
            return false;
        }
        if (!$this->checkAllOver(['step' => 'seller_submit', 'type' => 'demand', 'id' => $demand['id']])) {
            $this->error = '完成操作失败';
            $demand_m->rollback();
            return false;
        }
        M()->commit();
        return true;
    }

    /**
     * 法务审批
     * @param $data
     * @return bool
     */
    public function justiceApprove($data)
    {
        $d_demand = new DemandModel();
        $d_demand->startTrans();
        $d_data = $d_demand->lock(true)->where(['id' => $data['id']])->find();
        //检查报价是否通过
        if ($d_data['is_spot'] != DemandModel::$is_spot['spot_only'] && $data['status']) {
            $q_quotation        = new QuotationModel();
            $q_untreated_exists = $q_quotation->where([
                'demand_id' => $data['id'],
                'status'    => QuotationModel::$status['untreated'],
                'chosen'    => QuotationModel::$chosen['chosen'],
                'invalid'   => 0
            ])->getField('id');

            if ($q_untreated_exists) {
                $this->error = '有报价还没有审批通过';
                $d_demand->rollback();
                return false;
            }
        }

        //检查操作权限
        $permit = $this->stepCheck($d_data['step'], $d_data['status'], D('Scm/Quotation')->getStatusArr($d_data['id']));
        //检查法务权限
        if (!$permit || !$this->justiceCheck($d_data['sell_team'])) {
            return false;
        }
        if ($d_data) {
            $d_data['status'] = $data['status'] ? DemandModel::$status['pass_through'] : DemandModel::$status['not_pass_through'];
            $d_data['reason'] = $data['status'] ? '' : $data['reason'];
//            if ($data['status']) {
//                if (!empty($data['po'] && is_array($data['po']))) {
//                    $d_data['po'] = json_encode($data['po'], JSON_UNESCAPED_UNICODE);
//                }
//            }
            if ($d_demand->save($d_data) === false) {
                $d_demand->rollback();
                $this->error = '提交失败';
                return false;
            }
            if (!$this->checkAllOver(['step' => 'justice_approve', 'type' => 'demand', 'id' => $data['id']])) {//检查当阶段是否全部完成，修改主状态
                return false;
            }
        } else {
            $this->error = '订单状态异常';
            return false;
        }
        $b2b_info = (new Model())->table('tb_b2b_info bi')
            ->field('oc.b2b_manager_by')
            ->join('tb_ms_cmn_cd cd on cd.CD_VAL = bi.our_company')
            ->join('tb_con_division_our_company oc on oc.our_company_cd = cd.CD')
            ->where(['bi.PO_ID'=>$d_data['demand_code']])
            ->find();
        if (false === M('b2b_info', 'tb_')->where(['PO_ID'=>$d_data['demand_code']])->save(['verification_leader_by'=>$b2b_info['b2b_manager_by']])) {
            $d_demand->rollback();
            $this->error = 'B2B核销负责人设置失败';
            return false;
        }
        $d_demand->commit();
        $this->code = 2000;
        //发送邮件
        $data['status'] ? null : ScmEmailModel::M1($d_data);
        return true;
    }

    /**
     * 销售弃单
     * @param $id
     * @param $reason
     * @return bool
     */
    public function demandDiscard($id, $reason)
    {
        $demand_m = D('Scm/Demand');
        $demand_m->startTrans();
        $demand = $this->demand = $demand_m->lock(true)->where(['id' => $id])->find();
        if (!$this->stepCheck($demand['step'], $demand['status'])) return false;
        if (!$this->doDemandDiscard($id, $reason)) {
            $demand_m->rollback();
            return false;
        }
        $demand_m->commit();
        return true;
    }

    /**
     * 销售弃单保存数据
     * @param $id
     * @param $reason
     * @return bool
     */
    private function doDemandDiscard($id, $reason)
    {
        $demand_m       = D('Scm/Demand');
        $demand_goods_m = D('Scm/DemandGoods');
        $quotation_m    = D('Scm/Quotation');
        $demand_m->startTrans();
        if (!$demand_m->where(['id' => $id])->save(['status' => DemandModel::$status['discarded'], 'reason' => $reason])) {
            M()->rollback();
            $this->error = '需求保存失败';
            return false;
        }
        $q_list = $quotation_m->field('purchaser,id,create_user')->where(['demand_id' => $id, 'invalid' => 0])->select();//修改前查询
        if ($quotation_m->where(['demand_id' => $id, 'invalid' => 0])->save(['status' => QuotationModel::$status['discarded']]) === false) {
            M()->rollback();
            $this->error = '报价保存失败';
            return false;
        }
        $demand_goods_save = [
            'spot_number'      => 0,
            'spot_batch'       => '',
            'spot_warehouse'   => '',
            'spot_cost'        => 0,
            'on_way_number'    => 0,
            'on_way_batch'     => '',
            'on_way_warehouse' => '',
            'on_way_cost'      => 0
        ];
        if ($demand_goods_m->where(['demand_id' => $id])->save($demand_goods_save) === false) {
            M()->rollback();
            $this->error = '需求商品保存失败';
            return false;
        }
        if ($this->demand['is_spot'] != DemandModel::$is_spot['purchase_only']) {
            $res_batch = $this->freeUpBatch($this->demand['demand_code']);
            if (!$res_batch) {
                $demand_m->rollback();
                $this->error = '解除占用失败';
                return false;
            }
        }
        $demand_m->commit();
        //发送邮件
        $demand = $demand_m->where(['id' => $id])->find();
        ScmEmailModel::U($demand, $q_list);
        return true;
    }

    /**法务盖章
     * @param $data
     * @return bool
     */
    public function justiceStamp($data)
    {
        $d_demand = new DemandModel();
        $d_demand->startTrans();
        $d_data = $d_demand->lock(true)->where(['id' => $data['id']])->find();
        //检查操作权限
        $permit = $this->stepCheck($d_data['step'], $d_data['status'], D('Scm/Quotation')->getStatusArr($d_data['id']));
        //检查法务权限
        if (!$permit || !$this->justiceCheck($d_data['sell_team'])) {
            return false;
        }
        if ($d_data) {
            $d_data['status'] = $data['status'] ? DemandModel::$status['pass_through'] : DemandModel::$status['not_pass_through'];
            $d_data['reason'] = $data['status'] ? '' : $data['reason'];
            if ($d_demand->save($d_data) === false) {
                $d_demand->rollback();
                $this->error = '提交失败';
                return false;
            }
            if (!$this->checkAllOver(['step' => 'justice_stamp', 'type' => 'demand', 'id' => $data['id']])) {//检查当阶段是否全部完成，修改主状态
                return false;
            }
        } else {
            $this->error = '订单状态异常';
            return false;
        }
        $d_demand->commit();
        $this->code = 2000;
        //发送邮件
        $data['status'] ? null : ScmEmailModel::O1($d_data);
        return true;
    }

    /**法务盖章失败，直接提交按钮
     * @param $data
     * @return bool
     */
    public function returnStamp($data)
    {
        $d_demand = new DemandModel();
        $d_demand->startTrans();
        $d_status = DemandModel::$status;
        $d_data   = $d_demand->lock(true)->where(['id' => $data['id']])->find();
        //检查法务权限
        if (!$this->stepCheck($d_data['step'], $d_data['status'], D('Scm/Quotation')->getStatusArr($d_data['id']))) {
            return false;
        }
        if ($d_data) {
            $d_data['status'] = $d_status['untreated'];
            if ($d_demand->save($d_data) === false) {
                $d_demand->rollback();
                $this->error = '提交失败';
                return false;
            }
        } else {
            $this->error = '订单状态异常';
            return false;
        }
        $d_demand->commit();
        $this->code = 2000;
        //发送邮件
        return true;
    }

    /**
     * @param $data
     * @return bool
     * 保存法务审批意见
     */
    public function forensicAuditProposal($data)
    {
        $demand_m = D('Scm/Demand');
        $demand_m->startTrans();
        $demand = $demand_m->lock(true)->where(['id' => $data['id']])->find();
        if (!$this->stepCheck($demand['step'], $demand['status'])) {
            return false;
        }
        $res = $demand_m->where(['id' => $data['id']])->save(['forensic_audit_proposal' => $data['forensic_audit_proposal']]);
        if ($res === false) {
            $demand_m->rollback();
            $this->error = '保存失败';
            return false;
        } else {
            $demand_m->commit();
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
    public function forensicAuditEmail($id, $to, $cc)
    {
        $demand_m = D('Scm/Demand');
        $demand_m->startTrans();
        $demand = $demand_m->lock(true)->where(['id' => $id])->find();
        if (!$this->stepCheck($demand['step'], $demand['status'])) {
            return false;
        }
        $title   = "法务审核{$demand['demand_code']}";
        $content = <<<HHH
您的订单{$demand['demand_code']}上传的PO/合同已经通过法务的审核，请尽快打印邮件附件中的PO/合同，双方盖章后，提交给法务归档。<br/>
The PO/contract uploaded by your order {$demand['demand_code']} has been approved by legal. Please print the PO/contract in the email attachment as soon as possible. After the two sides stamp, it will be submitted to the legal for archive.<br/><br/>
法务审核意见：<br/>
Legal approval comments:<br/><br/>
{$demand['forensic_audit_proposal']}
HHH;
        $email   = new SMSEmail();
        if (!$email->sendEmail($to, $title, $content, $cc)) {
            $this->error = $email->getError();
            return false;
        }
        if (!empty($cc)) {
            $to = array_merge($to, $cc);
        }
        $receiver = implode(',', $to);
        if ($demand_m->where(['id' => $id])->save(['forensic_audit_email_receiver' => $receiver]) === false) {
            $demand_m->rollback();
            return false;
        }
        $demand_m->commit();
        return true;
    }

    public function watermarkToPo($id)
    {
        $demand_m = D('Scm/Demand');
        $demand_m->startTrans();
        $demand = $demand_m->lock(true)->where(['id' => $id])->find();
        if (!$this->stepCheck($demand['step'], $demand['status'])) {
            $this->error = '状态异常';
            return false;
        }
        $po = json_decode($demand['po'], true);
        //pic_watermark
        $res_watermark = $this->waterMarkToFile($po);
        if (!$res_watermark) {
            $demand_m->rollback();
            return false;
        }
        $res = $demand_m->where(['id' => $id])->save(['po_with_watermark' => json_encode($res_watermark)]);
        if ($res === false) {
            $demand_m->rollback();
            $this->error = '水印保存失败';
            return false;
        }
        $demand_m->commit();
        return $res_watermark;
    }

    /**
     * 处理申请
     * @param $id
     * @param $process_method
     * @param $approve_to_or_reason
     * @return bool
     */
    public function processApplication($id, $process_method, $approve_to_or_reason)
    {
        $demand_m = D('Scm/Demand');
        M()->startTrans();
        $this->demand = $demand = $demand_m->lock(true)->where(['id' => $id])->find();
        if (!$this->stepCheck($demand['step'], $demand['status'])) return false;
        if (!$this->isStepSync($id)) {
            $demand_m->rollback();
            return false;
        }
        switch ($process_method) {
            case self::$process_application_method['approve']:
                switch ($approve_to_or_reason) {
                    /*
                    case self::$process_application_approve_to['edit_demand']:
                        $res = $this->doReturnToDraft($id);
                        break;
                    case self::$process_application_approve_to['choose_quotation']:
                        $res = $this->doReturnToSellerChoose($id);
                        break;
                    */
                    case self::$process_application_approve_to['edit_quotation']:
                        $res = $this->doReturnToPurchaseClaim($id);
                        break;
                    default:
                        $this->error = '参数异常';
                        $res         = false;
                        break;
                }
                break;
            case self::$process_application_method['discard']:
                $res = $this->doDemandDiscard($id, $approve_to_or_reason);
                break;
            case self::$process_application_method['not_approve']:
                $e_q_list = D('Scm/Quotation')->lock(true)
                    ->field('id,quotation_code,purchaser,create_user')
                    ->where(['demand_id' => $id, 'chosen' => QuotationModel::$chosen['chosen'], 'invalid' => 0, 'status' => ['in', [QuotationModel::$status['apply_for_modification'], QuotationModel::$status['discarded']]]])
                    ->select();
                $res      = $this->processApplicationRefuse($id, $approve_to_or_reason);
                if ($res) {
                    ScmEmailModel::Y($e_q_list);
                }
                break;
            default:
                $this->error = '参数异常';
                $res         = false;
                break;
        }
        if ($res) {
            M()->commit();
        } else {
            M()->rollback();
        }
        return $res;
    }

    /**
     * 退回草稿
     * @param $id
     * @param $reason
     * @return bool
     */
    public function returnToDraft($id, $reason)
    {
        if (!$reason) {
            $this->error = '撤回需求原因必填';
            return false;
        }
        $demand_m       = D('Scm/Demand');
        $demand_goods_m = D('Scm/DemandGoods');
        $quotation_m    = D('Scm/Quotation');
        $demand_m->startTrans();
        $demand    = $demand_m->lock(true)->where(['id' => $id])->find();
        $q_list    = $quotation_m->field('purchaser,id,create_user,purchase_team,chosen')->where(['demand_id' => $id, 'invalid' => 0])->select();//修改前查询
        $quotation = $quotation_m->lock(true)->where(['demand_id' => $id, 'chosen' => QuotationModel::$chosen['chosen'], 'invalid' => 0])->select();
        if (!$this->stepCheck($demand['step'], $demand['status'], $quotation)) {
            $demand_m->rollback();
            return false;
        }
        if (!$this->isStepSync($id)) {
            $demand_m->rollback();
            return false;
        }
        if (!$this->doReturnToDraft($id)) {
            $demand_m->rollback();
            return false;
        }
        $onway_goods     = $demand_goods_m->field('sku_id,on_way_number,on_way_batch,spot_batch,spot_number')->where(['demand_id' => $id])->select();
        $is_free_spot    = false;//是否释放现货批次
        //根据采购需求商品在途批次数据区分采购在途、调拨在途
        foreach ($onway_goods as $item) {
            if ($item['spot_number'] > 0) {
                $is_free_spot = true;
            }
            if ($item['on_way_number'] <= 0) {
                continue;
            }
            $res = $this->freeOnWayBatch($demand['demand_code'], $item['sku_id'], $item['on_way_batch'], __FUNCTION__);
            if (!$res) {
                M()->rollback();
                $this->error = '释放在途批次失败';
                Logs($onway_goods, __FUNCTION__.'-释放在途批次失败', 'fm');
                return false;
            }
            if ($res) {
                $save = [
                    'on_way_number'    => 0,
                    'on_way_batch'     => '',
                    'on_way_warehouse' => '',
                ];
                $demand_goods_m->where(['demand_id' => $demand['id']])->save($save);
            }
        }
        if ($is_free_spot) {
            $res = $this->freeUpBatch($demand['demand_code'], self::$batch_type['batch']);
            if (!$res) {
                M()->rollback();
                $this->error = '释放现货批次失败';
                @SentinelModel::addAbnormal('SCM-'.__FUNCTION__, '释放现货批次失败', [$demand['demand_code'], self::$batch_type['batch'], $res],'warehouse_notice');
                return false;
            }
            if ($res) {
                $save = [
                    'spot_number'    => 0,
                    'spot_batch'     => '',
                    'spot_warehouse' => ''
                ];
                $demand_goods_m->where(['demand_id' => $demand['id']])->save($save);
            }
        }
        $demand_m->commit();
        //发送邮件
        ScmEmailModel::T($demand, $q_list, $reason);
        return true;
    }

    /**
     * 退回草稿数据保存
     * @param $id
     * @param $reason
     * @return bool
     */
    private function doReturnToDraft($id)
    {
        $demand_m          = D('Scm/Demand');
        $quotation_m       = D('Scm/Quotation');
        $quotation_goods_m = D('Scm/QuotationGoods');
        $demand_profit_m   = M('demand_profit', 'tb_sell_');
        $demand_m->startTrans();
        $res_d = $demand_m->where(['id' => $id])->save([
            'step'        => DemandModel::$step['demand_submit'],
            'status'      => DemandModel::$status['draft'],
            'po'          => '',
            'has_3rd_po'  => 1,
            'third_po_no' => '',
            'po_date'     => null,
            'po_archive'  => '',
        ]);
        if ($res_d === false) {
            $demand_m->rollback();
            $this->error = '需求状态重置失败';
            return false;
        }
        $res_goods = [];
//        $res_goods = $quotation_goods_m->where(['demand_id' => $id])->save(['_query' => 'require_number=require_number_original']);
        if ($res_goods) {
            $demand_m->rollback();
            $this->error = '重置商品需求数量失败';
            return false;
        }
        $res_q = $quotation_m->where(['demand_id' => $id, 'invalid' => 0])->save(['invalid' => 1]);
        if ($res_q === false) {
            $demand_m->rollback();
            $this->error = '报价置为失效失败';
            return false;
        }
        $res_p = $demand_profit_m->where(['demand_id' => $id])->delete();
        if ($res_p === false) {
            $demand_m->rollback();
            $this->error = '清空利润信息失败';
            return false;
        }
        $demand_m->commit();
        return true;
    }

    /**
     * 退回到待确认
     * @param $id
     * @param $reason
     * @return bool
     */
    public function returnToSellerChoose($id, $reason = '')
    {
        $demand_m    = D('Scm/Demand');
        $quotation_m = D('Scm/Quotation');
        $demand_m->startTrans();
        $demand    = $demand_m->lock(true)->where(['id' => $id])->find();
        $quotation = $quotation_m
            ->lock(true)
            ->where([
                'demand_id' => $id,
                'chosen'    => QuotationModel::$chosen['chosen'],
                'invalid'   => 0
            ])
            ->select();
        if (!$this->stepCheck($demand['step'], $demand['status'], $quotation)) {
            $demand_m->rollback();
            return false;
        }
        if (!$this->isStepSync($id)) {
            $demand_m->rollback();
            return false;
        }
        //保存数据
        if (!$this->doReturnToSellerChoose($id, $reason)) {
            $demand_m->rollback();
            return false;
        }
        $demand_m->commit();
        return true;
    }

    /**
     * 退回到待确认保存数据
     * @param $id
     * @param $reason
     * @return bool
     */
    private function doReturnToSellerChoose($id, $reason = '')
    {
        $demand_m          = D('Scm/Demand');
        $quotation_m       = D('Scm/Quotation');
        $quotation_goods_m = D('Scm/QuotationGoods');
        $demand_profit_m   = M('demand_profit', 'tb_sell_');
        $demand_m->startTrans();
        $res_d = $demand_m
            ->where(['id' => $id])
            ->save([
                'step'        => DemandModel::$step['seller_choose'],
                'status'      => DemandModel::$status['untreated'],
                'reason'      => $reason,
                'po'          => '',
                'has_3rd_po'  => 1,
                'third_po_no' => '',
                'po_date'     => null,
                'po_archive'  => '',
            ]);
        if ($res_d === false) {
            $demand_m->rollback();
            $this->error = '需求状态重置失败';
            return false;
        }
        //报价置为待选择
        $res_q = $quotation_m
            ->where(['demand_id' => $id, 'invalid' => 0, 'status' => ['neq', QuotationModel::$status['draft']]])
            ->save([
                'step'            => DemandModel::$step['seller_choose'],
                'chosen'          => QuotationModel::$chosen['to_choose'],
                'status'          => QuotationModel::$status['no_need_to_treat'],
                'pur_account'     => '',
                'pur_order_no'    => '',
                'po'              => '',
                'po_archive'      => '',
                'legal_man'       => '',
                'request_advance' => 0,
            ]);

        //报价商品被选择数量置为0
        $quotation_ids = $quotation_m
            ->where(['demand_id' => $id, 'invalid' => 0])
            ->getField('id', true);
        $res_q_g       = $quotation_goods_m
            ->where(['quotation_id' => ['in', $quotation_ids]])
            ->save(['choose_number' => 0]);
        if ($res_q === false || $res_q_g === false) {
            $demand_m->rollback();
            $this->error = '报价置为失效失败';
            return false;
        }
        $res_p = $demand_profit_m->where(['demand_id' => $id])->delete();
        if ($res_p === false) {
            $demand_m->rollback();
            $this->error = '清空利润信息失败';
            return false;
        }
        //更新报价ce
        $demand           = $demand_m->where(['id' => $id])->find();
        $quotation_ce_res = $quotation_m->updateCE($demand);
        if ($quotation_ce_res == false) {
            $this->error = $quotation_m->getError();
            return false;
        }
        $demand_m->commit();
        return true;
    }

    private function processApplicationRefuse($id, $reason)
    {
        $demand_m    = D('Scm/Demand');
        $quotation_m = D('Scm/Quotation');
        $demand_m->startTrans();
        //需求状态重置为申请前
        $res_d = $demand_m
            ->where(['id' => $id])
            ->save([
                'status'      => ['exp', 'last_status'],
                'last_status' => '',
                'reason'      => $reason
            ]);
        if (!$res_d) {
            $demand_m->rollback();
            $this->error = '重置需求进度败';
            return false;
        }
        $where_q = [
            'demand_id' => $id,
            'chosen'    => QuotationModel::$chosen['chosen'],
            'invalid'   => 0,
            'status'    => ['in', [QuotationModel::$status['apply_for_modification'], QuotationModel::$status['discarded']]]
        ];
        $save_q  = [
            'status'      => ['exp', 'last_status'],
            'last_status' => '',
            'reason'      => $reason
        ];
        $res_q   = $quotation_m->where($where_q)->save($save_q);
        if (!$res_q) {
            $demand_m->rollback();
            $this->error = '重置采购进度失败';
            return false;
        }
        $demand_m->commit();
        return true;
    }

    /**
     * 退回到提交报价
     * @param $id
     * @return bool
     */
    private function doReturnToPurchaseClaim($id)
    {
        $demand_m          = D('Scm/Demand');
        $quotation_m       = D('Scm/Quotation');
        $quotation_goods_m = D('Scm/QuotationGoods');
        $demand_profit_m   = M('demand_profit', 'tb_sell_');
        $demand_m->startTrans();
        $res_d = $demand_m
            ->where(['id' => $id])
            ->save([
                'step'        => DemandModel::$step['purchase_claim'],
                'status'      => DemandModel::$status['no_need_to_treat'],
                'po'          => '',
                'has_3rd_po'  => 1,
                'third_po_no' => '',
                'po_date'     => null,
                'po_archive'  => '',
            ]);
        if ($res_d === false) {
            $demand_m->rollback();
            $this->error = '需求状态重置失败';
            return false;
        }
        //报价置为待选择
        $res_q = $quotation_m
            ->where(['demand_id' => $id, 'invalid' => 0])
            ->save([
                'step'            => DemandModel::$step['purchase_claim'],
                'chosen'          => '',
                'status'          => QuotationModel::$status['draft'],
                'po'              => '[]',
                'po_archive'      => '[]',
                'legal_man'       => '',
                'invalid'         => 0,
                'request_advance' => 0,
            ]);

        //报价商品被选择数量置为0
        $quotation_ids = $quotation_m
            ->where(['demand_id' => $id, 'invalid' => 0])
            ->getField('id', true);
        $res_q_g       = $quotation_goods_m
            ->where(['quotation_id' => ['in', $quotation_ids]])
            ->save(['choose_number' => 0]);
        if ($res_q === false || $res_q_g === false) {
            $demand_m->rollback();
            $this->error = '报价置为失效失败';
            return false;
        }
        $res_p = $demand_profit_m->where(['demand_id' => $id])->delete();
        if ($res_p === false) {
            $demand_m->rollback();
            $this->error = '清空利润信息失败';
            return false;
        }
        //更新报价ce
        $demand           = $demand_m->where(['id' => $id])->find();
        $quotation_ce_res = $quotation_m->updateCE($demand);
        if ($quotation_ce_res == false) {
            $this->error = $quotation_m->getError();
            return false;
        }
        $demand_m->commit();
        //发送邮件
        $q_list = $quotation_m->field('purchaser,quotation_code,id,create_user')->where(['demand_id' => $id, 'status' => QuotationModel::$status['draft'], 'invalid' => 0])->select();
        ScmEmailModel::X($q_list);
        return true;
    }

    public function createOrders($id)
    {
        M()->startTrans();
        $demand_m     = D('Scm/Demand');
        $quotation_m  = D('Scm/Quotation');
        $this->demand = $demand = $demand_m->lock(true)->where(['id' => $id])->find();
        $quotation    = $quotation_m->where(['demand_id' => $id, 'invalid' => 0, 'chosen' => QuotationModel::$chosen['chosen'], 'step' => QuotationModel::$step['create_order']])->select();
//        if(!$this->stepCheck($demand['step'],$demand['status'],$quotation)) return false;
        $demand_save = ['status' => DemandModel::$status['pass_through']];
        if (in_array($demand['demand_type'], [DemandModel::$demand_type_sell, DemandModel::$demand_type_self_sell])) {
            //内部交易也可以生成B2B订单，原本只有销售订单才会
            if (!$b2b_res = $this->createB2bOrder($id)) {
                M()->rollback();
                return false;
            }
            $demand_save['b2b_order_id'] = $b2b_res;
        }
        $res_d = $demand_m->where(['id' => $id])->save($demand_save);
        if (!$res_d) {
            M()->rollback();
            $this->error = 'B2B订单关联失败';
            return false;
        }
        $quotation_error = false;
        foreach ($quotation as $v) {
            if (!$quotation_m->createOrder(['id' => $v['id']])) {
                $quotation_error = true;
                break;
            }
        }
        if ($quotation_error) {
            M()->rollback();
            $this->error = $quotation_m->getError();
            return false;
        }
        if (!$this->checkAllOver(['type' => 'demand', 'id' => $id, 'step' => array_flip(DemandModel::$step)[$demand['step']]])) {
            $this->error = '进度完成失败';
            M()->rollback();
            return false;
        }
        M()->commit();
        return true;
    }

    public function createB2bOrder($id)
    {
        M()->startTrans();
//        $redis                      = RedisModel::connect_init();
//        $rates                      = json_decode($redis->get('xchr_'.date('Ymd')),true);
//        $demand_m                   = D('Scm/Demand');
        $demand       = $this->demand;
        $profit       = M('demand_profit', 'tb_sell_')->where(['demand_id' => $id])->find();
        $demand_goods = D('Scm/DemandGoods')->where(['demand_id' => $id])->select();
//        $quotation                  = D('Scm/Quotation')
//            ->field('expense,expense_currency')
//            ->where(['demand_id'=>$id,'chosen'=>QuotationModel::$chosen['chosen'],'invalid'=>0])
//            ->select();
        $quotation_goods        = D('Scm/QuotationGoods')
            ->alias('t')
            ->field('t.choose_number,t.purchase_price,t.drawback_percent,a.currency,t.sell_price')
            ->join('tb_sell_quotation as a on a.id = t.quotation_id')
            ->where(['demand_id' => $id, 'chosen' => QuotationModel::$chosen['chosen'], 'invalid' => 0, 'choose_number' => ['neq', 0]])
            ->select();
        $poData                 = [];
        $poData['poNum']        = $demand['demand_code'];
        $poData['clientName']   = $demand['customer'];
        $poData['clientNameEN'] = M('sp_supplier', 'tb_crm_')
            ->where(['SP_CHARTER_NO' => $demand['customer_charter_no'], 'DATA_MARKING' => 1])
            ->getField('SP_RES_NAME_EN');
        $poData['busLice']      = $demand['customer_charter_no'];
        $poData['contract']     = $demand['contract'];
        $poData['ourCompany']   = cdVal($demand['our_company']);
        $poData['ourCompanyCd']   = $demand['our_company'];
        $poData['poAmount']     = $demand['sell_amount'];
        $poData['BZ']           = $demand['sell_currency'];
        $poScanner              = [];
        foreach (json_decode($demand['po_archive'], true) as $v) {
            $poScanner[] = ['file_path' => $v['save_name'], 'file_name' => $v['original_name']];
        }
        $poData['poScanner'] = json_encode($poScanner);
        $poData['poTime']    = date('Y-m-d');
        $poData['shipping']  = $demand['receive_mode'];
        switch ($demand['collection_cycle']) {
            case 'N002170100' :
                $poData['cycleNum'] = 1;
                break;
            case 'N002170200' :
                $poData['cycleNum'] = 2;
                break;
            case 'N002170300' :
                $poData['cycleNum'] = 3;
                break;
        }
        $poData['sale_tax']    = round($profit['tax'] + $profit['tax_spot'], 2);
        $poData['cur_saletax'] = TbMsCmnCdModel::$currency_usd;
        $collection_time       = [];
        foreach (json_decode($demand['collection_time'], true) as $k => $v) {
            $node                = [];
            $node['nodei']       = $k;
            $node['nodeType']    = $v['node'];
            $node['nodeDate']    = $v['days'];
            $node['nodeWorkday'] = 0;
            $node['nodeProp']    = $v['percent'];
            $collection_time[]   = $node;
        }
        $poData['poPaymentNode']  = $collection_time;
        $poData['country']        = $demand['receive_country'];
        $poData['province']       = $demand['receive_province'];
        $poData['city']           = $demand['receive_city'];
        $poData['detailAdd']      = $demand['receive_address'];
        $poData['saleTeam']       = $demand['sell_team'];
        $poData['Remarks']        = $demand['remark'];
        $poData['invioce']        = $demand['invoice_type'];
        $poData['tax_point']      = $demand['tax_rate'];
        $poData['other_income']   = $demand['other_income'];
        $poData['cur_other']      = $demand['other_income_currency'];
        $poData['business_type']  = $demand['business_mode'];
        $poData['scm_send_type']  = $demand['need_warehouse'];
        $poData['scm_order_meta'] = $demand['order_source'];
        $poData['ship_date']      = $demand['ship_date'];
        $poData['lastname']       = $demand['seller'];
        $poData['thrPoNum']       = $demand['third_po_no'];
        $poData['poTime']         = $demand['po_date'];
        $poData['delivery_time']  = $demand['ship_date'];
        $skuData                  = [];
        $cost                     = 0;
        foreach ($demand_goods as $v) {
            if ($v['require_number']) {
                $sku                     = [];
                $sku['skuId']            = $v['sku_id'];
                $sku['gudsName']         = $v['goods_name'];
                $sku['skuInfo']          = $v['sku_attribute'];
                $sku['selCurrency']      = $demand['sell_currency'];
                $sku['gudsPrice']        = $v['sell_price'];
                $sku['STD_XCHR_KIND_CD'] = $demand['sell_currency'];
                $sku['GUDS_OPT_ORG_PRC'] = $v['sell_price'];
                $sku['demand']           = $v['require_number'];
                $sku['subTotal']         = round($v['sell_price'] * $v['require_number'], 2);
                $sku['toskuid']          = $v['sku_id'];
                $sku['scm_batch_json']   = $v['spot_batch'];
                $sku['batch_prices']     = [];
                if ($spot = json_decode($v['spot_batch'], true)) {
                    foreach ($spot as $val) {
                        $cost                         += $val['unit_price_usd'] * $val['num'];
                        $batch                        = M('batch', 'tb_wms_')
                            ->alias('t')
                            ->field('t.id batch_id,batch_code,purchase_team_code purchasing_team,SP_JS_TEAM_CD introduce_team,tax_rate purchase_invoice_tax_rate,unit_price purchasing_price')
                            ->join('tb_wms_stream a on a.id=t.stream_id')
                            ->join('tb_pur_order_detail b on b.procurement_number=t.purchase_order_no')
                            ->join('tb_crm_sp_supplier c on c.SP_CHARTER_NO=b.sp_charter_no and c.DATA_MARKING=0')
                            ->where(['t.id' => $val['id']])
                            ->find();
                        $batch['purchasing_num']      = $val['num'];
                        $batch['purchasing_currency'] = TbMsCmnCdModel::$currency_cny;
                        $batch['sku_drawback']        = bcmul($val['proportionOfTax'], 100);
                        $sku['batch_prices'][]        = $batch;
                    }
                }
                if ($on_way = json_decode($v['on_way_batch'], true)) {
                    foreach ($on_way as $val) {
                        $cost                         += $val['unit_price_usd'] * $val['num'];
                        $batch                        = M('batch', 'tb_wms_')
                            ->alias('t')
                            ->field('t.id batch_id,batch_code,purchase_team_code purchasing_team,SP_JS_TEAM_CD introduce_team,tax_rate purchase_invoice_tax_rate,unit_price purchasing_price')
                            ->join('tb_wms_stream a on a.id=t.stream_id')
                            ->join('tb_pur_order_detail b on b.procurement_number=t.purchase_order_no')
                            ->join('tb_crm_sp_supplier c on c.SP_CHARTER_NO=b.sp_charter_no and c.DATA_MARKING=0')
                            ->where(['t.id' => $val['id']])
                            ->find();
                        $batch['purchasing_num']      = $val['num'];
                        $batch['purchasing_currency'] = TbMsCmnCdModel::$currency_cny;
                        $batch['sku_drawback']        = bcmul($val['proportionOfTax'], 100);
                        $sku['batch_prices'][]        = $batch;
                    }
                }
                $sku['delivery_prices'] = D('Scm/QuotationGoods')
                    ->alias('t')
                    ->field('a.purchase_team as purchasing_team,b.SP_JS_TEAM_CD as introduce_team,t.purchase_price as purchasing_price,a.quotation_code as procurement_number,a.currency as purchasing_currency,a.tax_rate purchase_invoice_tax_rate,t.choose_number purchasing_num,left(d.CD_VAL,length(d.CD_VAL)-1) sku_drawback')
                    ->join('tb_sell_quotation as a on a.id = t.quotation_id')
                    ->join('tb_crm_sp_supplier b on b.SP_CHARTER_NO=a.supplier_no and b.DATA_MARKING=0')
                    ->join('tb_sell_demand_goods c on c.id=t.demand_goods_id')
                    ->join('tb_ms_cmn_cd d on d.CD=t.drawback_percent')
                    ->where(['demand_goods_id' => $v['id'], 'chosen' => QuotationModel::$chosen['chosen'], 'invalid' => 0, 'choose_number' => ['neq', 0]])
                    ->select();
                $skuData[]              = $sku;
            }
        }
        foreach ($quotation_goods as $v) {
            $rate = exchangeRateToUsd(cdVal($v['currency']), date('Ymd', strtotime($demand['po_date'])));
            $cost += $v['purchase_price'] * $v['choose_number'] * $rate;
        }
        $poData['backend_estimat']        = round($cost, 2);
        $poData['backend_currency']       = TbMsCmnCdModel::$currency_usd;
        $poData['drawback_estimate']      = round($profit['tax_refund'] + $profit['tax_refund_spot'], 2);
        $poData['logistics_currency']     = TbMsCmnCdModel::$currency_usd;
        $poData['logistics_estimat']      = round($profit['total_cost'] + $profit['sale_cost_spot'], 2);
        $poData['logistics_currency']     = TbMsCmnCdModel::$currency_usd;
        $poData['rebate_rate']            = $demand['rebate_rate'] ?: 0;
        $poData['rebate_amount']          = $demand['rebate_amount'] ?: 0.00;

        //预估物流成本(logistics_estimat，insight计算使用所需，需求：11408 物流成本和返利金新增字段区分
        $logistics_estimat_cost = $this->calculateLogisticsEstimatCost($profit, $demand, $poData['logistics_estimat']);
        $poData['logistics_estimat_cost'] = $logistics_estimat_cost ? : 0.00;

        $b2b_data = ['poData' => $poData, 'skuData' => $skuData];
        $res = A('Home/B2b')->scmCreate($b2b_data);
        if ($res['code'] != 200) {
            M()->rollback();
            ELog::add(['msg' => '创建B2B订单失败', 'request' => $b2b_data, 'response' => $res], ELog::ERR);
            $this->error = '创建B2B订单失败';
            return false;
        }
        M()->commit();
        return $res['order_id'];
    }

    public function freeUpBatch($order_id, $type = '', $skus = [])
    {
        $param = [];
        if (!empty($skus)) {
            foreach ($skus as $v) {
                if ($type == self::$batch_type['allo_on_way']) {
                    $sku = ['orderId' => $order_id, 'virType' => $type, 'skuId' => $v['sku_id'], 'alloNo' => $v['on_way_batch']['purchaseOrderNo'], 'releaseNum' => $v['on_way_number']];
                } else {
                    $sku = ['orderId' => $order_id, 'virType' => $type, 'skuId' => $v['sku_id'], 'releaseNum' => $v['on_way_number']];
                }
                $param[] = $sku;
            }
        } else {
            if ($type == self::$batch_type['on_way']) return true;
            $param[] = ['orderId' => $order_id, 'virType' => $type];
        }
        $res = ApiModel::freeUpBatch($param);
        if (!in_array($res['code'], [2000, 40001011])) return false;
        return true;
    }

    public function flushDealDetail($demand_id_or_code, $type = 0)
    {
        $demand_id = $type ? $demand_id = D('Scm/Demand')->where(['demand_code' => $demand_id_or_code])->getField('id') : $demand_id_or_code;
        $res       = $this->saveDealDetail($demand_id);
        if (!$res) ELog::add($this->error);
        return $res;
    }

    public function saveDealDetail($demand_id)
    {
        $demand_m       = D('Scm/Demand');
        $demand_goods_m = D('Scm/DemandGoods');
        $quotation_m    = D('Scm/Quotation');
        $deal_detail_m  = D('Scm/DealDetail');
        $demand         = $demand_m
            ->field('demand_code,demand_type,is_spot,business_mode,customer,receive_mode,receive_country,receive_province,sell_currency,tax_rate,step,deal_time')
            ->where(['id' => $demand_id])
            ->find();
        if (!$demand) {
            $this->error = 'ID为' . $demand_id . '的需求不存在';
            return false;
        }
        $demand_goods = $demand_goods_m->field('id,sku_id,bar_code,sell_price,spot_number,on_way_number,require_number')->where(['demand_id' => $demand_id])->select();
//        if($demand['step'] != DemandModel::$step['success']) {
//            $this->error = $demand['demand_code'].'需求未成功结束';
//            return false;
//        }

        $demand_m->startTrans();

        $res_del = $deal_detail_m->where(['demand_code' => $demand['demand_code']])->delete();
        if ($res_del === false) {
            $this->error = '删除历史明细失败';
            $demand_m->rollback();
            return false;
        }
        foreach ($demand_goods as $v) {
            if ($demand['demand_type'] == DemandModel::$demand_type_sell) {
                $tax_rate                        = rtrim(cdVal($demand['tax_rate'], '%')) / 100;
                $v['sell_price_not_contain_tax'] = $v['sell_price'] / (1 + $tax_rate);
                $v['sell_price_tax']             = $v['sell_price_not_contain_tax'] * $tax_rate;
            } else {
                unset($demand['sell_currency'], $demand['receive_mode'], $demand['receive_country'], $demand['receive_province'], $demand['customer']);
            }
            $quotation_goods_info = $quotation_m
                ->alias('t')
                ->field('supplier,delivery_type,warehouse,currency,purchase_price,tax_rate')
                ->join('inner join tb_sell_quotation_goods a on a.quotation_id=t.id')
                ->where([
                    'demand_id'       => $demand_id,
                    'chosen'          => QuotationModel::$chosen['chosen'],
                    'invalid'         => 0,
                    'demand_goods_id' => $v['id']
                ])
                ->select();
            foreach ($quotation_goods_info as $v_q) {
                if ($demand['demand_type'] == DemandModel::$demand_type_sell) {
                    $demand['deal_type'] = DemandModel::$deal_type['mix'];
                } else {
                    $demand['deal_type'] = DemandModel::$deal_type['store_goods'];
                }
                $quotation_tax_rate                    = rtrim(cdVal($v_q['tax_rate'], '%')) / 100;
                $v_q['purchase_price_not_contain_tax'] = $v_q['purchase_price'] / (1 + $quotation_tax_rate);
                $v_q['purchase_price_tax']             = $v_q['sell_price_not_contain_tax'] * $quotation_tax_rate;
                $save_arr                              = array_merge($demand, $v, $v_q);
                unset($save_arr['id']);
                $save_arr = $deal_detail_m->create($save_arr);
                if ($save_arr === false || $deal_detail_m->add() === false) {
                    $this->error = '交易明细保存失败';
                    $demand_m->rollback();
                    return false;
                }

            }
            if ($v['spot_number'] || $v['on_way_number']) {
                $demand['deal_type'] = DemandModel::$deal_type['sell_store'];
                $save_arr            = array_merge($demand, $v);
                unset($save_arr['id']);
                $save_arr = $deal_detail_m->create($save_arr);
                if ($save_arr === false || $deal_detail_m->add() === false) {
                    $this->error = '交易明细保存失败';
                    $demand_m->rollback();
                    return false;
                }
            }
        }
        $demand_m->commit();
        return true;
    }

    public function saveAllDealDetail()
    {
        $demand_id_arr = D('Scm/Demand')->where(['step' => DemandModel::$step['success']])->getField('id', true);
        M()->startTrans();
        foreach ($demand_id_arr as $v) {
            $res = $this->saveDealDetail($v);
            if (!$res) {
                ELog::add('保存交易详情失败:' . $this->error);
                M()->rollback();
                return false;
            }
        }
        M()->commit();
        return true;
    }

    public function checkPredestinateNum($demand_code)
    {
        return true;
    }

    //释放在途批次
    private function freeOnWayBatch($demand_code, $sku_id, $on_way_batch, $function_name)
    {
        $param = [];
        $sum_allo_num = $sum_pur_num = 0;
        $allo_flag = $pur_flag = false;
        $batch_order_model = M('batch_order', 'tb_wms_');
        $on_way_batch      = json_decode($on_way_batch, true);
        foreach ($on_way_batch as $item) {
            $num = $item['num'];
            if ($item['type'] == "1") {
                $sum_allo_num += $num;
                $allo_flag = true;
            } else {
                $sum_pur_num += $num;
                $pur_flag = true;

            }
        }
        if ($allo_flag) {
            $where  = [
                'ORD_ID'   => $demand_code,
                'use_type' => 1,
                'SKU_ID'   => $sku_id,
                'vir_type' => 'N002410200',
            ];
            $occupy_num = $batch_order_model->where($where)->sum('occupy_num');
            if ($occupy_num) {
                if ($occupy_num < $sum_allo_num) {
                    $sum_allo_num = $occupy_num;
                }
                //调拨在途
                $param[] = [
                    "orderId"    => $demand_code,
                    "virType"    => 'N002410200',
                    "skuId"      => $sku_id,
                    "alloNo"     => '',
                    "releaseNum" => $sum_allo_num
                ];
            }
        }
        if ($pur_flag) {
            $where  = [
                'ORD_ID'   => $demand_code,
                'use_type' => 1,
                'SKU_ID'   => $sku_id,
                'vir_type' => 'N002440200',
            ];
            $occupy_num = $batch_order_model->where($where)->sum('occupy_num');
            if ($occupy_num) {
                if ($occupy_num < $sum_pur_num) {
                    $sum_pur_num = $occupy_num;
                }
                //采购在途
                $param[] = [
                    "orderId"    => $demand_code,
                    "virType"    => 'N002440200',
                    "skuId"      => $sku_id,
                    "releaseNum" => $sum_pur_num
                ];
            }
        }
        if (!empty($param)) {
            $res = ApiModel::freeUpBatch($param);
            Logs([$param, $res], __FUNCTION__.'-----释放在途', 'fm');
            if (!in_array($res['code'], [2000, 40001011])) {
                @SentinelModel::addAbnormal('SCM-' . $function_name, '释放在途库存失败', [$param, $res], 'warehouse_notice');
                return false;
            }
        }
        return true;
    }

    //检查在途可预订库存是否足够
    private function checkOnWayBatchNum($demand_goods)
    {
        $where_str_allo  = '';
        $pur_batch_ids   = [];
        foreach ($demand_goods as &$value) {
            if (!is_array($value['on_way_batch'])) {
                $value['on_way_batch'] = json_decode($value['on_way_batch'], true);
            }
            foreach ($value['on_way_batch'] as $item) {
                if ($item['type'] == '1') {
                    //调拨在途
                    $where_str_allo .= sprintf("(wb.id = '%s' AND ba.allo_no = '%s') OR ", $item['id'], $item['purchaseOrderNo']);
                } else {
                    $pur_batch_ids[]  = $item['id'];
                }
            }
        }
        $model = new Model();
        if ($where_str_allo) {
            $list = $model->table('tb_wms_batch wb')
                ->field('wb.id,ba.available_for_sale_num,ba.allo_no')
                ->join('inner join tb_wms_batch_allo ba on wb.id = ba.batch_id')
                ->where(trim($where_str_allo, 'OR '))
                ->select();
            foreach ($list as $v) {
                $allo_batch_map[$v['id']][$v['allo_no']] = $v['available_for_sale_num'];
            }
        }
        if ($pur_batch_ids) {
            $pur_batch_map = M('batch', 'tb_wms_')->where(['id' => ['in', $pur_batch_ids]])->getField('id,available_for_sale_num');
        }

        foreach ($demand_goods as $value) {
            foreach ($value['on_way_batch'] as $item) {
                if ($item['type'] == '1') {
                    //调拨在途
                    if ($item['num'] > $allo_batch_map[$item['id']][$item['purchaseOrderNo']]) {
                        $this->error = '调拨单号：'. $item['purchaseOrderNo'].'可预定数量不足';
                        @SentinelModel::addAbnormal('SCM-' . __FUNCTION__, $this->error, [$item, $allo_batch_map], 'warehouse_notice');
                        return false;
                    }
                } else {
                    if ($item['num'] > $pur_batch_map[$item['id']]) {
                        $this->error = '采购单号：'. $item['purchaseOrderNo'].'可预定数量不足';
                        @SentinelModel::addAbnormal('SCM-' . __FUNCTION__, $this->error, [$item, $pur_batch_map], 'warehouse_notice');
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * //预估物流成本(logistics_estimat，insight计算使用所需，需求：11408 物流成本和返利金新增字段区分
     * @param $profit
     * @param $demand
     * @param $logistics_estimat 总物流费用（包括采购和现货）
     * @return float|string
     */
    private function calculateLogisticsEstimatCost($profit, $demand, $logistics_estimat)
    {
        $pur_cost = $spot_cost = 0.00;
        if ($demand['rebate_rate'] > 0) {
            //采购部分扣除返利
            if ($profit['total_cost'] > 0) {
                if ($profit['revenue'] > 0) {
                    $pur_cost = bcsub($profit['total_cost'], $profit['revenue'] * $demand['rebate_rate'], 2);
                } else {
                    $pur_cost = $profit['total_cost'];
                }
            }
            //现货部分扣除返利
            if ($profit['sale_cost_spot'] > 0) {
                if ($profit['revenue_spot'] > 0) {
                    $spot_cost = bcsub($profit['sale_cost_spot'], $profit['revenue_spot'] * $demand['rebate_rate'], 2);
                } else {
                    $spot_cost = $profit['sale_cost_spot'];
                }
            }
            $logistics_estimat_cost = $pur_cost + $spot_cost;//扣除返利后的总物流费用
        } else {
            $logistics_estimat_cost = $logistics_estimat;
        }
        return $logistics_estimat_cost;
    }
}