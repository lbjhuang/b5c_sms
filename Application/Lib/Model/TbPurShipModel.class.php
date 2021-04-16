<?php

/**
 * User: yuanshixiao
 * Date: 2017/6/20
 * Time: 14:01
 */
class TbPurShipModel extends RelationModel
{
    protected $trueTableName    = 'tb_pur_ship';

    public static $warehouse_status_val = [
        0 => '待入库',
        1 => '已入库',
        2 => '部分入库',
    ];

    public static $warehouse_status = [
        'to_do'     => 0,
        'part_done' => 2,
        'done'      => 1,
    ];

    public $_validate = [
        ['arrival_date','require','预计到港/到货日期必须'],
        ['shipping_number','require','发货数量必须'],
    ];

    public $_auto = [
        ['create_user','session',1,'function','m_loginname'],
        ['create_time','date',1,'function','Y-m-d H:i:s'],
    ];

    public $error = '';

    protected $_link = [
        'TbPurRelevanceOrder' => [
            'mapping_type'  => BELONGS_TO,
            'foreign_key'   => 'relevance_id',
            'mapping_name'  => 'relevance',
            'relation_deep' => true,
        ],
        'TbPurShipGoods' => [
            'mapping_type'  => HAS_MANY,
            'foreign_key'   => 'ship_id',
            'mapping_name'  => 'goods',
            'relation_deep' => true,
        ],
        'TbMsCmnCd' => [
            'mapping_type'  => BELONGS_TO,
            'foreign_key'   => 'warehouse',
            'mapping_name'  => 'warehouse',
        ],
        'warehouse_correct' => [
            'class_name'    => 'TbMsCmnCd',
            'mapping_type'  => BELONGS_TO,
            'foreign_key'   => 'warehouse_correct',
            'mapping_name'  => 'warehouse_correct',
        ],
        'TbMsCmnCdCurr' => [
            'mapping_type'  => BELONGS_TO,
            'class_name'    => 'TbMsCmnCd',
            'foreign_key'   => 'extra_cost_currency',
            'mapping_name'  => 'extra_cost_currency',
        ],
    ];

    public function __construct() {
        parent::__construct();
    }

    /*
     * 计算单个商品入库成本，公式(E*B/F)+{（E*B/G）*（D*G/C+H）}/F简化为E*B/F*(1+D/C+H/G), 注：公式来源于产品提供的入库成本均摊表格
     */
    public function allWarehouseCost($ship_id,$warehouse) {
        if($warehouse['warehouse_extra_cost'] && $warehouse['warehouse_extra_cost_currency']) {
            $warehouse_cost_currency = M('cmn_cd','tb_ms_')->where(['CD'=>$warehouse['warehouse_extra_cost_currency']])->getField('CD_VAL');
            $tax_rate = exchangeRate($warehouse_cost_currency,$warehouse['arrival_date_actual']);
            if(!$tax_rate) {
                $this->error = '获取入库额外费用汇率失败';
            }
            $cost_warehouse_rmb = $warehouse['warehouse_extra_cost']*$tax_rate;
        }else {
            $cost_warehouse_rmb = 0;
        }
        $ship = $this
            ->field('t.*,a.CD_VAL currency')
            ->alias('t')
            ->where(['id'=>$ship_id])
            ->join('left join tb_ms_cmn_cd a on a.CD = t.extra_cost_currency')
            ->find();
        if(!$ship) {
            $this->error = '发货信息不存在';
            return false;
        }
        //计算发货费用
        $cost_ship                  = $ship['extra_cost'];
        $cost_currency_ship         = $ship['currency'];
        if($ship['shipment_date'] == '' || $ship['shipment_date'] == '0000-00-00') {
            $ship_date = date('Y-m-d');
        }else {
            $ship_date = $ship['shipment_date'];
        }
        if($cost_ship && $cost_currency_ship) {
            $tax_rate_ship = exchangeRate($cost_currency_ship,$ship_date);
            if(!$tax_rate_ship) {
                $this->error = '获取发货汇率失败';
                return false;
            }
            $cost_ship_rmb = $cost_ship*$tax_rate_ship;
        }else {
            $cost_ship_rmb = 0;
        }
        //计算入库费用
        $relevance_id = $ship['relevance_id'];
        $order = M('order_detail','tb_pur_')
            ->alias('t')
            ->join('left join tb_pur_relevance_order a on a.order_id = t.order_id')
            ->where(['relevance_id'=>$relevance_id])
            ->find();
        //所有费用总和
        $goods_all      = M('goods_information','tb_pur_')->where(['relevance_id'=>$relevance_id])->getField('information_id,unit_price',true);
        $goods_ship     = M('ship_goods','tb_pur_')->where(['ship_id'=>$ship_id])->select();
        //计算发货商品价值总金额
        $amount_ship_rmb    = 0;
        foreach ($goods_ship as $k => $v) {
            $goods_ship[$k]['price_rmb'] = $goods_all[$v['information_id']]*$order['amount_currency_rate'];
            $goods_ship[$k]['money'] = $money = $goods_ship[$k]['price_rmb']*$v['ship_number'];
            $amount_ship_rmb += $money;
        }
        $cost_amount_proportion_order   = $order['logistics_rmb']/($order['amount_rmb']-$order['logistics_rmb']);
        $cost_amount_proportion_ship    = ($cost_ship_rmb+$cost_warehouse_rmb)/$amount_ship_rmb;
        $proportion_all                 = 1+$cost_amount_proportion_order+$cost_amount_proportion_ship;
        foreach ($goods_ship as $k => $v) {
            $goods_ship[$k]['warehouse_cost'] = round($proportion_all*$v['price_rmb']*$v['ship_number']/$warehouse['goods'][$v['id']]['warehouse_number'],2);
        }
        return $goods_ship;
    }

