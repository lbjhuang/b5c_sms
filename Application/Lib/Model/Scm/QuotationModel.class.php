<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/8
 * Time: 13:26
 */

class QuotationModel extends BaseModel
{
    protected $trueTableName = 'tb_sell_quotation';

    protected $seller;

    protected $_link = [
        'TbSellQuotationGoods' => [
            'mapping_type' => HAS_MANY,
            'foreign_key' => 'quotation_id',
            'mapping_name'=>'goods',
        ]
    ];

    static $step = [
        'purchase_claim'    => 'N002120300',//待采购认领
        'seller_choose'     => 'N002120400',//待销售选择
        'purchase_confirm'  => 'N002120500',//待采购确认
        'seller_submit'     => 'N002120600',//待销售提交
        'leader_approve'    => 'N002120700',//待销售领导审批
        'ceo_approve'       => 'N002120800',//待CEO审批
        'upload_po'         => 'N002120900',//待上传PO
        'justice_approve'   => 'N002121000',//待法务审批
        'create_order'      => 'N002121300',//待创建订单
        'success'           => 'N002121400'//成功结束
    ];

    static $chosen = [
        'to_choose'     => 'N002140100',//待选择
        'chosen'        => 'N002140300',//已中标
        'not_chosen'    => 'N002140400'//已落选
    ];

    static $status = [
        'draft'                     => 'N002150100',//草稿
        'pass_through'              => 'N002150200',//处理通过
        'untreated'                 => 'N002150300',//未处理
        'no_need_to_treat'          => 'N002150400',//无需处理
        'not_pass_through'          => 'N002150500',//审批失败
        'apply_for_modification'    => 'N002150600',//已申请修改
        'discarded'                 => 'N002150700'//已弃单
    ];

    static $type_normal = 'N001890100';//普通采购
    static $type_online = 'N001890200';//线上采购

    static $ce_level = ['S','A','B','C','D','F'];

    public function quotationList($param,$limit = '') {
        $where = $this->getWhere($param);
        $cd_map = TbMsCmnCdModel::getInstance()->getKeysMap([
            TbMsCmnCdModel::$sell_demand_type_cd_pre,
            TbMsCmnCdModel::$purchase_team_cd_pre,
            TbMsCmnCdModel::$currency_cd_pre,
            TbMsCmnCdModel::$scm_step_cd_pre,
            TbMsCmnCdModel::$scm_quotation_status_cd_pre,
        ]);
        $list = $this->alias('t')
            ->field('t.id,quotation_code,demand_type,supplier,substring_index(group_concat(spu_name ORDER BY abs(right(language,6)-'.substr(TbMsCmnCdModel::$language_english_cd,-6).') desc ),",",1) goods_name,amount,currency,t.profit_margin,t.ce_level,purchase_team,t.status,t.create_time,t.step')
            ->join('left join tb_sell_demand a on a.id= t.demand_id')
            ->join('left join tb_sell_quotation_goods b on b.quotation_id=t.id')
            ->join('left join tb_sell_demand_goods c on c.id=b.demand_goods_id')
            ->join('left join '.PMS_DATABASE.'.product_sku d on d.sku_id=c.sku_id')
            ->join('left join '.PMS_DATABASE.'.product_detail e on e.spu_id=d.spu_id and e.language in ("'.implode('","',PmsBaseModel::getLangCondition()).'")')
            ->where($where)
            ->group('t.id')
            ->order('t.id desc')
            ->limit($limit)
            ->select();
        foreach ($list as &$v) {
            $v['demand_type'] = $cd_map[$v['demand_type']];
            $v['currency'] = $cd_map[$v['currency']];
            $v['purchase_team'] = $cd_map[$v['purchase_team']];
            $v['status'] = $cd_map[$v['status']];
            $v['step'] = $cd_map[$v['step']];
        }
        unset($v);
        return $list;
    }

