<?php

/**
 * Created by fuming.
 * User: Administrator
 * Date: 2019/8/26
 * Time: 10:17
 */
class TbPurPaymentAuditModel extends Model
{
    protected $trueTableName = 'tb_pur_payment_audit';
    public static $status_no_confirmed = 0;//待提交
    public static $status_no_payment = 1;//待确认付款账户
    public static $status_no_billing = 2;//待出账
    public static $status_finished = 3;//已完成
    public static $status_accounting_audit = 4;//待审核
    public static $status_deleted = 5;//已删除
    public static $status_business_audit = 6;//待业务审核
    public static $status_kyriba_not_pass = 7;//kyriba审核失败（kyriba审核未通过）
    public static $status_payment_failed = 8;//银行付款失败（即付款失败）
    public static $status_kyriba_wait_receive = 9;//待kyriba接收
    public static $status_kyriba_receive_failed = 10;//kyriba接收失败

    public static $status_after_payment = 11; // 泛指 已完成，kyriba接收失败，kyriba审核未通过，付款失败中的一种，仅用于#11276 付款退回流程优化 

    public static $channel_domestic_alipay = 'N001000200';//国内支付宝
    public static $channel_bank = 'N001000301';//银行
    public static $way_transfer = 'N003020001';//转账
    public static $way_order_pay = 'N003020002';//按订单支付
    public static $way_trade_no = 'N003020005';//按交易号退款

    public static $source_payable = 'N003010001';//来源-采购应付
    public static $source_allo_payable = 'N003010002';//来源-调拨费用单
    public static $source_b2c_payable = 'N003010003';  //来源-b2c退款
    public static $source_transfer_payable = 'N003010005';  //来源-转账换汇
    public static $source_transfer_payable_indirect = 'N003010006';  //来源-转账换汇

    public static $accounting_audit_user = 'Astor.Zhang';  //会计审核人
    public static $attendance_audit_user = 'Kathy.Tang';  //考勤审核人
    public static $source_general_payable = 'N003010004';  //来源-一般付款申请

    // 退回流程 退回前状态 1.【付款被退回，重新提交】 与 【重新提交申请】
    public static $return_type_before_confirmed = 'normal_return'; // 待审核 待确认付款账户 
    public static $return_type_after_confirmed = 'kyriba_return'; // kyriba接受失败，kyriba审核未通过，付款失败，已完成

    //一般付款费用类型
    public static $cost_salary_type = 'N002930007';//薪资类付款

    public static $status_map = [
        '待确认',
        '待确认付款账户',
        '待出账',
        '已完成',
        '待审核',
        '已删除',
        '待业务审核',
        'Kyriba审核未通过',
        '付款失败',
        '待kyriba接收',
        'kyriba接收失败'
    ];

    // 售后单 售后状态对应CODE  // 0.待确认,1.待付款，2.待出账,3.已完成,4.待会计审核,5.已删除
    public static $refund_status_map = [
        0 => "N002800009",
        1 => "N002800009",
        2 => "N002800009",
        3 => "N002800010",
        4 => "N002800009",
        5 => "N002800012",
        6 => "N002800012",
    ];
    //售后单 审核状态对应CODE
    public static $audit_status_map = [
        0 => "N003170006",
        1 => "N003170006",
        2 => "N003170006",
        3 => "N003170008",
        4 => "N003170004",
        5 => "N003170001",
        6 => "N003170001",
    ];

    //付款类型
    public static $pay_type_map = [
        '否',
        '是',
        '暂未确定',
    ];

    //erp和kyriba 手续费承担方式映射
    public static $commission_map = [
       'N003320001' => '000',//无
       'N003320002' => '001',//收付方共担
       'N003320003' => '002',//付方承担
       'N003320004' => '003',//收方承担
    ];

    /**
     * 获取查询条件映射
     * @param $payment_channel_cd 支付渠道
     * @param $payment_way_cd 支付方式
     * @return mixed
     */
    public static function getMergedPaymentBillSearchMap($payment_channel_cd, $payment_way_cd)
    {
        //银行转账
        $map[self::$way_transfer][self::$channel_bank] = [
            'supplier_collection_account' => 'pa.supplier_collection_account',
            'supplier_opening_bank'       => 'pa.supplier_opening_bank',
            'supplier_card_number'        => 'pa.supplier_card_number',
            'supplier_swift_code'         => 'pa.supplier_swift_code',
        ];
        //国内支付宝-转账
        $map[self::$way_transfer][self::$channel_domestic_alipay] = [
            'collection_account'   => 'pa.collection_account',
            'collection_user_name' => 'pa.collection_user_name',
        ];
        //国内支付宝-按订单支付
        $map[self::$way_order_pay][self::$channel_domestic_alipay] = [
            'platform_cd' => 'pa.platform_cd',
        ];

        return $map[$payment_way_cd][$payment_channel_cd] ? : [];
    }

