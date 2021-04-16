<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/7
 * Time: 13:15
 */

class DemandGoodsModel extends BaseModel
{
    protected $trueTableName = 'tb_sell_demand_goods';
    protected $_validate = [
        ['search_id','require','SKUID/BarCode必填',0,'',4],
        ['sku_id',0,'SKUID必填',0,'notequal',4],
        ['auth_and_link','require','授权和链路必填',0,'',4],
    ];

    static $_validate_submit = [
        ['search_id','require','SKUID/BarCode必填',0,'',4],
        ['sku_id',0,'SKUID必填',0,'notequal',4],
        //['sell_price','0','销售单价（含税）必填',0,'notequal'], #10082  SCM创建需求支持销售单价（含税）或（不含税）为0商品20200320
        //['sell_price_not_contain_tax','0','销售单价（不含税）必填',0,'notequal'],
        ['auth_and_link','require','授权和链路必填',0,'',4],
    ];

    static $part_updatable_fields = ['sell_price'];
}
