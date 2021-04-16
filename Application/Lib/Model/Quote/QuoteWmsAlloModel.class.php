<?php


class QuoteWmsAlloModel extends BaseModel
{
    protected $trueTableName = 'tb_quote_wms_allo';

    protected $fields = [
        "quotation_id","allo_id","allo_no","creator_id","created_by","created_at",
    ];

}