<?php
/**
 * User: yuanshixiao
 * Date: 2018/3/8
 * Time: 13:26
 */

class QuotationGoodsModel extends BaseModel
{
    protected $trueTableName = 'tb_sell_quotation_goods';

    protected $_validate = [
        ['demand_goods_id','require','需求商品id必填',1,'',4],
        ['supply_number','require','可供数量必填',1,'',4],
        ['purchase_price','require','采购单价（含税）必填',1,'',4],
        ['purchase_price_not_contain_tax','require','采购单价（不含税）必填',1,'',4],
        ['drawback_percent','require','退税比例必填',1,'',4],
        ['auth_and_link','require','授权和链路必填',1,'',4]
    ];
}