    public function quotationCount($param) {
        $where = $this->getWhere($param);
        $sql   = $this
            ->alias('t')
            ->field('t.id')
            ->join('left join tb_sell_demand a on a.id= t.demand_id')
            ->join('left join tb_sell_quotation_goods b on b.quotation_id=t.id')
            ->join('left join tb_sell_demand_goods c on c.id=b.demand_goods_id')
            ->join('left join '.PMS_DATABASE.'.product_sku d on d.sku_id=c.sku_id')
            ->join('left join '.PMS_DATABASE.'.product_detail e on e.spu_id=d.spu_id and e.language in ("'.implode('","',PmsBaseModel::getLangCondition()).'")')
            ->where($where)
            ->group('t.id')
            ->buildSql();
        return M()->table($sql.' a')->count();
    }

    public function getWhere($param) {
        $where = ['invalid' => 0];
        if (isset($param['status'])) {
            $where['t.status'] = ['in', $param['status']];
        }
        if (isset($param['ce_level'])) {
            $where['t.ce_level'] = ['in', $param['ce_level']];
        }
        if (isset($param['step'])) {
            $where['t.step'] = ['in', $param['step']];
        }
        if (!empty($param['legal_man'])) {
            $where['_string'] = "t.id in (select quotation_id from tb_sell_action_log where user = '{$param['legal_man']}' and quotation_id > 0 and info in ('需求侧-法务审批','需求侧-法务盖章','需求侧-归档PO','报价侧-法务审批','报价侧-法务盖章','报价测-归档PO'))";
        }
        if($param['keyword']) {
            if($param['keyword_type'] == 0) {
                $where['e.spu_name'] = ['like','%'.$param['keyword'].'%'];
            }elseif($param['keyword_type'] == 1) {
                $complex['b.search_id']   = $param['keyword'];
                $complex['b.sku_id']      = $param['keyword'];
                $complex['d.upc_id']      = $param['keyword'];
                $complex['_string'] = "FIND_IN_SET('{$param['keyword']}',d.upc_more)";
                $complex['_logic']      = 'or';
                $where['_complex']      = $complex;
            }
        }
        if($param['min_profit_margin'] && $param['max_profit_margin']) {
            $where['profit_margin'] = ['between',[$param['min_profit_margin'],$param['max_profit_margin']]];
        }elseif($param['min_profit_margin']) {
            $where['profit_margin'] = ['egt',$param['min_profit_margin']];
        }elseif($param['max_profit_margin']) {
            $where['profit_margin'] = ['elt',$param['max_profit_margin']];
        }
        if($param['min_create_time'] && $param['max_create_time']) {
            $where['t.create_time'] = ['between',[$param['min_create_time'],$param['max_create_time'] . ' 23:59:59']];
        }elseif($param['min_create_time']) {
            $where['t.create_time'] = ['egt',$param['min_create_time']];
        }elseif($param['max_create_time']) {
            $where['t.create_time'] = ['elt',$param['max_create_time'] . ' 23:59:59'];
        }
        unset($param['ce_level'], $param['status'], $param['keyword'], $param['keyword_type'], $param['min_profit_margin'], $param['max_profit_margin'], $param['min_create_time'], $param['max_create_time']);
        foreach ($param as $k => $v) {
            if (!in_array($k, [ 'chosen', 'status', 'source_country', 'payment_cycle', 'ce_level', 'quotation_code', 'supplier', 'purchaser', 'purchase_team', 'min_profit_margin', 'max_profit_margin', 'min_create_time', 'max_create_time'])) {
                continue;
            }
            if (in_array($k, ['quotation_code', 'supplier', 'purchaser'])) {
                $where[$k] = ['like', '%' . $v . '%'];
                continue;
            }
            if (is_array($v) && !empty($v)) {
                $where[$k] = ['in', $v];
            } elseif (!empty($v) || $v === 0 || $v === '0') {
                $where[$k] = $v;
            }
        }
        return $where;
    }

    public function updateQuotation($quotation) {
        //有demand_id为编辑需求
        $quotation_status = $this->where(['id'=>$quotation['id']])->getField('status');
        if($quotation_status != self::$status['draft']) {
            $this->error = '只有草稿状态的需求可以编辑';
            return false;
        }
        return $this->where(['id'=>$quotation['id']])->save($this->create($quotation));
    }

