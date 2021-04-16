<?php
/**
 * User: yuanshixiao
 * Date: 2017/8/15
 * Time: 14:32
 */

class TbPurSpecialOfferModel extends RelationModel
{
    protected $tablePrefix  = 'tb_pur_';
    protected $tableName    = 'special_offer';

    protected $_link = [
        'TbPurSpecialOfferGoods' =>  [
            'mapping_type'  => HAS_MANY,
            'foreign_key'   => 'special_offer_id',
            'mapping_name'  => 'goods',
        ]
    ];

    public $_validate = [
        array('supplier','require','供应商必须！'),
        array('has_invoice','require','是否有发票！'),
        array('country','require','地址必须！'),
        array('expected_ship_time','require','预计发货时间必须！'),
        array('purchase_staff','require','采购同事必须！'),
        array('purchase_team','require','采购团队必须！'),
    ];

    public static $has_invoice = [
        1 => '有发票',
        0 => '无发票'
    ];

    public function detail($id , $need_goods = false) {
        if($need_goods) {
            $detail = $this->where(['id'=>$id])->relation(true)->find();
        }else {
            $detail = $this->where(['id'=>$id])->find();
        }
        return $detail;
    }

}