<?php
/**
 * User: yuanshixiao
 * Date: 2019/4/25
 * Time: 18:05
 */

class SettlementOrderSellModel extends BaseModel
{
    protected $trueTableName = 'tb_op_settlement_order_sell';
    protected $_validate = [];
    protected $_auto = [
        array('created_by','userName',1,'function'),
        array('updated_by','userName',3,'function'),
    ];
}