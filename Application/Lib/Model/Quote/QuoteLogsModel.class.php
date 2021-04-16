<?php

import('ORG.Util.Date');// 导入日期类
class QuoteLogsModel extends BaseModel
{
    protected $trueTableName = 'tb_quote_logs';

    protected $fields = [
        'object_name','object_id','operation_detail','creator_id','created_by','created_at'
    ];

    # 对象类型，quote_lcl：拼柜，quotation：报价单',
    const OBJECT_NAME_QUOTE_LCL = 'quote_lcl';
    const OBJECT_NAME_QUOTATION = 'quotation';

    public static $allow_object_names = [
        self::OBJECT_NAME_QUOTE_LCL, self::OBJECT_NAME_QUOTATION
    ];
    /**
     * 添加前置函数
     * @param array $data
     * @param $option
     * @author Redbo He
     * @date 2020/11/13  10:37
     */
    protected function _before_insert(&$data, $option)
    {
        $date = new Date();
        $data['creator_id'] = session('user_id');
        $data['created_by'] = session('m_loginname');
        $data['created_at'] = $date->format();
    }
}