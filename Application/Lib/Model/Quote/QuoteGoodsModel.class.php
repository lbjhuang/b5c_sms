<?php

class QuoteGoodsModel  extends BaseModel
{
    // 数据表名（不包含表前缀）
    protected $trueTableName = 'tb_quote_goods';

    protected $fields = [
        "quotation_id","good_name","good_number","creator_id","created_by","created_at","sku_id",
    ];

    protected $_validate = [
        ['good_name', 'require', '商品名称不能为空！', 1, 'regex', 3],
        ['good_number', 'require', '商品数量不能为空！', 1, 'regex', 3],
    ];


}