<?php

/**
 * User: yuanshixiao
 * Date: 2017/6/20
 * Time: 14:01
 */
class TbPurInvoiceModel extends RelationModel
{
    protected $trueTableName                = 'tb_pur_invoice';

    protected $_link = [
        'TbPurInvoiceGoods' =>  [
            'mapping_type'  => HAS_MANY,
            'foreign_key'   => 'invoice_id',
            'mapping_name'  => 'invoice_goods',
        ],
    ];

    public static $status_unconfirmed    = 0;
    public static $status_confirmed      = 1;
    public static $status_return         = 2;

    public static $status = [
        0 => '待确认',
        1 => '已确认',
        2 => '已退回',
    ];

    public $error = '';

    public function __construct() {
        parent::__construct();
    }

    /**
     * 是否有被确认的发票
     * @param $relevance_id
     * @return bool
     */
    public function hasConfirmedInvoice($relevance_id) {
        $invoice = $this->where(['relevance_id'=>$relevance_id,'status'=>self::$status_confirmed])->find();
        return $invoice ? true : false;
    }

    /**
     * 删除采购订单所有发票
     * @param $relevance_id
     * @return mixed
     */
    public function deleteOrderInvoice($relevance_id) {
        $invoice_ids = $this->where(['relevance_id'=>$relevance_id])->getField('id',true);
        $res_invoice = $this->relation('invoice_goods')->where(['id'=>['in',$invoice_ids]])->delete();
    }

    /**
     * 获取发票总金额
     * @param $relevance_ids
     * @return float
     */
    public function getInvoiceTotalAmount($relevance_ids)
    {
        $result = $this->field('SUM(invoice_money) AS invoice_money, relevance_id')
            ->where(['relevance_id'=>['in',$relevance_ids], 'status'=>self::$status_confirmed])
            ->group('relevance_id')
            ->select();
        return array_column($result, 'invoice_money', 'relevance_id');
    }

}