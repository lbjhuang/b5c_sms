<?php
/**
 * User: yuanshixiao
 * Date: 2019/4/25
 * Time: 18:05
 */

class SettlementOrderOtherModel extends BaseModel
{
    protected $trueTableName = 'tb_op_settlement_order_other';
    protected $_validate = [];
    protected $_auto = [
        array('created_by','userName',1,'function'),
        array('updated_by','userName',3,'function'),
    ];
}