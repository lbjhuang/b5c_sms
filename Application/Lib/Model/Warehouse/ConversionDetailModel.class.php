<?php
/**
 * User: yuanshixiao
 * Date: 2019/4/16
 * Time: 13:19
 */

class ConversionDetailModel extends BaseModel
{

    protected $trueTableName='tb_scm_conversion_details';

    protected $_validate = [
        ['conversion_id','require','转换单id必须',1,'regex',1],
        ['sku_id','require','sku_id必须',1,'regex',1],
        ['stream_id','require','stream_id必须',1,'regex',1],
        ['batch_id','require','批次id必须',1,'regex',1],
        ['number','require','商品数量必须',1,'regex',1],
    ];

    protected $_auto = [
        array('created_by','userName',1,'function'),
        array('updated_by','userName',3,'function'),
    ];

}