    /**
     * 获取验证数据规则
     * @param $payment_channel_cd 支付渠道
     * @param $payment_way_cd 支付方式
     * @return mixed
     */
    public static function getValidateData($payment_channel_cd, $payment_way_cd)
    {
        //银行转账
        $validate[self::$way_transfer][self::$channel_bank] = [
            'rule' => [
                'supplier_collection_account' => 'required',
                'supplier_opening_bank'       => 'required',
                'supplier_card_number'        => 'required',
                //'supplier_swift_code'         => 'required',
                'our_company_cd'              => 'sometimes|required',
                'amount_currency'             => 'sometimes|required',
                //'bank_settlement_code'        => 'required',
                'bank_address'                => 'required',
//                'city'                        => 'required',
//                'bank_address_detail'         => 'required',
//                'bank_postal_code'            => 'required',
                'account_currency'            => 'required',
//                'account_type'                => 'required',
            ],
            'attributes' => [
                'supplier_collection_account' => '收款账户名',
                'supplier_opening_bank'       => '供应商开户行',
                'supplier_card_number'        => '银行账号',
                //'supplier_swift_code'         => '供应商swift code',
                'our_company_cd'              => '我方公司',
                'amount_currency'             => '应付单关联采购单币种',
                //'bank_settlement_code'        => '收款银行本地结算代码',
                'bank_address'                => '收款银行地址',
//                'city'                        => '收款银行地址id',
//                'bank_address_detail'         => '收款银行详细地址',
//                'bank_postal_code'            => '收款银行邮编',
                'account_currency'            => '收款账号币种CD',
//                'account_type'                => '收款账户种类CD',
            ]
        ];
        //国内支付宝-转账
        $validate[self::$way_transfer][self::$channel_domestic_alipay] = [
            'rule' => [
                'collection_account'   => 'required',
                'collection_user_name' => 'required',
            ],
            'attributes' => [
                'collection_account'   => '支付渠道收款账号',
                'collection_user_name' => '支付渠道收款用户名',
            ]
        ];
        //国内支付宝-按订单支付
        $validate[self::$way_order_pay][self::$channel_domestic_alipay] = [
            'rule' => [
                'platform_cd'       => 'required|size:10',
//                'store_name'        => 'sometimes|required',
                'platform_order_no' => 'sometimes|required',
            ],
            'attributes' => [
                'platform_cd'       => '平台名称',
//                'store_name'        => '店铺名称',
                'platform_order_no' => '平台订单号',
            ]
        ];
        $rule       = $validate[$payment_way_cd][$payment_channel_cd]['rule'] ? : [];
        $attributes = $validate[$payment_way_cd][$payment_channel_cd]['attributes'] ? : [];
        return [$rule, $attributes];
    }

    public static function getPaymentAccount($payment_channel_cd)
    {
        $map = [
            self::$channel_domestic_alipay => 'b5mtrade2@gshopper.com'
        ];
        return $map[$payment_channel_cd];
    }

    public static function filterData(&$data)
    {
        if ($data['payment_channel_cd'] == self::$channel_bank) {
            $data['platform_cd']          = null;
            $data['store_name']           = null;
            $data['platform_order_no']    = null;
            $data['collection_account']   = null;
            $data['collection_user_name'] = null;
        }
        if ($data['payment_channel_cd'] == self::$channel_domestic_alipay && $data['payment_way_cd'] == self::$way_transfer) {
            $data['platform_cd']                 = null;
            $data['store_name']                  = null;
            $data['platform_order_no']           = null;
            $data['supplier_collection_account'] = '';
            $data['supplier_opening_bank']       = '';
            $data['supplier_card_number']        = '';
            $data['supplier_name']               = '';
            $data['supplier_swift_code']         = '';

        }
        if ($data['payment_channel_cd'] == self::$channel_domestic_alipay && $data['payment_way_cd'] == self::$way_order_pay) {
            $data['collection_account']          = null;
            $data['collection_user_name']        = null;
            $data['supplier_collection_account'] = '';
            $data['supplier_opening_bank']       = '';
            $data['supplier_card_number']        = '';
            $data['supplier_name']               = '';
            $data['supplier_swift_code']         = '';
        }
        return $data;
    }

}