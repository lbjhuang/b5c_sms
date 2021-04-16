<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/7
 * Time: 13:15
 */

class DemandModel extends BaseModel
{
    protected $trueTableName = 'tb_sell_demand';
    protected $_link = [
        'DemandGoods' => [
            'mapping_type' => HAS_MANY,
            'foreign_key' => 'demand_id',
            'mapping_name'=>'goods',
        ]
    ];

    static $step = [
        'demand_submit'     => 'N002120100',//待提交需求
        'demand_approve'    => 'N002120200',//待需求审批
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

    static $step_us_team = [
        'demand_submit'     => 'N002120100',//待提交需求
        'demand_approve'    => 'N002120200',//待需求审批
        'purchase_claim'    => 'N002120300',//待采购认领
        'seller_choose'     => 'N002120400',//待销售选择
        'purchase_confirm'  => 'N002120500',//待采购确认
        'seller_submit'     => 'N002120600',//待销售提交
        'ceo_approve'       => 'N002120800',//待CEO审批
        'upload_po'         => 'N002120900',//待上传PO
        'justice_approve'   => 'N002121000',//待法务审批
        'create_order'      => 'N002121300',//待创建订单
        'success'           => 'N002121400'//成功结束
    ];

    static $status = [
        'draft'                     => 'N002130100',//草稿
        'pass_through'              => 'N002130200',//处理通过
        'untreated'                 => 'N002130300',//未处理
        'no_need_to_treat'          => 'N002130400',//无需处理
        'not_pass_through'          => 'N002130500',//审批失败
        'apply_for_modification'    => 'N002130600',//已申请修改
        'discarded'                 => 'N002130700'//已弃单
    ];

    static $deal_type = [
        'mix'           => 'N002580100',//采销混合
        'sell_store'    => 'N002580300',//现货销售
        'store_goods'   => 'N002580200',//囤货采购
    ];

    static $demand_type_store           = 'N002100100';
    static $demand_type_sell            = 'N002100200';
    static $demand_type_self_sell       = 'N002100201';

    static $is_spot             = [
        'mix'           => 0,
        'spot_only'     => 1,
        'purchase_only' => 2
    ];

    static $ce_level = ['S','A','B','C','D','F'];

    static $validate_sell_create = [
        ['demand_type','require','需求类型必填'],
        ['customer','require','客户必填'],
        ['customer_charter_no','require','未获取到客户营业执照号'],
        ['our_company','require','我方公司必填'],
        ['ship_date','0000-00-00','销售发货时间必填',0,'notequal'],
        ['seller','require','销售同事必填'],
        ['sell_team','require','销售团队必填'],
        ['deadline','0000-00-00','需求截止时间必填',0,'notequal'],
    ];

    static $validate_store_create = [
        ['demand_type','require','需求类型必填'],
        ['sell_team','require','销售团队必填'],
        ['seller','require','销售同事必填'],
        ['deadline','0000-00-00','需求截止时间必填',0,'notequal'],
    ];

    static $validate_sell_submit = [
        ['demand_type','require','订单类型必填'],
        ['customer','require','客户必填'],
        ['customer_charter_no','require','未获取到客户营业执照号'],
        ['our_company','require','我方公司必填'],
        ['business_mode','require','业务类型必填'],
        ['receive_mode','require','收货方式必填'],
        ['receive_country','require','收货国家必填'],
        ['receive_address','require','详细地址必填'],
        ['sell_currency','require','销售币种必填'],
        //['sell_amount','0','销售金额（含税）必填',0,'notequal'], #10082  SCM创建需求支持销售单价（含税）或（不含税）为0商品20200320
        //['sell_amount_not_contain_tax','0','销售金额（不含税）必填',0,'notequal'],
        ['ship_date','0000-00-00','销售发货时间必填',0,'notequal'],
        ['collection_cycle','require','收款周期必填'],
        ['collection_time','require','收款时间必填'],
        ['invoice_type','require','发票类型必填'],
        ['tax_rate','require','税点必填'],
        ['seller','require','销售同事必填'],
        ['sell_team','require','销售团队必填'],
        ['deadline','0000-00-00','需求截止时间必填',0,'notequal'],
    ];

    static $validate_store_submit = [
        ['demand_type','require','订单类型必填'],
        ['sell_currency','require','销售单价币种必选'],
//        ['sell_amount','0','销售金额（含税）必填',0,'notequal'], #10082  SCM创建需求支持销售单价（含税）或（不含税）为0商品20200320
  //      ['sell_amount_not_contain_tax','0','销售金额（不含税）必填',0,'notequal'],
        ['sell_team','require','销售团队必填'],
        ['seller','require','销售同事必填'],
        ['collection_time','require','收款时间必填'],
        ['deadline','0000-00-00','需求截止时间必填',0,'notequal'],
    ];

    /**
     * @var array 可编辑的字段
     */
    static $part_updatable_fields = ['rebate_rate','rebate_amount','id','is_spot','customer','customer_charter_no','is_purchase_only','contract','our_company','business_mode','receive_mode','need_warehouse','receive_country','receive_province','receive_city','receive_address','sell_currency','sell_amount','sell_amount_not_contain_tax','ship_date','collection_cycle','collection_time','invoice_type','tax_rate','expense_currency','expense','expense_currency_spot','expense_spot','tax_currency','tax','tax_currency_spot','tax_spot','other_income_currency','other_income','seller','sell_team','order_source','remark','attachment','expected_sales_country', 'expected_sales_platform_cd'];

    public static function getStepNm($step)
    {
        return array_flip(self::$step)[$step];
    }

    public function demandList($param,$limit = '') {
        $where = $this->getWhere($param);
        $where_string = '';
        if (isset($where['seller'])) {
            $where_string = "(seller = '{$where['seller']}' OR tb_con_division_client.sales_assistant_by  = '{$where['seller']}'  )";
            unset($where['seller']);
            if (isset($where['demand_code_string'])) {
                $where_string .= " AND ({$where['demand_code_string']})";
                unset($where['demand_code_string']);
            }
        } else {
            if (isset($where['demand_code_string'])) {
                $where['_string'] = $where['demand_code_string'];
                unset($where['demand_code_string']);
            }
        }
        $condition = implode(',',array_map(function($v){
            return '"'.$v.'"';
        },PmsBaseModel::getLangCondition()));
        $select_res = $this
            ->alias('t')
            ->field('t.id,demand_code,c.CD_VAL demand_type,customer,substring_index(group_concat(spu_name ORDER BY abs(right(language,6)-' . substr(TbMsCmnCdModel::$language_english_cd, -6) . ') desc ),",",1) goods_name,g.CD_VAL sell_currency,sell_amount,t.tax_rate,d.CD_VAL sell_team,e.CD_VAL step,f.CD_VAL status,t.create_time,h.gross_profit_rate_after_tax_refund,h.ce_level_after_tax_refund ce_level,t.create_user,seller')
            ->join('left join tb_sell_quotation a on a.demand_id = t.id')
            ->join('left join tb_sell_demand_goods b on b.demand_id=t.id')
            ->join('left join tb_ms_cmn_cd c on c.CD=t.demand_type')
            ->join('left join tb_ms_cmn_cd d on d.CD=t.sell_team')
            ->join('left join tb_ms_cmn_cd e on e.CD=t.step')
            ->join('left join tb_ms_cmn_cd f on f.CD=t.status')
            ->join('left join tb_ms_cmn_cd g on g.CD=t.sell_currency')
            ->join('left join tb_sell_demand_profit h on h.demand_id=t.id');
        if ($where_string) {
            $select_res = $this->join('LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = customer AND tb_crm_sp_supplier.DATA_MARKING = 1')
                ->join('left join tb_con_division_client on tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID');
        }
        $select_res = $this ->join('left join ' . PMS_DATABASE . '.product_sku i on b.sku_id=i.sku_id')
            ->join('left join ' . PMS_DATABASE . '.product_detail j on j.spu_id=i.spu_id and j.language in (' . $condition . ')')
            ->where($where)
            ->where($where_string, null, true)
            ->group('t.id')
            ->order('id desc')
            ->limit($limit)
            ->select();
        return $select_res;
    }

    public function demandCount($param) {
        $where = $this->getWhere($param);
        $where_string = '';
        if (isset($where['seller'])) {
            $where_string = "(seller = '{$where['seller']}' OR tb_con_division_client.sales_assistant_by  = '{$where['seller']}'  )";
            unset($where['seller']);
            if (isset($where['demand_code_string'])) {
                $where_string .= " AND ({$where['demand_code_string']})";
                unset($where['demand_code_string']);
            }
        } else {
            if (isset($where['demand_code_string'])) {
                $where['_string'] = $where['demand_code_string'];
                unset($where['demand_code_string']);
            }
        }
        $condition = implode(',',array_map(function($v){
            return '"'.$v.'"';
        },PmsBaseModel::getLangCondition()));
        $sql   = $this
            ->alias('t')
            ->field('t.id')
            ->join('left join tb_sell_quotation a on a.demand_id = t.id')
            ->join('left join tb_sell_demand_goods b on b.demand_id=t.id')
            ->join('left join tb_sell_demand_profit h on h.demand_id=t.id')
            ->join('left join tb_ms_cmn_cd d on d.CD=t.sell_team');
        if ($where_string) {
            $sql   =   $this->join('LEFT JOIN tb_crm_sp_supplier ON tb_crm_sp_supplier.SP_NAME = customer AND tb_crm_sp_supplier.DATA_MARKING = 1')
                ->join('left join tb_con_division_client on tb_con_division_client.supplier_id = tb_crm_sp_supplier.ID');
        }

        $sql   =  $this->join('left join '.PMS_DATABASE.'.product_sku i on b.sku_id=i.sku_id')
            ->join('left join '.PMS_DATABASE.'.product_detail j on j.spu_id=i.spu_id and j.language in ('.$condition.')')
            ->where($where)
            ->where($where_string,null,true)
            ->group('t.id')
            ->buildSql();
        return (new SlaveModel())->table($sql.' a')->count();
    }

    public function getWhere($param) {
        $where = [];
        $param['step'] ? $where['t.step'] = ['in',$param['step']] : '';
        $param['status'] ? $where['t.status'] = ['in',$param['status']] : '';
        $param['demand_type'] ? $where['demand_type'] = ['in',$param['demand_type']] : '';
        $param['business_type'] ? $where['business_mode'] = ['in',$param['business_type']] : '';
        $param['receive_mode'] ? $where['receive_mode'] = ['in',$param['receive_mode']] : '';
        /*$param['demand_code'] ? $where['demand_code'] = $param['demand_code'] : '';
        $param['third_po_no'] ? $where['third_po_no'] = $param['third_po_no'] : '';*/
        $param['customer'] ? $where['customer'] = ['like','%'.$param['customer'].'%'] : '';
        $param['seller'] ? $where['seller'] = $param['seller'] : '';
        $param['sell_team'] ? $where['sell_team'] = $param['sell_team'] : '';
        $param['create_user'] ? $where['t.create_user'] = $param['create_user'] : '';
        $param['quotation_code'] ? $where['quotation_code'] = $param['quotation_code'] : '';
        $param['supplier'] ? $where['supplier'] = ['like','%'.$param['supplier'].'%'] : '';
        $param['purchaser'] ? $where['purchaser'] = $param['purchaser'] : '';
        $param['purchase_team'] ? $where['purchase_team'] = $param['purchase_team'] : '';
        $param['sales_leader'] ? $where['d.ETC'] = ['like','%'.$param['sales_leader'].'%'] : '';
        if($param['keyword']) {
            if($param['keyword_type'] == 0) {
                $where['spu_name'] = ['like','%'.$param['keyword'].'%'];
            }elseif($param['keyword_type'] == 1) {
                $complex['search_id'] = $param['keyword'];
                $complex['b.sku_id']  = $param['keyword'];
                $complex['i.upc_id']  = $param['keyword'];
                $complex['_string']   = "FIND_IN_SET('{$param['keyword']}',i.upc_more)";
                $complex['_logic']      = 'or';
                $where['_complex']      = $complex;
            }
        }
        if ($param['demand_code']) {
            $where['demand_code_string'] = "demand_code = '{$param['demand_code']}' or third_po_no = '{$param['demand_code']}'";
        }
        $param['ce_level'] ? $where['h.ce_level'] = ['in',$param['ce_level']] : '';
        if($param['min_profit_margin'] && $param['max_profit_margin']) {
            $where['gross_profit_rate_after_tax_refund'] = ['between',[$param['min_profit_margin'],$param['max_profit_margin']]];
        }elseif($param['min_profit_margin']) {
            $where['gross_profit_rate_after_tax_refund'] = ['egt',$param['min_profit_margin']];
        }elseif($param['max_profit_margin']) {
            $where['gross_profit_rate_after_tax_refund'] = ['elt',$param['max_profit_margin']];
        }
        if($param['min_create_time'] && $param['max_create_time']) {
            $where['t.create_time'] = ['between',[$param['min_create_time'].' 00:00:00',$param['max_create_time'].' 23:59:59']];
        }elseif($param['min_create_time']) {
            $where['t.create_time'] = ['egt',$param['min_create_time']];
        }elseif($param['max_create_time']) {
            $where['t.create_time'] = ['elt',$param['max_create_time']];
        }
        $param['legal_man'] ? $where['t.legal_man'] = ['like','%'.$param['legal_man'].'%']:'';
        return $where;
    }

    public function updateDemand($demand,$need_filter = false) {
        //有demand_id为编辑需求
        if($need_filter) {
            foreach ($demand as $k => $v) {
                if(!in_array($k,self::$part_updatable_fields)) unset($demand[$k]);
            }
        }
        $this->create($demand);
        return $this->save();
    }

    /**
     * 生成需求code
     * @return mixed
     */
    public function createDemandCode() {
        $last_demand_code = $this->lock(true)->where(['demand_code'=>['like','RN'.date('Ymd').'%']])->order('id desc')->getField('demand_code');
        if($last_demand_code) {
            return 'RN'.(substr($last_demand_code,2)+1);
        }else {
            return 'RN'.date('Ymd').'0001';
        }
    }

    /**获取商品
     * @param $id
     * @return mixed
     */
    public function getGoods($id)
    {
        return D('DemandGoods')->where(['demand_id' => $id])->select();
    }

    public function isSell($demand_code) {
        $demand_type = $this->where(['demand_code'=>$demand_code])->getField('demand_type');
        if ($demand_type == self::$demand_type_sell) {
            return true;
        }
        if ($demand_type == self::$demand_type_self_sell) {
            return true;
        }
        return false;
    }
}