<?php

/**
 * User: yuanshixiao
 * Date: 2017/6/20
 * Time: 13:33
 */
class TbPurRelevanceOrderModel extends RelationModel
{
    protected $trueTableName    = 'tb_pur_relevance_order';
    protected $_link = [
        'TbPurOrderDetail' =>  [
            'mapping_type' => HAS_ONE,
            'foreign_key' => 'order_id',
            'mapping_key' => 'order_id',
            'mapping_name' => 'orders',
        ],
        'TbPurSellInformation' =>  [
            'mapping_type' => HAS_ONE,
            'foreign_key' => 'sell_id',
            'mapping_key' => 'sell_id',
            'mapping_name' => 'sell_information',
        ],
    ];

    static $ship_status = [
        '0' => '待发货',
        '1' => '部分发货',
        '2' => '发货完成',
    ];

    static $warehouse_status = [
        '0' => '待入库',
        '1' => '部分入库',
        '2' => '入库完成',
    ];

    static $payment_status = [
        '0' => '待付款',
        '1' => '部分付款',
        '2' => '付款完成',
    ];
    static $invoice_status = [
        '0' => '待开票',
        '1' => '部分开票',
        '2' => '已开票',
    ];
    static $status_draft = 'N001320100';

    static $order_status = [
        'not_cancelled' => 'N001320300',
        'cancelled'     => 'N001320500',
    ];

    /**
     * 开票状态：未开票
     */
    const INVOICE_STATUS_NOTYET = 0;

    /**
     * 开票状态：部分开票
     */
    const INVOICE_STATUS_PART = 1;

    /**
     * 开票状态：已开票
     */
    const INVOICE_STATUS_DONE = 2;

}