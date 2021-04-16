<?php

class B5cInvoiceTaskModel extends DataBaseModel
{
    // 数据表名（不包含表前缀）
    protected $trueTableName = 'b5c_invoice_task';

    public $fields  = [
        "order_inc_id","order_id","platform_cd","store_id",
        "country_id","invoice_name", "type","status","download_count",
        "created_by","created_at","updated_by","updated_at", "deleted_by","deleted_at",
        "platform_name","country_name","store_name",'invoice_counter',
        "url","order_created_at"
    ];

    const TYPE_EXCEL = 0;
    const TYPE_PDF = 1;
    public static $type_str_map = [
        self::TYPE_PDF => 'pdf',
        self::TYPE_EXCEL => 'xls',
    ];


    const STATUS_NOT_FINISH = 0;
    
    public $allow_types = [
        self::TYPE_EXCEL, self::TYPE_PDF
    ];

}