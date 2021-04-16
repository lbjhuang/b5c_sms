<?php
import('ORG.Util.Date');// 导入日期类

class QuotationLclQuotationRelationsModel extends BaseModel
{
    // 数据表名（不包含表前缀）
    protected $trueTableName = 'tb_quote_lcl_quotation_relations';

    protected $fields = [
        'quote_lcl_id','quotation_id','creator_id','created_by','created_at',
    ];

    protected function _before_insert(&$data, $option)
    {
        $date = new Date();
        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
    }

    protected function _before_write(&$data) {
        $date = new Date();
        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
    }


}