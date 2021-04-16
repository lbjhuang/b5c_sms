<?php
/**
 * User: yuanshixiao
 * Date: 2019/4/16
 * Time: 13:19
 */

class ConversionModel extends BaseModel
{

    protected $trueTableName='tb_scm_conversion';

    protected $_validate = [
        ['conversion_no','require','转换单号必须',1,'regex',1],
        ['type_cd','require','转换类型必须',1,'regex',1],
        ['affect_supplier_settlement','require','是否影响供应商结算必须',1,'regex',1],
        ['sales_team_cd','require','归属销售团队必须',1,'regex',1],
        ['warehouse_cd','require','归属仓库必须',1,'regex',1],
    ];

    protected $_auto = [
        array('status_cd','N002750001'),
        array('created_by','userName',1,'function'),
        array('updated_by','userName',3,'function'),
    ];

    public static $type = [
        'quality_to_broken' => 'N002720001',
        'broken_to_quality' => 'N002720002',
    ];

    public $status = [
        'to_approve'    => 'N002750001',
        'success'       => 'N002750002',
        'refused'       => 'N002750003',
        'drawback'      => 'N002750004',
    ];

}