    public function addQuotation($quotation) {
        //生成demand_code
        if (empty($quotation['demand_id'])) {
            $this->error = '主需求不存在';
            return false;
        }
        $this->startTrans();
        $quotation['create_user'] = $_SESSION['m_loginname'];
        $demand = D('Demand')
            ->field('demand_code,step')
            ->where(['id'=>$quotation['demand_id']])
            ->find();
        $last_quotation_code = $this
            ->lock(true)
            ->where(['quotation_code'=>['like',$demand['demand_code'].'%']])
            ->order('id desc')
            ->getField('quotation_code');
        $quotation['step'] = $demand['step'];
        if($last_quotation_code) {
            $quotation['quotation_code'] = $demand['demand_code'].'-'.str_pad((substr($last_quotation_code,15)+1),3,0,STR_PAD_LEFT);
        }else {
            $quotation['quotation_code'] = $demand['demand_code'].'-001';
        }
        $res = $this->add($this->create($quotation));
        M()->commit();
        return $res;
    }

    /**销售认领数据验证
     * @param $data
     * @return bool
     */
    public function validate($data)
    {
        $err = '%s验证未通过';
        $v_arr = [
            // ['字段', '字段名', '必填', ['校验类型', '校验因子']]
            ['amount', '报价金额（含税）', true, ['pattern', "/^(0|([1-9][0-9]{0,9}))(\.[0-9]{1,2})?$/"]],
            ['amount_not_contain_tax', '报价金额（不含税）', true, ['pattern', "/^(0|([1-9][0-9]{0,9}))(\.[0-9]{1,2})?$/"]],
            ['drawback_amount', '退税总金额', true, ['pattern', "/^(0|([1-9][0-9]{0,9}))(\.[0-9]{1,2})?$/"]],
//            ['service_expense', '向供应商支付的服务费用', false, ['pattern', "/^(0|([1-9][0-9]{0,9}))(\.[0-9]{1,2})?$/"]],
            ['currency', '报价币种', true, ['pattern', "/^N00059\d{4}$/"]],
            ['purchase_type', '采购类型', true, ['pattern', "/^N00189\d{4}$/"]],
            ['delivery_type', '交货方式', false, ['callable', function($val) use (&$data, &$err) {
                if ($data['purchase_type'] == self::$type_normal && empty($val)) {
                    $err = '%s不能为空';
                    return false;
                }
                return true;
            }]],
            ['supplier', '供应商', true],
            ['supplier_no', '供应商营业执照号', false, ['callable', function($val) use (&$data, &$err) {
                if ($data['purchase_type'] == self::$type_normal && empty($val)) {
                    $err = '未获取到供应商的营业执照号';
                    return false;
                }
                return true;
            }]],
            ['pur_website', '采购网站', false, ['callable', function($val) use (&$data, &$err) {
                if ($data['purchase_type'] == self::$type_online && empty($val)) {
                    $err = '%s不能为空';
                    return false;
                }
                return true;
            }]],
            ['has_contract', '有无框架合同', false, ['callable', function($val) use (&$data, &$err) {
                if ($data['purchase_type'] == self::$type_normal) {
                    if (empty($val) && $val !== 0 && $val !== '0') {
                        $err = '%s不能为空';
                        return false;
                    }
                }
                return true;
            }]],
            ['contract', '框架合同', false, ['callable', function($val) use (&$data, &$err) {
                if ($data['purchase_type'] == self::$type_normal) {
                    if ($data['has_contract'] && !$val) {
                        $err = '%s不能为空';
                        return false;
                    } elseif (!$data['has_contract'] && $val) {
                        $data['contract'] = '';
                    }
                }
                return true;
            }]],
            ['our_company', '我方公司', true, ['pattern', "/^N00124\d{4}$/"]],
            ['ship_time', '供应商发货时间', true],
            ['arrive_time', '预计到货时间', true, ['callable', function($val) use (&$data, &$err) {
                if (strtotime($val) < strtotime($data['ship_time'])) {
                    $err = '%s' . L('不能小于发货时间');
                    return false;
                }
                return true;
            }]],
            ['payment_cycle_type', '付款周期类型', true, ['pattern', "/^N00221\d{4}$/"]],
           /* ['payment_cycle', '付款周期', true, ['pattern', "/^N00216\d{4}$/"]],
            ['payment_time', '付款时间', true,  ['callable', function($val) use ($data, &$err) {
                if ($data['payment_cycle_type'] == 'N002210100') {//实际情况
                    $v_pt = [
                        ['node', '付款节点', true, ['pattern', "/^N00139\d{4}$/"]],
                        ['days', '天数', true, ['pattern', "/^N00142\d{4}$/"]],
                        ['day_type', '天数类型', true, ['pattern', "/^N00140\d{4}$/"]],
                        ['percent', '付款比例', true],
                        ['date', '预计付款日期', true],
                    ];
                } elseif ($data['payment_cycle_type'] == 'N002210200') {//指定时间
                    $v_pt = [
                        ['percent', '付款比例', true],
                        ['date', '预计付款日期', true],
                    ];
                }
                $flg = true;
                $percent_total = 0;
                foreach ($val as $pt) {
                    $flg = $flg && $this->_va($v_pt, $pt, $err);
                    $percent_total += $pt['percent'];
                }
                if ($percent_total != 100) {
                    $flg = false;
                    $err = '付款百分比之和小于100';
                }
                return $flg;
            }] ],*/
            ['expense_currency', '物流等费用币种', true, ['pattern', "/^N00059\d{4}$/"]],
            ['expense', '物流等费用', true, ['pattern', "/^(0|([1-9][0-9]{0,9}))(\.[0-9]{1,2})?$/"]],
            ['invoice_type', '发票类型', true, ['pattern', "/^N00135\d{4}$/"]],
            ['tax_rate', '税率', true, ['pattern', "/^N00134\d{4}$/"]],
            ['has_drawback', '有无退税', true],
            ['drawback_time', '退税时间', false, ['callable', function($val) use (&$data, &$err) {
                if ($data['has_drawback'] && !$val) {
                    $err = '%s不能为空';
                    return false;
                } elseif (!$data['has_drawback'] && $val) {
                    $data['drawback_time'] = '';
                }
                return true;
            }]],
            ['purchase_days', '采购账期', false],
            ['source_country', '货源国家', true],
            ['ship_type', '发货操作', true, ['pattern', "/^N00245\d{4}$/"]],
            ['warehouse', '入库仓库', true,  ['callable', function($val) use ($data, &$err) {
                switch ($data['ship_type']) {
                    case 'N002450100':
                        if(in_array($val,['N000680800','N000685800','N000685900'])) return false;
                        return true;
                    case  'N002450300' :
                        if($val != 'N000680800') return false;
                        return true;
                    default :
                        return true;
                }
            }] ],
            ['purchase_team', '采购团队', true, ['pattern', "/^N00129\d{4}$/"]],
            ['purchaser', '采购人员', true],
            ['remark', '备注', false],
            ['attachment', '订单附件', false],
            ['ce_level', 'ce等级', false],
            ['ce', 'ce值', false],
//            ['goods', '商品列表', true],
        ];
        return $this->_va($v_arr, $data, $err);
    }

