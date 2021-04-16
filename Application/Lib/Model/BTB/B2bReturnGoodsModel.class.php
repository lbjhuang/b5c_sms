<?php
/**
 * User: yuanshixiao
 * Date: 2019/5/27
 * Time: 18:05
 */

class B2bReturnGoodsModel extends BaseModel
{
    protected $trueTableName = 'tb_b2b_return_goods';
    protected $_validate = [];
    protected $_auto = [
        array('created_by','userName',1,'function'),
        array('updated_by','userName',3,'function'),
    ];
}