<?php

/**
 * User: yuanshixiao
 * Date: 2017/6/20
 * Time: 14:01
 */
class TbPurOrderDetailModel extends Model
{
    protected $trueTableName    = 'tb_pur_order_detail';

    public static $payment_period = [
        '0' => '请选择期数',
        '1' => '一次性付清',
        '2' => '分两期付清',
        '3' => '分三期付清',
    ];

    public static $payment_type = [
        '0' => '按指定时间付款',
        '1' => '按实际情况付款',
    ];

    public static $payment_node = [
        '0' => '第##期节点',
        '1' => '合同后',
        '2' => '发货后',
        '3' => '入库后'
    ];

    public static $payment_day_type = [
        0 => '天',
//        1 => '工作日'
    ];

    public static $purchase_type = [
        'N001890100' => '普通采购',
        'N001890200' => '线上采购'
    ];

    public static $payment_percent = [5,10,15,20,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100];

    public static $payment_days = [7,15,30,45,60,0];

    public function supplierHasCooperate($sp_charter_no = '') {
        if($sp_charter_no == '') {
            $this->error = '营业执照号不能为空';
            return false;
        }else {
            $res = $this->alias('t')
                ->join('left join tb_pur_relevance_order a on a.order_id=t.order_id')
                ->where(['sp_charter_no'=>$sp_charter_no,'order_status'=>'N001320300'])
                ->find();
            if($res) {
                return true;
            }else {
                return false;
            }
        }
    }

    /**采购入库超时列表
     * @return array
     */
    public static function getOutdateList()
    {
        $date = date('Y-m-d');
        $list = M('pur_ship', 'tb_')->alias('t1')
            ->field([
                't3.procurement_number',
                't3.online_purchase_order_number',
                't1.warehouse_id',
                't1.bill_of_landing',
                't3.supplier_id',
                't3.payment_company',
                't2.prepared_by',
                't4.CD_VAL as warehouse',
                't1.arrival_date',
                "datediff('$date',t1.arrival_date) as outdate",
            ])
            ->join('left join tb_pur_relevance_order t2 on t2.relevance_id=t1.relevance_id')
            ->join('left join tb_pur_order_detail t3 on t3.order_id=t2.order_id')
            ->join('left join tb_ms_cmn_cd t4 on t4.CD=t1.warehouse')
            ->where(['t1.create_time' => ['egt', '2018-08-01'], 't1.warehouse_status' => ['neq', 1], '_string' => "datediff('$date',t1.arrival_date) > 0"])
            ->select();
        return $list;
    }

    /**
     * 发货超时
     * @return array
     */
    public static function getShipOutdateList()
    {
        $date = date('Y-m-d');
        $list = M('pur_relevance_order', 'tb_')->alias('t1')
            ->field([
                't2.procurement_number',//采购单号
                't2.online_purchase_order_number as po',//po单号
                't2.supplier_id as supplier',//供应商
                'cd.CD_VAL as purchase_team',//采购团队
                't1.prepared_by as purchaser',//采购同事
                't3.ship_time',//预计发货日期
                "datediff('$date',t3.ship_time) as outdate",
            ])
            ->join('left join tb_pur_order_detail t2 on t2.order_id=t1.order_id')
            ->join('left join tb_sell_quotation t3 on t3.quotation_code=t2.procurement_number')
            ->join('left join tb_ms_cmn_cd cd on cd.CD=t2.payment_company')
            ->where(['t1.creation_time' => ['egt', '2018-08-01'], 't1.ship_status' => ['in', [0, 1]], '_string' => "datediff('$date',t3.ship_time) > 0", 't1.order_status' => ['neq', 'N001320500']])//隐藏已取消订单
            ->select();
        return $list;
    }
}