    /**数据验证方法
     * @param $v_arr
     * @param $data
     * @param $err
     * @return bool
     */
    private function _va($v_arr, $data, &$err)
    {
        foreach ($v_arr as list($field, $f_name, $must, $check)) {
            if ($must && empty($data[$field]) && $data[$field] !== 0 && $data[$field] !== '0') {
                $this->error = sprintf('%s不能为空', $f_name);
                return false;
            }
            if ($check) {
                switch ($check[0]) {
                    case 'pattern':
                        if (!preg_match($check[1], $data[$field])) {
                            $this->error = sprintf($err, $f_name);
                            return false;
                        }
                        break;
                    case 'callable':
                        if (!call_user_func_array($check[1], [$data[$field]])) {
                            $this->error = sprintf($err, $f_name);
                            return false;
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return true;
    }

    /**获取所有采购状态
     * @param $demand_id
     * @param bool $not_chosen
     * @return mixed
     */
    public function getStatusArr($demand_id, $not_chosen = false)
    {
        $chosen = $not_chosen ? ['in', [self::$chosen['chosen'], self::$chosen['not_chosen']]] : self::$chosen['chosen'];
        $status_arr = $this->where(['demand_id' => $demand_id, 'chosen' => $chosen])
            ->field('status,step')
            ->select();
        return $status_arr;
    }

    /**获取商品
     * @param $id
     * @return mixed
     */
    public function getGoods($id)
    {
        return D('QuotationGoods')->where(['quotation_id' => $id])->select();
    }

    /**创建订单
     * @param $data
     * @return bool
     */
    public function createOrder($data)
    {
        $this->startTrans();
        $q_data = $this->lock(true)->where(['id' => $data['id']])->find();
        if (!$this->seller) {
            $this->seller = D('Demand')->where(['id' => $q_data['demand_id']])->getField('seller');
        }
        if ($q_data) {
            $pur_data['supplier_new_id'] = $q_data['supplier_new_id'];
            $pur_data['purchase_type'] = $q_data['purchase_type'];
            $pur_data['seller'] = $this->seller;
            $pur_data['delivery_type'] = $q_data['delivery_type'];
            $pur_data['online_purchase_website'] = $q_data['pur_website'];
            $pur_data['online_purchase_account'] = $q_data['pur_account'];
            $pur_data['online_purchase_order_number'] = $q_data['pur_order_no'];
            $pur_data['procurement_number'] = $q_data['quotation_code'];
            $pur_data['supplier_id'] = $q_data['supplier'];
            $pur_data['sp_charter_no'] = $q_data['supplier_no'];
            $pur_data['amount_currency'] = $q_data['currency'];
            $pur_data['amount'] = $q_data['po_amount'];
            $rmb_amount = $this->getCNY($q_data['currency'], $q_data['po_amount']);
            $pur_data['amount_rmb'] = $rmb_amount[0];
            $pur_data['amount_currency_rate'] = $rmb_amount[1];
            $pur_data['payment_company'] = $q_data['purchase_team'];
            $pur_data['contract_number'] = $q_data['contract'];
            $pur_data['invoice_type'] = $q_data['invoice_type'];
            $pur_data['tax_rate'] = $q_data['tax_rate'];
            $pur_data['source_country'] = $q_data['source_country'];
            $pur_data['our_company'] = $q_data['our_company'];
            $pur_data['currency'] = $q_data['currency'];//物流费用改用供应商服务费用 20180528
            $pur_data['expense'] = $q_data['service_expense'];
            $rmb_expense = $this->getCNY($q_data['currency'], $q_data['service_expense']);
            $pur_data['logistics_rmb'] = $rmb_expense[0];
            $pur_data['real_currency_rate'] = $rmb_expense[1];
            $pur_data['arrival_date'] = $q_data['arrive_time'];
            $pur_data['warehouse'] = $q_data['warehouse'];
            if ($q_data['supplier_no']) {
                $pur_data['supplier_id_en'] = M('SpSupplier', 'tb_crm_')->where(['SP_CHARTER_NO' => $q_data['supplier_no']])->getField('SP_NAME_EN');
            } else {
                $pur_data['supplier_id_en'] = '';
            }
            $pur_data['payment_type'] = $q_data['payment_cycle_type'] == 'N002210100' ? 1 : 0;
            $pur_data['payment_period'] = $q_data['payment_cycle'] == 'N002160100' ? 1 : ($q_data['payment_cycle'] == 'N002160200' ? 2 : 3);
            $pay_time = json_decode($q_data['payment_time'], true);
            $payment_info = [];
            foreach ($pay_time as $k => $v) {
                $tmp = [];
                if ($pur_data['payment_type']) {
                    $tmp['payment_node'] = $v['node'];
                    $tmp['payment_days'] = cdVal($v['days']);
                    $tmp['payment_day_type'] = $v['day_type'] == 'N001400100' ? '0' : '1';
                    $tmp['payment_percent'] = $v['percent'];
                    $tmp['payment_date_estimate'] = $v['date'];
                } else {
                    $tmp['payment_date'] = $v['date'];
                    $tmp['payment_percent'] = $v['percent'];

                }
                $payment_info[$k + 1] = $tmp;
            }
            $pur_data['payment_info'] = json_encode($payment_info);
            $pur_data['attachment'] = $q_data['po_archive'];
            $pur_data['order_remark'] = $q_data['remark'];
            $pur_data['drawback_date'] = $q_data['drawback_time'];
            $pur_data['moneytype'] = $q_data['sell_currency'];
            $pur_data['drawback_money'] = $q_data['drawback_amount'];
            $rmb_drawback = $this->getCNY($q_data['sell_currency'], $q_data['drawback_amount']);
            $pur_data['drawback_rmb'] = $rmb_drawback[0];
            $pur_data['real_moneytype_rate'] = $rmb_drawback[1];
            $pur_data['prepared_by'] = $q_data['purchaser'];
            $pur_data['prepared_time'] = date('Y-m-d H:i:s');
            $pur_data['goods'] = [];
            $pur_data['number_total'] = 0;
            $goods = D('QuotationGoods')->alias('t')
                ->field('t.*,c.hotness')
                ->join('left join tb_sell_demand_goods c on c.id=t.demand_goods_id')
                ->where(['t.quotation_id' => $data['id'], 'choose_number' => array('gt', 0)])->select();
            $goods_cost = $q_data['po_amount']-$q_data['service_expense'];
            foreach ($goods as $v) {
                $tmp = [];
                $pur_data['number_total'] += $v['choose_number'];
                $tmp['search_information'] = $v['search_id'];
                $tmp['sku_information'] = $v['sku_id'];
                $tmp['goods_name'] = $v['goods_name'];
                $tmp['goods_attribute'] = $v['sku_attribute'];
                $tmp['unit_price'] = $v['purchase_price'];
                $tmp['unit_price_not_contain_tax'] = $v['purchase_price_not_contain_tax'];
                $tmp['hotness'] = $v['hotness'];
                $tmp['goods_number'] = $v['choose_number'];
                $tmp['goods_money'] = $tmp['goods_number'] * $tmp['unit_price'];
                $tmp['goods_money_not_contain_tax'] = $tmp['goods_number'] * $tmp['unit_price_not_contain_tax'];
                $tmp['drawback_percent'] = $v['drawback_percent'];
                $tmp['unit_expense'] = round($q_data['service_expense']/$goods_cost*$v['purchase_price'],2);
                $tmp['sell_small_team_json'] = $v['sell_small_team_json'];   // 销售小团队
                $pur_data['goods'][] = $tmp;
            }
            $pur_data['sell_team'] = D('Demand')->where(['id' => $q_data['demand_id']])->getField('sell_team');
            $pur_data['money_total'] = $q_data['po_amount'] - $q_data['service_expense'];
            $pur_data['money_total_not_contain_tax'] = $q_data['amount_not_contain_tax'];
            $pur_data['money_total_rmb'] = $this->getCNY($q_data['currency'], $pur_data['money_total'])[0];
            $pur_data['prepared_time'] = date('Y-m-d H:i:s');
            
            $purchase_id = D("Purchase", 'Logic')->scmOrderAdd($pur_data, $data['id']);
            if (!$purchase_id) {
                $this->error = '创建采购单失败:'.D('Purchase','Logic')->getError();
                return false;
            }
            $q_data['purchase_id'] = $purchase_id;
            $q_data['status'] = self::$status['pass_through'];
            if ($this->save($q_data) === false) {
                $this->rollback();
                $this->error = '报价状态保存失败';
                return false;
            }
        } else {
            $this->rollback();
            $this->error = '报价状态异常';
            return false;
        }
        $this->commit();
        return true;
    }

    /**获取汇率
     * @return mixed
     */
    public static function getXchr() {
        $redis      = RedisModel::connect_init();
        $rates      = json_decode($redis->get('xchr_'.date('Ymd')),true);
        if (empty($rates)) {
            $rates      = json_decode($redis->get('xchr_'.date('Ymd',strtotime('-1 day'))),true);;
        }
        if (!$rates) {
            throw new \Exception('汇率获取错误');
        }
        return $rates;
    }

    private function getCNY($currency, $amount) {
        $rates = self::getXchr();
        $rates['cnyXchrAmtCny'] = 1;
        $currency = strtolower(cdVal($currency));
        $expense_rate = $rates[$currency . 'XchrAmtCny'];
        $e_amount = round($amount * $expense_rate, 2);
        return [$e_amount, $expense_rate];
    }

    private function toUSD($q)
    {
        $rates      = self::getXchr();
        $rates['cnyXchrAmtCny'] = 1;
        $q['currency'] = strtolower($q['currency_val']);
        $q['d_currency'] = strtolower($q['d_currency']);
        $q['d_tax_currency'] = strtolower($q['d_tax_currency']);
        $q['expense_currency'] = strtolower($q['expense_currency_val']);
        $amt_rate = $q['currency'] == 'USD' ? 1 : $rates[$q['currency'] . 'XchrAmtCny'] / $rates['usdXchrAmtCny'];
        $sell_rate = $q['d_currency'] == 'USD' ? 1 : $rates[$q['d_currency'] . 'XchrAmtCny'] / $rates['usdXchrAmtCny'];
        $d_tax_rate = $q['d_tax_currency'] == 'USD' ? 1 : $rates[$q['d_tax_currency'] . 'XchrAmtCny'] / $rates['usdXchrAmtCny'];
        $expense_rate = $q['expense_currency'] == 'USD' ? 1 : $rates[$q['expense_currency'] . 'XchrAmtCny'] / $rates['usdXchrAmtCny'];
        $q['amount'] *= $amt_rate;
        $q['drawback_amount'] *= $sell_rate;//改为取售价币种
        $q['expense'] *= $expense_rate;
        $q['service_expense'] *= $amt_rate;
        $q['d_sell_amount'] *= $sell_rate;
        $q['d_tax'] *= $d_tax_rate;
        foreach ($q['goods'] as &$v) {
            $v['purchase_price'] *= $amt_rate;
            $v['sell_price'] *= $sell_rate;
        }
        return $q;
    }

    /**计算报价的采购金额,po金额和退税总金额
     * @param $quotation
     * @param $type
     */
    public function calcAmt($quotation, $type = 'supply')
    {
        $pro_number = $type == 'choose' ? 'choose_number' : 'supply_number';
        //汇率换算
        $rates = self::getXchr();
        $rates['cnyXchrAmtCny'] = 1;
        $q_currency = strtolower(cdVal($quotation['currency']));
        $q_expense_currency = strtolower(cdVal($quotation['expense_currency']));
        $d_currency = strtolower(cdVal(D('Demand')->where(['id' => $quotation['demand_id']])->getField('sell_currency')));
        $sell_rate = $d_currency == $q_currency ? 1 : $rates[$d_currency . 'XchrAmtCny'] / $rates[$q_currency . 'XchrAmtCny'];
        $expense_rate = $q_expense_currency == $q_currency ? 1 : $rates[$q_expense_currency . 'XchrAmtCny'] / $rates[$q_currency . 'XchrAmtCny'];
        $data['amount'] = $quotation['expense'] * $expense_rate + $quotation['service_expense'];
        $data['drawback_amount'] = 0;
        $quotation['tax_rate_val'] = cdVal($quotation['tax_rate']);
        array_map(function ($v) use (&$data, $quotation, $pro_number) {
            $data['amount'] += $v[$pro_number] * $v['purchase_price'];
            //fixdue 币种换算
            $data['drawback_amount'] += $v[$pro_number] * $v['purchase_price'] / (1 + $quotation['tax_rate_val'] / 100)  * cdVal($v['drawback_percent']) / 100;
        }, $quotation['goods']);
        $data['po_amount'] = round($data['amount'] - $quotation['expense'] * $expense_rate, 2);
        $data['amount'] = round($data['amount'], 2);
        $data['drawback_amount'] = round($data['drawback_amount'], 2);
        return $data;
    }

    /**计算ce
     * @param $q
     */
    public function calcCE($q, $demand)
    {
        $q['step'] = $demand['step'];
        $q['d_type'] = cdVal($demand['demand_type']);
        $q['d_currency'] = cdVal($demand['sell_currency']);
        $q['d_sell_amount'] = $demand['sell_amount'];
        $q['d_tax_currency'] = cdVal($demand['tax_currency']);
        $q['d_tax'] = $demand['tax'] ?: 0;
        $q['currency_val'] = cdVal($q['currency']);
        $q['expense_currency_val'] = cdVal($q['expense_currency']);
        $q = $this->toUSD($q);
        $ret = [];
        $number = $q['chosen'] == QuotationModel::$chosen['chosen'] ? 'choose_number' : 'supply_number';
        $decimals = 2;
        $ret['amt'] = $q['expense'] + $q['service_expense'];
        $ret['sell_amt'] = 0;
        $ret['gross_profit'] = 0;
        $ret['drawback'] = 0;
        $q['tax_rate_val'] = cdVal($q['tax_rate']);
        array_map(function ($v) use (&$ret, $number, $q) {
            $v['drawback_percent_val'] = cdVal($v['drawback_percent']);
            $ret['amt'] += $v[$number] * $v['purchase_price'];
            $ret['sell_amt'] += $v[$number] * $v['sell_price'];
            $ret['drawback'] += $v[$number] * $v['purchase_price'] / (1 + $q['tax_rate_val'] / 100) * $v['drawback_percent_val'] / 100;
            $ret['gross_profit'] += $v[$number] * ($v['sell_price'] - $v['purchase_price']);
        }, $q['goods']);
        $ret['drawback'] = number_format($ret['drawback'], $decimals, '.', '');
        $ret['gross_profit'] = number_format($ret['gross_profit'], $decimals, '.', '');
        $ret['amt'] = number_format($ret['amt'], $decimals, '.', '');
        $ret['gross_profit_tax_back'] = number_format($ret['gross_profit'] + $ret['drawback'], $decimals, '.', '');
        $ret['net_profit'] = $ret['gross_profit_tax_back'] - $q['expense'] - $q['service_expense'];
        $ret['net_profit_rate'] = '';
        $ret['sell_amt'] = $ret['sell_amt'] * (1 - $q['d_tax'] / $q['d_sell_amount']);
        $ret['net_profit_rate'] = number_format($ret['net_profit'] / $ret['sell_amt'] * 100, 2) . '%';
        $ret['sell_amt'] = number_format($ret['sell_amt'], $decimals, '.', '');
        $ret['net_profit'] = number_format($ret['net_profit'], $decimals, '.', '');
        $ret['expense'] = number_format($q['expense'] + $q['service_expense'], $decimals, '.', '');
        return $ret;
    }

    /**更新报价ce
     * @param $demand
     * @return bool
     */
    public function updateCE($demand)
    {
        $q_list = $this->where(['demand_id' => $demand['id'], 'invalid' => 0])->select();
        foreach ($q_list as $q) {
            $q['goods'] = D('QuotationGoods')->field('sell_price,purchase_price,drawback_percent,choose_number,supply_number')->where(['quotation_id' => $q['id']])->select();
            $ce_data = $this->calcCE($q, $demand);
            $ce_data['quotation_id'] = $q['id'];
            M('QuotationProfit', 'tb_sell_')->where(['quotation_id' => $q['id']])->delete();
            if (M('QuotationProfit', 'tb_sell_')->add($ce_data) === false) {
                $this->error = $this->getError() ?: '报价CE更新失败';
                return false;
            }
            $type = $q['chosen'] == QuotationModel::$chosen['chosen'] ? 'choose' : 'supply';
            $amt_data = $this->calcAmt($q, $type);
            if ($this->where(['id' => $q['id']])->save($amt_data) === false) {
                $this->error = $this->getError() ?: '报价po金额计算失败';
                return false;
            }
        }
        return true;
    }

}