    public function warehouseVirtual($id) {
        $ship_info                              = $this->where(['id'=>$id])->find();
        $warehouse['id']                        = $id;
        $warehouse['sale_no_correct']           = $ship_info['sale_no'];
        $warehouse['need_warehousing_correct']  = $ship_info['need_warehousing'];
        $warehouse['warehouse_correct']         = $ship_info['warehouse'];
        $warehouse['arrival_date_actual']       = $ship_info['arrival_date'];
        $goods                                  = M('ship_goods','tb_pur_')->where(['ship_id'=>$id])->select();
        foreach ($goods as $v) {
            $goods_w['tax_rate']                = 'N001340600';
            $goods_w['warehouse_number']        = $v['ship_number'];
            $goods_w['number_info_warehouse']   = $v['number_info_ship'];
            $warehouse['goods'][$v['id']]       = $goods_w;
            $warehouse['warehouse_number']      += $v['ship_number'];
        }
        if($res = $this->warehouse($warehouse)) {
            return true;
        }else {
            return false;
        }
    }

    public function warehouse($warehouse_save) {
        $model          = new Model();
        $ship_m         = new TbPurShipModel();
        $ship_info      = $model->table('tb_pur_ship')->where(['id'=>$warehouse_save['id']])->find();
        $order_info     = $model
            ->table('tb_pur_order_detail t')
            ->join('left join tb_pur_relevance_order a on a.order_id = t.order_id')
            ->join('left join tb_pur_sell_information b on b.sell_id = a.sell_id')
            ->where(['relevance_id'=>$ship_info['relevance_id']])
            ->find();
        if(!$ship_info) {
            $this->error = '发货信息不存在';
            return false;
        }
        $cost_total     = 0;
        $cost           = $ship_m->allWarehouseCost($warehouse_save['id'],$warehouse_save);
        if(!$cost) {
            return false;
        }
        foreach ($cost as $k => $v) {
            $goods_info = $model
                ->table('tb_pur_ship_goods t')
                ->field('a.sku_information,a.unit_price,t.ship_number')
                ->join('left join tb_pur_goods_information a on a.information_id=t.information_id')
                ->where(['t.id'=>$v['id']])
                ->find();
            $warehouse_save['goods'][$v['id']]['warehouse_cost']    = $v['warehouse_cost'];
            $goods['GSKU']                                          = $warehouse_save['goods'][$v['id']]['sku_id']?$warehouse_save['goods'][$v['id']]['sku_id']:$goods_info['sku_information'];
            $goods['taxes']                                         = '0.'.rtrim(cdVal($warehouse_save['goods'][$v['id']]['tax_rate']),'%');
            $goods['price']                                         = $goods_info['unit_price'];
            $goods['currency_id']                                   = $order_info['amount_currency'];
            $goods['currency_time']                                 = $order_info['procurement_date'];
            $number_info                                            = json_decode($warehouse_save['goods'][$v['id']]['number_info_warehouse'],true);
            foreach ($number_info as $value) {
                $goods['send_num']                  = $value['number'];
                $goods['deadline_date_for_use']     = $value['expiration_date'];
                $bill_goods[]                       = $goods;
            }
            $cost_total                                             += $v['warehouse_cost'];
            $on_way['SKU_ID']                                       = $goods_info['sku_information'];
            $on_way['TYPE']                                         = 1;
            $on_way['on_way']                                       = $goods_info['ship_number'];
            $on_way['on_way_money']                                 = round($goods_info['ship_number']*$goods_info['unit_price']*$order_info['amount_currency_rate'],2);
            $on_way_all[]                                           = $on_way;
        }
        $goods_save                         = $warehouse_save['goods'];
        $warehouse_save['warehouse_user']   = $_SESSION['m_loginname'];
        $warehouse_save['warehouse_time']   = date('Y-m-d H:i:s');
        $warehouse_save['warehouse_status'] = 1;
        $res = $model->table('tb_pur_ship')->save($warehouse_save);
        if($res === false) {
            $this->error = '入库失败:入库信息保存失败';
            return false;
        }
        foreach ($goods_save as $k => $v) {
            if(!$v['tax_rate'] && $v['tax_rate'] !== 0) {
                $this->error = '请选择商品税率';
                return false;
            }
            if($v['difference_number'] != 0 && !$v['difference_reason']) {
                $this->error = '请选择差异原因';
                return false;
            }
            $res = $model->table('tb_pur_ship_goods')->where(['id'=>$k])->save($v);
            if($res === false) {
                $this->error = '入库失败:商品信息保存失败';
                return false;
            }
        }
        //如果已经全部发货,并且其他发货已经入库，则修改入库状态为入库完成
        if($order_info['ship_status'] == 2) {
            $count = $model->table('tb_pur_ship')->where(['warehouse_status'=>0,['id'=>['neq',$warehouse_save['id']],'relevance_id'=>$order_info['relevance_id']]])->count();
            if($count > 0) {
                $relevance_save['warehouse_status'] = 1;
            }else {
                $relevance_save['warehouse_status'] = 2;
            }
        }else {
            $relevance_save['warehouse_status'] = 1;
        }
        $res = M('relevance_order','tb_pur_')->where(['relevance_id'=>$order_info['relevance_id']])->save($relevance_save);
        if($res === false) {
            $this->error = '更新订单发货状态失败';
            return false;
        }
        $bill_data      = [
            'bill' => [
                'bill_type'             => 'N000940100',//收发类型，采购入库为N000940100固定不变
                'procurement_number'    => $order_info['procurement_number'], //b5c单号
                'warehouse_rule'        => $warehouse_save['need_warehousing_correct'], //是否入我方仓库
                'batch'                 => $ship_info['bill_of_landing'],//批次，这个待定
                'sale_no'               => $warehouse_save['sale_no_correct'],// 数据库无对应字段
                'channel'               => 'N000830100',// 默认
                'supplier'              => $order_info['supplier_id'],// 供应商（tb_crm_sp_supplier所对应的供应商 id）
                'warehouse_id'          => $warehouse_save['warehouse_correct'],// 仓库id（码表或数据字典对应的值）
                'total_cost'            => $cost_total,//入库总成本
                'SALE_TEAM'             => $order_info['sell_team'],//销售团队
                'SP_TEAM_CD'            => $order_info['payment_company'],//采购团队
                'CON_COMPANY_CD'        => $order_info['our_company'],//我方公司
            ],
            'guds' => $bill_goods
        ];
        $url = U('bill/out_and_in_storage','',true,false,true);
        $cookie = '';
        foreach ($_COOKIE as $k => $v) {
            $cookie .= $k.'='.$v.';';
        }
        $res = json_decode(curl_request($url,$bill_data,$cookie),true);

        // 根据报价单id（仅限创建采购单时用）或采购单id获取条款信息，有信息记录，走新流程，没有，则走旧流程
        $clauseInfo = (new Model())->table('tb_pur_clause')->where(['purchase_id' => $order_info['relevance_id']])->getField('id'); 

        if(in_array($res['code'],[10000000,10001011])) {
            if (!$clauseInfo) {
                //创建应付
                (new TbPurPaymentModel())->createPayableByWarehouse($warehouse_save['id']);
            }

            //减少商品在途
            $url        = U('bill/on_way_and_on_way_money','','',false,true);
            $res        = curl_request($url,$on_way_all);
            $res_arr    = json_decode($res,true);
            if($res_arr['code'] != 10000111) {
                $this->error = $res['msg'];
                ELog::add('增加在途数据失败：'.json_encode($on_way_all).$res,ELog::ERR);
                return false;
            }
            if(ACTION_NAME == 'warehouse') {
                (new TbPurActionLogModel())->addLog($ship_info['relevance_id']);
            }
            return true;
        }else {
            ELog::add(['msg'=>'调用入库接口失败','request'=>$bill_data,'response'=>$res],ELog::ERR);
            $this->error = $res['msg'];
            return false;
        }
        if (!$clauseInfo) {
            //创建应付
            (new TbPurPaymentModel())->createPayableByWarehouse($warehouse_save['id']);            
        }

        //减少商品在途
        $url        = U('bill/on_way_and_on_way_money','','',false,true);
        $res        = curl_request($url,$on_way_all);
        $res_arr    = json_decode($res,true);
        if($res_arr['code'] != 10000111) {
            $this->error = $res['msg'];
            ELog::add('增加在途数据失败：'.json_encode($on_way_all).$res,ELog::ERR);
            return false;
        }
        if($warehouse_save['need_warehousing_correct']) {

        }
        if(ACTION_NAME == 'warehouse') {
            (new TbPurActionLogModel())->addLog($ship_info['relevance_id']);
        }
        return true;
    }

    /**
     * 采购订单是否有发货
     * @param $relevance_id
     * @return bool
     */
    public function hasShipment($relevance_id) {
        $shipment = $this->where(['relevance_id'=>$relevance_id])->find();
        return $shipment ? true : false;
    }

}